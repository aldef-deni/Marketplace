<x-layouts.admin>
    <x-slot name="title">Pesanan {{ $pesanan->no_invoice }}</x-slot>

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-extrabold text-slate-900">{{ $pesanan->no_invoice }}</h2>
                <span class="badge {{ $pesanan->status_warna }}">{{ $pesanan->status_label }}</span>
            </div>
            <p class="mt-1 text-sm text-slate-500">Dibuat {{ tanggalIndo($pesanan->created_at, true) }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('pesanan.cetak', $pesanan->no_invoice) }}" target="_blank" class="btn-secondary btn-sm">Cetak Invoice</a>
            <a href="{{ route('admin.pesanan.index') }}" class="btn-secondary btn-sm">← Kembali</a>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            {{-- Aksi status --}}
            <div class="card p-6">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-500">Kelola Status</h3>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">

                    @if ($pesanan->status === 'menunggu_konfirmasi')
                        <form action="{{ route('admin.pesanan.proses', $pesanan) }}" method="POST" class="rounded-2xl bg-brand-50/60 p-5 ring-1 ring-brand-100">
                            @csrf
                            <p class="text-sm font-extrabold text-slate-800">
                                {{ $pesanan->isCod() ? 'Pesanan COD' : 'Verifikasi & Proses' }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $pesanan->isCod()
                                    ? 'Terima pesanan COD dan lanjutkan ke pengemasan.'
                                    : 'Terima pembayaran dan lanjutkan ke pengemasan.' }}
                            </p>
                            <button class="btn-primary mt-4 w-full">Terima & Proses</button>
                        </form>
                    @endif

                    @if ($pesanan->status === 'menunggu_pembayaran' && $pesanan->pembayaran?->metodePembayaran?->tipe !== 'cod')
                        <div class="rounded-2xl bg-amber-50/60 p-5 ring-1 ring-amber-100 sm:col-span-2">
                            <p class="text-sm font-extrabold text-slate-800">Menunggu Bukti Pembayaran</p>
                            <p class="mt-1 text-xs text-slate-500">
                                Pelanggan belum mengunggah bukti pembayaran. Batas: {{ $pesanan->batas_pembayaran ? tanggalIndo($pesanan->batas_pembayaran, true) : '-' }}
                            </p>
                            <form action="{{ route('admin.pesanan.batalkan', $pesanan) }}" method="POST" class="mt-3" onsubmit="return confirm('Batalkan pesanan ini?')">
                                @csrf
                                <input type="hidden" name="keterangan" value="Melewati batas waktu pembayaran.">
                                <button class="btn-danger btn-sm">Batalkan Pesanan</button>
                            </form>
                        </div>
                    @endif

                    @if ($pesanan->status === 'diproses')
                        <form action="{{ route('admin.pesanan.kirim', $pesanan) }}" method="POST" class="rounded-2xl bg-sky-50/60 p-5 ring-1 ring-sky-100 sm:col-span-2">
                            @csrf
                            <p class="text-sm font-extrabold text-slate-800">Input No. Resi Pengiriman</p>
                            <p class="mt-1 text-xs text-slate-500">Kurir: {{ $pesanan->kurir }} {{ $pesanan->layanan_kurir }} — {{ $pesanan->alamat->kota }}, {{ $pesanan->alamat->provinsi }}</p>
                            <div class="mt-3 flex flex-wrap gap-3">
                                <input type="text" name="no_resi" placeholder="No. resi pengiriman" class="input-field flex-1" required>
                                <button class="btn-primary">Kirim Pesanan →</button>
                            </div>
                            <input type="text" name="catatan" placeholder="Catatan pengiriman (opsional)" class="input-field mt-3">
                        </form>
                    @endif

                    @if (in_array($pesanan->status, ['dikirim', 'diproses']))
                        <form action="{{ route('admin.pesanan.selesai', $pesanan) }}" method="POST" class="rounded-2xl bg-emerald-50/60 p-5 ring-1 ring-emerald-100">
                            @csrf
                            <p class="text-sm font-extrabold text-slate-800">Tandai Selesai</p>
                            <p class="mt-1 text-xs text-slate-500">Tandai pesanan telah diterima pelanggan.</p>
                            <button class="btn-primary mt-4 w-full !bg-emerald-600 hover:!bg-emerald-700">Selesaikan Pesanan</button>
                        </form>
                    @endif

                    @if (in_array($pesanan->status, ['menunggu_konfirmasi', 'menunggu_pembayaran', 'diproses']))
                        <form action="{{ route('admin.pesanan.batalkan', $pesanan) }}" method="POST" class="rounded-2xl bg-rose-50/60 p-5 ring-1 ring-rose-100" onsubmit="return confirm('Batalkan pesanan ini? Stok akan dikembalikan.')">
                            @csrf
                            <p class="text-sm font-extrabold text-slate-800">Batalkan Pesanan</p>
                            <p class="mt-1 text-xs text-slate-500">Stok otomatis dikembalikan ke katalog.</p>
                            <input type="text" name="keterangan" placeholder="Alasan pembatalan (opsional)" class="input-field mt-3">
                            <button class="btn-danger mt-4 w-full">Batalkan Pesanan</button>
                        </form>
                    @endif

                    @if (in_array($pesanan->status, ['selesai', 'dibatalkan']))
                        <div class="rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-200/70 sm:col-span-2">
                            <p class="text-sm font-extrabold text-slate-800">
                                {{ $pesanan->status === 'selesai' ? 'Pesanan telah selesai' : 'Pesanan dibatalkan' }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $pesanan->status === 'selesai' ? "Selesai pada ".tanggalIndo($pesanan->selesai_at, true) : ($pesanan->catatan ?: 'Tidak ada keterangan.') }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Bukti pembayaran --}}
            @if ($pesanan->pembayaran?->bukti)
                <div class="card p-6">
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-500">Bukti Pembayaran</h3>
                    <div class="mt-4 flex flex-wrap items-start gap-5">
                        <a href="{{ asset($pesanan->pembayaran->bukti) }}" target="_blank">
                            <img src="{{ asset($pesanan->pembayaran->bukti) }}" class="h-48 w-48 rounded-2xl object-cover ring-1 ring-slate-200" alt="Bukti bayar">
                        </a>
                        <div class="space-y-2 text-sm">
                            <p><span class="font-semibold text-slate-400">Pengirim:</span> <span class="font-extrabold text-slate-800">{{ $pesanan->pembayaran->nama_pengirim }}</span></p>
                            <p><span class="font-semibold text-slate-400">Jumlah:</span> <span class="font-extrabold text-brand-700">{{ rp($pesanan->pembayaran->jumlah) }}</span></p>
                            <p><span class="font-semibold text-slate-400">Metode:</span> {{ $pesanan->pembayaran->metodePembayaran->nama }}</p>
                            <p><span class="font-semibold text-slate-400">Status:</span> <span class="badge {{ $pesanan->pembayaran->status_warna }}">{{ $pesanan->pembayaran->status_label }}</span></p>
                            <p><span class="font-semibold text-slate-400">Kode:</span> {{ $pesanan->pembayaran->kode }}</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Item pesanan --}}
            <div class="card p-6">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-500">Item Pesanan</h3>
                <div class="mt-4 space-y-4">
                    @foreach ($pesanan->items as $item)
                        <div class="flex items-center gap-4">
                            @if ($item->gambar)
                                <img src="{{ asset($item->gambar) }}" class="h-16 w-16 rounded-xl object-cover" alt="">
                            @else
                                <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-slate-100 text-2xl"><x-ikon nama="toko" kelas="h-6 w-6" /></div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-slate-800">{{ $item->nama_produk }}</p>
                                <p class="text-xs text-slate-400">{{ rp($item->harga) }} × {{ $item->qty }}</p>
                            </div>
                            <p class="text-sm font-extrabold text-slate-800">{{ rp($item->subtotal) }}</p>
                        </div>
                    @endforeach
                </div>

                <dl class="mt-5 space-y-2.5 border-t border-dashed border-slate-200 pt-5 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd class="font-bold">{{ rp($pesanan->subtotal) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Ongkir ({{ $pesanan->kurir }} {{ $pesanan->layanan_kurir }})</dt><dd class="font-bold">{{ rp($pesanan->ongkir) }}</dd></div>
                    <div class="flex justify-between border-t border-slate-100 pt-3 text-base"><dt class="font-extrabold">Total</dt><dd class="font-extrabold text-brand-700">{{ rp($pesanan->total) }}</dd></div>
                </dl>
            </div>
        </div>

        {{-- Sidebar info --}}
        <div class="space-y-6">
            <div class="card p-6">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-500">Pelanggan</h3>
                <div class="mt-3 flex items-center gap-3">
                    <x-avatar :user="$pesanan->user" ukuran="h-11 w-11" teks="text-sm" cincin="ring-1 ring-slate-200" />
                    <div>
                        <p class="text-sm font-extrabold text-slate-800">{{ $pesanan->user->name }}</p>
                        <p class="text-xs text-slate-400">{{ $pesanan->user->email }}</p>
                        <p class="text-xs text-slate-400">{{ $pesanan->user->phone ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <div class="card p-6">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-500">Alamat Pengiriman</h3>
                <div class="mt-3">
                    <span class="badge bg-brand-50 text-brand-700 ring-brand-200">{{ $pesanan->alamat->label }}</span>
                    <p class="mt-3 text-sm font-extrabold text-slate-800">{{ $pesanan->alamat->nama_penerima }}</p>
                    <p class="text-xs font-medium text-slate-400">{{ $pesanan->alamat->no_hp }}</p>
                    <p class="mt-3 text-xs leading-relaxed text-slate-500">{{ $pesanan->alamat->alamat_lengkap_koma }}</p>
                </div>
            </div>

            <div class="card p-6">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-500">Info Pengiriman</h3>
                <dl class="mt-3 space-y-2.5 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Kurir</dt><dd class="font-bold">{{ $pesanan->kurir }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Layanan</dt><dd class="font-bold">{{ $pesanan->layanan_kurir }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Resi</dt><dd class="font-extrabold tracking-wide">{{ $pesanan->pengiriman?->no_resi ?? '-' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Status Kirim</dt><dd class="font-bold">{{ $pesanan->pengiriman?->status_label ?? 'Belum dibuat' }}</dd></div>
                    @if ($pesanan->dikirim_at)
                        <div class="flex justify-between"><dt class="text-slate-500">Dikirim</dt><dd class="font-bold">{{ tanggalIndo($pesanan->dikirim_at) }}</dd></div>
                    @endif
                    @if ($pesanan->selesai_at)
                        <div class="flex justify-between"><dt class="text-slate-500">Selesai</dt><dd class="font-bold">{{ tanggalIndo($pesanan->selesai_at) }}</dd></div>
                    @endif
                </dl>
            </div>

            <div class="card p-6">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-500">Pembayaran</h3>
                <div class="mt-3 flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl text-xl {{ match ($pesanan->pembayaran?->metodePembayaran?->tipe) { 'transfer' => 'bg-blue-100', 'ewallet' => 'bg-emerald-100', 'cod' => 'bg-amber-100', default => 'bg-slate-100' } }}">
                        <x-ikon :nama="match ($pesanan->pembayaran?->metodePembayaran?->tipe) { 'transfer' => 'bank', 'ewallet' => 'ponsel', 'cod' => 'uang', default => 'kartu' }" kelas="h-5 w-5 text-slate-700" />
                    </span>
                    <div>
                        <p class="text-sm font-bold text-slate-800">{{ $pesanan->pembayaran?->metodePembayaran?->nama }}</p>
                        <p class="text-xs text-slate-400">{{ $pesanan->pembayaran?->kode }}</p>
                    </div>
                </div>
            </div>

            @if ($pesanan->catatan)
                <div class="card p-6">
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-500">Catatan Pelanggan</h3>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $pesanan->catatan }}</p>
                </div>
            @endif
        </div>
    </div>

</x-layouts.admin>