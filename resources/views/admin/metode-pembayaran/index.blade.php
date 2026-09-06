<x-layouts.admin>
    <x-slot name="title">Metode Pembayaran</x-slot>

    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-ink-950 via-brand-950 to-brand-900 p-6 shadow-elevate sm:p-8">
        <div class="pointer-events-none absolute inset-0 pola-grid opacity-60"></div>
        <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-brand-500/30 blur-3xl"></div>
        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-brand-400/60 to-transparent"></div>

        <div class="relative flex flex-wrap items-center justify-between gap-5">
            <div class="min-w-0">
                <span class="badge bg-brand-500/15 text-brand-200 ring-brand-500/30">Superadmin</span>
                <h1 class="mt-3 text-2xl font-extrabold text-white">Metode Pembayaran</h1>
                <p class="mt-1.5 max-w-xl text-sm text-ink-300">
                    Isi nomor rekening atau nomor e-wallet tiap metode. Metode yang nomornya
                    kosong tidak ditawarkan saat checkout, dan lencananya ikut hilang dari footer.
                </p>
            </div>

            <div class="shrink-0 rounded-2xl bg-white/[0.07] p-4 text-center ring-1 ring-white/10 backdrop-blur">
                <p class="text-3xl font-extrabold text-white">{{ $jumlahSiap }}</p>
                <p class="text-xs font-bold uppercase tracking-wider text-ink-400">Siap dipakai</p>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- Daftar metode, tiap baris dapat disunting langsung --}}
        <div class="space-y-4 lg:col-span-2">
            @foreach ($metodes->groupBy('tipe') as $tipe => $kelompok)
                <div class="flex items-center gap-2">
                    <span class="badge {{ $kelompok->first()->warna_tipe }}">{{ $kelompok->first()->label_tipe }}</span>
                    <span class="text-xs font-semibold text-slate-400">{{ $kelompok->count() }} metode</span>
                </div>

                @foreach ($kelompok as $metode)
                    @php
                        $siap = $metode->siapDipakai();
                        $butuhNomor = $metode->tipe !== 'cod';

                        // old() berlaku untuk seluruh halaman, sedangkan halaman
                        // ini memuat satu formulir per metode. Tanpa penanda ini,
                        // kegagalan di satu kartu akan mengisi ulang semua kartu
                        // dengan nilai yang bukan miliknya.
                        $disunting = session('metode_disunting') === $metode->id;
                        $galat = $errors->getBag('metode'.$metode->id);
                        $isi = fn (string $kunci, $baku) => $disunting ? old($kunci, $baku) : $baku;
                    @endphp

                    <form action="{{ route('admin.metode-pembayaran.update', $metode) }}" method="POST"
                          class="card overflow-hidden p-5">
                        @csrf
                        @method('PATCH')

                        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-4">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl"
                                      style="background-color: {{ $metode->warna_merchant }}1a; color: {{ $metode->warna_merchant }}">
                                    <x-ikon :nama="match ($metode->tipe) { 'transfer' => 'bank', 'ewallet' => 'ponsel', 'cod' => 'uang', default => 'kartu' }" kelas="h-5 w-5" />
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-extrabold text-slate-800">{{ $metode->nama }}</p>
                                    <p class="text-xs text-slate-400">{{ $metode->pembayarans_count }}× dipakai pembeli</p>
                                </div>
                            </div>

                            {{-- Keadaan tampil dibuat menonjol: inilah akibat yang
                                 paling mudah terlewat saat nomornya dikosongkan. --}}
                            <span class="badge {{ $siap
                                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                    : 'bg-amber-50 text-amber-700 ring-amber-200' }}">
                                <x-ikon :nama="$siap ? 'centang' : 'peringatan'" kelas="h-3 w-3" />
                                {{ $siap ? 'Tampil di checkout' : $metode->alasan_belum_tampil }}
                            </span>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="label-field">Nama Metode *</label>
                                <input type="text" name="nama" class="input-field" required maxlength="100"
                                       value="{{ $isi('nama', $metode->nama) }}">
                            </div>

                            <div>
                                <label class="label-field">
                                    Nomor {{ $metode->tipe === 'ewallet' ? 'E-Wallet' : 'Rekening' }}
                                    @if ($butuhNomor) <span class="text-rose-500">*</span> @endif
                                </label>
                                <input type="text" name="nomor_rekening" class="input-field" maxlength="50"
                                       value="{{ $isi('nomor_rekening', $metode->nomor_rekening) }}"
                                       placeholder="{{ $butuhNomor ? 'Wajib agar metode ini tampil' : 'Tidak diperlukan untuk COD' }}">
                                @if ($butuhNomor)
                                    <p class="mt-1 text-[11px] text-slate-400">Dikosongkan berarti metode ini disembunyikan.</p>
                                @endif
                            </div>

                            <div>
                                <label class="label-field">Atas Nama</label>
                                <input type="text" name="atas_nama" class="input-field" maxlength="100"
                                       value="{{ $isi('atas_nama', $metode->atas_nama) }}">
                            </div>

                            <div>
                                <label class="label-field">Label Lencana Footer</label>
                                <input type="text" name="label_pendek" class="input-field" maxlength="30"
                                       value="{{ $isi('label_pendek', $metode->label_pendek) }}"
                                       placeholder="Contoh: BCA">
                            </div>

                            <div>
                                <label class="label-field">Warna Merchant</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" name="warna" value="{{ $isi('warna', $metode->warna_merchant) }}"
                                           class="h-[42px] w-14 cursor-pointer rounded-xl border border-slate-300 bg-white p-1">
                                    {{-- Pratinjau memakai warnanya langsung, bukan
                                         kelas .kartu-merchant yang baru berwarna
                                         saat disorot di footer gelap. --}}
                                    <span class="inline-flex items-center rounded-xl px-3.5 py-2 text-[11px] font-bold tracking-wide text-white"
                                          style="background-color: {{ $metode->warna_merchant }}">
                                        {{ $metode->label_badge }}
                                    </span>
                                </div>
                                <p class="mt-1 text-[11px] text-slate-400">Warna saat lencana disorot di footer.</p>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="label-field">Instruksi Pembayaran</label>
                                <textarea name="instruksi" rows="2" class="input-field"
                                          maxlength="500">{{ $isi('instruksi', $metode->instruksi) }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-4">
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl bg-slate-50 px-3.5 py-2 ring-1 ring-slate-200">
                                <input type="checkbox" name="aktif" value="1" @checked($metode->aktif)
                                       class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                <span class="text-xs font-semibold text-slate-600">Aktif</span>
                            </label>

                            <button class="btn-primary btn-sm">Simpan</button>

                            @if ($galat->isNotEmpty())
                                <p class="text-xs font-semibold text-rose-600">{{ $galat->first() }}</p>
                            @endif

                            <span class="ml-auto">
                                <button type="submit" form="hapus-{{ $metode->id }}"
                                        class="rounded-lg px-2 py-1.5 text-xs font-bold text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                    Hapus
                                </button>
                            </span>
                        </div>
                    </form>

                    {{-- Form hapus dipisah agar tidak bersarang di dalam form sunting. --}}
                    <form id="hapus-{{ $metode->id }}" method="POST" class="hidden"
                          action="{{ route('admin.metode-pembayaran.destroy', $metode) }}"
                          onsubmit="return confirm('Hapus metode {{ $metode->nama }}?')">
                        @csrf
                        @method('DELETE')
                    </form>
                @endforeach
            @endforeach
        </div>

        {{-- Form tambah --}}
        <div class="card h-fit p-6 lg:sticky lg:top-24">
            <h3 class="text-base font-extrabold text-slate-900">Tambah Metode</h3>
            <p class="mt-0.5 text-xs text-slate-400">Metode baru langsung tampil bila nomornya diisi.</p>

            <form action="{{ route('admin.metode-pembayaran.store') }}" method="POST" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="label-field">Nama Metode *</label>
                    <input type="text" name="nama" class="input-field" required placeholder="Contoh: Transfer Bank BNI">
                    <x-input-error :messages="$errors->get('nama')" class="mt-2" />
                </div>
                <div>
                    <label class="label-field">Tipe *</label>
                    <select name="tipe" class="input-field" required>
                        <option value="transfer">Transfer Bank</option>
                        <option value="ewallet">E-Wallet</option>
                        <option value="cod">COD (Bayar di Tempat)</option>
                    </select>
                </div>
                <div>
                    <label class="label-field">Nomor Rekening / Akun</label>
                    <input type="text" name="nomor_rekening" class="input-field" placeholder="Kosongkan jika COD">
                </div>
                <div>
                    <label class="label-field">Atas Nama</label>
                    <input type="text" name="atas_nama" class="input-field" placeholder="Nama pemilik rekening">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label-field">Label Lencana</label>
                        <input type="text" name="label_pendek" class="input-field" maxlength="30" placeholder="BNI">
                    </div>
                    <div>
                        <label class="label-field">Warna</label>
                        <input type="color" name="warna" value="#0B5FB0"
                               class="h-[42px] w-full cursor-pointer rounded-xl border border-slate-300 bg-white p-1">
                    </div>
                </div>
                <div>
                    <label class="label-field">Instruksi</label>
                    <textarea name="instruksi" rows="3" class="input-field" placeholder="Langkah pembayaran untuk pelanggan"></textarea>
                </div>
                <label class="flex items-center gap-3 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200/70">
                    <input type="checkbox" name="aktif" value="1" checked class="h-5 w-5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    <span class="text-sm font-semibold text-slate-700">Aktif</span>
                </label>
                <button class="btn-primary w-full">Simpan Metode</button>
            </form>
        </div>
    </div>
</x-layouts.admin>
