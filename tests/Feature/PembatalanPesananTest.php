<?php

namespace Tests\Feature;

use App\Models\Alamat;
use App\Models\FlashSale;
use App\Models\FlashSaleProduk;
use App\Models\Kategori;
use App\Models\Keranjang;
use App\Models\MetodePembayaran;
use App\Models\Produk;
use App\Models\Toko;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Apa yang dikembalikan saat pesanan dibatalkan.
 *
 * Checkout mencadangkan dua hal sekaligus: stok produk dan kuota potongan.
 * Keduanya harus kembali utuh saat pesanan gugur — kalau tidak, pesanan yang
 * batal tetap menghabiskan jatah flash sale, dan produknya tampak lebih sedikit
 * daripada yang sebenarnya ada di gudang.
 */
class PembatalanPesananTest extends TestCase
{
    use RefreshDatabase;

    private User $pembeli;

    private User $superadmin;

    private Produk $produk;

    private FlashSaleProduk $baris;

    private MetodePembayaran $metode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pembeli = User::factory()->create(['role' => 'pengguna']);
        $this->superadmin = User::factory()->create(['role' => 'superadmin']);

        $toko = Toko::create([
            'user_id' => User::factory()->create(['role' => 'admin'])->id,
            'nama' => 'Lapak Uji', 'slug' => 'lapak-uji',
            'status' => 'aktif', 'disetujui_at' => now(),
        ]);

        $kategori = Kategori::create(['nama' => 'Elektronik', 'slug' => 'elektronik']);

        $this->produk = Produk::create([
            'toko_id' => $toko->id, 'kategori_id' => $kategori->id,
            'nama' => 'Adaptor', 'slug' => 'adaptor',
            'harga' => 100000, 'stok' => 10, 'berat' => 500, 'status' => 'aktif',
        ]);

        // Flash sale berjalan dengan kuota terbatas, supaya pemakaian kuotanya
        // benar-benar terlihat.
        $flash = FlashSale::create([
            'nama' => 'Flash Uji', 'slug' => 'flash-uji', 'aktif' => true,
            'mulai_at' => Carbon::now()->subHour(), 'selesai_at' => Carbon::now()->addHours(5),
        ]);
        $flash->tokos()->attach($toko->id);

        $this->baris = FlashSaleProduk::create([
            'flash_sale_id' => $flash->id, 'produk_id' => $this->produk->id,
            'harga_flash' => 80000, 'kuota' => 5, 'terjual' => 0,
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

    private function checkout(int $qty = 3): \App\Models\Pesanan
    {
        Keranjang::create([
            'user_id' => $this->pembeli->id, 'produk_id' => $this->produk->id, 'qty' => $qty,
        ]);

        $this->actingAs($this->pembeli)->post(route('checkout.store'), [
            'alamat_id' => $this->pembeli->alamats()->first()->id,
            'kurir' => 'JNE',
            'metode_pembayaran_id' => $this->metode->id,
        ])->assertSessionHasNoErrors();

        return $this->pembeli->pesanans()->latest('id')->firstOrFail();
    }

    public function test_checkout_mencadangkan_stok_dan_kuota(): void
    {
        $this->checkout(3);

        $this->assertSame(7, $this->produk->fresh()->stok);
        $this->assertSame(3, $this->baris->fresh()->terjual);
    }

    public function test_pembeli_membatalkan_mengembalikan_stok(): void
    {
        $pesanan = $this->checkout(3);

        $this->actingAs($this->pembeli)->post(route('pesanan.batalkan', $pesanan));

        $this->assertSame(10, $this->produk->fresh()->stok);
    }

    public function test_pembeli_membatalkan_mengembalikan_kuota_potongan(): void
    {
        // Kuota yang tidak dikembalikan membuat pesanan batal tetap menghabiskan
        // jatah flash sale — pembeli berikutnya kehilangan harga promonya tanpa
        // ada satu pun barang yang benar-benar terjual.
        $pesanan = $this->checkout(3);

        $this->actingAs($this->pembeli)->post(route('pesanan.batalkan', $pesanan));

        $this->assertSame(0, $this->baris->fresh()->terjual);
    }

    public function test_admin_membatalkan_mengembalikan_stok_dan_kuota(): void
    {
        $pesanan = $this->checkout(2);

        $this->actingAs($this->superadmin)->post(route('admin.pesanan.batalkan', $pesanan), [
            'keterangan' => 'Stok habis di gudang',
        ]);

        $this->assertSame(10, $this->produk->fresh()->stok);
        $this->assertSame(0, $this->baris->fresh()->terjual);
    }

    public function test_pembatalan_menawarkan_pilihan_lanjut(): void
    {
        // Sesudah batal, halaman pesanan tidak menyisakan apa pun untuk
        // dikerjakan; pembeli tidak boleh ditinggal di halaman mati.
        $pesanan = $this->checkout(1);

        $this->actingAs($this->pembeli)
            ->post(route('pesanan.batalkan', $pesanan))
            ->assertSessionHas('pesanan_dibatalkan', $pesanan->no_invoice);
    }

    public function test_halaman_memunculkan_pilihan_kembali_sesudah_batal(): void
    {
        $pesanan = $this->checkout(1);

        $html = $this->actingAs($this->pembeli)
            ->withSession(['pesanan_dibatalkan' => $pesanan->no_invoice])
            ->get(route('pesanan.show', $pesanan->no_invoice))
            ->assertOk()->getContent();

        $this->assertStringContainsString('Kembali ke Beranda', $html);
        $this->assertStringContainsString('Kembali ke Keranjang', $html);
    }

    public function test_pembatalan_tidak_pernah_mengembalikan_dua_kali(): void
    {
        $pesanan = $this->checkout(3);

        $this->actingAs($this->pembeli)->post(route('pesanan.batalkan', $pesanan));
        // Percobaan kedua ditolak; stok tidak boleh naik melebihi asalnya.
        $this->actingAs($this->pembeli)->post(route('pesanan.batalkan', $pesanan));

        $this->assertSame(10, $this->produk->fresh()->stok);
        $this->assertSame(0, $this->baris->fresh()->terjual);
    }
}
