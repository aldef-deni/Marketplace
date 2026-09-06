<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Pemilik toko belum punya dashboard sendiri selama pesanan masih
        // menyatu lintas toko; daftar produknya adalah halaman kerja yang
        // bermakna, dan dashboard platform akan menyambutnya dengan 403.
        if ($user->isPengelola()) {
            return redirect()->to($user->rutaPanel());
        }

        $stats = [
            'total_pesanan' => $user->pesanans()->count(),
            'menunggu_pembayaran' => $user->pesanans()->where('status', 'menunggu_pembayaran')->count(),
            'dalam_perjalanan' => $user->pesanans()->where('status', 'dikirim')->count(),
            'selesai' => $user->pesanans()->where('status', 'selesai')->count(),
        ];

        $pesananTerakhir = $user->pesanans()
            ->with('items', 'pembayaran.metodePembayaran', 'pengiriman')
            ->latest()
            ->take(5)
            ->get();

        $aktivitasBulanIni = $user->pesanans()
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->count();

        $notifikasiTerbaru = $user->notifications()->take(5)->get();

        return view('dashboard', compact(
            'user', 'stats', 'pesananTerakhir', 'aktivitasBulanIni', 'notifikasiTerbaru',
        ));
    }
}