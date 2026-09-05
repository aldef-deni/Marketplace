<a href="{{ route('toko.show', $produk->slug) }}" class="kartu-produk group block">
    <div class="relative aspect-square overflow-hidden bg-slate-100">
        @if ($produk->gambar)
            <img src="{{ asset($produk->gambar) }}" alt="{{ $produk->nama }}"
                 class="h-full w-full object-cover transition duration-500 group-hover:scale-110" loading="lazy">
        @else
            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-indigo-100 to-violet-100 text-5xl">🛍️</div>
        @endif
        @if ($produk->diskon_persen)
            <span class="absolute left-3 top-3 rounded-full bg-rose-500 px-2.5 py-1 text-[10px] font-extrabold text-white shadow-lg">
                -{{ $produk->diskon_persen }}%
            </span>
        @endif
        @if ($produk->stok < 1)
            <span class="absolute inset-0 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm">
                <span class="rounded-full bg-white px-4 py-1.5 text-xs font-extrabold text-slate-800">Stok Habis</span>
            </span>
        @endif
    </div>
    <div class="p-4">
        <p class="truncate text-sm font-bold text-slate-800 transition group-hover:text-indigo-700">{{ $produk->nama }}</p>
        <p class="mt-0.5 text-xs font-medium text-slate-400">{{ $produk->kategori?->nama }}</p>
        <div class="mt-2 flex items-baseline gap-2">
            <p class="text-base font-extrabold text-indigo-700">{{ rp($produk->harga) }}</p>
            @if ($produk->harga_coret && $produk->harga_coret > $produk->harga)
                <p class="text-xs font-medium text-slate-400 line-through">{{ rp($produk->harga_coret) }}</p>
            @endif
        </div>
        <div class="mt-3 flex items-center justify-between">
            <span class="text-xs font-semibold {{ $produk->stok > 0 ? 'text-emerald-600' : 'text-rose-500' }}">
                {{ $produk->stok > 0 ? "Sisa {$produk->stok} pcs" : 'Habis' }}
            </span>
            @if ($produk->stok > 0)
                <form action="{{ route('keranjang.tambah', $produk) }}" method="POST">
                    @csrf
                    <button class="flex items-center gap-1.5 rounded-xl bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 transition hover:bg-indigo-600 hover:text-white">
                        🛒 Tambah
                    </button>
                </form>
            @endif
        </div>
    </div>
</a>