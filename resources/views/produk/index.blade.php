<x-layouts.guest>
    <x-slot name="title">Toko</x-slot>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-ink-950 via-brand-900 to-brand-800 p-8 sm:p-10">
            <div class="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full bg-accent-500/20 blur-3xl"></div>
            <div class="pointer-events-none absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-accent-500/60 to-transparent"></div>
            <div class="relative">
                <h1 class="text-2xl font-extrabold text-white sm:text-3xl">Katalog Produk</h1>
                <p class="mt-2 text-sm text-ink-300">Temukan produk impianmu dengan harga terbaik</p>
            </div>
        </div>

        <div class="mt-8 flex flex-col gap-8 lg:flex-row">
            {{-- Filter --}}
            <aside class="shrink-0 lg:w-60">
                <div class="card sticky top-24 p-5">
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-500">Kategori</h3>
                    <div class="mt-4 space-y-1">
                        <a href="{{ route('produk.index', array_merge(request()->except('kategori'), ['kategori' => ''])) }}"
                           class="flex items-center justify-between rounded-xl px-3 py-2 text-sm font-semibold transition {{ ! request('kategori') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50' }}">
                            <span>Semua Produk</span>
                        </a>
                        @foreach ($kategoris as $kategori)
                            <a href="{{ route('produk.index', ['kategori' => $kategori->slug] + request()->except('kategori')) }}"
                               class="flex items-center justify-between rounded-xl px-3 py-2 text-sm font-semibold transition {{ request('kategori') === $kategori->slug ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50' }}">
                                <span class="flex items-center gap-2"><x-ikon :nama="$kategori->ikon" kelas="h-4 w-4 shrink-0 opacity-70" />{{ $kategori->nama }}</span>
                            </a>
                        @endforeach
                    </div>

                    <h3 class="mt-6 text-sm font-extrabold uppercase tracking-wider text-slate-500">Urutkan</h3>
                    <div class="mt-4 space-y-1">
                        @foreach ([
                            '' => 'Terbaru',
                            'termurah' => 'Harga Termurah',
                            'termahal' => 'Harga Termahal',
                        ] as $nilai => $label)
                            <a href="{{ route('produk.index', ['urutkan' => $nilai] + request()->except('urutkan')) }}"
                               class="block rounded-xl px-3 py-2 text-sm font-semibold transition {{ request('urutkan', '') === $nilai ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </aside>

            {{-- Grid produk --}}
            <div class="flex-1">
                @if (request('q'))
                    <p class="mb-4 text-sm text-slate-500">
                        Hasil pencarian untuk <span class="font-bold text-slate-800">"{{ request('q') }}"</span>
                        <a href="{{ route('produk.index') }}" class="ml-2 font-bold text-brand-600 hover:underline">✕ Hapus</a>
                    </p>
                @endif

                @if ($produks->isEmpty())
                    <div class="card flex flex-col items-center py-20 text-center">
                        <span class="text-6xl"><x-ikon nama="cari" kelas="h-5 w-5" /></span>
                        <h3 class="mt-4 text-lg font-bold text-slate-800">Produk tidak ditemukan</h3>
                        <p class="mt-1 text-sm text-slate-500">Coba kata kunci atau kategori lain.</p>
                        <a href="{{ route('produk.index') }}" class="btn-primary mt-6">Lihat Semua Produk</a>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 xl:grid-cols-4">
                        @foreach ($produks as $produk)
                            @include('partials.kartu-produk', ['produk' => $produk])
                        @endforeach
                    </div>
                    <div class="mt-10">
                        {{ $produks->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

</x-layouts.guest>