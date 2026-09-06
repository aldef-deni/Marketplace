<x-layouts.admin>
    <x-slot name="title">{{ $flashSale->nama }}</x-slot>

    @php
        $terkunci = $flashSale->sudahBerakhir();
    @endphp

    <div class="mb-6">
        <a href="{{ route('admin.flash-sale.index') }}"
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

    {{-- Pemilihan produk --}}
    <form method="POST" action="{{ route('admin.flash-sale.produk', $flashSale) }}" class="mt-6"
          x-data="{ cari: '', hanyaTerpilih: false }">
        @csrf

        <div class="card overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 p-6">
                <div>
                    <h2 class="text-base font-extrabold text-slate-900">Produk yang Disertakan</h2>
                    <p class="mt-0.5 text-xs text-slate-400">
                        Centang produk, tentukan harga flash dan kuotanya. Kuota membatasi berapa unit
                        yang dijual dengan harga promo.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
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
                <table class="w-full min-w-[860px]">
                    <thead class="sticky top-0 z-10 bg-slate-50">
                        <tr>
                            <th class="table-head w-12"></th>
                            <th class="table-head">Produk</th>
                            <th class="table-head text-right">Harga Normal</th>
                            <th class="table-head text-center">Stok</th>
                            <th class="table-head">Harga Flash</th>
                            <th class="table-head">Kuota</th>
                            <th class="table-head text-center">Hemat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($produks as $item)
                            @php
                                $produk = $item['model'];
                                $baris = $item['baris'];
                                $usulan = (int) round($produk->harga * (100 - $flashSale->diskon_persen) / 100);
                                $kunciHarga = "produk.{$produk->id}.harga_flash";
                                $kunciKuota = "produk.{$produk->id}.kuota";
                                $hargaAwal = old($kunciHarga, $baris?->harga_flash ?? $usulan);
                                $kuotaAwal = old($kunciKuota, $baris?->kuota ?? min(10, $produk->stok));
                            @endphp

                            <tr x-data="{ ikut: {{ old("produk.{$produk->id}.ikut", $baris ? 'true' : 'false') }} }"
                                x-show="(cari === '' || '{{ Str::lower($produk->nama).' '.Str::lower($produk->kategori?->nama) }}'.includes(cari.toLowerCase()))
                                        && (! hanyaTerpilih || ikut)"
                                class="transition"
                                :class="ikut && 'bg-accent-50/40'">

                                <td class="table-cell">
                                    <input type="checkbox" x-model="ikut" value="1"
                                           name="produk[{{ $produk->id }}][ikut]"
                                           @disabled($terkunci)
                                           class="h-5 w-5 rounded border-slate-300 text-accent-500 focus:ring-accent-500">
                                </td>

                                <td class="table-cell">
                                    <p class="font-bold text-slate-800">{{ $produk->nama }}</p>
                                    <p class="text-xs text-slate-400">{{ $produk->kategori?->nama }}</p>
                                </td>

                                <td class="table-cell text-right font-semibold">{{ rp($produk->harga) }}</td>

                                <td class="table-cell text-center">
                                    <span class="font-bold {{ $produk->stok <= 0 ? 'text-rose-600' : ($produk->stok <= 5 ? 'text-amber-600' : 'text-slate-700') }}">
                                        {{ $produk->stok }}
                                    </span>
                                </td>

                                <td class="table-cell">
                                    <input type="number" name="produk[{{ $produk->id }}][harga_flash]"
                                           value="{{ $hargaAwal }}" min="0" step="1000"
                                           x-bind:disabled="! ikut || {{ $terkunci ? 'true' : 'false' }}"
                                           class="input-field !w-36 !py-1.5 text-sm disabled:bg-slate-50 disabled:text-slate-400">
                                    <x-input-error :messages="$errors->get($kunciHarga)" class="mt-1" />
                                </td>

                                <td class="table-cell">
                                    <input type="number" name="produk[{{ $produk->id }}][kuota]"
                                           value="{{ $kuotaAwal }}" min="1" max="{{ $produk->stok }}"
                                           x-bind:disabled="! ikut || {{ $terkunci ? 'true' : 'false' }}"
                                           class="input-field !w-24 !py-1.5 text-sm disabled:bg-slate-50 disabled:text-slate-400">
                                    <x-input-error :messages="$errors->get($kunciKuota)" class="mt-1" />

                                    @if ($baris && $baris->terjual > 0)
                                        <p class="mt-1 text-[11px] font-semibold text-emerald-600">
                                            {{ $baris->terjual }} terjual
                                        </p>
                                    @endif
                                </td>

                                <td class="table-cell text-center">
                                    @if ($baris)
                                        <span class="badge bg-accent-100 text-accent-700 ring-accent-200">
                                            −{{ $baris->persen_hemat }}%
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-300">—</span>
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

            @unless ($terkunci)
                <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 p-6">
                    <button class="btn-primary">Simpan Pilihan Produk</button>
                    <a href="{{ route('admin.flash-sale.index') }}" class="btn-secondary">Batal</a>

                    <p class="ml-auto text-xs text-slate-400">
                        Harga flash harus lebih murah dari harga normal, dan kuota tidak boleh melebihi stok.
                    </p>
                </div>
            @endunless
        </div>
    </form>
</x-layouts.admin>
