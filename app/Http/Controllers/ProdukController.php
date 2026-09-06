<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Produk;
use App\Models\Toko;
use Illuminate\Http\Request;

/**
 * Katalog produk lintas toko.
 *
 * Hanya produk milik toko aktif yang tampil; produk toko yang ditangguhkan
 * ikut hilang dari katalog tanpa perlu menonaktifkan produknya satu per satu.
 */
class ProdukController extends Controller
{
    public function index(Request $request)
    {
        $query = Produk::aktif()
            ->with('kategori', 'toko')
            ->whereHas('toko', fn ($q) => $q->tampil());

        if ($request->filled('kategori')) {
            $query->whereHas('kategori', fn ($q) => $q->where('slug', $request->kategori));
        }

        if ($request->filled('toko')) {
            $query->whereHas('toko', fn ($q) => $q->where('slug', $request->toko));
        }

        if ($request->filled('q')) {
            $kata = trim($request->q);
            $query->where(fn ($q) => $q
                ->where('nama', 'like', "%{$kata}%")
                ->orWhere('deskripsi', 'like', "%{$kata}%"));
        }

        if ($request->filled('urutkan')) {
            $query->orderBy(match ($request->urutkan) {
                'termurah', 'termahal' => 'harga',
                default => 'created_at',
            }, $request->urutkan === 'termahal' ? 'desc' : 'asc');
        } else {
            $query->latest();
        }

        $produks = $query->paginate(12)->withQueryString();
        $kategoris = Kategori::where('aktif', true)->orderBy('nama')->get();
        $tokoTerpilih = $request->filled('toko')
            ? Toko::tampil()->where('slug', $request->toko)->first()
            : null;

        return view('produk.index', compact('produks', 'kategoris', 'tokoTerpilih'));
    }

    public function show(string $slug)
    {
        $produk = Produk::aktif()
            ->with('kategori', 'toko', 'gambars')
            ->whereHas('toko', fn ($q) => $q->tampil())
            ->where('slug', $slug)
            ->firstOrFail();

        // Produk terkait diambil dari toko yang sama lebih dulu: pembeli yang
        // sudah membuka satu lapak biasanya ingin melihat lapak itu juga.
        $terkait = Produk::aktif()
            ->where('toko_id', $produk->toko_id)
            ->where('id', '!=', $produk->id)
            ->with('kategori')
            ->take(6)
            ->get();

        if ($terkait->count() < 6) {
            $terkait = $terkait->concat(
                Produk::aktif()
                    ->whereHas('toko', fn ($q) => $q->tampil())
                    ->where('kategori_id', $produk->kategori_id)
                    ->whereNotIn('id', $terkait->pluck('id')->push($produk->id))
                    ->with('kategori')
                    ->take(6 - $terkait->count())
                    ->get()
            );
        }

        return view('produk.show', compact('produk', 'terkait'));
    }
}
