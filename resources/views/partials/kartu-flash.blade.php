{{-- Kartu produk khusus halaman flash sale.

     Lebih besar daripada kartu katalog karena halamannya hanya memuat empat per
     baris, dan menampilkan satu hal yang tidak ada di kartu biasa: seberapa
     banyak jatah promonya sudah terpakai. Di dagang kilat, sisa kuota adalah
     alasan orang menekan tombol.

     Variabel: $baris (FlashSaleProduk). --}}
@php
    $produk = $baris->produk;
    $sisa = $baris->sisaKuota();
    $terjual = (int) $baris->terjual;
    $kuota = max(1, (int) $baris->kuota);
    $persenTerpakai = min(100, (int) round($terjual / $kuota * 100));
    $menipis = $sisa > 0 && $sisa <= max(1, (int) ceil($kuota * 0.25));
@endphp

<div class="kartu-flash group">
    <a href="{{ route('produk.show', $produk->slug) }}" class="absolute inset-0 z-10">
        <span class="sr-only">Lihat {{ $produk->nama }}</span>
    </a>

    <div class="relative aspect-square overflow-hidden bg-slate-100">
        @if ($produk->gambar)
            <img src="{{ asset($produk->gambar) }}" alt="{{ $produk->nama }}" draggable="false"
                 class="h-full w-full object-cover transition duration-500 ease-out group-hover:scale-105" loading="lazy">
        @else
            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-brand-100 to-accent-100">
                <x-ikon nama="toko" kelas="h-12 w-12 text-brand-400" />
            </div>
        @endif

        <span class="kilau-flash"></span>

        <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-accent-500 to-rose-500 px-2.5 py-1 text-[11px] font-extrabold text-white shadow-lg transition duration-300 group-hover:scale-105">
            <x-ikon nama="petir" kelas="h-3.5 w-3.5" />
            −{{ $baris->persen_hemat }}%
        </span>

        @if ($menipis)
            <span class="absolute right-3 top-3 rounded-full bg-white/95 px-2.5 py-1 text-[11px] font-extrabold text-rose-600 shadow-sm backdrop-blur">
                Hampir habis
            </span>
        @endif
    </div>

    <div class="flex flex-1 flex-col p-4">
        <p class="line-clamp-2 text-sm font-bold leading-snug text-slate-800 transition group-hover:text-brand-700 sm:text-[15px]">
            {{ $produk->nama }}
        </p>

        <p class="mt-1 flex items-center gap-1 text-xs font-medium text-slate-400">
            <x-ikon nama="toko" kelas="h-3.5 w-3.5 shrink-0" />
            <span class="truncate">{{ $produk->toko?->nama }}</span>
        </p>

        <div class="mt-auto pt-3">
            <p class="text-lg font-extrabold text-rose-600 sm:text-xl">{{ rp($baris->harga_flash) }}</p>
            <p class="text-xs font-medium text-slate-400 line-through">{{ rp($produk->harga) }}</p>

            {{-- Bilah jatah promo: angka terjual saja sulit dibayangkan, sedangkan
                 bilah yang hampir penuh langsung terbaca sebagai "tinggal sedikit". --}}
            <div class="mt-3">
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-gradient-to-r from-accent-500 to-rose-500 transition-[width] duration-500"
                         style="width: {{ max(6, $persenTerpakai) }}%"></div>
                </div>
                <p class="mt-1.5 text-[11px] font-bold {{ $menipis ? 'text-rose-600' : 'text-slate-500' }}">
                    {{ $terjual > 0 ? $terjual.' terjual · ' : '' }}Sisa {{ $sisa }} promo
                </p>
            </div>

            @if ($produk->stok > 0)
                <form action="{{ route('keranjang.tambah', $produk) }}" method="POST" class="relative z-20 mt-3">
                    @csrf
                    <button class="w-full rounded-xl bg-brand-50 py-2.5 text-xs font-bold text-brand-700 transition duration-300 hover:bg-brand-600 hover:text-white group-hover:bg-brand-600 group-hover:text-white sm:text-sm">
                        Tambah ke Keranjang
                    </button>
                </form>
            @else
                <p class="mt-3 rounded-xl bg-slate-100 py-2.5 text-center text-xs font-bold text-slate-400">Stok habis</p>
            @endif
        </div>
    </div>
</div>
