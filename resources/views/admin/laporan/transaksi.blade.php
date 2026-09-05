<x-layouts.admin>
    <x-slot name="title">Laporan Transaksi</x-slot>

    @include('admin.laporan._filter', [
        'aksi' => route('admin.laporan.transaksi'),
        'unduhPdf' => route('admin.laporan.transaksi.pdf', $filter->sebagaiParameter()),
        'unduhExcel' => route('admin.laporan.transaksi.excel', $filter->sebagaiParameter()),
    ])

    {{-- Ringkasan --}}
    <div class="mt-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
        @foreach ([
            ['kotak', 'Jumlah Pesanan', number_format($ringkasan['jumlah_pesanan']), 'bg-brand-50 ring-brand-100'],
            ['label', 'Unit Terjual', number_format($ringkasan['unit_terjual']), 'bg-accent-50 ring-accent-100'],
            ['uang', 'Total Transaksi', rpSingkat($ringkasan['total']), 'bg-emerald-50 ring-emerald-100'],
            ['grafik', 'Rata-rata / Pesanan', rpSingkat($ringkasan['rata_rata']), 'bg-sky-50 ring-sky-100'],
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

    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <div class="card p-5">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Subtotal Produk</p>
            <p class="mt-1 text-xl font-extrabold text-slate-900">{{ rp($ringkasan['subtotal']) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Ongkos Kirim Terkumpul</p>
            <p class="mt-1 text-xl font-extrabold text-slate-900">{{ rp($ringkasan['ongkir']) }}</p>
        </div>
    </div>

    {{-- Rincian --}}
    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        @foreach ([
            ['Per Status', $perStatus, 'Nilai'],
            ['Per Metode Pembayaran', $perMetode, 'Nilai'],
            ['Per Kurir', $perKurir, 'Ongkir'],
        ] as [$judul, $data, $kolomNilai])
            <div class="card p-6">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-500">{{ $judul }}</h3>

                <table class="mt-4 w-full">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="pb-2 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Nama</th>
                            <th class="pb-2 text-right text-[11px] font-bold uppercase tracking-wider text-slate-400">Jml</th>
                            <th class="pb-2 text-right text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $kolomNilai }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse ($data as $baris)
                            <tr>
                                <td class="py-2 text-sm font-semibold text-slate-700">{{ $baris['label'] }}</td>
                                <td class="py-2 text-right text-sm text-slate-500">{{ $baris['jumlah'] }}</td>
                                <td class="py-2 text-right text-sm font-bold text-slate-800">{{ rpSingkat($baris['nilai']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-center text-xs text-slate-400">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>

    {{-- Pelanggan teratas --}}
    <div class="mt-6 card p-6">
        <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-500">Pelanggan Teratas</h3>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[520px]">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="table-head">Pelanggan</th>
                        <th class="table-head text-right">Pesanan</th>
                        <th class="table-head text-right">Nilai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pelangganTeratas as $baris)
                        <tr>
                            <td class="table-cell">
                                <p class="font-bold text-slate-800">{{ $baris['nama'] }}</p>
                                <p class="text-xs text-slate-400">{{ $baris['email'] }}</p>
                            </td>
                            <td class="table-cell text-right">{{ $baris['jumlah'] }}</td>
                            <td class="table-cell text-right font-extrabold">{{ rp($baris['nilai']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-10 text-center text-sm text-slate-400">Belum ada transaksi pada rentang ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Daftar transaksi --}}
    <div class="mt-6 card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 p-6 pb-4">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">Daftar Transaksi</h3>
                <p class="mt-0.5 text-xs text-slate-400">{{ $transaksi->count() }} pesanan pada rentang terpilih</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px]">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="table-head">Invoice</th>
                        <th class="table-head">Tanggal</th>
                        <th class="table-head">Pelanggan</th>
                        <th class="table-head text-center">Item</th>
                        <th class="table-head">Metode</th>
                        <th class="table-head">Kurir</th>
                        <th class="table-head">Status</th>
                        <th class="table-head text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($transaksi as $baris)
                        <tr class="transition hover:bg-slate-50/60">
                            <td class="table-cell font-bold text-brand-700">{{ $baris['invoice'] }}</td>
                            <td class="table-cell text-xs text-slate-500">{{ tanggalIndo($baris['tanggal'], true) }}</td>
                            <td class="table-cell">
                                <p class="font-semibold text-slate-800">{{ $baris['pelanggan'] }}</p>
                                <p class="text-xs text-slate-400">{{ $baris['email'] }}</p>
                            </td>
                            <td class="table-cell text-center">{{ $baris['item'] }}</td>
                            <td class="table-cell text-xs">{{ $baris['metode'] }}</td>
                            <td class="table-cell text-xs">{{ $baris['kurir'] }}</td>
                            <td class="table-cell text-xs font-semibold">{{ $baris['status'] }}</td>
                            <td class="table-cell text-right font-extrabold">{{ rp($baris['total']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-16 text-center">
                                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                    <x-ikon nama="kotak" kelas="h-5 w-5" />
                                </span>
                                <p class="mt-3 text-sm font-semibold text-slate-500">Tidak ada transaksi pada kriteria ini.</p>
                                <p class="mt-1 text-xs text-slate-400">Coba longgarkan filter atau perlebar rentang tanggalnya.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.admin>
