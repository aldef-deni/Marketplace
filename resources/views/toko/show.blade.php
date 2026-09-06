<x-layouts.guest>
    <x-slot name="title">{{ $toko->nama }}</x-slot>
    <x-slot name="deskripsi">{{ $toko->deskripsi ?? 'Koleksi produk dari '.$toko->nama.'.' }}</x-slot>

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
        <nav class="mb-5 flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-400">
            <a href="{{ route('toko.index') }}" class="transition hover:text-brand-600">Toko</a>
            <span>/</span>
            <span class="text-slate-600">{{ $toko->nama }}</span>
        </nav>

        {{-- Kepala toko --}}
        <div class="overflow-hidden rounded-3xl bg-white shadow-elevate ring-1 ring-slate-200/70">
            <div class="relative h-36 bg-gradient-to-br from-ink-950 via-brand-800 to-accent-500 sm:h-48">
                @if ($toko->banner)
                    <img src="{{ asset($toko->banner) }}" alt="" class="h-full w-full object-cover">
                @else
                    <div class="pola-grid absolute inset-0 opacity-70"></div>
                @endif
                <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
            </div>

            <div class="px-5 pb-6 sm:px-8">
                {{-- "relative" wajib: sampul di atasnya berposisi, dan elemen
                     berposisi selalu dilukis di atas yang statis. --}}
                <div class="relative -mt-12 flex flex-col gap-5 sm:-mt-14 sm:flex-row sm:items-end sm:justify-between">
                    <div class="flex items-end gap-4">
                        <span class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-3xl bg-white shadow-lg ring-1 ring-slate-200 sm:h-28 sm:w-28">
                            @if ($toko->logo)
                                <img src="{{ asset($toko->logo) }}" alt="{{ $toko->nama }}" class="h-full w-full object-cover">
                            @else
                                <span class="flex h-full w-full items-center justify-center bg-gradient-to-br from-brand-600 to-accent-500 text-3xl font-extrabold text-white">
                                    {{ $toko->inisial }}
                                </span>
                            @endif
                        </span>

                        <div class="min-w-0 pb-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="truncate text-xl font-extrabold text-slate-900 sm:text-2xl">{{ $toko->nama }}</h1>
                                <span class="badge bg-emerald-50 text-emerald-700 ring-emerald-200">
                                    <x-ikon nama="perisai" kelas="h-3 w-3" />
                                    Terverifikasi
                                </span>
                            </div>
                            <p class="mt-1 flex items-center gap-1 text-sm text-slate-500">
                                <x-ikon nama="lokasi" kelas="h-4 w-4 shrink-0" />
                                {{ $toko->lokasi ?? 'Lokasi belum diisi' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2.5">
                        @foreach ([
                            ['kotak', number_format($toko->produks_count), 'Produk'],
                            ['jam', tanggalIndo($toko->created_at), 'Bergabung'],
                        ] as [$ikon, $nilai, $label])
                            <div class="rounded-2xl bg-slate-50 px-4 py-2.5 ring-1 ring-slate-200/80">
                                <p class="flex items-center gap-1.5 text-sm font-extrabold text-slate-800">
                                    <x-ikon :nama="$ikon" kelas="h-4 w-4 text-brand-600" />
                                    {{ $nilai }}
                                </p>
                                <p class="mt-0.5 text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ $label }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if ($toko->deskripsi)
                    <p class="mt-5 max-w-3xl text-sm leading-relaxed text-slate-600">{{ $toko->deskripsi }}</p>
                @endif
            </div>
        </div>

        {{-- Penyaring produk toko ini --}}
        <form method="GET" action="{{ route('toko.show', $toko->slug) }}"
              class="card mt-6 flex flex-wrap items-center gap-3 p-4">
            <div class="relative min-w-0 flex-1">
                <input type="search" name="q" value="{{ request('q') }}"
                       placeholder="Cari di toko ini…" class="input-field !py-2.5 pl-10 text-sm">
                <x-ikon nama="cari" kelas="pointer-events-none absolute inset-y-0 left-3 my-auto h-4 w-4 text-slate-400" />
            </div>

            <select name="kategori" class="input-field !w-auto !py-2.5 text-sm">
                <option value="">Semua kategori</option>
                @foreach ($kategoris as $kategori)
                    <option value="{{ $kategori->slug }}" @selected(request('kategori') === $kategori->slug)>
                        {{ $kategori->nama }}
                    </option>
                @endforeach
            </select>

            <select name="urutkan" class="input-field !w-auto !py-2.5 text-sm">
                @foreach ([
                    '' => 'Terbaru',
                    'termurah' => 'Harga termurah',
                    'termahal' => 'Harga termahal',
                ] as $nilai => $label)
                    <option value="{{ $nilai }}" @selected(request('urutkan') === $nilai)>{{ $label }}</option>
                @endforeach
            </select>

            <button class="btn-primary !py-2.5">Terapkan</button>

            @if (request()->hasAny(['q', 'kategori', 'urutkan']))
                <a href="{{ route('toko.show', $toko->slug) }}" class="btn-ghost !py-2.5">Reset</a>
            @endif
        </form>

        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
            @forelse ($produks as $produk)
                @include('partials.kartu-produk', ['produk' => $produk])
            @empty
                <div class="col-span-2 sm:col-span-3 md:col-span-4 lg:col-span-5 xl:col-span-6">
                    <div class="rounded-3xl border-2 border-dashed border-slate-300 bg-white/70 p-14 text-center">
                        <p class="text-sm font-semibold text-slate-600">
                            {{ request()->hasAny(['q', 'kategori']) ? 'Tidak ada produk yang cocok' : 'Toko ini belum memajang produk' }}
                        </p>
                        <a href="{{ route('produk.index') }}" class="btn-primary mt-6">Jelajahi Katalog</a>
                    </div>
                </div>
            @endforelse
        </div>

        @if ($produks->hasPages())
            <div class="mt-8">{{ $produks->links() }}</div>
        @endif
    </div>
</x-layouts.guest>
