<?php

namespace Tests\Feature;

use App\Models\Alamat;
use App\Models\FlashSale;
use App\Models\Kategori;
use App\Models\MetodePembayaran;
use App\Models\Produk;
use App\Models\Toko;
use App\Models\User;
use App\Notifications\NotifikasiKampanye;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FlashSaleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $superadmin;

    private User $pembeli;

    private Produk $produk;

    private Toko $toko;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->superadmin = User::factory()->create(['role' => 'superadmin']);
        $this->pembeli = User::factory()->create(['role' => 'pengguna']);

        $kategori = Kategori::create([
            'nama' => 'Elektronik', 'slug' => 'elektronik', 'ikon' => 'ponsel', 'aktif' => true,
        ]);

        $this->toko = Toko::create([
            'user_id' => $this->admin->id,
            'nama' => 'Toko Uji', 'slug' => 'toko-uji',
            'status' => 'aktif', 'disetujui_at' => now(),
        ]);

        $this->produk = Produk::create([
            'toko_id' => $this->toko->id,
            'kategori_id' => $kategori->id,
            'nama' => 'Produk Flash', 'slug' => 'produk-flash', 'deskripsi' => 'Uji.',
            'harga' => 200000, 'stok' => 20, 'berat' => 500, 'status' => 'aktif',
        ]);

        Alamat::create([
            'user_id' => $this->pembeli->id, 'label' => 'Rumah',
            'nama_penerima' => 'Uji', 'no_hp' => '0812',
            'provinsi' => 'Jabar', 'kota' => 'Bekasi', 'kecamatan' => 'Pondok Gede',
            'kode_pos' => '17412', 'alamat_lengkap' => 'Jl. Uji', 'is_default' => true,
        ]);

        MetodePembayaran::create([
            'nama' => 'Transfer Uji', 'tipe' => 'transfer',
            'no_rekening' => '1', 'atas_nama' => 'ArahInn', 'aktif' => true,
        ]);
    }

    private function buatKampanye(array $ubah = []): FlashSale
    {
        return FlashSale::create(array_merge([
            'nama' => 'Flash Sale Uji',
            'slug' => 'flash-sale-uji',
            'mulai_at' => Carbon::now()->subHour(),
            'selesai_at' => Carbon::now()->addHours(3),
            'tipe_diskon' => 'persen',
            'nilai_diskon' => 25,
            'aktif' => true,
            'dibuat_oleh' => $this->superadmin->id,
        ], $ubah));
    }

    /**
     * Kampanye yang sudah diikuti toko uji — bentuk paling lazim di pengujian.
     */
    private function buatKampanyeDiikuti(array $ubah = []): FlashSale
    {
        $kampanye = $this->buatKampanye($ubah);
        $kampanye->tokos()->attach($this->toko->id, ['diikuti_at' => now()]);

        return $kampanye;
    }

    private function sertakanProduk(FlashSale $kampanye, int $harga = 150000, int $kuota = 5): void
    {
        $kampanye->produks()->create([
            'produk_id' => $this->produk->id,
            'harga_flash' => $harga,
            'kuota' => $kuota,
        ]);
    }

    /* ---------- Hak akses ---------- */

    public function test_hanya_superadmin_yang_dapat_menyusun_kampanye(): void
    {
        $this->actingAs($this->admin)->get(route('admin.flash-sale.kampanye.index'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('admin.flash-sale.kampanye.create'))->assertForbidden();

        $this->actingAs($this->superadmin)->get(route('admin.flash-sale.kampanye.index'))->assertOk();
        $this->actingAs($this->superadmin)->get(route('admin.flash-sale.kampanye.create'))->assertOk();
    }

    public function test_admin_dapat_membuka_halaman_flash_sale(): void
    {
        $kampanye = $this->buatKampanye();

        $this->actingAs($this->admin)
            ->get(route('admin.flash-sale.index'))
            ->assertOk()
            ->assertSee($kampanye->nama);
    }

    public function test_kampanye_draf_tidak_tampil_bagi_admin(): void
    {
        $draf = $this->buatKampanye(['aktif' => false, 'nama' => 'Kampanye Draf', 'slug' => 'kampanye-draf']);

        $this->actingAs($this->admin)
            ->get(route('admin.flash-sale.index'))
            ->assertOk()
            ->assertDontSee($draf->nama);

        $this->actingAs($this->admin)
            ->get(route('admin.flash-sale.kelola', $draf))
            ->assertNotFound();
    }

    /* ---------- Penyusunan kampanye ---------- */

    public function test_superadmin_membuat_kampanye_sebagai_draf(): void
    {
        $this->actingAs($this->superadmin)
            ->post(route('admin.flash-sale.kampanye.store'), [
                'nama' => 'Promo Akhir Pekan',
                'mulai_at' => Carbon::now()->addDay()->format('Y-m-d H:i'),
                'selesai_at' => Carbon::now()->addDays(2)->format('Y-m-d H:i'),
                'tipe_diskon' => 'persen',
                'nilai_diskon' => 30,
            ])
            ->assertSessionHasNoErrors();

        $kampanye = FlashSale::firstOrFail();

        $this->assertSame('promo-akhir-pekan', $kampanye->slug);
        $this->assertFalse($kampanye->aktif, 'Kampanye baru harus berupa draf sampai diterbitkan.');
        $this->assertSame($this->superadmin->id, $kampanye->dibuat_oleh);
    }

    public function test_jadwal_yang_berakhir_sebelum_dimulai_ditolak(): void
    {
        $this->actingAs($this->superadmin)
            ->post(route('admin.flash-sale.kampanye.store'), [
                'nama' => 'Jadwal Terbalik',
                'mulai_at' => Carbon::now()->addDays(2)->format('Y-m-d H:i'),
                'selesai_at' => Carbon::now()->addDay()->format('Y-m-d H:i'),
                'diskon_persen' => 10,
            ])
            ->assertSessionHasErrors('selesai_at');

        $this->assertSame(0, FlashSale::count());
    }

    /* ---------- Keikutsertaan ---------- */

    public function test_menerbitkan_kampanye_memberi_tahu_pemilik_toko(): void
    {
        Notification::fake();

        $kampanye = $this->buatKampanye(['aktif' => false]);

        $this->actingAs($this->superadmin)->patch(route('admin.flash-sale.kampanye.terbit', $kampanye));

        Notification::assertSentTo(
            $this->admin,
            fn (NotifikasiKampanye $n) => $n->peristiwa === 'flash_sale_baru',
        );
    }

    public function test_toko_mengikuti_dan_berhenti_mengikuti_kampanye(): void
    {
        $kampanye = $this->buatKampanye();

        $this->actingAs($this->admin)->post(route('admin.flash-sale.ikut', $kampanye));

        $this->assertTrue($kampanye->fresh()->diikutiOleh($this->toko));
        $this->assertDatabaseHas('flash_sale_tokos', [
            'flash_sale_id' => $kampanye->id,
            'toko_id' => $this->toko->id,
            'diikuti_oleh' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post(route('admin.flash-sale.ikut', $kampanye));

        $this->assertFalse($kampanye->fresh()->diikutiOleh($this->toko));
        $this->assertDatabaseMissing('flash_sale_tokos', [
            'flash_sale_id' => $kampanye->id,
            'toko_id' => $this->toko->id,
        ]);
    }

    public function test_kampanye_yang_tidak_diikuti_toko_tidak_memotong_harga(): void
    {
        // Kampanye berjalan dan produknya terdaftar, tetapi tokonya belum ikut.
        $kampanye = $this->buatKampanye();
        $this->sertakanProduk($kampanye);

        $this->assertSame(200000.0, $this->produk->fresh()->hargaEfektif());

        $kampanye->tokos()->attach($this->toko->id, ['diikuti_at' => now()]);

        $this->assertSame(150000.0, $this->produk->fresh()->hargaEfektif());
    }

    public function test_halaman_kelola_mengisi_harga_flash_sesuai_diskon_kampanye(): void
    {
        // Harga produk 200.000, diskon kampanye 20% -> usulan 160.000.
        $kampanye = $this->buatKampanyeDiikuti(['nilai_diskon' => 20]);

        $this->actingAs($this->admin)
            ->get(route('admin.flash-sale.kelola', $kampanye))
            ->assertOk()
            ->assertSee('value="160000"', false)
            ->assertSee('form="fs-'.$this->produk->id.'"', false)
            ->assertSee('name="harga_flash"', false)
            // Tombol borong di kaki halaman sudah tidak ada; tiap baris berdiri sendiri.
            ->assertDontSee('Simpan Pilihan Produk');
    }

    public function test_admin_mengikutkan_produk_beserta_harga_dan_kuota(): void
    {
        $kampanye = $this->buatKampanyeDiikuti();

        $this->actingAs($this->admin)
            ->post(route('admin.flash-sale.produk', [$kampanye, $this->produk]), [
                'harga_flash' => 150000,
                'kuota' => 5,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('baris_tersimpan', $this->produk->id);

        $baris = $kampanye->produks()->firstOrFail();

        $this->assertSame($this->produk->id, $baris->produk_id);
        $this->assertSame('150000', $baris->harga_flash);
        $this->assertSame(5, $baris->kuota);
    }

    public function test_mengikutkan_ulang_memperbarui_baris_yang_sama(): void
    {
        $kampanye = $this->buatKampanyeDiikuti();
        $this->sertakanProduk($kampanye);

        $this->actingAs($this->admin)
            ->post(route('admin.flash-sale.produk', [$kampanye, $this->produk]), [
                'harga_flash' => 120000,
                'kuota' => 8,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $kampanye->produks()->count());
        $this->assertSame('120000', $kampanye->produks()->firstOrFail()->harga_flash);
    }

    public function test_harga_flash_harus_lebih_murah_dari_harga_normal(): void
    {
        $kampanye = $this->buatKampanyeDiikuti();

        $this->actingAs($this->admin)
            ->post(route('admin.flash-sale.produk', [$kampanye, $this->produk]), [
                'harga_flash' => 250000,
                'kuota' => 5,
            ])
            ->assertSessionHasErrors('harga_flash', null, 'baris'.$this->produk->id);

        $this->assertSame(0, $kampanye->produks()->count());
    }

    public function test_kuota_tidak_boleh_melebihi_stok(): void
    {
        $kampanye = $this->buatKampanyeDiikuti();

        $this->actingAs($this->admin)
            ->post(route('admin.flash-sale.produk', [$kampanye, $this->produk]), [
                'harga_flash' => 150000,
                'kuota' => 999,
            ])
            ->assertSessionHasErrors('kuota', null, 'baris'.$this->produk->id);

        $this->assertSame(0, $kampanye->produks()->count());
    }

    public function test_admin_membatalkan_keikutsertaan_satu_produk(): void
    {
        $kampanye = $this->buatKampanyeDiikuti();
        $this->sertakanProduk($kampanye);

        $this->actingAs($this->admin)
            ->post(route('admin.flash-sale.produk', [$kampanye, $this->produk]), ['tindakan' => 'lepas'])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, $kampanye->produks()->count());
    }

    /* ---------- Harga yang berlaku ---------- */

    public function test_harga_flash_berlaku_hanya_saat_semua_syarat_terpenuhi(): void
    {
        $kampanye = $this->buatKampanye(['diikuti' => false]);
        $this->sertakanProduk($kampanye);

        $this->assertSame(200000.0, $this->produk->fresh()->hargaEfektif(),
            'Kampanye yang belum diikuti toko tidak boleh mengubah harga.');

        $kampanye->tokos()->attach($this->toko->id, ['diikuti_at' => now()]);
        $this->assertSame(150000.0, $this->produk->fresh()->hargaEfektif());

        $kampanye->update(['aktif' => false]);
        $this->assertSame(200000.0, $this->produk->fresh()->hargaEfektif(),
            'Kampanye yang ditarik penerbitannya harus berhenti berlaku.');
    }

    public function test_harga_normal_kembali_saat_kampanye_belum_mulai_atau_sudah_berakhir(): void
    {
        $kampanye = $this->buatKampanye([
            'diikuti' => true,
            'mulai_at' => Carbon::now()->addDay(),
            'selesai_at' => Carbon::now()->addDays(2),
        ]);
        $this->sertakanProduk($kampanye);

        $this->assertSame(200000.0, $this->produk->fresh()->hargaEfektif());

        $kampanye->update([
            'mulai_at' => Carbon::now()->subDays(2),
            'selesai_at' => Carbon::now()->subDay(),
        ]);

        $this->assertSame(200000.0, $this->produk->fresh()->hargaEfektif());
    }

    public function test_kuota_habis_mengembalikan_harga_normal(): void
    {
        $kampanye = $this->buatKampanyeDiikuti();
        $this->sertakanProduk($kampanye, kuota: 2);

        $this->assertSame(150000.0, $this->produk->fresh()->hargaEfektif());

        $kampanye->produks()->first()->update(['terjual' => 2]);

        $this->assertSame(200000.0, $this->produk->fresh()->hargaEfektif(),
            'Kuota yang habis harus mengembalikan harga normal, bukan menghilangkan produknya.');
    }

    /* ---------- Jalur uang ---------- */

    public function test_checkout_memakai_harga_flash_dan_memakai_kuotanya(): void
    {
        $kampanye = $this->buatKampanyeDiikuti();
        $this->sertakanProduk($kampanye, harga: 150000, kuota: 5);

        $this->actingAs($this->pembeli)->post(route('keranjang.tambah', $this->produk), ['qty' => 2]);
        $this->actingAs($this->pembeli)->post(route('checkout.store'), [
            'alamat_id' => $this->pembeli->alamats()->first()->id,
            'kurir' => 'JNE',
            'metode_pembayaran_id' => MetodePembayaran::first()->id,
        ])->assertSessionHasNoErrors();

        $pesanan = $this->pembeli->pesanans()->firstOrFail();
        $item = $pesanan->items()->firstOrFail();

        $this->assertSame('150000', $item->harga, 'Harga flash harus tercatat pada pesanan.');
        $this->assertSame(300000, (int) $pesanan->subtotal);
        $this->assertSame(2, $kampanye->produks()->first()->refresh()->terjual);
    }

    public function test_beranda_menampilkan_kampanye_yang_berjalan(): void
    {
        $kampanye = $this->buatKampanyeDiikuti();
        $this->sertakanProduk($kampanye);

        $this->get('/')
            ->assertOk()
            ->assertSee($kampanye->nama)
            ->assertSee('Flash Sale')
            // Rel geser, bukan kisi kaku, dan hitung mundurnya menyertakan hari.
            ->assertSee('rel-geser', false)
            ->assertSee("'Hari', this.angka(this.sisa / 86400)", false)
            ->assertSee($this->produk->nama);
    }

    public function test_kartu_produk_memisahkan_tautan_dari_tombol_keranjang(): void
    {
        $kampanye = $this->buatKampanyeDiikuti();
        $this->sertakanProduk($kampanye);

        $html = $this->get('/')->assertOk()->getContent();

        // Tombol keranjang tidak boleh berada di dalam tautan produk, sebab
        // kliknya akan ikut membuka halaman produk alih-alih menambah ke keranjang.
        $this->assertStringNotContainsString('<button', explode('</a>', explode('<a href="'.route('produk.show', $this->produk->slug).'"', $html)[1])[0]);
    }

    public function test_beranda_tidak_menampilkan_kampanye_tanpa_produk(): void
    {
        $kampanye = $this->buatKampanyeDiikuti(['nama' => 'Kampanye Kosong', 'slug' => 'kampanye-kosong']);

        $this->get('/')->assertOk()->assertDontSee('Kampanye Kosong');
    }

    /* ---------- Halaman flash sale ---------- */

    public function test_halaman_flash_sale_hanya_memuat_produk_yang_berpromo(): void
    {
        $kampanye = $this->buatKampanyeDiikuti();
        $this->sertakanProduk($kampanye);

        $biasa = Produk::create([
            'toko_id' => $this->toko->id,
            'kategori_id' => $this->produk->kategori_id,
            'nama' => 'Produk Tanpa Promo', 'slug' => 'produk-tanpa-promo', 'deskripsi' => 'Uji.',
            'harga' => 90000, 'stok' => 10, 'berat' => 300, 'status' => 'aktif',
        ]);

        $this->get(route('flash-sale.index'))
            ->assertOk()
            ->assertSee($kampanye->nama)
            ->assertSee($this->produk->nama)
            ->assertDontSee($biasa->nama);
    }

    public function test_halaman_flash_sale_melewatkan_produk_yang_kuotanya_habis(): void
    {
        $kampanye = $this->buatKampanyeDiikuti();
        $this->sertakanProduk($kampanye, kuota: 3);
        $kampanye->produks()->firstOrFail()->update(['terjual' => 3]);

        $this->get(route('flash-sale.index'))
            ->assertOk()
            ->assertDontSee($this->produk->nama)
            ->assertSee('Tidak ada flash sale yang berjalan');
    }

    public function test_halaman_flash_sale_menawarkan_jadwal_berikutnya_saat_kosong(): void
    {
        $this->buatKampanyeDiikuti([
            'nama' => 'Kampanye Depan', 'slug' => 'kampanye-depan',
            'mulai_at' => Carbon::now()->addDays(2), 'selesai_at' => Carbon::now()->addDays(3),
        ]);

        $this->get(route('flash-sale.index'))
            ->assertOk()
            ->assertSee('Tidak ada flash sale yang berjalan')
            ->assertSee('Kampanye Depan');
    }

    public function test_tautan_flash_sale_di_navigasi_mengikuti_ada_tidaknya_promo(): void
    {
        $this->get('/')->assertOk()->assertDontSee(route('flash-sale.index'));

        $kampanye = $this->buatKampanyeDiikuti();
        $this->sertakanProduk($kampanye);

        $this->get('/')->assertOk()->assertSee(route('flash-sale.index'));
    }

    public function test_kampanye_berjalan_tidak_dapat_dihapus(): void
    {
        $kampanye = $this->buatKampanyeDiikuti();

        $this->actingAs($this->superadmin)
            ->delete(route('admin.flash-sale.kampanye.destroy', $kampanye))
            ->assertSessionHas('error');

        $this->assertNotNull($kampanye->fresh());
    }
}
