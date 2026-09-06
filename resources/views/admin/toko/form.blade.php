<x-layouts.admin>
    <x-slot name="title">{{ $toko->exists ? 'Edit Toko' : 'Buat Toko' }}</x-slot>

    @php ($pengelola = auth()->user()->isSuperadmin())

    <div class="mx-auto max-w-4xl">
        <div class="mb-6">
            <a href="{{ route('admin.toko.index') }}"
               class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 transition hover:text-brand-700">
                &larr; Kembali ke daftar toko
            </a>
        </div>

        <form method="POST" enctype="multipart/form-data" class="card space-y-6 p-6 sm:p-8"
              action="{{ $toko->exists ? route('admin.toko.update', $toko) : route('admin.toko.store') }}">
            @csrf
            @if ($toko->exists) @method('PATCH') @endif

            <div class="flex items-center gap-3 border-b border-slate-100 pb-5">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-100 to-accent-100">
                    <x-ikon nama="toko" kelas="h-6 w-6 text-brand-700" />
                </span>
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900">{{ $toko->exists ? 'Edit Toko' : 'Buat Toko Baru' }}</h2>
                    <p class="text-xs text-slate-400">Identitas ini yang dilihat pembeli di etalase</p>
                </div>

                @if ($toko->exists)
                    <span class="badge {{ $toko->status_warna }} ml-auto">{{ $toko->status_label }}</span>
                @endif
            </div>

            {{-- Gambar --}}
            <div class="grid gap-5 sm:grid-cols-2">
                @foreach ([
                    ['logo', 'Logo Toko', 'Persegi, minimal 400×400 px', 'h-24 w-24 rounded-2xl'],
                    ['banner', 'Sampul Toko', 'Melebar, sekitar 1200×400 px', 'h-24 w-full rounded-2xl'],
                ] as [$nama, $label, $petunjuk, $bentuk])
                    <div>
                        <label class="label-field">{{ $label }}</label>
                        <div class="flex items-center gap-4">
                            <span class="flex shrink-0 items-center justify-center overflow-hidden bg-slate-100 ring-1 ring-slate-200 {{ $bentuk }}">
                                @if ($toko->$nama)
                                    <img src="{{ asset($toko->$nama) }}" alt="" class="h-full w-full object-cover">
                                @else
                                    <x-ikon nama="gambar" kelas="h-6 w-6 text-slate-300" />
                                @endif
                            </span>
                            <div class="min-w-0 flex-1">
                                <input type="file" name="{{ $nama }}" accept="image/*"
                                       class="block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-xs file:font-bold file:text-brand-700 hover:file:bg-brand-100">
                                <p class="mt-1 text-[11px] text-slate-400">{{ $petunjuk }}</p>
                                @error($nama) <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Identitas --}}
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="label-field">Nama Toko *</label>
                    <input type="text" name="nama" value="{{ old('nama', $toko->nama) }}" class="input-field" required
                           placeholder="Contoh: Sentra Elektronik Bekasi">
                    @error('nama') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>

                @if ($pengelola)
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
                    <textarea name="deskripsi" rows="3" class="input-field"
                              placeholder="Ceritakan apa yang dijual toko ini">{{ old('deskripsi', $toko->deskripsi) }}</textarea>
                    @error('deskripsi') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label-field">Nomor WhatsApp</label>
                    <input type="text" name="no_hp" value="{{ old('no_hp', $toko->no_hp) }}" class="input-field" placeholder="08xxxxxxxxxx">
                    @error('no_hp') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label-field">Email Toko</label>
                    <input type="email" name="email" value="{{ old('email', $toko->email) }}" class="input-field">
                    @error('email') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Alamat --}}
            <div class="border-t border-slate-100 pt-6">
                <h3 class="text-sm font-extrabold text-slate-800">Alamat Toko</h3>
                <p class="mt-0.5 text-xs text-slate-400">Kota dipakai sebagai penyaring di halaman daftar toko.</p>

                <div class="mt-4 grid gap-5 sm:grid-cols-3">
                    @foreach ([
                        ['provinsi', 'Provinsi'],
                        ['kota', 'Kota / Kabupaten'],
                        ['kecamatan', 'Kecamatan'],
                    ] as [$nama, $label])
                        <div>
                            <label class="label-field">{{ $label }}</label>
                            <input type="text" name="{{ $nama }}" value="{{ old($nama, $toko->$nama) }}" class="input-field">
                            @error($nama) <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    @endforeach

                    <div class="sm:col-span-3">
                        <label class="label-field">Alamat Lengkap</label>
                        <input type="text" name="alamat" value="{{ old('alamat', $toko->alamat) }}" class="input-field"
                               placeholder="Nama jalan, nomor, patokan">
                        @error('alamat') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-6">
                <button class="btn-primary">{{ $toko->exists ? 'Simpan Perubahan' : 'Buat Toko' }}</button>
                <a href="{{ route('admin.toko.index') }}" class="btn-secondary">Batal</a>

                @if ($toko->exists && $pengelola && $toko->produks()->doesntExist())
                    <form method="POST" action="{{ route('admin.toko.destroy', $toko) }}" class="ml-auto"
                          onsubmit="return confirm('Hapus toko {{ $toko->nama }}?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn-secondary !text-rose-600">Hapus Toko</button>
                    </form>
                @endif
            </div>
        </form>
    </div>
</x-layouts.admin>
