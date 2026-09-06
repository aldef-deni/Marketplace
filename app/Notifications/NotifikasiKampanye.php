<?php

namespace App\Notifications;

use App\Models\FlashSale;
use App\Models\Promo;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;

/**
 * Pemberitahuan kampanye kepada pemilik toko.
 *
 * Dikirim saat superadmin menerbitkan kampanye, bukan saat menyimpannya sebagai
 * draf: draf belum tentu jadi, dan memberitahukan sesuatu yang belum bisa
 * ditindaklanjuti hanya melatih orang untuk mengabaikan notifikasi.
 */
class NotifikasiKampanye extends Notification
{
    use Queueable;

    public const PERISTIWA = [
        'flash_sale_baru' => [
            'judul' => 'Kampanye flash sale baru',
            'ikon' => 'petir', 'nada' => 'accent',
        ],
        'promo_baru' => [
            'judul' => 'Promo baru dari pengelola',
            'ikon' => 'label', 'nada' => 'brand',
        ],
        'flash_sale_ditarik' => [
            'judul' => 'Kampanye flash sale ditarik',
            'ikon' => 'silang', 'nada' => 'rose',
        ],
        'promo_ditarik' => [
            'judul' => 'Promo ditarik pengelola',
            'ikon' => 'silang', 'nada' => 'rose',
        ],
    ];

    public function __construct(
        public Model $kampanye,
        public string $peristiwa,
    ) {
        if (! array_key_exists($peristiwa, self::PERISTIWA)) {
            throw new InvalidArgumentException("Peristiwa notifikasi tidak dikenal: {$peristiwa}");
        }
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $meta = self::PERISTIWA[$this->peristiwa];
        $flash = $this->kampanye instanceof FlashSale;

        return [
            'peristiwa' => $this->peristiwa,
            'judul' => $meta['judul'],
            'pesan' => $this->pesan(),
            'ikon' => $meta['ikon'],
            'nada' => $meta['nada'],
            'kampanye_id' => $this->kampanye->id,
            // Tautan disimpan saat notifikasi dibuat, bukan dihitung saat
            // ditampilkan, agar riwayat lama tetap mengarah ke tempat yang benar.
            'url' => $flash
                ? route('admin.flash-sale.kelola', $this->kampanye->id)
                : route('admin.promo.kelola', $this->kampanye->id),
        ];
    }

    private function pesan(): string
    {
        $nama = $this->kampanye->nama;
        $potongan = $this->kampanye->label_diskon;

        return match ($this->peristiwa) {
            'flash_sale_baru' => "\"{$nama}\" potongan {$potongan} dibuka sampai "
                .tanggalIndo($this->kampanye->selesai_at, true).'. Pilih ikut atau tidak.',
            'promo_baru' => "\"{$nama}\" potongan {$potongan} tersedia sampai "
                .tanggalIndo($this->kampanye->selesai_at, true).'. Pilih ikut atau tidak.',
            'flash_sale_ditarik' => "\"{$nama}\" ditarik pengelola. Harga flash-nya tidak lagi berlaku.",
            'promo_ditarik' => "\"{$nama}\" ditarik pengelola. Potongannya tidak lagi berlaku.",
        };
    }

    /**
     * Jenis kampanye dalam satu kata, dipakai saat menyaring riwayat.
     */
    public static function jenis(Model $kampanye): string
    {
        return match (true) {
            $kampanye instanceof FlashSale => 'flash-sale',
            $kampanye instanceof Promo => 'promo',
            default => 'kampanye',
        };
    }
}
