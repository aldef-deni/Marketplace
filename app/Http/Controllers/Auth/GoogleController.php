<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Contracts\User as PenggunaGoogle;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * Masuk dan mendaftar memakai akun Google.
 *
 * Alurnya: pengguna diarahkan ke Google, kembali ke callback dengan kode,
 * lalu dicocokkan dengan akun yang ada. Pencocokan dilakukan dua lapis —
 * lebih dulu berdasarkan google_id, baru berdasarkan surel — supaya seorang
 * pengguna tidak berakhir memiliki dua akun terpisah.
 */
class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! $this->siap()) {
            return redirect()->route('login')
                ->with('error', 'Masuk dengan Google belum tersedia. Hubungi pengelola situs.');
        }

        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! $this->siap()) {
            return redirect()->route('login')
                ->with('error', 'Masuk dengan Google belum tersedia. Hubungi pengelola situs.');
        }

        // Pengguna bisa menekan "Batal" di layar Google; itu bukan galat sistem.
        if ($request->filled('error')) {
            return redirect()->route('login')
                ->with('info', 'Proses masuk dengan Google dibatalkan.');
        }

        try {
            $akunGoogle = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            Log::warning('Google SSO gagal', ['pesan' => $e->getMessage()]);

            return redirect()->route('login')
                ->with('error', 'Gagal terhubung ke Google. Silakan coba lagi.');
        }

        if (blank($akunGoogle->getEmail())) {
            return redirect()->route('login')
                ->with('error', 'Akun Google Anda tidak membagikan alamat email, sehingga tidak bisa dipakai masuk.');
        }

        // Surel yang belum diverifikasi Google tidak boleh dipakai menautkan diri
        // ke akun yang sudah ada — itu jalan masuk untuk pengambilalihan akun.
        if (($akunGoogle->user['email_verified'] ?? false) !== true) {
            return redirect()->route('login')
                ->with('error', 'Email akun Google Anda belum terverifikasi oleh Google.');
        }

        $pengguna = $this->cocokkan($akunGoogle);

        Auth::login($pengguna, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Selamat datang, '.$pengguna->name.'!');
    }

    /**
     * Temukan akun yang sesuai, atau buat yang baru.
     */
    private function cocokkan(PenggunaGoogle $akunGoogle): User
    {
        $pengguna = User::where('google_id', $akunGoogle->getId())->first();

        if (! $pengguna) {
            $pengguna = User::where('email', $akunGoogle->getEmail())->first();
        }

        if ($pengguna) {
            $pengguna->forceFill([
                'google_id' => $akunGoogle->getId(),
                // Surel sudah dipastikan terverifikasi Google di atas.
                'email_verified_at' => $pengguna->email_verified_at ?? now(),
                // Avatar dan nama yang sudah disunting sendiri tidak ditimpa.
                'avatar' => $pengguna->avatar ?: $akunGoogle->getAvatar(),
            ])->save();

            return $pengguna;
        }

        // forceCreate dipakai karena email_verified_at sengaja tidak masuk
        // $fillable; seluruh nilai di bawah berasal dari kode ini sendiri,
        // bukan dari masukan pengguna, jadi aman melewati mass assignment.
        return User::forceCreate([
            'name' => $akunGoogle->getName() ?: strstr($akunGoogle->getEmail(), '@', true),
            'email' => $akunGoogle->getEmail(),
            'google_id' => $akunGoogle->getId(),
            'avatar' => $akunGoogle->getAvatar(),
            'email_verified_at' => now(),
            'password' => null,
            'role' => 'pengguna',
        ]);
    }

    /**
     * Kredensial Google sudah diisi di .env.
     */
    private function siap(): bool
    {
        // Paket Socialite dipasang lewat composer dan tidak ikut di dalam paket
        // rilis. Bila "composer install" terlewat di server, kelasnya tidak ada
        // dan pengguna harus melihat pesan yang wajar, bukan galat 500.
        if (! class_exists(Socialite::class)) {
            Log::error('Paket laravel/socialite belum terpasang. Jalankan "composer install --no-dev" di server.');

            return false;
        }

        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }
}
