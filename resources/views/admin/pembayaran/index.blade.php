<x-layouts.admin>
    <x-slot name="title">Verifikasi Pembayaran</x-slot>

    <div class="card overflow-hidden">
        <div class="p-6 pb-4">
            <h3 class="text-base font-extrabold text-slate-900">Verifikasi Pembayaran</h3>
            <p class="mt-0.5 text-xs text-slate-400">Periksa dan verifikasi bukti pembayaran pelanggan</p>
        </div>

        <div class="flex flex-wrap gap-2 px-6 pb-4">
            <a href="{{ route('admin.pembayaran.index') }}"
               class="rounded-full px-4 py-2 text-xs font-bold transition {{ ! request('status') ? 'bg-brand-600 text-white shadow-md shadow-brand-200' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                Semua ({{ $jumlahStatus->sum() }})
            </a>
            @foreach (['menunggu' => 'Menunggu', 'ditolak' => 'Ditolak', 'dibayar' => 'Dibayar', 'dibatalkan' => 'Dibatalkan'] as $nilai => $label)
                <a href="{{ route('admin.pembayaran.index', ['status' => $nilai]) }}"
                   class="rounded-full px-4 py-2 text-xs font-bold transition {{ request('status') === $nilai ? 'bg-brand-600 text-white shadow-md shadow-brand-200' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                    {{ $label }} ({{ $jumlahStatus[$nilai] ?? 0 }})
                </a>
            @endforeach
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px]">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="table-head">Kode / Invoice</th>
                        <th class="table-head">Pelanggan</th>
                        <th class="table-head">Metode</th>
                        <th class="table-head">Jumlah</th>
                        <th class="table-head">Bukti</th>
                        <th class="table-head">Status</th>
                        <th class="table-head text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pembayarans as $pembayaran)
                        <tr class="transition hover:bg-slate-50/60">
                            <td class="table-cell">
                                <p class="font-extrabold text-brand-700">{{ $pembayaran->kode }}</p>
                                <p class="text-xs text-slate-400">{{ $pembayaran->pesanan->no_invoice }}</p>
                            </td>
                            <td class="table-cell">
                                <p class="font-bold text-slate-800">{{ $pembayaran->pesanan->user->name }}</p>
                                <p class="text-xs text-slate-400">{{ $pembayaran->nama_pengirim ?? '-' }}</p>
                            </td>
                            <td class="table-cell text-xs font-semibold text-slate-500">{{ $pembayaran->metodePembayaran->nama }}</td>
                            <td class="table-cell font-extrabold">{{ rp($pembayaran->jumlah) }}</td>
                            <td class="table-cell">
                                @if ($pembayaran->bukti)
                                    <a href="{{ asset($pembayaran->bukti) }}" target="_blank">
                                        <img src="{{ asset($pembayaran->bukti) }}" class="h-12 w-12 rounded-xl object-cover ring-1 ring-slate-200 transition hover:scale-110" alt="Bukti">
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="table-cell">
                                <span class="badge {{ $pembayaran->status_warna }}">{{ $pembayaran->status_label }}</span>
                                {{-- Alasan penolakan dulu hanya tampil bila ada bukti, sehingga
                                     penolakan pada pembayaran tanpa bukti tidak meninggalkan jejak. --}}
                                @if ($pembayaran->keterangan)
                                    <p class="mt-1 max-w-48 text-[10px] leading-relaxed text-rose-600" title="{{ $pembayaran->keterangan }}">
                                        {{ \Illuminate\Support\Str::limit($pembayaran->keterangan, 60) }}
                                    </p>
                                @endif
                            </td>
                            <td class="table-cell">
                                {{-- Tombol hanya muncul bila ada bukti untuk dinilai. Sebelumnya
                                     admin bisa menolak atau bahkan memverifikasi pembayaran yang
                                     buktinya belum pernah dikirim pembeli. --}}
                                @if ($pembayaran->menungguPenilaian())
                                    <div x-data="{ tolak: false }" class="flex flex-col items-end gap-2">
                                        <div class="flex items-center gap-2">
                                            <form action="{{ route('admin.pembayaran.verifikasi', $pembayaran) }}" method="POST" onsubmit="return confirm('Verifikasi pembayaran ini?')">
                                                @csrf
                                                <button class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-emerald-700">Verifikasi</button>
                                            </form>

                                            <button type="button" @click="tolak = ! tolak"
                                                    class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-600 ring-1 ring-rose-200 transition hover:bg-rose-100">
                                                Tolak
                                            </button>
                                        </div>

                                        {{-- Alasan diketik di kolom sungguhan, bukan prompt() —
                                             prompt tidak menyisakan apa pun bila terpotong,
                                             dan tidak bisa divalidasi sebelum terkirim. --}}
                                        <form x-show="tolak" x-cloak x-transition
                                              action="{{ route('admin.pembayaran.tolak', $pembayaran) }}" method="POST"
                                              class="w-64 rounded-xl bg-rose-50 p-3 text-left ring-1 ring-rose-200">
                                            @csrf
                                            <label class="block text-[10px] font-bold uppercase tracking-wider text-rose-700">Alasan penolakan</label>
                                            <textarea name="keterangan" rows="2" required minlength="5" maxlength="300"
                                                      placeholder="Contoh: nominal transfer tidak sesuai."
                                                      class="mt-1.5 w-full rounded-lg border-rose-200 text-xs focus:border-rose-400 focus:ring-rose-400"></textarea>
                                            <div class="mt-2 flex justify-end gap-2">
                                                <button type="button" @click="tolak = false"
                                                        class="rounded-lg px-2.5 py-1 text-[11px] font-bold text-slate-500">Batal</button>
                                                <button class="rounded-lg bg-rose-600 px-3 py-1 text-[11px] font-bold text-white transition hover:bg-rose-700">Kirim Penolakan</button>
                                            </div>
                                        </form>
                                    </div>
                                @elseif ($pembayaran->status === 'menunggu' && $pembayaran->metodePembayaran->tipe !== 'cod')
                                    <div class="flex items-center justify-end gap-2">
                                        <span class="text-[11px] font-semibold text-slate-400">Menunggu bukti dari pembeli</span>
                                        <a href="{{ route('admin.pesanan.show', $pembayaran->pesanan) }}" class="rounded-lg bg-brand-50 px-3 py-1.5 text-xs font-bold text-brand-700 transition hover:bg-brand-100">Detail</a>
                                    </div>
                                @else
                                    <a href="{{ route('admin.pesanan.show', $pembayaran->pesanan) }}" class="rounded-lg bg-brand-50 px-3 py-1.5 text-xs font-bold text-brand-700 transition hover:bg-brand-100">Detail</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center">
                                <p class="text-4xl"><x-ikon nama="kartu" kelas="h-9 w-9" /></p>
                                <p class="mt-3 text-sm font-semibold text-slate-500">Tidak ada pembayaran ditemukan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-6">{{ $pembayarans->links() }}</div>
    </div>

</x-layouts.admin>