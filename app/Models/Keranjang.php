<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Keranjang extends Model
{
    protected $fillable = ['user_id', 'produk_id', 'qty', 'dipilih'];

    protected $casts = [
        // Lihat catatan pada model Pesanan: kunci asing yang terbaca sebagai
        // string membuat pemeriksaan kepemilikan menolak pemiliknya sendiri.
        'user_id' => 'integer',
        'produk_id' => 'integer',
        'qty' => 'integer',
        'dipilih' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }

    /**
     * Item yang benar-benar akan dibeli pada checkout berikutnya.
     */
    public function scopeDipilih($q)
    {
        return $q->where('dipilih', true);
    }

    /**
     * Produk yang sudah tidak dapat dibeli tidak boleh ikut terbawa.
     *
     * Produk bisa dinonaktifkan atau kehabisan stok setelah masuk keranjang;
     * membiarkannya tercentang hanya akan menggagalkan checkout di detik
     * terakhir tanpa penjelasan.
     */
    public function tersedia(): bool
    {
        return $this->produk
            && $this->produk->status === 'aktif'
            && $this->produk->stok > 0;
    }

    public function getSubtotalAttribute(): float
    {
        // Harga berlaku, bukan harga daftar: flash sale yang sedang jalan
        // harus terpakai sama di keranjang, checkout, dan katalog.
        return $this->produk->hargaEfektif() * $this->qty;
    }
}