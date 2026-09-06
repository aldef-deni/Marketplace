<x-layouts.admin>
    <x-slot name="title">Promo</x-slot>

    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-ink-950 via-brand-950 to-brand-900 p-6 shadow-elevate sm:p-8">
        <div class="pointer-events-none absolute inset-0 pola-grid opacity-60"></div>
        <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-brand-500/30 blur-3xl"></div>
        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-brand-400/60 to-transparent"></div>

        <div class="relative flex flex-wrap items-center justify-between gap-5">
            <div class="min-w-0">
                <span class="badge bg-brand-500/15 text-brand-200 ring-brand-500/30">Program Potongan</span>
                <h1 class="mt-3 text-2xl font-extrabold text-white">Promo</h1>
                <p class="mt-1.5 max-w-xl text-sm text-ink-300">
                    Promo dari pengelola perlu Anda putuskan: ikut atau tidak. Promo yang Anda
                    buat sendiri untuk <span class="font-bold text-white">{{ $toko->nama }}</span>
                    berlaku langsung begitu diterbitkan.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @include('admin._pemilih-toko')

                <a href="{{ route('admin.promo.kampanye.create') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-accent-500 px-5 py-3 text-sm font-bold text-ink-950 shadow-accent transition hover:-translate-y-0.5 hover:bg-accent-400">
                    <x-ikon nama="tambah" kelas="h-4 w-4" />
                    Buat Promo Sendiri
                </a>
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        @foreach ([
            ['api', 'Sedang Berlangsung', $jumlah['berlangsung'], 'bg-emerald-50 ring-emerald-100'],
            ['jam', 'Menunggu Keputusan', $jumlah['perlu_keputusan'], 'bg-accent-50 ring-accent-100'],
            ['label', 'Promo Toko Ini', $jumlah['sendiri'], 'bg-brand-50 ring-brand-100'],
        ] as [$ikon, $label, $nilai, $warna])
            <div class="card p-5">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl ring-1 {{ $warna }}">
                    <x-ikon :nama="$ikon" kelas="h-5 w-5 text-slate-700" />
                </span>
                <p class="mt-4 text-2xl font-extrabold text-slate-900">{{ number_format($nilai) }}</p>
                <p class="text-xs font-bold text-slate-500">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    {{-- Promo dari pengelola --}}
    <div class="mt-8 flex items-end justify-between border-b border-slate-200 pb-3">
        <div>
            <h2 class="text-base font-extrabold text-slate-900">Promo dari Pengelola</h2>
            <p class="mt-0.5 text-xs text-slate-400">Ditawarkan ke semua toko. Anda yang memutuskan ikut atau tidak.</p>
        </div>
    </div>

    <div class="mt-5 grid gap-4 lg:grid-cols-2">
        @forelse ($platform as $promo)
            @php ($ikut = $promo->tokos->contains('id', $toko->id))

            <div class="card relative overflow-hidden p-6 {{ $promo->sudahBerakhir() ? 'opacity-70' : '' }}">
                @if ($promo->sedangBerlangsung() && $ikut)
                    <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-500 to-accent-500"></span>
                @endif

                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="truncate text-base font-extrabold text-slate-900">{{ $promo->nama }}</h3>
                            <span class="badge {{ $promo->status_warna }}">{{ $promo->status_label }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-400">{{ $promo->durasi_label }}</p>
                    </div>

                    <span class="shrink-0 rounded-xl bg-brand-50 px-3 py-1.5 text-sm font-extrabold text-brand-700 ring-1 ring-brand-200">
                        −{{ $promo->label_diskon }}
                    </span>
                </div>

                @if ($promo->deskripsi)
                    <p class="mt-3 line-clamp-2 text-sm leading-relaxed text-slate-500">{{ $promo->deskripsi }}</p>
                @endif

                <div class="mt-4 flex items-center gap-3 rounded-2xl p-4 ring-1
                            {{ $ikut ? 'bg-emerald-50 ring-emerald-200' : 'bg-amber-50 ring-amber-200' }}">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl
                                 {{ $ikut ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                        <x-ikon :nama="$ikut ? 'centang' : 'jam'" kelas="h-5 w-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold {{ $ikut ? 'text-emerald-800' : 'text-amber-800' }}">
                            {{ $ikut ? 'Toko mengikuti promo ini' : 'Toko belum mengikuti promo ini' }}
                        </p>
                        <p class="mt-0.5 text-xs {{ $ikut ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ $ikut
                                ? $promo->produks_count.' produk toko ini disertakan'
                                : 'Potongan belum berlaku sampai Anda ikut serta' }}
                        </p>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
                    @unless ($promo->sudahBerakhir())
                        <form method="POST" action="{{ route('admin.promo.ikut', $promo) }}">
                            @csrf
                            <button class="{{ $ikut ? 'btn-secondary' : 'btn-primary' }} btn-sm">
                                {{ $ikut ? 'Berhenti Ikut' : 'Ikuti Promo' }}
                            </button>
                        </form>
                    @endunless

                    <a href="{{ route('admin.promo.kelola', $promo) }}" class="btn-secondary btn-sm">
                        {{ $promo->sudahBerakhir() ? 'Lihat Produk' : 'Kelola Produk' }}
                    </a>
                </div>
            </div>
        @empty
            <div class="lg:col-span-2">
                <div class="rounded-3xl border-2 border-dashed border-slate-300 bg-white/60 p-12 text-center">
                    <p class="text-sm font-semibold text-slate-600">Belum ada promo dari pengelola</p>
                    <p class="mt-1 text-xs text-slate-400">
                        Halaman ini akan terisi begitu ada promo yang diterbitkan, dan Anda akan diberi tahu.
                    </p>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Promo milik toko ini --}}
    <div class="mt-10 flex items-end justify-between border-b border-slate-200 pb-3">
        <div>
            <h2 class="text-base font-extrabold text-slate-900">Promo Toko Ini</h2>
            <p class="mt-0.5 text-xs text-slate-400">Anda yang membuatnya, jadi tidak perlu diikuti siapa pun.</p>
        </div>
        <a href="{{ route('admin.promo.kampanye.index') }}" class="text-sm font-bold text-brand-600 hover:text-brand-800">
            Kelola &rarr;
        </a>
    </div>

    <div class="mt-5 grid gap-4 lg:grid-cols-2">
        @forelse ($sendiri as $promo)
            <div class="card relative overflow-hidden p-6">
                @if ($promo->sedangBerlangsung())
                    <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-500 to-accent-500"></span>
                @endif

                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="truncate text-base font-extrabold text-slate-900">{{ $promo->nama }}</h3>
                            <span class="badge {{ $promo->status_warna }}">{{ $promo->status_label }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-400">{{ $promo->durasi_label }}</p>
                    </div>

                    <span class="shrink-0 rounded-xl bg-accent-50 px-3 py-1.5 text-sm font-extrabold text-accent-700 ring-1 ring-accent-200">
                        −{{ $promo->label_diskon }}
                    </span>
                </div>

                <p class="mt-4 text-xs font-semibold text-slate-500">
                    {{ $promo->produks_count }} produk disertakan
                </p>

                <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
                    <a href="{{ route('admin.promo.kelola', $promo) }}" class="btn-primary btn-sm">Kelola Produk</a>
                    <a href="{{ route('admin.promo.kampanye.edit', $promo) }}" class="btn-secondary btn-sm">Edit</a>
                </div>
            </div>
        @empty
            <div class="lg:col-span-2">
                <div class="rounded-3xl border-2 border-dashed border-slate-300 bg-white/60 p-12 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-100 text-brand-700">
                        <x-ikon nama="label" kelas="h-6 w-6" />
                    </span>
                    <p class="mt-4 text-sm font-semibold text-slate-600">Toko ini belum punya promo sendiri</p>
                    <p class="mt-1 text-xs text-slate-400">Buat promo untuk produk Anda tanpa menunggu pengelola.</p>
                    <a href="{{ route('admin.promo.kampanye.create') }}" class="btn-primary mt-6">Buat Promo</a>
                </div>
            </div>
        @endforelse
    </div>
</x-layouts.admin>
