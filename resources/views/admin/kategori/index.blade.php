<x-layouts.admin>
    <x-slot name="title">Manajemen Kategori</x-slot>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Daftar --}}
        <div class="card overflow-hidden lg:col-span-2">
            <div class="p-6 pb-4">
                <h3 class="text-base font-extrabold text-slate-900">🗂️ Daftar Kategori</h3>
                <p class="mt-0.5 text-xs text-slate-400">Total {{ $kategoris->count() }} kategori</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px]">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="table-head">Kategori</th>
                            <th class="table-head">Deskripsi</th>
                            <th class="table-head">Produk</th>
                            <th class="table-head">Status</th>
                            <th class="table-head text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($kategoris as $kategori)
                            <tr class="transition hover:bg-slate-50/60">
                                <td class="table-cell">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-xl">{{ $kategori->ikon }}</span>
                                        <p class="font-bold text-slate-800">{{ $kategori->nama }}</p>
                                    </div>
                                </td>
                                <td class="table-cell max-w-56 truncate text-xs text-slate-500">{{ $kategori->deskripsi }}</td>
                                <td class="table-cell font-extrabold">{{ $kategori->produks_count }}</td>
                                <td class="table-cell">
                                    <span class="badge {{ $kategori->aktif ? 'bg-emerald-100 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-slate-200' }}">
                                        {{ $kategori->aktif ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="table-cell">
                                    <div class="flex items-center justify-end gap-2">
                                        <form action="{{ route('admin.kategori.status', $kategori) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button class="rounded-lg bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-100">
                                                {{ $kategori->aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                        <button onclick="editKategori({{ $kategori->id }}, '{{ addslashes($kategori->nama) }}', '{{ addslashes($kategori->deskripsi ?? '') }}', '{{ $kategori->ikon }}')"
                                                class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100">✏️ Edit</button>
                                        <form action="{{ route('admin.kategori.destroy', $kategori) }}" method="POST" onsubmit="return confirm('Hapus kategori ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-600 transition hover:bg-rose-100">🗑️</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Form tambah --}}
        <div class="card h-fit p-6 lg:sticky lg:top-24">
            <h3 class="text-base font-extrabold text-slate-900">➕ Tambah Kategori</h3>
            <form action="{{ route('admin.kategori.store') }}" method="POST" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="label-field">Nama Kategori *</label>
                    <input type="text" name="nama" class="input-field" required placeholder="Contoh: Elektronik">
                </div>
                <div>
                    <label class="label-field">Ikon (Emoji)</label>
                    <input type="text" name="ikon" class="input-field" placeholder="Contoh: 📱">
                </div>
                <div>
                    <label class="label-field">Deskripsi</label>
                    <textarea name="deskripsi" rows="3" class="input-field" placeholder="Deskripsi singkat kategori"></textarea>
                </div>
                <button class="btn-primary w-full">Simpan Kategori</button>
            </form>
        </div>
    </div>

    {{-- Modal edit --}}
    <div id="modal-kategori" x-data="{ open: false, id: null, nama: '', deskripsi: '', ikon: '' }" x-cloak>
        <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="open = false"></div>
            <div class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-extrabold text-slate-900">✏️ Edit Kategori</h3>
                <form :action="`/admin/kategori/${id}`" method="POST" class="mt-5 space-y-4">
                    <input type="hidden" name="_method" value="PATCH">
                    @csrf
                    <div>
                        <label class="label-field">Nama Kategori *</label>
                        <input type="text" name="nama" x-model="nama" class="input-field" required>
                    </div>
                    <div>
                        <label class="label-field">Ikon (Emoji)</label>
                        <input type="text" name="ikon" x-model="ikon" class="input-field">
                    </div>
                    <div>
                        <label class="label-field">Deskripsi</label>
                        <textarea name="deskripsi" x-model="deskripsi" rows="3" class="input-field"></textarea>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button class="btn-primary flex-1">Simpan</button>
                        <button type="button" @click="open = false" class="btn-secondary">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editKategori(id, nama, deskripsi, ikon) {
            const data = Alpine.$data(document.getElementById('modal-kategori'));
            data.id = id;
            data.nama = nama;
            data.deskripsi = deskripsi;
            data.ikon = ikon;
            data.open = true;
        }
    </script>

</x-layouts.admin>