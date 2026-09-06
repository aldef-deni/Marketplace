<?php

namespace App\Http\Controllers;

use App\Models\FlashSale;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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
    /**
     * Kartu per halaman. Kelipatan empat agar baris terakhir tidak menyisakan
     * kartu tunggal yang menggantung pada kisi empat kolom.
     */
    private const PER_HALAMAN = 12;

    public function index(Request $request)
    {
        $kampanyes = FlashSale::berlangsung()
            ->with(['produks.produk.kategori', 'produks.produk.toko'])
            ->orderBy('selesai_at')
            ->get();

        /*
        | Seluruh baris promo dari semua kampanye berjalan digabung jadi satu
        | daftar, bukan dipisah per kampanye. Halaman ini menjual harga, dan
        | pemisahan per kampanye membuat satu halaman berisi beberapa potongan
        | pendek yang masing-masing tidak penuh — persis yang membuat paginasi
        | dua belas kartu mustahil ditepati.
        */
        $baris = $kampanyes
            ->flatMap(fn (FlashSale $kampanye) => $this->barisTersedia($kampanye))
            ->sortByDesc(fn ($item) => $item->persen_hemat)
            ->values();

        $produks = $this->halamani($baris, $request);

        // Kampanye terjadwal berikutnya dipakai mengisi halaman saat tidak ada
        // promo berjalan, supaya pengunjung punya alasan untuk kembali.
        $berikutnya = FlashSale::terbit()
            ->whereHas('tokos')
            ->where('mulai_at', '>', now())
            ->orderBy('mulai_at')
            ->first();

        return view('flash-sale', [
            'produks' => $produks,
            'jumlahProduk' => $baris->count(),
            'jumlahKampanye' => $kampanyes->count(),
            // Kampanye yang paling cepat berakhir; itulah tenggat yang paling
            // mendesak bagi pembeli, jadi itu yang dihitung mundur.
            'kampanyeTerdekat' => $kampanyes->first(fn (FlashSale $k) => $this->barisTersedia($k)->isNotEmpty()),
            'berikutnya' => $berikutnya,
        ]);
    }

    /**
     * Ubah koleksi menjadi paginator supaya tautan halamannya lengkap.
     *
     * Baris promo lahir dari penyaringan di PHP — kuota dan status toko tidak
     * dapat disaring seluruhnya lewat kueri — sehingga paginasinya dibangun
     * dari koleksi, bukan dari kueri basis data.
     */
    private function halamani(Collection $baris, Request $request): LengthAwarePaginator
    {
        $halaman = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $baris->forPage($halaman, self::PER_HALAMAN)->values(),
            $baris->count(),
            self::PER_HALAMAN,
            $halaman,
            ['path' => $request->url(), 'query' => $request->query()],
        );
    }

    private function barisTersedia(FlashSale $kampanye): Collection
    {
        return $kampanye->produks
            ->filter(fn ($baris) => $baris->produk
                && $baris->produk->status === 'aktif'
                && $baris->produk->stok > 0
                // Toko yang ditangguhkan ikut menarik produknya dari promo;
                // tanpa ini lapak bermasalah tetap terpajang di halaman utama.
                && $baris->produk->toko?->aktif()
                && ! $baris->kuotaHabis())
            ->values();
    }
}
