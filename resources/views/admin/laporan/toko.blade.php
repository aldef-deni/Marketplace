<x-layouts.admin>
    <x-slot name="title">Laporan Toko</x-slot>

    @include('admin.laporan._filter', [
        'aksi' => route('admin.laporan.toko'),
        'unduhPdf' => route('admin.laporan.toko.pdf', $filter->sebagaiParameter()),
        'unduhExcel' => route('admin.laporan.toko.excel', $filter->sebagaiParameter()),
    ])

    {{-- Kondisi katalog. Angka-angka ini menggambarkan keadaan saat ini,
         bukan rentang tanggal — stok tidak punya riwayat per periode. --}}
    <div class="mt-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
        @foreach ([
            ['label', 'Total Produk', number_format($ringkasanToko['total_produk']), 'bg-brand-50 ring-brand-100'],
            ['folder', 'Kategori', number_format($ringkasanToko['total_kategori']), 'bg-sky-50 ring-sky-100'],
            ['kotak', 'Stok Tersedia', number_format($ringkasanToko['stok_total']).' unit', 'bg-emerald-50 ring-emerald-100'],
            ['uang', 'Nilai Stok', rpSingkat($ringkasanToko['nilai_stok']), 'bg-accent-50 ring-accent-100'],
        ] as [$ikon, $label, $nilai, $warna])
            <div class="card p-5">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl ring-1 {{ $warna }}">
                    <x-ikon :nama="$ikon" kelas="h-5 w-5 text-slate-700" />
                </span>
                <p class="mt-4 text-2xl font-extrabold text-slate-900">{{ $nilai }}</p>
                <p class="text-xs font-bold text-slate-500">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-4 grid grid-cols-2 gap-4 xl:grid-cols-4">
        @foreach ([
            ['Produk Aktif', $ringkasanToko['produk_aktif'], 'text-emerald-600'],
            ['Produk Nonaktif', $ringkasanToko['produk_nonaktif'], 'text-slate-400'],
            ['Stok Menipis (1–5)', $ringkasanToko['stok_menipis'], 'text-amber-600'],
            ['Stok Habis', $ringkasanToko['stok_habis'], 'text-rose-600'],
        ] as [$label, $nilai, $warna])
            <div class="card p-5">
                <p class="text-2xl font-extrabold {{ $warna }}">{{ number_format($nilai) }}</p>
                <p class="mt-0.5 text-xs font-bold text-slate-500">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    {{-- Kinerja penjualan pada rentang terpilih --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <div class="card p-5">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Pesanan pada Periode</p>
            <p class="mt-1 text-xl font-extrabold text-slate-900">{{ number_format($ringkasan['jumlah_pesanan']) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Unit Terjual</p>
            <p class="mt-1 text-xl font-extrabold text-slate-900">{{ number_format($ringkasan['unit_terjual']) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Omzet Periode</p>
            <p class="mt-1 text-xl font-extrabold text-slate-900">{{ rp($ringkasan['total']) }}</p>
        </div>
    </div>

    {{-- Per kategori --}}
    <div class="mt-6 card overflow-hidden">
        <div class="p-6 pb-4">
            <h3 class="text-base font-extrabold text-slate-900">Kinerja per Kategori</h3>
            <p class="mt-0.5 text-xs text-slate-400">Jumlah produk beserta penjualannya pada rentang terpilih</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[600px]">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="table-head">Kategori</th>
                        <th class="table-head text-center">Produk</th>
                        <th class="table-head text-center">Unit Terjual</th>
                        <th class="table-head text-right">Omzet</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($perKategori as $baris)
                        <tr class="transition hover:bg-slate-50/60">
                            <td class="table-cell font-bold text-slate-800">{{ $baris['kategori'] }}</td>
                            <td class="table-cell text-center">{{ $baris['produk'] }}</td>
                            <td class="table-cell text-center">{{ number_format($baris['unit']) }}</td>
                            <td class="table-cell text-right font-extrabold">{{ rp($baris['omzet']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-10 text-center text-sm text-slate-400">Belum ada kategori.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Kinerja produk --}}
    <div class="mt-6 card overflow-hidden">
        <div class="p-6 pb-4">
            <h3 class="text-base font-extrabold text-slate-900">Kinerja Produk</h3>
            <p class="mt-0.5 text-xs text-slate-400">
                Produk tanpa penjualan tetap ditampilkan — justru itu yang perlu diperhatikan
            </p>
        </div>

        <div class="max-h-[36rem] overflow-auto">
            <table class="w-full min-w-[820px]">
                <thead class="sticky top-0 bg-slate-50">
                    <tr>
                        <th class="table-head">Produk</th>
                        <th class="table-head">Kategori</th>
                        <th class="table-head text-right">Harga</th>
                        <th class="table-head text-center">Stok</th>
                        <th class="table-head text-center">Status</th>
                        <th class="table-head text-center">Terjual</th>
                        <th class="table-head text-right">Omzet</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($kinerjaProduk as $baris)
                        <tr class="transition hover:bg-slate-50/60">
                            <td class="table-cell font-semibold text-slate-800">{{ $baris['produk'] }}</td>
                            <td class="table-cell text-xs text-slate-500">{{ $baris['kategori'] }}</td>
                            <td class="table-cell text-right">{{ rp($baris['harga']) }}</td>
                            <td class="table-cell text-center">
                                <span class="font-bold {{ $baris['stok'] <= 0 ? 'text-rose-600' : ($baris['stok'] <= 5 ? 'text-amber-600' : 'text-slate-700') }}">
                                    {{ $baris['stok'] }}
                                </span>
                            </td>
                            <td class="table-cell text-center">
                                <span class="badge {{ $baris['status'] === 'Aktif' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-500 ring-slate-200' }}">
                                    {{ $baris['status'] }}
                                </span>
                            </td>
                            <td class="table-cell text-center font-bold">{{ number_format($baris['unit']) }}</td>
                            <td class="table-cell text-right font-extrabold">{{ rp($baris['omzet']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-sm text-slate-400">Belum ada produk.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.admin>
