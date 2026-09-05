<x-layouts.app>
    <x-slot name="title">Pesanan {{ $pesanan->no_invoice }}</x-slot>

    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-extrabold text-slate-900">{{ $pesanan->no_invoice }}</h2>
                    <span class="badge {{ $pesanan->status_warna }}">{{ $pesanan->status_label }}</span>
                </div>
                <p class="mt-0.5 text-sm text-slate-500">Dibuat {{ tanggalIndo($pesanan->created_at, true) }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('pesanan.cetak', $pesanan->no_invoice) }}" target="_blank" class="btn-secondary btn-sm">Cetak</a>
                <a href="{{ route('pesanan.index') }}" class="btn-secondary btn-sm">← Semua Pesanan</a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid gap-6 lg:grid-cols-3">

            <div class="space-y-6 lg:col-span-2">
                {{-- Timeline --}}
                <section class="card p-6">
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-500">Status Pesanan</h3>
                    <div class="mt-6">
                        @php $langkah = $pesanan->langkah_selesai; @endphp
                        <div class="flex items-center">
                            @foreach ([
                                ['1', 'Pesanan Dibuat', 'menunggu_pembayaran', 'menunggu_konfirmasi'],
                                ['2', 'Diverifikasi & Diproses', 'menunggu_konfirmasi', 'diproses'],
                                ['3', 'Dalam Pengiriman', 'diproses', 'dikirim'],
                                ['4', 'Pesanan Selesai', 'dikirim', 'selesai'],
                            ] as $i => [$angka, $label, $dari, $ke])
                                <div class="flex flex-1 flex-col items-center {{ $i === 3 ? '' : 'sm:flex-row' }} {{ $i > 0 ? 'ml-2 sm:ml-0' : '' }}">
                                    @if ($i > 0)
                                        <div class="h-0.5 w-full bg-slate-200 sm:w-10 {{ $langkah > $i ? '!bg-emerald-500' : '' }}"></div>
                                    @endif
                                    <div class="flex flex-col items-center">
                                        <span class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-extrabold ring-4 ring-white {{ $pesanan->status === 'dibatalkan' ? 'bg-rose-500 text-white' : ($langkah > $i ? 'bg-emerald-500 text-white' : ($langkah === $i && ! in_array($pesanan->status, ['menunggu_pembayaran', 'menunggu_konfirmasi', 'diproses', 'dikirim', 'selesai']) ? 'bg-slate-300 text-white' : ($langkah >= $i ? 'bg-brand-600 text-white' : 'bg-slate-200 text-slate-400'))) }}">
                                            {{ $langkah > $i ? '✓' : $angka }}
                                        </span>
                                        <p class="mt-2 w-24 text-center text-[11px] font-bold {{ $langkah >= $i ? 'text-slate-800' : 'text-slate-400' }}">{{ $label }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                {{-- Instruksi pembayaran --}}
                @if ($pesanan->status === 'menunggu_pembayaran')
                    <section class="card overflow-hidden">
                        <div class="bg-gradient-to-r from-accent-600 to-accent-500 px-6 py-4">
                            <h3 class="text-base font-extrabold text-white">Menunggu Pembayaran</h3>
                            <p class="mt-0.5 text-xs text-accent-100">
                                Batas pembayaran: {{ tanggalIndo($pesanan->batas_pembayaran, true) }}
                            </p>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center gap-4 rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-200/70">
                                <span class="flex h-14 w-14 items-center justify-center rounded-2xl text-3xl {{ match ($pesanan->pembayaran->metodePembayaran->tipe) { 'transfer' => 'bg-blue-100', 'ewallet' => 'bg-emerald-100', 'cod' => 'bg-amber-100', default => 'bg-slate-100' } }}">
                                    <x-ikon :nama="match ($pesanan->pembayaran->metodePembayaran->tipe) { 'transfer' => 'bank', 'ewallet' => 'ponsel', 'cod' => 'uang', default => 'kartu' }" kelas="h-7 w-7 text-slate-700" />
                                </span>
                                <div class="flex-1">
                                    <p class="text-sm font-extrabold text-slate-800">{{ $pesanan->pembayaran->metodePembayaran->nama }}</p>
                                    <p class="text-xs text-slate-400">{{ $pesanan->pembayaran->metodePembayaran->label_tipe }}</p>
                                </div>
                                <p class="text-lg font-extrabold text-slate-900">{{ rp($pesanan->total) }}</p>
                            </div>

                            @if ($pesanan->pembayaran->metodePembayaran->nomor_rekening)
                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-2xl bg-blue-50/60 p-4 ring-1 ring-blue-100">
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-blue-400">Nomor Rekening / Akun</p>
                                        <p class="mt-1 text-lg font-extrabold tracking-wide text-blue-800">{{ $pesanan->pembayaran->metodePembayaran->nomor_rekening }}</p>
                                        <p class="text-xs font-semibold text-blue-600">a.n. {{ $pesanan->pembayaran->metodePembayaran->atas_nama }}</p>
                                    </div>
                                    <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200/70">
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total yang harus dibayar</p>
                                        <p class="mt-1 text-lg font-extrabold text-slate-900">{{ rp($pesanan->total) }}</p>
                                    </div>
                                </div>
                            @endif

                            @if ($pesanan->pembayaran->metodePembayaran->instruksi)
                                <div class="mt-4 rounded-2xl bg-amber-50/70 p-4 text-sm leading-relaxed text-amber-800 ring-1 ring-amber-100">
                                    <x-ikon nama="petir" kelas="h-4 w-4" /> <span class="font-bold">Instruksi:</span> {{ $pesanan->pembayaran->metodePembayaran->instruksi }}
                                </div>
                            @endif

                            {{-- Form unggah bukti --}}
                            <form action="{{ route('pesanan.bayar', $pesanan) }}" method="POST" enctype="multipart/form-data"
                                  class="mt-5 rounded-2xl border-2 border-dashed border-brand-200 bg-brand-50/40 p-5"
                                  onsubmit="this.querySelector('button[type=submit]').disabled = true">
                                @csrf
                                <p class="text-sm font-extrabold text-slate-800">Unggah Bukti Pembayaran</p>
                                <p class="mt-0.5 text-xs text-slate-500">Upload screenshot / foto bukti transfer untuk diverifikasi admin.</p>
                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <input type="file" name="bukti" accept="image/*" required
                                               class="block w-full cursor-pointer rounded-xl border border-slate-300 bg-white p-2.5 text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-4 file:py-2 file:text-xs file:font-bold file:text-white hover:file:bg-brand-700">
                                        @error('bukti') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <input type="text" name="nama_pengirim" placeholder="Nama pengirim (sesuai rekening)"
                                               class="input-field" value="{{ old('nama_pengirim') }}" required>
                                        @error('nama_pengirim') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                                <button type="submit" class="btn-primary mt-4 w-full">Kirim Bukti Pembayaran</button>
                            </form>

                            <form action="{{ route('pesanan.batalkan', $pesanan) }}" method="POST" class="mt-3 text-center"
                                  onsubmit="return confirm('Yakin ingin membatalkan pesanan ini?')">
                                @csrf
                                <button class="text-xs font-bold text-rose-500 hover:text-rose-700">Batalkan Pesanan</button>
                            </form>
                        </div>
                    </section>
                @endif

                {{-- Menunggu konfirmasi --}}
                @if ($pesanan->status === 'menunggu_konfirmasi')
                    <section class="card p-6">
                        <div class="flex items-start gap-4">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-orange-100 text-2xl"><x-ikon nama="cari" kelas="h-6 w-6" /></span>
                            <div>
                                <h3 class="text-base font-extrabold text-slate-900">Menunggu Konfirmasi Admin</h3>
                                <p class="mt-1 text-sm leading-relaxed text-slate-500">
                                    @if ($pesanan->isCod())
                                        Pesanan COD-mu sedang ditinjau admin. Kami akan segera memproses pesananmu. 
                                    @else
                                        Bukti pembayaranmu sedang diverifikasi oleh admin. Proses ini biasanya memakan waktu 1×24 jam.
                                    @endif
                                </p>
                                @if ($pesanan->pembayaran?->bukti)
                                    <div class="mt-4 flex items-center gap-3 rounded-2xl bg-emerald-50 p-4 ring-1 ring-emerald-100">
                                        <img src="{{ asset($pesanan->pembayaran->bukti) }}" class="h-16 w-16 rounded-xl object-cover" alt="Bukti bayar">
                                        <div>
                                            <p class="text-sm font-bold text-emerald-800">Bukti telah diunggah</p>
                                            <p class="text-xs text-emerald-600">a.n. {{ $pesanan->pembayaran->nama_pengirim }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        @if (! $pesanan->isCod())
                            <form action="{{ route('pesanan.batalkan', $pesanan) }}" method="POST" class="mt-5 border-t border-slate-100 pt-4 text-center"
                                  onsubmit="return confirm('Yakin ingin membatalkan pesanan ini?')">
                                @csrf
                                <button class="text-xs font-bold text-rose-500 hover:text-rose-700">Batalkan Pesanan</button>
                            </form>
                        @endif
                    </section>
                @endif

                {{-- Diproses / dikirim / selesai --}}
                @if (in_array($pesanan->status, ['diproses', 'dikirim', 'selesai']))
                    <section class="card p-6">
                        <div class="flex items-start gap-4">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-2xl {{ $pesanan->status === 'selesai' ? 'bg-emerald-100' : 'bg-brand-100' }}">
                                <x-ikon :nama="match ($pesanan->status) { 'diproses' => 'kotak', 'dikirim' => 'truk', 'selesai' => 'centang', default => 'papan' }" kelas="h-6 w-6 text-slate-700" />
                            </span>
                            <div class="flex-1">
                                <h3 class="text-base font-extrabold text-slate-900">{{ $pesanan->status_label }}</h3>
                                <p class="mt-1 text-sm leading-relaxed text-slate-500">
                                    @if ($pesanan->status === 'diproses')
                                        Pesananmu sedang dipersiapkan oleh tim kami. Mohon tunggu ya!
                                    @elseif ($pesanan->status === 'dikirim')
                                        Pesananmu sedang dalam perjalanan menuju alamat tujuan.
                                    @else
                                        Pesananmu telah diterima. Terima kasih sudah berbelanja! 
                                    @endif
                                </p>

                                @if ($pesanan->pengiriman)
                                    <div class="mt-4 grid gap-3 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200/70 sm:grid-cols-3">
                                        <div>
                                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Kurir</p>
                                            <p class="mt-0.5 text-sm font-extrabold text-slate-800">{{ $pesanan->pengiriman->kurir }} — {{ $pesanan->pengiriman->layanan }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">No. Resi</p>
                                            <p class="mt-0.5 text-sm font-extrabold tracking-wide text-slate-800">{{ $pesanan->pengiriman->no_resi ?? '-' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Estimasi</p>
                                            <p class="mt-0.5 text-sm font-extrabold text-slate-800">{{ $pesanan->pengiriman->dikirim_at ? tanggalIndo($pesanan->pengiriman->dikirim_at) : '-' }}</p>
                                        </div>
                                    </div>
                                @endif

                                @if ($pesanan->status === 'dikirim')
                                    <form action="{{ route('pesanan.terima', $pesanan) }}" method="POST" class="mt-5"
                                          onsubmit="return confirm('Konfirmasi bahwa pesanan sudah kamu terima?')">
                                        @csrf
                                        <button class="btn-primary w-full sm:w-auto">Konfirmasi Pesanan Diterima</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </section>
                @endif

                {{-- Dibatalkan --}}
                @if ($pesanan->status === 'dibatalkan')
                    <section class="card p-6">
                        <div class="flex items-start gap-4">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-2xl"><x-ikon nama="silang" kelas="h-6 w-6" /></span>
                            <div>
                                <h3 class="text-base font-extrabold text-slate-900">Pesanan Dibatalkan</h3>
                                <p class="mt-1 text-sm text-slate-500">Pesanan ini telah dibatalkan. Stok telah dikembalikan.</p>
                                @if ($pesanan->pembayaran?->keterangan)
                                    <p class="mt-2 rounded-xl bg-rose-50 p-3 text-xs text-rose-700 ring-1 ring-rose-100">{{ $pesanan->pembayaran->keterangan }}</p>
                                @endif
                            </div>
                        </div>
                    </section>
                @endif

                {{-- Detail item --}}
                <section class="card p-6">
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-500">Detail Pesanan</h3>
                    <div class="mt-4 space-y-4">
                        @foreach ($pesanan->items as $item)
                            <div class="flex items-center gap-4">
                                <div class="relative shrink-0">
                                    @if ($item->gambar)
                                        <img src="{{ asset($item->gambar) }}" class="h-16 w-16 rounded-xl object-cover" alt="">
                                    @else
                                        <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-slate-100 text-2xl"><x-ikon nama="toko" kelas="h-6 w-6" /></div>
                                    @endif
                                    <span class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-slate-800 text-[10px] font-bold text-white">{{ $item->qty }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-bold text-slate-800">{{ $item->nama_produk }}</p>
                                    <p class="text-xs text-slate-400">{{ rp($item->harga) }} × {{ $item->qty }}</p>
                                </div>
                                <p class="text-sm font-extrabold text-slate-800">{{ rp($item->subtotal) }}</p>
                            </div>
                        @endforeach
                    </div>

                    <dl class="mt-5 space-y-2.5 border-t border-dashed border-slate-200 pt-5 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd class="font-bold text-slate-800">{{ rp($pesanan->subtotal) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Ongkos Kirim ({{ $pesanan->kurir }} {{ $pesanan->layanan_kurir }})</dt><dd class="font-bold text-slate-800">{{ rp($pesanan->ongkir) }}</dd></div>
                        <div class="flex justify-between border-t border-slate-100 pt-3 text-base"><dt class="font-extrabold text-slate-900">Total</dt><dd class="font-extrabold text-brand-700">{{ rp($pesanan->total) }}</dd></div>
                    </dl>
                </section>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6 lg:sticky lg:top-24 lg:self-start">
                <section class="card p-6">
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-500">Alamat Pengiriman</h3>
                    <div class="mt-3 flex items-center gap-2">
                        <span class="badge bg-brand-50 text-brand-700 ring-brand-200">{{ $pesanan->alamat->label }}</span>
                    </div>
                    <p class="mt-3 text-sm font-extrabold text-slate-800">{{ $pesanan->alamat->nama_penerima }}</p>
                    <p class="text-xs font-medium text-slate-400">{{ $pesanan->alamat->no_hp }}</p>
                    <p class="mt-3 text-xs leading-relaxed text-slate-500">{{ $pesanan->alamat->alamat_lengkap_koma }}</p>
                </section>

                <section class="card p-6">
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
                    <div class="mt-4 flex items-center justify-between rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200/70">
                        <span class="text-xs font-bold text-slate-500">Status Bayar</span>
                        <span class="badge {{ $pesanan->pembayaran?->status_warna ?? 'bg-slate-100 text-slate-600 ring-slate-200' }}">{{ $pesanan->pembayaran?->status_label ?? '-' }}</span>
                    </div>
                </section>

                <section class="card p-6">
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-500">Ringkasan</h3>
                    <dl class="mt-3 space-y-2.5 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">No. Invoice</dt><dd class="font-bold text-slate-800">{{ $pesanan->no_invoice }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Tanggal Pesan</dt><dd class="font-bold text-slate-800">{{ tanggalIndo($pesanan->created_at) }}</dd></div>
                        @if ($pesanan->dikirim_at)
                            <div class="flex justify-between"><dt class="text-slate-500">Dikirim</dt><dd class="font-bold text-slate-800">{{ tanggalIndo($pesanan->dikirim_at) }}</dd></div>
                        @endif
                        @if ($pesanan->selesai_at)
                            <div class="flex justify-between"><dt class="text-slate-500">Selesai</dt><dd class="font-bold text-slate-800">{{ tanggalIndo($pesanan->selesai_at) }}</dd></div>
                        @endif
                        @if ($pesanan->catatan)
                            <div class="rounded-xl bg-slate-50 p-3 text-xs text-slate-500 ring-1 ring-slate-200/70">Catatan: {{ $pesanan->catatan }}</div>
                        @endif
                    </dl>
                </section>
            </div>
        </div>
    </div>

</x-layouts.app>