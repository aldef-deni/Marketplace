<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $pendapatan = Pesanan::where('status', '!=', 'dibatalkan')
            ->where('status', '!=', 'menunggu_pembayaran')
            ->sum('total');

        $pendapatanBulanIni = Pesanan::where('status', '!=', 'dibatalkan')
            ->where('status', '!=', 'menunggu_pembayaran')
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->sum('total');

        $stats = [
            'pendapatan' => $pendapatan,
            'pendapatan_bulan_ini' => $pendapatanBulanIni,
            'pesanan' => Pesanan::count(),
            'pesanan_baru' => Pesanan::whereIn('status', ['menunggu_pembayaran', 'menunggu_konfirmasi'])->count(),
            'dikirim' => Pesanan::where('status', 'dikirim')->count(),
            'produk' => Produk::count(),
            'stok_menipis' => Produk::where('stok', '<=', 5)->count(),
            'pengguna' => User::where('role', 'pengguna')->count(),
            'menunggu_verifikasi' => Pembayaran::where('status', 'menunggu')
                ->whereHas('pesanan', fn ($q) => $q->where('status', 'menunggu_konfirmasi'))
                ->count(),
            'perlu_dikirim' => Pesanan::where('status', 'diproses')->count(),
        ];

        /*
        | Antrean pekerjaan: hal-hal yang menunggu tindakan admin sekarang.
        | Dashboard yang hanya menampilkan angka membuat admin harus menebak
        | apa yang harus dikerjakan berikutnya.
        */
        $perluTindakan = [
            [
                'label' => 'Bukti bayar menunggu verifikasi',
                'jumlah' => $stats['menunggu_verifikasi'],
                'ikon' => 'kartu',
                'nada' => 'bg-accent-100 text-accent-700',
                'url' => route('admin.pembayaran.index', ['status' => 'menunggu']),
            ],
            [
                'label' => 'Pesanan siap dikirim',
                'jumlah' => $stats['perlu_dikirim'],
                'ikon' => 'truk',
                'nada' => 'bg-brand-100 text-brand-700',
                'url' => route('admin.pesanan.index', ['status' => 'diproses']),
            ],
            [
                'label' => 'Produk dengan stok menipis',
                'jumlah' => $stats['stok_menipis'],
                'ikon' => 'peringatan',
                'nada' => 'bg-rose-100 text-rose-700',
                'url' => route('admin.produk.index'),
            ],
        ];

        $pesananTerbaru = Pesanan::with('user')->latest()->take(8)->get();

        $penjualan7Hari = collect(range(6, 0))->map(function ($i) {
            $hari = Carbon::now()->subDays($i);

            return [
                'label' => $hari->translatedFormat('D'),
                'total' => (int) Pesanan::where('status', '!=', 'dibatalkan')
                    ->whereDate('created_at', $hari->toDateString())
                    ->sum('total'),
            ];
        });

        $produkLaris = \DB::table('pesanan_items')
            ->selectRaw('pesanan_items.nama_produk, SUM(pesanan_items.qty) as terjual, SUM(pesanan_items.subtotal) as omzet')
            ->join('pesanans', 'pesanans.id', '=', 'pesanan_items.pesanan_id')
            ->whereNotIn('pesanans.status', ['dibatalkan', 'menunggu_pembayaran'])
            ->groupBy('pesanan_items.nama_produk')
            ->orderByDesc('terjual')
            ->take(5)
            ->get();

        $notifikasiTerbaru = auth()->user()->notifications()->take(5)->get();

        return view('admin.dashboard', compact(
            'stats', 'pesananTerbaru', 'penjualan7Hari', 'produkLaris',
            'perluTindakan', 'notifikasiTerbaru',
        ));
    }
}