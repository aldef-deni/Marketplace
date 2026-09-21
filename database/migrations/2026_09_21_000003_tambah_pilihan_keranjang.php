<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Centang pilihan pada keranjang.
 *
 * Isi keranjang belum tentu ingin dibeli seluruhnya sekali jalan. Sebelum ini
 * checkout memborong semuanya, sehingga satu-satunya cara membeli sebagian
 * adalah menghapus sisanya lebih dulu — lalu menambahkannya kembali nanti.
 *
 * Pilihan disimpan di basis data, bukan hanya di layar, supaya bertahan saat
 * pembeli berpindah halaman atau membuka keranjangnya dari perangkat lain.
 *
 * Bakunya tercentang: item yang baru dimasukkan memang berniat dibeli, dan
 * keranjang lama tidak boleh tiba-tiba kosong di mata pemiliknya.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('keranjangs', 'dipilih')) {
            return;
        }

        Schema::table('keranjangs', function (Blueprint $table) {
            $table->boolean('dipilih')->default(true)->after('qty');
        });
    }

    public function down(): void
    {
        Schema::table('keranjangs', function (Blueprint $table) {
            $table->dropColumn('dipilih');
        });
    }
};
