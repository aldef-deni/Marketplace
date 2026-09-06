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
        'kategori_id' => 'integer',
        'harga' => 'decimal:0',
        'harga_coret' => 'decimal:0',
        'stok' => 'integer',
        'berat' => 'integer',
    ];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    public function flashSaleItems(): HasMany
    {
        return $this->hasMany(FlashSaleProduk::class);
    }

    /**
     * Baris flash sale yang harganya berlaku untuk produk ini sekarang.
     *
     * Hasilnya di-cache pada instance karena satu halaman katalog memanggilnya
     * sekali per produk, dan tanpa itu setiap kartu memicu kuerinya sendiri.
     */
    public function flashSaleBerlaku(): ?FlashSaleProduk
    {
        if (array_key_exists('flashSaleBerlaku', $this->relations)) {
            return $this->relations['flashSaleBerlaku'];
        }

        $baris = $this->flashSaleItems()
            ->with('flashSale')
            ->whereHas('flashSale', fn ($q) => $q->berlangsung())
            ->get()
            ->first(fn (FlashSaleProduk $b) => ! $b->kuotaHabis());

        $this->relations['flashSaleBerlaku'] = $baris;

        return $baris;
    }

    public function sedangFlashSale(): bool
    {
        return $this->flashSaleBerlaku() !== null;
    }

    /**
     * Harga yang benar-benar dibayar pembeli saat ini.
     *
     * Seluruh tampilan dan perhitungan memakai satu sumber ini, supaya harga di
     * katalog, keranjang, dan checkout tidak mungkin berbeda.
     */
    public function hargaEfektif(): float
    {
        return (float) ($this->flashSaleBerlaku()?->harga_flash ?? $this->harga);
    }

    /**
     * Harga sebelum potongan, untuk dicoret di tampilan.
     */
    public function hargaSebelumPotongan(): ?float
    {
        if ($this->sedangFlashSale()) {
            return (float) $this->harga;
        }

        return $this->harga_coret && $this->harga_coret > $this->harga
            ? (float) $this->harga_coret
            : null;
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