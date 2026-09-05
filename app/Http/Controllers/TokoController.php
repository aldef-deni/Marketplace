<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Http\Request;

class TokoController extends Controller
{
    public function index(Request $request)
    {
        $query = Produk::aktif()->with('kategori');

        if ($request->filled('kategori')) {
            $query->whereHas('kategori', fn ($q) => $q->where('slug', $request->kategori));
        }

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(fn ($qq) => $qq
                ->where('nama', 'like', "%{$q}%")
                ->orWhere('deskripsi', 'like', "%{$q}%"));
        }

        if ($request->filled('urutkan')) {
            $query->orderBy(match ($request->urutkan) {
                'termurah' => 'harga',
                'termahal' => 'harga',
                default => 'created_at',
            }, $request->urutkan === 'termahal' ? 'desc' : 'asc');
        } else {
            $query->latest();
        }

        $produks = $query->paginate(12)->withQueryString();
        $kategoris = Kategori::where('aktif', true)->orderBy('nama')->get();

        return view('toko.index', compact('produks', 'kategoris'));
    }

    public function show(string $slug)
    {
        $produk = Produk::aktif()
            ->with('kategori')
            ->where('slug', $slug)
            ->firstOrFail();

        $terkait = Produk::aktif()
            ->where('kategori_id', $produk->kategori_id)
            ->where('id', '!=', $produk->id)
            ->take(4)
            ->get();

        return view('toko.show', compact('produk', 'terkait'));
    }
}