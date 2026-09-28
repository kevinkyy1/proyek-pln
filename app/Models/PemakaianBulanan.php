<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PemakaianBulanan extends Model
{
    protected $table = 'pemakaian_bulanan';

    public $timestamps = false;

    protected $fillable = ['idpel', 'periode', 'kwh'];

    protected function casts(): array
    {
        return [
            'kwh' => 'decimal:2',
        ];
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'idpel', 'idpel');
    }
}
