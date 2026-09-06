@props(['title' => null])

<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.meta', ['judul' => ($title ?? 'Panel Admin') . ' · Admin'])
</head>
<body class="font-sans antialiased">

<div x-data="{ sidebar: true }" class="min-h-screen bg-slate-100">

    {{-- Sidebar --}}
    <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-ink-950 text-ink-300 transition-transform duration-300 lg:translate-x-0">

        <div class="flex h-[4.5rem] items-center gap-3 border-b border-white/5 px-5">
            <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('admin.produk.index') }}"
               class="flex items-center transition hover:opacity-90">
                <x-logo varian="landscape" kelas="h-8 w-auto" />
            </a>
            <span class="rounded-md bg-accent-500/15 px-2 py-1 text-[10px] font-bold uppercase tracking-widest text-accent-400 ring-1 ring-accent-500/25">{{ auth()->user()->isPenjual() ? 'Penjual' : 'Admin' }}</span>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto p-4">
            @php
                $pengelola = auth()->user()->isAdmin();
                // Pengelola platform bertindak atas nama toko mana pun yang
                // aktif; penjual hanya atas nama lapaknya sendiri.
                $kelolaToko = $pengelola
                    ? \App\Models\Toko::tampil()->exists()
                    : \App\Models\Toko::where('user_id', auth()->id())->exists();
            @endphp

            {{-- Penjual tidak diberi menu tingkat platform. Rutenya pun sudah
                 dikunci di sisi server; ini hanya agar sidebarnya tidak
                 menampilkan pintu yang pasti tertutup. --}}
            @if ($pengelola)
            <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-widest text-ink-500">Menu Utama</p>
            <a href="{{ route('admin.dashboard') }}"
               class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.dashboard') ? 'bg-brand-600 text-white shadow-lg shadow-brand-900/30' : 'hover:bg-white/5 hover:text-white' }}">
                <x-ikon nama="dashboard" kelas="h-5 w-5" /> Dashboard
            </a>
            <a href="{{ route('admin.pesanan.index') }}"
               class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.pesanan.*') ? 'bg-brand-600 text-white shadow-lg shadow-brand-900/30' : 'hover:bg-white/5 hover:text-white' }}">
                <x-ikon nama="kotak" kelas="h-5 w-5" /> Pesanan
            </a>
            <a href="{{ route('admin.pembayaran.index') }}"
               class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.pembayaran.*') ? 'bg-brand-600 text-white shadow-lg shadow-brand-900/30' : 'hover:bg-white/5 hover:text-white' }}">
                <x-ikon nama="kartu" kelas="h-5 w-5" /> Pembayaran
            </a>
            <a href="{{ route('admin.pengiriman.index') }}"
               class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.pengiriman.*') ? 'bg-brand-600 text-white shadow-lg shadow-brand-900/30' : 'hover:bg-white/5 hover:text-white' }}">
                <x-ikon nama="truk" kelas="h-5 w-5" /> Pengiriman
            </a>


            <a href="{{ route('admin.laporan.transaksi') }}"
               class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.laporan.transaksi*') ? 'bg-brand-600 text-white shadow-lg shadow-brand-900/30' : 'hover:bg-white/5 hover:text-white' }}">
                <x-ikon nama="grafik" kelas="h-5 w-5" /> Laporan Transaksi
            </a>

            @if (auth()->user()->isSuperadmin())
                <a href="{{ route('admin.laporan.toko') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.laporan.toko*') ? 'bg-brand-600 text-white shadow-lg shadow-brand-900/30' : 'hover:bg-white/5 hover:text-white' }}">
                    <x-ikon nama="toko" kelas="h-5 w-5" /> Laporan Toko
                </a>
            @endif

            @endif

            {{-- Judulnya ikut disembunyikan saat tidak ada satu pun menu di
                 bawahnya; kepala seksi yang menggantung kosong terbaca sebagai
                 halaman yang rusak. --}}
            @if ($kelolaToko || auth()->user()->isSuperadmin())
            <p class="px-3 pb-2 pt-5 text-[10px] font-bold uppercase tracking-widest text-ink-500">Promo</p>

            @if ($kelolaToko)
                <a href="{{ route('admin.flash-sale.index') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.flash-sale.index') || request()->routeIs('admin.flash-sale.kelola') ? 'bg-brand-600 text-white shadow-lg shadow-brand-900/30' : 'hover:bg-white/5 hover:text-white' }}">
                    <x-ikon nama="api" kelas="h-5 w-5" /> Flash Sale
                </a>

                <a href="{{ route('admin.promo.index') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.promo.index') || request()->routeIs('admin.promo.kelola') ? 'bg-brand-600 text-white shadow-lg shadow-brand-900/30' : 'hover:bg-white/5 hover:text-white' }}">
                    <x-ikon nama="label" kelas="h-5 w-5" /> Promo
                </a>
            @endif

            @if (auth()->user()->isSuperadmin())
                <a href="{{ route('admin.flash-sale.kampanye.index') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.flash-sale.kampanye.*') ? 'bg-brand-600 text-white shadow-lg shadow-brand-900/30' : 'hover:bg-white/5 hover:text-white' }}">
                    <x-ikon nama="petir" kelas="h-5 w-5" /> Kampanye Flash Sale
                </a>
            @endif

            @if (auth()->user()->isSuperadmin() || $kelolaToko)
                <a href="{{ route('admin.promo.kampanye.index') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.promo.kampanye.*') ? 'bg-brand-600 text-white shadow-lg shadow-brand-900/30' : 'hover:bg-white/5 hover:text-white' }}">
                    <x-ikon nama="tambah" kelas="h-5 w-5" />
                    @if (auth()->user()->isSuperadmin())
                        Kampanye Promo
                    @else
                        {{ $pengelola ? 'Promo Toko' : 'Promo Saya' }}
                    @endif
                </a>
            @endif
            @endif

            <p class="px-3 pb-2 pt-5 text-[10px] font-bold uppercase tracking-widest text-ink-500">Katalog</p>

            <a href="{{ route('admin.toko.index') }}"
               class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.toko.*') ? 'bg-brand-600 text-white shadow-lg shadow-brand-900/30' : 'hover:bg-white/5 hover:text-white' }}">
                <x-ikon nama="toko" kelas="h-5 w-5" /> {{ $pengelola ? 'Toko' : 'Toko Saya' }}
            </a>

            <a href="{{ route('admin.produk.index') }}"
               class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.produk.*') ? 'bg-brand-600 text-white shadow-lg shadow-brand-900/30' : 'hover:bg-white/5 hover:text-white' }}">
                <x-ikon nama="label" kelas="h-5 w-5" /> Produk
            </a>
            @if ($pengelola)
                <a href="{{ route('admin.kategori.index') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.kategori.*') ? 'bg-brand-600 text-white shadow-lg shadow-brand-900/30' : 'hover:bg-white/5 hover:text-white' }}">
                    <x-ikon nama="folder" kelas="h-5 w-5" /> Kategori
                </a>
            @endif

            @if (auth()->user()->isSuperadmin())
                <p class="px-3 pb-2 pt-5 text-[10px] font-bold uppercase tracking-widest text-ink-500">Manajemen</p>
                <a href="{{ route('admin.pengguna.index') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.pengguna.*') ? 'bg-brand-600 text-white shadow-lg shadow-brand-900/30' : 'hover:bg-white/5 hover:text-white' }}">
                    <x-ikon nama="pengguna" kelas="h-5 w-5" /> Pengguna
                </a>
                <a href="{{ route('admin.metode-pembayaran.index') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.metode-pembayaran.*') ? 'bg-brand-600 text-white shadow-lg shadow-brand-900/30' : 'hover:bg-white/5 hover:text-white' }}">
                    <x-ikon nama="kartu" kelas="h-5 w-5" /> Metode Pembayaran
                </a>
            @endif
        </nav>

        <div class="border-t border-white/5 p-4">
            <a href="{{ route('beranda') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition hover:bg-white/5">
                <x-ikon nama="rumah" kelas="h-5 w-5" /> Lihat Toko
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-rose-400 transition hover:bg-white/5">
                    <x-ikon nama="keluar" kelas="h-5 w-5" /> Keluar
                </button>
            </form>
        </div>
    </aside>

    {{-- Overlay mobile --}}
    <div x-show="sidebar" x-cloak @click="sidebar = false" class="fixed inset-0 z-40 bg-ink-950/60 backdrop-blur-sm lg:hidden" x-transition.opacity></div>

    {{-- Konten --}}
    {{-- Pergeseran konten dipasang sebagai kelas statis juga, supaya tata letak
         tetap benar sebelum Alpine sempat aktif atau bila skrip gagal dimuat. --}}
    <div :class="sidebar ? 'lg:pl-64' : 'lg:pl-0'" class="flex min-h-screen flex-col transition-all duration-300 lg:pl-64">

        {{-- Top bar --}}
        <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-slate-200 bg-white/90 px-4 backdrop-blur-lg sm:px-6">
            <button @click="sidebar = !sidebar" class="rounded-xl p-2 text-slate-500 transition hover:bg-slate-100 lg:hidden">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div>
                <h1 class="text-sm font-extrabold text-slate-900 sm:text-base">{{ $title ?? 'Panel Admin' }}</h1>
                <p class="hidden text-xs text-slate-400 sm:block">{{ \Illuminate\Support\Carbon::now()->translatedFormat('l, d F Y') }}</p>
            </div>

            <div class="ml-auto flex items-center gap-3">
                <x-lonceng-notifikasi :gelap="false" />

                <a href="{{ route('produk.index') }}" class="hidden rounded-xl px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 sm:block">Toko</a>
                <div class="flex items-center gap-2.5 rounded-full bg-slate-50 py-1.5 pl-1.5 pr-4 ring-1 ring-slate-200">
                    <x-avatar :user="auth()->user()" ukuran="h-8 w-8" cincin="ring-1 ring-slate-200" />
                    <div class="hidden sm:block">
                        <p class="text-xs font-bold text-slate-800">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] font-medium text-slate-400">{{ auth()->user()->role_label }}</p>
                    </div>
                </div>
            </div>
        </header>

        @include('partials.flash')

        <main class="flex-1 p-4 sm:p-6">
            <div class="mx-auto max-w-7xl">
                {{ $slot }}
            </div>
        </main>

        <footer class="px-6 pb-6 text-center text-xs text-slate-400">
            &copy; {{ date('Y') }} {{ config('brand.nama') }} &mdash; Panel Admin
        </footer>
    </div>
</div>

@stack('skrip')

</body>
</html>