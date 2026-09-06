<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Keranjang extends Model
{
    protected $fillable = ['user_id', 'produk_id', 'qty'];

    protected $casts = [
        // Lihat catatan pada model Pesanan: kunci asing yang terbaca sebagai
        // string membuat pemeriksaan kepemilikan menolak pemiliknya sendiri.
        'user_id' => 'integer',
        'produk_id' => 'integer',
        'qty' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }

    public function getSubtotalAttribute(): float
    {
        // Harga berlaku, bukan harga daftar: flash sale yang sedang jalan
        // harus terpakai sama di keranjang, checkout, dan katalog.
        return $this->produk->hargaEfektif() * $this->qty;
    }
}