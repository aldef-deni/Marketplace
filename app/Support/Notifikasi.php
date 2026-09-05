<?php

namespace App\Support;

use App\Models\Pesanan;
use App\Models\User;
use App\Notifications\NotifikasiPesanan;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Pengirim notifikasi transaksi.
 *
 * Dibungkus di satu tempat supaya controller tidak perlu tahu siapa saja yang
 * harus diberi tahu, dan supaya kegagalan pengiriman tidak pernah menggagalkan
 * transaksinya sendiri — pesanan yang sudah dibayar jauh lebih penting daripada
 * satu baris notifikasi.
 */
class Notifikasi
{
    /**
     * Beri tahu pembeli pemilik pesanan.
     */
    public static function kePembeli(Pesanan $pesanan, string $peristiwa, ?string $pesan = null): void
    {
        if (! $pesanan->user) {
            return;
        }

        self::kirim([$pesanan->user], $pesanan, $peristiwa, $pesan);
    }

    /**
     * Beri tahu seluruh admin dan superadmin.
     */
    public static function keAdmin(Pesanan $pesanan, string $peristiwa, ?string $pesan = null): void
    {
        $penerima = User::whereIn('role', ['admin', 'superadmin'])->get();

        if ($penerima->isEmpty()) {
            return;
        }

        self::kirim($penerima, $pesanan, $peristiwa, $pesan);
    }

    private static function kirim(iterable $penerima, Pesanan $pesanan, string $peristiwa, ?string $pesan): void
    {
        try {
            Notification::send($penerima, new NotifikasiPesanan($pesanan, $peristiwa, $pesan));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
