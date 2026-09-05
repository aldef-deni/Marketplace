<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengiriman;
use Illuminate\Http\Request;

class PengirimanController extends Controller
{
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

        $pengirimans = $query->latest()->paginate(15)->withQueryString();

        return view('admin.pengiriman.index', compact('pengirimans'));
    }
}