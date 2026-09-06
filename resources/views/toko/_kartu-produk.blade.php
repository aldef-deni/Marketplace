<a href="{{ route('toko.show', $produk->slug) }}" class="kartu-produk group block">
    <div class="relative aspect-square overflow-hidden bg-slate-100">
        @if ($produk->gambar)
            <img src="{{ asset($produk->gambar) }}" alt="{{ $produk->nama }}"
                 class="h-full w-full object-cover transition duration-500 group-hover:scale-110" loading="lazy">
        @else
            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-brand-100 to-accent-100 text-5xl"><x-ikon nama="toko" kelas="h-10 w-10" /></div>
        @endif
        @php ($barisFlash = $produk->flashSaleBerlaku())

        {{-- Lencana flash sale menggantikan lencana diskon biasa: menampilkan
             keduanya sekaligus hanya membingungkan pembeli. --}}
        @if ($barisFlash)
            <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-accent-500 to-rose-500 px-2.5 py-1 text-[10px] font-extrabold text-white shadow-lg">
                <x-ikon nama="api" kelas="h-3 w-3" />
                −{{ $barisFlash->persen_hemat }}%
            </span>
        @elseif ($produk->diskon_persen)
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
        <p class="truncate text-sm font-bold text-slate-800 transition group-hover:text-brand-700">{{ $produk->nama }}</p>
        <p class="mt-0.5 text-xs font-medium text-slate-400">{{ $produk->kategori?->nama }}</p>
        <div class="mt-2 flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
            <p class="whitespace-nowrap text-base font-extrabold {{ $barisFlash ? 'text-rose-600' : 'text-brand-700' }}">
                {{ rp($produk->hargaEfektif()) }}
            </p>
            @if ($produk->hargaSebelumPotongan())
                <p class="whitespace-nowrap text-xs font-medium text-slate-400 line-through">{{ rp($produk->hargaSebelumPotongan()) }}</p>
            @endif
        </div>
        <div class="mt-3 flex items-center justify-between">
            @if ($barisFlash)
                <span class="text-xs font-semibold text-rose-600">Sisa {{ $barisFlash->sisaKuota() }} promo</span>
            @else
            <span class="text-xs font-semibold {{ $produk->stok > 0 ? 'text-emerald-600' : 'text-rose-500' }}">
                {{ $produk->stok > 0 ? "Sisa {$produk->stok} pcs" : 'Habis' }}
            </span>
            @endif
            @if ($produk->stok > 0)
                <form action="{{ route('keranjang.tambah', $produk) }}" method="POST">
                    @csrf
                    <button class="flex items-center gap-1.5 rounded-xl bg-brand-50 px-3 py-1.5 text-xs font-bold text-brand-700 transition hover:bg-brand-600 hover:text-white">
                        Tambah
                    </button>
                </form>
            @endif
        </div>
    </div>
</a>