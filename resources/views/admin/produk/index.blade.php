<x-layouts.admin>
    <x-slot name="title">Manajemen Produk</x-slot>

    {{-- Checklist hapus massal (Alpine). "Pilih semua" = produk di HALAMAN INI
         saja, mengikuti filter & pencarian yang sedang aktif. --}}
    <div class="card overflow-hidden"
         x-data="{
            pilih: [],
            semua: @js($produks->map(fn ($p) => (string) $p->id)->values()),
            nama: @js((object) $produks->mapWithKeys(fn ($p) => [(string) $p->id => $p->nama])->all()),
            get semuaTerpilih() { return this.semua.length > 0 && this.pilih.length === this.semua.length },
            get sebagian() { return this.pilih.length > 0 && ! this.semuaTerpilih },
            aturSemua(centang) { this.pilih = centang ? [...this.semua] : [] },
            konfirmasi() {
                const daftar = this.pilih.slice(0, 5).map(id => '• ' + this.nama[id]).join('\n');
                const lagi = this.pilih.length > 5 ? '\n…dan ' + (this.pilih.length - 5) + ' produk lainnya' : '';
                return confirm('Hapus ' + this.pilih.length + ' produk terpilih?\n\n' + daftar + lagi + '\n\nTindakan ini tidak bisa dibatalkan.');
            }
         }">
        <div class="flex flex-wrap items-center justify-between gap-4 p-6 pb-4">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">Daftar Produk</h3>
                <p class="mt-0.5 text-xs text-slate-400">
                    Total {{ $produks->total() }} produk
                    <span x-cloak x-show="pilih.length > 0" class="font-bold text-brand-700">· <span x-text="pilih.length"></span> dipilih</span>
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <button type="button" x-cloak x-show="pilih.length > 0" @click="pilih = []" class="btn-secondary">Batal pilih</button>
                <form x-cloak x-show="pilih.length > 0" action="{{ route('admin.produk.destroy-massal') }}" method="POST"
                      @submit="if (! konfirmasi()) $event.preventDefault()">
                    @csrf
                    @method('DELETE')
                    <template x-for="id in pilih" :key="id">
                        <input type="hidden" name="ids[]" :value="id">
                    </template>
                    <button type="submit" class="btn-danger">
                        <x-ikon nama="sampah" kelas="h-4 w-4" /> Hapus (<span x-text="pilih.length"></span>)
                    </button>
                </form>
                <a href="{{ route('admin.produk.create') }}" class="btn-primary">+ Tambah Produk</a>
            </div>
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
                <button class="btn-secondary">Filter</button>
                @if (request()->hasAny(['q', 'kategori', 'status']))
                    <a href="{{ route('admin.produk.index') }}" class="btn-secondary">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px]">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="table-head w-10">
                            <input type="checkbox" title="Pilih semua produk di halaman ini"
                                   class="h-4 w-4 cursor-pointer rounded border-slate-300 text-brand-600"
                                   :checked="semuaTerpilih"
                                   :disabled="semua.length === 0"
                                   x-effect="$el.indeterminate = sebagian"
                                   @change="aturSemua($event.target.checked)">
                        </th>
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
                        <tr class="transition hover:bg-slate-50/60" :class="pilih.includes('{{ $produk->id }}') ? 'bg-brand-50' : ''">
                            <td class="table-cell w-10">
                                <input type="checkbox" value="{{ $produk->id }}" x-model="pilih"
                                       class="h-4 w-4 cursor-pointer rounded border-slate-300 text-brand-600"
                                       aria-label="Pilih {{ $produk->nama }}">
                            </td>
                            <td class="table-cell">
                                <div class="flex items-center gap-3">
                                    @if ($produk->gambar)
                                        <img src="{{ asset($produk->gambar) }}" class="h-12 w-12 rounded-xl object-cover" alt="">
                                    @else
                                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-2xl"><x-ikon nama="toko" kelas="h-6 w-6" /></div>
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
                                @if ($produk->stok <= 5)<span class="ml-1 text-xs"><x-ikon nama="peringatan" kelas="h-3.5 w-3.5" /></span>@endif
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
                                    <a href="{{ route('admin.produk.edit', $produk) }}" class="rounded-lg bg-brand-50 px-3 py-1.5 text-xs font-bold text-brand-700 transition hover:bg-brand-100">Edit</a>
                                    <form action="{{ route('admin.produk.destroy', $produk) }}" method="POST" onsubmit="return confirm('Hapus produk ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-600 transition hover:bg-rose-100"><x-ikon nama="sampah" kelas="h-3.5 w-3.5" /></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-16 text-center">
                                <p class="text-4xl"><x-ikon nama="cari" kelas="h-9 w-9" /></p>
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