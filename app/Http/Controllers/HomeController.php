<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Toko;
use App\Models\FlashSale;
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
            ->whereHas('toko', fn ($q) => $q->tampil())
            ->with('kategori', 'toko')
            ->latest()
            ->take(8)
            ->get();

        // Toko dengan katalog paling berisi ditampilkan lebih dulu: lapak
        // kosong di beranda memberi kesan pasar yang sepi.
        $tokos = Toko::tampil()
            ->withCount(['produks' => fn ($q) => $q->where('status', 'aktif')])
            ->orderByDesc('produks_count')
            ->take(8)
            ->get();

        // Kampanye yang sudah terbit dan berada dalam rentang waktunya.
        // Keikutsertaan toko disaring di tingkat produk, bukan di sini: satu
        // kampanye kini dapat diikuti sebagian lapak saja.
        $flashSale = FlashSale::berlangsung()
            ->with(['produks.produk.kategori', 'produks.produk.toko'])
            ->orderBy('selesai_at')
            ->first();

        return view('beranda', compact('kategoris', 'produkTerbaru', 'flashSale', 'tokos'));
    }
}