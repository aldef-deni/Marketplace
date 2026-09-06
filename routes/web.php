<?php

use App\Http\Controllers\AlamatController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FlashSaleController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KeranjangController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\PesananController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TokoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman Publik
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('beranda');

Route::get('/flash-sale', [FlashSaleController::class, 'index'])->name('flash-sale.index');

/* Katalog produk lintas toko. */
Route::get('/produk', [ProdukController::class, 'index'])->name('produk.index');
Route::get('/produk/{slug}', [ProdukController::class, 'show'])->name('produk.show');

/* Etalase toko: daftar lapak, lalu halaman tiap lapak. */
Route::get('/toko', [TokoController::class, 'index'])->name('toko.index');
Route::get('/toko/{slug}', [TokoController::class, 'show'])->name('toko.show');

/*
|--------------------------------------------------------------------------
| Area Terautentikasi (Pengguna)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('keranjang')->name('keranjang.')->group(function () {
        Route::get('/', [KeranjangController::class, 'index'])->name('index');
        Route::post('/tambah/{produk}', [KeranjangController::class, 'tambah'])->name('tambah');
        Route::patch('/{item}/qty', [KeranjangController::class, 'updateQty'])->name('updateQty');
        Route::delete('/{item}', [KeranjangController::class, 'hapus'])->name('hapus');
        Route::delete('/', [KeranjangController::class, 'kosongkan'])->name('kosongkan');
    });

    Route::prefix('alamat')->name('alamat.')->group(function () {
        Route::get('/', [AlamatController::class, 'index'])->name('index');
        Route::get('/tambah', [AlamatController::class, 'create'])->name('create');
        Route::post('/', [AlamatController::class, 'store'])->name('store');
        Route::get('/{alamat}/edit', [AlamatController::class, 'edit'])->name('edit');
        Route::patch('/{alamat}', [AlamatController::class, 'update'])->name('update');
        Route::patch('/{alamat}/default', [AlamatController::class, 'jadikanDefault'])->name('default');
        Route::delete('/{alamat}', [AlamatController::class, 'destroy'])->name('destroy');
    });

    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::prefix('pesanan')->name('pesanan.')->group(function () {
        Route::get('/', [PesananController::class, 'index'])->name('index');
        Route::get('/{noInvoice}', [PesananController::class, 'show'])->name('show');
        Route::post('/{pesanan}/bayar', [PesananController::class, 'uploadBukti'])->name('bayar');
        Route::post('/{pesanan}/terima', [PesananController::class, 'konfirmasiTerima'])->name('terima');
        Route::post('/{pesanan}/batalkan', [PesananController::class, 'batalkan'])->name('batalkan');
        Route::get('/{noInvoice}/cetak', [PesananController::class, 'cetak'])->name('cetak');
    });

    /*
    | Notifikasi — dipakai semua peran, termasuk admin dan superadmin.
    */
    Route::prefix('notifikasi')->name('notifikasi.')->group(function () {
        Route::get('/', [NotifikasiController::class, 'index'])->name('index');
        Route::get('/data', [NotifikasiController::class, 'data'])->name('data');
        Route::get('/{id}/baca', [NotifikasiController::class, 'baca'])->name('baca');
        Route::post('/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('baca-semua');
        Route::delete('/terbaca', [NotifikasiController::class, 'hapusTerbaca'])->name('hapus-terbaca');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
    Route::delete('/profile/avatar', [ProfileController::class, 'hapusAvatar'])->name('profile.avatar.hapus');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Area Admin (Superadmin & Admin)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'role:superadmin,admin,penjual'])->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\DashboardController::class, 'index'])
        ->middleware('role:superadmin,admin')->name('dashboard');

    Route::prefix('produk')->name('produk.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\ProdukController::class, 'index'])->name('index');
        Route::get('/tambah', [App\Http\Controllers\Admin\ProdukController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\ProdukController::class, 'store'])->name('store');
        Route::get('/{produk}/edit', [App\Http\Controllers\Admin\ProdukController::class, 'edit'])->name('edit');
        Route::patch('/{produk}', [App\Http\Controllers\Admin\ProdukController::class, 'update'])->name('update');
        Route::patch('/{produk}/status', [App\Http\Controllers\Admin\ProdukController::class, 'toggleStatus'])->name('status');
        Route::delete('/{produk}', [App\Http\Controllers\Admin\ProdukController::class, 'destroy'])->name('destroy');
    });

    /*
    | Toko. Pengelola melihat semuanya dan menyetujui pendaftaran baru; penjual
    | hanya menyentuh tokonya sendiri — pembatasnya di controller, bukan di rute,
    | supaya satu kendali tidak bisa terlewat saat rutenya berubah.
    */
    Route::prefix('toko')->name('toko.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\TokoController::class, 'index'])->name('index');
        Route::get('/tambah', [App\Http\Controllers\Admin\TokoController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\TokoController::class, 'store'])->name('store');
        Route::get('/{toko}/edit', [App\Http\Controllers\Admin\TokoController::class, 'edit'])->name('edit');
        Route::patch('/{toko}', [App\Http\Controllers\Admin\TokoController::class, 'update'])->name('update');
        Route::patch('/{toko}/status', [App\Http\Controllers\Admin\TokoController::class, 'toggleStatus'])->name('status');
        Route::delete('/{toko}', [App\Http\Controllers\Admin\TokoController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('kategori')->name('kategori.')->middleware('role:superadmin,admin')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\KategoriController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\Admin\KategoriController::class, 'store'])->name('store');
        Route::patch('/{kategori}', [App\Http\Controllers\Admin\KategoriController::class, 'update'])->name('update');
        Route::patch('/{kategori}/status', [App\Http\Controllers\Admin\KategoriController::class, 'toggle'])->name('status');
        Route::delete('/{kategori}', [App\Http\Controllers\Admin\KategoriController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('pesanan')->name('pesanan.')->middleware('role:superadmin,admin')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\PesananController::class, 'index'])->name('index');
        Route::get('/{pesanan}', [App\Http\Controllers\Admin\PesananController::class, 'show'])->name('show');
        Route::post('/{pesanan}/proses', [App\Http\Controllers\Admin\PesananController::class, 'proses'])->name('proses');
        Route::post('/{pesanan}/kirim', [App\Http\Controllers\Admin\PesananController::class, 'kirim'])->name('kirim');
        Route::post('/{pesanan}/selesai', [App\Http\Controllers\Admin\PesananController::class, 'tandaiSelesai'])->name('selesai');
        Route::post('/{pesanan}/batalkan', [App\Http\Controllers\Admin\PesananController::class, 'batalkan'])->name('batalkan');
    });

    Route::prefix('pembayaran')->name('pembayaran.')->middleware('role:superadmin,admin')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\PembayaranController::class, 'index'])->name('index');
        Route::post('/{pembayaran}/verifikasi', [App\Http\Controllers\Admin\PembayaranController::class, 'verifikasi'])->name('verifikasi');
        Route::post('/{pembayaran}/tolak', [App\Http\Controllers\Admin\PembayaranController::class, 'tolak'])->name('tolak');
    });

    Route::prefix('pengiriman')->name('pengiriman.')->middleware('role:superadmin,admin')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\PengirimanController::class, 'index'])->name('index');
    });

    /*
    | Flash sale. Kampanye disusun superadmin; admin memutuskan keikutsertaan
    | toko dan memilih produk yang disertakan.
    */
    Route::prefix('flash-sale')->name('flash-sale.')->middleware('role:superadmin,admin')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\FlashSalePartisipasiController::class, 'index'])->name('index');
        Route::get('/{flashSale}/kelola', [App\Http\Controllers\Admin\FlashSalePartisipasiController::class, 'show'])->name('kelola');
        Route::post('/{flashSale}/ikut', [App\Http\Controllers\Admin\FlashSalePartisipasiController::class, 'toggleIkut'])->name('ikut');
        Route::post('/{flashSale}/produk/{produk}', [App\Http\Controllers\Admin\FlashSalePartisipasiController::class, 'simpanBaris'])->name('produk');

        Route::middleware('role:superadmin')->prefix('kampanye')->name('kampanye.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\FlashSaleController::class, 'index'])->name('index');
            Route::get('/tambah', [App\Http\Controllers\Admin\FlashSaleController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\FlashSaleController::class, 'store'])->name('store');
            Route::get('/{flashSale}/edit', [App\Http\Controllers\Admin\FlashSaleController::class, 'edit'])->name('edit');
            Route::patch('/{flashSale}', [App\Http\Controllers\Admin\FlashSaleController::class, 'update'])->name('update');
            Route::patch('/{flashSale}/terbit', [App\Http\Controllers\Admin\FlashSaleController::class, 'toggleAktif'])->name('terbit');
            Route::delete('/{flashSale}', [App\Http\Controllers\Admin\FlashSaleController::class, 'destroy'])->name('destroy');
        });
    });

    /*
    | Laporan transaksi terbuka bagi admin dan superadmin; laporan toko —
    | kondisi katalog dan kinerja produk — khusus superadmin.
    */
    Route::prefix('laporan')->name('laporan.')->middleware('role:superadmin,admin')->group(function () {
        Route::get('/transaksi', [App\Http\Controllers\Admin\LaporanController::class, 'transaksi'])->name('transaksi');
        Route::get('/transaksi/pdf', [App\Http\Controllers\Admin\LaporanController::class, 'transaksiPdf'])->name('transaksi.pdf');
        Route::get('/transaksi/excel', [App\Http\Controllers\Admin\LaporanController::class, 'transaksiExcel'])->name('transaksi.excel');

        Route::middleware('role:superadmin')->group(function () {
            Route::get('/toko', [App\Http\Controllers\Admin\LaporanController::class, 'toko'])->name('toko');
            Route::get('/toko/pdf', [App\Http\Controllers\Admin\LaporanController::class, 'tokoPdf'])->name('toko.pdf');
            Route::get('/toko/excel', [App\Http\Controllers\Admin\LaporanController::class, 'tokoExcel'])->name('toko.excel');
        });
    });

    Route::prefix('pengguna')->name('pengguna.')->middleware('role:superadmin')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\PenggunaController::class, 'index'])->name('index');
        Route::patch('/{user}/role', [App\Http\Controllers\Admin\PenggunaController::class, 'updateRole'])->name('role');
        Route::delete('/{user}', [App\Http\Controllers\Admin\PenggunaController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('metode-pembayaran')->name('metode-pembayaran.')->middleware('role:superadmin')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\MetodePembayaranController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\Admin\MetodePembayaranController::class, 'store'])->name('store');
        Route::patch('/{metode}', [App\Http\Controllers\Admin\MetodePembayaranController::class, 'update'])->name('update');
        Route::patch('/{metode}/status', [App\Http\Controllers\Admin\MetodePembayaranController::class, 'toggle'])->name('status');
        Route::delete('/{metode}', [App\Http\Controllers\Admin\MetodePembayaranController::class, 'destroy'])->name('destroy');
    });
});

require __DIR__.'/auth.php';