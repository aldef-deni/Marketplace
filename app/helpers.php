<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

if (! function_exists('rp')) {
    /**
     * Format angka menjadi Rupiah, contoh: Rp 25.000
     */
    function rp(float|int|string|null $nilai): string
    {
        return 'Rp '.number_format((float) $nilai, 0, ',', '.');
    }
}

if (! function_exists('rpSingkat')) {
    /**
     * Format angka Rupiah singkat, contoh: Rp 25 rb, Rp 1,2 jt
     */
    function rpSingkat(float|int|string|null $nilai): string
    {
        $n = (float) $nilai;
        if ($n >= 1_000_000) {
            return 'Rp '.number_format($n / 1_000_000, 1, ',', '.').' jt';
        }
        if ($n >= 1_000) {
            return 'Rp '.number_format($n / 1_000, 0, ',', '.').' rb';
        }

        return 'Rp '.number_format($n, 0, ',', '.');
    }
}

if (! function_exists('tanggalIndo')) {
    /**
     * Format tanggal Indonesia, contoh: 5 September 2026
     */
    function tanggalIndo($tanggal, bool $denganJam = false): string
    {
        if (! $tanggal) {
            return '-';
        }

        $carbon = $tanggal instanceof Carbon ? $tanggal : Carbon::parse($tanggal);

        return $denganJam
            ? $carbon->translatedFormat('d F Y H:i')
            : $carbon->translatedFormat('d F Y');
    }
}

if (! function_exists('bulatkanRibuan')) {
    /**
     * Bulatkan ke atas ke kelipatan 1.000.
     */
    function bulatkanRibuan(float|int $nilai): int
    {
        return (int) (ceil($nilai / 1000) * 1000);
    }
}

if (! function_exists('initials')) {
    function initials(?string $nama): string
    {
        if (! $nama) {
            return '?';
        }
        $kata = preg_split('/\s+/', trim($nama));

        return Str::upper(Str::substr($kata[0], 0, 1).Str::substr(end($kata), 0, 1));
    }
}

if (! function_exists('jmlKeranjang')) {
    function jmlKeranjang(): int
    {
        return Auth::check()
            ? Auth::user()->keranjangs()->sum('qty')
            : 0;
    }
}

if (! function_exists('googleAktif')) {
    /**
     * Kredensial Google Sign-In sudah terisi.
     *
     * Dipakai untuk menyembunyikan tombol SSO di lingkungan yang belum
     * dikonfigurasi, alih-alih menampilkan tombol yang pasti gagal.
     */
    function googleAktif(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }
}
