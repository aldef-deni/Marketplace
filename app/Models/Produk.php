<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produk extends Model
{
    protected $fillable = [
        'kategori_id', 'nama', 'slug', 'deskripsi', 'harga', 'harga_coret',
        'stok', 'berat', 'gambar', 'status',
    ];

    protected $casts = [
        'harga' => 'decimal:0',
        'harga_coret' => 'decimal:0',
    ];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    public function keranjangItems(): HasMany
    {
        return $this->hasMany(Keranjang::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    public function scopeTersedia(Builder $query): Builder
    {
        return $query->where('stok', '>', 0);
    }

    public function getDiskonPersenAttribute(): ?int
    {
        if ($this->harga_coret && $this->harga_coret > $this->harga) {
            return (int) round((($this->harga_coret - $this->harga) / $this->harga_coret) * 100);
        }

        return null;
    }
}