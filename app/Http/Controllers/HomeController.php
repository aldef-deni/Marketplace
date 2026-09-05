<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Produk;

class HomeController extends Controller
{
    public function index()
    {
        $kategoris = Kategori::where('aktif', true)
            ->withCount(['produks' => fn ($q) => $q->where('status', 'aktif')])
            ->orderBy('nama')
            ->get();

        $produkTerbaru = Produk::aktif()->tersedia()
            ->with('kategori')
            ->latest()
            ->take(8)
            ->get();

        $produkDiskon = Produk::aktif()->tersedia()
            ->whereNotNull('harga_coret')
            ->with('kategori')
            ->latest()
            ->take(4)
            ->get();

        return view('beranda', compact('kategoris', 'produkTerbaru', 'produkDiskon'));
    }
}