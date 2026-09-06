<x-layouts.admin>
    <x-slot name="title">{{ $flashSale->nama }}</x-slot>

    @php
        $terkunci = $flashSale->sudahBerakhir();

        // Superadmin tidak punya menu keikutsertaan; ia tiba di sini dari kartu
        // kampanye, jadi tautan kembalinya pun mengarah ke sana.
        $rutaKembali = auth()->user()->isSuperadmin()
            ? route('admin.flash-sale.kampanye.index')
            : route('admin.flash-sale.index');

        $masukanBaris = session('masukan_baris');
        $jumlahDisertakan = $flashSale->produks->count();
    @endphp

    <div class="mb-6">
        <a href="{{ $rutaKembali }}"
           class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 transition hover:text-brand-700">
            &larr; Kembali ke daftar kampanye
        </a>
    </div>

    {{-- Kepala kampanye --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-ink-950 via-brand-950 to-brand-900 p-6 shadow-elevate sm:p-8">
        <div class="pointer-events-none absolute inset-0 pola-grid opacity-60"></div>
        <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-accent-500/25 blur-3xl"></div>
        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-accent-500/60 to-transparent"></div>

        <div class="relative flex flex-wrap items-center justify-between gap-5">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-extrabold text-white">{{ $flashSale->nama }}</h1>
                    <span class="badge {{ $flashSale->status_warna }}">{{ $flashSale->status_label }}</span>
                </div>
                <p class="mt-1.5 text-sm text-ink-300">{{ $flashSale->durasi_label }}</p>

                @if ($flashSale->deskripsi)
                    <p class="mt-3 max-w-xl text-sm leading-relaxed text-ink-400">{{ $flashSale->deskripsi }}</p>
                @endif
            </div>

            <div class="flex flex-col items-end gap-3">
                <span class="rounded-2xl bg-accent-500 px-5 py-2.5 text-lg font-extrabold text-ink-950 shadow-accent">
                    −{{ $flashSale->diskon_persen }}%
                </span>

                @unless ($terkunci)
                    <form method="POST" action="{{ route('admin.flash-sale.ikut', $flashSale) }}">
                        @csrf
                        <button class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold transition
                                       {{ $flashSale->diikuti
                                            ? 'bg-white/[0.06] text-ink-200 ring-1 ring-white/10 hover:bg-white/10'
                                            : 'bg-emerald-500 text-ink-950 hover:-translate-y-0.5 hover:bg-emerald-400' }}">
                            <x-ikon :nama="$flashSale->diikuti ? 'silang' : 'centang'" kelas="h-4 w-4" />
                            {{ $flashSale->diikuti ? 'Berhenti Ikut' : 'Ikuti Kampanye' }}
                        </button>
                    </form>
                @endunless
            </div>
        </div>
    </div>

    @unless ($flashSale->diikuti)
        <div class="mt-6 flex items-start gap-3 rounded-2xl bg-amber-50 p-5 ring-1 ring-amber-200">
            <x-ikon nama="peringatan" kelas="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
            <div>
                <p class="text-sm font-bold text-amber-900">Toko belum mengikuti kampanye ini</p>
                <p class="mt-1 text-xs leading-relaxed text-amber-800">
                    Produk boleh dipilih sekarang, tetapi harga flash baru berlaku bagi pembeli
                    setelah Anda menekan &ldquo;Ikuti Kampanye&rdquo;.
                </p>
            </div>
        </div>
    @endunless

    @if ($terkunci)
        <div class="mt-6 flex items-start gap-3 rounded-2xl bg-slate-100 p-5 ring-1 ring-slate-200">
            <x-ikon nama="jam" kelas="mt-0.5 h-5 w-5 shrink-0 text-slate-500" />
            <div>
                <p class="text-sm font-bold text-slate-700">Kampanye sudah berakhir</p>
                <p class="mt-1 text-xs text-slate-500">Daftar di bawah hanya dapat dilihat, tidak dapat diubah.</p>
            </div>
        </div>
    @endif

    {{-- Pemilihan produk. Setiap baris berdiri sendiri: harganya sudah terisi
         potongan kampanye, dan tombolnya menempel di kanan baris itu sendiri
         sehingga daftar panjang tidak perlu digulung sampai ujung halaman. --}}
    <div class="card mt-6 overflow-hidden" x-data="{ cari: '', hanyaTerpilih: false }">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 p-6">
            <div>
                <h2 class="text-base font-extrabold text-slate-900">Produk yang Disertakan</h2>
                <p class="mt-0.5 text-xs text-slate-400">
                    Harga flash sudah terisi otomatis −{{ $flashSale->diskon_persen }}% dari harga normal.
                    Ubah bila perlu, lalu tekan tombol di kanan barisnya.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-xl bg-accent-50 px-3.5 py-2 text-xs font-bold text-accent-700 ring-1 ring-accent-200">
                    {{ $jumlahDisertakan }} diikuti
                </span>

                <input type="search" x-model="cari" placeholder="Cari produk…"
                       class="input-field !w-56 !py-2 text-sm">

                <label class="flex cursor-pointer items-center gap-2 rounded-xl bg-slate-50 px-3.5 py-2 ring-1 ring-slate-200">
                    <input type="checkbox" x-model="hanyaTerpilih"
                           class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    <span class="text-xs font-semibold text-slate-600">Hanya terpilih</span>
                </label>
            </div>
        </div>

        <div class="max-h-[38rem] overflow-auto">
            <table class="w-full min-w-[900px]">
                <thead class="sticky top-0 z-20 bg-slate-50">
                    <tr>
                        <th class="table-head">Produk</th>
                        <th class="table-head text-right">Harga Normal</th>
                        <th class="table-head text-center">Stok</th>
                        <th class="table-head">Harga Flash</th>
                        <th class="table-head">Kuota</th>
                        <th class="table-head text-center">Hemat</th>
                        <th class="table-head sticky right-0 bg-slate-50 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($produks as $item)
                        @php
                            $produk = $item['model'];
                            $baris = $item['baris'];

                            $usulan = (int) round($produk->harga * (100 - $flashSale->diskon_persen) / 100);
                            $galat = $errors->getBag('baris'.$produk->id);
                            $punyaMasukan = ($masukanBaris['id'] ?? null) === $produk->id;

                            $hargaAwal = $punyaMasukan ? $masukanBaris['harga_flash'] : ($baris?->harga_flash ?? $usulan);
                            $kuotaAwal = $punyaMasukan ? $masukanBaris['kuota'] : ($baris?->kuota ?? max(1, min(10, $produk->stok)));

                            $formId = 'fs-'.$produk->id;
                            $latar = $baris ? 'bg-accent-50/40' : 'bg-white';
                            $kataCari = Str::lower($produk->nama.' '.$produk->kategori?->nama);
                            $kabar = match (true) {
                                session('baris_tersimpan') === $produk->id => 'diikuti',
                                session('baris_dilepas') === $produk->id => 'dibatalkan',
                                default => '',
                            };
                        @endphp

                        <tr id="produk-{{ $produk->id }}"
                            x-data="{
                                harga: {{ (int) $hargaAwal }},
                                kabar: '{{ $kabar }}',
                                init() { if (this.kabar) setTimeout(() => this.kabar = '', 3200) },
                            }"
                            x-show="'{{ $kataCari }}'.includes(cari.toLowerCase())
                                    && (! hanyaTerpilih || {{ $baris ? 'true' : 'false' }})"
                            class="scroll-mt-20 {{ $latar }}">

                            <td class="table-cell">
                                <div class="flex items-center gap-2.5">
                                    <span class="h-2 w-2 shrink-0 rounded-full {{ $baris ? 'bg-accent-500' : 'bg-slate-200' }}"></span>
                                    <div class="min-w-0">
                                        <p class="font-bold text-slate-800">{{ $produk->nama }}</p>
                                        <p class="text-xs text-slate-400">{{ $produk->kategori?->nama }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="table-cell text-right font-semibold">{{ rp($produk->harga) }}</td>

                            <td class="table-cell text-center">
                                <span class="font-bold {{ $produk->stok <= 0 ? 'text-rose-600' : ($produk->stok <= 5 ? 'text-amber-600' : 'text-slate-700') }}">
                                    {{ $produk->stok }}
                                </span>
                            </td>

                            <td class="table-cell">
                                <input type="number" name="harga_flash" form="{{ $formId }}"
                                       x-model.number="harga"
                                       value="{{ $hargaAwal }}" min="1" step="1"
                                       @disabled($terkunci)
                                       class="input-field !w-36 !py-1.5 text-sm disabled:bg-slate-50 disabled:text-slate-400
                                              {{ $galat->has('harga_flash') ? '!border-rose-400' : '' }}">
                                @if ($galat->has('harga_flash'))
                                    <p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $galat->first('harga_flash') }}</p>
                                @endif
                            </td>

                            <td class="table-cell">
                                <input type="number" name="kuota" form="{{ $formId }}"
                                       value="{{ $kuotaAwal }}" min="1" max="{{ $produk->stok }}" step="1"
                                       @disabled($terkunci)
                                       class="input-field !w-24 !py-1.5 text-sm disabled:bg-slate-50 disabled:text-slate-400
                                              {{ $galat->has('kuota') ? '!border-rose-400' : '' }}">
                                @if ($galat->has('kuota'))
                                    <p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $galat->first('kuota') }}</p>
                                @endif

                                @if ($baris && $baris->terjual > 0)
                                    <p class="mt-1 text-[11px] font-semibold text-emerald-600">{{ $baris->terjual }} terjual</p>
                                @endif
                            </td>

                            {{-- Persentase dihitung ulang sambil harga diketik, supaya
                                 dampak perubahannya terlihat sebelum disimpan. --}}
                            <td class="table-cell text-center">
                                <span class="badge bg-accent-100 text-accent-700 ring-accent-200"
                                      x-show="harga > 0 && harga < {{ (int) $produk->harga }}">
                                    −<span x-text="Math.round((1 - harga / {{ (int) $produk->harga }}) * 100)"></span>%
                                </span>
                                <span class="text-xs text-slate-300"
                                      x-show="! (harga > 0 && harga < {{ (int) $produk->harga }})">—</span>
                            </td>

                            <td class="table-cell sticky right-0 {{ $latar }} text-right
                                       shadow-[-10px_0_14px_-10px_rgba(15,23,42,0.18)]">
                                @if ($terkunci)
                                    <span class="text-xs text-slate-400">Terkunci</span>
                                @else
                                    <div class="flex items-center justify-end gap-2">
                                        <span x-show="kabar" x-cloak x-transition
                                              class="inline-flex items-center rounded-lg px-2 py-1 text-[11px] font-bold"
                                              :class="kabar === 'diikuti' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600'"
                                              x-text="kabar === 'diikuti' ? 'Diikuti' : 'Dibatalkan'"></span>

                                        <button form="{{ $formId }}"
                                                class="{{ $baris ? 'btn-secondary' : 'btn-primary' }} btn-sm">
                                            {{ $baris ? 'Perbarui' : 'Ikuti' }}
                                        </button>

                                        @if ($baris)
                                            <button form="{{ $formId }}" name="tindakan" value="lepas"
                                                    class="rounded-lg px-2 py-1.5 text-xs font-bold text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                                Batal Ikuti
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-14 text-center">
                                <p class="text-sm font-semibold text-slate-500">Belum ada produk di katalog.</p>
                                <a href="{{ route('admin.produk.create') }}" class="btn-primary mt-4">Tambah Produk</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Form per produk diletakkan di luar tabel lalu dirujuk lewat atribut
             form= pada tiap kolom, sebab <form> tidak sah bersarang di dalam <tr>. --}}
        @unless ($terkunci)
            <div class="hidden">
                @foreach ($produks as $item)
                    <form id="fs-{{ $item['model']->id }}" method="POST"
                          action="{{ route('admin.flash-sale.produk', [$flashSale, $item['model']]) }}">
                        @csrf
                    </form>
                @endforeach
            </div>
        @endunless

        <div class="border-t border-slate-100 px-6 py-4">
            <p class="text-xs text-slate-400">
                Harga flash harus lebih murah dari harga normal, dan kuota tidak boleh melebihi stok.
                Setiap produk diikutkan sendiri-sendiri.
            </p>
        </div>
    </div>
</x-layouts.admin>
