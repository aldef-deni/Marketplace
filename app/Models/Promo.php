<?php

namespace App\Models;

use App\Support\PunyaDiskon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Promo potongan harga.
 *
 * Dua asalnya berbeda dan menentukan cara kerjanya:
 *
 * - toko_id kosong — promo platform yang disusun superadmin, ditawarkan ke
 *   seluruh toko, dan baru berlaku setelah sebuah toko ikut serta.
 * - toko_id terisi — promo milik toko itu sendiri, tidak perlu diikuti siapa
 *   pun karena pemiliknya yang membuatnya.
 */
class Promo extends Model
{
    use PunyaDiskon;

    protected $fillable = [
        'toko_id', 'nama', 'slug', 'deskripsi', 'tipe_diskon', 'nilai_diskon',
        'mulai_at', 'selesai_at', 'aktif', 'dibuat_oleh',
    ];

    protected $casts = [
        'toko_id' => 'integer',
        'mulai_at' => 'datetime',
        'selesai_at' => 'datetime',
        'nilai_diskon' => 'decimal:0',
        'aktif' => 'boolean',
        'dibuat_oleh' => 'integer',
    ];

    public function toko(): BelongsTo
    {
        return $this->belongsTo(Toko::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function produks(): HasMany
    {
        return $this->hasMany(PromoProduk::class);
    }

    public function tokos(): BelongsToMany
    {
        return $this->belongsToMany(Toko::class, 'promo_tokos')
            ->withPivot('diikuti_at', 'diikuti_oleh')
            ->withTimestamps();
    }

    /**
     * Promo platform ditawarkan ke semua toko; promo toko hanya miliknya sendiri.
     */
    public function milikPlatform(): bool
    {
        return $this->toko_id === null;
    }

    /**
     * Apakah promo ini berlaku bagi sebuah toko.
     *
     * Promo miliknya sendiri berlaku tanpa perlu diikuti; promo platform baru
     * berlaku setelah toko itu menyatakan ikut.
     */
    public function berlakuUntukToko(int|Toko $toko): bool
    {
        $id = $toko instanceof Toko ? $toko->id : $toko;

        if (! $this->milikPlatform()) {
            return $this->toko_id === $id;
        }

        return $this->relationLoaded('tokos')
            ? $this->tokos->contains('id', $id)
            : $this->tokos()->where('tokos.id', $id)->exists();
    }

    public function scopeBerlangsung(Builder $q): Builder
    {
        return $q->where('aktif', true)
            ->where('mulai_at', '<=', now())
            ->where('selesai_at', '>=', now());
    }

    public function scopeTerbit(Builder $q): Builder
    {
        return $q->where('aktif', true);
    }

    public function scopePlatform(Builder $q): Builder
    {
        return $q->whereNull('toko_id');
    }

    public function sedangBerlangsung(): bool
    {
        return $this->aktif
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

    public function getStatusAttribute(): string
    {
        return match (true) {
            ! $this->aktif => 'draf',
            $this->sudahBerakhir() => 'berakhir',
            $this->belumMulai() => 'terjadwal',
            default => 'berlangsung',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draf' => 'Draf',
            'terjadwal' => 'Terjadwal',
            'berlangsung' => 'Berlangsung',
            'berakhir' => 'Berakhir',
        };
    }

    public function getStatusWarnaAttribute(): string
    {
        return match ($this->status) {
            'draf' => 'bg-slate-100 text-slate-600 ring-slate-200',
            'terjadwal' => 'bg-brand-50 text-brand-700 ring-brand-200',
            'berlangsung' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
            'berakhir' => 'bg-slate-100 text-slate-500 ring-slate-200',
        };
    }

    public function getDurasiLabelAttribute(): string
    {
        return tanggalIndo($this->mulai_at, true).' — '.tanggalIndo($this->selesai_at, true);
    }

    public static function slugUnik(string $nama, ?int $abaikan = null): string
    {
        $dasar = Str::slug($nama) ?: 'promo';
        $slug = $dasar;
        $urutan = 1;

        while (static::where('slug', $slug)
            ->when($abaikan, fn (Builder $q) => $q->where('id', '!=', $abaikan))
            ->exists()) {
            $slug = $dasar.'-'.(++$urutan);
        }

        return $slug;
    }
}
