<x-layouts.admin>
    <x-slot name="title">Verifikasi Pembayaran</x-slot>

    <div class="card overflow-hidden">
        <div class="p-6 pb-4">
            <h3 class="text-base font-extrabold text-slate-900">💳 Verifikasi Pembayaran</h3>
            <p class="mt-0.5 text-xs text-slate-400">Periksa dan verifikasi bukti pembayaran pelanggan</p>
        </div>

        <div class="flex flex-wrap gap-2 px-6 pb-4">
            <a href="{{ route('admin.pembayaran.index') }}"
               class="rounded-full px-4 py-2 text-xs font-bold transition {{ ! request('status') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                Semua ({{ $jumlahStatus->sum() }})
            </a>
            @foreach (['menunggu' => 'Menunggu', 'dibayar' => 'Dibayar', 'dibatalkan' => 'Dibatalkan'] as $nilai => $label)
                <a href="{{ route('admin.pembayaran.index', ['status' => $nilai]) }}"
                   class="rounded-full px-4 py-2 text-xs font-bold transition {{ request('status') === $nilai ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                    {{ $label }} ({{ $jumlahStatus[$nilai] ?? 0 }})
                </a>
            @endforeach
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px]">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="table-head">Kode / Invoice</th>
                        <th class="table-head">Pelanggan</th>
                        <th class="table-head">Metode</th>
                        <th class="table-head">Jumlah</th>
                        <th class="table-head">Bukti</th>
                        <th class="table-head">Status</th>
                        <th class="table-head text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pembayarans as $pembayaran)
                        <tr class="transition hover:bg-slate-50/60">
                            <td class="table-cell">
                                <p class="font-extrabold text-indigo-700">{{ $pembayaran->kode }}</p>
                                <p class="text-xs text-slate-400">{{ $pembayaran->pesanan->no_invoice }}</p>
                            </td>
                            <td class="table-cell">
                                <p class="font-bold text-slate-800">{{ $pembayaran->pesanan->user->name }}</p>
                                <p class="text-xs text-slate-400">{{ $pembayaran->nama_pengirim ?? '-' }}</p>
                            </td>
                            <td class="table-cell text-xs font-semibold text-slate-500">{{ $pembayaran->metodePembayaran->nama }}</td>
                            <td class="table-cell font-extrabold">{{ rp($pembayaran->jumlah) }}</td>
                            <td class="table-cell">
                                @if ($pembayaran->bukti)
                                    <a href="{{ asset($pembayaran->bukti) }}" target="_blank">
                                        <img src="{{ asset($pembayaran->bukti) }}" class="h-12 w-12 rounded-xl object-cover ring-1 ring-slate-200 transition hover:scale-110" alt="Bukti">
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="table-cell">
                                <span class="badge {{ $pembayaran->status_warna }}">{{ $pembayaran->status_label }}</span>
                                @if ($pembayaran->keterangan && $pembayaran->status === 'menunggu' && $pembayaran->bukti)
                                    <p class="mt-1 max-w-40 text-[10px] text-rose-500" title="{{ $pembayaran->keterangan }}">⚠️ {{ \Illuminate\Support\Str::limit($pembayaran->keterangan, 28) }}</p>
                                @endif
                            </td>
                            <td class="table-cell">
                                @if ($pembayaran->status === 'menunggu' && $pembayaran->metodePembayaran->tipe !== 'cod')
                                    <div class="flex items-center justify-end gap-2">
                                        <form action="{{ route('admin.pembayaran.verifikasi', $pembayaran) }}" method="POST" onsubmit="return confirm('Verifikasi pembayaran ini?')">
                                            @csrf
                                            <button class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-emerald-700">✓ Verifikasi</button>
                                        </form>
                                        <form action="{{ route('admin.pembayaran.tolak', $pembayaran) }}" method="POST" onsubmit="const a = prompt('Alasan penolakan:'); if (!a) return false; this.querySelector('input[name=keterangan]').value = a;">
                                            @csrf
                                            <input type="hidden" name="keterangan">
                                            <button class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-600 ring-1 ring-rose-200 transition hover:bg-rose-100">✕ Tolak</button>
                                        </form>
                                    </div>
                                @else
                                    <a href="{{ route('admin.pesanan.show', $pembayaran->pesanan) }}" class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100">Detail</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center">
                                <p class="text-4xl">💳</p>
                                <p class="mt-3 text-sm font-semibold text-slate-500">Tidak ada pembayaran ditemukan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-6">{{ $pembayarans->links() }}</div>
    </div>

</x-layouts.admin>