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
                                {{ number_format($jumlahProduk) }} produk sedang berpromo{{ $jumlahKampanye > 1 ? ' dari '.$jumlahKampanye.' kampanye' : '' }}.
                                Harga khusus hanya berlaku selama kampanye berjalan dan kuotanya masih ada.
                            @else
                                Belum ada promo yang berjalan saat ini.
                            @endif
                        </p>
                    </div>

                    {{-- Nama kampanye tetap disebut meski kartunya digabung jadi
                         satu daftar: pembeli perlu tahu tenggat mana yang sedang
                         dihitung mundur di sebelahnya. --}}
                    @if ($kampanyeTerdekat)
                        <div class="shrink-0">
                            <p class="mb-2 text-sm font-extrabold text-slate-800 lg:text-right">
                                {{ $kampanyeTerdekat->nama }}
                            </p>

                            @include('partials.flash-hitung-mundur', [
                                'flashSale' => $kampanyeTerdekat,
                                'label' => $jumlahKampanye > 1 ? 'Kampanye terdekat berakhir' : 'Berakhir dalam',
                            ])
                        </div>
                    @endif
                </div>
            </div>

            {{-- Kisi produk: empat per baris di layar lebar, dua belas per
                 halaman sehingga baris terakhir selalu genap. --}}
            @if ($produks->isNotEmpty())
                <div class="mt-10 grid grid-cols-2 gap-4 sm:gap-5 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($produks as $baris)
                        @include('partials.kartu-flash', ['baris' => $baris])
                    @endforeach
                </div>

                @if ($produks->hasPages())
                    <div class="mt-10">
                        {{ $produks->links('vendor.pagination.brand') }}
                    </div>
                @endif
            @else
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

                    <a href="{{ route('produk.index') }}" class="btn-primary btn-kilat mt-7">Jelajahi Katalog</a>
                </div>
            @endif

            {{-- Cara kerja promo. Aturannya dijelaskan terbuka supaya pembeli
                 tidak merasa dikelabui saat harga kembali normal. --}}
            @if ($produks->isNotEmpty())
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
