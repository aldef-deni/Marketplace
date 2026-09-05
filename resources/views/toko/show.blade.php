<x-layouts.guest>
    <x-slot name="title">{{ $produk->nama }}</x-slot>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        {{-- Breadcrumb --}}
        <nav class="mb-6 flex items-center gap-2 text-xs font-semibold text-slate-400">
            <a href="{{ route('beranda') }}" class="hover:text-brand-600">Beranda</a> <span>/</span>
            <a href="{{ route('toko.index') }}" class="hover:text-brand-600">Toko</a> <span>/</span>
            <a href="{{ route('toko.index', ['kategori' => $produk->kategori?->slug]) }}" class="hover:text-brand-600">{{ $produk->kategori?->nama }}</a> <span>/</span>
            <span class="text-slate-700">{{ \Illuminate\Support\Str::limit($produk->nama, 30) }}</span>
        </nav>

        <div class="grid gap-10 lg:grid-cols-2">
            {{-- Gambar --}}
            <div>
                <div class="overflow-hidden rounded-3xl bg-slate-100 ring-1 ring-slate-200">
                    @if ($produk->gambar)
                        <img src="{{ asset($produk->gambar) }}" alt="{{ $produk->nama }}" class="aspect-square w-full object-cover">
                    @else
                        <div class="flex aspect-square w-full items-center justify-center bg-gradient-to-br from-brand-100 to-accent-100 text-8xl"><x-ikon nama="toko" kelas="h-5 w-5" /></div>
                    @endif
                </div>
                <div class="mt-4 grid grid-cols-3 gap-4">
                    @foreach ([['truk', 'Pengiriman cepat', 'Ke seluruh Indonesia'], ['perisai', 'Garansi kualitas', 'Produk 100% original'], ['papan', 'Mudah dikembalikan', 'Jika produk tidak sesuai']] as [$ikon, $judul, $ket])
                        <div class="rounded-2xl bg-white p-3 text-center shadow-sm ring-1 ring-slate-200/70">
                            <x-ikon :nama="$ikon" kelas="h-5 w-5 text-brand-600" />
                            <p class="mt-1 text-xs font-bold text-slate-700">{{ $judul }}</p>
                            <p class="text-[10px] text-slate-400">{{ $ket }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Detail --}}
            <div>
                <span class="badge {{ $produk->kategori ? 'bg-brand-50 text-brand-700 ring-brand-200' : '' }}">{{ $produk->kategori?->nama }}</span>
                <h1 class="mt-3 text-2xl font-extrabold leading-snug text-slate-900 sm:text-3xl">{{ $produk->nama }}</h1>

                <div class="mt-4 flex items-baseline gap-3">
                    <p class="text-3xl font-extrabold text-brand-700">{{ rp($produk->harga) }}</p>
                    @if ($produk->harga_coret && $produk->harga_coret > $produk->harga)
                        <p class="text-lg font-medium text-slate-400 line-through">{{ rp($produk->harga_coret) }}</p>
                        <span class="badge bg-rose-100 text-rose-700 ring-rose-200">Hemat {{ $produk->diskon_persen }}%</span>
                    @endif
                </div>

                <div class="mt-5 grid grid-cols-2 gap-4 rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-200/70 sm:grid-cols-4">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Status</p>
                        <p class="mt-1 text-sm font-bold {{ $produk->stok > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $produk->stok > 0 ? 'Tersedia' : 'Habis' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Stok</p>
                        <p class="mt-1 text-sm font-bold text-slate-700">{{ $produk->stok }} pcs</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Berat</p>
                        <p class="mt-1 text-sm font-bold text-slate-700">{{ number_format($produk->berat) }} gram</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Terjual</p>
                        <p class="mt-1 text-sm font-bold text-slate-700">{{ rand(20, 500) }}+</p>
                    </div>
                </div>

                @auth
                    @if ($produk->stok > 0)
                        <form action="{{ route('keranjang.tambah', $produk) }}" method="POST" class="mt-6">
                            @csrf
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="flex items-center rounded-2xl ring-1 ring-slate-300">
                                    <button type="button" onclick="this.parentElement.querySelector('input').stepDown(); this.parentElement.querySelector('input').dispatchEvent(new Event('input'))" class="px-4 py-3 text-lg font-bold text-slate-500 hover:text-brand-600">−</button>
                                    <input type="number" name="qty" value="1" min="1" max="{{ $produk->stok }}"
                                           class="w-16 border-0 bg-transparent text-center text-sm font-bold text-slate-800 focus:ring-0">
                                    <button type="button" onclick="this.parentElement.querySelector('input').stepUp(); this.parentElement.querySelector('input').dispatchEvent(new Event('input'))" class="px-4 py-3 text-lg font-bold text-slate-500 hover:text-brand-600">+</button>
                                </div>
                                <button type="submit" class="btn-primary flex-1 py-3.5 sm:flex-none sm:px-10">
                                    Tambah ke Keranjang
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="mt-6 rounded-2xl bg-rose-50 px-5 py-4 text-sm font-bold text-rose-600 ring-1 ring-rose-200">
                            Mohon maaf, stok produk ini sedang habis.
                        </div>
                    @endif
                @else
                    <div class="mt-6 rounded-2xl bg-brand-50 px-5 py-4 text-sm text-brand-700 ring-1 ring-brand-200">
                        <a href="{{ route('login') }}" class="font-bold underline">Masuk</a> atau
                        <a href="{{ route('register') }}" class="font-bold underline">daftar</a> untuk mulai berbelanja.
                    </div>
                @endauth

                <div class="mt-8">
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-500">Deskripsi Produk</h3>
                    <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $produk->deskripsi }}</p>
                </div>
            </div>
        </div>

        {{-- Produk terkait --}}
        @if ($terkait->isNotEmpty())
            <section class="mt-16">
                <h2 class="text-xl font-extrabold text-slate-900">Produk <span class="teks-gradien">Terkait</span></h2>
                <div class="mt-6 grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 lg:grid-cols-4">
                    @foreach ($terkait as $produk)
                        @include('toko._kartu-produk', ['produk' => $produk])
                    @endforeach
                </div>
            </section>
        @endif
    </div>

</x-layouts.guest>