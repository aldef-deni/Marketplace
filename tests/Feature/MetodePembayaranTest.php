<?php

namespace Tests\Feature;

use App\Models\MetodePembayaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Metode pembayaran punya satu aturan yang menentukan tiga tempat sekaligus:
 * pilihan di checkout, lencana di footer, dan penanda di panel. Pengujian ini
 * menjaga ketiganya tetap membaca aturan yang sama.
 */
class MetodePembayaranTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::factory()->create(['role' => 'superadmin']);
    }

    private function buat(array $ubah = []): MetodePembayaran
    {
        return MetodePembayaran::create(array_merge([
            'nama' => 'Transfer Bank BCA',
            'label_pendek' => 'BCA',
            'tipe' => 'transfer',
            'nomor_rekening' => '8830 1234 5678',
            'atas_nama' => 'Market ArahInn',
            'warna' => '#0060AF',
            'aktif' => true,
        ], $ubah));
    }

    /* ---------- Aturan siap dipakai ---------- */

    public function test_metode_tanpa_nomor_tidak_dianggap_siap(): void
    {
        $terisi = $this->buat();
        $kosong = $this->buat(['nama' => 'Transfer Bank BNI', 'label_pendek' => 'BNI', 'nomor_rekening' => null]);

        $this->assertTrue($terisi->siapDipakai());
        $this->assertFalse($kosong->siapDipakai());
        $this->assertSame('Nomor belum diisi', $kosong->alasan_belum_tampil);

        $this->assertSame([$terisi->id], MetodePembayaran::siap()->pluck('id')->all());
    }

    public function test_cod_tetap_siap_walau_tanpa_nomor(): void
    {
        // COD memang tidak punya nomor tujuan; menyaringnya sama seperti metode
        // lain akan membuang satu-satunya cara bayar yang tidak butuh rekening.
        $cod = $this->buat([
            'nama' => 'COD', 'label_pendek' => 'COD', 'tipe' => 'cod', 'nomor_rekening' => null,
        ]);

        $this->assertTrue($cod->siapDipakai());
    }

    public function test_metode_nonaktif_tidak_siap_meski_nomornya_terisi(): void
    {
        $mati = $this->buat(['aktif' => false]);

        $this->assertFalse($mati->siapDipakai());
        $this->assertSame('Dinonaktifkan', $mati->alasan_belum_tampil);
    }

    /* ---------- Lencana footer ---------- */

    public function test_lencana_footer_hanya_memuat_metode_yang_siap(): void
    {
        $this->buat();
        $this->buat(['nama' => 'Transfer Bank BNI', 'label_pendek' => 'BNI', 'nomor_rekening' => null]);
        $this->buat(['nama' => 'GoPay', 'label_pendek' => 'GoPay', 'tipe' => 'ewallet', 'nomor_rekening' => '0812']);

        $this->get('/')
            ->assertOk()
            ->assertSee('BCA')
            ->assertSee('GoPay')
            ->assertDontSee('BNI');
    }

    public function test_mengosongkan_nomor_menghilangkan_lencananya_dari_footer(): void
    {
        $metode = $this->buat(['nama' => 'DANA', 'label_pendek' => 'DANA', 'tipe' => 'ewallet']);

        $this->get('/')->assertOk()->assertSee('DANA');

        $this->actingAs($this->superadmin)->patch(route('admin.metode-pembayaran.update', $metode), [
            'nama' => 'DANA',
            'label_pendek' => 'DANA',
            'tipe' => 'ewallet',
            'nomor_rekening' => '',
            'aktif' => '1',
        ])->assertSessionHasNoErrors();

        $this->get('/')->assertOk()->assertDontSee('DANA');
    }

    /* ---------- Checkout ---------- */

    public function test_metode_tanpa_nomor_tidak_ditawarkan_di_checkout(): void
    {
        $siap = $this->buat();
        $kosong = $this->buat(['nama' => 'Transfer Bank BNI', 'label_pendek' => 'BNI', 'nomor_rekening' => null]);

        $ditawarkan = MetodePembayaran::siap()->pluck('id');

        $this->assertTrue($ditawarkan->contains($siap->id));
        $this->assertFalse($ditawarkan->contains($kosong->id));
    }

    /* ---------- Panel ---------- */

    public function test_halaman_panel_menandai_metode_yang_belum_tampil(): void
    {
        $this->buat();
        $this->buat(['nama' => 'Transfer Bank BNI', 'label_pendek' => 'BNI', 'nomor_rekening' => null]);

        $this->actingAs($this->superadmin)
            ->get(route('admin.metode-pembayaran.index'))
            ->assertOk()
            ->assertSee('Tampil di checkout')
            ->assertSee('Nomor belum diisi');
    }

    public function test_menyunting_satu_kartu_tidak_menuntut_mengirim_ulang_tipenya(): void
    {
        $metode = $this->buat();

        // Formulir per baris tidak menampilkan pilihan tipe — tipenya sudah
        // melekat pada metode itu. Mewajibkannya membuat setiap penyimpanan
        // gagal diam-diam.
        $this->actingAs($this->superadmin)
            ->from(route('admin.metode-pembayaran.index'))
            ->patch(route('admin.metode-pembayaran.update', $metode), [
                'nama' => 'Bank BCA',
                'nomor_rekening' => '8830 1234 5678',
                'warna' => '#123456',
                'aktif' => '1',
            ])
            ->assertSessionHasNoErrors();

        $metode->refresh();

        $this->assertSame('Bank BCA', $metode->nama);
        $this->assertSame('#123456', $metode->warna);
        $this->assertSame('transfer', $metode->tipe, 'Tipe lama harus dipertahankan.');
    }

    public function test_gagal_menyunting_satu_kartu_tidak_mengubah_isian_kartu_lain(): void
    {
        $bca = $this->buat();
        $bni = $this->buat(['nama' => 'Transfer Bank BNI', 'label_pendek' => 'BNI', 'warna' => '#F05A22']);

        // Warna tidak sah pada satu kartu; kartu lain tidak boleh ikut
        // menampilkan nilai yang barusan diketik di kartu yang gagal.
        $halaman = $this->actingAs($this->superadmin)
            ->from(route('admin.metode-pembayaran.index'))
            ->patch(route('admin.metode-pembayaran.update', $bca), [
                'nama' => 'Bank BCA',
                'warna' => 'bukan-warna',
            ])
            ->assertRedirect(route('admin.metode-pembayaran.index'));

        $isi = $this->actingAs($this->superadmin)
            ->get(route('admin.metode-pembayaran.index'))
            ->assertOk()
            ->getContent();

        // Kartu yang gagal memang menyimpan isiannya — itu gunanya old(). Yang
        // dibuktikan di sini: nilainya muncul tepat sekali, tidak menular.
        $this->assertSame(1, substr_count($isi, 'value="Bank BCA"'),
            'Nama yang gagal disimpan hanya boleh muncul di kartunya sendiri.');

        $this->assertStringContainsString('value="Transfer Bank BNI"', $isi,
            'Kartu lain harus tetap menampilkan nilainya sendiri.');

        $this->assertStringContainsString('#F05A22', $isi,
            'Warna kartu lain harus tetap seperti tersimpan.');
    }

    public function test_warna_harus_berupa_kode_heksadesimal(): void
    {
        $metode = $this->buat();

        // Warna dipakai langsung sebagai nilai CSS di lencana footer, jadi
        // nilai sembarang tidak boleh lolos ke sana.
        $this->actingAs($this->superadmin)
            ->patch(route('admin.metode-pembayaran.update', $metode), [
                'nama' => 'Transfer Bank BCA',
                'tipe' => 'transfer',
                'warna' => 'merah; background: url(x)',
            ])
            // Galat dikantongi per metode supaya kartu lain tidak ikut menyala.
            ->assertSessionHasErrors('warna', null, 'metode'.$metode->id);
    }

    public function test_hanya_superadmin_yang_dapat_mengelola(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.metode-pembayaran.index'))->assertForbidden();
    }
}
