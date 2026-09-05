<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Panel Admin' }} — Marketplace Nusantara</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">

<div x-data="{ sidebar: true }" class="min-h-screen bg-slate-100">

    {{-- Sidebar --}}
    <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-slate-900 text-slate-300 transition-transform duration-300 lg:translate-x-0">

        <div class="flex h-16 items-center gap-2 border-b border-slate-800 px-5">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-fuchsia-500 text-lg text-white shadow-lg shadow-indigo-900/40">🛍️</span>
            <div>
                <p class="text-sm font-extrabold text-white">Marketplace<span class="text-indigo-400">Nusantara</span></p>
                <p class="text-[11px] font-medium text-slate-500">Panel Admin</p>
            </div>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto p-4">
            <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-widest text-slate-600">Menu Utama</p>
            <a href="{{ route('admin.dashboard') }}"
               class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <span>📊</span> Dashboard
            </a>
            <a href="{{ route('admin.pesanan.index') }}"
               class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.pesanan.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <span>📦</span> Pesanan
            </a>
            <a href="{{ route('admin.pembayaran.index') }}"
               class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.pembayaran.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <span>💳</span> Pembayaran
            </a>
            <a href="{{ route('admin.pengiriman.index') }}"
               class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.pengiriman.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <span>🚚</span> Pengiriman
            </a>

            <p class="px-3 pb-2 pt-5 text-[10px] font-bold uppercase tracking-widest text-slate-600">Katalog</p>
            <a href="{{ route('admin.produk.index') }}"
               class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.produk.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <span>🏷️</span> Produk
            </a>
            <a href="{{ route('admin.kategori.index') }}"
               class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.kategori.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/30' : 'hover:bg-slate-800 hover:text-white' }}">
                <span>🗂️</span> Kategori
            </a>

            @if (auth()->user()->isSuperadmin())
                <p class="px-3 pb-2 pt-5 text-[10px] font-bold uppercase tracking-widest text-slate-600">Manajemen</p>
                <a href="{{ route('admin.pengguna.index') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.pengguna.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/30' : 'hover:bg-slate-800 hover:text-white' }}">
                    <span>👥</span> Pengguna
                </a>
                <a href="{{ route('admin.metode-pembayaran.index') }}"
                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.metode-pembayaran.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/30' : 'hover:bg-slate-800 hover:text-white' }}">
                    <span>💳</span> Metode Pembayaran
                </a>
            @endif
        </nav>

        <div class="border-t border-slate-800 p-4">
            <a href="{{ route('beranda') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition hover:bg-slate-800">
                <span>🏠</span> Lihat Toko
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-rose-400 transition hover:bg-slate-800">
                    <span>🚪</span> Keluar
                </button>
            </form>
        </div>
    </aside>

    {{-- Overlay mobile --}}
    <div x-show="sidebar" @click="sidebar = false" class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden" x-transition.opacity></div>

    {{-- Konten --}}
    <div :class="sidebar ? 'lg:pl-64' : ''" class="flex min-h-screen flex-col transition-all duration-300">

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
                <a href="{{ route('toko.index') }}" class="hidden rounded-xl px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 sm:block">🏠 Toko</a>
                <div class="flex items-center gap-2.5 rounded-full bg-slate-50 py-1.5 pl-1.5 pr-4 ring-1 ring-slate-200">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-500 text-xs font-bold text-white">
                        {{ initials(auth()->user()->name) }}
                    </span>
                    <div class="hidden sm:block">
                        <p class="text-xs font-bold text-slate-800">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] font-medium text-slate-400">{{ auth()->user()->role_label }}</p>
                    </div>
                </div>
            </div>
        </header>

        {{-- Flash --}}
        @if (session('success') || session('error') || session('info'))
            <div class="mx-auto mt-4 w-full max-w-7xl px-4 sm:px-6">
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

        <main class="flex-1 p-4 sm:p-6">
            <div class="mx-auto max-w-7xl">
                {{ $slot }}
            </div>
        </main>

        <footer class="px-6 pb-6 text-center text-xs text-slate-400">
            © {{ date('Y') }} Marketplace Nusantara — Panel Admin
        </footer>
    </div>
</div>

</body>
</html>