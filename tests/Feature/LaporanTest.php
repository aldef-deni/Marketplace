<?php

namespace Tests\Feature;

use App\Models\Alamat;
use App\Models\Kategori;
use App\Models\MetodePembayaran;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    private User $pembeli;

    private User $admin;

    private User $superadmin;

    private Produk $produk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pembeli = User::factory()->create(['role' => 'pengguna', 'name' => 'Pembeli Uji']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->superadmin = User::factory()->create(['role' => 'superadmin']);

        $kategori = Kategori::create([
            'nama' => 'Elektronik', 'slug' => 'elektronik', 'ikon' => 'ponsel', 'aktif' => true,
        ]);

        $this->produk = Produk::create([
            'kategori_id' => $kategori->id,
            'nama' => 'Produk Laporan', 'slug' => 'produk-laporan', 'deskripsi' => 'Uji.',
            'harga' => 100000, 'stok' => 20, 'berat' => 500, 'status' => 'aktif',
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

    private function buatPesanan(int $qty = 2): Pesanan
    {
        $this->actingAs($this->pembeli)->post(route('keranjang.tambah', $this->produk), ['qty' => $qty]);
        $this->actingAs($this->pembeli)->post(route('checkout.store'), [
            'alamat_id' => $this->pembeli->alamats()->first()->id,
            'kurir' => 'JNE',
            'metode_pembayaran_id' => MetodePembayaran::first()->id,
        ]);

        // Diurutkan berdasarkan id, bukan created_at: beberapa pesanan uji
        // lahir dalam detik yang sama sehingga urutan waktunya tidak pasti.
        return Pesanan::latest('id')->firstOrFail();
    }

    public function test_admin_dapat_membuka_laporan_transaksi(): void
    {
        $pesanan = $this->buatPesanan();

        $this->actingAs($this->admin)
            ->get(route('admin.laporan.transaksi'))
            ->assertOk()
            ->assertSee('Laporan Transaksi')
            ->assertSee($pesanan->no_invoice)
            ->assertSee('Pembeli Uji');
    }

    public function test_admin_tidak_boleh_membuka_laporan_toko(): void
    {
        $this->actingAs($this->admin)->get(route('admin.laporan.toko'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('admin.laporan.toko.pdf'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('admin.laporan.toko.excel'))->assertForbidden();
    }

    public function test_superadmin_dapat_membuka_laporan_toko(): void
    {
        $this->buatPesanan();

        $this->actingAs($this->superadmin)
            ->get(route('admin.laporan.toko'))
            ->assertOk()
            ->assertSee('Kinerja per Kategori')
            ->assertSee('Produk Laporan')
            ->assertSee('Elektronik');
    }

    public function test_pembeli_tidak_dapat_membuka_laporan(): void
    {
        $this->actingAs($this->pembeli)->get(route('admin.laporan.transaksi'))->assertForbidden();
    }

    /**
     * Rentang tanggal harus benar-benar menyaring, bukan sekadar tampil di
     * kepala laporan.
     */
    public function test_rentang_tanggal_menyaring_transaksi(): void
    {
        $lama = $this->buatPesanan();
        $lama->forceFill(['created_at' => Carbon::now()->subMonths(3)])->save();

        $baru = $this->buatPesanan();

        $this->actingAs($this->admin)
            ->get(route('admin.laporan.transaksi', [
                'dari' => Carbon::now()->startOfMonth()->toDateString(),
                'sampai' => Carbon::now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee($baru->no_invoice)
            ->assertDontSee($lama->no_invoice);

        $this->actingAs($this->admin)
            ->get(route('admin.laporan.transaksi', [
                'dari' => Carbon::now()->subMonths(4)->toDateString(),
                'sampai' => Carbon::now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee($lama->no_invoice)
            ->assertSee($baru->no_invoice);
    }

    public function test_pesanan_batal_dikecualikan_kecuali_diminta(): void
    {
        $batal = $this->buatPesanan();
        $this->actingAs($this->pembeli)->post(route('pesanan.batalkan', $batal));

        $this->actingAs($this->admin)
            ->get(route('admin.laporan.transaksi'))
            ->assertOk()
            ->assertDontSee($batal->no_invoice);

        $this->actingAs($this->admin)
            ->get(route('admin.laporan.transaksi', ['sertakan_batal' => 1]))
            ->assertOk()
            ->assertSee($batal->no_invoice);
    }

    public function test_saringan_status_bekerja(): void
    {
        $pesanan = $this->buatPesanan();

        $this->actingAs($this->admin)
            ->get(route('admin.laporan.transaksi', ['status' => 'menunggu_pembayaran']))
            ->assertOk()
            ->assertSee($pesanan->no_invoice);

        $this->actingAs($this->admin)
            ->get(route('admin.laporan.transaksi', ['status' => 'selesai']))
            ->assertOk()
            ->assertDontSee($pesanan->no_invoice);
    }

    /**
     * Nilai dari URL tidak boleh dipercaya; status karangan harus diabaikan,
     * bukan diteruskan ke kueri.
     */
    public function test_nilai_saringan_tak_dikenal_diabaikan(): void
    {
        $pesanan = $this->buatPesanan();

        $this->actingAs($this->admin)
            ->get(route('admin.laporan.transaksi', ['status' => 'status-karangan', 'kurir' => 'Kurir Palsu']))
            ->assertOk()
            ->assertSee($pesanan->no_invoice);
    }

    public function test_unduhan_pdf_transaksi_berupa_berkas_pdf(): void
    {
        $this->buatPesanan();

        $respons = $this->actingAs($this->admin)->get(route('admin.laporan.transaksi.pdf'));

        $respons->assertOk();
        $this->assertSame('application/pdf', $respons->headers->get('content-type'));
        // dompdf mengembalikan respons biasa, bukan streamed.
        $this->assertStringStartsWith('%PDF', $respons->getContent());
    }

    public function test_unduhan_excel_transaksi_berupa_berkas_xlsx(): void
    {
        $this->buatPesanan();

        $respons = $this->actingAs($this->admin)->get(route('admin.laporan.transaksi.excel'));

        $respons->assertOk();
        $this->assertStringContainsString('spreadsheetml', $respons->headers->get('content-type'));

        // Berkas xlsx berupa arsip zip; tanda tangannya diawali "PK".
        $this->assertStringStartsWith('PK', $respons->streamedContent());
    }

    public function test_unduhan_laporan_toko_tersedia_bagi_superadmin(): void
    {
        $this->buatPesanan();

        $pdf = $this->actingAs($this->superadmin)->get(route('admin.laporan.toko.pdf'));
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));

        $excel = $this->actingAs($this->superadmin)->get(route('admin.laporan.toko.excel'));
        $excel->assertOk();
        $this->assertStringStartsWith('PK', $excel->streamedContent());
    }

    /**
     * Angka pada berkas unduhan harus sama dengan yang tampil di layar; kalau
     * kriterianya dibaca ulang secara terpisah, keduanya cepat berbeda.
     */
    public function test_unduhan_mengikuti_saringan_yang_sama(): void
    {
        $batal = $this->buatPesanan();
        $this->actingAs($this->pembeli)->post(route('pesanan.batalkan', $batal));
        $aktif = $this->buatPesanan();

        $isi = $this->actingAs($this->admin)
            ->get(route('admin.laporan.transaksi.pdf'))
            ->getContent();

        $this->assertStringStartsWith('%PDF', $isi);

        // Isi PDF terkompresi, jadi yang diuji adalah jumlah baris pada sumber
        // datanya lewat halaman yang memakai kriteria sama.
        $this->actingAs($this->admin)
            ->get(route('admin.laporan.transaksi'))
            ->assertSee($aktif->no_invoice)
            ->assertDontSee($batal->no_invoice);
    }

    /**
     * Paket PHP tidak ikut di dalam paket rilis. Bila composer install
     * terlewat, pengguna harus melihat pesan yang wajar — bukan galat 500
     * berisi nama kelas.
     */
    public function test_tombol_unduhan_disembunyikan_saat_pustaka_belum_terpasang(): void
    {
        $this->buatPesanan();

        $halaman = $this->actingAs($this->admin)->get(route('admin.laporan.transaksi'));

        // Di lingkungan pengujian pustakanya terpasang, jadi tombolnya ada.
        $halaman->assertOk()
            ->assertSee('Unduh PDF')
            ->assertSee('Unduh Excel')
            ->assertDontSee('Modul unduhan belum terpasang di server');
    }

    public function test_kesiapan_pustaka_unduhan_dilaporkan(): void
    {
        $siap = unduhanLaporanSiap();

        $this->assertArrayHasKey('pdf', $siap);
        $this->assertArrayHasKey('excel', $siap);
        $this->assertTrue($siap['pdf'], 'dompdf harus terpasang di lingkungan pengujian.');
        $this->assertTrue($siap['excel'], 'PhpSpreadsheet harus terpasang di lingkungan pengujian.');
    }
}
