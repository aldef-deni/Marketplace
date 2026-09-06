<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rapikan peran menjadi tiga, dan hanya tiga.
 *
 *   superadmin — administrator platform
 *   admin      — pemilik toko
 *   pengguna   — pembeli
 *
 * Peran 'penjual' lahir saat 'admin' masih berarti pengelola platform. Sejak
 * 'admin' menjadi pemilik toko, keduanya menggambarkan hal yang sama, dan dua
 * nama untuk satu peran adalah sumber kekeliruan hak akses yang paling mudah
 * terlewat.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'penjual')->update(['role' => 'admin']);

        // Peran yang tidak dikenali dikembalikan ke pembeli. Nilai asing pada
        // kolom peran akan lolos setiap penjaga rute yang menyebut peran secara
        // eksplisit, dan berujung pengguna tanpa panel sama sekali.
        DB::table('users')
            ->whereNotIn('role', ['superadmin', 'admin', 'pengguna'])
            ->update(['role' => 'pengguna']);
    }

    public function down(): void
    {
        // Pemilik toko dikembalikan ke 'penjual' hanya bila ia memang punya
        // lapak; tanpa itu, admin platform lama ikut terbawa turun.
        DB::table('users')
            ->whereIn('id', DB::table('tokos')->select('user_id'))
            ->where('role', 'admin')
            ->update(['role' => 'penjual']);
    }
};
