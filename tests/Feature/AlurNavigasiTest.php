<?php

namespace Tests\Feature;

use App\Models\Alamat;
use App\Models\Kategori;
use App\Models\MetodePembayaran;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menjaga agar pengguna tidak pernah berakhir di jalan buntu: menambah alamat
 * di tengah checkout harus kembali ke checkout, dan halaman yang ditolak harus
 * mengantar ke tempat yang berguna.
 */
class AlurNavigasiTest extends TestCase
{
    use RefreshDatabase;

    private User $pembeli;

    private Produk $produk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pembeli = User::factory()->create(['role' => 'pengguna']);

        $kategori = Kategori::create([
            'nama' => 'Elektronik', 'slug' => 'elektronik', 'ikon' => 'ponsel', 'aktif' => true,
        ]);

        $this->produk = Produk::create([
            'kategori_id' => $kategori->id,
            'nama' => 'Uji Produk', 'slug' => 'uji-produk',
            'deskripsi' => 'Produk pengujian.',
            'harga' => 100000, 'stok' => 5, 'berat' => 500,
            'status' => 'aktif',
        ]);

        MetodePembayaran::create([
            'nama' => 'Transfer Uji', 'tipe' => 'transfer',
            'nomor_rekening' => '123', 'atas_nama' => 'Market ArahInn', 'aktif' => true,
        ]);
    }

    private array $alamatValid = [
        'label' => 'Rumah',
        'nama_penerima' => 'Penerima Uji',
        'no_hp' => '08123456789',
        'provinsi' => 'Jawa Barat',
        'kota' => 'Bekasi',
        'kecamatan' => 'Pondok Gede',
        'kode_pos' => '17412',
        'alamat_lengkap' => 'Jl. Uji No. 1',
    ];

    public function test_menambah_alamat_dari_checkout_kembali_ke_checkout(): void
    {
        $this->actingAs($this->pembeli)->post(route('keranjang.tambah', $this->produk), ['qty' => 1]);

        $this->actingAs($this->pembeli)
            ->post(route('alamat.store'), [...$this->alamatValid, 'dari' => 'checkout'])
            ->assertRedirect(route('checkout.index'));
    }

    public function test_menambah_alamat_dari_buku_alamat_tetap_di_buku_alamat(): void
    {
        $this->actingAs($this->pembeli)
            ->post(route('alamat.store'), $this->alamatValid)
            ->assertRedirect(route('alamat.index'));
    }

    /**
     * Keranjang kosong berarti checkout akan menolak; mengantar ke sana hanya
     * memindahkan jalan buntunya.
     */
    public function test_tanpa_isi_keranjang_tidak_dipaksa_ke_checkout(): void
    {
        $this->actingAs($this->pembeli)
            ->post(route('alamat.store'), [...$this->alamatValid, 'dari' => 'checkout'])
            ->assertRedirect(route('alamat.index'));
    }

    public function test_formulir_alamat_meneruskan_penanda_asal(): void
    {
        $this->actingAs($this->pembeli)
            ->get(route('alamat.create', ['dari' => 'checkout']))
            ->assertOk()
            ->assertSee('name="dari" value="checkout"', escape: false)
            ->assertSee('Simpan &amp; Lanjut Checkout', escape: false);
    }

    public function test_buku_alamat_menawarkan_jalan_kembali_saat_keranjang_berisi(): void
    {
        $this->actingAs($this->pembeli)->post(route('keranjang.tambah', $this->produk), ['qty' => 1]);

        $this->actingAs($this->pembeli)
            ->get(route('alamat.index'))
            ->assertOk()
            ->assertSee('Lanjutkan ke Checkout');
    }

    public function test_buku_alamat_tanpa_isi_keranjang_tidak_menawarkan_checkout(): void
    {
        $this->actingAs($this->pembeli)
            ->get(route('alamat.index'))
            ->assertOk()
            ->assertDontSee('Lanjutkan ke Checkout');
    }

    /**
     * Membuka pesanan milik orang lain — misalnya dari tautan lama di riwayat
     * peramban — dulu berhenti di halaman 403 kosong tanpa jalan keluar.
     */
    public function test_pesanan_milik_orang_lain_mengantar_ke_daftar_sendiri(): void
    {
        $orangLain = User::factory()->create(['role' => 'pengguna']);
        Alamat::create(['user_id' => $orangLain->id, ...$this->alamatValid, 'is_default' => true]);

        $this->actingAs($orangLain)->post(route('keranjang.tambah', $this->produk), ['qty' => 1]);
        $this->actingAs($orangLain)->post(route('checkout.store'), [
            'alamat_id' => $orangLain->alamats()->first()->id,
            'kurir' => 'JNE',
            'metode_pembayaran_id' => MetodePembayaran::first()->id,
        ]);

        $pesanan = $orangLain->pesanans()->firstOrFail();

        $this->actingAs($this->pembeli)
            ->get(route('pesanan.show', $pesanan->no_invoice))
            ->assertRedirect(route('pesanan.index'))
            ->assertSessionHas('error');

        $this->actingAs($this->pembeli)
            ->get(route('pesanan.cetak', $pesanan->no_invoice))
            ->assertRedirect(route('pesanan.index'));
    }

    public function test_admin_tetap_boleh_membuka_pesanan_siapa_pun(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Alamat::create(['user_id' => $this->pembeli->id, ...$this->alamatValid, 'is_default' => true]);

        $this->actingAs($this->pembeli)->post(route('keranjang.tambah', $this->produk), ['qty' => 1]);
        $this->actingAs($this->pembeli)->post(route('checkout.store'), [
            'alamat_id' => $this->pembeli->alamats()->first()->id,
            'kurir' => 'JNE',
            'metode_pembayaran_id' => MetodePembayaran::first()->id,
        ]);

        $pesanan = $this->pembeli->pesanans()->firstOrFail();

        $this->actingAs($admin)->get(route('pesanan.show', $pesanan->no_invoice))->assertOk();
    }
}
