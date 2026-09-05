<x-layouts.app>
    <x-slot name="title">{{ $alamat->exists ? 'Edit Alamat' : 'Tambah Alamat' }}</x-slot>

    <x-slot name="header">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900">{{ $alamat->exists ? 'Edit Alamat' : 'Tambah Alamat Baru' }}</h2>
            <p class="mt-0.5 text-sm text-slate-500">Pastikan alamat yang diisi benar agar paket sampai tepat</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        <form action="{{ $alamat->exists ? route('alamat.update', $alamat) : route('alamat.store') }}" method="POST" class="card space-y-5 p-6 sm:p-8">
            @csrf
            @if ($alamat->exists) @method('PATCH') @endif

            {{-- Penanda asal ikut terkirim agar setelah tersimpan pengguna
                 dikembalikan ke checkout, bukan ditinggalkan di buku alamat. --}}
            @if (($dari ?? null) === 'checkout')
                <input type="hidden" name="dari" value="checkout">
            @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="label-field">Label Alamat</label>
                    <select name="label" class="input-field">
                        @foreach (['Rumah' => 'Rumah', 'Kantor' => 'Kantor', 'Lainnya' => 'Lainnya'] as $nilai => $label)
                            <option value="{{ $nilai }}" @selected(old('label', $alamat->label) === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('label') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Nama Penerima</label>
                    <input type="text" name="nama_penerima" value="{{ old('nama_penerima', $alamat->nama_penerima) }}" class="input-field" placeholder="Nama lengkap penerima">
                    @error('nama_penerima') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="label-field">No. HP</label>
                    <input type="text" name="no_hp" value="{{ old('no_hp', $alamat->no_hp) }}" class="input-field" placeholder="08xxxxxxxxxx">
                    @error('no_hp') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Kode Pos</label>
                    <input type="text" name="kode_pos" value="{{ old('kode_pos', $alamat->kode_pos) }}" class="input-field" placeholder="12345">
                    @error('kode_pos') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-3">
                <div>
                    <label class="label-field">Provinsi</label>
                    <input type="text" name="provinsi" value="{{ old('provinsi', $alamat->provinsi) }}" class="input-field" placeholder="Provinsi">
                    @error('provinsi') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Kota/Kabupaten</label>
                    <input type="text" name="kota" value="{{ old('kota', $alamat->kota) }}" class="input-field" placeholder="Kota">
                    @error('kota') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label-field">Kecamatan</label>
                    <input type="text" name="kecamatan" value="{{ old('kecamatan', $alamat->kecamatan) }}" class="input-field" placeholder="Kecamatan">
                    @error('kecamatan') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="label-field">Alamat Lengkap</label>
                <textarea name="alamat_lengkap" rows="3" class="input-field" placeholder="Nama jalan, nomor rumah, RT/RW, patokan...">{{ old('alamat_lengkap', $alamat->alamat_lengkap) }}</textarea>
                @error('alamat_lengkap') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
            </div>

            <label class="flex cursor-pointer items-center gap-3 rounded-2xl bg-brand-50/60 p-4 ring-1 ring-brand-100">
                <input type="checkbox" name="is_default" value="1" class="h-5 w-5 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                    @checked(old('is_default', $alamat->is_default))>
                <span class="text-sm font-semibold text-slate-700">Jadikan alamat utama</span>
            </label>

            <div class="flex items-center gap-3 border-t border-slate-100 pt-5">
                <button type="submit" class="btn-primary">
                    {{ ($dari ?? null) === 'checkout'
                        ? 'Simpan & Lanjut Checkout'
                        : ($alamat->exists ? 'Simpan Perubahan' : 'Simpan Alamat') }}
                </button>

                <a href="{{ ($dari ?? null) === 'checkout' ? route('checkout.index') : route('alamat.index') }}"
                   class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>

</x-layouts.app>