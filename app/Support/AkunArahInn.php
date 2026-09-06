<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Jembatan akun ke sistem induk ArahInn.
 *
 * Pengguna yang sudah terdaftar di ArahInn dapat langsung masuk ke marketplace
 * memakai surel dan kata sandi yang sama, tanpa mendaftar ulang. Saat berhasil,
 * akunnya disalin sekali ke basis data marketplace — sesudah itu ia menjadi
 * pengguna biasa di sini dan tidak lagi bergantung pada koneksi induk.
 *
 * Dua hal disengaja:
 *
 * - Basis data induk hanya dibaca, tidak pernah ditulis. Marketplace adalah
 *   tamu di sana, dan kesalahan di sini tidak boleh merusak sistem utamanya.
 * - Setiap kegagalan koneksi ditelan menjadi "tidak cocok", bukan galat. Induk
 *   yang sedang mati harus membuat login ArahInn gagal, bukan melumpuhkan
 *   login pengguna yang akunnya memang milik marketplace.
 */
class AkunArahInn
{
    /**
     * Apakah jembatan ini dikonfigurasi sama sekali.
     */
    public static function aktif(): bool
    {
        return filled(config('database.connections.arahinn.database'));
    }

    /**
     * Cari dan sahkan akun induk, lalu kembalikan pengguna lokalnya.
     *
     * Mengembalikan null bila jembatannya mati, akunnya tidak ada, kata sandinya
     * salah, atau akunnya tidak layak dipakai masuk.
     */
    public static function masuk(string $email, string $kataSandi): ?User
    {
        $baris = self::cari($email);

        if (! $baris || ! self::layak($baris)) {
            return null;
        }

        if (! Hash::check($kataSandi, $baris->password)) {
            return null;
        }

        return self::salin($baris, $kataSandi);
    }

    /**
     * Ambil satu baris pengguna dari basis data induk.
     */
    private static function cari(string $email): ?object
    {
        if (! self::aktif()) {
            return null;
        }

        try {
            return DB::connection('arahinn')
                ->table('users')
                ->where('email', $email)
                ->first(['name', 'email', 'password', 'phone', 'is_active', 'email_verified_at']);
        } catch (Throwable $e) {
            // Dicatat, bukan dilempar: pengguna marketplace tidak boleh gagal
            // masuk hanya karena basis data induk sedang tidak dapat dihubungi.
            Log::warning('Koneksi akun ArahInn gagal: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Akun yang boleh dipakai masuk.
     *
     * Akun tanpa kata sandi adalah akun OAuth di sistem induk — tidak ada yang
     * bisa dicocokkan, dan pemiliknya memang seharusnya memakai tombol masuk
     * dengan Google di sini.
     */
    private static function layak(object $baris): bool
    {
        return blank($baris->password) === false && (bool) $baris->is_active;
    }

    /**
     * Salin akun induk ke basis data marketplace.
     *
     * Peran selalu 'pengguna'. Jabatan di sistem induk tidak berarti apa-apa di
     * sini, dan menyalinnya berarti menyerahkan panel marketplace kepada
     * keputusan yang dibuat di sistem lain.
     */
    private static function salin(object $baris, string $kataSandi): User
    {
        $pengguna = User::where('email', $baris->email)->first();

        if ($pengguna) {
            // Kata sandi di induk bisa saja sudah berganti sejak salinan dibuat;
            // yang barusan terbukti benar itulah yang disimpan.
            if (! Hash::check($kataSandi, (string) $pengguna->password)) {
                $pengguna->forceFill(['password' => Hash::make($kataSandi)])->save();
            }

            return $pengguna;
        }

        $pengguna = new User;

        $pengguna->forceFill([
            'name' => $baris->name,
            'email' => $baris->email,
            'password' => Hash::make($kataSandi),
            'phone' => $baris->phone,
            'role' => 'pengguna',
            // Surel sudah disahkan di induk; meminta verifikasi ulang hanya
            // mengulang langkah yang sudah dilalui pemiliknya.
            'email_verified_at' => $baris->email_verified_at ?? now(),
        ])->save();

        return $pengguna;
    }
}
