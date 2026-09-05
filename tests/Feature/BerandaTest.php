<?php

namespace Tests\Feature;

use Database\Seeders\KategoriSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BerandaTest extends TestCase
{
    use RefreshDatabase;

    public function test_beranda_dapat_diakses(): void
    {
        $this->seed(KategoriSeeder::class);

        $this->get('/')->assertOk();
    }

    public function test_halaman_publik_memakai_identitas_market_arahinn(): void
    {
        $this->seed(KategoriSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertSee('Market ArahInn', escape: false)
            ->assertSee('images/logo-landscape-160', escape: false)
            ->assertDontSee('Marketplace Nusantara');
    }

    public function test_katalog_dan_autentikasi_dapat_diakses(): void
    {
        $this->seed(KategoriSeeder::class);

        $this->get('/toko')->assertOk();
        $this->get('/login')->assertOk()->assertSee('Market ArahInn', escape: false);
        $this->get('/register')->assertOk();
    }

    /**
     * Config cache lama di server bisa belum memuat kunci yang baru
     * ditambahkan, sehingga config() mengembalikan null. Footer tampil di
     * setiap halaman, jadi kasus itu tidak boleh menjatuhkan seluruh situs.
     */
    public function test_footer_tetap_tampil_saat_kunci_config_belum_ada(): void
    {
        $this->seed(KategoriSeeder::class);
        config(['brand.metode_bayar' => null]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Seluruh hak cipta dilindungi', escape: false);
    }
}
