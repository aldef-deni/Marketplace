<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PembayaranController extends Controller
{
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

        $pembayarans = $query->latest()->paginate(15)->withQueryString();

        $jumlahStatus = Pembayaran::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.pembayaran.index', compact('pembayarans', 'jumlahStatus'));
    }

    public function verifikasi(Pembayaran $pembayaran)
    {
        abort_if($pembayaran->status !== 'menunggu', 422, 'Pembayaran sudah diverifikasi.');

        \DB::transaction(function () use ($pembayaran) {
            $pembayaran->update([
                'status' => 'dibayar',
                'dibayar_at' => Carbon::now(),
            ]);

            $pesanan = $pembayaran->pesanan;

            if (in_array($pesanan->status, ['menunggu_pembayaran', 'menunggu_konfirmasi'])) {
                $pesanan->update([
                    'status' => 'diproses',
                    'diproses_at' => Carbon::now(),
                ]);

                $pesanan->pengiriman()?->create([
                    'kurir' => $pesanan->kurir,
                    'layanan' => $pesanan->layanan_kurir,
                    'ongkir' => $pesanan->ongkir,
                    'status' => 'menunggu',
                ]);
            }
        });

        return back()->with('success', 'Pembayaran diverifikasi. Pesanan diproses.');
    }

    public function tolak(Request $request, Pembayaran $pembayaran)
    {
        abort_if($pembayaran->status !== 'menunggu', 422, 'Pembayaran sudah diverifikasi.');

        $keterangan = $request->input('keterangan', 'Bukti pembayaran tidak sesuai.');

        \DB::transaction(function () use ($pembayaran, $keterangan) {
            $pembayaran->update([
                'status' => 'menunggu',
                'keterangan' => $keterangan,
            ]);

            $pesanan = $pembayaran->pesanan;
            if ($pesanan->status === 'menunggu_konfirmasi') {
                $pesanan->update(['status' => 'menunggu_pembayaran']);
            }
        });

        return back()->with('success', 'Pembayaran ditolak. Pengguna diminta mengunggah ulang bukti.');
    }
}