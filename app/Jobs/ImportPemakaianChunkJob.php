<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Tahap 2 import: satu job = sepasang CSV chunk hasil PreparePemakaianImportJob
 * (pelanggan-XXXXX.csv + pemakaian-XXXXX.csv).
 *
 * Insert dikerjakan per kelompok 1.000 pelanggan per transaksi supaya transaksi
 * pendek dan progres cepat terlihat. insertOrIgnore membuat job aman diulang
 * (tidak menabrak unique idpel / unique (idpel, periode)).
 */
class ImportPemakaianChunkJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public int $tries = 3;

    /** Jeda antar percobaan (detik) kalau job gagal. */
    public function backoff(): int
    {
        return 30;
    }

    /** Jumlah pelanggan per transaksi. */
    private const BARIS_PER_TRANSAKSI = 1000;

    /** Jumlah baris pemakaian per statement insert. */
    private const INSERT_CHUNK_PEMAKAIAN = 5000;

    public function __construct(
        private readonly int $importBatchId,
        private readonly int $chunkIndex,
    ) {}

    public function handle(): void
    {
        $batch = ImportBatch::query()->find($this->importBatchId);

        if ($batch === null) {
            return;
        }

        $disk = Storage::disk('local');
        $pathPelanggan = $disk->path($batch->chunkPelangganPath($this->chunkIndex));
        $pathPemakaian = $disk->path($batch->chunkPemakaianPath($this->chunkIndex));

        if (! is_file($pathPelanggan)) {
            return;
        }

        $handlePelanggan = fopen($pathPelanggan, 'rb');
        $handlePemakaian = is_file($pathPemakaian) ? fopen($pathPemakaian, 'rb') : null;
        $now = now()->toDateTimeString();

        $pelangganRows = [];
        $pemakaianRows = [];

        while (($baris = fgetcsv($handlePelanggan, escape: '\\')) !== false) {
            $idpel = trim((string) ($baris[0] ?? ''));

            if ($idpel === '') {
                continue;
            }

            $jumlahPeriode = (int) ($baris[4] ?? 0);

            $pelangganRows[] = [
                'idpel' => $idpel,
                'nama' => ($baris[1] ?? '') === '' ? null : $baris[1],
                'tarif' => ($baris[2] ?? '') === '' ? null : $baris[2],
                'daya' => is_numeric($baris[3] ?? null) ? (int) $baris[3] : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            for ($i = 0; $i < $jumlahPeriode; $i++) {
                $pemakaian = $handlePemakaian === null ? false : fgetcsv($handlePemakaian, escape: '\\');

                if ($pemakaian === false) {
                    break;
                }

                $pemakaianRows[] = [
                    'idpel' => $pemakaian[0],
                    'periode' => $pemakaian[1],
                    'kwh' => is_numeric($pemakaian[2] ?? null) ? round((float) $pemakaian[2], 2) : null,
                ];
            }

            if (count($pelangganRows) >= self::BARIS_PER_TRANSAKSI) {
                $this->simpan($batch, $pelangganRows, $pemakaianRows);
                $pelangganRows = [];
                $pemakaianRows = [];
            }
        }

        fclose($handlePelanggan);

        if ($handlePemakaian !== null) {
            fclose($handlePemakaian);
        }

        if ($pelangganRows !== []) {
            $this->simpan($batch, $pelangganRows, $pemakaianRows);
        }
    }

    /**
     * Simpan satu kelompok baris + tambah progres (atomik) dalam satu transaksi.
     *
     * @param  array<int, array<string, mixed>>  $pelanggan
     * @param  array<int, array<string, mixed>>  $pemakaian
     */
    private function simpan(ImportBatch $batch, array $pelanggan, array $pemakaian): void
    {
        $jumlahBaris = count($pelanggan);

        DB::transaction(function () use ($batch, $pelanggan, $pemakaian, $jumlahBaris): void {
            DB::table('pelanggan')->insertOrIgnore($pelanggan);

            foreach (array_chunk($pemakaian, self::INSERT_CHUNK_PEMAKAIAN) as $chunk) {
                DB::table('pemakaian_bulanan')->insertOrIgnore($chunk);
            }

            ImportBatch::query()->whereKey($batch->id)->increment('processed_rows', $jumlahBaris);
        });
    }
}
