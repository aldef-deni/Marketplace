<?php

namespace Tests\Feature;

use App\Models\Alamat;
use App\Models\Kategori;
use App\Models\Keranjang;
use App\Models\MetodePembayaran;
use App\Models\Produk;
use App\Models\Toko;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Batas stok pada keranjang, dan kebenaran angka yang ditampilkan.
 *
 * Keranjang yang membiarkan kuantitas melebihi stok hanya menunda kegagalan
 * sampai checkout, ketika pembeli sudah mengisi alamat dan memilih kurir.
 */
class BatasStokKeranjangTest extends TestCase
{
    use RefreshDatabase;

    private User $pembeli;

    private Produk $produk;

    private MetodePembayaran $metode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pembeli = User::factory()->create(['role' => 'pengguna']);

        $toko = Toko::create([
            'user_id' => User::factory()->create(['role' => 'admin'])->id,
            'nama' => 'Lapak Uji', 'slug' => 'lapak-uji',
            'status' => 'aktif', 'disetujui_at' => now(),
        ]);

        $kategori = Kategori::create(['nama' => 'Elektronik', 'slug' => 'elektronik']);

        $this->produk = Produk::create([
            'toko_id' => $toko->id, 'kategori_id' => $kategori->id,
            'nama' => 'Adaptor', 'slug' => 'adaptor',
            'harga' => 150000, 'stok' => 5, 'berat' => 500, 'status' => 'aktif',
        ]);

        $this->metode = MetodePembayaran::create([
            'nama' => 'Transfer Uji', 'tipe' => 'transfer',
            'nomor_rekening' => '123456', 'atas_nama' => 'Uji', 'aktif' => true,
        ]);

        Alamat::create([
            'user_id' => $this->pembeli->id, 'label' => 'Rumah',
            'nama_penerima' => 'Uji', 'no_hp' => '0812',
            'provinsi' => 'Jabar', 'kota' => 'Bekasi', 'kecamatan' => 'Pondok Gede',
            'kode_pos' => '17412', 'alamat_lengkap' => 'Jl. Uji', 'is_default' => true,
        ]);
    }

    private function item(int $qty = 1): Keranjang
    {
        return Keranjang::create([
            'user_id' => $this->pembeli->id, 'produk_id' => $this->produk->id, 'qty' => $qty,
        ]);
    }

    /* ---------- Batas atas ---------- */

    public function test_ubah_kuantitas_tidak_boleh_melebihi_stok(): void
    {
        $item = $this->item(1);

        $this->actingAs($this->pembeli)
            ->patch(route('keranjang.updateQty', $item), ['qty' => 99]);

        $this->assertSame(5, $item->fresh()->qty);
    }

    public function test_ubah_kuantitas_tidak_boleh_nol_atau_minus(): void
    {
        $item = $this->item(3);

        $this->actingAs($this->pembeli)
            ->patch(route('keranjang.updateQty', $item), ['qty' => 0]);
        $this->assertSame(1, $item->fresh()->qty);

        $this->actingAs($this->pembeli)
            ->patch(route('keranjang.updateQty', $item), ['qty' => -5]);
        $this->assertSame(1, $item->fresh()->qty);
    }

    public function test_menambah_ke_keranjang_tidak_melampaui_stok(): void
    {
        $this->actingAs($this->pembeli)
            ->post(route('keranjang.tambah', $this->produk), ['qty' => 99]);

        $this->assertSame(5, $this->pembeli->keranjangs()->first()->qty);
    }

    public function test_menambah_berulang_tetap_berhenti_di_stok(): void
    {
        // Tiga kali menambah empat butir pada stok lima: yang tersimpan tetap
        // lima, bukan dua belas.
        foreach (range(1, 3) as $kali) {
            $this->actingAs($this->pembeli)
                ->post(route('keranjang.tambah', $this->produk), ['qty' => 4]);
        }

        $this->assertSame(5, $this->pembeli->keranjangs()->first()->qty);
    }

    public function test_pembeli_diberi_tahu_saat_kuantitasnya_dipangkas(): void
    {
        // Diam-diam memangkas membuat pembeli mengira permintaannya diterima,
        // lalu heran melihat angka yang berbeda.
        $item = $this->item(1);

        $this->actingAs($this->pembeli)
            ->patch(route('keranjang.updateQty', $item), ['qty' => 99])
            ->assertSessionHas('info');
    }

    /* ---------- Angka yang ditampilkan ---------- */

    public function test_subtotal_item_mengikuti_kuantitas(): void
    {
        $item = $this->item(1);

        $this->actingAs($this->pembeli)
            ->patch(route('keranjang.updateQty', $item), ['qty' => 3]);

        $this->assertEquals(450000, $item->fresh()->subtotal);
    }

    public function test_subtotal_memakai_harga_berlaku_bukan_harga_daftar(): void
    {
        // Harga coret tidak boleh ikut terhitung; yang dipakai harga efektif,
        // sama seperti di katalog dan checkout.
        $this->produk->update(['harga_coret' => 300000]);
        $item = $this->item(2);

        $this->assertEquals(
            $this->produk->hargaEfektif() * 2,
            $item->fresh()->subtotal,
        );
    }

    public function test_halaman_keranjang_menampilkan_subtotal_yang_benar(): void
    {
        $this->item(3);

        $this->actingAs($this->pembeli)
            ->get(route('keranjang.index'))
            ->assertOk()
            ->assertSee(rp(450000));
    }

    public function test_total_pesanan_adalah_subtotal_ditambah_ongkir(): void
    {
        $this->item(3);

        $this->actingAs($this->pembeli)->post(route('checkout.store'), [
            'alamat_id' => $this->pembeli->alamats()->first()->id,
            'kurir' => 'JNE',
            'metode_pembayaran_id' => $this->metode->id,
        ])->assertSessionHasNoErrors();

        $pesanan = $this->pembeli->pesanans()->latest('id')->firstOrFail();

        $this->assertEquals(450000, $pesanan->subtotal);
        $this->assertEquals($pesanan->subtotal + $pesanan->ongkir, $pesanan->total);
        $this->assertEquals($pesanan->items->sum('subtotal'), $pesanan->subtotal);
    }

    public function test_jumlah_tagihan_sama_dengan_total_pesanan(): void
    {
        // Selisih di sini berarti pembeli ditagih angka yang berbeda dari yang
        // tertera pada pesanannya.
        $this->item(2);

        $this->actingAs($this->pembeli)->post(route('checkout.store'), [
            'alamat_id' => $this->pembeli->alamats()->first()->id,
            'kurir' => 'JNE',
            'metode_pembayaran_id' => $this->metode->id,
        ]);

        $pesanan = $this->pembeli->pesanans()->latest('id')->firstOrFail();

        $this->assertEquals($pesanan->total, $pesanan->pembayaran->jumlah);
    }

    public function test_checkout_menolak_kuantitas_yang_melampaui_stok(): void
    {
        // Jaring terakhir: stok bisa habis dibeli orang lain setelah item ini
        // masuk keranjang.
        $item = $this->item(3);
        $this->produk->update(['stok' => 2]);

        $this->actingAs($this->pembeli)->post(route('checkout.store'), [
            'alamat_id' => $this->pembeli->alamats()->first()->id,
            'kurir' => 'JNE',
            'metode_pembayaran_id' => $this->metode->id,
        ])->assertStatus(422);

        $this->assertSame(0, $this->pembeli->pesanans()->count());
    }
}
