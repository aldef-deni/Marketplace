<x-layouts.admin>
    <x-slot name="title">{{ $produk->exists ? 'Edit Produk' : 'Tambah Produk' }}</x-slot>

    <div class="mx-auto max-w-4xl">
        <form action="{{ $produk->exists ? route('admin.produk.update', $produk) : route('admin.produk.store') }}" method="POST" enctype="multipart/form-data" class="card space-y-6 p-6 sm:p-8">
            @csrf
            @if ($produk->exists) @method('PATCH') @endif

            <div class="flex items-center gap-3 border-b border-slate-100 pb-5">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-100 to-accent-100 text-2xl"><x-ikon nama="label" kelas="h-6 w-6" /></span>
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900">{{ $produk->exists ? 'Edit Produk' : 'Tambah Produk Baru' }}</h2>
                    <p class="text-xs text-slate-400">Lengkapi informasi produk dengan benar</p>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="label-field">Nama Produk *</label>
                    <input type="text" name="nama" value="{{ old('nama', $produk->nama) }}" class="input-field" required placeholder="Contoh: Smart TV 43 Inch Ultra HD">
                    @error('nama') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>
                {{-- Pemilihan toko hanya ditawarkan kepada pengelola platform.
                     Penjual dikunci ke tokonya sendiri di sisi server, jadi
                     menampilkan pilihan di sini hanya akan menyesatkan. --}}
                @if (auth()->user()->isAdmin())
                    <div class="sm:col-span-2">
                        <label class="label-field">Toko Pemilik *</label>
                        <select name="toko_id" class="input-field" required>
                            <option value="">— Pilih Toko —</option>
                            @foreach ($tokos as $item)
                                <option value="{{ $item->id }}" @selected(old('toko_id', $produk->toko_id) == $item->id)>
                                    {{ $item->nama }}@if (! $item->aktif()) — {{ $item->status_label }} @endif
                                </option>
                            @endforeach
                        </select>
                        @error('toko_id') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @else
                    <input type="hidden" name="toko_id" value="{{ $tokos->first()?->id }}">
                @endif

                <div>
                    <label class="label-field">Kategori *</label>
                    <select name="kategori_id" class="input-field" required>
                        <option value="">— Pilih Kategori —</option>
                        @foreach ($kategoris as $kategori)
                            <option value="{{ $kategori->id }}" @selected(old('kategori_id', $produk->kategori_id) == $kategori->id)>{{ $kategori->nama }}</option>
                        @endforeach
                    </select>
                    @error('kategori_id') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Status *</label>
                    <select name="status" class="input-field">
                        <option value="aktif" @selected(old('status', $produk->status) === 'aktif')>Aktif (Tampil di Toko)</option>
                        <option value="nonaktif" @selected(old('status', $produk->status) === 'nonaktif')>Nonaktif (Sembunyikan)</option>
                    </select>
                </div>
                <div>
                    <label class="label-field">Harga Jual (Rp) *</label>
                    <input type="number" name="harga" value="{{ old('harga', $produk->harga) }}" class="input-field" required min="0">
                    @error('harga') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Harga Coret (Rp) <span class="font-normal text-slate-400">— opsional</span></label>
                    <input type="number" name="harga_coret" value="{{ old('harga_coret', $produk->harga_coret) }}" class="input-field" min="0">
                    @error('harga_coret') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Stok *</label>
                    <input type="number" name="stok" value="{{ old('stok', $produk->stok) }}" class="input-field" required min="0">
                    @error('stok') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Berat (gram) *</label>
                    <input type="number" name="berat" value="{{ old('berat', $produk->berat) }}" class="input-field" required min="1">
                    @error('berat') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label-field">Gambar Produk <span class="font-normal text-slate-400">— JPG/PNG/WebP maks 2MB</span></label>
                    <div class="flex items-center gap-4">
                        @if ($produk->gambar)
                            <img src="{{ asset($produk->gambar) }}" class="h-20 w-20 rounded-2xl object-cover ring-1 ring-slate-200" alt="">
                        @endif
                        <input type="file" name="gambar" accept="image/*" class="block w-full cursor-pointer rounded-xl border border-slate-300 bg-white p-2.5 text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-4 file:py-2 file:text-xs file:font-bold file:text-white hover:file:bg-brand-700">
                    </div>
                    @error('gambar') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label-field">Deskripsi Produk</label>
                    <textarea name="deskripsi" rows="5" class="input-field" placeholder="Jelaskan detail produk...">{{ old('deskripsi', $produk->deskripsi) }}</textarea>
                    @error('deskripsi') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center gap-3 border-t border-slate-100 pt-5">
                <button type="submit" class="btn-primary">{{ $produk->exists ? 'Simpan Perubahan' : 'Tambah Produk' }}</button>
                <a href="{{ route('admin.produk.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>

</x-layouts.admin>