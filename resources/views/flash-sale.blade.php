<x-layouts.guest>
    <x-slot name="title">Flash Sale</x-slot>
    <x-slot name="deskripsi">Promo kilat {{ config('brand.nama') }} — harga khusus dengan kuota terbatas.</x-slot>

    <div class="relative">
        {{-- Bola cahaya bergerak sebagai latar seluruh halaman. Panel kaca di
             atasnya membiaskan warna-warna ini; tanpa mereka kacanya kosong. --}}
        <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[46rem] overflow-hidden">
            <div class="absolute -left-24 top-4 h-80 w-80 animate-geser-blob rounded-full bg-accent-400/45 blur-3xl"></div>
            <div class="absolute right-0 top-24 h-96 w-96 animate-geser-blob-2 rounded-full bg-brand-400/45 blur-3xl"></div>
            <div class="absolute left-1/3 top-72 h-72 w-72 animate-geser-blob-lambat rounded-full bg-rose-400/35 blur-3xl"></div>
        </div>

        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            {{-- Kepala halaman --}}
            <div class="kaca p-6 sm:p-8 lg:p-10">
                <div class="pointer-events-none absolute inset-0 animate-kilat bg-gradient-to-br from-white via-white to-accent-200/60"></div>
                <div class="pointer-events-none absolute inset-0 overflow-hidden">
                    <div class="absolute inset-y-0 w-full animate-sheen-sweep bg-kilau-kaca"></div>
                </div>

                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-accent-500 to-accent-400 px-3 py-1.5 text-[11px] font-extrabold uppercase tracking-wider text-ink-950 shadow-accent">
                            <x-ikon nama="petir" kelas="h-4 w-4 animate-denyut-petir" />
                            Flash Sale
                        </span>

                        <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">
                            Promo Kilat, <span class="teks-gradien">Kuota Terbatas</span>
                        </h1>

                        <p class="mt-3 max-w-xl text-sm leading-relaxed text-slate-600">
                            @if ($jumlahProduk > 0)
                                {{ $jumlahProduk }} produk sedang berpromo. Harga khusus hanya berlaku selama
                                kampanye berjalan dan kuotanya masih ada.
                            @else
                                Belum ada promo yang berjalan saat ini.
                            @endif
                        </p>
                    </div>

                    @if ($kampanyes->isNotEmpty())
                        @include('partials.flash-hitung-mundur', [
                            'flashSale' => $kampanyes->first(),
                            'label' => $kampanyes->count() > 1 ? 'Kampanye terdekat berakhir' : 'Berakhir dalam',
                        ])
                    @endif
                </div>
            </div>

            {{-- Kampanye berjalan --}}
            @forelse ($kampanyes as $kampanye)
                <section class="mt-10">
                    <div class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-200/80 pb-4">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">
                                    {{ $kampanye->nama }}
                                </h2>
                                <span class="badge bg-rose-100 text-rose-700 ring-rose-200">
                                    <x-ikon nama="petir" kelas="h-3 w-3" />
                                    Hemat sampai {{ $kampanye->produks->max(fn ($b) => $b->persen_hemat) }}%
                                </span>
                            </div>

                            @if ($kampanye->deskripsi)
                                <p class="mt-1.5 max-w-2xl text-sm text-slate-500">{{ $kampanye->deskripsi }}</p>
                            @endif
                        </div>

                        <p class="text-xs font-semibold text-slate-400">
                            Berakhir {{ tanggalIndo($kampanye->selesai_at, true) }}
                        </p>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                        @foreach ($kampanye->produks as $baris)
                            @include('toko._kartu-produk', ['produk' => $baris->produk])
                        @endforeach
                    </div>
                </section>
            @empty
                {{-- Kosong tanpa promo berjalan. Ditawarkan jadwal berikutnya bila
                     ada, sebab "kosong" saja tidak memberi alasan untuk kembali. --}}
                <div class="mt-10 rounded-3xl border-2 border-dashed border-slate-300 bg-white/70 p-12 text-center backdrop-blur sm:p-16">
                    <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-accent-100 text-accent-600">
                        <x-ikon nama="petir" kelas="h-7 w-7" />
                    </span>

                    <h2 class="mt-5 text-lg font-extrabold text-slate-800">Tidak ada flash sale yang berjalan</h2>

                    @if ($berikutnya)
                        <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-slate-500">
                            Kampanye berikutnya, <span class="font-bold text-slate-700">{{ $berikutnya->nama }}</span>,
                            dimulai {{ tanggalIndo($berikutnya->mulai_at, true) }}.
                        </p>
                    @else
                        <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-slate-500">
                            Belum ada jadwal promo berikutnya. Sementara itu, katalog kami tetap terbuka.
                        </p>
                    @endif

                    <a href="{{ route('toko.index') }}" class="btn-primary btn-kilat mt-7">Jelajahi Katalog</a>
                </div>
            @endforelse

            {{-- Cara kerja promo. Aturannya dijelaskan terbuka supaya pembeli
                 tidak merasa dikelabui saat harga kembali normal. --}}
            @if ($kampanyes->isNotEmpty())
                <div class="mt-12 grid gap-4 sm:grid-cols-3">
                    @foreach ([
                        ['jam', 'Berlaku selama kampanye', 'Harga promo otomatis kembali normal begitu waktunya habis.'],
                        ['kotak', 'Kuota terbatas', 'Tiap produk punya jatah promo. Setelah jatahnya habis, harganya kembali normal.'],
                        ['perisai', 'Harga yang Anda lihat', 'Harga promo yang tampil di sini juga yang dipakai saat checkout.'],
                    ] as [$ikon, $judul, $isi])
                        <div class="card p-5">
                            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-accent-50 ring-1 ring-accent-100">
                                <x-ikon :nama="$ikon" kelas="h-5 w-5 text-accent-600" />
                            </span>
                            <p class="mt-4 text-sm font-extrabold text-slate-800">{{ $judul }}</p>
                            <p class="mt-1 text-xs leading-relaxed text-slate-500">{{ $isi }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-layouts.guest>
