<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Toko extends Model
{
    public const STATUS = [
        'menunggu' => 'Menunggu Persetujuan',
        'aktif' => 'Aktif',
        'nonaktif' => 'Nonaktif',
    ];

    protected $fillable = [
        'user_id', 'nama', 'slug', 'deskripsi', 'logo', 'banner',
        'no_hp', 'email', 'provinsi', 'kota', 'kecamatan', 'alamat',
        'status', 'disetujui_at',
    ];

    protected $casts = [
        // Tanpa cast ini sebagian driver mengembalikan kunci asing sebagai
        // string, sehingga perbandingan ketat dengan auth()->id() selalu gagal.
        'user_id' => 'integer',
        'disetujui_at' => 'datetime',
    ];

    public function pemilik(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function produks(): HasMany
    {
        return $this->hasMany(Produk::class);
    }

    /**
     * Toko yang boleh tampil di etalase pembeli.
     */
    public function scopeTampil(Builder $q): Builder
    {
        return $q->where('status', 'aktif');
    }

    public function aktif(): bool
    {
        return $this->status === 'aktif';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    public function getStatusWarnaAttribute(): string
    {
        return match ($this->status) {
            'aktif' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
            'nonaktif' => 'bg-slate-100 text-slate-600 ring-slate-200',
            default => 'bg-accent-100 text-accent-700 ring-accent-200',
        };
    }

    public function getLokasiAttribute(): ?string
    {
        $bagian = array_filter([$this->kota, $this->provinsi]);

        return $bagian === [] ? null : implode(', ', $bagian);
    }

    /**
     * Inisial untuk logo cadangan, dipakai saat toko belum mengunggah gambar.
     */
    public function getInisialAttribute(): string
    {
        return Str::of($this->nama)
            ->explode(' ')
            ->take(2)
            ->map(fn (string $kata) => Str::upper(Str::substr($kata, 0, 1)))
            ->implode('');
    }

    /**
     * Slug unik dari nama toko; angka urut ditambahkan bila sudah terpakai.
     */
    public static function slugUnik(string $nama, ?int $abaikan = null): string
    {
        $dasar = Str::slug($nama) ?: 'toko';
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
