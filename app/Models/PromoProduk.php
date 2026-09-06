<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoProduk extends Model
{
    protected $fillable = ['promo_id', 'produk_id', 'kuota', 'terjual'];

    protected $casts = [
        'promo_id' => 'integer',
        'produk_id' => 'integer',
        'kuota' => 'integer',
        'terjual' => 'integer',
    ];

    public function promo(): BelongsTo
    {
        return $this->belongsTo(Promo::class);
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }

    /**
     * Kuota kosong berarti tak dibatasi, bukan nol.
     */
    public function tanpaBatas(): bool
    {
        return $this->kuota === null;
    }

    public function sisaKuota(): ?int
    {
        return $this->tanpaBatas() ? null : max(0, $this->kuota - $this->terjual);
    }

    public function kuotaHabis(): bool
    {
        return ! $this->tanpaBatas() && $this->sisaKuota() <= 0;
    }

    public function hargaPromo(): float
    {
        return $this->promo?->hargaSetelahPotongan((float) ($this->produk?->harga ?? 0)) ?? 0.0;
    }

    public function getPersenHematAttribute(): int
    {
        return $this->promo?->persenHemat((float) ($this->produk?->harga ?? 0)) ?? 0;
    }
}
