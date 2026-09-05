<x-layouts.admin>
    <x-slot name="title">Metode Pembayaran</x-slot>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Daftar metode --}}
        <div class="card overflow-hidden lg:col-span-2">
            <div class="p-6 pb-4">
                <h3 class="text-base font-extrabold text-slate-900">Metode Pembayaran</h3>
                <p class="mt-0.5 text-xs text-slate-400">Metode aktif akan tampil di halaman checkout</p>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach ($metodes->groupBy('tipe') as $tipe => $kelompok)
                    <div class="px-6 pt-5">
                        <span class="badge {{ $kelompok->first()->warna }}">{{ $kelompok->first()->label_tipe }}</span>
                    </div>
                    @foreach ($kelompok as $metode)
                        <div class="flex flex-wrap items-center gap-4 px-6 py-4 transition hover:bg-slate-50/60">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-2xl {{ match ($metode->tipe) { 'transfer' => 'bg-blue-50', 'ewallet' => 'bg-emerald-50', 'cod' => 'bg-amber-50', default => 'bg-slate-50' } }}">
                                <x-ikon :nama="match ($metode->tipe) { 'transfer' => 'bank', 'ewallet' => 'ponsel', 'cod' => 'uang', default => 'kartu' }" kelas="h-6 w-6 text-slate-700" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-extrabold text-slate-800">{{ $metode->nama }}</p>
                                @if ($metode->nomor_rekening)
                                    <p class="text-xs text-slate-400">{{ $metode->nomor_rekening }} • {{ $metode->atas_nama }}</p>
                                @else
                                    <p class="text-xs text-slate-400">{{ $metode->instruksi }}</p>
                                @endif
                            </div>
                            <span class="badge bg-slate-100 text-slate-500 ring-slate-200">{{ $metode->pembayarans_count }}× dipakai</span>
                            <div class="flex items-center gap-2">
                                <form action="{{ route('admin.metode-pembayaran.status', $metode) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button class="rounded-lg px-3 py-1.5 text-xs font-bold ring-1 transition {{ $metode->aktif ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 hover:bg-emerald-100' : 'bg-slate-50 text-slate-500 ring-slate-200 hover:bg-slate-100' }}">
                                        {{ $metode->aktif ? '● Aktif' : '○ Nonaktif' }}
                                    </button>
                                </form>
                                <form action="{{ route('admin.metode-pembayaran.destroy', $metode) }}" method="POST" onsubmit="return confirm('Hapus metode ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-600 transition hover:bg-rose-100"><x-ikon nama="sampah" kelas="h-3.5 w-3.5" /></button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>

        {{-- Form tambah --}}
        <div class="card h-fit p-6 lg:sticky lg:top-24">
            <h3 class="text-base font-extrabold text-slate-900">Tambah Metode</h3>
            <form action="{{ route('admin.metode-pembayaran.store') }}" method="POST" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="label-field">Nama Metode *</label>
                    <input type="text" name="nama" class="input-field" required placeholder="Contoh: Transfer Bank BNI">
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
                <div>
                    <label class="label-field">Instruksi</label>
                    <textarea name="instruksi" rows="3" class="input-field" placeholder="Langkah pembayaran untuk pelanggan"></textarea>
                </div>
                <label class="flex items-center gap-3 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200/70">
                    <input type="checkbox" name="aktif" value="1" checked class="h-5 w-5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    <span class="text-sm font-semibold text-slate-700">Aktif (tampil di checkout)</span>
                </label>
                <button class="btn-primary w-full">Simpan Metode</button>
            </form>
        </div>
    </div>

</x-layouts.admin>