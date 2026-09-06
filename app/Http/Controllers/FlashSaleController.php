<?php

namespace App\Http\Controllers;

use App\Models\FlashSale;
use Illuminate\Support\Collection;

/**
 * Halaman flash sale untuk pembeli.
 *
 * Isinya hanya produk yang benar-benar sedang berpromo: kampanyenya berjalan,
 * produknya masih aktif, dan kuota promonya belum habis. Produk yang kuotanya
 * sudah ludes sengaja tidak ditampilkan di sini — harganya sudah kembali normal,
 * jadi memajangnya di halaman promo hanya menjanjikan hal yang tidak ada.
 */
class FlashSaleController extends Controller
{
    public function index()
    {
        $kampanyes = FlashSale::berlangsung()
            ->with(['produks.produk.kategori'])
            ->orderBy('selesai_at')
            ->get()
            ->map(function (FlashSale $kampanye) {
                $kampanye->setRelation('produks', $this->barisTersedia($kampanye));

                return $kampanye;
            })
            ->filter(fn (FlashSale $kampanye) => $kampanye->produks->isNotEmpty())
            ->values();

        // Kampanye terjadwal berikutnya dipakai mengisi halaman saat tidak ada
        // promo berjalan, supaya pengunjung punya alasan untuk kembali.
        $berikutnya = FlashSale::terbit()
            ->where('diikuti', true)
            ->where('mulai_at', '>', now())
            ->orderBy('mulai_at')
            ->first();

        $jumlahProduk = $kampanyes->sum(fn (FlashSale $kampanye) => $kampanye->produks->count());

        return view('flash-sale', compact('kampanyes', 'berikutnya', 'jumlahProduk'));
    }

    private function barisTersedia(FlashSale $kampanye): Collection
    {
        return $kampanye->produks
            ->filter(fn ($baris) => $baris->produk
                && $baris->produk->status === 'aktif'
                && $baris->produk->stok > 0
                && ! $baris->kuotaHabis())
            ->sortByDesc(fn ($baris) => $baris->persen_hemat)
            ->values();
    }
}
