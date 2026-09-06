<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashSaleProduk extends Model
{
    protected $table = 'flash_sale_produk';

    protected $fillable = ['flash_sale_id', 'produk_id', 'harga_flash', 'kuota', 'terjual'];

    protected $casts = [
        'flash_sale_id' => 'integer',
        'produk_id' => 'integer',
        'harga_flash' => 'decimal:0',
        'kuota' => 'integer',
        'terjual' => 'integer',
    ];

    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }

    /**
     * Sisa unit yang masih boleh dijual dengan harga flash.
     */
    public function sisaKuota(): int
    {
        return max(0, $this->kuota - $this->terjual);
    }

    public function kuotaHabis(): bool
    {
        return $this->sisaKuota() <= 0;
    }

    public function getPersenHematAttribute(): int
    {
        $normal = (float) ($this->produk?->harga ?? 0);

        if ($normal <= 0 || $this->harga_flash >= $normal) {
            return 0;
        }

        return (int) round((($normal - (float) $this->harga_flash) / $normal) * 100);
    }
}
