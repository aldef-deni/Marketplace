<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Memberi pembayaran keadaan "ditolak" yang berdiri sendiri.
 *
 * Sebelumnya penolakan menulis kembali status 'menunggu' — nilai yang sudah
 * dipakainya — sehingga tidak ada yang berubah di layar. Admin mengira
 * tindakannya gagal, dan pembeli tidak pernah tahu buktinya ditolak.
 *
 * Kolomnya diubah dari enum menjadi string agar penambahan keadaan berikutnya
 * tidak lagi menuntut perubahan skema, sekaligus menghindari perbedaan
 * penanganan enum antara MySQL dan SQLite yang dipakai pengujian.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->string('status', 20)->default('menunggu')->change();
        });
    }

    public function down(): void
    {
        // Baris yang terlanjur ditolak dikembalikan ke keadaan menunggu, sebab
        // enum lama tidak mengenal nilai tersebut dan akan menolaknya.
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->string('status', 20)->default('menunggu')->change();
        });

        \DB::table('pembayarans')->where('status', 'ditolak')->update(['status' => 'menunggu']);

        Schema::table('pembayarans', function (Blueprint $table) {
            $table->enum('status', ['menunggu', 'dibayar', 'dibatalkan'])->default('menunggu')->change();
        });
    }
};
