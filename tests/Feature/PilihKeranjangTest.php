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
 * Memilih sebagian isi keranjang untuk dibeli.
 *
 * Yang dijaga: pilihan itu ditentukan di sisi server, bukan oleh formulir yang
 * dikirim peramban — dan keranjang orang lain tidak pernah dapat disentuh.
 */
class PilihKeranjangTest extends TestCase
{
    use RefreshDatabase;

    private User $pembeli;

    private Produk $satu;

    private Produk $dua;

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

        $this->satu = $this->buatProduk($toko, $kategori, 'Adaptor', 'adaptor');
        $this->dua = $this->buatProduk($toko, $kategori, 'Kabel', 'kabel');

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

    private function buatProduk(Toko $toko, Kategori $kategori, string $nama, string $slug): Produk
    {
        return Produk::create([
            'toko_id' => $toko->id, 'kategori_id' => $kategori->id,
            'nama' => $nama, 'slug' => $slug,
            'harga' => 100000, 'stok' => 10, 'berat' => 500, 'status' => 'aktif',
        ]);
    }

    private function isiKeranjang(): array
    {
        return [
            Keranjang::create(['user_id' => $this->pembeli->id, 'produk_id' => $this->satu->id, 'qty' => 1]),
            Keranjang::create(['user_id' => $this->pembeli->id, 'produk_id' => $this->dua->id, 'qty' => 2]),
        ];
    }

    public function test_item_baru_langsung_tercentang(): void
    {
        // Yang baru dimasukkan memang berniat dibeli; keranjang yang seluruhnya
        // tidak tercentang hanya membingungkan pemiliknya.
        $this->actingAs($this->pembeli)->post(route('keranjang.tambah', $this->satu), ['qty' => 1]);

        $this->assertTrue($this->pembeli->keranjangs()->first()->dipilih);
    }

    public function test_pilihan_tersimpan_dan_yang_lain_dilepas(): void
    {
        [$a, $b] = $this->isiKeranjang();

        $this->actingAs($this->pembeli)
            ->postJson(route('keranjang.pilih'), ['id' => [$a->id]])
            ->assertOk();

        $this->assertTrue($a->fresh()->dipilih);
        $this->assertFalse($b->fresh()->dipilih);
    }

    public function test_keranjang_orang_lain_tidak_dapat_dicentang(): void
    {
        $orangLain = User::factory()->create(['role' => 'pengguna']);
        $milikOrangLain = Keranjang::create([
            'user_id' => $orangLain->id, 'produk_id' => $this->satu->id, 'qty' => 1, 'dipilih' => false,
        ]);

        [$a] = $this->isiKeranjang();

        $this->actingAs($this->pembeli)
            ->postJson(route('keranjang.pilih'), ['id' => [$a->id, $milikOrangLain->id]])
            ->assertOk();

        $this->assertFalse($milikOrangLain->fresh()->dipilih);
    }

    public function test_checkout_hanya_memuat_yang_tercentang(): void
    {
        [$a, $b] = $this->isiKeranjang();
        $b->update(['dipilih' => false]);

        $this->actingAs($this->pembeli)
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('Adaptor')
            ->assertDontSee('Kabel');
    }

    public function test_pesanan_hanya_berisi_yang_tercentang(): void
    {
        [$a, $b] = $this->isiKeranjang();
        $b->update(['dipilih' => false]);

        $this->actingAs($this->pembeli)->post(route('checkout.store'), [
            'alamat_id' => $this->pembeli->alamats()->first()->id,
            'kurir' => 'JNE',
            'metode_pembayaran_id' => $this->metode->id,
        ])->assertSessionHasNoErrors();

        $pesanan = $this->pembeli->pesanans()->latest('id')->firstOrFail();

        $this->assertCount(1, $pesanan->items);
        $this->assertSame('Adaptor', $pesanan->items->first()->nama_produk);
    }

    public function test_yang_tidak_dibeli_tetap_tinggal_di_keranjang(): void
    {
        // Sebelumnya checkout mengosongkan seluruh keranjang, sehingga membeli
        // sebagian berarti kehilangan sisanya.
        [$a, $b] = $this->isiKeranjang();
        $b->update(['dipilih' => false]);

        $this->actingAs($this->pembeli)->post(route('checkout.store'), [
            'alamat_id' => $this->pembeli->alamats()->first()->id,
            'kurir' => 'JNE',
            'metode_pembayaran_id' => $this->metode->id,
        ]);

        $sisa = $this->pembeli->keranjangs()->get();

        $this->assertCount(1, $sisa);
        $this->assertSame($this->dua->id, $sisa->first()->produk_id);
    }

    public function test_stok_hanya_berkurang_untuk_yang_dibeli(): void
    {
        [$a, $b] = $this->isiKeranjang();
        $b->update(['dipilih' => false]);

        $this->actingAs($this->pembeli)->post(route('checkout.store'), [
            'alamat_id' => $this->pembeli->alamats()->first()->id,
            'kurir' => 'JNE',
            'metode_pembayaran_id' => $this->metode->id,
        ]);

        $this->assertSame(9, $this->satu->fresh()->stok);
        $this->assertSame(10, $this->dua->fresh()->stok);
    }

    public function test_checkout_tanpa_centang_dikembalikan_ke_keranjang(): void
    {
        [$a, $b] = $this->isiKeranjang();
        $this->pembeli->keranjangs()->update(['dipilih' => false]);

        $this->actingAs($this->pembeli)
            ->get(route('checkout.index'))
            ->assertRedirect(route('keranjang.index'));

        $this->actingAs($this->pembeli)->post(route('checkout.store'), [
            'alamat_id' => $this->pembeli->alamats()->first()->id,
            'kurir' => 'JNE',
            'metode_pembayaran_id' => $this->metode->id,
        ])->assertRedirect(route('keranjang.index'));

        $this->assertSame(0, $this->pembeli->pesanans()->count());
    }

    public function test_produk_tak_tersedia_tidak_dapat_dicentang_di_halaman(): void
    {
        [$a, $b] = $this->isiKeranjang();
        $this->dua->update(['stok' => 0]);

        $html = $this->actingAs($this->pembeli)
            ->get(route('keranjang.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Stok habis', $html);
        $this->assertStringContainsString('disabled', $html);
    }
}
