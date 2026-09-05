<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembayaran extends Model
{
    public const STATUS = [
        'menunggu' => 'Menunggu Pembayaran',
        'dibayar' => 'Pembayaran Diterima',
        'ditolak' => 'Bukti Ditolak',
        'dibatalkan' => 'Dibatalkan',
    ];

    protected $fillable = [
        'pesanan_id', 'metode_pembayaran_id', 'kode', 'jumlah', 'status',
        'bukti', 'nama_pengirim', 'dibayar_at', 'keterangan',
    ];

    protected $casts = [
        'pesanan_id' => 'integer',
        'metode_pembayaran_id' => 'integer',
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

    /**
     * Bukti sudah dikirim pembeli dan menunggu penilaian admin.
     *
     * Tanpa bukti, tidak ada yang bisa diverifikasi maupun ditolak — tombolnya
     * pun tidak seharusnya ditawarkan.
     */
    public function menungguPenilaian(): bool
    {
        return $this->status === 'menunggu'
            && filled($this->bukti)
            && $this->metodePembayaran?->tipe !== 'cod';
    }

    public function ditolak(): bool
    {
        return $this->status === 'ditolak';
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
            'ditolak' => 'bg-rose-100 text-rose-700 ring-rose-200',
            'dibatalkan' => 'bg-slate-100 text-slate-600 ring-slate-200',
            default => 'bg-gray-100 text-gray-700 ring-gray-200',
        };
    }
}