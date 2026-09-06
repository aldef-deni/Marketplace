{{-- Pita flash sale di beranda. Hanya dirender bila ada kampanye berjalan
     yang benar-benar punya produk, sehingga tidak pernah tampil kosong. --}}
@php
    $barisFlash = $flashSale?->produks->filter(fn ($b) => $b->produk && ! $b->kuotaHabis()) ?? collect();
@endphp

@if ($flashSale && $barisFlash->isNotEmpty())
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-ink-950 via-brand-950 to-brand-900 p-6 shadow-elevate sm:p-8">
            <div class="pointer-events-none absolute inset-0 pola-grid opacity-60"></div>
            <div class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full bg-accent-500/25 blur-3xl"></div>
            <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-accent-500/70 to-transparent"></div>

            <div class="relative flex flex-wrap items-end justify-between gap-5">
                <div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-accent-500 px-3 py-1 text-[11px] font-extrabold uppercase tracking-wider text-ink-950">
                        <x-ikon nama="api" kelas="h-3.5 w-3.5" />
                        Flash Sale
                    </span>

                    <h2 class="mt-3 text-2xl font-extrabold text-white sm:text-3xl">{{ $flashSale->nama }}</h2>

                    @if ($flashSale->deskripsi)
                        <p class="mt-2 max-w-lg text-sm text-ink-300">{{ $flashSale->deskripsi }}</p>
                    @endif
                </div>

                {{-- Hitung mundur dimulai dari sisa detik yang dihitung server,
                     supaya jam perangkat pembeli yang meleset tidak mengubahnya. --}}
                <div x-data="{
                        sisa: {{ $flashSale->sisaDetik() }},
                        get jam() { return String(Math.floor(this.sisa / 3600)).padStart(2, '0'); },
                        get menit() { return String(Math.floor((this.sisa % 3600) / 60)).padStart(2, '0'); },
                        get detik() { return String(this.sisa % 60).padStart(2, '0'); },
                        mulai() { setInterval(() => { if (this.sisa > 0) this.sisa--; }, 1000); }
                     }"
                     x-init="mulai()"
                     class="flex items-center gap-2">
                    <p class="mr-1 text-xs font-semibold text-ink-400">Berakhir dalam</p>

                    <template x-for="bagian in [jam, menit, detik]">
                        <span class="min-w-[2.75rem] rounded-xl bg-white/10 px-2 py-2 text-center text-lg font-extrabold text-white ring-1 ring-white/15"
                              x-text="bagian"></span>
                    </template>
                </div>
            </div>

            <div class="relative mt-7 grid grid-cols-2 gap-4 sm:gap-5 md:grid-cols-4">
                @foreach ($barisFlash->take(4) as $baris)
                    @include('toko._kartu-produk', ['produk' => $baris->produk])
                @endforeach
            </div>

            <div class="relative mt-6 text-center">
                <a href="{{ route('toko.index') }}"
                   class="inline-flex items-center gap-2 rounded-2xl bg-accent-500 px-7 py-3 text-sm font-bold text-ink-950 shadow-accent transition hover:-translate-y-0.5 hover:bg-accent-400">
                    Lihat Semua Produk
                </a>
            </div>
        </div>
    </section>
@endif
