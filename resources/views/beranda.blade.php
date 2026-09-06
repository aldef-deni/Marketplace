<x-layouts.guest>
    <x-slot name="title">Beranda</x-slot>

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-ink-950 via-brand-950 to-brand-900">
        <div class="pointer-events-none absolute inset-0 pola-grid opacity-60"></div>
        <div class="pointer-events-none absolute -left-32 top-1/4 h-[28rem] w-[28rem] rounded-full bg-brand-600/35 blur-3xl"></div>
        <div class="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-accent-500/20 blur-3xl"></div>
        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-accent-500/60 to-transparent"></div>

        <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
            <div class="grid items-center gap-10 lg:grid-cols-2">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-xs font-bold text-white ring-1 ring-white/25 backdrop-blur">
                        Belanja Online Terpercaya #1 di Indonesia
                    </span>
                    <h1 class="mt-5 text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                        Belanja Mudah,<br>
                        <span class="teks-emas">Hemat & Aman</span>
                    </h1>
                    <p class="mt-5 max-w-lg text-base leading-relaxed text-ink-300 sm:text-lg">
                        Temukan ribuan produk berkualitas dengan harga terbaik.
                        Pembayaran fleksibel — transfer bank, e-wallet, hingga COD.
                        Dikirim cepat ke seluruh Indonesia.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('produk.index') }}" class="rounded-2xl bg-accent-500 px-7 py-3.5 text-sm font-bold text-ink-950 shadow-accent transition hover:-translate-y-0.5 hover:bg-accent-400">
                            Belanja Sekarang
                        </a>
                        <a href="#kategori" class="rounded-2xl bg-white/10 px-7 py-3.5 text-sm font-bold text-white ring-1 ring-white/30 backdrop-blur transition hover:bg-white/20">
                            Jelajahi Kategori
                        </a>
                    </div>
                    <div class="mt-10 grid max-w-md grid-cols-3 gap-4">
                        <div class="rounded-2xl bg-white/10 p-4 text-center ring-1 ring-white/15 backdrop-blur">
                            <p class="text-2xl font-extrabold text-white">1000+</p>
                            <p class="mt-1 text-xs font-medium text-brand-100">Produk Pilihan</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-4 text-center ring-1 ring-white/15 backdrop-blur">
                            <p class="text-2xl font-extrabold text-white">7+</p>
                            <p class="mt-1 text-xs font-medium text-brand-100">Metode Bayar</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-4 text-center ring-1 ring-white/15 backdrop-blur">
                            <p class="text-2xl font-extrabold text-white">34</p>
                            <p class="mt-1 text-xs font-medium text-brand-100">Provinsi Terjangkau</p>
                        </div>
                    </div>
                </div>
                <div class="hidden lg:block">
                    <div class="relative mx-auto max-w-lg">
                        {{-- Cahaya lembut di belakang ilustrasi, mengikuti warna merek. --}}
                        <div class="pointer-events-none absolute inset-8 rounded-full bg-gradient-to-br from-brand-500/40 via-brand-400/20 to-accent-500/30 blur-3xl"></div>

                        <picture>
                            <source srcset="{{ asset('images/hero-market.webp') }}" type="image/webp">
                            <img src="{{ asset('images/hero-market.png') }}"
                                 alt="Belanja di {{ config('brand.nama') }} — pesanan diantar ke seluruh Indonesia"
                                 width="1100" height="983"
                                 fetchpriority="high" decoding="async"
                                 class="relative w-full animate-float-soft drop-shadow-2xl">
                        </picture>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('partials.flash-sale')

    {{-- Kategori --}}
    <section id="kategori" class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between">
            <div>
                <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Jelajahi <span class="teks-gradien">Kategori</span></h2>
                <p class="mt-2 text-sm text-slate-500">Temukan produk sesuai kebutuhanmu</p>
            </div>
            <a href="{{ route('produk.index') }}" class="hidden text-sm font-bold text-brand-600 transition hover:text-brand-800 sm:block">Lihat Semua →</a>
        </div>

        <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            @forelse ($kategoris as $kategori)
                <a href="{{ route('produk.index', ['kategori' => $kategori->slug]) }}"
                   class="group rounded-2xl bg-white p-6 text-center shadow-sm ring-1 ring-slate-200/70 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-brand-100 hover:ring-brand-200">
                    <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-50 to-accent-50 text-4xl ring-1 ring-brand-100 transition group-hover:scale-110">
                        <x-ikon :nama="$kategori->ikon" kelas="h-7 w-7 text-brand-700 transition group-hover:text-accent-500" />
                    </span>
                    <p class="mt-4 text-sm font-bold text-slate-800 group-hover:text-brand-700">{{ $kategori->nama }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ $kategori->produks_count }} produk</p>
                </a>
            @empty
                <p class="col-span-full text-center text-sm text-slate-400">Belum ada kategori.</p>
            @endforelse
        </div>
    </section>

    {{-- Toko pilihan --}}
    @if ($tokos->isNotEmpty())
        <section class="bg-white py-14">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-end justify-between">
                    <div>
                        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                            Toko <span class="teks-gradien">Pilihan</span>
                        </h2>
                        <p class="mt-2 text-sm text-slate-500">Lapak terpercaya dengan koleksi paling lengkap</p>
                    </div>
                    <a href="{{ route('toko.index') }}" class="text-sm font-bold text-brand-600 transition hover:text-brand-800">
                        Lihat Semua &rarr;
                    </a>
                </div>

                <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($tokos->take(4) as $toko)
                        @include('partials.kartu-toko', ['toko' => $toko])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Produk terbaru --}}
    <section class="bg-white py-14">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between">
                <div>
                    <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Produk <span class="teks-gradien">Terbaru</span></h2>
                    <p class="mt-2 text-sm text-slate-500">Koleksi pilihan terbaru di toko kami</p>
                </div>
                <a href="{{ route('produk.index') }}" class="text-sm font-bold text-brand-600 transition hover:text-brand-800">Lihat Semua →</a>
            </div>

            <div class="mt-8 grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($produkTerbaru as $produk)
                    @include('partials.kartu-produk', ['produk' => $produk])
                @endforeach
            </div>
        </div>
    </section>

    {{-- Keunggulan --}}
    <section class="border-t border-slate-200 bg-slate-50 py-14">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['truk', 'Pengiriman Cepat', 'Bekerja sama dengan kurir terpercaya ke seluruh Indonesia.'],
                    ['kartu', 'Pembayaran Fleksibel', 'Transfer bank, e-wallet, hingga COD. Pilih yang paling nyaman.'],
                    ['perisai', 'Aman & Terpercaya', 'Transaksi dipantau, pembayaran diverifikasi oleh admin.'],
                    ['label', 'Harga Terbaik', 'Produk berkualitas dengan harga bersaing dan promo rutin.'],
                ] as [$ikon, $judul, $deskripsi])
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-100 to-accent-100 text-brand-800"><x-ikon :nama="$ikon" kelas="h-6 w-6" /></span>
                        <h3 class="mt-4 text-base font-bold text-slate-900">{{ $judul }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ $deskripsi }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

</x-layouts.guest>