{{-- Kerangka halaman galat.
     Halaman bawaan Laravel tidak menyediakan jalan keluar sama sekali; pengguna
     yang tiba di sana hanya bisa menekan tombol mundur peramban. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $kode }} — {{ config('brand.nama') }}</title>
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="{{ config('brand.warna.gelap') }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased">

<div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-gradient-to-b from-ink-950 via-brand-950 to-brand-900 px-6 py-16 text-center">
    <div class="pointer-events-none absolute inset-0 pola-grid opacity-60"></div>
    <div class="pointer-events-none absolute -left-32 top-1/4 h-[28rem] w-[28rem] rounded-full bg-brand-600/30 blur-3xl"></div>
    <div class="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-accent-500/20 blur-3xl"></div>

    <div class="relative w-full max-w-lg">
        <a href="{{ url('/') }}" class="inline-flex transition hover:opacity-90">
            <x-logo varian="landscape" kelas="mx-auto h-12 w-auto" />
        </a>

        <p class="mt-12 text-7xl font-extrabold tracking-tight text-white/15 sm:text-8xl">{{ $kode }}</p>

        <h1 class="-mt-6 text-2xl font-extrabold text-white sm:text-3xl">{{ $judul }}</h1>
        <p class="mx-auto mt-3 max-w-sm text-sm leading-relaxed text-ink-300">{{ $pesan }}</p>

        <div class="mt-9 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ url('/') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-accent-500 px-6 py-3 text-sm font-bold text-ink-950 shadow-accent transition hover:-translate-y-0.5 hover:bg-accent-400">
                Kembali ke Beranda
            </a>

            @auth
                <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-white/[0.06] px-6 py-3 text-sm font-semibold text-ink-200 ring-1 ring-white/10 transition hover:bg-white/10">
                    Ke Dashboard
                </a>
            @else
                <a href="{{ route('toko.index') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-white/[0.06] px-6 py-3 text-sm font-semibold text-ink-200 ring-1 ring-white/10 transition hover:bg-white/10">
                    Lihat Katalog
                </a>
            @endauth
        </div>

        <p class="mt-10 text-xs text-ink-500">
            Butuh bantuan? Hubungi
            <a href="mailto:{{ config('brand.email') }}" class="font-semibold text-ink-300 underline-offset-2 hover:underline">{{ config('brand.email') }}</a>
        </p>
    </div>
</div>

</body>
</html>
