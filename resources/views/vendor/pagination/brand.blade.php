{{-- Paginasi bergaya merek.

     Dipakai eksplisit lewat $paginator->links('vendor.pagination.brand'), bukan
     dipasang sebagai bawaan global, supaya tabel di panel admin tidak ikut
     berubah tanpa diminta. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-col items-center gap-4">
        {{-- Angka halaman disembunyikan di layar sempit; deret angka yang
             membungkus jadi dua baris lebih sulit dibaca daripada satu
             keterangan singkat. --}}
        <div class="flex items-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-300">
                    <x-ikon nama="panah-kiri" kelas="h-4 w-4" />
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya"
                   class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-slate-600 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:text-brand-700 hover:ring-brand-300">
                    <x-ikon nama="panah-kiri" kelas="h-4 w-4" />
                </a>
            @endif

            <div class="hidden items-center gap-1.5 sm:flex">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="px-1.5 text-sm font-bold text-slate-300">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page"
                                      class="flex h-10 min-w-[2.5rem] items-center justify-center rounded-xl bg-brand-600 px-3 text-sm font-extrabold text-white shadow-brand">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}"
                                   class="flex h-10 min-w-[2.5rem] items-center justify-center rounded-xl bg-white px-3 text-sm font-bold text-slate-600 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:text-brand-700 hover:ring-brand-300">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            <span class="rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-slate-600 shadow-sm ring-1 ring-slate-200 sm:hidden">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya"
                   class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-slate-600 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:text-brand-700 hover:ring-brand-300">
                    <x-ikon nama="panah-kanan" kelas="h-4 w-4" />
                </a>
            @else
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-300">
                    <x-ikon nama="panah-kanan" kelas="h-4 w-4" />
                </span>
            @endif
        </div>

        <p class="text-xs font-semibold text-slate-400">
            Menampilkan {{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }}
            dari {{ number_format($paginator->total()) }} produk
        </p>
    </nav>
@endif
