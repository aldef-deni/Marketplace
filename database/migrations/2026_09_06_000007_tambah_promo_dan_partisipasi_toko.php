<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Promo, serta potongan bernominal dan keikutsertaan per toko.
 *
 * Tiga perubahan yang saling bergantung sehingga dijadikan satu migrasi:
 *
 * 1. Potongan tidak lagi selalu persentase. Kolom diskon_persen diganti pasangan
 *    tipe_diskon + nilai_diskon agar rupiah dan persen memakai jalur yang sama.
 * 2. Keikutsertaan flash sale pindah dari satu kolom boolean di kampanye ke tabel
 *    tersendiri. Kolom tunggal itu warisan masa satu toko; sejak katalog dimiliki
 *    banyak lapak, "toko ikut" tidak lagi punya jawaban tunggal.
 * 3. Promo ditambahkan, dengan bentuk yang sama: disusun superadmin lalu diikuti
 *    toko, atau dibuat sendiri oleh toko untuk produknya.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->ubahFlashSale();
        $this->buatPartisipasiFlashSale();
        $this->buatPromo();
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_produks');
        Schema::dropIfExists('promo_tokos');
        Schema::dropIfExists('promos');
        Schema::dropIfExists('flash_sale_tokos');

        Schema::table('flash_sales', function (Blueprint $table) {
            $table->integer('diskon_persen')->default(0);
            $table->boolean('diikuti')->default(false);
            $table->timestamp('diikuti_at')->nullable();
            $table->unsignedBigInteger('diikuti_oleh')->nullable();
            $table->dropColumn(['tipe_diskon', 'nilai_diskon']);
        });
    }

    private function ubahFlashSale(): void
    {
        if (Schema::hasColumn('flash_sales', 'tipe_diskon')) {
            return;
        }

        Schema::table('flash_sales', function (Blueprint $table) {
            $table->string('tipe_diskon', 10)->default('persen')->after('selesai_at');
            $table->decimal('nilai_diskon', 12, 0)->default(0)->after('tipe_diskon');
        });

        DB::table('flash_sales')->update([
            'tipe_diskon' => 'persen',
            'nilai_diskon' => DB::raw('diskon_persen'),
        ]);
    }

    private function buatPartisipasiFlashSale(): void
    {
        if (Schema::hasTable('flash_sale_tokos')) {
            return;
        }

        Schema::create('flash_sale_tokos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_id')->constrained('flash_sales')->cascadeOnDelete();
            $table->foreignId('toko_id')->constrained('tokos')->cascadeOnDelete();
            $table->timestamp('diikuti_at')->nullable();
            $table->foreignId('diikuti_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Keberadaan baris berarti toko ikut serta; berhenti ikut berarti
            // barisnya dihapus. Tanpa kolom boolean tambahan, keadaannya tidak
            // mungkin saling bertentangan.
            $table->unique(['flash_sale_id', 'toko_id']);
        });

        $this->pindahkanKeikutsertaanLama();

        // Kunci asingnya harus dilepas bersama kolomnya. MySQL menolak menghapus
        // kolom yang masih dipakai constraint, dan SQLite menyisakan definisi
        // kunci yang menggantung — dropConstrainedForeignId menangani keduanya.
        Schema::table('flash_sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('diikuti_oleh');
        });

        Schema::table('flash_sales', function (Blueprint $table) {
            $table->dropColumn(['diskon_persen', 'diikuti', 'diikuti_at']);
        });
    }

    /**
     * Kampanye yang sebelumnya ditandai diikuti dipetakan ke seluruh toko yang
     * benar-benar menyertakan produk di dalamnya, supaya promo yang sedang
     * berjalan tidak mendadak berhenti begitu migrasi dijalankan.
     */
    private function pindahkanKeikutsertaanLama(): void
    {
        $kampanyes = DB::table('flash_sales')->where('diikuti', true)->get();

        foreach ($kampanyes as $kampanye) {
            $tokoIds = DB::table('flash_sale_produk')
                ->join('produks', 'produks.id', '=', 'flash_sale_produk.produk_id')
                ->where('flash_sale_produk.flash_sale_id', $kampanye->id)
                ->whereNotNull('produks.toko_id')
                ->distinct()
                ->pluck('produks.toko_id');

            foreach ($tokoIds as $tokoId) {
                DB::table('flash_sale_tokos')->insert([
                    'flash_sale_id' => $kampanye->id,
                    'toko_id' => $tokoId,
                    'diikuti_at' => $kampanye->diikuti_at ?? now(),
                    'diikuti_oleh' => $kampanye->diikuti_oleh,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function buatPromo(): void
    {
        if (Schema::hasTable('promos')) {
            return;
        }

        Schema::create('promos', function (Blueprint $table) {
            $table->id();

            // Kosong berarti promo platform yang disusun superadmin dan
            // ditawarkan ke semua toko; terisi berarti promo milik satu toko.
            $table->foreignId('toko_id')->nullable()->constrained('tokos')->cascadeOnDelete();

            $table->string('nama');
            $table->string('slug')->unique();
            $table->text('deskripsi')->nullable();
            $table->string('tipe_diskon', 10)->default('persen');
            $table->decimal('nilai_diskon', 12, 0)->default(0);
            $table->dateTime('mulai_at');
            $table->dateTime('selesai_at');
            $table->boolean('aktif')->default(false);
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['aktif', 'mulai_at', 'selesai_at']);
        });

        Schema::create('promo_tokos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_id')->constrained('promos')->cascadeOnDelete();
            $table->foreignId('toko_id')->constrained('tokos')->cascadeOnDelete();
            $table->timestamp('diikuti_at')->nullable();
            $table->foreignId('diikuti_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['promo_id', 'toko_id']);
        });

        Schema::create('promo_produks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_id')->constrained('promos')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produks')->cascadeOnDelete();

            // Kuota kosong berarti tak dibatasi. Berbeda dengan flash sale yang
            // memang dagang kilat, promo biasa sering dijalankan tanpa jatah.
            $table->integer('kuota')->nullable();
            $table->integer('terjual')->default(0);
            $table->timestamps();

            $table->unique(['promo_id', 'produk_id']);
        });
    }
};
