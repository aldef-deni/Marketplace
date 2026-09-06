<x-layouts.admin>
    <x-slot name="title">{{ $kampanye->exists ? 'Edit Kampanye' : 'Buat Kampanye' }}</x-slot>

    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <a href="{{ route('admin.flash-sale.kampanye.index') }}"
               class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 transition hover:text-brand-700">
                &larr; Kembali ke daftar kampanye
            </a>
            <h1 class="mt-2 text-xl font-extrabold tracking-tight text-slate-900">
                {{ $kampanye->exists ? 'Edit Kampanye Flash Sale' : 'Buat Kampanye Flash Sale' }}
            </h1>
            <p class="mt-0.5 text-sm text-slate-500">
                Kampanye baru tersimpan sebagai draf. Terbitkan agar pemilik toko diberi tahu
                dan dapat mengikutinya.
            </p>
        </div>

        <form method="POST"
              action="{{ $kampanye->exists ? route('admin.flash-sale.kampanye.update', $kampanye) : route('admin.flash-sale.kampanye.store') }}"
              class="card space-y-5 p-6 sm:p-8"
              x-data="{ tipe: '{{ old('tipe_diskon', $kampanye->tipe_diskon ?? 'persen') }}' }">
            @csrf
            @if ($kampanye->exists) @method('PATCH') @endif

            <div>
                <label class="label-field">Nama Kampanye *</label>
                <input type="text" name="nama" class="input-field" required maxlength="120"
                       value="{{ old('nama', $kampanye->nama) }}"
                       placeholder="Contoh: Flash Sale Akhir Pekan">
                <x-input-error :messages="$errors->get('nama')" class="mt-2" />
            </div>

            <div>
                <label class="label-field">Deskripsi <span class="font-normal text-slate-400">(opsional)</span></label>
                <textarea name="deskripsi" rows="3" class="input-field" maxlength="500"
                          placeholder="Keterangan singkat yang membantu admin memahami kampanye ini">{{ old('deskripsi', $kampanye->deskripsi) }}</textarea>
                <x-input-error :messages="$errors->get('deskripsi')" class="mt-2" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="label-field">Mulai *</label>
                    <input type="datetime-local" name="mulai_at" class="input-field" required
                           value="{{ old('mulai_at', $kampanye->mulai_at?->format('Y-m-d\TH:i') ?? now()->addDay()->format('Y-m-d\T09:00')) }}">
                    <x-input-error :messages="$errors->get('mulai_at')" class="mt-2" />
                </div>

                <div>
                    <label class="label-field">Selesai *</label>
                    <input type="datetime-local" name="selesai_at" class="input-field" required
                           value="{{ old('selesai_at', $kampanye->selesai_at?->format('Y-m-d\TH:i') ?? now()->addDay()->format('Y-m-d\T21:00')) }}">
                    <x-input-error :messages="$errors->get('selesai_at')" class="mt-2" />
                </div>
            </div>

            <div>
                <label class="label-field">Saran Potongan *</label>
                <div class="flex gap-2">
                    <select name="tipe_diskon" class="input-field !w-36"
                            x-model="tipe">
                        <option value="persen" @selected(old('tipe_diskon', $kampanye->tipe_diskon ?? 'persen') === 'persen')>Persentase</option>
                        <option value="nominal" @selected(old('tipe_diskon', $kampanye->tipe_diskon ?? 'persen') === 'nominal')>Nominal</option>
                    </select>
                    <div class="relative flex-1">
                        <input type="number" name="nilai_diskon" class="input-field pr-12" required min="1"
                               :max="tipe === 'persen' ? 90 : 1000000000"
                               value="{{ old('nilai_diskon', $kampanye->nilai_diskon ?: 20) }}">
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs font-bold text-slate-400"
                              x-text="tipe === 'persen' ? '%' : 'Rp'"></span>
                    </div>
                </div>
                <p class="mt-1.5 text-xs text-slate-400">
                    Dipakai untuk menghitung usulan harga saat admin memilih produk.
                    Harga akhir tetap ditentukan per produk agar marginnya bisa disesuaikan.
                </p>
                <x-input-error :messages="$errors->get('nilai_diskon')" class="mt-2" />
                <x-input-error :messages="$errors->get('tipe_diskon')" class="mt-2" />
            </div>

            <div class="flex items-center gap-3 border-t border-slate-100 pt-5">
                <button class="btn-primary">{{ $kampanye->exists ? 'Simpan Perubahan' : 'Simpan Kampanye' }}</button>
                <a href="{{ route('admin.flash-sale.kampanye.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
