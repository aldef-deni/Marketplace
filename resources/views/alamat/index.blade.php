<x-layouts.app>
    <x-slot name="title">Buku Alamat</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-extrabold text-slate-900">Buku Alamat</h2>
                <p class="mt-0.5 text-sm text-slate-500">Kelola alamat pengirimanmu</p>
            </div>
            <a href="{{ route('alamat.create') }}" class="btn-primary">+ Tambah Alamat</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        {{-- Keranjang berisi berarti pengguna kemungkinan besar sedang di
             tengah checkout. Tanpa jalan kembali di sini, pesanan yang hampir
             jadi mudah terbengkalai. --}}
        @if (jmlKeranjang() > 0)
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-brand-50 px-5 py-4 ring-1 ring-brand-200">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700">
                        <x-ikon nama="keranjang" kelas="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-sm font-bold text-brand-900">Ada {{ jmlKeranjang() }} item menunggu di keranjang</p>
                        <p class="text-xs text-brand-700">Alamat sudah siap? Lanjutkan pesanan Anda.</p>
                    </div>
                </div>

                <a href="{{ route('checkout.index') }}" class="btn-primary">
                    Lanjutkan ke Checkout
                </a>
            </div>
        @endif

        @if ($alamats->isEmpty())
            <div class="card flex flex-col items-center py-24 text-center">
                <span class="text-7xl"><x-ikon nama="lokasi" kelas="h-5 w-5" /></span>
                <h3 class="mt-6 text-xl font-extrabold text-slate-900">Belum ada alamat</h3>
                <p class="mt-2 max-w-sm text-sm text-slate-500">Tambahkan alamat pengiriman agar bisa checkout pesananmu.</p>
                <a href="{{ route('alamat.create') }}" class="btn-primary mt-8">+ Tambah Alamat</a>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($alamats as $alamat)
                    <div class="card relative p-5 {{ $alamat->is_default ? 'ring-2 ring-brand-500' : '' }}">
                        @if ($alamat->is_default)
                            <span class="absolute right-4 top-4 badge bg-brand-100 text-brand-700 ring-brand-200">Utama</span>
                        @endif
                        <div class="flex items-center gap-2">
                            <span class="badge bg-slate-100 text-slate-700 ring-slate-200">{{ $alamat->label }}</span>
                            @if ($alamat->label === 'Rumah') <span class="text-lg"><x-ikon nama="rumah" kelas="h-5 w-5" /></span> @endif
                            @if ($alamat->label === 'Kantor') <span class="text-lg"><x-ikon nama="toko" kelas="h-5 w-5" /></span> @endif
                        </div>
                        <p class="mt-3 text-sm font-extrabold text-slate-800">{{ $alamat->nama_penerima }}</p>
                        <p class="text-xs font-medium text-slate-400">{{ $alamat->no_hp }}</p>
                        <p class="mt-3 text-xs leading-relaxed text-slate-500">{{ $alamat->alamat_lengkap_koma }}</p>

                        <div class="mt-4 flex items-center gap-2 border-t border-slate-100 pt-4">
                            @if (! $alamat->is_default)
                                <form action="{{ route('alamat.default', $alamat) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button class="rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100">Jadikan Utama</button>
                                </form>
                            @endif
                            <a href="{{ route('alamat.edit', $alamat) }}" class="rounded-lg bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-100">Edit</a>
                            <form action="{{ route('alamat.destroy', $alamat) }}" method="POST" onsubmit="return confirm('Hapus alamat ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-600 transition hover:bg-rose-100">Hapus</button>
                            </form>
                        </div>
                    </div>
                @endforeach

                <a href="{{ route('alamat.create') }}" class="flex min-h-40 flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 text-slate-400 transition hover:border-brand-400 hover:bg-brand-50/50 hover:text-brand-600">
                    <span class="text-3xl">＋</span>
                    <span class="mt-2 text-sm font-bold">Tambah Alamat</span>
                </a>
            </div>
        @endif
    </div>

</x-layouts.app>