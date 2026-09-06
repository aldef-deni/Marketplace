<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengiriman;
use Illuminate\Http\Request;

class PengirimanController extends Controller
{
    /**
     * Baris per halaman. Daftar ini memuat seluruh transaksi lintas toko, jadi
     * tanpa batas yang tegas satu halaman bisa memuat ribuan baris sekaligus.
     */
    private const PER_HALAMAN = 20;

    public function index(Request $request)
    {
        $query = Pengiriman::with('pesanan.user');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(fn ($qq) => $qq
                ->where('no_resi', 'like', "%{$q}%")
                ->orWhereHas('pesanan', fn ($p) => $p->where('no_invoice', 'like', "%{$q}%")));
        }

        $pengirimans = $query->latest()->paginate(self::PER_HALAMAN)->withQueryString();

        return view('admin.pengiriman.index', compact('pengirimans'));
    }
}