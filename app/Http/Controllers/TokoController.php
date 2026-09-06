<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Produk;
use App\Models\Toko;
use Illuminate\Http\Request;

/**
 * Etalase toko: daftar lapak, lalu halaman tiap lapak beserta produknya.
 */
class TokoController extends Controller
{
    public function index(Request $request)
    {
        $query = Toko::tampil()
            ->withCount(['produks' => fn ($q) => $q->where('status', 'aktif')]);

        if ($request->filled('q')) {
            $kata = trim($request->q);
            $query->where(fn ($q) => $q
                ->where('nama', 'like', "%{$kata}%")
                ->orWhere('deskripsi', 'like', "%{$kata}%")
                ->orWhere('kota', 'like', "%{$kata}%"));
        }

        if ($request->filled('kota')) {
            $query->where('kota', $request->kota);
        }

        $query->orderBy(match ($request->urutkan) {
            'nama' => 'nama',
            'terbaru' => 'created_at',
            default => 'produks_count',
        }, $request->urutkan === 'nama' ? 'asc' : 'desc');

        $tokos = $query->paginate(12)->withQueryString();

        // Daftar kota diambil dari toko yang benar-benar tampil, supaya
        // penyaringnya tidak pernah menawarkan pilihan yang hasilnya kosong.
        $kotas = Toko::tampil()
            ->whereNotNull('kota')
            ->distinct()
            ->orderBy('kota')
            ->pluck('kota');

        return view('toko.index', compact('tokos', 'kotas'));
    }

    public function show(Request $request, string $slug)
    {
        $toko = Toko::tampil()
            ->withCount(['produks' => fn ($q) => $q->where('status', 'aktif')])
            ->where('slug', $slug)
            ->firstOrFail();

        $query = $toko->produks()->aktif()->with('kategori');

        if ($request->filled('kategori')) {
            $query->whereHas('kategori', fn ($q) => $q->where('slug', $request->kategori));
        }

        if ($request->filled('q')) {
            $kata = trim($request->q);
            $query->where('nama', 'like', "%{$kata}%");
        }

        $query->orderBy(match ($request->urutkan) {
            'termurah', 'termahal' => 'harga',
            default => 'created_at',
        }, $request->urutkan === 'termahal' ? 'desc' : 'asc');

        $produks = $query->paginate(18)->withQueryString();

        // Hanya kategori yang benar-benar dijual toko ini yang ditawarkan.
        $kategoris = Kategori::whereIn('id', Produk::where('toko_id', $toko->id)
            ->where('status', 'aktif')
            ->distinct()
            ->pluck('kategori_id'))
            ->orderBy('nama')
            ->get();

        return view('toko.show', compact('toko', 'produks', 'kategoris'));
    }
}
