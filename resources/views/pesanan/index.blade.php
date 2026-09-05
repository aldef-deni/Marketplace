<x-layouts.app>
    <x-slot name="title">Pesanan Saya</x-slot>

    <x-slot name="header">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900">📦 Pesanan Saya</h2>
            <p class="mt-0.5 text-sm text-slate-500">Pantau status pesananmu di sini</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Filter status --}}
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('pesanan.index') }}"
               class="rounded-full px-4 py-2 text-xs font-bold transition {{ ! $status ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                Semua
            </a>
            @foreach (\App\Models\Pesanan::STATUS as $nilai => $label)
                <a href="{{ route('pesanan.index', ['status' => $nilai]) }}"
                   class="rounded-full px-4 py-2 text-xs font-bold transition {{ $status === $nilai ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        @if ($pesanans->isEmpty())
            <div class="card mt-6 flex flex-col items-center py-24 text-center">
                <span class="text-7xl">📦</span>
                <h3 class="mt-6 text-xl font-extrabold text-slate-900">Belum ada pesanan</h3>
                <p class="mt-2 max-w-sm text-sm text-slate-500">Ayo mulai belanja dan lihat pesananmu tampil di sini.</p>
                <a href="{{ route('toko.index') }}" class="btn-primary mt-8">Mulai Belanja →</a>
            </div>
        @else
            <div class="mt-6 space-y-4">
                @foreach ($pesanans as $pesanan)
                    <a href="{{ route('pesanan.show', $pesanan->no_invoice) }}" class="card block p-5 transition hover:shadow-lg hover:shadow-indigo-100">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-100 to-violet-100 text-xl">
                                    {{ match ($pesanan->status) { 'menunggu_pembayaran' => '⏳', 'menunggu_konfirmasi' => '🔍', 'diproses' => '📦', 'dikirim' => '🚚', 'selesai' => '✅', 'dibatalkan' => '❌', default => '📋' } }}
                                </span>
                                <div>
                                    <p class="text-sm font-extrabold text-slate-900">{{ $pesanan->no_invoice }}</p>
                                    <p class="text-xs text-slate-400">{{ tanggalIndo($pesanan->created_at, true) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="text-right">
                                    <p class="text-xs text-slate-400">{{ $pesanan->items->sum('qty') }} item</p>
                                    <p class="text-sm font-extrabold text-indigo-700">{{ rp($pesanan->total) }}</p>
                                </div>
                                <span class="badge {{ $pesanan->status_warna }}">{{ $pesanan->status_label }}</span>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center gap-3 border-t border-slate-100 pt-4">
                            @if ($pesanan->pengiriman?->no_resi)
                                <span class="text-xs text-slate-500">📦 Resi: <span class="font-bold text-slate-700">{{ $pesanan->pengiriman->no_resi }}</span> ({{ $pesanan->kurir }})</span>
                            @else
                                <span class="text-xs text-slate-500">🚚 {{ $pesanan->kurir }} — {{ $pesanan->layanan_kurir }}</span>
                            @endif
                            <span class="ml-auto text-xs font-bold text-indigo-600">Lihat Detail →</span>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-8">{{ $pesanans->links() }}</div>
        @endif
    </div>

</x-layouts.app>