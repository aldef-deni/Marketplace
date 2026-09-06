@php
    // $filter, $pilihanStatus, $pilihanKurir, dan $pilihanMetode diwariskan
    // dari view pemanggil; sisanya dikirim lewat @include.
    $lengkap = $lengkap ?? true;
@endphp

{{-- Panel filter dipakai kedua laporan. Tautan unduhan membawa parameter yang
     sedang aktif, supaya berkasnya berisi persis apa yang tampil di layar. --}}
<form method="GET" action="{{ $aksi }}" class="card p-5">
    <div class="grid gap-4 md:grid-cols-4">
        <div>
            <label class="label-field">Dari Tanggal</label>
            <input type="date" name="dari" value="{{ $filter->dari->toDateString() }}" class="input-field">
        </div>

        <div>
            <label class="label-field">Sampai Tanggal</label>
            <input type="date" name="sampai" value="{{ $filter->sampai->toDateString() }}" class="input-field">
        </div>

        @if ($lengkap)
            <div>
                <label class="label-field">Status Pesanan</label>
                <select name="status" class="input-field">
                    <option value="">Semua status</option>
                    @foreach ($pilihanStatus as $nilai => $label)
                        <option value="{{ $nilai }}" @selected($filter->status === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label-field">Metode Pembayaran</label>
                <select name="metode" class="input-field">
                    <option value="">Semua metode</option>
                    @foreach ($pilihanMetode as $metode)
                        <option value="{{ $metode->id }}" @selected($filter->metodePembayaranId === $metode->id)>{{ $metode->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label-field">Kurir</label>
                <select name="kurir" class="input-field">
                    <option value="">Semua kurir</option>
                    @foreach ($pilihanKurir as $kurir)
                        <option value="{{ $kurir }}" @selected($filter->kurir === $kurir)>{{ $kurir }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="label-field">Cari Invoice / Pelanggan</label>
                <input type="text" name="cari" value="{{ $filter->cari }}" class="input-field"
                       placeholder="Nomor invoice, nama, atau email">
            </div>
        @endif

        <div class="flex items-end">
            <label class="flex cursor-pointer items-center gap-2.5 rounded-xl bg-slate-50 px-4 py-2.5 ring-1 ring-slate-200">
                <input type="checkbox" name="sertakan_batal" value="1" @checked($filter->sertakanBatal)
                       class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                <span class="text-xs font-semibold text-slate-600">Sertakan pesanan batal</span>
            </label>
        </div>
    </div>

    <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
        <button class="btn-primary">Terapkan Filter</button>
        <a href="{{ $aksi }}" class="btn-secondary">Atur Ulang</a>

        {{-- Tombol hanya muncul bila pustaka pembuat berkasnya benar-benar
             terpasang; menawarkan unduhan yang pasti gagal hanya menyesatkan. --}}
        @php $siap = unduhanLaporanSiap(); @endphp

        <div class="ml-auto flex items-center gap-2">
            @if ($siap['pdf'])
                <a href="{{ $unduhPdf }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-rose-50 px-4 py-2.5 text-sm font-bold text-rose-700 ring-1 ring-rose-200 transition hover:bg-rose-100">
                    <x-ikon nama="printer" kelas="h-4 w-4" />
                    Unduh PDF
                </a>
            @endif

            @if ($siap['excel'])
                <a href="{{ $unduhExcel }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-emerald-50 px-4 py-2.5 text-sm font-bold text-emerald-700 ring-1 ring-emerald-200 transition hover:bg-emerald-100">
                    <x-ikon nama="grafik" kelas="h-4 w-4" />
                    Unduh Excel
                </a>
            @endif

            @unless ($siap['pdf'] && $siap['excel'])
                <span class="rounded-xl bg-amber-50 px-4 py-2.5 text-xs font-semibold text-amber-800 ring-1 ring-amber-200">
                    Modul unduhan belum terpasang di server
                </span>
            @endunless
        </div>
    </div>
</form>
