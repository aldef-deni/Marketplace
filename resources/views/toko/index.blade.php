<x-layouts.guest>
    <x-slot name="title">Daftar Toko</x-slot>
    <x-slot name="deskripsi">Jelajahi lapak-lapak terpercaya di {{ config('brand.nama') }}.</x-slot>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        {{-- Kepala --}}
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-ink-950 via-brand-900 to-brand-700 p-7 shadow-elevate sm:p-10">
            <div class="pointer-events-none absolute inset-0 pola-grid opacity-60"></div>
            <div class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 animate-geser-blob rounded-full bg-accent-500/30 blur-3xl"></div>
            <div class="pointer-events-none absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-accent-500/60 to-transparent"></div>

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0">
                    <span class="badge bg-white/10 text-accent-300 ring-white/15">Etalase Lapak</span>
                    <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                        Belanja Langsung dari <span class="text-accent-400">Tokonya</span>
                    </h1>
                    <p class="mt-3 max-w-lg text-sm leading-relaxed text-ink-300">
                        Tiap lapak dikelola penjualnya sendiri. Pilih toko, lihat koleksinya,
                        lalu belanja seperti biasa.
                    </p>
                </div>

                <div class="shrink-0 rounded-2xl bg-white/[0.07] p-4 ring-1 ring-white/10 backdrop-blur">
                    <p class="text-3xl font-extrabold text-white">{{ number_format($tokos->total()) }}</p>
                    <p class="text-xs font-bold uppercase tracking-wider text-ink-400">Toko aktif</p>
                </div>
            </div>
        </div>

        {{-- Penyaring --}}
        <form method="GET" action="{{ route('toko.index') }}"
              class="card mt-6 flex flex-wrap items-center gap-3 p-4">
            <div class="relative min-w-0 flex-1">
                <input type="search" name="q" value="{{ request('q') }}"
                       placeholder="Cari nama toko, deskripsi, atau kota…"
                       class="input-field !py-2.5 pl-10 text-sm">
                <x-ikon nama="cari" kelas="pointer-events-none absolute inset-y-0 left-3 my-auto h-4 w-4 text-slate-400" />
            </div>

            <select name="kota" class="input-field !w-auto !py-2.5 text-sm">
                <option value="">Semua kota</option>
                @foreach ($kotas as $kota)
                    <option value="{{ $kota }}" @selected(request('kota') === $kota)>{{ $kota }}</option>
                @endforeach
            </select>

            <select name="urutkan" class="input-field !w-auto !py-2.5 text-sm">
                @foreach ([
                    '' => 'Produk terbanyak',
                    'nama' => 'Nama A–Z',
                    'terbaru' => 'Toko terbaru',
                ] as $nilai => $label)
                    <option value="{{ $nilai }}" @selected(request('urutkan') === $nilai)>{{ $label }}</option>
                @endforeach
            </select>

            <button class="btn-primary !py-2.5">Terapkan</button>

            @if (request()->hasAny(['q', 'kota', 'urutkan']))
                <a href="{{ route('toko.index') }}" class="btn-ghost !py-2.5">Reset</a>
            @endif
        </form>

        {{-- Daftar toko --}}
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse ($tokos as $toko)
                @include('partials.kartu-toko', ['toko' => $toko])
            @empty
                <div class="sm:col-span-2 lg:col-span-3 xl:col-span-4">
                    <div class="rounded-3xl border-2 border-dashed border-slate-300 bg-white/70 p-14 text-center">
                        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                            <x-ikon nama="toko" kelas="h-6 w-6" />
                        </span>
                        <p class="mt-4 text-sm font-semibold text-slate-600">
                            {{ request()->hasAny(['q', 'kota']) ? 'Tidak ada toko yang cocok' : 'Belum ada toko aktif' }}
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            {{ request()->hasAny(['q', 'kota'])
                                ? 'Coba kata kunci lain atau hapus penyaringnya.'
                                : 'Etalase akan terisi begitu ada toko yang disetujui.' }}
                        </p>
                        <a href="{{ route('produk.index') }}" class="btn-primary mt-6">Lihat Katalog Produk</a>
                    </div>
                </div>
            @endforelse
        </div>

        @if ($tokos->hasPages())
            <div class="mt-8">{{ $tokos->links() }}</div>
        @endif
    </div>
</x-layouts.guest>
