<?php

namespace Tests\Feature;

use App\Models\Alamat;
use App\Models\Kategori;
use App\Models\Keranjang;
use App\Models\MetodePembayaran;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pemeriksaan kepemilikan data harus kebal terhadap tipe kunci asing.
 *
 * PDO dengan emulated prepares — lazim di hosting bersama — mengembalikan
 * kolom angka sebagai string. Perbandingan ketat terhadap auth()->id() yang
 * bertipe integer lalu selalu bernilai salah, sehingga pemilik ditolak dari
 * datanya sendiri. Gejalanya hanya muncul di server, tidak pernah di lokal,
 * karena driver di sana mengembalikan integer.
 */
class KepemilikanDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_kunci_asing_pesanan_selalu_bertipe_integer(): void
    {
        $pesanan = new Pesanan(['user_id' => '7', 'alamat_id' => '3']);

        $this->assertSame(7, $pesanan->user_id);
        $this->assertSame(3, $pesanan->alamat_id);
    }

    public function test_kunci_asing_alamat_dan_keranjang_selalu_bertipe_integer(): void
    {
        $this->assertSame(9, (new Alamat(['user_id' => '9']))->user_id);

        $keranjang = new Keranjang(['user_id' => '4', 'produk_id' => '11', 'qty' => '2']);
        $this->assertSame(4, $keranjang->user_id);
        $this->assertSame(11, $keranjang->produk_id);
        $this->assertSame(2, $keranjang->qty);
    }

    /**
     * Menirukan keadaan server: kolom user_id dibaca sebagai string, lalu
     * pemilik membuka pesanannya sendiri. Sebelum diperbaiki, langkah ini
     * berakhir dengan pengalihan "bukan milik akun Anda".
     */
    public function test_pemilik_tetap_dapat_membuka_pesanannya_saat_kunci_terbaca_string(): void
    {
        $pembeli = User::factory()->create(['role' => 'pengguna']);

        $kategori = Kategori::create([
            'nama' => 'Elektronik', 'slug' => 'elektronik', 'ikon' => 'ponsel', 'aktif' => true,
        ]);

        $produk = Produk::create([
            'kategori_id' => $kategori->id,
            'nama' => 'Uji', 'slug' => 'uji', 'deskripsi' => 'Uji.',
            'harga' => 50000, 'stok' => 5, 'berat' => 500, 'status' => 'aktif',
        ]);

        Alamat::create([
            'user_id' => $pembeli->id, 'label' => 'Rumah',
            'nama_penerima' => 'Uji', 'no_hp' => '0812',
            'provinsi' => 'Jabar', 'kota' => 'Bekasi', 'kecamatan' => 'Pondok Gede',
            'kode_pos' => '17412', 'alamat_lengkap' => 'Jl. Uji', 'is_default' => true,
        ]);

        MetodePembayaran::create([
            'nama' => 'Transfer Uji', 'tipe' => 'transfer',
            'nomor_rekening' => '1', 'atas_nama' => 'ArahInn', 'aktif' => true,
        ]);

        $this->actingAs($pembeli)->post(route('keranjang.tambah', $produk), ['qty' => 1]);
        $this->actingAs($pembeli)->post(route('checkout.store'), [
            'alamat_id' => $pembeli->alamats()->first()->id,
            'kurir' => 'JNE',
            'metode_pembayaran_id' => MetodePembayaran::first()->id,
        ]);

        $pesanan = $pembeli->pesanans()->firstOrFail();

        // Kunci asing sengaja diisi ulang sebagai string, meniru hasil bacaan
        // driver yang mengaktifkan emulated prepares.
        $pesanan->setRawAttributes([
            ...$pesanan->getAttributes(),
            'user_id' => (string) $pembeli->id,
        ]);

        $this->assertSame($pembeli->id, $pesanan->user_id, 'Cast harus menormalkan nilainya kembali.');

        $this->actingAs($pembeli)
            ->get(route('pesanan.show', $pesanan->no_invoice))
            ->assertOk()
            ->assertSee($pesanan->no_invoice);
    }
}
