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
                {{-- Pemilihan toko hanya ditawarkan kepada superadmin. Pemilik
                     toko dikunci ke lapaknya sendiri di sisi server, jadi
                     menampilkan pilihan di sini hanya akan menyesatkan. --}}
                @if (auth()->user()->isSuperadmin())
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

                {{-- Galeri gambar.

                     Unggahan baru, gambar yang dibuang, dan pilihan gambar
                     bawaan dikirim bersama produknya dalam satu formulir.

                     Gambar utama tidak ditandai dengan radio biasa: berkas yang
                     baru dipilih belum punya id, dan indeksnya bergeser setiap
                     satu berkas dibatalkan. Pilihannya disimpan sebagai kunci
                     di Alpine, lalu diterjemahkan ke kolom tersembunyi tepat
                     saat dikirim. --}}
                <div class="sm:col-span-2"
                     x-data="{
                         baru: [],
                         dibuang: [],
                         nomor: 0,
                         utama: @js($kunciUtama),

                         /* Berkas yang dipilih ditumpuk, bukan menimpa pilihan
                            sebelumnya, supaya gambar bisa diambil bertahap dari
                            beberapa folder berbeda. */
                         pilih(e) {
                             for (const berkas of e.target.files) {
                                 this.baru.push({ uid: 'b' + (++this.nomor), berkas, url: URL.createObjectURL(berkas) })
                             }
                             this.sinkron()
                             if (! this.utama) this.utama = this.baru[0]?.uid ?? ''
                         },

                         batalBaru(uid) {
                             const g = this.baru.find(x => x.uid === uid)
                             if (g) URL.revokeObjectURL(g.url)
                             this.baru = this.baru.filter(x => x.uid !== uid)
                             this.sinkron()
                             if (this.utama === uid) this.utama = this.utamaPengganti()
                         },

                         /* Isi input berkas hanya bisa diubah lewat DataTransfer;
                            menulis ke .files secara langsung ditolak browser. */
                         sinkron() {
                             const dt = new DataTransfer()
                             this.baru.forEach(g => dt.items.add(g.berkas))
                             this.$refs.berkas.files = dt.files
                         },

                         toggleBuang(id) {
                             this.dibuang = this.dibuang.includes(id)
                                 ? this.dibuang.filter(x => x !== id)
                                 : [...this.dibuang, id]

                             if (this.utama === 'lama:' + id && this.dibuang.includes(id)) {
                                 this.utama = this.utamaPengganti()
                             }
                         },

                         /* Gambar pertama yang masih tersisa, dibaca dari urutan
                            kartu di layar supaya sama dengan yang dilihat. */
                         utamaPengganti() {
                             const sisa = [...this.$el.querySelectorAll('[data-kunci]')]
                                 .map(n => n.dataset.kunci)
                                 .filter(k => k !== this.utama)
                                 .filter(k => ! (k.startsWith('lama:') && this.dibuang.includes(Number(k.slice(5)))))

                             return sisa[0] ?? ''
                         },

                         /* Bentuk yang dimengerti server: id untuk gambar yang
                            sudah tersimpan, indeks untuk berkas yang diunggah. */
                         nilaiUtama() {
                             if (this.utama.startsWith('lama:')) return this.utama
                             const i = this.baru.findIndex(g => g.uid === this.utama)
                             return i < 0 ? '' : 'baru:' + i
                         },

                         jumlah() {
                             return this.baru.length + {{ $produk->gambars->count() }} - this.dibuang.length
                         },
                     }">

                    <div class="flex flex-wrap items-end justify-between gap-2">
                        <div>
                            <label class="label-field !mb-0">Galeri Gambar Produk</label>
                            <p class="mt-0.5 text-[11px] text-slate-400">
                                JPG, PNG, atau WebP maks 2MB &middot; hingga 8 gambar &middot; tekan
                                <span class="font-bold text-slate-500">bintang</span> untuk menetapkan gambar utama
                            </p>
                        </div>
                        <span class="badge bg-slate-100 text-slate-500 ring-slate-200" x-text="jumlah() + ' / 8 gambar'"></span>
                    </div>

                    <input type="file" name="gambar[]" accept="image/jpeg,image/png,image/webp" multiple
                           x-ref="berkas" @change="pilih($event)" class="hidden">
                    <input type="hidden" name="gambar_utama" :value="nilaiUtama()">

                    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        {{-- Gambar yang sudah tersimpan --}}
                        @foreach ($produk->gambars as $gambar)
                            <div data-kunci="lama:{{ $gambar->id }}"
                                 class="group relative aspect-square overflow-hidden rounded-2xl bg-slate-100 ring-1 transition"
                                 :class="utama === 'lama:{{ $gambar->id }}' ? 'ring-2 ring-brand-500 shadow-brand' : 'ring-slate-200 hover:ring-brand-300'">
                                <img src="{{ asset($gambar->jalur) }}" alt=""
                                     class="h-full w-full object-cover transition duration-300"
                                     :class="dibuang.includes({{ $gambar->id }}) ? 'opacity-30 grayscale' : 'group-hover:scale-105'">

                                {{-- Kotak centang tersembunyi: penanda buang ikut
                                     terkirim tanpa menuntut baris kendali sendiri. --}}
                                <input type="checkbox" name="buang_gambar[]" value="{{ $gambar->id }}"
                                       class="hidden" :checked="dibuang.includes({{ $gambar->id }})">

                                {{-- Sudah ditandai buang: seluruh kartu menjadi
                                     tombol batal supaya mudah dikembalikan. --}}
                                <button type="button" x-show="dibuang.includes({{ $gambar->id }})" x-cloak
                                        @click="toggleBuang({{ $gambar->id }})"
                                        class="absolute inset-0 flex flex-col items-center justify-center gap-1 bg-white/70 text-[11px] font-extrabold text-rose-600 backdrop-blur-[2px]">
                                    <x-ikon nama="sampah" kelas="h-4 w-4" />
                                    Akan dihapus
                                    <span class="text-[10px] font-bold text-slate-500 underline">Batalkan</span>
                                </button>

                                <div x-show="! dibuang.includes({{ $gambar->id }})">
                                    <span x-show="utama === 'lama:{{ $gambar->id }}'" x-cloak
                                          class="absolute left-2 top-2 inline-flex items-center gap-1 rounded-lg bg-brand-600 px-2 py-1 text-[10px] font-extrabold text-white shadow-brand">
                                        <x-ikon nama="bintang" kelas="h-3 w-3" /> Utama
                                    </span>

                                    <div class="absolute inset-x-0 bottom-0 flex items-center justify-end gap-1.5 bg-gradient-to-t from-ink-950/80 to-transparent p-2 opacity-0 transition group-hover:opacity-100">
                                        <button type="button" x-show="utama !== 'lama:{{ $gambar->id }}'"
                                                @click="utama = 'lama:{{ $gambar->id }}'" title="Jadikan gambar utama"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/95 text-slate-600 transition hover:bg-accent-500 hover:text-white">
                                            <x-ikon nama="bintang" kelas="h-4 w-4" />
                                        </button>
                                        <button type="button" @click="toggleBuang({{ $gambar->id }})" title="Buang gambar ini"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/95 text-slate-600 transition hover:bg-rose-600 hover:text-white">
                                            <x-ikon nama="sampah" kelas="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        {{-- Berkas yang baru dipilih, dipratinjau sebelum dikirim --}}
                        <template x-for="g in baru" :key="g.uid">
                            <div :data-kunci="g.uid"
                                 class="group relative aspect-square overflow-hidden rounded-2xl bg-slate-100 ring-1 transition"
                                 :class="utama === g.uid ? 'ring-2 ring-brand-500 shadow-brand' : 'ring-slate-200 hover:ring-brand-300'">
                                <img :src="g.url" alt="" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">

                                <span class="absolute right-2 top-2 rounded-lg bg-emerald-500 px-2 py-0.5 text-[10px] font-extrabold text-white">Baru</span>

                                <span x-show="utama === g.uid" x-cloak
                                      class="absolute left-2 top-2 inline-flex items-center gap-1 rounded-lg bg-brand-600 px-2 py-1 text-[10px] font-extrabold text-white shadow-brand">
                                    <x-ikon nama="bintang" kelas="h-3 w-3" /> Utama
                                </span>

                                <div class="absolute inset-x-0 bottom-0 flex items-center justify-end gap-1.5 bg-gradient-to-t from-ink-950/80 to-transparent p-2 opacity-0 transition group-hover:opacity-100">
                                    <button type="button" x-show="utama !== g.uid" @click="utama = g.uid"
                                            title="Jadikan gambar utama"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/95 text-slate-600 transition hover:bg-accent-500 hover:text-white">
                                        <x-ikon nama="bintang" kelas="h-4 w-4" />
                                    </button>
                                    <button type="button" @click="batalBaru(g.uid)" title="Batalkan gambar ini"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/95 text-slate-600 transition hover:bg-rose-600 hover:text-white">
                                        <x-ikon nama="silang" kelas="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </template>

                        {{-- Petak unggah, selalu di ujung deretan --}}
                        <button type="button" x-show="jumlah() < 8" @click="$refs.berkas.click()"
                                class="flex aspect-square flex-col items-center justify-center gap-1.5 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50/60 text-slate-400 transition hover:border-brand-400 hover:bg-brand-50/60 hover:text-brand-600">
                            <x-ikon nama="unggah" kelas="h-6 w-6" />
                            <span class="text-[11px] font-extrabold">Tambah Gambar</span>
                            <span class="text-[10px] text-slate-400">Bisa pilih beberapa</span>
                        </button>
                    </div>

                    <p class="mt-3 rounded-xl bg-slate-50 px-4 py-3 text-[11px] leading-relaxed text-slate-500 ring-1 ring-slate-200/70">
                        Gambar utama dipakai pada kartu produk, keranjang, dan checkout.
                        Sisanya tampil sebagai galeri di halaman produk.
                    </p>

                    @error('gambar') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                    @foreach ($errors->get('gambar.*') as $pesan)
                        <p class="mt-1 text-xs font-semibold text-rose-600">{{ $pesan[0] }}</p>
                    @endforeach
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
