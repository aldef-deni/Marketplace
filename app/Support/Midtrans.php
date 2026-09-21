<?php

namespace App\Support;

use App\Models\Pesanan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Pemanggil Midtrans Snap.
 *
 * Dipanggil lewat HTTP biasa, bukan lewat paket midtrans-php. Alasannya
 * praktis: paket rilis tidak menyertakan vendor/, dan server cPanel ini tidak
 * punya composer di PATH — satu dependensi baru berarti satu langkah pasang
 * manual yang bisa terlewat dan menjatuhkan seluruh checkout. Yang dibutuhkan
 * dari Snap hanya dua panggilan, keduanya JSON sederhana dengan Basic Auth.
 */
class Midtrans
{
    /**
     * Gerbang siap dipakai: dinyalakan dan kredensialnya lengkap.
     *
     * Keduanya diperiksa bersama supaya metode bergerbang tidak pernah
     * ditawarkan di checkout hanya karena saklarnya menyala.
     */
    public static function aktif(): bool
    {
        return (bool) config('midtrans.aktif') && self::kunciTerpasang();
    }

    /**
     * Kredensialnya ada, terlepas dari saklarnya.
     *
     * Dipisah supaya panel dapat membedakan "kunci belum diisi" dari "sengaja
     * dimatikan" — dua keadaan yang menuntut tindakan berbeda.
     */
    public static function kunciTerpasang(): bool
    {
        return filled(config('midtrans.server_key')) && filled(config('midtrans.client_key'));
    }

    public static function produksi(): bool
    {
        return (bool) config('midtrans.produksi');
    }

    /**
     * Membuat tagihan Snap dan mengembalikan token beserta alamat bayarnya.
     *
     * @param  array<int, string>  $saluran  kanal yang boleh muncul pada halaman Midtrans
     * @return array{token: string, redirect_url: string}
     *
     * @throws RuntimeException bila Midtrans menolak atau tidak dapat dihubungi
     */
    public static function buatTagihan(Pesanan $pesanan, string $orderId, array $saluran): array
    {
        if (! self::aktif()) {
            throw new RuntimeException('Midtrans belum dikonfigurasi di server ini.');
        }

        $saluran = self::saring($saluran);

        if ($saluran === []) {
            throw new RuntimeException('Metode ini tidak punya kanal pembayaran yang diizinkan.');
        }

        $jawaban = self::kirim(self::alamat('snap'), [
            'transaction_details' => [
                'order_id' => $orderId,
                // gross_amount wajib bilangan bulat rupiah; desimal ditolak.
                'gross_amount' => (int) round($pesanan->total),
            ],
            'customer_details' => self::pembeli($pesanan),
            'item_details' => self::rincian($pesanan),
            'enabled_payments' => $saluran,
            'expiry' => [
                'unit' => 'minute',
                'duration' => (int) config('midtrans.kedaluwarsa_menit'),
            ],
            'callbacks' => [
                'finish' => route('pesanan.show', $pesanan->no_invoice),
            ],
        ]);

        if (blank($jawaban['token'] ?? null)) {
            throw new RuntimeException('Midtrans tidak mengembalikan token pembayaran.');
        }

        return [
            'token' => $jawaban['token'],
            'redirect_url' => $jawaban['redirect_url'] ?? '',
        ];
    }

    /**
     * Menanyakan langsung keadaan sebuah transaksi ke Midtrans.
     *
     * Dipakai sebagai jaring pengaman ketika notifikasi tidak pernah sampai —
     * webhook bisa tertahan firewall atau gagal saat server sedang mati.
     *
     * @return array<string, mixed>
     */
    public static function tanyaStatus(string $orderId): array
    {
        if (! self::aktif()) {
            throw new RuntimeException('Midtrans belum dikonfigurasi di server ini.');
        }

        $respons = Http::withBasicAuth((string) config('midtrans.server_key'), '')
            ->acceptJson()
            ->timeout((int) config('midtrans.timeout'))
            ->get(self::alamat('api').'/'.rawurlencode($orderId).'/status');

        // 404 berarti transaksinya memang belum pernah dibuat — bukan galat
        // yang perlu dilemparkan, cukup dilaporkan apa adanya.
        if ($respons->status() === 404) {
            return ['transaction_status' => 'tidak_ada'];
        }

        if ($respons->failed()) {
            throw new RuntimeException('Midtrans menolak permintaan status: '.$respons->status());
        }

        return $respons->json() ?? [];
    }

    /**
     * Keaslian notifikasi: SHA512(order_id + status_code + gross_amount + ServerKey).
     *
     * Tanpa pemeriksaan ini, siapa pun yang tahu alamat webhook dapat
     * menyatakan sebuah pesanan lunas hanya dengan mengirim satu POST.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function tandaTanganSah(array $payload): bool
    {
        $tandaTangan = (string) ($payload['signature_key'] ?? '');
        $serverKey = (string) config('midtrans.server_key');

        if ($tandaTangan === '' || $serverKey === '') {
            return false;
        }

        $seharusnya = hash('sha512',
            ($payload['order_id'] ?? '')
            .($payload['status_code'] ?? '')
            .($payload['gross_amount'] ?? '')
            .$serverKey
        );

        return hash_equals($seharusnya, $tandaTangan);
    }

    /**
     * Terjemahan keadaan Midtrans ke status pembayaran kita.
     *
     * capture hanya dianggap lunas bila lolos penyaringan penipuan; "challenge"
     * berarti Midtrans sendiri belum yakin, jadi kita pun belum.
     */
    public static function terjemahkanStatus(string $statusMidtrans, string $fraud = 'accept'): string
    {
        return match ($statusMidtrans) {
            'settlement' => 'dibayar',
            'capture' => $fraud === 'accept' ? 'dibayar' : 'menunggu',
            'pending' => 'menunggu',
            'deny', 'cancel', 'expire', 'failure' => 'dibatalkan',
            default => 'menunggu',
        };
    }

    /* ---------- Bagian dalam ---------- */

    /**
     * Buang kanal yang tidak ada dalam daftar izin.
     *
     * Batasnya ditegakkan di sini, sedekat mungkin dengan panggilan keluar,
     * supaya tidak ada jalur lain yang bisa melewatinya.
     *
     * @param  array<int, string>  $saluran
     * @return array<int, string>
     */
    private static function saring(array $saluran): array
    {
        $diizinkan = (array) config('midtrans.saluran_diizinkan');

        return array_values(array_intersect(array_map('strval', $saluran), $diizinkan));
    }

    private static function alamat(string $jenis): string
    {
        $lingkungan = self::produksi() ? 'produksi' : 'sandbox';

        return (string) config("midtrans.url.{$lingkungan}.{$jenis}");
    }

    /**
     * @param  array<string, mixed>  $isi
     * @return array<string, mixed>
     */
    private static function kirim(string $url, array $isi): array
    {
        try {
            $respons = Http::withBasicAuth((string) config('midtrans.server_key'), '')
                ->acceptJson()
                ->timeout((int) config('midtrans.timeout'))
                ->post($url, $isi);
        } catch (\Throwable $e) {
            // Pesan aslinya bisa memuat potongan permintaan; cukup dicatat.
            Log::error('Midtrans tidak dapat dihubungi', ['pesan' => $e->getMessage()]);

            throw new RuntimeException('Gerbang pembayaran tidak dapat dihubungi. Coba lagi sebentar.');
        }

        if ($respons->failed()) {
            Log::error('Midtrans menolak permintaan', [
                'status' => $respons->status(),
                'isi' => $respons->json(),
            ]);

            $pesan = collect((array) $respons->json('error_messages'))->implode('; ');

            throw new RuntimeException($pesan !== ''
                ? 'Midtrans menolak: '.$pesan
                : 'Gerbang pembayaran menolak permintaan ('.$respons->status().').');
        }

        return $respons->json() ?? [];
    }

    /**
     * @return array<string, string>
     */
    private static function pembeli(Pesanan $pesanan): array
    {
        $alamat = $pesanan->alamat;

        return [
            'first_name' => (string) ($alamat?->nama_penerima ?: $pesanan->user?->name ?: 'Pembeli'),
            'email' => (string) ($pesanan->user?->email ?: ''),
            'phone' => (string) ($alamat?->no_hp ?: ''),
        ];
    }

    /**
     * Rincian belanja, termasuk ongkir sebagai barisnya sendiri.
     *
     * Midtrans menolak transaksi bila jumlah item_details tidak sama persis
     * dengan gross_amount, jadi selisih pembulatan apa pun ditutup dengan satu
     * baris penyesuaian alih-alih membiarkan tagihannya gagal dibuat.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function rincian(Pesanan $pesanan): array
    {
        $baris = [];

        foreach ($pesanan->items as $item) {
            $baris[] = [
                'id' => (string) $item->produk_id,
                'name' => mb_substr((string) $item->nama_produk, 0, 50),
                'price' => (int) round($item->harga),
                'quantity' => (int) $item->qty,
            ];
        }

        if ((int) round($pesanan->ongkir) > 0) {
            $baris[] = [
                'id' => 'ongkir',
                'name' => mb_substr('Ongkir '.$pesanan->kurir, 0, 50),
                'price' => (int) round($pesanan->ongkir),
                'quantity' => 1,
            ];
        }

        $selisih = (int) round($pesanan->total) - collect($baris)->sum(fn ($b) => $b['price'] * $b['quantity']);

        if ($selisih !== 0) {
            $baris[] = [
                'id' => 'penyesuaian',
                'name' => $selisih > 0 ? 'Penyesuaian' : 'Potongan',
                'price' => $selisih,
                'quantity' => 1,
            ];
        }

        return $baris;
    }
}
