<x-layouts.app>
    <x-slot name="title">Keranjang Belanja</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-extrabold text-slate-900">Keranjang Belanja</h2>
                <p class="mt-0.5 text-sm text-slate-500">Centang produk yang ingin dibeli sekarang</p>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if ($items->isEmpty())
            <div class="card flex flex-col items-center py-24 text-center">
                <span class="flex h-20 w-20 items-center justify-center rounded-3xl bg-brand-50 text-brand-500">
                    <x-ikon nama="keranjang" kelas="h-9 w-9" />
                </span>
                <h3 class="mt-6 text-xl font-extrabold text-slate-900">Keranjangmu masih kosong</h3>
                <p class="mt-2 max-w-sm text-sm text-slate-500">Yuk mulai belanja! Temukan produk favoritmu dengan harga terbaik.</p>
                <a href="{{ route('produk.index') }}" class="btn-primary mt-8">Mulai Belanja &rarr;</a>
            </div>
        @else
            {{-- Pilihan disimpan ke server tiap kali centangnya berubah, supaya
                 bertahan saat pembeli berpindah halaman. Totalnya dihitung di
                 layar agar angkanya berubah seketika tanpa memuat ulang.

                 Produk yang sudah tidak tersedia tidak pernah bisa dicentang:
                 membiarkannya hanya akan menggagalkan checkout di detik terakhir. --}}
            @php
                $tersedia = $items->filter->tersedia();
                $awal = $tersedia->where('dipilih', true)->pluck('id')->values();
            @endphp

            <div x-data="{
                    pilihan: @js($awal),
                    semuaId: @js($tersedia->pluck('id')->values()),
                    harga: @js($items->mapWithKeys(fn ($i) => [$i->id => (float) $i->subtotal])),
                    jumlah: @js($items->mapWithKeys(fn ($i) => [$i->id => $i->qty])),
                    menyimpan: false,

                    ada(id) { return this.pilihan.includes(id) },

                    alih(id) {
                        this.pilihan = this.ada(id)
                            ? this.pilihan.filter(x => x !== id)
                            : [...this.pilihan, id]
                        this.simpan()
                    },

                    get semua() { return this.semuaId.length > 0 && this.pilihan.length === this.semuaId.length },

                    alihSemua() {
                        this.pilihan = this.semua ? [] : [...this.semuaId]
                        this.simpan()
                    },

                    get totalItem() { return this.pilihan.reduce((n, id) => n + (this.jumlah[id] ?? 0), 0) },
                    get subtotal() { return this.pilihan.reduce((n, id) => n + (this.harga[id] ?? 0), 0) },

                    rupiah(n) {
                        return 'Rp ' + Math.round(n).toLocaleString('id-ID')
                    },

                    /* Disimpan diam-diam. Kegagalan jaringan sengaja tidak
                       memunculkan galat: pilihan tetap terlihat benar di layar,
                       dan checkout menyaring ulang di sisi server. */
                    simpan() {
                        this.menyimpan = true
                        fetch('{{ route('keranjang.pilih') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ id: this.pilihan }),
                        }).finally(() => { this.menyimpan = false })
                    },
                }"
                 class="grid gap-6 lg:grid-cols-3">

                {{-- Daftar item --}}
                <div class="space-y-4 lg:col-span-2">

                    {{-- Aksi massal --}}
                    <div class="card flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                        <label class="flex cursor-pointer select-none items-center gap-3">
                            <input type="checkbox" @change="alihSemua()" :checked="semua"
                                   class="h-5 w-5 rounded-md border-slate-300 text-brand-600 focus:ring-brand-500">
                            <span class="text-sm font-extrabold text-slate-700">Pilih Semua</span>
                            <span class="badge bg-slate-100 text-slate-500 ring-slate-200"
                                  x-text="pilihan.length + ' / ' + semuaId.length"></span>
                        </label>

                        <form action="{{ route('keranjang.kosongkan') }}" method="POST"
                              onsubmit="return confirm('Kosongkan seluruh keranjang?')">
                            @csrf
                            @method('DELETE')
                            <button class="text-xs font-bold text-rose-500 transition hover:text-rose-700">Kosongkan Keranjang</button>
                        </form>
                    </div>

                    @foreach ($items as $item)
                        @php $bisa = $item->tersedia(); @endphp

                        <div class="card flex flex-col gap-4 p-5 transition sm:flex-row sm:items-center"
                             @if ($bisa) :class="ada({{ $item->id }}) ? 'ring-2 ring-brand-500/60' : ''" @endif>

                            {{-- Centang --}}
                            <div class="flex items-center gap-4 sm:gap-3">
                                @if ($bisa)
                                    <input type="checkbox" @change="alih({{ $item->id }})" :checked="ada({{ $item->id }})"
                                           aria-label="Pilih {{ $item->produk->nama }}"
                                           class="h-5 w-5 shrink-0 cursor-pointer rounded-md border-slate-300 text-brand-600 focus:ring-brand-500">
                                @else
                                    <input type="checkbox" disabled title="Produk tidak tersedia"
                                           class="h-5 w-5 shrink-0 cursor-not-allowed rounded-md border-slate-200 bg-slate-100">
                                @endif

                                <a href="{{ route('produk.show', $item->produk->slug) }}"
                                   class="shrink-0 overflow-hidden rounded-2xl bg-slate-100 {{ $bisa ? '' : 'opacity-50 grayscale' }}">
                                    @if ($item->produk->gambar)
                                        <img src="{{ asset($item->produk->gambar) }}" alt="{{ $item->produk->nama }}" class="h-24 w-24 object-cover sm:h-28 sm:w-28">
                                    @else
                                        <div class="flex h-24 w-24 items-center justify-center sm:h-28 sm:w-28"><x-ikon nama="toko" kelas="h-9 w-9 text-slate-300" /></div>
                                    @endif
                                </a>
                            </div>

                            <div class="min-w-0 flex-1">
                                <a href="{{ route('produk.show', $item->produk->slug) }}" class="line-clamp-2 text-sm font-bold text-slate-800 hover:text-brand-700">{{ $item->produk->nama }}</a>
                                <p class="mt-1 text-xs font-medium text-slate-400">{{ $item->produk->kategori?->nama }}</p>
                                <p class="mt-2 text-sm font-extrabold text-brand-700">{{ rp($item->produk->hargaEfektif()) }}</p>

                                @unless ($bisa)
                                    <p class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-rose-50 px-2.5 py-1 text-[11px] font-bold text-rose-600 ring-1 ring-rose-100">
                                        <x-ikon nama="peringatan" kelas="h-3.5 w-3.5" />
                                        {{ $item->produk?->stok < 1 ? 'Stok habis' : 'Tidak tersedia' }}
                                    </p>
                                @endunless
                            </div>

                            <div class="flex items-center justify-between gap-4 sm:flex-col sm:items-end">
                                <form action="{{ route('keranjang.updateQty', $item) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <div class="flex items-center rounded-xl ring-1 ring-slate-300">
                                        <button type="button" onclick="let i=this.parentElement.querySelector('input'); i.value=Math.max(1, +i.value-1); this.closest('form').submit();" class="px-3 py-2 text-sm font-bold text-slate-500 hover:text-brand-600">&minus;</button>
                                        <input type="number" name="qty" value="{{ $item->qty }}" min="1" max="{{ $item->produk->stok }}"
                                               class="w-12 border-0 bg-transparent text-center text-sm font-bold focus:ring-0">
                                        <button type="button" onclick="let i=this.parentElement.querySelector('input'); i.value=Math.min(+i.max||999, +i.value+1); this.closest('form').submit();" class="px-3 py-2 text-sm font-bold text-slate-500 hover:text-brand-600">+</button>
                                    </div>
                                </form>
                                <div class="flex items-center gap-3">
                                    <p class="text-sm font-extrabold text-slate-800">{{ rp($item->subtotal) }}</p>
                                    <form action="{{ route('keranjang.hapus', $item) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-rose-400 transition hover:text-rose-600" title="Hapus"><x-ikon nama="sampah" kelas="h-5 w-5" /></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Ringkasan --}}
                <div class="lg:sticky lg:top-24 lg:self-start">
                    <div class="card p-6">
                        <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-500">Ringkasan Belanja</h3>
                        <dl class="mt-5 space-y-3 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-slate-500">Item Terpilih</dt>
                                <dd class="font-bold text-slate-800"><span x-text="totalItem"></span> item</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-slate-500">Subtotal</dt>
                                <dd class="font-extrabold text-slate-900" x-text="rupiah(subtotal)"></dd>
                            </div>
                            <div class="flex justify-between text-xs">
                                <dt class="text-slate-400">Ongkos kirim</dt>
                                <dd class="font-semibold text-slate-500">Dihitung saat checkout</dd>
                            </div>
                        </dl>

                        <div class="mt-5 border-t border-dashed border-slate-200 pt-4">
                            {{-- Dimatikan selama belum ada yang dicentang: mengantar
                                 pembeli ke checkout kosong hanya untuk dipantulkan
                                 balik bukan jawaban. --}}
                            <a href="{{ route('checkout.index') }}"
                               x-show="pilihan.length > 0"
                               class="btn-primary block w-full py-3.5 text-center text-base">
                                Checkout (<span x-text="totalItem"></span>) &rarr;
                            </a>
                            <button type="button" disabled x-show="pilihan.length === 0" x-cloak
                                    class="w-full cursor-not-allowed rounded-xl bg-slate-100 py-3.5 text-center text-base font-bold text-slate-400">
                                Pilih produk dulu
                            </button>

                            <a href="{{ route('produk.index') }}" class="mt-3 block text-center text-sm font-bold text-brand-600 hover:text-brand-800">&larr; Lanjut Belanja</a>
                        </div>
                    </div>

                    {{-- Bilah tetap di ponsel: ringkasan di atas terlalu jauh untuk
                         dijangkau setelah menggulir daftar yang panjang. --}}
                    <div class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 p-4 shadow-elevate backdrop-blur lg:hidden">
                        <div class="flex items-center gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] font-semibold text-slate-400"><span x-text="totalItem"></span> item terpilih</p>
                                <p class="truncate text-base font-extrabold text-slate-900" x-text="rupiah(subtotal)"></p>
                            </div>
                            <a href="{{ route('checkout.index') }}" x-show="pilihan.length > 0"
                               class="btn-primary shrink-0 px-6 py-3">Checkout</a>
                            <button type="button" disabled x-show="pilihan.length === 0" x-cloak
                                    class="shrink-0 cursor-not-allowed rounded-xl bg-slate-100 px-6 py-3 text-sm font-bold text-slate-400">Checkout</button>
                        </div>
                    </div>
                    {{-- Ruang agar bilah tetap tidak menutupi isi terakhir. --}}
                    <div class="h-24 lg:hidden"></div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
