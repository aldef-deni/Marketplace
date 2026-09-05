<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends Model
{
    protected $fillable = ['nama', 'slug', 'deskripsi', 'ikon', 'aktif'];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function produks(): HasMany
    {
        return $this->hasMany(Produk::class);
    }

    public function getJumlahProdukAttribute(): int
    {
        return $this->produks()->where('status', 'aktif')->count();
    }
}