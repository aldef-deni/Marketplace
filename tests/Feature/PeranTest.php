<?php

namespace Tests\Feature;

use App\Models\Toko;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Batas antar-peran.
 *
 * Tiga peran, dan hanya tiga: superadmin memegang platform, admin memiliki
 * satu toko, pengguna membeli. Pengujian ini menyebut setiap rute platform satu
 * per satu — bukan mencontoh sebagian — karena satu rute yang terlewat berarti
 * pemilik toko dapat membuka pesanan, pembayaran, atau laporan lapak lain.
 */
class PeranTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    private User $pemilikToko;

    private User $pembeli;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::factory()->create(['role' => 'superadmin']);
        $this->pemilikToko = User::factory()->create(['role' => 'admin']);
        $this->pembeli = User::factory()->create(['role' => 'pengguna']);

        Toko::create([
            'user_id' => $this->pemilikToko->id,
            'nama' => 'Lapak Uji',
            'slug' => 'lapak-uji',
            'status' => 'aktif',
            'disetujui_at' => now(),
        ]);
    }

    /**
     * Seluruh kendali yang hanya boleh disentuh administrator platform.
     */
    private function rutaPlatform(): array
    {
        return [
            route('admin.dashboard'),
            route('admin.pesanan.index'),
            route('admin.pembayaran.index'),
            route('admin.pengiriman.index'),
            route('admin.kategori.index'),
            route('admin.laporan.transaksi'),
            route('admin.pengguna.index'),
            route('admin.metode-pembayaran.index'),
            route('admin.flash-sale.kampanye.index'),
        ];
    }

    public function test_pemilik_toko_ditolak_dari_seluruh_kendali_platform(): void
    {
        foreach ($this->rutaPlatform() as $url) {
            $this->actingAs($this->pemilikToko)->get($url)
                ->assertForbidden("Pemilik toko seharusnya ditolak di {$url}");
        }
    }

    public function test_pembeli_ditolak_dari_seluruh_kendali_platform(): void
    {
        foreach ($this->rutaPlatform() as $url) {
            $this->actingAs($this->pembeli)->get($url)
                ->assertForbidden("Pembeli seharusnya ditolak di {$url}");
        }
    }

    public function test_superadmin_dapat_membuka_seluruh_kendali_platform(): void
    {
        foreach ($this->rutaPlatform() as $url) {
            $this->actingAs($this->superadmin)->get($url)
                ->assertOk("Superadmin seharusnya dapat membuka {$url}");
        }
    }

    /* ---------- Wewenang pemilik toko ---------- */

    public function test_pemilik_toko_dapat_membuka_panel_lapaknya(): void
    {
        foreach ([
            route('admin.toko.index'),
            route('admin.produk.index'),
            route('admin.promo.index'),
            route('admin.flash-sale.index'),
            route('admin.promo.kampanye.index'),
        ] as $url) {
            $this->actingAs($this->pemilikToko)->get($url)
                ->assertOk("Pemilik toko seharusnya dapat membuka {$url}");
        }
    }

    public function test_pembeli_tidak_punya_panel_sama_sekali(): void
    {
        $this->actingAs($this->pembeli)->get(route('admin.produk.index'))->assertForbidden();
        $this->actingAs($this->pembeli)->get(route('admin.toko.index'))->assertForbidden();
    }

    public function test_tautan_lihat_toko_hanya_untuk_pemilik_lapak(): void
    {
        $superadmin = $this->actingAs($this->superadmin)
            ->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Lihat Toko', $superadmin,
            'Superadmin tidak punya lapak untuk dilihat.');

        // Pemilik toko mendapat tautan ke lapaknya sendiri, bukan ke beranda.
        $pemilik = $this->actingAs($this->pemilikToko)
            ->get(route('admin.produk.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Lihat Toko Saya', $pemilik);
        $this->assertStringContainsString(route('toko.show', 'lapak-uji'), $pemilik);
    }

    /* ---------- Pengalihan sesudah masuk ---------- */

    public function test_setiap_peran_mendarat_di_halaman_yang_dapat_dibukanya(): void
    {
        // Pemilik toko yang diarahkan ke dashboard platform akan disambut 403
        // tepat sesudah berhasil masuk.
        $this->actingAs($this->superadmin)->get(route('dashboard'))
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($this->pemilikToko)->get(route('dashboard'))
            ->assertRedirect(route('admin.produk.index'));

        $this->actingAs($this->pembeli)->get(route('dashboard'))->assertOk();
    }

    /* ---------- Batas daftar ---------- */

    public function test_daftar_transaksi_dibatasi_dua_puluh_baris_per_halaman(): void
    {
        $pembeli = User::factory()->create(['role' => 'pengguna']);

        $alamat = \App\Models\Alamat::create([
            'user_id' => $pembeli->id, 'label' => 'Rumah',
            'nama_penerima' => 'Uji', 'no_hp' => '0812',
            'provinsi' => 'Jabar', 'kota' => 'Bekasi', 'kecamatan' => 'Pondok Gede',
            'kode_pos' => '17412', 'alamat_lengkap' => 'Jl. Uji', 'is_default' => true,
        ]);

        // Dua puluh lima pesanan: dua puluh di halaman pertama, lima sisanya
        // wajib pindah — daftar ini memuat transaksi seluruh toko sekaligus.
        for ($i = 1; $i <= 25; $i++) {
            \App\Models\Pesanan::create([
                'no_invoice' => sprintf('INV-UJI-%04d', $i),
                'user_id' => $pembeli->id,
                'alamat_id' => $alamat->id,
                'subtotal' => 100000, 'ongkir' => 10000, 'total' => 110000,
                'status' => 'selesai', 'kurir' => 'JNE', 'layanan_kurir' => 'REG',
            ]);
        }

        $satu = $this->actingAs($this->superadmin)
            ->get(route('admin.pesanan.index'))->assertOk()->getContent();

        $this->assertSame(20, substr_count($satu, 'INV-UJI-'));

        $dua = $this->actingAs($this->superadmin)
            ->get(route('admin.pesanan.index', ['page' => 2]))->assertOk()->getContent();

        $this->assertSame(5, substr_count($dua, 'INV-UJI-'));
    }

    /* ---------- Kebersihan data ---------- */

    public function test_tidak_ada_peran_selain_ketiganya(): void
    {
        $peran = DB::table('users')->distinct()->pluck('role')->all();

        $this->assertEmpty(
            array_diff($peran, ['superadmin', 'admin', 'pengguna']),
            'Kolom peran hanya boleh berisi superadmin, admin, atau pengguna.',
        );
    }

    public function test_pengelola_mencakup_superadmin_dan_pemilik_toko_saja(): void
    {
        $this->assertTrue($this->superadmin->isPengelola());
        $this->assertTrue($this->pemilikToko->isPengelola());
        $this->assertFalse($this->pembeli->isPengelola());

        $this->assertTrue($this->pemilikToko->isPemilikToko());
        $this->assertFalse($this->superadmin->isPemilikToko());
    }
}
