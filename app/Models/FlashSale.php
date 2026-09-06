<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class FlashSale extends Model
{
    protected $fillable = [
        'nama', 'slug', 'deskripsi', 'mulai_at', 'selesai_at',
        'diskon_persen', 'aktif', 'diikuti', 'diikuti_at', 'diikuti_oleh', 'dibuat_oleh',
    ];

    protected $casts = [
        'mulai_at' => 'datetime',
        'selesai_at' => 'datetime',
        'diikuti_at' => 'datetime',
        'diskon_persen' => 'integer',
        'aktif' => 'boolean',
        'diikuti' => 'boolean',
        'diikuti_oleh' => 'integer',
        'dibuat_oleh' => 'integer',
    ];

    public function produks(): HasMany
    {
        return $this->hasMany(FlashSaleProduk::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diikuti_oleh');
    }

    /**
     * Kampanye yang harganya benar-benar berlaku bagi pembeli saat ini.
     *
     * Tiga syarat harus terpenuhi sekaligus: sudah diterbitkan superadmin,
     * diikuti admin toko, dan sedang berada dalam rentang waktunya. Dipakai
     * scope agar aturan yang sama tidak ditulis ulang di banyak tempat.
     */
    public function scopeBerlangsung(Builder $q): Builder
    {
        return $q->where('aktif', true)
            ->where('diikuti', true)
            ->where('mulai_at', '<=', now())
            ->where('selesai_at', '>=', now());
    }

    public function scopeTerbit(Builder $q): Builder
    {
        return $q->where('aktif', true);
    }

    public function sedangBerlangsung(): bool
    {
        return $this->aktif
            && $this->diikuti
            && $this->mulai_at?->lte(now())
            && $this->selesai_at?->gte(now());
    }

    public function sudahBerakhir(): bool
    {
        return $this->selesai_at?->lt(now()) ?? false;
    }

    public function belumMulai(): bool
    {
        return $this->mulai_at?->gt(now()) ?? false;
    }

    /**
     * Keadaan kampanye dalam satu kata, untuk lencana di antarmuka.
     */
    public function getStatusAttribute(): string
    {
        return match (true) {
            ! $this->aktif => 'draf',
            $this->sudahBerakhir() => 'berakhir',
            $this->belumMulai() => 'terjadwal',
            $this->diikuti => 'berlangsung',
            default => 'menunggu',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draf' => 'Draf',
            'terjadwal' => 'Terjadwal',
            'berlangsung' => 'Berlangsung',
            'berakhir' => 'Berakhir',
            'menunggu' => 'Menunggu Keikutsertaan',
        };
    }

    public function getStatusWarnaAttribute(): string
    {
        return match ($this->status) {
            'draf' => 'bg-slate-100 text-slate-600 ring-slate-200',
            'terjadwal' => 'bg-brand-50 text-brand-700 ring-brand-200',
            'berlangsung' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
            'berakhir' => 'bg-slate-100 text-slate-500 ring-slate-200',
            'menunggu' => 'bg-accent-100 text-accent-700 ring-accent-200',
        };
    }

    /**
     * Sisa waktu sampai kampanye berakhir, dalam detik.
     *
     * Dipakai penghitung mundur di halaman toko. Bernilai nol bila kampanye
     * belum berjalan, supaya tampilan tidak menghitung mundur sesuatu yang
     * belum dimulai.
     */
    public function sisaDetik(): int
    {
        if (! $this->sedangBerlangsung()) {
            return 0;
        }

        // Carbon mengembalikan pecahan; dibulatkan eksplisit agar tidak
        // memicu peringatan kehilangan presisi saat dikembalikan sebagai int.
        return max(0, (int) round(Carbon::now()->diffInSeconds($this->selesai_at, false)));
    }

    public function getDurasiLabelAttribute(): string
    {
        return tanggalIndo($this->mulai_at, true).' — '.tanggalIndo($this->selesai_at, true);
    }
}
