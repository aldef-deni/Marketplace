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
 * Menelusuri satu siklus transaksi utuh, dari keranjang sampai pesanan selesai,
 * beserta notifikasi yang harus terbit di tiap langkah untuk pembeli dan admin.
 */
class AlurTransaksiTest extends TestCase
{
    use RefreshDatabase;

    private User $pembeli;

    private User $admin;

    private Produk $produk;

    private MetodePembayaran $transfer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pembeli = User::factory()->create(['role' => 'pengguna']);
        $this->admin = User::factory()->create(['role' => 'admin']);

        $kategori = Kategori::create([
            'nama' => 'Elektronik', 'slug' => 'elektronik', 'ikon' => 'ponsel', 'aktif' => true,
        ]);

        $this->produk = Produk::create([
            'kategori_id' => $kategori->id,
            'nama' => 'Uji Produk', 'slug' => 'uji-produk',
            'deskripsi' => 'Produk untuk pengujian.',
            'harga' => 150000, 'stok' => 10, 'berat' => 1000,
            'status' => 'aktif',
        ]);

        $this->transfer = MetodePembayaran::create([
            'nama' => 'Transfer Bank Uji', 'tipe' => 'transfer',
            'nomor_rekening' => '1234567890', 'atas_nama' => 'Market ArahInn', 'aktif' => true,
        ]);

        Alamat::create([
            'user_id' => $this->pembeli->id,
            'label' => 'Rumah', 'nama_penerima' => 'Pembeli Uji', 'no_hp' => '08123456789',
            'provinsi' => 'Jawa Barat', 'kota' => 'Bogor', 'kecamatan' => 'Bogor Tengah',
            'kode_pos' => '16121', 'alamat_lengkap' => 'Jl. Uji No. 1',
            'is_default' => true,
        ]);
    }

    /**
     * Cari notifikasi berdasarkan peristiwanya.
     *
     * Mengandalkan urutan tidak aman di sini: beberapa notifikasi terbit dalam
     * detik yang sama sehingga latest() — yang hanya mengurutkan created_at —
     * tidak menjamin mana yang lebih dulu.
     */
    private function cariNotifikasi(User $pengguna, string $peristiwa): ?object
    {
        return $pengguna->notifications()->get()
            ->first(fn ($n) => ($n->data['peristiwa'] ?? null) === $peristiwa);
    }

    private function buatPesanan(): Pesanan
    {
        $this->actingAs($this->pembeli)
            ->post(route('keranjang.tambah', $this->produk), ['qty' => 2])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->pembeli)
            ->post(route('checkout.store'), [
                'alamat_id' => $this->pembeli->alamats()->first()->id,
                'kurir' => 'JNE',
                'metode_pembayaran_id' => $this->transfer->id,
            ])
            ->assertSessionHasNoErrors();

        // Diurutkan berdasarkan id, bukan created_at: beberapa pesanan uji
        // lahir dalam detik yang sama sehingga urutan waktunya tidak pasti.
        return Pesanan::latest('id')->firstOrFail();
    }

    public function test_siklus_transfer_bank_berjalan_sampai_selesai(): void
    {
        Storage::fake('uploads');

        // --- Pembeli membuat pesanan ---
        $pesanan = $this->buatPesanan();

        $this->assertSame('menunggu_pembayaran', $pesanan->status);
        $this->assertSame(300000, (int) $pesanan->subtotal);
        $this->assertSame(8, $this->produk->refresh()->stok, 'Stok dipesan sebagai reservasi.');
        $this->assertSame(0, $this->pembeli->keranjangs()->count(), 'Keranjang dikosongkan.');

        $this->assertSame(1, $this->pembeli->notifications()->count());
        $this->assertSame(1, $this->admin->notifications()->count(), 'Admin diberi tahu ada pesanan baru.');

        // --- Pembeli mengunggah bukti bayar ---
        $this->actingAs($this->pembeli)
            ->post(route('pesanan.bayar', $pesanan), [
                'bukti' => UploadedFile::fake()->image('bukti.jpg'),
                'nama_pengirim' => 'Pembeli Uji',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('menunggu_konfirmasi', $pesanan->refresh()->status);
        $this->assertSame(2, $this->admin->notifications()->count());

        // --- Admin memverifikasi pembayaran ---
        $this->actingAs($this->admin)
            ->post(route('admin.pembayaran.verifikasi', $pesanan->pembayaran))
            ->assertSessionHasNoErrors();

        $pesanan->refresh();
        $this->assertSame('diproses', $pesanan->status);
        $this->assertSame('dibayar', $pesanan->pembayaran->status);
        $this->assertNotNull($pesanan->pengiriman, 'Data pengiriman dibuat saat pesanan diproses.');
        $this->assertSame(2, $this->pembeli->notifications()->count());

        // --- Admin memasukkan nomor resi ---
        $this->actingAs($this->admin)
            ->post(route('admin.pesanan.kirim', $pesanan), ['no_resi' => 'JNE00099'])
            ->assertSessionHasNoErrors();

        $pesanan->refresh();
        $this->assertSame('dikirim', $pesanan->status);
        $this->assertSame('JNE00099', $pesanan->pengiriman->no_resi);
        $this->assertSame(3, $this->pembeli->notifications()->count());

        $kabarKirim = $this->cariNotifikasi($this->pembeli, 'pesanan_dikirim');
        $this->assertNotNull($kabarKirim);
        $this->assertStringContainsString('JNE00099', $kabarKirim->data['pesan'],
            'Nomor resi ikut disampaikan ke pembeli.');

        // --- Pembeli mengonfirmasi penerimaan ---
        $this->actingAs($this->pembeli)
            ->post(route('pesanan.terima', $pesanan))
            ->assertSessionHasNoErrors();

        $pesanan->refresh();
        $this->assertSame('selesai', $pesanan->status);
        $this->assertSame('diterima', $pesanan->pengiriman->status);
        $this->assertSame(3, $this->admin->notifications()->count());

        // --- Invoice dapat dicetak ---
        $this->actingAs($this->pembeli)->get(route('pesanan.cetak', $pesanan->no_invoice))->assertOk();
    }

    public function test_pembayaran_ditolak_mengembalikan_pesanan_agar_bisa_unggah_ulang(): void
    {
        Storage::fake('uploads');
        $pesanan = $this->buatPesanan();

        $this->actingAs($this->pembeli)->post(route('pesanan.bayar', $pesanan), [
            'bukti' => UploadedFile::fake()->image('bukti.jpg'),
            'nama_pengirim' => 'Pembeli Uji',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.pembayaran.tolak', $pesanan->pembayaran), ['keterangan' => 'Nominal tidak sesuai.'])
            ->assertSessionHasNoErrors();

        $this->assertSame('menunggu_pembayaran', $pesanan->refresh()->status,
            'Pembeli harus bisa mengunggah bukti lagi.');

        $kabarTolak = $this->cariNotifikasi($this->pembeli, 'pembayaran_ditolak');
        $this->assertNotNull($kabarTolak, 'Pembeli harus diberi tahu alasan penolakan.');
        $this->assertStringContainsString('Nominal tidak sesuai', $kabarTolak->data['pesan']);
    }

    public function test_pembatalan_mengembalikan_stok(): void
    {
        $pesanan = $this->buatPesanan();
        $this->assertSame(8, $this->produk->refresh()->stok);

        $this->actingAs($this->pembeli)
            ->post(route('pesanan.batalkan', $pesanan))
            ->assertSessionHasNoErrors();

        $this->assertSame('dibatalkan', $pesanan->refresh()->status);
        $this->assertSame(10, $this->produk->refresh()->stok, 'Stok dikembalikan utuh.');
    }

    /**
     * Pesanan bisa berpindah ke "diproses" lewat dua jalur — verifikasi
     * pembayaran dan tombol proses. Keduanya membuat data pengiriman, dan
     * relasinya HasOne, jadi baris kedua akan menyembunyikan yang pertama.
     */
    public function test_data_pengiriman_tidak_terduplikasi(): void
    {
        Storage::fake('uploads');
        $pesanan = $this->buatPesanan();

        $this->actingAs($this->pembeli)->post(route('pesanan.bayar', $pesanan), [
            'bukti' => UploadedFile::fake()->image('bukti.jpg'),
            'nama_pengirim' => 'Pembeli Uji',
        ]);

        $this->actingAs($this->admin)->post(route('admin.pembayaran.verifikasi', $pesanan->pembayaran));
        $this->actingAs($this->admin)->post(route('admin.pesanan.proses', $pesanan));

        $this->assertSame(1, $pesanan->pengiriman()->count());
    }

    public function test_notifikasi_dapat_dibuka_dibaca_dan_dibersihkan(): void
    {
        $this->buatPesanan();

        $this->actingAs($this->pembeli)->get(route('notifikasi.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('notifikasi.index'))->assertOk();

        $this->actingAs($this->pembeli)
            ->getJson(route('notifikasi.data'))
            ->assertOk()
            ->assertJsonPath('belum_dibaca', 1)
            ->assertJsonStructure(['belum_dibaca', 'daftar' => [['id', 'judul', 'pesan', 'url', 'dibaca', 'waktu']]]);

        $notifikasi = $this->cariNotifikasi($this->pembeli, 'pesanan_dibuat');

        $this->actingAs($this->pembeli)
            ->get(route('notifikasi.baca', $notifikasi->id))
            ->assertRedirect($notifikasi->data['url']);

        $this->assertSame(0, $this->pembeli->unreadNotifications()->count());

        $this->actingAs($this->pembeli)
            ->delete(route('notifikasi.hapus-terbaca'))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, $this->pembeli->notifications()->count());
    }

    public function test_notifikasi_orang_lain_tidak_dapat_dibuka(): void
    {
        $this->buatPesanan();
        $milikAdmin = $this->admin->notifications()->first();

        $this->actingAs($this->pembeli)
            ->get(route('notifikasi.baca', $milikAdmin->id))
            ->assertNotFound();
    }
}
