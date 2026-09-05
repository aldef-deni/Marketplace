<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alamat extends Model
{
    protected $fillable = [
        'user_id', 'label', 'nama_penerima', 'no_hp', 'provinsi',
        'kota', 'kecamatan', 'kode_pos', 'alamat_lengkap', 'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getAlamatRingkasAttribute(): string
    {
        return implode(', ', array_filter([
            $this->kecamatan,
            $this->kota,
            $this->provinsi,
            $this->kode_pos,
        ]));
    }

    public function getAlamatLengkapKomaAttribute(): string
    {
        return $this->alamat_lengkap.', '.$this->alamat_ringkas;
    }
}