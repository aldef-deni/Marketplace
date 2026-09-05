<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengiriman extends Model
{
    /**
     * Nama tabel eksplisit agar tidak terpluralisasi menjadi "pengirimen".
     */
    protected $table = 'pengirimans';

    public const STATUS = [
        'menunggu' => 'Menunggu Pengiriman',
        'dikirim' => 'Dalam Pengiriman',
        'diterima' => 'Diterima',
    ];

    protected $fillable = [
        'pesanan_id', 'kurir', 'layanan', 'no_resi', 'ongkir',
        'status', 'dikirim_at', 'diterima_at', 'catatan',
    ];

    protected $casts = [
        'ongkir' => 'decimal:0',
        'dikirim_at' => 'datetime',
        'diterima_at' => 'datetime',
    ];

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }
}