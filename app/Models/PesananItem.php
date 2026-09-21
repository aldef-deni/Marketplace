<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PesananItem extends Model
{
    protected $fillable = [
        'pesanan_id', 'produk_id', 'nama_produk', 'gambar', 'harga', 'qty', 'subtotal',
        'potongan_type', 'potongan_id', 'kuota_terpakai',
    ];

    protected $casts = [
        'harga' => 'decimal:0',
        'subtotal' => 'decimal:0',
        'kuota_terpakai' => 'integer',
    ];

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class);
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }

    /**
     * Baris kampanye yang jatahnya terpakai oleh item ini.
     *
     * Dicatat saat checkout, bukan dihitung ulang saat pembatalan: kampanyenya
     * bisa sudah berakhir atau berganti, dan jatah yang terambil belum tentu
     * sebanyak kuantitas yang dibeli.
     */
    public function potongan(): MorphTo
    {
        return $this->morphTo();
    }
}