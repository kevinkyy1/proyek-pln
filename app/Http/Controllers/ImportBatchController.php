<?php

namespace App\Http\Controllers;

use App\Jobs\PreparePemakaianImportJob;
use App\Models\ImportBatch;
use App\Support\XlsxSheetInfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ImportBatchController extends Controller
{
    /** Folder penyimpanan potongan upload (relatif terhadap disk 'local'). */
    private const UPLOAD_DIR = 'imports/upload';

    /**
     * Data riwayat import untuk pemantauan progres (dipanggil berkala oleh halaman).
     */
    public function status(): JsonResponse
    {
        $batches = ImportBatch::query()
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'running' => $batches->contains(fn (ImportBatch $batch) => $batch->isRunning()),
            'html' => view('data-table.partials.riwayat-import', ['batches' => $batches])->render(),
        ]);
    }

    /**
     * Upload biasa (satu request) — dipakai untuk file kecil.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:204800'],
        ], attributes: ['file' => 'file Excel']);

        $uploaded = $validated['file'];
        $path = $uploaded->store('imports', 'local');

        $batch = $this->mulaiImport($path, $uploaded->getClientOriginalName());

        return redirect()
            ->route('home')
            ->with('status', $this->pesanAntrean($batch));
    }

    /**
     * Upload berpotong (file besar dikirim per bagian oleh browser).
     * Potongan terakhir menggabungkan semua bagian lalu memulai import.
     */
    public function storeChunk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'chunk' => ['required', 'file', 'max:12288'],
            'upload_id' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{6,64}$/'],
            'chunk_index' => ['required', 'integer', 'min:0'],
            'total_chunks' => ['required', 'integer', 'min:1', 'max:5000'],
            'file_name' => ['required', 'string', 'max:200'],
            'total_size' => ['nullable', 'integer', 'min:0'],
        ]);

        $uploadId = (string) $data['upload_id'];
        $index = (int) $data['chunk_index'];
        $total = (int) $data['total_chunks'];
        $disk = Storage::disk('local');

        if ($index >= $total) {
            return response()->json(['message' => 'Indeks potongan tidak valid.'], 422);
        }

        $disk->putFileAs(self::UPLOAD_DIR.'/'.$uploadId, $data['chunk'], sprintf('%05d.part', $index));

        // masih ada potongan berikutnya
        if ($index < $total - 1) {
            return response()->json([
                'status' => 'partial',
                'diterima' => $index + 1,
                'total' => $total,
            ]);
        }

        try {
            $path = $this->gabungPotongan($uploadId, $total, (string) $data['file_name'], $data['total_size'] ?? null);
        } catch (Throwable $e) {
            $disk->deleteDirectory(self::UPLOAD_DIR.'/'.$uploadId);

            return response()->json(['message' => $e->getMessage()], 422);
        }

        $batch = $this->mulaiImport($path, (string) $data['file_name']);
        $pesan = $this->pesanAntrean($batch);

        session()->flash('status', $pesan);

        return response()->json([
            'status' => 'selesai',
            'redirect' => route('home'),
            'message' => $pesan,
        ]);
    }

    /**
     * Gabungkan potongan menjadi satu file xlsx dan hapus bagian sementaranya.
     */
    private function gabungPotongan(string $uploadId, int $total, string $namaAsli, ?int $totalSize): string
    {
        $ekstensi = strtolower(pathinfo($namaAsli, PATHINFO_EXTENSION));

        if (! in_array($ekstensi, ['xlsx', 'xls'], true)) {
            throw new RuntimeException('Hanya file .xlsx / .xls yang diterima.');
        }

        $disk = Storage::disk('local');
        $dir = self::UPLOAD_DIR.'/'.$uploadId;
        $tujuan = 'imports/'.$uploadId.'.'.$ekstensi;
        $keluar = fopen($disk->path($tujuan), 'wb');

        if ($keluar === false) {
            throw new RuntimeException('Gagal membuat file gabungan.');
        }

        try {
            for ($i = 0; $i < $total; $i++) {
                $bagian = $disk->path($dir.'/'.sprintf('%05d.part', $i));

                if (! is_file($bagian)) {
                    throw new RuntimeException('Potongan ke-'.($i + 1).' dari '.$total.' tidak ditemukan.');
                }

                $masuk = fopen($bagian, 'rb');
                stream_copy_to_stream($masuk, $keluar);
                fclose($masuk);
            }
        } finally {
            fclose($keluar);
        }

        $disk->deleteDirectory($dir);

        $ukuranAktual = (int) $disk->size($tujuan);

        if ($totalSize !== null && $totalSize > 0 && $ukuranAktual !== $totalSize) {
            $disk->delete($tujuan);

            throw new RuntimeException(sprintf(
                'Ukuran hasil gabungan tidak cocok (diterima %s byte, seharusnya %s byte) — upload terputus.',
                number_format($ukuranAktual, 0, ',', '.'),
                number_format($totalSize, 0, ',', '.'),
            ));
        }

        if (! XlsxSheetInfo::isValid($disk->path($tujuan))) {
            $disk->delete($tujuan);

            throw new RuntimeException('File hasil gabungan bukan xlsx yang valid.');
        }

        return $tujuan;
    }

    /**
     * Buat batch import, kosongkan data lama (replace total), lalu dispatch job pembaca file.
     */
    private function mulaiImport(string $path, string $namaFile): ImportBatch
    {
        $chunkSize = (int) config('import.chunk_size');
        $headerRows = (int) config('import.data_start_row') - 1;

        $totalRows = XlsxSheetInfo::totalRows(Storage::disk('local')->path($path), (string) config('import.sheet'));
        $totalRows = $totalRows > $headerRows ? $totalRows - $headerRows : 0;

        $batch = ImportBatch::query()->create([
            'file_name' => $namaFile,
            'file_path' => $path,
            'total_rows' => $totalRows,
            'processed_rows' => 0,
            'chunks_total' => $totalRows > 0 ? (int) ceil($totalRows / $chunkSize) : 0,
            'chunk_size' => $chunkSize,
            'status' => ImportBatch::STATUS_QUEUED,
        ]);

        DB::table('pemakaian_bulanan')->truncate();
        DB::table('pelanggan')->truncate();

        PreparePemakaianImportJob::dispatch($batch->id);

        return $batch;
    }

    private function pesanAntrean(ImportBatch $batch): string
    {
        return sprintf(
            'File "%s" masuk antrean import (%s baris, %d chunk, %d baris per chunk).',
            $batch->file_name,
            number_format($batch->total_rows, 0, ',', '.'),
            $batch->chunks_total,
            $batch->chunk_size,
        );
    }
}
