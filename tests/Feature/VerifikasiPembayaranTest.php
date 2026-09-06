<?php

namespace Tests\Feature;

use App\Models\Alamat;
use App\Models\Kategori;
use App\Models\MetodePembayaran;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Penolakan bukti pembayaran harus meninggalkan jejak yang bisa dilihat kedua
 * pihak: admin tahu tindakannya berhasil, pembeli tahu apa yang harus
 * diperbaiki.
 */
class VerifikasiPembayaranTest extends TestCase
{
    use RefreshDatabase;

    private User $pembeli;

    private User $admin;

    private Produk $produk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pembeli = User::factory()->create(['role' => 'pengguna']);
        $this->admin = User::factory()->create(['role' => 'superadmin']);

        $kategori = Kategori::create([
            'nama' => 'Elektronik', 'slug' => 'elektronik', 'ikon' => 'ponsel', 'aktif' => true,
        ]);

        $this->produk = Produk::create([
            'kategori_id' => $kategori->id,
            'nama' => 'Uji', 'slug' => 'uji', 'deskripsi' => 'Uji.',
            'harga' => 100000, 'stok' => 5, 'berat' => 500, 'status' => 'aktif',
        ]);

        Alamat::create([
            'user_id' => $this->pembeli->id, 'label' => 'Rumah',
            'nama_penerima' => 'Uji', 'no_hp' => '0812',
            'provinsi' => 'Jabar', 'kota' => 'Bekasi', 'kecamatan' => 'Pondok Gede',
            'kode_pos' => '17412', 'alamat_lengkap' => 'Jl. Uji', 'is_default' => true,
        ]);

        MetodePembayaran::create([
            'nama' => 'Transfer Uji', 'tipe' => 'transfer',
            'nomor_rekening' => '1', 'atas_nama' => 'ArahInn', 'aktif' => true,
        ]);
    }

    private function buatPesanan(): Pesanan
    {
        $this->actingAs($this->pembeli)->post(route('keranjang.tambah', $this->produk), ['qty' => 1]);
        $this->actingAs($this->pembeli)->post(route('checkout.store'), [
            'alamat_id' => $this->pembeli->alamats()->first()->id,
            'kurir' => 'JNE',
            'metode_pembayaran_id' => MetodePembayaran::first()->id,
        ]);

        return $this->pembeli->pesanans()->firstOrFail();
    }

    private function unggahBukti(Pesanan $pesanan): void
    {
        $this->actingAs($this->pembeli)->post(route('pesanan.bayar', $pesanan), [
            'bukti' => UploadedFile::fake()->image('bukti.jpg'),
            'nama_pengirim' => 'Pembeli Uji',
        ]);
    }

    public function test_penolakan_mengubah_status_menjadi_ditolak(): void
    {
        Storage::fake('uploads');
        $pesanan = $this->buatPesanan();
        $this->unggahBukti($pesanan);

        $this->actingAs($this->admin)
            ->post(route('admin.pembayaran.tolak', $pesanan->pembayaran), [
                'keterangan' => 'Nominal transfer tidak sesuai.',
            ])
            ->assertSessionHasNoErrors();

        $pembayaran = $pesanan->pembayaran->refresh();

        $this->assertSame('ditolak', $pembayaran->status,
            'Sebelumnya status ditulis ulang menjadi menunggu, sehingga tak ada yang berubah.');
        $this->assertSame('Nominal transfer tidak sesuai.', $pembayaran->keterangan);
        $this->assertSame('menunggu_pembayaran', $pesanan->refresh()->status);
    }

    public function test_alasan_penolakan_wajib_diisi(): void
    {
        Storage::fake('uploads');
        $pesanan = $this->buatPesanan();
        $this->unggahBukti($pesanan);

        $this->actingAs($this->admin)
            ->post(route('admin.pembayaran.tolak', $pesanan->pembayaran), ['keterangan' => ''])
            ->assertSessionHasErrors('keterangan');

        $this->assertSame('menunggu', $pesanan->pembayaran->refresh()->status);
    }

    /**
     * Tanpa bukti tidak ada yang bisa dinilai. Sebelumnya tombolnya tetap
     * ditawarkan, dan menekannya tidak menghasilkan perubahan apa pun.
     */
    public function test_pembayaran_tanpa_bukti_tidak_dapat_ditolak_atau_diverifikasi(): void
    {
        $pesanan = $this->buatPesanan();

        $this->actingAs($this->admin)
            ->post(route('admin.pembayaran.tolak', $pesanan->pembayaran), ['keterangan' => 'Alasan apa pun.'])
            ->assertSessionHas('error');

        $this->actingAs($this->admin)
            ->post(route('admin.pembayaran.verifikasi', $pesanan->pembayaran))
            ->assertSessionHas('error');

        $this->assertSame('menunggu', $pesanan->pembayaran->refresh()->status);
        $this->assertSame('menunggu_pembayaran', $pesanan->refresh()->status);
    }

    public function test_unggah_ulang_mengembalikan_pembayaran_ke_antrean_penilaian(): void
    {
        Storage::fake('uploads');
        $pesanan = $this->buatPesanan();
        $this->unggahBukti($pesanan);

        $this->actingAs($this->admin)->post(route('admin.pembayaran.tolak', $pesanan->pembayaran), [
            'keterangan' => 'Bukti buram, tidak terbaca.',
        ]);

        $this->unggahBukti($pesanan->refresh());

        $pembayaran = $pesanan->pembayaran->refresh();

        $this->assertSame('menunggu', $pembayaran->status);
        $this->assertNull($pembayaran->keterangan, 'Alasan lama tidak lagi berlaku setelah bukti baru dikirim.');
        $this->assertSame('menunggu_konfirmasi', $pesanan->refresh()->status);
    }

    public function test_bukti_yang_ditolak_dapat_diverifikasi_setelah_diperbaiki(): void
    {
        Storage::fake('uploads');
        $pesanan = $this->buatPesanan();
        $this->unggahBukti($pesanan);

        $this->actingAs($this->admin)->post(route('admin.pembayaran.tolak', $pesanan->pembayaran), [
            'keterangan' => 'Nama pengirim berbeda.',
        ]);

        $this->unggahBukti($pesanan->refresh());

        $this->actingAs($this->admin)
            ->post(route('admin.pembayaran.verifikasi', $pesanan->pembayaran->refresh()))
            ->assertSessionHasNoErrors();

        $pembayaran = $pesanan->pembayaran->refresh();

        $this->assertSame('dibayar', $pembayaran->status);
        $this->assertNull($pembayaran->keterangan);
        $this->assertSame('diproses', $pesanan->refresh()->status);
    }

    public function test_pembeli_melihat_alasan_penolakan(): void
    {
        Storage::fake('uploads');
        $pesanan = $this->buatPesanan();
        $this->unggahBukti($pesanan);

        $this->actingAs($this->admin)->post(route('admin.pembayaran.tolak', $pesanan->pembayaran), [
            'keterangan' => 'Nominal kurang Rp 10.000.',
        ]);

        $this->actingAs($this->pembeli)
            ->get(route('pesanan.show', $pesanan->no_invoice))
            ->assertOk()
            ->assertSee('Bukti sebelumnya ditolak')
            ->assertSee('Nominal kurang Rp 10.000.')
            ->assertSee('Unggah Ulang Bukti Pembayaran');
    }

    public function test_admin_hanya_ditawari_tindakan_saat_ada_bukti(): void
    {
        Storage::fake('uploads');
        $pesanan = $this->buatPesanan();

        $this->actingAs($this->admin)
            ->get(route('admin.pembayaran.index'))
            ->assertOk()
            ->assertSee('Menunggu bukti dari pembeli');

        $this->unggahBukti($pesanan);

        $this->actingAs($this->admin)
            ->get(route('admin.pembayaran.index'))
            ->assertOk()
            ->assertSee('Alasan penolakan')
            ->assertDontSee('Menunggu bukti dari pembeli');
    }
}
