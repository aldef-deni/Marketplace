<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetodePembayaran extends Model
{
    public const TIPE = ['transfer', 'ewallet', 'cod'];

    protected $fillable = [
        'nama', 'tipe', 'nomor_rekening', 'atas_nama', 'logo', 'instruksi', 'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function pembayarans(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }

    public function getLabelTipeAttribute(): string
    {
        return match ($this->tipe) {
            'transfer' => 'Transfer Bank',
            'ewallet' => 'E-Wallet',
            'cod' => 'COD (Bayar di Tempat)',
            default => ucfirst($this->tipe),
        };
    }

    public function getWarnaAttribute(): string
    {
        return match ($this->tipe) {
            'transfer' => 'bg-blue-500/10 text-blue-700 ring-blue-200',
            'ewallet' => 'bg-emerald-500/10 text-emerald-700 ring-emerald-200',
            'cod' => 'bg-amber-500/10 text-amber-700 ring-amber-200',
            default => 'bg-gray-500/10 text-gray-700 ring-gray-200',
        };
    }
}