<x-layouts.app>
    <x-slot name="title">Keranjang Belanja</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-extrabold text-slate-900">Keranjang Belanja</h2>
                <p class="mt-0.5 text-sm text-slate-500">Periksa kembali pesananmu sebelum checkout</p>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if ($items->isEmpty())
            <div class="card flex flex-col items-center py-24 text-center">
                <span class="text-7xl"><x-ikon nama="keranjang" kelas="h-5 w-5" /></span>
                <h3 class="mt-6 text-xl font-extrabold text-slate-900">Keranjangmu masih kosong</h3>
                <p class="mt-2 max-w-sm text-sm text-slate-500">Yuk mulai belanja! Temukan produk favoritmu dengan harga terbaik.</p>
                <a href="{{ route('toko.index') }}" class="btn-primary mt-8">Mulai Belanja →</a>
            </div>
        @else
            <div class="grid gap-6 lg:grid-cols-3">
                {{-- Daftar item --}}
                <div class="space-y-4 lg:col-span-2">
                    @foreach ($items as $item)
                        <div class="card flex flex-col gap-4 p-5 sm:flex-row sm:items-center">
                            <a href="{{ route('toko.show', $item->produk->slug) }}" class="shrink-0 overflow-hidden rounded-2xl bg-slate-100">
                                @if ($item->produk->gambar)
                                    <img src="{{ asset($item->produk->gambar) }}" alt="{{ $item->produk->nama }}" class="h-24 w-24 object-cover sm:h-28 sm:w-28">
                                @else
                                    <div class="flex h-24 w-24 items-center justify-center text-4xl sm:h-28 sm:w-28"><x-ikon nama="toko" kelas="h-9 w-9" /></div>
                                @endif
                            </a>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('toko.show', $item->produk->slug) }}" class="line-clamp-2 text-sm font-bold text-slate-800 hover:text-brand-700">{{ $item->produk->nama }}</a>
                                <p class="mt-1 text-xs font-medium text-slate-400">{{ $item->produk->kategori?->nama }}</p>
                                <p class="mt-2 text-sm font-extrabold text-brand-700">{{ rp($item->produk->harga) }}</p>
                            </div>
                            <div class="flex items-center justify-between gap-4 sm:flex-col sm:items-end">
                                <form action="{{ route('keranjang.updateQty', $item) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <div class="flex items-center rounded-xl ring-1 ring-slate-300">
                                        <button type="button" onclick="let i=this.parentElement.querySelector('input'); i.value=Math.max(1, +i.value-1); this.closest('form').submit();" class="px-3 py-2 text-sm font-bold text-slate-500 hover:text-brand-600">−</button>
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
                                        <button class="text-lg text-rose-400 transition hover:text-rose-600" title="Hapus"><x-ikon nama="sampah" kelas="h-5 w-5" /></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="flex justify-end">
                        <form action="{{ route('keranjang.kosongkan') }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button class="text-xs font-bold text-rose-500 transition hover:text-rose-700">Kosongkan Keranjang</button>
                        </form>
                    </div>
                </div>

                {{-- Ringkasan --}}
                <div class="lg:sticky lg:top-24 lg:self-start">
                    <div class="card p-6">
                        <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-500">Ringkasan Belanja</h3>
                        <dl class="mt-5 space-y-3 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-slate-500">Total Item</dt>
                                <dd class="font-bold text-slate-800">{{ $items->sum('qty') }} item</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-slate-500">Subtotal</dt>
                                <dd class="font-extrabold text-slate-900">{{ rp($items->sum(fn ($i) => $i->subtotal)) }}</dd>
                            </div>
                            <div class="flex justify-between text-xs">
                                <dt class="text-slate-400">Ongkos kirim</dt>
                                <dd class="font-semibold text-slate-500">Dihitung saat checkout</dd>
                            </div>
                        </dl>
                        <div class="mt-5 border-t border-dashed border-slate-200 pt-4">
                            <a href="{{ route('checkout.index') }}" class="btn-primary w-full py-3.5 text-base">Lanjut ke Checkout →</a>
                            <a href="{{ route('toko.index') }}" class="mt-3 block text-center text-sm font-bold text-brand-600 hover:text-brand-800">← Lanjut Belanja</a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

</x-layouts.app>