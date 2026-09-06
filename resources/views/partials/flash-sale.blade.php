{{-- Pita flash sale di beranda. Hanya dirender bila ada kampanye berjalan
     yang benar-benar punya produk, sehingga tidak pernah tampil kosong. --}}
@php
    $barisFlash = $flashSale?->produks->filter(fn ($b) => $b->produk && ! $b->kuotaHabis()) ?? collect();
@endphp

@if ($flashSale && $barisFlash->isNotEmpty())
    <section class="relative py-12 sm:py-16">
        {{-- Latar dibiarkan tembus ke warna halaman; yang ada hanya bola cahaya
             yang bergerak pelan. Inilah yang dibiaskan panel kaca di atasnya —
             tanpa sesuatu di belakangnya, kaca hanya akan terlihat putih. --}}
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
            <div class="absolute -left-20 top-0 h-72 w-72 animate-geser-blob rounded-full bg-accent-400/45 blur-3xl"></div>
            <div class="absolute right-0 top-16 h-80 w-80 animate-geser-blob-2 rounded-full bg-brand-400/45 blur-3xl"></div>
            <div class="absolute bottom-0 left-1/3 h-64 w-64 animate-geser-blob-lambat rounded-full bg-rose-400/35 blur-3xl"></div>
        </div>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="kaca p-5 sm:p-7 lg:p-8">
                {{-- Dua lapis gerak di atas kaca: kilatan halilintar berkala dan
                     sapuan cahaya yang melintas terus-menerus. --}}
                <div class="pointer-events-none absolute inset-0 animate-kilat bg-gradient-to-br from-white via-white to-accent-200/60"></div>
                <div class="pointer-events-none absolute inset-0 overflow-hidden">
                    <div class="absolute inset-y-0 w-full animate-sheen-sweep bg-kilau-kaca"></div>
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
                            <p class="mt-2 max-w-lg text-sm leading-relaxed text-slate-600">{{ $flashSale->deskripsi }}</p>
                        @endif
                    </div>

                    @include('partials.flash-hitung-mundur', ['flashSale' => $flashSale])
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
                         @dragstart.prevent
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
                    <p class="text-xs font-semibold text-slate-500">
                        {{ $barisFlash->count() }} produk ikut kampanye ini
                    </p>

                    <a href="{{ route('flash-sale.index') }}"
                       class="btn-kilat inline-flex items-center gap-2 rounded-2xl bg-accent-500 px-6 py-3 text-sm font-bold text-ink-950 shadow-accent transition hover:-translate-y-0.5 hover:bg-accent-400">
                        <x-ikon nama="petir" kelas="h-4 w-4" />
                        Lihat Semua Flash Sale
                    </a>
                </div>
            </div>
        </div>
    </section>
@endif
