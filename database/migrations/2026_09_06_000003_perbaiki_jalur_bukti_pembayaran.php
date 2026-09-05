<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Menyeragamkan jalur bukti pembayaran dengan konvensi gambar produk.
 *
 * Bukti disimpan lewat disk "uploads" yang berakar di public/uploads, sehingga
 * nilai kolomnya hanya "bukti-pembayaran/berkas.png". Namun tampilan memakai
 * asset() yang menghasilkan "/bukti-pembayaran/berkas.png" — tanpa segmen
 * uploads — sehingga gambarnya selalu 404 dan admin memverifikasi pembayaran
 * tanpa benar-benar melihat buktinya.
 *
 * Kolom gambar produk sudah menyimpan awalan "uploads/", jadi baris lama
 * disamakan ke bentuk itu.
 *
 * Diproses baris per baris, bukan dengan CONCAT/SUBSTRING, agar migrasi ini
 * berjalan sama di MySQL produksi maupun SQLite yang dipakai pengujian.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->ubahJalur(
            fn (string $bukti) => ! Str::startsWith($bukti, ['uploads/', 'http://', 'https://']),
            fn (string $bukti) => 'uploads/'.$bukti,
        );
    }

    public function down(): void
    {
        $this->ubahJalur(
            fn (string $bukti) => Str::startsWith($bukti, 'uploads/'),
            fn (string $bukti) => Str::after($bukti, 'uploads/'),
        );
    }

    private function ubahJalur(callable $perlu, callable $ubah): void
    {
        DB::table('pembayarans')
            ->whereNotNull('bukti')
            ->select('id', 'bukti')
            ->orderBy('id')
            ->chunk(200, function ($baris) use ($perlu, $ubah) {
                foreach ($baris as $satu) {
                    if ($perlu($satu->bukti)) {
                        DB::table('pembayarans')
                            ->where('id', $satu->id)
                            ->update(['bukti' => $ubah($satu->bukti)]);
                    }
                }
            });
    }
};
