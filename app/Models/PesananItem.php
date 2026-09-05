<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PesananItem extends Model
{
    protected $fillable = [
        'pesanan_id', 'produk_id', 'nama_produk', 'gambar', 'harga', 'qty', 'subtotal',
    ];

    protected $casts = [
        'harga' => 'decimal:0',
        'subtotal' => 'decimal:0',
    ];

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class);
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }
}