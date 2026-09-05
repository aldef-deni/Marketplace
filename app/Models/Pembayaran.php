<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembayaran extends Model
{
    public const STATUS = [
        'menunggu' => 'Menunggu Pembayaran',
        'dibayar' => 'Pembayaran Diterima',
        'dibatalkan' => 'Dibatalkan',
    ];

    protected $fillable = [
        'pesanan_id', 'metode_pembayaran_id', 'kode', 'jumlah', 'status',
        'bukti', 'nama_pengirim', 'dibayar_at', 'keterangan',
    ];

    protected $casts = [
        'jumlah' => 'decimal:0',
        'dibayar_at' => 'datetime',
    ];

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class);
    }

    public function metodePembayaran(): BelongsTo
    {
        return $this->belongsTo(MetodePembayaran::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    public function getStatusWarnaAttribute(): string
    {
        return match ($this->status) {
            'menunggu' => 'bg-amber-100 text-amber-700 ring-amber-200',
            'dibayar' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
            'dibatalkan' => 'bg-rose-100 text-rose-700 ring-rose-200',
            default => 'bg-gray-100 text-gray-700 ring-gray-200',
        };
    }
}