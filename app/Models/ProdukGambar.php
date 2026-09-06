<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu gambar dalam galeri sebuah produk.
 *
 * Mana yang menjadi gambar bawaan tidak disimpan di sini melainkan pada
 * produks.gambar. Dengan begitu hanya ada satu tempat yang perlu dibaca oleh
 * kartu produk, keranjang, dan checkout — dan mustahil ada dua baris yang
 * sama-sama mengaku bawaan.
 */
class ProdukGambar extends Model
{
    protected $fillable = ['produk_id', 'jalur', 'urutan'];

    protected $casts = [
        'produk_id' => 'integer',
        'urutan' => 'integer',
    ];

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }

    public function utama(): bool
    {
        return $this->jalur === $this->produk?->gambar;
    }
}
