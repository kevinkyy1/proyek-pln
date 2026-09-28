<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pelanggan extends Model
{
    protected $table = 'pelanggan';

    protected $fillable = ['idpel', 'nama', 'tarif', 'daya'];

    protected function casts(): array
    {
        return [
            'daya' => 'integer',
        ];
    }

    public function pemakaianBulanan(): HasMany
    {
        return $this->hasMany(PemakaianBulanan::class, 'idpel', 'idpel');
    }
}
