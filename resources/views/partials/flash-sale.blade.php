{{-- Pita flash sale di beranda. Hanya dirender bila ada kampanye berjalan
     yang benar-benar punya produk, sehingga tidak pernah tampil kosong. --}}
@php
    $barisFlash = $flashSale?->produks->filter(fn ($b) => $b->produk && ! $b->kuotaHabis()) ?? collect();
@endphp

@if ($flashSale && $barisFlash->isNotEmpty())
    <section class="relative overflow-hidden py-12 sm:py-16">
        {{-- Latar terang berlapis: gradien lembut, dua bola cahaya, dan kilat
             berulang. Semuanya pointer-events-none agar tidak menghalangi kartu. --}}
        <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-br from-accent-50 via-white to-brand-50"></div>
        <div class="pointer-events-none absolute -left-24 top-4 -z-10 h-72 w-72 rounded-full bg-accent-300/40 blur-3xl"></div>
        <div class="pointer-events-none absolute -right-24 bottom-0 -z-10 h-80 w-80 rounded-full bg-brand-300/35 blur-3xl"></div>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-white/75 via-white/45 to-brand-50/60 p-5 shadow-elevate ring-1 ring-white/70 backdrop-blur-xl sm:p-7 lg:p-8">
                {{-- Dua lapis gerak: kilat halilintar yang berkedip berkala, dan
                     sapuan cahaya yang melintas terus-menerus. --}}
                <div class="pointer-events-none absolute inset-0 animate-kilat bg-gradient-to-br from-white via-accent-100 to-transparent"></div>
                <div class="pointer-events-none absolute inset-0 overflow-hidden">
                    <div class="absolute inset-y-0 w-full animate-sheen-sweep bg-brand-sheen opacity-70"></div>
                </div>

                {{-- Kepala: identitas kampanye dan hitung mundur --}}
                <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-accent-500 to-accent-400 px-3 py-1.5 text-[11px] font-extrabold uppercase tracking-wider text-ink-950 shadow-accent">
                            <x-ikon nama="petir" kelas="h-4 w-4 animate-denyut-petir" />
                            Flash Sale
                        </span>

                        <h2 class="mt-3 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                            {{ $flashSale->nama }}
                        </h2>

                        @if ($flashSale->deskripsi)
                            <p class="mt-2 max-w-lg text-sm leading-relaxed text-slate-500">{{ $flashSale->deskripsi }}</p>
                        @endif
                    </div>

                    {{-- Hitung mundur dimulai dari sisa detik yang dihitung server,
                         supaya jam perangkat pembeli yang meleset tidak mengubahnya. --}}
                    <div x-data="{
                            sisa: {{ $flashSale->sisaDetik() }},
                            angka(n) { return String(Math.floor(n)).padStart(2, '0') },
                            get bagian() {
                                return [
                                    ['Hari', this.angka(this.sisa / 86400)],
                                    ['Jam', this.angka((this.sisa % 86400) / 3600)],
                                    ['Menit', this.angka((this.sisa % 3600) / 60)],
                                    ['Detik', this.angka(this.sisa % 60)],
                                ]
                            },
                            mulai() { setInterval(() => { if (this.sisa > 0) this.sisa-- }, 1000) },
                         }"
                         x-init="mulai()"
                         class="shrink-0">
                        <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400 lg:text-right">
                            Berakhir dalam
                        </p>

                        <div class="flex items-start gap-1.5 sm:gap-2">
                            <template x-for="(b, i) in bagian" :key="b[0]">
                                <div class="flex items-start gap-1.5 sm:gap-2">
                                    <div class="w-14 rounded-2xl bg-white/80 px-1 py-2 text-center shadow-sm ring-1 ring-slate-200/80 backdrop-blur sm:w-16">
                                        <span class="block text-xl font-extrabold tabular-nums text-slate-900 sm:text-2xl" x-text="b[1]"></span>
                                        <span class="mt-0.5 block text-[10px] font-bold uppercase tracking-wide text-slate-400" x-text="b[0]"></span>
                                    </div>
                                    <span x-show="i < 3" class="pt-2 text-lg font-extrabold text-accent-500 sm:text-xl">:</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Rel produk. Kartunya berukuran sama dengan kartu di seluruh
                     situs; yang berubah hanya jumlah yang muat per layar. --}}
                <div class="relative mt-6 sm:mt-7"
                     x-data="{
                        kiri: false,
                        kanan: false,
                        seret: false,
                        digeser: false,
                        awalX: 0,
                        awalGulir: 0,
                        perbarui() {
                            const r = this.$refs.rel
                            this.kiri = r.scrollLeft > 8
                            this.kanan = r.scrollLeft + r.clientWidth < r.scrollWidth - 8
                        },
                        geser(arah) {
                            const r = this.$refs.rel
                            r.scrollBy({ left: arah * r.clientWidth * 0.85, behavior: 'smooth' })
                        },
                        turun(e) {
                            if (e.pointerType === 'touch') return
                            this.seret = true
                            this.digeser = false
                            this.awalX = e.clientX
                            this.awalGulir = this.$refs.rel.scrollLeft
                        },
                        gerak(e) {
                            if (! this.seret) return
                            const jarak = e.clientX - this.awalX
                            if (Math.abs(jarak) > 6) this.digeser = true
                            this.$refs.rel.scrollLeft = this.awalGulir - jarak
                        },
                        lepas() { this.seret = false },
                        klik(e) {
                            if (! this.digeser) return
                            e.preventDefault()
                            e.stopPropagation()
                            this.digeser = false
                        },
                     }"
                     x-init="$nextTick(() => perbarui())"
                     @pointerup.window="lepas()"
                     @pointermove.window="gerak($event)"
                     @resize.window.debounce.150="perbarui()">

                    <button type="button" class="tombol-rel -left-3 lg:-left-5"
                            x-show="kiri" x-cloak x-transition
                            @click="geser(-1)" aria-label="Geser ke kiri">
                        <x-ikon nama="panah-kiri" kelas="h-5 w-5" />
                    </button>

                    <div x-ref="rel" class="rel-geser cursor-grab"
                         :class="seret && '!cursor-grabbing select-none'"
                         @scroll.passive="perbarui()"
                         @pointerdown="turun($event)"
                         @click.capture="klik($event)">
                        @foreach ($barisFlash as $baris)
                            @include('toko._kartu-produk', ['produk' => $baris->produk])
                        @endforeach
                    </div>

                    <button type="button" class="tombol-rel -right-3 lg:-right-5"
                            x-show="kanan" x-cloak x-transition
                            @click="geser(1)" aria-label="Geser ke kanan">
                        <x-ikon nama="panah-kanan" kelas="h-5 w-5" />
                    </button>
                </div>

                <div class="relative mt-6 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs font-semibold text-slate-400">
                        {{ $barisFlash->count() }} produk ikut kampanye ini
                    </p>

                    <a href="{{ route('toko.index') }}"
                       class="inline-flex items-center gap-2 rounded-2xl bg-accent-500 px-6 py-3 text-sm font-bold text-ink-950 shadow-accent transition hover:-translate-y-0.5 hover:bg-accent-400">
                        Lihat Semua Produk
                        <x-ikon nama="panah-kanan" kelas="h-4 w-4" />
                    </a>
                </div>
            </div>
        </div>
    </section>
@endif
