<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ImportBatch extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_PREPARING = 'preparing';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    /** Folder chunk CSV relatif terhadap disk 'local'. */
    public const CHUNK_DIR_PREFIX = 'imports/chunks/';

    protected $table = 'import_batches';

    protected $fillable = [
        'file_name',
        'file_path',
        'total_rows',
        'processed_rows',
        'chunks_total',
        'periode_list',
        'chunk_size',
        'status',
        'error',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'total_rows' => 'integer',
            'processed_rows' => 'integer',
            'chunks_total' => 'integer',
            'periode_list' => 'array',
            'chunk_size' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function progressPercent(): float
    {
        if ($this->total_rows <= 0) {
            return 0.0;
        }

        return round(min(100, $this->processed_rows / $this->total_rows * 100), 2);
    }

    public function isRunning(): bool
    {
        return in_array($this->status, [self::STATUS_QUEUED, self::STATUS_PREPARING, self::STATUS_PROCESSING], true);
    }

    /**
     * Perkiraan jumlah chunk yang sudah selesai diproses (dari baris yang sudah masuk).
     */
    public function chunksProcessed(): int
    {
        if ($this->chunk_size <= 0 || $this->processed_rows <= 0) {
            return 0;
        }

        return min($this->chunks_total, (int) ceil($this->processed_rows / $this->chunk_size));
    }

    public function chunkDir(): string
    {
        return self::CHUNK_DIR_PREFIX.$this->id;
    }

    /** CSV berisi baris pelanggan: idpel,nama,tarif,daya,jumlah_periode */
    public function chunkPelangganPath(int $index): string
    {
        return sprintf('%s/pelanggan-%05d.csv', $this->chunkDir(), $index);
    }

    /** CSV berisi baris pemakaian: idpel,periode,kwh */
    public function chunkPemakaianPath(int $index): string
    {
        return sprintf('%s/pemakaian-%05d.csv', $this->chunkDir(), $index);
    }

    /**
     * Hapus file upload + folder chunk setelah import selesai/gagal.
     */
    public static function cleanupFiles(int $batchId, ?string $filePath = null): void
    {
        $disk = Storage::disk('local');

        $disk->deleteDirectory(self::CHUNK_DIR_PREFIX.$batchId);

        if ($filePath !== null && $filePath !== '') {
            $disk->delete($filePath);
        }
    }
}
