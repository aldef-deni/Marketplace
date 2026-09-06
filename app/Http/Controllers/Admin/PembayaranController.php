<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Support\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PembayaranController extends Controller
{
    /**
     * Baris per halaman. Daftar ini memuat seluruh transaksi lintas toko, jadi
     * tanpa batas yang tegas satu halaman bisa memuat ribuan baris sekaligus.
     */
    private const PER_HALAMAN = 20;

    public function index(Request $request)
    {
        $query = Pembayaran::with('pesanan.user', 'metodePembayaran');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->whereHas('pesanan', fn ($p) => $p->where('no_invoice', 'like', "%{$q}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%")));
        }

        $pembayarans = $query->latest()->paginate(self::PER_HALAMAN)->withQueryString();

        $jumlahStatus = Pembayaran::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.pembayaran.index', compact('pembayarans', 'jumlahStatus'));
    }

    public function verifikasi(Pembayaran $pembayaran)
    {
        abort_if(! in_array($pembayaran->status, ['menunggu', 'ditolak']), 422, 'Pembayaran sudah diverifikasi.');

        // Tanpa bukti tidak ada yang bisa dinilai; memverifikasinya berarti
        // menyatakan uang sudah diterima padahal tidak ada dasarnya.
        if (blank($pembayaran->bukti) && $pembayaran->metodePembayaran?->tipe !== 'cod') {
            return back()->with('error', 'Pembeli belum mengunggah bukti pembayaran.');
        }

        \DB::transaction(function () use ($pembayaran) {
            $pembayaran->update([
                'status' => 'dibayar',
                'dibayar_at' => Carbon::now(),
                // Alasan penolakan sebelumnya tidak lagi berlaku.
                'keterangan' => null,
            ]);

            $pesanan = $pembayaran->pesanan;

            if (in_array($pesanan->status, ['menunggu_pembayaran', 'menunggu_konfirmasi'])) {
                $pesanan->update([
                    'status' => 'diproses',
                    'diproses_at' => Carbon::now(),
                ]);

                $pesanan->pengiriman()->firstOrCreate(
                    ['pesanan_id' => $pesanan->id],
                    [
                        'kurir' => $pesanan->kurir,
                        'layanan' => $pesanan->layanan_kurir,
                        'ongkir' => $pesanan->ongkir,
                        'status' => 'menunggu',
                    ],
                );
            }
        });

        Notifikasi::kePembeli($pembayaran->pesanan, 'pembayaran_diverifikasi');

        return back()->with('success', 'Pembayaran diverifikasi. Pesanan diproses.');
    }

    public function tolak(Request $request, Pembayaran $pembayaran)
    {
        abort_if($pembayaran->status !== 'menunggu', 422, 'Pembayaran sudah diverifikasi.');

        if (blank($pembayaran->bukti)) {
            return back()->with('error', 'Belum ada bukti yang bisa ditolak. Pembeli belum mengunggah apa pun.');
        }

        $validated = $request->validate([
            'keterangan' => ['required', 'string', 'min:5', 'max:300'],
        ], [
            'keterangan.required' => 'Alasan penolakan wajib diisi agar pembeli tahu apa yang harus diperbaiki.',
            'keterangan.min' => 'Alasan penolakan terlalu singkat.',
        ]);

        $keterangan = $validated['keterangan'];

        \DB::transaction(function () use ($pembayaran, $keterangan) {
            // Penolakan sebelumnya menulis ulang status 'menunggu' — nilai yang
            // sudah dipakainya — sehingga tidak ada yang berubah di layar dan
            // admin mengira tindakannya gagal.
            $pembayaran->update([
                'status' => 'ditolak',
                'keterangan' => $keterangan,
            ]);

            $pesanan = $pembayaran->pesanan;
            if ($pesanan->status === 'menunggu_konfirmasi') {
                $pesanan->update(['status' => 'menunggu_pembayaran']);
            }
        });

        Notifikasi::kePembeli($pembayaran->pesanan, 'pembayaran_ditolak',
            "Bukti pembayaran {$pembayaran->pesanan->no_invoice} ditolak: {$keterangan}");

        return back()->with('success', 'Pembayaran ditolak. Pengguna diminta mengunggah ulang bukti.');
    }
}