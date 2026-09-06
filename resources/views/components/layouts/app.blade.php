@props(['title' => null, 'deskripsi' => null])

<!DOCTYPE html>
<html lang="id" class="scroll-pt-24">
<head>
    @include('partials.meta', ['judul' => $title ?? 'Dashboard', 'deskripsi' => $deskripsi])
</head>
<body class="font-sans antialiased">

<div class="min-h-screen bg-slate-50">

    {{-- Navigasi atas --}}
    <nav class="sticky top-0 z-40 border-b border-white/5 bg-ink-950/95 backdrop-blur-xl">
        <div class="mx-auto flex h-[4.5rem] max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">

            <a href="{{ route('beranda') }}" class="flex shrink-0 items-center transition hover:opacity-90"
               aria-label="{{ config('brand.nama') }} — beranda">
                <x-logo varian="landscape" kelas="h-9 w-auto sm:h-10" />
            </a>

            <div class="flex items-center gap-1 sm:gap-1.5">
                <x-lonceng-notifikasi />

                <a href="{{ route('produk.index') }}"
                   class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-ink-300 transition hover:bg-white/5 hover:text-white sm:block">
                    Toko
                </a>

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

                {{-- Menu profil --}}
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" :aria-expanded="open"
                            class="flex items-center gap-2 rounded-full py-1 pl-1 pr-2 transition hover:bg-white/5">
                        <x-avatar :user="auth()->user()" />
                        <span class="hidden max-w-[9rem] truncate text-sm font-semibold text-ink-200 sm:block">{{ auth()->user()->name }}</span>
                        <svg class="h-4 w-4 text-ink-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="open" x-cloak @click.outside="open = false" x-transition
                         class="absolute right-0 mt-2 w-60 overflow-hidden rounded-2xl border border-slate-200 bg-white p-1.5 shadow-elevate">
                        <div class="border-b border-slate-100 px-3 py-2.5">
                            <p class="truncate text-sm font-bold text-slate-800">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-slate-400">{{ auth()->user()->email }}</p>
                            <span class="badge mt-2 {{ auth()->user()->role_warna }}">{{ auth()->user()->role_label }}</span>
                        </div>

                        <a href="{{ route('dashboard') }}" class="mt-1 block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-brand-50 hover:text-brand-700">Dashboard</a>
                        <a href="{{ route('pesanan.index') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-brand-50 hover:text-brand-700">Pesanan Saya</a>
                        <a href="{{ route('alamat.index') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-brand-50 hover:text-brand-700">Buku Alamat</a>
                        <a href="{{ route('profile.edit') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-brand-50 hover:text-brand-700">Profil</a>

                        @if (auth()->user()->isPengelola())
                            <a href="{{ route('admin.dashboard') }}" class="block rounded-xl px-3 py-2 text-sm font-bold text-brand-700 transition hover:bg-brand-50">Panel Admin</a>
                        @endif

                        <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-slate-100 pt-1">
                            @csrf
                            <button type="submit" class="block w-full rounded-xl px-3 py-2 text-left text-sm font-medium text-rose-600 transition hover:bg-rose-50">Keluar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    @include('partials.flash')

    {{-- Header halaman --}}
    @isset($header)
        <header class="border-b border-slate-200/70 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
    @endisset

    <main class="pb-16">
        {{ $slot }}
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-6 text-xs text-slate-400 sm:flex-row sm:px-6 lg:px-8">
            <p>&copy; {{ date('Y') }} {{ config('brand.nama') }}</p>
            <p>{{ config('brand.tagline') }}</p>
        </div>
    </footer>
</div>

@stack('skrip')

</body>
</html>
