<?php

namespace Tests\Feature;

use App\Models\Alamat;
use App\Models\MetodePembayaran;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mengganti metode pembayaran pesanan yang belum dibayar.
 *
 * Tanpa ini, pembeli yang salah pilih metode hanya punya satu jalan keluar:
 * membatalkan pesanannya lalu menyusunnya ulang dari awal.
 */
class UbahMetodePembayaranTest extends TestCase
{
    use RefreshDatabase;

    private User $pembeli;

    private Pesanan $pesanan;

    private MetodePembayaran $transfer;

    private MetodePembayaran $cod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pembeli = User::factory()->create(['role' => 'pengguna']);

        $this->transfer = MetodePembayaran::create([
            'nama' => 'Transfer BCA', 'tipe' => 'transfer',
            'nomor_rekening' => '123456', 'atas_nama' => 'Uji', 'aktif' => true,
        ]);

        $this->cod = MetodePembayaran::create([
            'nama' => 'COD', 'tipe' => 'cod', 'aktif' => true,
        ]);

        $alamat = Alamat::create([
            'user_id' => $this->pembeli->id, 'label' => 'Rumah',
            'nama_penerima' => 'Uji', 'no_hp' => '0812',
            'provinsi' => 'Jabar', 'kota' => 'Bekasi', 'kecamatan' => 'Pondok Gede',
            'kode_pos' => '17412', 'alamat_lengkap' => 'Jl. Uji', 'is_default' => true,
        ]);

        $this->pesanan = Pesanan::create([
            'no_invoice' => 'INV-UBAH-001',
            'user_id' => $this->pembeli->id, 'alamat_id' => $alamat->id,
            'subtotal' => 100000, 'ongkir' => 10000, 'total' => 110000,
            'status' => 'menunggu_pembayaran', 'kurir' => 'JNE', 'layanan_kurir' => 'REG',
            'batas_pembayaran' => now()->addDay(),
        ]);

        Pembayaran::create([
            'pesanan_id' => $this->pesanan->id,
            'metode_pembayaran_id' => $this->transfer->id,
            'kode' => 'PAY-UBAH-001', 'jumlah' => 110000, 'status' => 'menunggu',
        ]);
    }

    private function ubah(MetodePembayaran $ke)
    {
        return $this->actingAs($this->pembeli)->post(route('pesanan.metode', $this->pesanan), [
            'metode_pembayaran_id' => $ke->id,
        ]);
    }

    public function test_metode_dapat_diganti_selama_belum_dibayar(): void
    {
        $this->ubah($this->cod)->assertRedirect(route('pesanan.show', $this->pesanan->no_invoice));

        $this->assertSame($this->cod->id, $this->pesanan->pembayaran->fresh()->metode_pembayaran_id);
    }

    public function test_beralih_ke_cod_mengubah_status_dan_melepas_tenggat(): void
    {
        // COD tidak menunggu pembayaran melainkan konfirmasi, dan tidak punya
        // tenggat bayar sama sekali.
        $this->ubah($this->cod);

        $segar = $this->pesanan->fresh();

        $this->assertSame('menunggu_konfirmasi', $segar->status);
        $this->assertNull($segar->batas_pembayaran);
    }

    public function test_beralih_dari_cod_mengembalikan_tenggat_bayar(): void
    {
        $this->ubah($this->cod);
        $this->ubah($this->transfer);

        $segar = $this->pesanan->fresh();

        $this->assertSame('menunggu_pembayaran', $segar->status);
        $this->assertNotNull($segar->batas_pembayaran);
    }

    public function test_tagihan_gerbang_lama_dilepas_saat_metode_berganti(): void
    {
        // Tagihan Midtrans terikat pada kanal metode sebelumnya; membawanya ke
        // metode baru akan menagih lewat kanal yang sudah tidak dipilih.
        $this->pesanan->pembayaran->update([
            'gateway' => 'midtrans',
            'order_id_gateway' => 'INV-UBAH-001-ABCDE',
            'snap_token' => 'token-lama',
            'snap_url' => 'https://contoh/snap/token-lama',
        ]);

        // Harus benar-benar berpindah metode; mengirim metode yang sama
        // memang sengaja tidak mengubah apa pun.
        $this->ubah($this->cod);

        $segar = $this->pesanan->pembayaran->fresh();

        $this->assertNull($segar->gateway);
        $this->assertNull($segar->order_id_gateway);
        $this->assertNull($segar->snap_url);
    }

    public function test_bukti_lama_ikut_dilepas(): void
    {
        $this->pesanan->pembayaran->update(['bukti' => null, 'nama_pengirim' => 'Budi']);

        $this->ubah($this->cod);

        $this->assertNull($this->pesanan->pembayaran->fresh()->nama_pengirim);
    }

    public function test_metode_tidak_dapat_diganti_saat_bukti_menunggu_penilaian(): void
    {
        // Kalau boleh, admin akan menilai bukti untuk metode yang sudah tidak
        // dipakai lagi.
        $this->pesanan->pembayaran->update(['bukti' => 'uploads/bukti/a.jpg']);

        $this->ubah($this->cod)->assertStatus(422);

        $this->assertSame($this->transfer->id, $this->pesanan->pembayaran->fresh()->metode_pembayaran_id);
    }

    public function test_metode_tidak_dapat_diganti_setelah_lunas(): void
    {
        $this->pesanan->pembayaran->update(['status' => 'dibayar']);
        $this->pesanan->update(['status' => 'diproses']);

        $this->ubah($this->cod)->assertStatus(422);
    }

    public function test_metode_yang_belum_siap_ditolak(): void
    {
        $belumSiap = MetodePembayaran::create([
            'nama' => 'Bank Kosong', 'tipe' => 'transfer', 'nomor_rekening' => null, 'aktif' => true,
        ]);

        $this->ubah($belumSiap)->assertNotFound();

        $this->assertSame($this->transfer->id, $this->pesanan->pembayaran->fresh()->metode_pembayaran_id);
    }

    public function test_pesanan_orang_lain_tidak_dapat_diubah(): void
    {
        $orangLain = User::factory()->create(['role' => 'pengguna']);

        $this->actingAs($orangLain)
            ->post(route('pesanan.metode', $this->pesanan), ['metode_pembayaran_id' => $this->cod->id])
            ->assertForbidden();
    }

    public function test_halaman_pesanan_menawarkan_penggantian_metode(): void
    {
        $html = $this->actingAs($this->pembeli)
            ->get(route('pesanan.show', $this->pesanan->no_invoice))->assertOk()->getContent();

        $this->assertStringContainsString('Ubah Metode Pembayaran', $html);
        $this->assertStringContainsString('Kembali ke Keranjang', $html);
    }

    public function test_penggantian_tidak_ditawarkan_setelah_lunas(): void
    {
        $this->pesanan->pembayaran->update(['status' => 'dibayar']);
        $this->pesanan->update(['status' => 'diproses']);

        $html = $this->actingAs($this->pembeli)
            ->get(route('pesanan.show', $this->pesanan->no_invoice))->assertOk()->getContent();

        $this->assertStringNotContainsString('Ubah Metode Pembayaran', $html);
    }
}
