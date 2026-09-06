<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Produk;
use App\Models\Toko;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $penjualA;

    private User $penjualB;

    private Toko $tokoA;

    private Toko $tokoB;

    private Kategori $kategori;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->penjualA = User::factory()->create(['role' => 'penjual']);
        $this->penjualB = User::factory()->create(['role' => 'penjual']);

        $this->kategori = Kategori::create([
            'nama' => 'Elektronik', 'slug' => 'elektronik', 'ikon' => 'ponsel', 'aktif' => true,
        ]);

        $this->tokoA = $this->buatToko($this->penjualA, 'Lapak Alfa');
        $this->tokoB = $this->buatToko($this->penjualB, 'Lapak Beta');
    }

    private function buatToko(User $pemilik, string $nama, string $status = 'aktif'): Toko
    {
        return Toko::create([
            'user_id' => $pemilik->id,
            'nama' => $nama,
            'slug' => Toko::slugUnik($nama),
            'kota' => 'Bekasi',
            'provinsi' => 'Jawa Barat',
            'status' => $status,
            'disetujui_at' => $status === 'aktif' ? now() : null,
        ]);
    }

    private function buatProduk(Toko $toko, string $nama): Produk
    {
        return Produk::create([
            'toko_id' => $toko->id,
            'kategori_id' => $this->kategori->id,
            'nama' => $nama,
            'slug' => \Illuminate\Support\Str::slug($nama),
            'deskripsi' => 'Uji.',
            'harga' => 100000, 'stok' => 5, 'berat' => 500, 'status' => 'aktif',
        ]);
    }

    /* ---------- Etalase ---------- */

    public function test_halaman_toko_menampilkan_kartu_toko(): void
    {
        $this->get(route('toko.index'))
            ->assertOk()
            ->assertSee('Lapak Alfa')
            ->assertSee('Lapak Beta');
    }

    public function test_toko_yang_ditangguhkan_tidak_tampil_di_etalase(): void
    {
        $this->tokoB->update(['status' => 'nonaktif']);

        $this->get(route('toko.index'))
            ->assertOk()
            ->assertSee('Lapak Alfa')
            ->assertDontSee('Lapak Beta');

        $this->get(route('toko.show', $this->tokoB->slug))->assertNotFound();
    }

    public function test_produk_ikut_hilang_saat_tokonya_ditangguhkan(): void
    {
        $produk = $this->buatProduk($this->tokoB, 'Kabel Beta');

        $this->get(route('produk.index'))->assertOk()->assertSee('Kabel Beta');
        $this->get(route('produk.show', $produk->slug))->assertOk();

        $this->tokoB->update(['status' => 'nonaktif']);

        $this->get(route('produk.index'))->assertOk()->assertDontSee('Kabel Beta');
        $this->get(route('produk.show', $produk->slug))->assertNotFound();
    }

    public function test_halaman_toko_hanya_memuat_produk_miliknya(): void
    {
        $this->buatProduk($this->tokoA, 'Adaptor Alfa');
        $this->buatProduk($this->tokoB, 'Kabel Beta');

        $this->get(route('toko.show', $this->tokoA->slug))
            ->assertOk()
            ->assertSee('Adaptor Alfa')
            ->assertDontSee('Kabel Beta');
    }

    /* ---------- Batas antar-penjual ---------- */

    public function test_penjual_hanya_melihat_tokonya_sendiri(): void
    {
        $this->actingAs($this->penjualA)
            ->get(route('admin.toko.index'))
            ->assertOk()
            ->assertSee('Lapak Alfa')
            ->assertDontSee('Lapak Beta');
    }

    public function test_penjual_tidak_dapat_menyunting_toko_orang_lain(): void
    {
        $this->actingAs($this->penjualA)
            ->get(route('admin.toko.edit', $this->tokoB))
            ->assertForbidden();

        $this->actingAs($this->penjualA)
            ->patch(route('admin.toko.update', $this->tokoB), ['nama' => 'Dibajak', 'user_id' => $this->penjualA->id])
            ->assertForbidden();

        $this->assertSame('Lapak Beta', $this->tokoB->fresh()->nama);
    }

    public function test_penjual_tidak_dapat_menyetujui_atau_menangguhkan_toko(): void
    {
        $this->actingAs($this->penjualA)
            ->patch(route('admin.toko.status', $this->tokoA))
            ->assertForbidden();

        $this->assertSame('aktif', $this->tokoA->fresh()->status);
    }

    public function test_penjual_hanya_melihat_produk_tokonya(): void
    {
        $this->buatProduk($this->tokoA, 'Adaptor Alfa');
        $this->buatProduk($this->tokoB, 'Kabel Beta');

        $this->actingAs($this->penjualA)
            ->get(route('admin.produk.index'))
            ->assertOk()
            ->assertSee('Adaptor Alfa')
            ->assertDontSee('Kabel Beta');
    }

    public function test_penjual_tidak_dapat_menyunting_produk_toko_lain(): void
    {
        $produk = $this->buatProduk($this->tokoB, 'Kabel Beta');

        $this->actingAs($this->penjualA)->get(route('admin.produk.edit', $produk))->assertForbidden();
        $this->actingAs($this->penjualA)->delete(route('admin.produk.destroy', $produk))->assertForbidden();
        $this->actingAs($this->penjualA)->patch(route('admin.produk.status', $produk))->assertForbidden();

        $this->assertDatabaseHas('produks', ['id' => $produk->id, 'status' => 'aktif']);
    }

    public function test_produk_penjual_selalu_masuk_ke_tokonya_sendiri(): void
    {
        // Meski formulirnya dipalsukan menunjuk toko lain, produknya tetap
        // mendarat di toko milik penjual yang sedang masuk.
        $this->actingAs($this->penjualA)
            ->post(route('admin.produk.store'), [
                'toko_id' => $this->tokoB->id,
                'kategori_id' => $this->kategori->id,
                'nama' => 'Produk Selundupan',
                'harga' => 50000, 'stok' => 3, 'berat' => 200, 'status' => 'aktif',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('produks', [
            'nama' => 'Produk Selundupan',
            'toko_id' => $this->tokoA->id,
        ]);
    }

    public function test_penjual_tidak_dapat_membuka_kendali_platform(): void
    {
        foreach ([
            route('admin.dashboard'),
            route('admin.pesanan.index'),
            route('admin.pembayaran.index'),
            route('admin.pengiriman.index'),
            route('admin.kategori.index'),
            route('admin.flash-sale.index'),
            route('admin.laporan.transaksi'),
        ] as $url) {
            $this->actingAs($this->penjualA)->get($url)->assertForbidden();
        }
    }

    /* ---------- Pengelola platform ---------- */

    public function test_pengelola_melihat_seluruh_toko(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.toko.index'))
            ->assertOk()
            ->assertSee('Lapak Alfa')
            ->assertSee('Lapak Beta');
    }

    public function test_pengelola_menyetujui_toko_yang_menunggu(): void
    {
        $baru = $this->buatToko($this->penjualA, 'Lapak Gama', 'menunggu');

        $this->get(route('toko.index'))->assertOk()->assertDontSee('Lapak Gama');

        $this->actingAs($this->admin)->patch(route('admin.toko.status', $baru));

        $this->assertSame('aktif', $baru->fresh()->status);
        $this->assertNotNull($baru->fresh()->disetujui_at);
        $this->get(route('toko.index'))->assertOk()->assertSee('Lapak Gama');
    }

    public function test_membuat_toko_menaikkan_pembeli_menjadi_penjual(): void
    {
        $pembeli = User::factory()->create(['role' => 'pengguna']);

        $this->actingAs($this->admin)
            ->post(route('admin.toko.store'), [
                'user_id' => $pembeli->id,
                'nama' => 'Lapak Delta',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('penjual', $pembeli->fresh()->role);
        $this->assertDatabaseHas('tokos', ['nama' => 'Lapak Delta', 'status' => 'aktif']);
    }

    public function test_toko_yang_masih_punya_produk_tidak_dapat_dihapus(): void
    {
        $this->buatProduk($this->tokoA, 'Adaptor Alfa');

        $this->actingAs($this->admin)->delete(route('admin.toko.destroy', $this->tokoA));

        $this->assertDatabaseHas('tokos', ['id' => $this->tokoA->id]);
    }
}
