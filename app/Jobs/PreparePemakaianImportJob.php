<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\XLSX\Reader;
use Throwable;

/**
 * Tahap 1 import: baca file xlsx sekali (streaming, tanpa memuat sheet ke memori),
 * deteksi kolom dari baris header, lalu tulis CSV per chunk.
 *
 * Kolom periode TIDAK dipatok di config: semua kolom header yang berbentuk YYYYMM
 * (mis. 202401 … 202701) otomatis dikenali, jadi menambah kolom bulan baru di file
 * cukup dengan mengimpor ulang — aplikasi ikut menampilkan kolom baru itu.
 *
 * Tiap chunk menghasilkan dua CSV:
 *   pelanggan-00000.csv : idpel,nama,tarif,daya,jumlah_periode
 *   pemakaian-00000.csv : idpel,periode,kwh
 */
class PreparePemakaianImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 7200;

    public int $tries = 2;

    /** Jeda sebelum percobaan berikutnya (detik). */
    public function backoff(): int
    {
        return 15;
    }

    public function __construct(private readonly int $importBatchId) {}

    public function handle(): void
    {
        $batch = ImportBatch::query()->findOrFail($this->importBatchId);
        $disk = Storage::disk('local');

        try {
            $batch->update([
                'status' => ImportBatch::STATUS_PREPARING,
                'started_at' => now(),
                'processed_rows' => 0,
                'chunks_total' => 0,
                'error' => null,
            ]);

            $disk->makeDirectory($batch->chunkDir());

            $chunkIndex = $this->streamToChunks($batch);

            $batch->update([
                'chunks_total' => $chunkIndex,
                'status' => ImportBatch::STATUS_PROCESSING,
            ]);

            if ($chunkIndex === 0) {
                ImportBatch::cleanupFiles($batch->id, $batch->file_path);
                $batch->update([
                    'status' => ImportBatch::STATUS_COMPLETED,
                    'finished_at' => now(),
                ]);

                return;
            }

            $this->dispatchChunkJobs($batch, $chunkIndex);
        } catch (Throwable $e) {
            ImportBatch::cleanupFiles($batch->id, $batch->file_path);

            $batch->update([
                'status' => ImportBatch::STATUS_FAILED,
                'error' => mb_substr($e->getMessage(), 0, 500),
                'finished_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Baca sheet sumber baris per baris; tulis tiap `chunk_size` baris ke sepasang CSV.
     *
     * @return int jumlah chunk yang ditulis
     */
    private function streamToChunks(ImportBatch $batch): int
    {
        $disk = Storage::disk('local');
        $chunkSize = max(100, (int) ($batch->chunk_size ?: config('import.chunk_size')));
        $sheetName = (string) config('import.sheet');
        $startRow = (int) config('import.data_start_row');

        $reader = new Reader();
        $reader->open($disk->path($batch->file_path));

        $petaPeriode = [];
        $kolom = ['idpel' => 0, 'nama' => 1, 'tarif' => 2, 'daya' => 3];
        $chunkIndex = 0;
        $totalRows = 0;
        $handlePelanggan = null;
        $handlePemakaian = null;

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                if ($sheet->getName() !== $sheetName) {
                    continue;
                }

                foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                    $values = $row->toArray();

                    if ($rowIndex === 1) {
                        [$petaPeriode, $kolom] = $this->bacaHeader($values);
                        $batch->update(['periode_list' => array_values($petaPeriode)]);

                        continue;
                    }

                    if ($rowIndex < $startRow) {
                        continue;
                    }

                    $idpel = trim((string) ($values[$kolom['idpel']] ?? ''));

                    if ($idpel === '') {
                        continue;
                    }

                    if ($handlePelanggan === null) {
                        $handlePelanggan = fopen($disk->path($batch->chunkPelangganPath($chunkIndex)), 'wb');
                        $handlePemakaian = fopen($disk->path($batch->chunkPemakaianPath($chunkIndex)), 'wb');
                    }

                    $jumlahPeriode = 0;

                    foreach ($petaPeriode as $indexKolom => $periode) {
                        $nilai = $values[$indexKolom] ?? null;

                        if ($nilai === null || $nilai === '') {
                            continue;
                        }

                        fputcsv(
                            $handlePemakaian,
                            [$idpel, $periode, is_numeric($nilai) ? round((float) $nilai, 2) : $nilai],
                            escape: '\\',
                        );
                        $jumlahPeriode++;
                    }

                    $nama = $values[$kolom['nama']] ?? null;
                    $tarif = $values[$kolom['tarif']] ?? null;
                    $daya = $values[$kolom['daya']] ?? null;

                    fputcsv($handlePelanggan, [
                        $idpel,
                        $nama === null ? '' : trim((string) $nama),
                        $tarif === null ? '' : trim((string) $tarif),
                        is_numeric($daya) ? (int) $daya : '',
                        $jumlahPeriode,
                    ], escape: '\\');

                    $totalRows++;

                    if ($totalRows % $chunkSize === 0) {
                        fclose($handlePelanggan);
                        fclose($handlePemakaian);
                        $handlePelanggan = null;
                        $handlePemakaian = null;
                        $chunkIndex++;

                        $batch->update(['total_rows' => $totalRows, 'chunks_total' => $chunkIndex]);
                    }
                }

                break;
            }
        } finally {
            $reader->close();

            if ($handlePelanggan !== null) {
                fclose($handlePelanggan);
                fclose($handlePemakaian);
                $chunkIndex++;
            }
        }

        $batch->update(['total_rows' => $totalRows, 'chunks_total' => $chunkIndex]);

        return $chunkIndex;
    }

    /**
     * Deteksi kolom dari baris header:
     *   - kolom periode: header berbentuk YYYYMM (202401, 202701, …) -> dipetakan per index kolom
     *   - kolom identitas: dicari lewat nama header (IDPEL/NAMA/TARIF/DAYA), fallback ke A..D
     *
     * @param  array<int, mixed>  $values
     * @return array{0: array<int, string>, 1: array<string, int>}
     */
    private function bacaHeader(array $values): array
    {
        $petaPeriode = [];
        $namaKolom = [];

        foreach ($values as $index => $nilai) {
            if ($nilai === null) {
                continue;
            }

            $teks = trim((string) $nilai);

            if ($teks === '') {
                continue;
            }

            if (preg_match('/^((?:19|20)\d{2})(0[1-9]|1[0-2])$/', $teks) === 1) {
                $petaPeriode[(int) $index] = $teks;

                continue;
            }

            $namaKolom[strtoupper($teks)] = (int) $index;
        }

        ksort($petaPeriode);

        return [
            $petaPeriode,
            [
                'idpel' => $namaKolom['IDPEL'] ?? 0,
                'nama' => $namaKolom['NAMA'] ?? 1,
                'tarif' => $namaKolom['TARIF'] ?? 2,
                'daya' => $namaKolom['DAYA'] ?? 3,
            ],
        ];
    }

    private function dispatchChunkJobs(ImportBatch $batch, int $chunkIndex): void
    {
        $jobs = [];

        for ($index = 0; $index < $chunkIndex; $index++) {
            $jobs[] = new ImportPemakaianChunkJob($batch->id, $index);
        }

        $batchId = $batch->id;
        $filePath = $batch->file_path;

        Bus::batch($jobs)
            ->name('import-pemakaian-'.$batchId)
            ->then(function () use ($batchId, $filePath): void {
                ImportBatch::cleanupFiles($batchId, $filePath);

                ImportBatch::query()->whereKey($batchId)->update([
                    'status' => ImportBatch::STATUS_COMPLETED,
                    'finished_at' => now(),
                ]);
            })
            ->catch(function (Throwable $e) use ($batchId, $filePath): void {
                ImportBatch::cleanupFiles($batchId, $filePath);

                ImportBatch::query()->whereKey($batchId)->update([
                    'status' => ImportBatch::STATUS_FAILED,
                    'error' => mb_substr($e->getMessage(), 0, 500),
                    'finished_at' => now(),
                ]);
            })
            ->dispatch();
    }
}
