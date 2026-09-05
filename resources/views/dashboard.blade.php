<x-layouts.app>
    <x-slot name="title">Dashboard</x-slot>

    <x-slot name="header">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900">Halo, {{ $user->name }}! 👋</h2>
            <p class="mt-0.5 text-sm text-slate-500">Selamat datang kembali di Marketplace Nusantara</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Kartu selamat datang --}}
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-600 via-violet-600 to-fuchsia-600 p-8">
            <div class="pointer-events-none absolute -right-10 -top-10 h-48 w-48 rounded-full bg-white/10 blur-2xl"></div>
            <div class="pointer-events-none absolute -bottom-16 right-24 h-40 w-40 rounded-full bg-fuchsia-300/20 blur-2xl"></div>
            <div class="relative flex flex-wrap items-center justify-between gap-6">
                <div>
                    <h3 class="text-xl font-extrabold text-white sm:text-2xl">Siap belanja hari ini? 🛍️</h3>
                    <p class="mt-1.5 max-w-md text-sm text-indigo-100">
                        Kamu {{ $aktivitasBulanIni ? "sudah membuat {$aktivitasBulanIni} pesanan bulan ini" : 'belum berbelanja bulan ini' }}.
                        Yuk temukan produk favoritmu!
                    </p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('toko.index') }}" class="rounded-2xl bg-white px-6 py-3 text-sm font-bold text-indigo-700 shadow-lg transition hover:-translate-y-0.5">Mulai Belanja →</a>
                </div>
            </div>
        </div>

        {{-- Statistik --}}
        <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ([
                ['📋', 'Total Pesanan', $stats['total_pesanan'], 'bg-indigo-50 ring-indigo-100'],
                ['⏳', 'Menunggu Bayar', $stats['menunggu_pembayaran'], 'bg-amber-50 ring-amber-100'],
                ['🚚', 'Dalam Perjalanan', $stats['dalam_perjalanan'], 'bg-sky-50 ring-sky-100'],
                ['✅', 'Pesanan Selesai', $stats['selesai'], 'bg-emerald-50 ring-emerald-100'],
            ] as [$ikon, $label, $nilai, $warna])
                <div class="card flex items-center gap-4 p-5">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-2xl ring-1 {{ $warna }}">{{ $ikon }}</span>
                    <div>
                        <p class="text-2xl font-extrabold text-slate-900">{{ $nilai }}</p>
                        <p class="text-xs font-semibold text-slate-400">{{ $label }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            {{-- Pesanan terakhir --}}
            <div class="lg:col-span-2">
                <div class="card p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-extrabold text-slate-900">📦 Pesanan Terakhir</h3>
                        <a href="{{ route('pesanan.index') }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-800">Lihat Semua →</a>
                    </div>
                    @if ($pesananTerakhir->isEmpty())
                        <div class="flex flex-col items-center py-14 text-center">
                            <span class="text-5xl">📦</span>
                            <p class="mt-3 text-sm font-semibold text-slate-500">Belum ada pesanan. Yuk mulai belanja!</p>
                            <a href="{{ route('toko.index') }}" class="btn-primary mt-5">Belanja Sekarang</a>
                        </div>
                    @else
                        <div class="mt-4 space-y-3">
                            @foreach ($pesananTerakhir as $pesanan)
                                <a href="{{ route('pesanan.show', $pesanan->no_invoice) }}"
                                   class="flex items-center gap-4 rounded-2xl border border-slate-100 p-4 transition hover:border-indigo-200 hover:bg-indigo-50/40">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-100 to-violet-100 text-xl">
                                        {{ match ($pesanan->status) { 'menunggu_pembayaran' => '⏳', 'menunggu_konfirmasi' => '🔍', 'diproses' => '📦', 'dikirim' => '🚚', 'selesai' => '✅', 'dibatalkan' => '❌', default => '📋' } }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-bold text-slate-800">{{ $pesanan->no_invoice }}</p>
                                        <p class="text-xs text-slate-400">{{ $pesanan->items->sum('qty') }} item • {{ tanggalIndo($pesanan->created_at) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-extrabold text-indigo-700">{{ rp($pesanan->total) }}</p>
                                        <span class="badge mt-1 {{ $pesanan->status_warna }}">{{ $pesanan->status_label }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Akses cepat --}}
            <div class="space-y-6">
                <div class="card p-6">
                    <h3 class="text-base font-extrabold text-slate-900">⚡ Akses Cepat</h3>
                    <div class="mt-4 space-y-2">
                        @foreach ([
                            [route('toko.index'), '🛒', 'Belanja Produk', 'Jelajahi katalog lengkap'],
                            [route('keranjang.index'), '🛍️', 'Keranjang Saya', 'Periksa item belanjaan'],
                            [route('pesanan.index'), '📦', 'Pesanan Saya', 'Pantau status pesanan'],
                            [route('alamat.index'), '📍', 'Buku Alamat', 'Kelola alamat pengiriman'],
                            [route('profile.edit'), '⚙️', 'Pengaturan Profil', 'Perbarui data akun'],
                        ] as [$url, $ikon, $judul, $ket])
                            <a href="{{ $url }}" class="flex items-center gap-3 rounded-2xl p-3 transition hover:bg-slate-50">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-lg">{{ $ikon }}</span>
                                <div>
                                    <p class="text-sm font-bold text-slate-800">{{ $judul }}</p>
                                    <p class="text-xs text-slate-400">{{ $ket }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-3xl bg-gradient-to-br from-emerald-500 to-teal-600 p-6 text-white">
                    <p class="text-2xl">🎁</p>
                    <h3 class="mt-2 text-base font-extrabold">Butuh bantuan?</h3>
                    <p class="mt-1 text-sm text-emerald-50">Hubungi admin kami untuk pertanyaan seputar pesanan.</p>
                    <a href="mailto:halo@marketplace.test" class="mt-4 inline-block rounded-xl bg-white px-4 py-2 text-xs font-bold text-emerald-700">halo@marketplace.test</a>
                </div>
            </div>
        </div>
    </div>

</x-layouts.app>