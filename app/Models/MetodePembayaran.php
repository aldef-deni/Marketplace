<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetodePembayaran extends Model
{
    public const TIPE = ['transfer', 'ewallet', 'cod'];

    protected $fillable = [
        'nama', 'label_pendek', 'tipe', 'nomor_rekening', 'atas_nama',
        'logo', 'warna', 'instruksi', 'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function pembayarans(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }

    /**
     * Metode yang benar-benar dapat ditawarkan kepada pembeli.
     *
     * Aktif saja tidak cukup: metode transfer atau e-wallet tanpa nomor tujuan
     * membuat pembeli sampai di layar pembayaran tanpa tahu harus mengirim ke
     * mana. COD dikecualikan karena memang tidak punya nomor.
     */
    public function scopeSiap(Builder $q): Builder
    {
        return $q->where('aktif', true)
            ->where(fn (Builder $s) => $s->where('tipe', 'cod')
                ->orWhere(fn (Builder $t) => $t->whereNotNull('nomor_rekening')
                    ->where('nomor_rekening', '!=', '')));
    }

    public function siapDipakai(): bool
    {
        if (! $this->aktif) {
            return false;
        }

        return $this->tipe === 'cod' || filled($this->nomor_rekening);
    }

    /**
     * Alasan sebuah metode belum tampil, untuk ditunjukkan di panel.
     */
    public function getAlasanBelumTampilAttribute(): ?string
    {
        return match (true) {
            ! $this->aktif => 'Dinonaktifkan',
            $this->tipe !== 'cod' && blank($this->nomor_rekening) => 'Nomor belum diisi',
            default => null,
        };
    }

    /**
     * Nama pendek untuk lencana; jatuh ke nama lengkap bila belum diisi.
     */
    public function getLabelBadgeAttribute(): string
    {
        return filled($this->label_pendek) ? $this->label_pendek : $this->nama;
    }

    public function getWarnaMerchantAttribute(): string
    {
        return filled($this->warna) ? $this->warna : '#0B5FB0';
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

    /**
     * Kelas lencana untuk tipe metode — bukan warna khas merchantnya.
     *
     * Sebelumnya bernama "warna" dan menutupi kolom warna yang menyimpan hex
     * merchant, sehingga lencana footer selalu menerima kelas Tailwind alih-alih
     * kode warna.
     */
    public function getWarnaTipeAttribute(): string
    {
        return match ($this->tipe) {
            'transfer' => 'bg-blue-500/10 text-blue-700 ring-blue-200',
            'ewallet' => 'bg-emerald-500/10 text-emerald-700 ring-emerald-200',
            'cod' => 'bg-amber-500/10 text-amber-700 ring-amber-200',
            default => 'bg-gray-500/10 text-gray-700 ring-gray-200',
        };
    }
}