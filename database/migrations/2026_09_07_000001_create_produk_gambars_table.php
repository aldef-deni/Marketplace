<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Galeri gambar produk.
 *
 * Kolom produks.gambar sengaja dipertahankan sebagai gambar utama, bukan
 * dipindahkan ke tabel ini. Kolom itu dibaca belasan tempat — kartu produk,
 * keranjang, checkout, dan salinan gambar pada item pesanan — sehingga
 * memindahkannya berarti mengubah semuanya sekaligus demi keuntungan yang nol.
 *
 * Jadi pembagiannya: tabel ini menyimpan seluruh gambar, dan produks.gambar
 * menunjuk mana di antaranya yang menjadi bawaan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('produk_gambars')) {
            return;
        }

        Schema::create('produk_gambars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('produks')->cascadeOnDelete();
            $table->string('jalur');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['produk_id', 'urutan']);
        });

        $this->pindahkanGambarLama();
    }

    public function down(): void
    {
        Schema::dropIfExists('produk_gambars');
    }

    /**
     * Gambar yang sudah ada dijadikan anggota pertama galerinya.
     *
     * Tanpa ini, produk lama akan tampak tidak bergambar di panel meski
     * gambarnya masih terpajang di etalase.
     */
    private function pindahkanGambarLama(): void
    {
        $baris = DB::table('produks')
            ->whereNotNull('gambar')
            ->where('gambar', '!=', '')
            ->get(['id', 'gambar']);

        foreach ($baris->chunk(200) as $bagian) {
            DB::table('produk_gambars')->insert(
                $bagian->map(fn ($p) => [
                    'produk_id' => $p->id,
                    'jalur' => $p->gambar,
                    'urutan' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all()
            );
        }
    }
};
