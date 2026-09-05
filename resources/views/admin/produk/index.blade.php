<x-layouts.admin>
    <x-slot name="title">Manajemen Produk</x-slot>

    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-4 p-6 pb-4">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">🏷️ Daftar Produk</h3>
                <p class="mt-0.5 text-xs text-slate-400">Total {{ $produks->total() }} produk</p>
            </div>
            <a href="{{ route('admin.produk.create') }}" class="btn-primary">+ Tambah Produk</a>
        </div>

        {{-- Filter --}}
        <div class="px-6 pb-4">
            <form method="GET" class="flex flex-wrap gap-3">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari produk..." class="input-field w-56">
                <select name="kategori" class="input-field w-48">
                    <option value="">Semua Kategori</option>
                    @foreach ($kategoris as $kategori)
                        <option value="{{ $kategori->id }}" @selected(request('kategori') == $kategori->id)>{{ $kategori->nama }}</option>
                    @endforeach
                </select>
                <select name="status" class="input-field w-44">
                    <option value="">Semua Status</option>
                    <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
                    <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
                </select>
                <button class="btn-secondary">🔍 Filter</button>
                @if (request()->hasAny(['q', 'kategori', 'status']))
                    <a href="{{ route('admin.produk.index') }}" class="btn-secondary">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px]">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="table-head">Produk</th>
                        <th class="table-head">Kategori</th>
                        <th class="table-head">Harga</th>
                        <th class="table-head">Stok</th>
                        <th class="table-head">Berat</th>
                        <th class="table-head">Status</th>
                        <th class="table-head text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($produks as $produk)
                        <tr class="transition hover:bg-slate-50/60">
                            <td class="table-cell">
                                <div class="flex items-center gap-3">
                                    @if ($produk->gambar)
                                        <img src="{{ asset($produk->gambar) }}" class="h-12 w-12 rounded-xl object-cover" alt="">
                                    @else
                                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-2xl">🛍️</div>
                                    @endif
                                    <div>
                                        <p class="max-w-56 truncate text-sm font-bold text-slate-800">{{ $produk->nama }}</p>
                                        <p class="text-xs text-slate-400">{{ $produk->slug }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="table-cell"><span class="text-xs font-semibold text-slate-500">{{ $produk->kategori?->nama }}</span></td>
                            <td class="table-cell">
                                <p class="font-extrabold text-slate-800">{{ rp($produk->harga) }}</p>
                                @if ($produk->harga_coret)
                                    <p class="text-xs text-slate-400 line-through">{{ rp($produk->harga_coret) }}</p>
                                @endif
                            </td>
                            <td class="table-cell">
                                <span class="font-extrabold {{ $produk->stok <= 5 ? 'text-rose-600' : ($produk->stok <= 0 ? 'text-rose-500' : 'text-slate-800') }}">{{ $produk->stok }}</span>
                                @if ($produk->stok <= 5)<span class="ml-1 text-xs">⚠️</span>@endif
                            </td>
                            <td class="table-cell text-xs text-slate-500">{{ number_format($produk->berat) }} gr</td>
                            <td class="table-cell">
                                <span class="badge {{ $produk->status === 'aktif' ? 'bg-emerald-100 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-slate-200' }}">
                                    {{ $produk->status === 'aktif' ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="table-cell">
                                <div class="flex items-center justify-end gap-2">
                                    <form action="{{ route('admin.produk.status', $produk) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button class="rounded-lg bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-100" title="Ubah status">
                                            {{ $produk->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                    <a href="{{ route('admin.produk.edit', $produk) }}" class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100">✏️ Edit</a>
                                    <form action="{{ route('admin.produk.destroy', $produk) }}" method="POST" onsubmit="return confirm('Hapus produk ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-600 transition hover:bg-rose-100">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center">
                                <p class="text-4xl">🔍</p>
                                <p class="mt-3 text-sm font-semibold text-slate-500">Tidak ada produk ditemukan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-6">{{ $produks->links() }}</div>
    </div>

</x-layouts.admin>