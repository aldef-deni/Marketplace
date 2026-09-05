<x-layouts.admin>
    <x-slot name="title">Dashboard Admin</x-slot>

    {{-- Profil pengelola --}}
    @php
        // Dirakit di sini, bukan di dalam atribut komponen: tanda kutip ganda
        // pada ekspresinya akan menutup atribut HTML lebih awal.
        $antre = $stats['menunggu_verifikasi'] + $stats['perlu_dikirim'];

        $ringkasanAktivitas = $antre > 0
            ? "Ada {$antre} pesanan yang menunggu tindakan Anda."
            : 'Tidak ada antrean yang menunggu tindakan. Semua terkendali.';
    @endphp

    <x-kartu-profil :aktivitas="$ringkasanAktivitas" class="mb-6">
        <a href="{{ route('profile.edit') }}"
           class="inline-flex items-center justify-center gap-2 rounded-xl bg-accent-500 px-5 py-2.5 text-sm font-bold text-ink-950 shadow-accent transition hover:-translate-y-0.5 hover:bg-accent-400">
            <x-ikon nama="pensil" kelas="h-4 w-4" />
            Edit Profil
        </a>

        @if (auth()->user()->isSuperadmin())
            <a href="{{ route('admin.pengguna.index') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-white/[0.06] px-5 py-2.5 text-sm font-semibold text-ink-200 ring-1 ring-white/10 transition hover:bg-white/10">
                <x-ikon nama="pengguna" kelas="h-4 w-4" />
                Kelola Pengguna
            </a>
        @else
            <a href="{{ route('admin.pesanan.index') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-white/[0.06] px-5 py-2.5 text-sm font-semibold text-ink-200 ring-1 ring-white/10 transition hover:bg-white/10">
                <x-ikon nama="kotak" kelas="h-4 w-4" />
                Kelola Pesanan
            </a>
        @endif
    </x-kartu-profil>

    {{-- Kartu statistik --}}
    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        @foreach ([
            ['uang', 'Pendapatan', rpSingkat($stats['pendapatan']), 'bg-emerald-50 ring-emerald-100', 'Total seluruh transaksi'],
            ['grafik', 'Bulan Ini', rpSingkat($stats['pendapatan_bulan_ini']), 'bg-brand-50 ring-brand-100', 'Pendapatan bulan berjalan'],
            ['kotak', 'Total Pesanan', $stats['pesanan'], 'bg-sky-50 ring-sky-100', 'Semua pesanan masuk'],
            ['jam', 'Pesanan Baru', $stats['pesanan_baru'], 'bg-amber-50 ring-amber-100', 'Menunggu tindakan admin'],
        ] as [$ikon, $label, $nilai, $warna, $ket])
            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl ring-1 {{ $warna }}"><x-ikon :nama="$ikon" kelas="h-5 w-5 text-slate-700" /></span>
                </div>
                <p class="mt-4 text-2xl font-extrabold text-slate-900">{{ $nilai }}</p>
                <p class="text-xs font-bold text-slate-500">{{ $label }}</p>
                <p class="mt-0.5 text-[11px] text-slate-400">{{ $ket }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-4 grid grid-cols-2 gap-4 xl:grid-cols-4">
        @foreach ([
            ['truk', 'Sedang Dikirim', $stats['dikirim'], 'bg-brand-50 ring-brand-100', 'Pesanan dalam perjalanan'],
            ['label', 'Produk Aktif', $stats['produk'], 'bg-accent-50 ring-accent-100', 'Jumlah produk di katalog'],
            ['peringatan', 'Stok Menipis', $stats['stok_menipis'], 'bg-rose-50 ring-rose-100', 'Stok ≤ 5, perlu restok'],
            ['kartu', 'Verifikasi Bayar', $stats['menunggu_verifikasi'], 'bg-teal-50 ring-teal-100', 'Bukti pembayaran menunggu'],
        ] as [$ikon, $label, $nilai, $warna, $ket])
            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl ring-1 {{ $warna }}"><x-ikon :nama="$ikon" kelas="h-5 w-5 text-slate-700" /></span>
                </div>
                <p class="mt-4 text-2xl font-extrabold text-slate-900">{{ $nilai }}</p>
                <p class="text-xs font-bold text-slate-500">{{ $label }}</p>
                <p class="mt-0.5 text-[11px] text-slate-400">{{ $ket }}</p>
            </div>
        @endforeach
    </div>

    {{-- Antrean tindakan: apa yang menunggu dikerjakan admin sekarang --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        @foreach ($perluTindakan as $tugas)
            <a href="{{ $tugas['url'] }}"
               class="card flex items-center gap-4 p-5 transition hover:-translate-y-0.5 hover:shadow-md
                      {{ $tugas['jumlah'] > 0 ? 'ring-brand-200' : '' }}">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $tugas['nada'] }}">
                    <x-ikon :nama="$tugas['ikon']" kelas="h-5 w-5" />
                </span>
                <div class="min-w-0">
                    <p class="text-2xl font-extrabold {{ $tugas['jumlah'] > 0 ? 'text-slate-900' : 'text-slate-300' }}">
                        {{ $tugas['jumlah'] }}
                    </p>
                    <p class="text-xs font-semibold leading-snug text-slate-500">{{ $tugas['label'] }}</p>
                </div>
            </a>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        {{-- Grafik penjualan --}}
        <div class="card p-6 xl:col-span-2">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-extrabold text-slate-900">Penjualan 7 Hari Terakhir</h3>
                <span class="badge bg-brand-50 text-brand-700 ring-brand-200">Total: {{ rpSingkat($penjualan7Hari->sum('total')) }}</span>
            </div>
            <div class="mt-6 flex h-56 items-end justify-between gap-3">
                @php $max = max(1, $penjualan7Hari->max('total')); @endphp
                @foreach ($penjualan7Hari as $hari)
                    <div class="flex flex-1 flex-col items-center gap-2">
                        <p class="text-[10px] font-bold text-slate-500">{{ rpSingkat($hari['total']) }}</p>
                        <div class="flex w-full flex-1 items-end">
                            <div class="w-full rounded-t-xl bg-gradient-to-t from-brand-600 to-accent-500 transition hover:from-brand-700"
                                 style="height: {{ max(4, round($hari['total'] / $max * 100)) }}%"
                                 title="{{ rp($hari['total']) }}"></div>
                        </div>
                        <p class="text-xs font-bold text-slate-600">{{ $hari['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Produk terlaris --}}
        <div class="card p-6">
            <h3 class="text-base font-extrabold text-slate-900">Produk Terlaris</h3>
            @if ($produkLaris->isEmpty())
                <p class="mt-6 text-sm text-slate-400">Belum ada penjualan tercatat.</p>
            @else
                <div class="mt-4 space-y-4">
                    @foreach ($produkLaris as $i => $p)
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-sm font-extrabold {{ $i === 0 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $i + 1 }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-slate-800">{{ $p->nama_produk }}</p>
                                <p class="text-xs text-slate-400">{{ $p->terjual }} terjual • {{ rpSingkat($p->omzet) }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Pesanan terbaru --}}
    <div class="card mt-6 overflow-hidden">
        <div class="flex items-center justify-between p-6 pb-4">
            <h3 class="text-base font-extrabold text-slate-900">Pesanan Terbaru</h3>
            <a href="{{ route('admin.pesanan.index') }}" class="text-sm font-bold text-brand-600 hover:text-brand-800">Lihat Semua →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px]">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="table-head">Invoice</th>
                        <th class="table-head">Pelanggan</th>
                        <th class="table-head">Total</th>
                        <th class="table-head">Metode</th>
                        <th class="table-head">Status</th>
                        <th class="table-head">Tanggal</th>
                        <th class="table-head"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($pesananTerbaru as $pesanan)
                        <tr class="transition hover:bg-slate-50/60">
                            <td class="table-cell font-extrabold text-brand-700">{{ $pesanan->no_invoice }}</td>
                            <td class="table-cell">
                                <p class="font-bold text-slate-800">{{ $pesanan->user->name }}</p>
                                <p class="text-xs text-slate-400">{{ $pesanan->user->email }}</p>
                            </td>
                            <td class="table-cell font-extrabold">{{ rp($pesanan->total) }}</td>
                            <td class="table-cell">
                                <span class="text-xs font-semibold text-slate-500">{{ $pesanan->pembayaran?->metodePembayaran?->nama ?? '-' }}</span>
                            </td>
                            <td class="table-cell"><span class="badge {{ $pesanan->status_warna }}">{{ $pesanan->status_label }}</span></td>
                            <td class="table-cell text-xs text-slate-500">{{ tanggalIndo($pesanan->created_at) }}</td>
                            <td class="table-cell">
                                <a href="{{ route('admin.pesanan.show', $pesanan) }}" class="rounded-lg bg-brand-50 px-3 py-1.5 text-xs font-bold text-brand-700 transition hover:bg-brand-100">Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Notifikasi terbaru --}}
    <div class="mt-6">
        @include('partials.panel-notifikasi')
    </div>

</x-layouts.admin>