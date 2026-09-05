<x-layouts.guest>
    <x-slot name="title">Beranda</x-slot>

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-indigo-700 via-violet-700 to-fuchsia-700">
        <div class="pointer-events-none absolute -left-20 -top-20 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 right-10 h-80 w-80 rounded-full bg-fuchsia-400/20 blur-3xl"></div>
        <div class="pointer-events-none absolute right-1/4 top-10 h-40 w-40 rounded-full bg-indigo-400/30 blur-2xl"></div>

        <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
            <div class="grid items-center gap-10 lg:grid-cols-2">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-xs font-bold text-white ring-1 ring-white/25 backdrop-blur">
                        ✨ Belanja Online Terpercaya #1 di Indonesia
                    </span>
                    <h1 class="mt-5 text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                        Belanja Mudah,<br>
                        <span class="bg-gradient-to-r from-amber-300 to-pink-300 bg-clip-text text-transparent">Hemat & Aman</span>
                    </h1>
                    <p class="mt-5 max-w-lg text-base leading-relaxed text-indigo-100 sm:text-lg">
                        Temukan ribuan produk berkualitas dengan harga terbaik.
                        Pembayaran fleksibel — transfer bank, e-wallet, hingga COD.
                        Dikirim cepat ke seluruh Indonesia.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('toko.index') }}" class="rounded-2xl bg-white px-7 py-3.5 text-sm font-bold text-indigo-700 shadow-xl shadow-indigo-900/20 transition hover:-translate-y-0.5 hover:bg-indigo-50">
                            🛒 Belanja Sekarang
                        </a>
                        <a href="#kategori" class="rounded-2xl bg-white/10 px-7 py-3.5 text-sm font-bold text-white ring-1 ring-white/30 backdrop-blur transition hover:bg-white/20">
                            Jelajahi Kategori
                        </a>
                    </div>
                    <div class="mt-10 grid max-w-md grid-cols-3 gap-4">
                        <div class="rounded-2xl bg-white/10 p-4 text-center ring-1 ring-white/15 backdrop-blur">
                            <p class="text-2xl font-extrabold text-white">1000+</p>
                            <p class="mt-1 text-xs font-medium text-indigo-100">Produk Pilihan</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-4 text-center ring-1 ring-white/15 backdrop-blur">
                            <p class="text-2xl font-extrabold text-white">7+</p>
                            <p class="mt-1 text-xs font-medium text-indigo-100">Metode Bayar</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-4 text-center ring-1 ring-white/15 backdrop-blur">
                            <p class="text-2xl font-extrabold text-white">34</p>
                            <p class="mt-1 text-xs font-medium text-indigo-100">Provinsi Terjangkau</p>
                        </div>
                    </div>
                </div>
                <div class="hidden lg:block">
                    <div class="relative mx-auto max-w-md">
                        <div class="absolute inset-0 -rotate-6 rounded-[2rem] bg-gradient-to-br from-amber-300 to-pink-400 opacity-60 blur-sm"></div>
                        <div class="relative rotate-3 rounded-[2rem] bg-white/95 p-8 shadow-2xl backdrop-blur">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Pesanan Terbaru</p>
                                    <p class="mt-1 text-xl font-extrabold text-slate-900">Pesanan #{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(6)) }}</p>
                                </div>
                                <span class="badge bg-emerald-100 text-emerald-700 ring-emerald-200">✓ Selesai</span>
                            </div>
                            <div class="mt-6 space-y-4">
                                <div class="flex items-center gap-4 rounded-2xl bg-slate-50 p-4">
                                    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-100 to-violet-100 text-2xl">📦</span>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800">Pesanan diproses</p>
                                        <p class="text-xs text-slate-400">Pengepakan oleh kurir terpercaya</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 rounded-2xl bg-slate-50 p-4">
                                    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-100 to-teal-100 text-2xl">🚚</span>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800">Dalam pengiriman</p>
                                        <p class="text-xs text-slate-400">Estimasi tiba 2-3 hari</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 rounded-2xl bg-slate-50 p-4">
                                    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-amber-100 to-orange-100 text-2xl">💳</span>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800">Bayar dengan mudah</p>
                                        <p class="text-xs text-slate-400">Transfer, e-wallet, atau COD</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Kategori --}}
    <section id="kategori" class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between">
            <div>
                <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Jelajahi <span class="teks-gradien">Kategori</span></h2>
                <p class="mt-2 text-sm text-slate-500">Temukan produk sesuai kebutuhanmu</p>
            </div>
            <a href="{{ route('toko.index') }}" class="hidden text-sm font-bold text-indigo-600 transition hover:text-indigo-800 sm:block">Lihat Semua →</a>
        </div>

        <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            @forelse ($kategoris as $kategori)
                <a href="{{ route('toko.index', ['kategori' => $kategori->slug]) }}"
                   class="group rounded-2xl bg-white p-6 text-center shadow-sm ring-1 ring-slate-200/70 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-indigo-100 hover:ring-indigo-200">
                    <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-50 to-violet-50 text-4xl ring-1 ring-indigo-100 transition group-hover:scale-110">
                        {{ $kategori->ikon }}
                    </span>
                    <p class="mt-4 text-sm font-bold text-slate-800 group-hover:text-indigo-700">{{ $kategori->nama }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ $kategori->produks_count }} produk</p>
                </a>
            @empty
                <p class="col-span-full text-center text-sm text-slate-400">Belum ada kategori.</p>
            @endforelse
        </div>
    </section>

    {{-- Produk terbaru --}}
    <section class="bg-white py-14">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between">
                <div>
                    <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Produk <span class="teks-gradien">Terbaru</span></h2>
                    <p class="mt-2 text-sm text-slate-500">Koleksi pilihan terbaru di toko kami</p>
                </div>
                <a href="{{ route('toko.index') }}" class="text-sm font-bold text-indigo-600 transition hover:text-indigo-800">Lihat Semua →</a>
            </div>

            <div class="mt-8 grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($produkTerbaru as $produk)
                    @include('toko._kartu-produk', ['produk' => $produk])
                @endforeach
            </div>
        </div>
    </section>

    {{-- Promo / penawaran --}}
    @if ($produkDiskon->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="rounded-3xl bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500 p-8 sm:p-12">
                <div class="flex flex-col items-start justify-between gap-6 lg:flex-row lg:items-center">
                    <div>
                        <span class="badge bg-white/20 text-white ring-white/30">🔥 Promo Spesial</span>
                        <h2 class="mt-3 text-2xl font-extrabold text-white sm:text-3xl">Hemat Besar Hari Ini!</h2>
                        <p class="mt-2 max-w-md text-sm text-orange-50">Jangan lewatkan produk dengan penawaran terbaik. Stok terbatas!</p>
                    </div>
                    <a href="{{ route('toko.index', ['urutkan' => 'termurah']) }}"
                       class="rounded-2xl bg-white px-7 py-3.5 text-sm font-bold text-orange-600 shadow-lg transition hover:-translate-y-0.5">
                        Lihat Semua Promo →
                    </a>
                </div>
                <div class="mt-8 grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-4">
                    @foreach ($produkDiskon as $produk)
                        @include('toko._kartu-produk', ['produk' => $produk])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Keunggulan --}}
    <section class="border-t border-slate-200 bg-slate-50 py-14">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['🚚', 'Pengiriman Cepat', 'Bekerja sama dengan kurir terpercaya ke seluruh Indonesia.'],
                    ['💳', 'Pembayaran Fleksibel', 'Transfer bank, e-wallet, hingga COD. Pilih yang paling nyaman.'],
                    ['🛡️', 'Aman & Terpercaya', 'Transaksi dipantau, pembayaran diverifikasi oleh admin.'],
                    ['🎁', 'Harga Terbaik', 'Produk berkualitas dengan harga bersaing dan promo rutin.'],
                ] as [$ikon, $judul, $deskripsi])
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-100 to-violet-100 text-2xl">{{ $ikon }}</span>
                        <h3 class="mt-4 text-base font-bold text-slate-900">{{ $judul }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ $deskripsi }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

</x-layouts.guest>