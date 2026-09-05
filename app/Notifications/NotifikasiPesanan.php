<?php

namespace App\Notifications;

use App\Models\Pesanan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Satu notifikasi untuk seluruh peristiwa transaksi.
 *
 * Semua peristiwa dikumpulkan dalam satu katalog di bawah, bukan dipecah
 * menjadi belasan kelas, supaya menambah jenis notifikasi cukup menambah satu
 * baris — dan seluruh judul, ikon, serta warnanya bisa dibaca sekaligus.
 */
class NotifikasiPesanan extends Notification
{
    use Queueable;

    /**
     * Katalog peristiwa: judul, penyusun pesan, ikon <x-ikon>, dan nada warna.
     *
     * 'untuk' menandai sasaran notifikasi — dipakai saat menentukan tautan,
     * karena pembeli dan admin membuka halaman pesanan yang berbeda.
     */
    public const PERISTIWA = [
        'pesanan_dibuat' => [
            'judul' => 'Pesanan berhasil dibuat',
            'ikon' => 'kotak', 'nada' => 'brand', 'untuk' => 'pembeli',
        ],
        'pesanan_baru' => [
            'judul' => 'Pesanan baru masuk',
            'ikon' => 'kotak', 'nada' => 'brand', 'untuk' => 'admin',
        ],
        'bukti_diunggah' => [
            'judul' => 'Bukti pembayaran menunggu verifikasi',
            'ikon' => 'unggah', 'nada' => 'accent', 'untuk' => 'admin',
        ],
        'pembayaran_diverifikasi' => [
            'judul' => 'Pembayaran terverifikasi',
            'ikon' => 'centang', 'nada' => 'emerald', 'untuk' => 'pembeli',
        ],
        'pembayaran_ditolak' => [
            'judul' => 'Bukti pembayaran ditolak',
            'ikon' => 'peringatan', 'nada' => 'rose', 'untuk' => 'pembeli',
        ],
        'pesanan_diproses' => [
            'judul' => 'Pesanan sedang diproses',
            'ikon' => 'papan', 'nada' => 'brand', 'untuk' => 'pembeli',
        ],
        'pesanan_dikirim' => [
            'judul' => 'Pesanan dalam pengiriman',
            'ikon' => 'truk', 'nada' => 'brand', 'untuk' => 'pembeli',
        ],
        'pesanan_selesai' => [
            'judul' => 'Pesanan selesai',
            'ikon' => 'centang', 'nada' => 'emerald', 'untuk' => 'pembeli',
        ],
        'pesanan_diterima_pembeli' => [
            'judul' => 'Pembeli mengonfirmasi penerimaan',
            'ikon' => 'centang', 'nada' => 'emerald', 'untuk' => 'admin',
        ],
        'pesanan_dibatalkan' => [
            'judul' => 'Pesanan dibatalkan',
            'ikon' => 'silang', 'nada' => 'rose', 'untuk' => 'pembeli',
        ],
        'pesanan_dibatalkan_pembeli' => [
            'judul' => 'Pesanan dibatalkan pembeli',
            'ikon' => 'silang', 'nada' => 'rose', 'untuk' => 'admin',
        ],
    ];

    public function __construct(
        public Pesanan $pesanan,
        public string $peristiwa,
        public ?string $pesanTambahan = null,
    ) {
        if (! array_key_exists($peristiwa, self::PERISTIWA)) {
            throw new \InvalidArgumentException("Peristiwa notifikasi tidak dikenal: {$peristiwa}");
        }
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $meta = self::PERISTIWA[$this->peristiwa];

        return [
            'peristiwa' => $this->peristiwa,
            'judul' => $meta['judul'],
            'pesan' => $this->pesanTambahan ?: $this->pesanBaku(),
            'ikon' => $meta['ikon'],
            'nada' => $meta['nada'],
            'invoice' => $this->pesanan->no_invoice,
            'pesanan_id' => $this->pesanan->id,
            // Tautan disimpan saat notifikasi dibuat, bukan dihitung saat
            // ditampilkan, agar riwayat lama tetap mengarah ke tempat yang benar
            // walau strukturnya kelak berubah.
            'url' => $meta['untuk'] === 'admin'
                ? route('admin.pesanan.show', $this->pesanan->id)
                : route('pesanan.show', $this->pesanan->no_invoice),
        ];
    }

    private function pesanBaku(): string
    {
        $invoice = $this->pesanan->no_invoice;
        $total = rp($this->pesanan->total);

        return match ($this->peristiwa) {
            'pesanan_dibuat' => "Pesanan {$invoice} senilai {$total} menunggu pembayaran.",
            'pesanan_baru' => "{$invoice} dari {$this->pesanan->user?->name} senilai {$total}.",
            'bukti_diunggah' => "Pembeli mengunggah bukti pembayaran untuk {$invoice}.",
            'pembayaran_diverifikasi' => "Pembayaran {$invoice} sudah kami terima.",
            'pembayaran_ditolak' => "Bukti pembayaran {$invoice} belum sesuai. Silakan unggah ulang.",
            'pesanan_diproses' => "Pesanan {$invoice} sedang kami siapkan.",
            'pesanan_dikirim' => "Pesanan {$invoice} dikirim via {$this->pesanan->kurir}"
                .($this->pesanan->pengiriman?->no_resi ? " — resi {$this->pesanan->pengiriman->no_resi}." : '.'),
            'pesanan_selesai' => "Pesanan {$invoice} telah selesai. Terima kasih!",
            'pesanan_diterima_pembeli' => "Pembeli mengonfirmasi {$invoice} sudah diterima.",
            'pesanan_dibatalkan' => "Pesanan {$invoice} dibatalkan.",
            'pesanan_dibatalkan_pembeli' => "{$invoice} dibatalkan oleh pembeli.",
        };
    }
}
