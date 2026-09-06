@props(['title' => null, 'deskripsi' => null])

<!DOCTYPE html>
<html lang="id" class="scroll-pt-24">
<head>
    @include('partials.meta', ['judul' => $title, 'deskripsi' => $deskripsi])
</head>
<body class="font-sans antialiased">

{{-- Strip promosi --}}
<div class="bg-ink-950 py-2.5 text-center text-[11px] font-semibold tracking-wide text-ink-300 sm:text-xs">
    {{-- Di layar sempit kedua kalimat berdiri sendiri-sendiri; dipaksa satu
         baris, keduanya patah di tengah kata dan pemisah titiknya justru
         mendarat di awal baris kedua. --}}
    <span class="block sm:inline">
        <span class="text-accent-400">Gratis ongkir</span> untuk belanja di atas {{ rp(500000) }}
    </span>
    <span class="mx-2 hidden text-ink-700 sm:inline">&bull;</span>
    <span class="block sm:inline">
        <span class="hidden sm:inline">Pembayaran aman, pengiriman ke seluruh Indonesia</span>
        <span class="sm:hidden">Aman &amp; terpercaya</span>
    </span>
</div>

{{-- Navigasi utama --}}
<header x-data="{ cari: false }" class="sticky top-0 z-40 border-b border-white/5 bg-ink-950/95 backdrop-blur-xl">
    <div class="mx-auto flex h-[4.5rem] max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">

        <a href="{{ route('beranda') }}" class="flex shrink-0 items-center transition hover:opacity-90"
           aria-label="{{ config('brand.nama') }} — beranda">
            <x-logo varian="landscape" kelas="h-9 w-auto sm:h-10" />
        </a>

        {{-- Pencarian (desktop) --}}
        <form action="{{ route('produk.index') }}" method="GET" class="hidden flex-1 justify-center md:flex">
            <div class="relative w-full max-w-lg">
                <input type="search" name="q" value="{{ request('q') }}"
                       placeholder="Cari produk, merek, atau kategori…"
                       class="w-full rounded-full border-white/10 bg-white/[0.06] py-2.5 pl-11 pr-4 text-sm text-white placeholder:text-ink-400 focus:border-accent-500/60 focus:bg-white/10 focus:ring-1 focus:ring-accent-500/50">
                <svg class="pointer-events-none absolute inset-y-0 left-4 my-auto h-4 w-4 text-ink-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                </svg>
            </div>
        </form>

        <nav class="ml-auto flex items-center gap-1 sm:gap-1.5">
            {{-- Pencarian (mobile) --}}
            <button type="button" @click="cari = !cari"
                    class="rounded-lg p-2.5 text-ink-300 transition hover:bg-white/5 hover:text-white md:hidden"
                    aria-label="Cari produk">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                </svg>
            </button>

            <a href="{{ route('toko.index') }}"
               class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-ink-300 transition hover:bg-white/5 hover:text-white sm:block">
                Toko
            </a>

            <a href="{{ route('produk.index') }}"
               class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-ink-300 transition hover:bg-white/5 hover:text-white sm:block">
                Katalog
            </a>

            @auth
                <x-lonceng-notifikasi />

                <a href="{{ route('keranjang.index') }}"
                   class="relative rounded-lg p-2.5 text-ink-300 transition hover:bg-white/5 hover:text-white" title="Keranjang">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.5l1.7 10.2a1.5 1.5 0 001.48 1.25h8.9a1.5 1.5 0 001.47-1.19l1.4-6.76H5.2"/>
                        <circle cx="9" cy="19.5" r="1.4"/><circle cx="17" cy="19.5" r="1.4"/>
                    </svg>
                    @if (jmlKeranjang() > 0)
                        <span class="absolute right-0.5 top-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-accent-500 px-1 text-[10px] font-bold leading-none text-ink-950">
                            {{ jmlKeranjang() }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('dashboard') }}"
                   class="hidden rounded-full bg-accent-500 px-5 py-2.5 text-sm font-bold text-ink-950 shadow-accent transition hover:-translate-y-0.5 hover:bg-accent-400 sm:block">
                    Dashboard
                </a>
            @else
                <a href="{{ route('login') }}"
                   class="rounded-lg px-3 py-2 text-sm font-semibold text-ink-300 transition hover:text-white">Masuk</a>
                <a href="{{ route('register') }}"
                   class="rounded-full bg-accent-500 px-5 py-2.5 text-sm font-bold text-ink-950 shadow-accent transition hover:-translate-y-0.5 hover:bg-accent-400">
                    Daftar
                </a>
            @endauth
        </nav>
    </div>

    {{-- Pencarian mobile, muncul saat ikon ditekan --}}
    <div x-show="cari" x-cloak x-transition class="border-t border-white/5 px-4 py-3 md:hidden">
        <form action="{{ route('produk.index') }}" method="GET">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari produk…"
                   class="w-full rounded-full border-white/10 bg-white/[0.06] px-4 py-2.5 text-sm text-white placeholder:text-ink-400 focus:border-accent-500/60 focus:ring-1 focus:ring-accent-500/50">
        </form>
    </div>
</header>

@include('partials.flash')

<main>
    {{ $slot }}
</main>

{{-- Footer --}}
<footer class="mt-20 bg-ink-950 text-ink-300">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 gap-x-6 gap-y-10 md:grid-cols-12">

            <div class="col-span-2 text-center md:col-span-5">
                <a href="{{ route('beranda') }}" class="inline-flex transition hover:opacity-90"
                   aria-label="{{ config('brand.nama') }} — beranda">
                    <x-logo varian="landscape" kelas="h-16 w-auto sm:h-20" loading="lazy" />
                </a>

                <p class="mx-auto mt-6 max-w-md text-sm leading-relaxed text-ink-400">
                    {{ config('brand.deskripsi') }}
                </p>

            </div>

            <div class="col-span-1 text-center md:col-span-2 md:text-left">
                <h4 class="text-xs font-bold uppercase tracking-widest text-white">Belanja</h4>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('toko.index') }}" class="text-ink-400 transition hover:text-accent-400">Daftar Toko</a></li>
                    <li><a href="{{ route('produk.index') }}" class="text-ink-400 transition hover:text-accent-400">Semua Produk</a></li>
                    <li><a href="{{ route('produk.index', ['urutkan' => 'termurah']) }}" class="text-ink-400 transition hover:text-accent-400">Harga Termurah</a></li>
                    <li><a href="{{ route('produk.index', ['urutkan' => 'terbaru']) }}" class="text-ink-400 transition hover:text-accent-400">Produk Terbaru</a></li>
                    <li><a href="{{ route('beranda') }}#kategori" class="text-ink-400 transition hover:text-accent-400">Kategori</a></li>
                </ul>
            </div>

            <div class="col-span-1 text-center md:col-span-2 md:text-left">
                <h4 class="text-xs font-bold uppercase tracking-widest text-white">Akun</h4>
                <ul class="mt-4 space-y-2.5 text-sm">
                    @auth
                        <li><a href="{{ route('dashboard') }}" class="text-ink-400 transition hover:text-accent-400">Dashboard</a></li>
                        <li><a href="{{ route('pesanan.index') }}" class="text-ink-400 transition hover:text-accent-400">Pesanan Saya</a></li>
                        <li><a href="{{ route('alamat.index') }}" class="text-ink-400 transition hover:text-accent-400">Buku Alamat</a></li>
                        <li><a href="{{ route('profile.edit') }}" class="text-ink-400 transition hover:text-accent-400">Profil Saya</a></li>
                    @else
                        <li><a href="{{ route('login') }}" class="text-ink-400 transition hover:text-accent-400">Masuk</a></li>
                        <li><a href="{{ route('register') }}" class="text-ink-400 transition hover:text-accent-400">Daftar Akun</a></li>
                    @endauth
                </ul>
            </div>

            <div class="col-span-2 text-center md:col-span-3 md:text-left">
                <h4 class="text-xs font-bold uppercase tracking-widest text-white">Hubungi Kami</h4>
                <ul class="mt-4 space-y-2.5 text-sm text-ink-400">
                    <li><a href="mailto:{{ config('brand.email') }}" class="transition hover:text-accent-400">{{ config('brand.email') }}</a></li>
                    <li>
                        <a href="https://wa.me/{{ config('brand.whatsapp') }}" target="_blank" rel="noopener"
                           class="transition hover:text-accent-400">WhatsApp {{ config('brand.telepon') }}</a>
                    </li>
                    <li>
                        <a href="{{ config('brand.induk.url') }}" target="_blank" rel="noopener"
                           class="transition hover:text-accent-400">{{ config('brand.induk.nama') }} Group</a>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Metode pembayaran sebagai satu deret di atas garis penutup. Tanpa
             judul: nama-nama seperti BCA dan GoPay sudah menjelaskan dirinya,
             dan label di atasnya hanya menambah baris tanpa menambah arti.

             Dibaca dari tabel metode pembayaran, bukan daftar tetap: lencana di
             sini menjanjikan cara membayar, dan menjanjikan merchant yang
             nomornya belum dipasang adalah janji yang tidak bisa ditepati. --}}
        @if (($metodeBayar ?? collect())->isNotEmpty())
            <div class="mt-12 flex flex-wrap items-center justify-center gap-2">
                @foreach ($metodeBayar as $metode)
                    <span class="kartu-merchant" style="--warna-merchant: {{ $metode->warna_merchant }}">
                        {{ $metode->label_badge }}
                    </span>
                @endforeach
            </div>
        @endif

        <div class="divider-brand mt-8"></div>

        <p class="mt-6 text-center text-xs text-white">
            &copy; {{ date('Y') }} {{ config('brand.nama') }}. Seluruh hak cipta dilindungi.
        </p>
    </div>
</footer>

@stack('skrip')

</body>
</html>
