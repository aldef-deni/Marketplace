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
            ->assertSessionHasErrors('warna');
    }

    public function test_hanya_superadmin_yang_dapat_mengelola(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.metode-pembayaran.index'))->assertForbidden();
    }
}
