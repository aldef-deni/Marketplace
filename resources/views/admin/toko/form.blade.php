<x-layouts.admin>
    <x-slot name="title">{{ $toko->exists ? 'Edit Toko' : 'Buat Toko' }}</x-slot>

    @php $superadmin = auth()->user()->isSuperadmin(); @endphp

    <div class="mx-auto max-w-4xl">
        <div class="mb-6">
            <a href="{{ route('admin.toko.index') }}"
               class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 transition hover:text-brand-700">
                &larr; Kembali ke daftar toko
            </a>
        </div>

        {{-- Kepala --}}
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-ink-950 via-brand-950 to-brand-900 p-6 shadow-elevate sm:p-8">
            <div class="pointer-events-none absolute inset-0 pola-grid opacity-60"></div>
            <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-accent-500/25 blur-3xl"></div>
            <div class="pointer-events-none absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-accent-500/60 to-transparent"></div>

            <div class="relative flex flex-wrap items-center justify-between gap-4">
                <div class="min-w-0">
                    <span class="badge bg-accent-500/15 text-accent-300 ring-accent-500/30">
                        {{ $toko->exists ? 'Edit Toko' : 'Toko Baru' }}
                    </span>
                    <h1 class="mt-3 truncate text-2xl font-extrabold text-white">
                        {{ $toko->exists ? $toko->nama : 'Buat Toko Baru' }}
                    </h1>
                    <p class="mt-1.5 max-w-lg text-sm text-ink-300">
                        Identitas ini yang dilihat pembeli di etalase.
                    </p>
                </div>

                @if ($toko->exists)
                    <span class="badge {{ $toko->status_warna }}">{{ $toko->status_label }}</span>
                @endif
            </div>
        </div>

        {{-- Pratinjau gambar diperbarui langsung dari berkas yang dipilih, jadi
             pemilik toko melihat hasilnya sebelum menyimpan. --}}
        <form method="POST" enctype="multipart/form-data" class="mt-6 space-y-6"
              action="{{ $toko->exists ? route('admin.toko.update', $toko) : route('admin.toko.store') }}"
              x-data="{
                  pratinjau(e, ref) {
                      const berkas = e.target.files[0]
                      if (! berkas) return
                      this.$refs[ref].src = URL.createObjectURL(berkas)
                      this.$refs[ref].classList.remove('hidden')
                      this.$refs[ref + 'Kosong']?.classList.add('hidden')
                  },
              }">
            @csrf
            @if ($toko->exists) @method('PATCH') @endif

            {{-- Tampilan toko --}}
            <div class="card p-6 sm:p-8">
                <h2 class="text-base font-extrabold text-slate-900">Tampilan Toko</h2>
                <p class="mt-0.5 text-xs text-slate-400">Logo dan sampul yang tampil di kartu dan halaman lapak Anda.</p>

                <div class="mt-6 grid gap-6 lg:grid-cols-[auto_1fr]">
                    {{-- Logo persegi: pratinjaunya berukuran tetap. --}}
                    <div>
                        <label class="label-field">Logo Toko</label>
                        <div class="flex flex-col items-start gap-3">
                            <span class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-2xl bg-slate-100 ring-1 ring-slate-200">
                                <img x-ref="logo" src="{{ $toko->logo ? asset($toko->logo) : '' }}" alt=""
                                     class="h-full w-full object-cover {{ $toko->logo ? '' : 'hidden' }}">
                                <span x-ref="logoKosong" class="{{ $toko->logo ? 'hidden' : '' }}">
                                    <x-ikon nama="gambar" kelas="h-7 w-7 text-slate-300" />
                                </span>
                            </span>

                            <input type="file" name="logo" accept="image/*" @change="pratinjau($event, 'logo')"
                                   class="block w-full max-w-[12rem] text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-xs file:font-bold file:text-brand-700 hover:file:bg-brand-100">
                            <p class="text-[11px] text-slate-400">Persegi, minimal 400×400 px</p>
                            @error('logo') <p class="text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Sampul melebar: pratinjaunya mengisi sisa kolom dengan
                         rasio mendekati hasil sebenarnya.

                         Sebelumnya pratinjau ini memakai w-full sekaligus
                         shrink-0 di dalam satu baris flex bersama isiannya —
                         akibatnya ia merebut seluruh lebar baris dan mendorong
                         kolom unggahan beserta keterangannya keluar kartu. --}}
                    <div class="min-w-0">
                        <label class="label-field">Sampul Toko</label>
                        <div class="flex flex-col gap-3">
                            <span class="flex aspect-[3/1] w-full items-center justify-center overflow-hidden rounded-2xl bg-slate-100 ring-1 ring-slate-200">
                                <img x-ref="banner" src="{{ $toko->banner ? asset($toko->banner) : '' }}" alt=""
                                     class="h-full w-full object-cover {{ $toko->banner ? '' : 'hidden' }}">
                                <span x-ref="bannerKosong" class="{{ $toko->banner ? 'hidden' : '' }}">
                                    <x-ikon nama="gambar" kelas="h-7 w-7 text-slate-300" />
                                </span>
                            </span>

                            <input type="file" name="banner" accept="image/*" @change="pratinjau($event, 'banner')"
                                   class="block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-xs file:font-bold file:text-brand-700 hover:file:bg-brand-100">
                            <p class="text-[11px] text-slate-400">Melebar, sekitar 1200×400 px</p>
                            @error('banner') <p class="text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <p class="mt-6 rounded-xl bg-slate-50 px-4 py-3 text-[11px] leading-relaxed text-slate-500 ring-1 ring-slate-200/70">
                    Kolom unggahan yang dibiarkan kosong tidak menghapus gambar yang sudah ada.
                </p>
            </div>

            {{-- Identitas --}}
            <div class="card p-6 sm:p-8">
                <h2 class="text-base font-extrabold text-slate-900">Identitas</h2>
                <p class="mt-0.5 text-xs text-slate-400">Nama dan keterangan yang dibaca pembeli.</p>

                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label-field">Nama Toko *</label>
                        <input type="text" name="nama" value="{{ old('nama', $toko->nama) }}" class="input-field" required
                               maxlength="120" placeholder="Contoh: Sentra Elektronik Bekasi">
                        @error('nama') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    @if ($superadmin)
                        <div class="sm:col-span-2">
                            <label class="label-field">Pemilik *</label>
                            <select name="user_id" class="input-field" required>
                                <option value="">— Pilih Pemilik —</option>
                                @foreach ($pemiliks as $pemilik)
                                    <option value="{{ $pemilik->id }}" @selected(old('user_id', $toko->user_id) == $pemilik->id)>
                                        {{ $pemilik->name }} — {{ $pemilik->email }} ({{ $pemilik->role_label }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-slate-400">
                                Pembeli biasa yang dipilih di sini otomatis dinaikkan menjadi pemilik toko.
                            </p>
                            @error('user_id') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <input type="hidden" name="user_id" value="{{ $toko->user_id ?? auth()->id() }}">
                    @endif

                    <div class="sm:col-span-2">
                        <label class="label-field">Deskripsi</label>
                        <textarea name="deskripsi" rows="3" class="input-field" maxlength="1000"
                                  placeholder="Ceritakan apa yang dijual toko ini">{{ old('deskripsi', $toko->deskripsi) }}</textarea>
                        @error('deskripsi') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label-field">Nomor WhatsApp</label>
                        <input type="text" name="no_hp" value="{{ old('no_hp', $toko->no_hp) }}" class="input-field"
                               maxlength="30" placeholder="08xxxxxxxxxx">
                        @error('no_hp') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label-field">Email Toko</label>
                        <input type="email" name="email" value="{{ old('email', $toko->email) }}" class="input-field" maxlength="150">
                        @error('email') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Alamat --}}
            <div class="card p-6 sm:p-8">
                <h2 class="text-base font-extrabold text-slate-900">Alamat Toko</h2>
                <p class="mt-0.5 text-xs text-slate-400">Kota dipakai sebagai penyaring di halaman daftar toko.</p>

                <div class="mt-6 grid gap-5 sm:grid-cols-3">
                    @foreach ([
                        ['provinsi', 'Provinsi', 'Jawa Barat'],
                        ['kota', 'Kota / Kabupaten', 'Bekasi'],
                        ['kecamatan', 'Kecamatan', 'Bekasi Selatan'],
                    ] as [$kolom, $label, $contoh])
                        <div>
                            <label class="label-field">{{ $label }}</label>
                            <input type="text" name="{{ $kolom }}" value="{{ old($kolom, $toko->$kolom) }}"
                                   class="input-field" maxlength="80" placeholder="{{ $contoh }}">
                            @error($kolom) <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    @endforeach

                    <div class="sm:col-span-3">
                        <label class="label-field">Alamat Lengkap</label>
                        <input type="text" name="alamat" value="{{ old('alamat', $toko->alamat) }}" class="input-field"
                               maxlength="255" placeholder="Nama jalan, nomor, patokan">
                        @error('alamat') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Aksi --}}
            <div class="card flex flex-wrap items-center gap-3 p-5 sm:p-6">
                <button class="btn-primary">{{ $toko->exists ? 'Simpan Perubahan' : 'Buat Toko' }}</button>
                <a href="{{ route('admin.toko.index') }}" class="btn-secondary">Batal</a>

                @if ($toko->exists && $superadmin && $toko->produks()->doesntExist())
                    <button type="submit" form="hapus-toko" class="btn-secondary ml-auto !text-rose-600">
                        Hapus Toko
                    </button>
                @endif
            </div>
        </form>

        {{-- Form hapus dipisah: <form> tidak sah bersarang di dalam <form>. --}}
        @if ($toko->exists && $superadmin && $toko->produks()->doesntExist())
            <form id="hapus-toko" method="POST" class="hidden"
                  action="{{ route('admin.toko.destroy', $toko) }}"
                  onsubmit="return confirm('Hapus toko {{ $toko->nama }}?')">
                @csrf
                @method('DELETE')
            </form>
        @endif
    </div>
</x-layouts.admin>
