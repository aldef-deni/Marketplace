<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Marketplace Nusantara' }} — Belanja Mudah & Aman</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">

    {{-- Strip promosi --}}
    <div class="bg-gradient-to-r from-indigo-700 via-violet-700 to-fuchsia-700 py-2 text-center text-xs font-semibold text-white sm:text-sm">
        🎉 Gratis ongkir untuk pembelian di atas Rp 500.000 &nbsp;•&nbsp; Belanja mudah, aman, dan terpercaya
    </div>

    {{-- Navigasi --}}
    <header class="sticky top-0 z-40 border-b border-slate-200/70 bg-white/90 backdrop-blur-lg">
        <div class="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('beranda') }}" class="flex shrink-0 items-center gap-2">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 to-fuchsia-600 text-lg text-white shadow-md shadow-indigo-200">
                    🛍️
                </span>
                <span class="hidden text-lg font-extrabold tracking-tight text-slate-900 sm:block">
                    Marketplace<span class="text-indigo-600">Nusantara</span>
                </span>
            </a>

            {{-- Pencarian --}}
            <form action="{{ route('toko.index') }}" method="GET" class="hidden flex-1 md:block">
                <div class="relative">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari produk impianmu..."
                        class="w-full rounded-full border-slate-200 bg-slate-100 py-2.5 pl-10 pr-4 text-sm placeholder:text-slate-400 focus:border-indigo-400 focus:bg-white focus:ring-indigo-400">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">🔍</span>
                </div>
            </form>

            <nav class="ml-auto flex items-center gap-1 sm:gap-2">
                <a href="{{ route('toko.index') }}"
                   class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-indigo-700">Toko</a>

                @auth
                    <a href="{{ route('keranjang.index') }}" class="relative rounded-lg px-2.5 py-2 text-slate-600 transition hover:bg-slate-100" title="Keranjang">
                        <span class="text-xl">🛒</span>
                        @if (jmlKeranjang() > 0)
                            <span class="absolute -right-0.5 -top-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-rose-500 text-[10px] font-bold text-white">
                                {{ jmlKeranjang() }}
                            </span>
                        @endif
                    </a>

                    <a href="{{ route('dashboard') }}"
                       class="hidden rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 sm:block">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 transition hover:text-indigo-700">Masuk</a>
                    <a href="{{ route('register') }}"
                       class="rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">Daftar</a>
                @endauth
            </nav>
        </div>
    </header>

    {{-- Pesan flash --}}
    @if (session('success') || session('error') || session('info'))
        <div class="mx-auto mt-4 max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-sm">✅ {{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 shadow-sm">⛔ {{ session('error') }}</div>
            @endif
            @if (session('info'))
                <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-medium text-sky-800 shadow-sm">ℹ️ {{ session('info') }}</div>
            @endif
        </div>
    @endif

    <main>
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="mt-16 border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-10 md:grid-cols-4">
                <div class="md:col-span-2">
                    <div class="flex items-center gap-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 to-fuchsia-600 text-lg text-white">🛍️</span>
                        <span class="text-lg font-extrabold text-slate-900">Marketplace<span class="text-indigo-600">Nusantara</span></span>
                    </div>
                    <p class="mt-4 max-w-md text-sm leading-relaxed text-slate-500">
                        Platform belanja online terpercaya dengan ribuan produk pilihan.
                        Belanja mudah, pembayaran fleksibel, dan pengiriman cepat ke seluruh Indonesia.
                    </p>
                    <div class="mt-5 flex items-center gap-2">
                        <span class="rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 ring-1 ring-blue-200">BCA</span>
                        <span class="rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700 ring-1 ring-amber-200">Mandiri</span>
                        <span class="rounded-lg bg-cyan-50 px-3 py-1.5 text-xs font-bold text-cyan-700 ring-1 ring-cyan-200">BRI</span>
                        <span class="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-bold text-red-600 ring-1 ring-red-200">GoPay</span>
                        <span class="rounded-lg bg-purple-50 px-3 py-1.5 text-xs font-bold text-purple-700 ring-1 ring-purple-200">OVO</span>
                        <span class="rounded-lg bg-sky-50 px-3 py-1.5 text-xs font-bold text-sky-700 ring-1 ring-sky-200">DANA</span>
                        <span class="rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200">COD</span>
                    </div>
                </div>
                <div>
                    <h4 class="text-sm font-bold uppercase tracking-wider text-slate-900">Belanja</h4>
                    <ul class="mt-4 space-y-2.5 text-sm text-slate-500">
                        <li><a href="{{ route('toko.index') }}" class="transition hover:text-indigo-600">Semua Produk</a></li>
                        <li><a href="{{ route('toko.index', ['urutkan' => 'termurah']) }}" class="transition hover:text-indigo-600">Produk Termurah</a></li>
                        <li><a href="{{ route('beranda') }}#kategori" class="transition hover:text-indigo-600">Kategori</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-bold uppercase tracking-wider text-slate-900">Bantuan</h4>
                    <ul class="mt-4 space-y-2.5 text-sm text-slate-500">
                        @auth
                            <li><a href="{{ route('pesanan.index') }}" class="transition hover:text-indigo-600">Pesanan Saya</a></li>
                            <li><a href="{{ route('alamat.index') }}" class="transition hover:text-indigo-600">Buku Alamat</a></li>
                            <li><a href="{{ route('profile.edit') }}" class="transition hover:text-indigo-600">Profil Saya</a></li>
                        @else
                            <li><a href="{{ route('login') }}" class="transition hover:text-indigo-600">Masuk Akun</a></li>
                            <li><a href="{{ route('register') }}" class="transition hover:text-indigo-600">Daftar Akun</a></li>
                        @endauth
                        <li>Email: <a href="mailto:halo@marketplace.test" class="transition hover:text-indigo-600">halo@marketplace.test</a></li>
                    </ul>
                </div>
            </div>
            <div class="mt-10 flex flex-col items-center justify-between gap-3 border-t border-slate-100 pt-6 text-xs text-slate-400 sm:flex-row">
                <p>© {{ date('Y') }} Marketplace Nusantara. Seluruh hak cipta dilindungi.</p>
                <p>Dibuat dengan ❤️ untuk Indonesia</p>
            </div>
        </div>
    </footer>

</body>
</html>