<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pesanan extends Model
{
    public const STATUS = [
        'menunggu_pembayaran' => 'Menunggu Pembayaran',
        'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
        'diproses' => 'Sedang Diproses',
        'dikirim' => 'Dikirim',
        'selesai' => 'Selesai',
        'dibatalkan' => 'Dibatalkan',
    ];

    protected $fillable = [
        'no_invoice', 'user_id', 'alamat_id', 'subtotal', 'ongkir', 'total',
        'status', 'kurir', 'layanan_kurir', 'catatan', 'batas_pembayaran',
        'diproses_at', 'dikirim_at', 'selesai_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:0',
        'ongkir' => 'decimal:0',
        'total' => 'decimal:0',
        'batas_pembayaran' => 'datetime',
        'diproses_at' => 'datetime',
        'dikirim_at' => 'datetime',
        'selesai_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function alamat(): BelongsTo
    {
        return $this->belongsTo(Alamat::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PesananItem::class);
    }

    public function pembayaran(): HasOne
    {
        return $this->hasOne(Pembayaran::class);
    }

    public function pengiriman(): HasOne
    {
        return $this->hasOne(Pengiriman::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    public function getStatusWarnaAttribute(): string
    {
        return match ($this->status) {
            'menunggu_pembayaran' => 'bg-amber-100 text-amber-700 ring-amber-200',
            'menunggu_konfirmasi' => 'bg-orange-100 text-orange-700 ring-orange-200',
            'diproses' => 'bg-blue-100 text-blue-700 ring-blue-200',
            'dikirim' => 'bg-indigo-100 text-indigo-700 ring-indigo-200',
            'selesai' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
            'dibatalkan' => 'bg-rose-100 text-rose-700 ring-rose-200',
            default => 'bg-gray-100 text-gray-700 ring-gray-200',
        };
    }

    public function getLangkahSelesaiAttribute(): int
    {
        return match ($this->status) {
            'menunggu_pembayaran', 'menunggu_konfirmasi' => 1,
            'diproses' => 2,
            'dikirim' => 3,
            'selesai' => 4,
            default => 0,
        };
    }

    public function isCod(): bool
    {
        return $this->pembayaran?->metodePembayaran?->tipe === 'cod';
    }
}