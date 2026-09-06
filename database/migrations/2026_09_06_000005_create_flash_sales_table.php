<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kampanye flash sale.
 *
 * Kampanye disusun superadmin; admin toko memutuskan ikut atau tidak, lalu
 * memilih produk mana yang disertakan. Keputusan itu disimpan di sini
 * (diikuti/diikuti_at) supaya jejaknya jelas — siapa yang menyetujui dan kapan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flash_sales', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('slug')->unique();
            $table->text('deskripsi')->nullable();

            $table->dateTime('mulai_at');
            $table->dateTime('selesai_at');

            // Saran diskon bagi admin saat memilih produk; harga akhir tetap
            // ditentukan per produk agar bisa disesuaikan margin masing-masing.
            $table->unsignedTinyInteger('diskon_persen')->default(0);

            // Superadmin menerbitkan kampanye; sebelum itu masih berupa draf.
            $table->boolean('aktif')->default(false);

            // Keputusan admin toko.
            $table->boolean('diikuti')->default(false);
            $table->dateTime('diikuti_at')->nullable();
            $table->foreignId('diikuti_oleh')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['aktif', 'mulai_at', 'selesai_at']);
        });

        Schema::create('flash_sale_produk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_id')->constrained('flash_sales')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produks')->cascadeOnDelete();

            $table->decimal('harga_flash', 12, 0);

            // Kuota membatasi berapa unit yang dijual dengan harga flash;
            // sisanya kembali ke harga normal alih-alih kehabisan stok.
            $table->unsignedInteger('kuota')->default(0);
            $table->unsignedInteger('terjual')->default(0);

            $table->timestamps();

            $table->unique(['flash_sale_id', 'produk_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flash_sale_produk');
        Schema::dropIfExists('flash_sales');
    }
};
