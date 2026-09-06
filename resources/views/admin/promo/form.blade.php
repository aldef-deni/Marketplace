<x-layouts.admin>
    <x-slot name="title">{{ $promo->exists ? 'Edit Promo' : 'Buat Promo' }}</x-slot>

    @php ($superadmin = auth()->user()->isSuperadmin())

    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <a href="{{ route('admin.promo.kampanye.index') }}"
               class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 transition hover:text-brand-700">
                &larr; Kembali ke daftar promo
            </a>
            <h1 class="mt-2 text-xl font-extrabold tracking-tight text-slate-900">
                {{ $promo->exists ? 'Edit Promo' : 'Buat Promo' }}
            </h1>
            <p class="mt-0.5 text-sm text-slate-500">
                {{ $superadmin
                    ? 'Promo baru tersimpan sebagai draf. Terbitkan agar pemilik toko diberi tahu dan dapat mengikutinya.'
                    : 'Promo baru tersimpan sebagai draf. Terbitkan lalu pilih produk yang disertakan.' }}
            </p>
        </div>

        <form method="POST"
              action="{{ $promo->exists ? route('admin.promo.kampanye.update', $promo) : route('admin.promo.kampanye.store') }}"
              class="card space-y-5 p-6 sm:p-8"
              x-data="{ tipe: '{{ old('tipe_diskon', $promo->tipe_diskon ?? 'persen') }}' }">
            @csrf
            @if ($promo->exists) @method('PATCH') @endif

            <div>
                <label class="label-field">Nama Promo *</label>
                <input type="text" name="nama" class="input-field" required maxlength="120"
                       value="{{ old('nama', $promo->nama) }}"
                       placeholder="Contoh: Promo Gajian September">
                <x-input-error :messages="$errors->get('nama')" class="mt-2" />
            </div>

            <div>
                <label class="label-field">Deskripsi</label>
                <textarea name="deskripsi" rows="3" class="input-field"
                          placeholder="Keterangan singkat yang dibaca pemilik toko">{{ old('deskripsi', $promo->deskripsi) }}</textarea>
                <x-input-error :messages="$errors->get('deskripsi')" class="mt-2" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="label-field">Mulai *</label>
                    <input type="datetime-local" name="mulai_at" class="input-field" required
                           value="{{ old('mulai_at', $promo->mulai_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}">
                    <x-input-error :messages="$errors->get('mulai_at')" class="mt-2" />
                </div>
                <div>
                    <label class="label-field">Selesai *</label>
                    <input type="datetime-local" name="selesai_at" class="input-field" required
                           value="{{ old('selesai_at', $promo->selesai_at?->format('Y-m-d\TH:i') ?? now()->addWeek()->format('Y-m-d\T21:00')) }}">
                    <x-input-error :messages="$errors->get('selesai_at')" class="mt-2" />
                </div>
            </div>

            {{-- Potongan bisa persentase atau rupiah. Satuan di ujung kolom ikut
                 berganti supaya angka yang diketik tidak salah arti. --}}
            <div>
                <label class="label-field">Potongan *</label>
                <div class="flex gap-2">
                    <select name="tipe_diskon" class="input-field !w-36" x-model="tipe">
                        <option value="persen" @selected(old('tipe_diskon', $promo->tipe_diskon ?? 'persen') === 'persen')>Persentase</option>
                        <option value="nominal" @selected(old('tipe_diskon', $promo->tipe_diskon ?? 'persen') === 'nominal')>Nominal</option>
                    </select>
                    <div class="relative flex-1">
                        <input type="number" name="nilai_diskon" class="input-field pr-12" required min="1"
                               :max="tipe === 'persen' ? 90 : 1000000000"
                               value="{{ old('nilai_diskon', $promo->nilai_diskon ?: 10) }}">
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs font-bold text-slate-400"
                              x-text="tipe === 'persen' ? '%' : 'Rp'"></span>
                    </div>
                </div>
                <p class="mt-1.5 text-xs text-slate-400"
                   x-text="tipe === 'persen'
                        ? 'Potongan dihitung dari harga tiap produk. Maksimal 90%.'
                        : 'Potongan rupiah yang sama untuk tiap produk, dan tidak pernah melebihi harganya.'"></p>
                <x-input-error :messages="$errors->get('nilai_diskon')" class="mt-2" />
                <x-input-error :messages="$errors->get('tipe_diskon')" class="mt-2" />
            </div>

            <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
                <button class="btn-primary">{{ $promo->exists ? 'Simpan Perubahan' : 'Buat Promo' }}</button>
                <a href="{{ route('admin.promo.kampanye.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
