<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mencatat jatah potongan yang dipakai tiap baris pesanan.
 *
 * Checkout mencadangkan dua hal: stok produk dan kuota flash sale/promo. Yang
 * pertama sudah dikembalikan saat pesanan batal, yang kedua tidak pernah —
 * sehingga pesanan batal tetap menghabiskan jatah, dan pembeli berikutnya
 * kehilangan harga promonya tanpa ada satu pun barang yang benar-benar terjual.
 *
 * Mengembalikannya tidak bisa ditebak ulang saat pembatalan. Jatah yang
 * terpakai belum tentu sebanyak kuantitas yang dibeli — bila sisa kuotanya
 * tinggal sedikit, hanya sisa itu yang terpakai. Kampanyenya pun bisa sudah
 * berakhir atau berganti ketika pesanannya dibatalkan. Karena itu yang
 * dicadangkan dicatat di sini saat mencadangkan, bukan dihitung ulang nanti.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pesanan_items', 'kuota_terpakai')) {
            return;
        }

        Schema::table('pesanan_items', function (Blueprint $table) {
            // Baris kampanye yang jatahnya terpakai — FlashSaleProduk atau
            // PromoProduk. Sengaja relasi morph agar tidak perlu dua pasang
            // kolom untuk hal yang sama.
            $table->nullableMorphs('potongan');

            // Berapa jatah yang benar-benar terambil. Nol berarti tidak ada
            // yang perlu dikembalikan.
            $table->unsignedInteger('kuota_terpakai')->default(0)->after('potongan_id');
        });
    }

    public function down(): void
    {
        Schema::table('pesanan_items', function (Blueprint $table) {
            $table->dropMorphs('potongan');
            $table->dropColumn('kuota_terpakai');
        });
    }
};
