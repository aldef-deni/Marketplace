<x-layouts.app>
    <x-slot name="title">Checkout</x-slot>

    <x-slot name="header">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900">Checkout</h2>
            <p class="mt-0.5 text-sm text-slate-500">Lengkapi alamat, pilih pengiriman dan metode pembayaran</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <form action="{{ route('checkout.store') }}" method="POST"
              x-data="{
                  kurir: 'JNE',
                  tarif: {
                      @foreach (\App\Http\Controllers\CheckoutController::KURIR as $namaKurir => $konfigKurir)
                          '{{ $namaKurir }}': {{ \App\Http\Controllers\CheckoutController::hitungOngkir($namaKurir, $beratGram) }},
                      @endforeach
                  },
                  beratGram: {{ $beratGram }},
                  alamat: '{{ old('alamat_id', $alamats->firstWhere('is_default', true)?->id ?? $alamats->first()?->id) }}',
                  metode: '{{ old('metode_pembayaran_id') }}',
                  mengirim: false,
                  get ongkir() { return this.tarif[this.kurir] ?? 0; },
                  get total() { return {{ $subtotal }} + this.ongkir; },
                  get siap() { return this.alamat !== '' && this.metode !== ''; }
              }"
              @submit="mengirim = true">
            @csrf

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">

                    {{-- 1. Alamat --}}
                    <section class="card p-6">
                        <div class="flex items-center justify-between">
                            <h3 class="flex items-center gap-2 text-base font-extrabold text-slate-900">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-600 text-xs font-extrabold text-white">1</span>
                                Alamat Pengiriman
                            </h3>
                            <a href="{{ route('alamat.create') }}" class="text-sm font-bold text-brand-600 hover:text-brand-800">+ Tambah Alamat</a>
                        </div>

                        @if ($alamats->isEmpty())
                            <div class="mt-5 rounded-2xl border-2 border-dashed border-slate-300 p-8 text-center">
                                <p class="text-sm font-semibold text-slate-500">Belum ada alamat tersimpan.</p>
                                <a href="{{ route('alamat.create') }}" class="btn-primary mt-4">Tambah Alamat Baru</a>
                            </div>
                        @else
                            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                @foreach ($alamats as $alamat)
                                    <label class="relative block cursor-pointer rounded-2xl border-2 p-4 transition"
                                           x-bind:class="alamat === '{{ $alamat->id }}' ? 'border-brand-500 bg-brand-50/50' : 'border-slate-200 bg-white hover:border-brand-200'">
                                        <input type="radio" name="alamat_id" value="{{ $alamat->id }}" x-model="alamat" class="sr-only">
                                        <span class="pointer-events-none absolute right-3 top-3 h-5 w-5 rounded-full bg-white transition"
                                              x-bind:class="alamat === '{{ $alamat->id }}' ? 'border-[6px] border-brand-600' : 'border-2 border-slate-300'"></span>
                                        <div class="pr-6">
                                            <div class="flex items-center gap-2">
                                                <span class="badge bg-brand-50 text-brand-700 ring-brand-200">{{ $alamat->label }}</span>
                                                @if ($alamat->is_default)
                                                    <span class="badge bg-emerald-50 text-emerald-700 ring-emerald-200">Utama</span>
                                                @endif
                                            </div>
                                            <p class="mt-2 text-sm font-bold text-slate-800">{{ $alamat->nama_penerima }} <span class="font-medium text-slate-400">• {{ $alamat->no_hp }}</span></p>
                                            <p class="mt-1 text-xs leading-relaxed text-slate-500">{{ $alamat->alamat_lengkap_koma }}</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            @error('alamat_id')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </section>

                    {{-- 2. Pengiriman --}}
                    <section class="card p-6">
                        <h3 class="flex items-center gap-2 text-base font-extrabold text-slate-900">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-600 text-xs font-extrabold text-white">2</span>
                            Metode Pengiriman
                            <span class="ml-auto rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-500">{{ number_format($beratGram) }} gram</span>
                        </h3>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            @foreach (\App\Http\Controllers\CheckoutController::KURIR as $nama => $konfig)
                                <label class="relative block cursor-pointer rounded-2xl border-2 p-4 transition" x-bind:class="kurir === '{{ $nama }}' ? 'border-brand-500 bg-brand-50/50' : 'border-slate-200 bg-white hover:border-brand-200'">
                                    <input type="radio" name="kurir" value="{{ $nama }}" x-model="kurir" class="sr-only">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-xl"><x-ikon nama="truk" kelas="h-5 w-5" /></span>
                                            <div>
                                                <p class="text-sm font-bold text-slate-800">{{ $nama }}</p>
                                                <p class="text-xs text-slate-400">{{ $konfig['layanan'] }} • {{ $konfig['estimasi'] }}</p>
                                            </div>
                                        </div>
                                        <p class="text-sm font-extrabold text-brand-700">{{ rp(\App\Http\Controllers\CheckoutController::hitungOngkir($nama, $beratGram)) }}</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('kurir')
                            <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </section>

                    {{-- 3. Pembayaran --}}
                    <section class="card p-6">
                        <h3 class="flex items-center gap-2 text-base font-extrabold text-slate-900">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-600 text-xs font-extrabold text-white">3</span>
                            Metode Pembayaran
                        </h3>

                        @if ($metodes->isEmpty())
                            <div class="mt-5 rounded-2xl border-2 border-dashed border-amber-300 bg-amber-50/60 p-6 text-center">
                                <p class="text-sm font-semibold text-amber-800">Belum ada metode pembayaran yang aktif.</p>
                                <p class="mt-1 text-xs text-amber-700">Hubungi pengelola toko untuk mengaktifkannya.</p>
                            </div>
                        @endif

                        <div class="mt-5 space-y-3">
                            @foreach ($metodes->groupBy('tipe') as $tipe => $kelompok)
                                <div class="mb-2 flex items-center gap-2">
                                    <span class="badge {{ $kelompok->first()->warna }}">{{ $kelompok->first()->label_tipe }}</span>
                                    <span class="h-px flex-1 bg-slate-200"></span>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    @foreach ($kelompok as $metode)
                                        <label class="relative block cursor-pointer rounded-2xl border-2 p-4 transition"
                                               x-bind:class="metode === '{{ $metode->id }}' ? 'border-brand-500 bg-brand-50/50' : 'border-slate-200 bg-white hover:border-brand-200'">
                                            <input type="radio" name="metode_pembayaran_id" value="{{ $metode->id }}" x-model="metode" class="sr-only">
                                            <span class="pointer-events-none absolute right-3 top-3 h-5 w-5 rounded-full bg-white transition"
                                                  x-bind:class="metode === '{{ $metode->id }}' ? 'border-[6px] border-brand-600' : 'border-2 border-slate-300'"></span>
                                            <div class="flex items-center gap-3 pr-6">
                                                <span class="flex h-11 w-11 items-center justify-center rounded-xl text-xl {{ match ($metode->tipe) { 'transfer' => 'bg-blue-50', 'ewallet' => 'bg-emerald-50', 'cod' => 'bg-amber-50', default => 'bg-slate-50' } }}">
                                                    <x-ikon :nama="match ($metode->tipe) { 'transfer' => 'bank', 'ewallet' => 'ponsel', 'cod' => 'uang', default => 'kartu' }" kelas="h-5 w-5 text-slate-700" />
                                                </span>
                                                <div class="min-w-0">
                                                    <p class="text-sm font-bold text-slate-800">{{ $metode->nama }}</p>
                                                    @if ($metode->tipe === 'transfer' || $metode->tipe === 'ewallet')
                                                        <p class="truncate text-xs text-slate-400">a.n. {{ $metode->atas_nama }}</p>
                                                    @else
                                                        <p class="text-xs text-slate-400">Bayar tunai saat pesanan tiba</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                        @error('metode_pembayaran_id')
                            <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </section>

                    {{-- 4. Catatan --}}
                    <section class="card p-6">
                        <h3 class="flex items-center gap-2 text-base font-extrabold text-slate-900">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-600 text-xs font-extrabold text-white">4</span>
                            Catatan (Opsional)
                        </h3>
                        <textarea name="catatan" rows="3" placeholder="Contoh: tolong kemas dengan aman, tambahkan bubble wrap..."
                                  class="input-field mt-4">{{ old('catatan') }}</textarea>
                    </section>
                </div>

                {{-- Ringkasan --}}
                <div class="lg:sticky lg:top-24 lg:self-start">
                    <div class="card p-6">
                        <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-500">Ringkasan Pesanan</h3>
                        <div class="mt-4 max-h-64 space-y-3 overflow-y-auto pr-1">
                            @foreach ($items as $item)
                                <div class="flex items-center gap-3">
                                    <div class="relative shrink-0">
                                        @if ($item->produk->gambar)
                                            <img src="{{ asset($item->produk->gambar) }}" class="h-14 w-14 rounded-xl object-cover" alt="">
                                        @else
                                            <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-slate-100 text-2xl"><x-ikon nama="toko" kelas="h-6 w-6" /></div>
                                        @endif
                                        <span class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-slate-800 text-[10px] font-bold text-white">{{ $item->qty }}</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-bold text-slate-700">{{ $item->produk->nama }}</p>
                                        <p class="text-xs text-slate-400">{{ rp($item->produk->harga) }} × {{ $item->qty }}</p>
                                    </div>
                                    <p class="text-xs font-extrabold text-slate-800">{{ rp($item->produk->harga * $item->qty) }}</p>
                                </div>
                            @endforeach
                        </div>

                        <dl class="mt-5 space-y-3 border-t border-dashed border-slate-200 pt-5 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-slate-500">Subtotal</dt>
                                <dd class="font-bold text-slate-800">{{ rp($subtotal) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-slate-500">Ongkos Kirim</dt>
                                <dd class="font-bold text-slate-800" x-text="'Rp ' + Number(ongkir).toLocaleString('id-ID')">{{ rp(\App\Http\Controllers\CheckoutController::hitungOngkir('JNE', $beratGram)) }}</dd>
                            </div>
                            <div class="flex justify-between border-t border-slate-100 pt-3 text-base">
                                <dt class="font-extrabold text-slate-900">Total Bayar</dt>
                                <dd class="font-extrabold text-brand-700" x-text="'Rp ' + Number(total).toLocaleString('id-ID')">{{ rp($subtotal + \App\Http\Controllers\CheckoutController::hitungOngkir('JNE', $beratGram)) }}</dd>
                            </div>
                        </dl>

                        <div class="mt-5 rounded-xl bg-slate-50 p-3 text-[11px] leading-relaxed text-slate-500 ring-1 ring-slate-200/70">
                            Dengan melanjutkan, Anda menyetujui bahwa pesanan akan diproses sesuai kebijakan toko.
                        </div>

                        {{-- Dimatikan sampai alamat dan metode pembayaran terpilih, supaya
                             pengguna tidak menekan tombol lalu dilempar balik oleh validasi. --}}
                        <button type="submit" id="btn-bayar" class="btn-primary mt-5 w-full py-3.5 text-base"
                                x-bind:disabled="mengirim || ! siap"
                                x-text="mengirim ? 'Memproses…' : (siap ? 'Buat Pesanan' : 'Pilih alamat & metode pembayaran')"
                                {{ $alamats->isEmpty() || $metodes->isEmpty() ? 'disabled' : '' }}>
                            Buat Pesanan
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

</x-layouts.app>