<?php

use App\Models\Toko;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Fondasi multi-penjual: setiap produk kini dimiliki sebuah toko.
 *
 * Data lama tidak boleh menggantung. Seluruh produk yang sudah ada dipindahkan
 * ke satu toko bawaan milik pengelola pertama, sehingga katalog, keranjang, dan
 * pesanan yang berjalan tetap utuh sesudah migrasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tokos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nama');
            $table->string('slug')->unique();
            $table->text('deskripsi')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
            $table->string('no_hp', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('kota')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('alamat')->nullable();

            // Toko baru menunggu persetujuan pengelola sebelum tampil di etalase.
            $table->string('status', 20)->default('menunggu');
            $table->timestamp('disetujui_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        // Peran penjual ditambahkan. Kolomnya diubah jadi string biasa supaya
        // penambahan peran berikutnya tidak perlu mengubah skema lagi.
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('pengguna')->change();
        });

        Schema::table('produks', function (Blueprint $table) {
            $table->foreignId('toko_id')->nullable()->after('id')
                ->constrained('tokos')->cascadeOnDelete();
        });

        $this->pindahkanProdukLama();

        Schema::table('produks', function (Blueprint $table) {
            $table->index('toko_id');
        });
    }

    public function down(): void
    {
        Schema::table('produks', function (Blueprint $table) {
            $table->dropForeign(['toko_id']);
            $table->dropColumn('toko_id');
        });

        Schema::dropIfExists('tokos');
    }

    /**
     * Buat toko bawaan lalu pindahkan seluruh produk lama ke sana.
     *
     * Dijalankan hanya bila memang ada produk yang perlu dipindahkan, agar
     * pemasangan baru tidak memiliki toko kosong yang tidak pernah dipakai.
     */
    private function pindahkanProdukLama(): void
    {
        if (! DB::table('produks')->exists()) {
            return;
        }

        $pemilik = DB::table('users')->whereIn('role', ['superadmin', 'admin'])->orderBy('id')->first();

        if (! $pemilik) {
            return;
        }

        $nama = config('brand.nama', 'Toko Utama');

        $tokoId = DB::table('tokos')->insertGetId([
            'user_id' => $pemilik->id,
            'nama' => $nama,
            // Tabelnya baru dibuat pada migrasi ini, jadi belum mungkin bentrok.
            'slug' => Str::slug($nama),
            'deskripsi' => 'Toko resmi '.$nama.'.',
            'status' => 'aktif',
            'disetujui_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('produks')->whereNull('toko_id')->update(['toko_id' => $tokoId]);
    }
};
