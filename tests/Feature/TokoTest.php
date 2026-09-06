<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Produk;
use App\Models\Toko;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TokoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $pemilikA;

    private User $pemilikB;

    private Toko $tokoA;

    private Toko $tokoB;

    private Kategori $kategori;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'superadmin']);
        $this->pemilikA = User::factory()->create(['role' => 'admin']);
        $this->pemilikB = User::factory()->create(['role' => 'admin']);

        $this->kategori = Kategori::create([
            'nama' => 'Elektronik', 'slug' => 'elektronik', 'ikon' => 'ponsel', 'aktif' => true,
        ]);

        $this->tokoA = $this->buatToko($this->pemilikA, 'Lapak Alfa');
        $this->tokoB = $this->buatToko($this->pemilikB, 'Lapak Beta');
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

    public function test_inisial_melewati_kata_bersimbol(): void
    {
        $toko = $this->buatToko($this->pemilikA, 'Dapur & Griya Sejahtera');

        $this->assertSame('DG', $toko->inisial);
        $this->assertSame('LA', $this->tokoA->inisial);
    }

    /* ---------- Batas antar-pemilik toko ---------- */

    public function test_pemilik_toko_hanya_melihat_tokonya_sendiri(): void
    {
        $this->actingAs($this->pemilikA)
            ->get(route('admin.toko.index'))
            ->assertOk()
            ->assertSee('Lapak Alfa')
            ->assertDontSee('Lapak Beta');
    }

    public function test_pemilik_toko_tidak_dapat_menyunting_toko_orang_lain(): void
    {
        $this->actingAs($this->pemilikA)
            ->get(route('admin.toko.edit', $this->tokoB))
            ->assertForbidden();

        $this->actingAs($this->pemilikA)
            ->patch(route('admin.toko.update', $this->tokoB), ['nama' => 'Dibajak', 'user_id' => $this->pemilikA->id])
            ->assertForbidden();

        $this->assertSame('Lapak Beta', $this->tokoB->fresh()->nama);
    }

    public function test_pemilik_toko_tidak_dapat_menyetujui_atau_menangguhkan_toko(): void
    {
        $this->actingAs($this->pemilikA)
            ->patch(route('admin.toko.status', $this->tokoA))
            ->assertForbidden();

        $this->assertSame('aktif', $this->tokoA->fresh()->status);
    }

    public function test_pemilik_toko_hanya_melihat_produk_tokonya(): void
    {
        $this->buatProduk($this->tokoA, 'Adaptor Alfa');
        $this->buatProduk($this->tokoB, 'Kabel Beta');

        $this->actingAs($this->pemilikA)
            ->get(route('admin.produk.index'))
            ->assertOk()
            ->assertSee('Adaptor Alfa')
            ->assertDontSee('Kabel Beta');
    }

    public function test_pemilik_toko_tidak_dapat_menyunting_produk_toko_lain(): void
    {
        $produk = $this->buatProduk($this->tokoB, 'Kabel Beta');

        $this->actingAs($this->pemilikA)->get(route('admin.produk.edit', $produk))->assertForbidden();
        $this->actingAs($this->pemilikA)->delete(route('admin.produk.destroy', $produk))->assertForbidden();
        $this->actingAs($this->pemilikA)->patch(route('admin.produk.status', $produk))->assertForbidden();

        $this->assertDatabaseHas('produks', ['id' => $produk->id, 'status' => 'aktif']);
    }

    public function test_produk_pemilik_toko_selalu_masuk_ke_tokonya_sendiri(): void
    {
        // Meski formulirnya dipalsukan menunjuk toko lain, produknya tetap
        // mendarat di toko milik pemilik toko yang sedang masuk.
        $this->actingAs($this->pemilikA)
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

    public function test_pemilik_toko_tidak_dapat_membuka_kendali_platform(): void
    {
        foreach ([
            // Flash sale dan promo justru terbuka bagi pemilik toko sejak
            // keikutsertaan menjadi urusan tiap toko.
            route('admin.dashboard'),
            route('admin.pesanan.index'),
            route('admin.pembayaran.index'),
            route('admin.pengiriman.index'),
            route('admin.kategori.index'),
            route('admin.laporan.transaksi'),
        ] as $url) {
            $this->actingAs($this->pemilikA)->get($url)->assertForbidden();
        }
    }

    public function test_pengelola_tanpa_toko_tetap_dapat_membuka_menu_promo(): void
    {
        // Admin platform tidak memiliki lapak, tetapi bertindak atas nama toko
        // aktif mana pun. Sebelumnya menunya hilang dan menyisakan judul kosong.
        $this->actingAs($this->admin)
            ->get(route('admin.promo.index'))
            ->assertOk()
            ->assertSee('Lapak Alfa');

        $this->actingAs($this->admin)
            ->get(route('admin.flash-sale.index'))
            ->assertOk();
    }

    public function test_pengelola_dapat_berpindah_toko_yang_dikelola(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.promo.index', ['toko' => $this->tokoB->slug]))
            ->assertOk()
            ->assertSee('Lapak Beta');

        // Pilihan bertahan pada permintaan berikutnya tanpa parameter, supaya
        // kiriman formulir mengenai lapak yang sama.
        $this->actingAs($this->admin)
            ->get(route('admin.promo.index'))
            ->assertOk()
            ->assertSee('Lapak Beta');
    }

    public function test_pemilik_toko_tidak_dapat_berpindah_ke_toko_lain(): void
    {
        $this->actingAs($this->pemilikA)
            ->get(route('admin.promo.index', ['toko' => $this->tokoB->slug]))
            ->assertOk()
            ->assertSee('Lapak Alfa')
            ->assertDontSee('Lapak Beta');
    }

    /* ---------- Penyuntingan lapak sendiri ---------- */

    public function test_pemilik_toko_dapat_menyunting_lapaknya(): void
    {
        $this->actingAs($this->pemilikA)
            ->patch(route('admin.toko.update', $this->tokoA), [
                'user_id' => $this->pemilikA->id,
                'nama' => 'Aldis Skincare',
                'deskripsi' => 'Brightening Facial Wash',
                'no_hp' => '085138136009',
                'email' => 'aldis@arahinn.com',
                'provinsi' => 'Jawa Barat',
                'kota' => 'Bekasi',
                'kecamatan' => 'Bekasi Selatan',
                'alamat' => 'Jl. Raya Pekayon No. 27',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.toko.index'));

        $toko = $this->tokoA->fresh();

        $this->assertSame('Aldis Skincare', $toko->nama);
        $this->assertSame('Bekasi Selatan', $toko->kecamatan);
        $this->assertSame('aktif', $toko->status, 'Menyunting tidak boleh mengubah status lapak.');
    }

    public function test_mengunggah_logo_dan_sampul_menyimpan_jalur_yang_dapat_diakses(): void
    {
        Storage::fake('uploads');

        $this->actingAs($this->pemilikA)
            ->patch(route('admin.toko.update', $this->tokoA), [
                'user_id' => $this->pemilikA->id,
                'nama' => 'Lapak Alfa',
                'logo' => UploadedFile::fake()->image('logo.png', 400, 400),
                'banner' => UploadedFile::fake()->image('sampul.jpg', 1200, 400),
            ])
            ->assertSessionHasNoErrors();

        $toko = $this->tokoA->fresh();

        // Awalan "uploads/" wajib ikut tersimpan; tanpa itu asset() menghasilkan
        // URL yang berujung 404.
        $this->assertStringStartsWith('uploads/', $toko->logo);
        $this->assertStringStartsWith('uploads/', $toko->banner);
    }

    public function test_menyunting_tanpa_mengunggah_tidak_menghapus_gambar_lama(): void
    {
        $this->tokoA->update(['logo' => 'uploads/toko/lama.png', 'banner' => 'uploads/toko/sampul.png']);

        $this->actingAs($this->pemilikA)
            ->patch(route('admin.toko.update', $this->tokoA), [
                'user_id' => $this->pemilikA->id,
                'nama' => 'Lapak Alfa',
            ])
            ->assertSessionHasNoErrors();

        $toko = $this->tokoA->fresh();

        $this->assertSame('uploads/toko/lama.png', $toko->logo);
        $this->assertSame('uploads/toko/sampul.png', $toko->banner);
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
        $baru = $this->buatToko($this->pemilikA, 'Lapak Gama', 'menunggu');

        $this->get(route('toko.index'))->assertOk()->assertDontSee('Lapak Gama');

        $this->actingAs($this->admin)->patch(route('admin.toko.status', $baru));

        $this->assertSame('aktif', $baru->fresh()->status);
        $this->assertNotNull($baru->fresh()->disetujui_at);
        $this->get(route('toko.index'))->assertOk()->assertSee('Lapak Gama');
    }

    public function test_membuat_toko_menaikkan_pembeli_menjadi_pemilik_toko(): void
    {
        $pembeli = User::factory()->create(['role' => 'pengguna']);

        $this->actingAs($this->admin)
            ->post(route('admin.toko.store'), [
                'user_id' => $pembeli->id,
                'nama' => 'Lapak Delta',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('admin', $pembeli->fresh()->role);
        $this->assertDatabaseHas('tokos', ['nama' => 'Lapak Delta', 'status' => 'aktif']);
    }

    public function test_toko_yang_masih_punya_produk_tidak_dapat_dihapus(): void
    {
        $this->buatProduk($this->tokoA, 'Adaptor Alfa');

        $this->actingAs($this->admin)->delete(route('admin.toko.destroy', $this->tokoA));

        $this->assertDatabaseHas('tokos', ['id' => $this->tokoA->id]);
    }
}
