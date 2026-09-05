<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} — Marketplace Nusantara</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">

    <div class="min-h-screen bg-slate-50">

        {{-- Navigasi atas --}}
        <nav class="sticky top-0 z-40 border-b border-slate-200/70 bg-white/90 backdrop-blur-lg">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <a href="{{ route('beranda') }}" class="flex items-center gap-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 to-fuchsia-600 text-lg text-white shadow-md shadow-indigo-200">🛍️</span>
                        <span class="hidden text-lg font-extrabold tracking-tight text-slate-900 sm:block">
                            Marketplace<span class="text-indigo-600">Nusantara</span>
                        </span>
                    </a>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('toko.index') }}" class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-indigo-700 sm:block">Toko</a>
                    <a href="{{ route('keranjang.index') }}" class="relative rounded-lg px-2.5 py-2 text-slate-600 transition hover:bg-slate-100" title="Keranjang">
                        <span class="text-xl">🛒</span>
                        @if (jmlKeranjang() > 0)
                            <span class="absolute -right-0.5 -top-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-rose-500 text-[10px] font-bold text-white">{{ jmlKeranjang() }}</span>
                        @endif
                    </a>

                    {{-- Menu profil --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center gap-2 rounded-full py-1 pl-1 pr-2 transition hover:bg-slate-100">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-500 text-xs font-bold text-white">
                                {{ initials(auth()->user()->name) }}
                            </span>
                            <span class="hidden text-sm font-semibold text-slate-700 sm:block">{{ auth()->user()->name }}</span>
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" @click.outside="open = false" x-transition
                             class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200 bg-white p-1.5 shadow-xl">
                            <div class="border-b border-slate-100 px-3 py-2">
                                <p class="truncate text-sm font-bold text-slate-800">{{ auth()->user()->name }}</p>
                                <p class="truncate text-xs text-slate-400">{{ auth()->user()->email }}</p>
                                <span class="badge mt-1.5 {{ auth()->user()->role_warna }}">{{ auth()->user()->role_label }}</span>
                            </div>
                            <a href="{{ route('dashboard') }}" class="mt-1 block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">📊 Dashboard</a>
                            <a href="{{ route('pesanan.index') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">📦 Pesanan Saya</a>
                            <a href="{{ route('alamat.index') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">📍 Buku Alamat</a>
                            <a href="{{ route('profile.edit') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">⚙️ Profil</a>
                            @if (auth()->user()->isAdmin())
                                <a href="{{ route('admin.dashboard') }}" class="block rounded-xl px-3 py-2 text-sm font-semibold text-indigo-600 hover:bg-indigo-50">🛠️ Panel Admin</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-slate-100 pt-1">
                                @csrf
                                <button type="submit" class="block w-full rounded-xl px-3 py-2 text-left text-sm font-medium text-rose-600 hover:bg-rose-50">🚪 Keluar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        {{-- Flash --}}
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

        {{-- Header halaman --}}
        @isset($header)
            <header class="border-b border-slate-200/70 bg-white">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main>
            {{ $slot }}
        </main>
    </div>

</body>
</html>