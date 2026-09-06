<?php

namespace Tests\Feature;

use App\Models\Alamat;
use App\Models\Kategori;
use App\Models\MetodePembayaran;
use App\Models\Produk;
use App\Models\Promo;
use App\Models\Toko;
use App\Models\User;
use App\Notifications\NotifikasiKampanye;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PromoTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    private User $penjualA;

    private User $penjualB;

    private Toko $tokoA;

    private Toko $tokoB;

    private Produk $produkA;

    private Kategori $kategori;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::factory()->create(['role' => 'superadmin']);
        $this->penjualA = User::factory()->create(['role' => 'penjual']);
        $this->penjualB = User::factory()->create(['role' => 'penjual']);

        $this->kategori = Kategori::create([
            'nama' => 'Elektronik', 'slug' => 'elektronik', 'ikon' => 'ponsel', 'aktif' => true,
        ]);

        $this->tokoA = $this->buatToko($this->penjualA, 'Lapak Alfa');
        $this->tokoB = $this->buatToko($this->penjualB, 'Lapak Beta');

        $this->produkA = $this->buatProduk($this->tokoA, 'Adaptor Alfa', 200000);
    }

    /* ---------- Penopang ---------- */

    private function buatToko(User $pemilik, string $nama): Toko
    {
        return Toko::create([
            'user_id' => $pemilik->id,
            'nama' => $nama,
            'slug' => Toko::slugUnik($nama),
            'status' => 'aktif',
            'disetujui_at' => now(),
        ]);
    }

    private function buatProduk(Toko $toko, string $nama, int $harga): Produk
    {
        return Produk::create([
            'toko_id' => $toko->id,
            'kategori_id' => $this->kategori->id,
            'nama' => $nama,
            'slug' => \Illuminate\Support\Str::slug($nama),
            'deskripsi' => 'Uji.',
            'harga' => $harga, 'stok' => 10, 'berat' => 500, 'status' => 'aktif',
        ]);
    }

    private function buatPromo(array $ubah = []): Promo
    {
        return Promo::create(array_merge([
            'toko_id' => null,
            'nama' => 'Promo Uji',
            'slug' => 'promo-uji',
            'tipe_diskon' => 'persen',
            'nilai_diskon' => 25,
            'mulai_at' => Carbon::now()->subHour(),
            'selesai_at' => Carbon::now()->addDays(3),
            'aktif' => true,
            'dibuat_oleh' => $this->superadmin->id,
        ], $ubah));
    }

    /* ---------- Perhitungan potongan ---------- */

    public function test_potongan_persentase_dan_nominal_menghasilkan_harga_benar(): void
    {
        $persen = $this->buatPromo(['tipe_diskon' => 'persen', 'nilai_diskon' => 25]);
        $nominal = $this->buatPromo(['tipe_diskon' => 'nominal', 'nilai_diskon' => 30000,
            'nama' => 'Promo Nominal', 'slug' => 'promo-nominal']);

        $this->assertSame(150000.0, $persen->hargaSetelahPotongan(200000));
        $this->assertSame(170000.0, $nominal->hargaSetelahPotongan(200000));

        $this->assertSame('25%', $persen->label_diskon);
        $this->assertSame(rp(30000), $nominal->label_diskon);
    }

    public function test_potongan_nominal_tidak_pernah_membuat_harga_minus(): void
    {
        $promo = $this->buatPromo(['tipe_diskon' => 'nominal', 'nilai_diskon' => 500000]);

        // Potongan lebih besar dari harganya sendiri; pesanan bernilai minus
        // jauh lebih berbahaya daripada promo yang tidak menarik.
        $this->assertSame(0.0, $promo->hargaSetelahPotongan(200000));
    }

    /* ---------- Hak akses ---------- */

    public function test_penjual_hanya_melihat_promo_tokonya_sendiri(): void
    {
        $this->buatPromo(['nama' => 'Promo Platform', 'slug' => 'promo-platform']);
        $this->buatPromo(['toko_id' => $this->tokoA->id, 'nama' => 'Promo Alfa', 'slug' => 'promo-alfa']);
        $this->buatPromo(['toko_id' => $this->tokoB->id, 'nama' => 'Promo Beta', 'slug' => 'promo-beta']);

        $this->actingAs($this->penjualA)
            ->get(route('admin.promo.kampanye.index'))
            ->assertOk()
            ->assertSee('Promo Alfa')
            ->assertDontSee('Promo Beta')
            ->assertDontSee('Promo Platform');
    }

    public function test_penjual_tidak_dapat_menyunting_promo_platform(): void
    {
        $promo = $this->buatPromo();

        $this->actingAs($this->penjualA)->get(route('admin.promo.kampanye.edit', $promo))->assertForbidden();
        $this->actingAs($this->penjualA)->patch(route('admin.promo.kampanye.terbit', $promo))->assertForbidden();
    }

    public function test_promo_penjual_selalu_milik_tokonya_sendiri(): void
    {
        $this->actingAs($this->penjualA)
            ->post(route('admin.promo.kampanye.store'), [
                'nama' => 'Promo Selundupan',
                'mulai_at' => Carbon::now()->format('Y-m-d H:i'),
                'selesai_at' => Carbon::now()->addDay()->format('Y-m-d H:i'),
                'tipe_diskon' => 'nominal',
                'nilai_diskon' => 15000,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('promos', [
            'nama' => 'Promo Selundupan',
            'toko_id' => $this->tokoA->id,
            'aktif' => false,
        ]);
    }

    public function test_promo_superadmin_selalu_milik_platform(): void
    {
        $this->actingAs($this->superadmin)
            ->post(route('admin.promo.kampanye.store'), [
                'nama' => 'Promo Nasional',
                'mulai_at' => Carbon::now()->format('Y-m-d H:i'),
                'selesai_at' => Carbon::now()->addDay()->format('Y-m-d H:i'),
                'tipe_diskon' => 'persen',
                'nilai_diskon' => 15,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('promos', ['nama' => 'Promo Nasional', 'toko_id' => null]);
    }

    public function test_persentase_di_atas_sembilan_puluh_ditolak(): void
    {
        $this->actingAs($this->superadmin)
            ->post(route('admin.promo.kampanye.store'), [
                'nama' => 'Promo Kebablasan',
                'mulai_at' => Carbon::now()->format('Y-m-d H:i'),
                'selesai_at' => Carbon::now()->addDay()->format('Y-m-d H:i'),
                'tipe_diskon' => 'persen',
                'nilai_diskon' => 95,
            ])
            ->assertSessionHasErrors('nilai_diskon');

        // Nilai sebesar itu wajar untuk potongan rupiah.
        $this->actingAs($this->superadmin)
            ->post(route('admin.promo.kampanye.store'), [
                'nama' => 'Promo Rupiah',
                'mulai_at' => Carbon::now()->format('Y-m-d H:i'),
                'selesai_at' => Carbon::now()->addDay()->format('Y-m-d H:i'),
                'tipe_diskon' => 'nominal',
                'nilai_diskon' => 95000,
            ])
            ->assertSessionHasNoErrors();
    }

    /* ---------- Notifikasi ---------- */

    public function test_menerbitkan_promo_platform_memberi_tahu_setiap_pemilik_toko(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $promo = $this->buatPromo(['aktif' => false]);

        $this->actingAs($this->superadmin)->patch(route('admin.promo.kampanye.terbit', $promo));

        \Illuminate\Support\Facades\Notification::assertSentTo(
            [$this->penjualA, $this->penjualB],
            fn (NotifikasiKampanye $n) => $n->peristiwa === 'promo_baru',
        );
    }

    public function test_promo_milik_toko_tidak_menghasilkan_notifikasi(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $promo = $this->buatPromo(['toko_id' => $this->tokoA->id, 'aktif' => false]);

        $this->actingAs($this->penjualA)->patch(route('admin.promo.kampanye.terbit', $promo));

        // Pemberitahuan hanya berguna bila pengirim dan penerimanya berbeda.
        \Illuminate\Support\Facades\Notification::assertNothingSent();
    }

    /* ---------- Harga yang berlaku ---------- */

    public function test_promo_platform_baru_berlaku_setelah_toko_ikut(): void
    {
        $promo = $this->buatPromo();
        $promo->produks()->create(['produk_id' => $this->produkA->id]);

        $this->assertSame(200000.0, $this->produkA->fresh()->hargaEfektif());

        $promo->tokos()->attach($this->tokoA->id, ['diikuti_at' => now()]);

        $this->assertSame(150000.0, $this->produkA->fresh()->hargaEfektif());
    }

    public function test_promo_milik_toko_berlaku_tanpa_perlu_diikuti(): void
    {
        $promo = $this->buatPromo(['toko_id' => $this->tokoA->id]);
        $promo->produks()->create(['produk_id' => $this->produkA->id]);

        $this->assertSame(150000.0, $this->produkA->fresh()->hargaEfektif());
    }

    public function test_potongan_terbaik_yang_dipakai_saat_promo_dan_flash_sale_bersamaan(): void
    {
        $kampanye = \App\Models\FlashSale::create([
            'nama' => 'Flash Uji', 'slug' => 'flash-uji',
            'mulai_at' => Carbon::now()->subHour(), 'selesai_at' => Carbon::now()->addHours(3),
            'tipe_diskon' => 'persen', 'nilai_diskon' => 10, 'aktif' => true,
            'dibuat_oleh' => $this->superadmin->id,
        ]);
        $kampanye->tokos()->attach($this->tokoA->id, ['diikuti_at' => now()]);
        $kampanye->produks()->create([
            'produk_id' => $this->produkA->id, 'harga_flash' => 180000, 'kuota' => 5,
        ]);

        // Promo 25% menghasilkan 150.000 — lebih murah daripada flash 180.000.
        $promo = $this->buatPromo(['toko_id' => $this->tokoA->id]);
        $promo->produks()->create(['produk_id' => $this->produkA->id]);

        $produk = $this->produkA->fresh();

        $this->assertSame(150000.0, $produk->hargaEfektif());
        $this->assertFalse($produk->potonganBerlaku()->flashSale());
    }

    public function test_kuota_promo_habis_mengembalikan_harga_normal(): void
    {
        $promo = $this->buatPromo(['toko_id' => $this->tokoA->id]);
        $baris = $promo->produks()->create(['produk_id' => $this->produkA->id, 'kuota' => 2]);

        $this->assertSame(150000.0, $this->produkA->fresh()->hargaEfektif());

        $baris->update(['terjual' => 2]);

        $this->assertSame(200000.0, $this->produkA->fresh()->hargaEfektif());
    }

    public function test_promo_tanpa_kuota_tidak_pernah_habis(): void
    {
        $promo = $this->buatPromo(['toko_id' => $this->tokoA->id]);
        $baris = $promo->produks()->create(['produk_id' => $this->produkA->id, 'kuota' => null]);

        $baris->update(['terjual' => 999]);

        $this->assertNull($baris->fresh()->sisaKuota());
        $this->assertSame(150000.0, $this->produkA->fresh()->hargaEfektif());
    }

    /* ---------- Keikutsertaan ---------- */

    public function test_toko_mengikuti_lalu_berhenti_mengikuti_promo(): void
    {
        $promo = $this->buatPromo();

        $this->actingAs($this->penjualA)->post(route('admin.promo.ikut', $promo));
        $this->assertTrue($promo->fresh()->berlakuUntukToko($this->tokoA));

        $this->actingAs($this->penjualA)->post(route('admin.promo.ikut', $promo));
        $this->assertFalse($promo->fresh()->berlakuUntukToko($this->tokoA));
    }

    public function test_penjual_hanya_menyertakan_produk_tokonya(): void
    {
        $promo = $this->buatPromo(['toko_id' => $this->tokoA->id]);
        $produkB = $this->buatProduk($this->tokoB, 'Kabel Beta', 50000);

        $this->actingAs($this->penjualA)
            ->post(route('admin.promo.produk', [$promo, $produkB]), ['kuota' => 3])
            ->assertForbidden();

        $this->assertSame(0, $promo->produks()->count());
    }

    public function test_kuota_tidak_boleh_melebihi_stok(): void
    {
        $promo = $this->buatPromo(['toko_id' => $this->tokoA->id]);

        $this->actingAs($this->penjualA)
            ->post(route('admin.promo.produk', [$promo, $this->produkA]), ['kuota' => 999])
            ->assertSessionHasErrors('kuota', null, 'baris'.$this->produkA->id);

        $this->assertSame(0, $promo->produks()->count());
    }

    /* ---------- Checkout ---------- */

    public function test_checkout_memakai_harga_promo_dan_memakai_kuotanya(): void
    {
        $pembeli = User::factory()->create(['role' => 'pengguna']);

        Alamat::create([
            'user_id' => $pembeli->id, 'label' => 'Rumah',
            'nama_penerima' => 'Uji', 'no_hp' => '0812',
            'provinsi' => 'Jabar', 'kota' => 'Bekasi', 'kecamatan' => 'Pondok Gede',
            'kode_pos' => '17412', 'alamat_lengkap' => 'Jl. Uji', 'is_default' => true,
        ]);

        $metode = MetodePembayaran::create([
            'nama' => 'Transfer Uji', 'tipe' => 'transfer',
            'nomor_rekening' => '1', 'atas_nama' => 'ArahInn', 'aktif' => true,
        ]);

        $promo = $this->buatPromo(['toko_id' => $this->tokoA->id]);
        $baris = $promo->produks()->create(['produk_id' => $this->produkA->id, 'kuota' => 5]);

        $pembeli->keranjangs()->create(['produk_id' => $this->produkA->id, 'qty' => 2]);

        $this->actingAs($pembeli)->post(route('checkout.store'), [
            'alamat_id' => $pembeli->alamats()->first()->id,
            'kurir' => 'JNE',
            'metode_pembayaran_id' => $metode->id,
        ]);

        $pesanan = \App\Models\Pesanan::firstOrFail();

        $this->assertSame('300000', $pesanan->subtotal, 'Checkout harus memakai harga promo, bukan harga normal.');
        $this->assertSame(2, $baris->fresh()->terjual);
    }
}
