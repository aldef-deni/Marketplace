<?php

namespace App\Models;

use App\Support\Midtrans;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetodePembayaran extends Model
{
    public const TIPE = ['transfer', 'ewallet', 'cod'];

    protected $fillable = [
        'nama', 'label_pendek', 'tipe', 'gateway', 'saluran', 'nomor_rekening',
        'atas_nama', 'logo', 'warna', 'instruksi', 'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
        'saluran' => 'array',
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
        $q->where('aktif', true);

        // Metode bergerbang tidak punya nomor tujuan — yang menentukan siap
        // atau tidaknya adalah kredensial Midtrans di server. Selama itu belum
        // terpasang, metodenya disembunyikan sama sekali, bukan ditawarkan
        // lalu gagal saat pembeli menekan bayar.
        if (! Midtrans::aktif()) {
            $q->whereNull('gateway');
        }

        return $q->where(fn (Builder $s) => $s->whereNotNull('gateway')
            ->orWhere('tipe', 'cod')
            ->orWhere(fn (Builder $t) => $t->whereNotNull('nomor_rekening')
                ->where('nomor_rekening', '!=', '')));
    }

    public function siapDipakai(): bool
    {
        if (! $this->aktif) {
            return false;
        }

        if ($this->viaGateway()) {
            return Midtrans::aktif();
        }

        return $this->tipe === 'cod' || filled($this->nomor_rekening);
    }

    /**
     * Pembayarannya diverifikasi gerbang, bukan oleh admin.
     */
    public function viaGateway(): bool
    {
        return filled($this->gateway);
    }

    /**
     * Alasan sebuah metode belum tampil, untuk ditunjukkan di panel.
     */
    public function getAlasanBelumTampilAttribute(): ?string
    {
        return match (true) {
            ! $this->aktif => 'Dinonaktifkan',
            // Pesannya menyebut sebab yang sebenarnya. "Belum disetel" pada
            // server yang kuncinya sudah terisi hanya menyesatkan orang untuk
            // memeriksa ulang hal yang sudah benar.
            $this->viaGateway() && ! Midtrans::kunciTerpasang() => 'Kunci Midtrans belum diisi di .env server',
            $this->viaGateway() && ! Midtrans::aktif() => 'Midtrans dimatikan lewat MIDTRANS_AKTIF',
            $this->viaGateway() => null,
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
        // Yang membedakan metode bergerbang di mata pembeli bukan bentuknya,
        // melainkan bahwa pembayarannya tercatat sendiri.
        if ($this->viaGateway()) {
            return 'Bayar Otomatis';
        }

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