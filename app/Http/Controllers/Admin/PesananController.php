<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Support\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PesananController extends Controller
{
    public function index(Request $request)
    {
        $query = Pesanan::with('user', 'items', 'pembayaran.metodePembayaran');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($qq) use ($q) {
                $qq->where('no_invoice', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$q}%"));
            });
        }

        $pesanans = $query->latest()->paginate(15)->withQueryString();

        $jumlahStatus = Pesanan::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.pesanan.index', compact('pesanans', 'jumlahStatus'));
    }

    public function show(Pesanan $pesanan)
    {
        $pesanan->load('user', 'items', 'alamat', 'pembayaran.metodePembayaran', 'pengiriman');

        return view('admin.pesanan.show', compact('pesanan'));
    }

    public function proses(Pesanan $pesanan)
    {
        abort_if($pesanan->status !== 'menunggu_konfirmasi', 422, 'Status pesanan tidak valid.');

        DB::transaction(function () use ($pesanan) {
            $pesanan->update([
                'status' => 'diproses',
                'diproses_at' => Carbon::now(),
            ]);

            $pesanan->pembayaran?->update([
                'status' => 'dibayar',
                'dibayar_at' => Carbon::now(),
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
        });

        Notifikasi::kePembeli($pesanan, 'pesanan_diproses');

        return back()->with('success', 'Pesanan diterima dan sedang diproses.');
    }

    public function kirim(Request $request, Pesanan $pesanan)
    {
        abort_if($pesanan->status !== 'diproses', 422, 'Pesanan harus diproses terlebih dahulu.');

        $validated = $request->validate([
            'no_resi' => ['required', 'string', 'max:50'],
            'catatan' => ['nullable', 'string', 'max:300'],
        ]);

        DB::transaction(function () use ($pesanan, $validated) {
            $pesanan->update([
                'status' => 'dikirim',
                'dikirim_at' => Carbon::now(),
            ]);

            $pesanan->pengiriman()->updateOrCreate(
                ['pesanan_id' => $pesanan->id],
                [
                    'no_resi' => $validated['no_resi'],
                    'status' => 'dikirim',
                    'dikirim_at' => Carbon::now(),
                    'catatan' => $validated['catatan'] ?? null,
                ],
            );
        });

        Notifikasi::kePembeli($pesanan->fresh('pengiriman'), 'pesanan_dikirim');

        return back()->with('success', 'Pesanan ditandai sebagai dikirim.');
    }

    public function tandaiSelesai(Pesanan $pesanan)
    {
        abort_if(! in_array($pesanan->status, ['dikirim', 'diproses']), 422, 'Status pesanan tidak valid.');

        DB::transaction(function () use ($pesanan) {
            $pesanan->update([
                'status' => 'selesai',
                'selesai_at' => Carbon::now(),
            ]);
            $pesanan->pengiriman?->update([
                'status' => 'diterima',
                'diterima_at' => Carbon::now(),
            ]);
        });

        Notifikasi::kePembeli($pesanan, 'pesanan_selesai');

        return back()->with('success', 'Pesanan ditandai selesai.');
    }

    public function batalkan(Request $request, Pesanan $pesanan)
    {
        abort_if(in_array($pesanan->status, ['selesai', 'dibatalkan']), 422, 'Pesanan tidak dapat dibatalkan.');

        $keterangan = $request->input('keterangan');

        DB::transaction(function () use ($pesanan, $keterangan) {
            foreach ($pesanan->items as $item) {
                if ($item->produk) {
                    $item->produk->increment('stok', $item->qty);
                }
            }
            $pesanan->pembayaran?->update([
                'status' => 'dibatalkan',
                'keterangan' => $keterangan ?: $pesanan->pembayaran->keterangan,
            ]);
            $pesanan->update([
                'status' => 'dibatalkan',
                'catatan' => $keterangan ?: $pesanan->catatan,
            ]);
        });

        Notifikasi::kePembeli($pesanan, 'pesanan_dibatalkan',
            $keterangan ? "Pesanan {$pesanan->no_invoice} dibatalkan: {$keterangan}" : null);

        return back()->with('success', 'Pesanan dibatalkan dan stok dikembalikan.');
    }
}