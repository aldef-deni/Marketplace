<x-layouts.admin>
    <x-slot name="title">Manajemen Pengiriman</x-slot>

    <div class="card overflow-hidden">
        <div class="p-6 pb-4">
            <h3 class="text-base font-extrabold text-slate-900">🚚 Manajemen Pengiriman</h3>
            <p class="mt-0.5 text-xs text-slate-400">Pantau seluruh pengiriman pesanan</p>
        </div>

        <div class="flex flex-wrap gap-2 px-6 pb-4">
            <a href="{{ route('admin.pengiriman.index') }}"
               class="rounded-full px-4 py-2 text-xs font-bold transition {{ ! request('status') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                Semua
            </a>
            @foreach (['menunggu' => 'Menunggu Kirim', 'dikirim' => 'Dalam Perjalanan', 'diterima' => 'Diterima'] as $nilai => $label)
                <a href="{{ route('admin.pengiriman.index', ['status' => $nilai]) }}"
                   class="rounded-full px-4 py-2 text-xs font-bold transition {{ request('status') === $nilai ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px]">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="table-head">Invoice</th>
                        <th class="table-head">Pelanggan</th>
                        <th class="table-head">Kurir</th>
                        <th class="table-head">No. Resi</th>
                        <th class="table-head">Tujuan</th>
                        <th class="table-head">Status</th>
                        <th class="table-head">Dikirim</th>
                        <th class="table-head"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pengirimans as $pengiriman)
                        <tr class="transition hover:bg-slate-50/60">
                            <td class="table-cell">
                                <a href="{{ route('admin.pesanan.show', $pengiriman->pesanan) }}" class="font-extrabold text-indigo-700 hover:underline">{{ $pengiriman->pesanan->no_invoice }}</a>
                            </td>
                            <td class="table-cell">
                                <p class="font-bold text-slate-800">{{ $pengiriman->pesanan->user->name }}</p>
                                <p class="text-xs text-slate-400">{{ $pengiriman->pesanan->user->phone ?? '-' }}</p>
                            </td>
                            <td class="table-cell text-xs font-semibold text-slate-500">
                                🚚 {{ $pengiriman->kurir }}<br><span class="text-slate-400">{{ $pengiriman->layanan }}</span>
                            </td>
                            <td class="table-cell">
                                @if ($pengiriman->no_resi)
                                    <span class="font-extrabold tracking-wider text-slate-800">{{ $pengiriman->no_resi }}</span>
                                @else
                                    <span class="badge bg-slate-100 text-slate-500 ring-slate-200">Belum ada</span>
                                @endif
                            </td>
                            <td class="table-cell text-xs text-slate-500">
                                {{ $pengiriman->pesanan->alamat->kota }}, {{ $pengiriman->pesanan->alamat->provinsi }}
                            </td>
                            <td class="table-cell">
                                <span class="badge {{ match ($pengiriman->status) { 'menunggu' => 'bg-amber-100 text-amber-700 ring-amber-200', 'dikirim' => 'bg-indigo-100 text-indigo-700 ring-indigo-200', 'diterima' => 'bg-emerald-100 text-emerald-700 ring-emerald-200', default => 'bg-slate-100 text-slate-600 ring-slate-200' } }}">
                                    {{ $pengiriman->status_label }}
                                </span>
                            </td>
                            <td class="table-cell text-xs text-slate-500">{{ $pengiriman->dikirim_at ? tanggalIndo($pengiriman->dikirim_at) : '-' }}</td>
                            <td class="table-cell">
                                <a href="{{ route('admin.pesanan.show', $pengiriman->pesanan) }}" class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-16 text-center">
                                <p class="text-4xl">🚚</p>
                                <p class="mt-3 text-sm font-semibold text-slate-500">Belum ada data pengiriman.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-6">{{ $pengirimans->links() }}</div>
    </div>

</x-layouts.admin>