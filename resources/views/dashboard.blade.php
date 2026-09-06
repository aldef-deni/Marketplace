<x-layouts.app>
    <x-slot name="title">Dashboard</x-slot>

    <x-slot name="header">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900">Halo, {{ $user->name }}! </h2>
            <p class="mt-0.5 text-sm text-slate-500">Selamat datang kembali di {{ config('brand.nama') }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Profil pengguna --}}
        @php
            // Dirakit di sini, bukan di dalam atribut komponen: tanda kutip
            // ganda pada ekspresinya akan menutup atribut HTML lebih awal.
            $ringkasanAktivitas = $aktivitasBulanIni
                ? "Sudah membuat {$aktivitasBulanIni} pesanan bulan ini."
                : 'Belum ada pesanan bulan ini — yuk temukan produk favoritmu.';
        @endphp

        <x-kartu-profil :aktivitas="$ringkasanAktivitas">

            <a href="{{ route('produk.index') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-accent-500 px-5 py-2.5 text-sm font-bold text-ink-950 shadow-accent transition hover:-translate-y-0.5 hover:bg-accent-400">
                <x-ikon nama="toko" kelas="h-4 w-4" />
                Mulai Belanja
            </a>

            <a href="{{ route('profile.edit') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-white/[0.06] px-5 py-2.5 text-sm font-semibold text-ink-200 ring-1 ring-white/10 transition hover:bg-white/10">
                <x-ikon nama="pensil" kelas="h-4 w-4" />
                Edit Profil
            </a>
        </x-kartu-profil>

        {{-- Statistik --}}
        <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ([
                ['kotak', 'Total Pesanan', $stats['total_pesanan'], 'bg-brand-50 ring-brand-100'],
                ['jam', 'Menunggu Bayar', $stats['menunggu_pembayaran'], 'bg-amber-50 ring-amber-100'],
                ['truk', 'Dalam Perjalanan', $stats['dalam_perjalanan'], 'bg-sky-50 ring-sky-100'],
                ['centang', 'Pesanan Selesai', $stats['selesai'], 'bg-emerald-50 ring-emerald-100'],
            ] as [$ikon, $label, $nilai, $warna])
                <div class="card flex items-center gap-4 p-5">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl ring-1 {{ $warna }}"><x-ikon :nama="$ikon" kelas="h-6 w-6 text-slate-700" /></span>
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
                        <h3 class="text-base font-extrabold text-slate-900">Pesanan Terakhir</h3>
                        <a href="{{ route('pesanan.index') }}" class="text-sm font-bold text-brand-600 hover:text-brand-800">Lihat Semua →</a>
                    </div>
                    @if ($pesananTerakhir->isEmpty())
                        <div class="flex flex-col items-center py-14 text-center">
                            <span class="text-5xl"><x-ikon nama="kotak" kelas="h-10 w-10" /></span>
                            <p class="mt-3 text-sm font-semibold text-slate-500">Belum ada pesanan. Yuk mulai belanja!</p>
                            <a href="{{ route('produk.index') }}" class="btn-primary mt-5">Belanja Sekarang</a>
                        </div>
                    @else
                        <div class="mt-4 space-y-3">
                            @foreach ($pesananTerakhir as $pesanan)
                                <a href="{{ route('pesanan.show', $pesanan->no_invoice) }}"
                                   class="flex items-center gap-4 rounded-2xl border border-slate-100 p-4 transition hover:border-brand-200 hover:bg-brand-50/40">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-100 to-accent-100 text-xl">
                                        <x-ikon :nama="match ($pesanan->status) { 'menunggu_pembayaran' => 'jam', 'menunggu_konfirmasi' => 'papan', 'diproses' => 'kotak', 'dikirim' => 'truk', 'selesai' => 'centang', 'dibatalkan' => 'silang', default => 'kotak' }" kelas="h-5 w-5 text-slate-700" />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-bold text-slate-800">{{ $pesanan->no_invoice }}</p>
                                        <p class="text-xs text-slate-400">{{ $pesanan->items->sum('qty') }} item • {{ tanggalIndo($pesanan->created_at) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-extrabold text-brand-700">{{ rp($pesanan->total) }}</p>
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
                    <h3 class="text-base font-extrabold text-slate-900">Akses Cepat</h3>
                    <div class="mt-4 space-y-2">
                        @foreach ([
                            [route('produk.index'), 'toko', 'Belanja Produk', 'Jelajahi katalog lengkap'],
                            [route('keranjang.index'), 'keranjang', 'Keranjang Saya', 'Periksa item belanjaan'],
                            [route('pesanan.index'), 'kotak', 'Pesanan Saya', 'Pantau status pesanan'],
                            [route('alamat.index'), 'lokasi', 'Buku Alamat', 'Kelola alamat pengiriman'],
                            [route('profile.edit'), 'gerigi', 'Pengaturan Profil', 'Perbarui data akun'],
                        ] as [$url, $ikon, $judul, $ket])
                            <a href="{{ $url }}" class="flex items-center gap-3 rounded-2xl p-3 transition hover:bg-slate-50">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><x-ikon :nama="$ikon" kelas="h-5 w-5" /></span>
                                <div>
                                    <p class="text-sm font-bold text-slate-800">{{ $judul }}</p>
                                    <p class="text-xs text-slate-400">{{ $ket }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-3xl bg-gradient-to-br from-emerald-500 to-teal-600 p-6 text-white">
                    <p class="text-2xl"><x-ikon nama="hadiah" kelas="h-6 w-6" /></p>
                    <h3 class="mt-2 text-base font-extrabold">Butuh bantuan?</h3>
                    <p class="mt-1 text-sm text-emerald-50">Hubungi admin kami untuk pertanyaan seputar pesanan.</p>
                    <a href="mailto:halo@arahinn.com" class="mt-4 inline-block rounded-xl bg-white px-4 py-2 text-xs font-bold text-emerald-700">halo@arahinn.com</a>
                </div>
            </div>
        </div>

        {{-- Notifikasi terbaru --}}
        <div class="mt-6">
            @include('partials.panel-notifikasi')
        </div>
    </div>
</x-layouts.app>