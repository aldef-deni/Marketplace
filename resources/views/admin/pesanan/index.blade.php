<x-layouts.admin>
    <x-slot name="title">Manajemen Pesanan</x-slot>

    <div class="card overflow-hidden">
        <div class="p-6 pb-4">
            <h3 class="text-base font-extrabold text-slate-900">Daftar Pesanan</h3>
            <p class="mt-0.5 text-xs text-slate-400">Total {{ $pesanans->total() }} pesanan</p>
        </div>

        {{-- Filter status --}}
        <div class="flex flex-wrap gap-2 px-6 pb-4">
            <a href="{{ route('admin.pesanan.index') }}"
               class="rounded-full px-4 py-2 text-xs font-bold transition {{ ! request('status') ? 'bg-brand-600 text-white shadow-md shadow-brand-200' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                Semua ({{ $jumlahStatus->sum() }})
            </a>
            @foreach (\App\Models\Pesanan::STATUS as $nilai => $label)
                <a href="{{ route('admin.pesanan.index', ['status' => $nilai]) }}"
                   class="rounded-full px-4 py-2 text-xs font-bold transition {{ request('status') === $nilai ? 'bg-brand-600 text-white shadow-md shadow-brand-200' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                    {{ $label }} ({{ $jumlahStatus[$nilai] ?? 0 }})
                </a>
            @endforeach
        </div>

        <form method="GET" class="px-6 pb-4">
            <div class="flex gap-3">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari no. invoice / nama / email..." class="input-field max-w-md">
                <button class="btn-secondary">Cari</button>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px]">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="table-head">Invoice</th>
                        <th class="table-head">Pelanggan</th>
                        <th class="table-head">Item</th>
                        <th class="table-head">Total</th>
                        <th class="table-head">Pembayaran</th>
                        <th class="table-head">Status</th>
                        <th class="table-head">Tanggal</th>
                        <th class="table-head"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pesanans as $pesanan)
                        <tr class="transition hover:bg-slate-50/60">
                            <td class="table-cell">
                                <a href="{{ route('admin.pesanan.show', $pesanan) }}" class="font-extrabold text-brand-700 hover:underline">{{ $pesanan->no_invoice }}</a>
                            </td>
                            <td class="table-cell">
                                <p class="font-bold text-slate-800">{{ $pesanan->user->name }}</p>
                                <p class="text-xs text-slate-400">{{ $pesanan->user->email }}</p>
                            </td>
                            <td class="table-cell font-bold">{{ $pesanan->items->sum('qty') }} item</td>
                            <td class="table-cell font-extrabold text-slate-800">{{ rp($pesanan->total) }}</td>
                            <td class="table-cell">
                                <span class="text-xs font-semibold text-slate-500">{{ $pesanan->pembayaran?->metodePembayaran?->nama ?? '-' }}</span>
                                @if ($pesanan->pembayaran?->status === 'menunggu' && $pesanan->pembayaran?->metodePembayaran?->tipe !== 'cod')
                                    <span class="badge ml-1 bg-amber-100 text-amber-700 ring-amber-200">Belum Verif</span>
                                @endif
                            </td>
                            <td class="table-cell"><span class="badge {{ $pesanan->status_warna }}">{{ $pesanan->status_label }}</span></td>
                            <td class="table-cell text-xs text-slate-500">{{ tanggalIndo($pesanan->created_at) }}</td>
                            <td class="table-cell">
                                <a href="{{ route('admin.pesanan.show', $pesanan) }}" class="rounded-lg bg-brand-50 px-3 py-1.5 text-xs font-bold text-brand-700 transition hover:bg-brand-100">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-16 text-center">
                                <p class="text-4xl"><x-ikon nama="kotak" kelas="h-9 w-9" /></p>
                                <p class="mt-3 text-sm font-semibold text-slate-500">Tidak ada pesanan ditemukan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-6">{{ $pesanans->links('vendor.pagination.brand', ['satuan' => 'pesanan']) }}</div>
    </div>

</x-layouts.admin>