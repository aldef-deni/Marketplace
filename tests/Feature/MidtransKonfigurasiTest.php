<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Pembacaan config/midtrans.php dari .env.
 *
 * Dibuat setelah kejadian nyata: kunci produksi sudah terpasang di server,
 * tetapi panel tetap berkata "Midtrans belum disetel" karena saklar terpisah
 * MIDTRANS_AKTIF tidak ikut ditulis, dan mode produksi ditulis dengan nama
 * MIDTRANS_IS_PRODUCTION mengikuti kebiasaan ArahInn.
 *
 * Berkas confignya dimuat ulang apa adanya di sini, bukan lewat config(),
 * supaya yang diuji benar-benar logika pembacaan env — bukan nilai yang
 * terlanjur di-cache kerangka kerja.
 */
class MidtransKonfigurasiTest extends TestCase
{
    private const KUNCI_ENV = [
        'MIDTRANS_SERVER_KEY',
        'MIDTRANS_CLIENT_KEY',
        'MIDTRANS_AKTIF',
        'MIDTRANS_PRODUKSI',
        'MIDTRANS_IS_PRODUCTION',
    ];

    protected function tearDown(): void
    {
        $this->bersihkanEnv();

        parent::tearDown();
    }

    private function bersihkanEnv(): void
    {
        foreach (self::KUNCI_ENV as $kunci) {
            putenv($kunci);
            unset($_ENV[$kunci], $_SERVER[$kunci]);
        }
    }

    /**
     * @param  array<string, string>  $env
     * @return array<string, mixed>
     */
    private function muat(array $env): array
    {
        $this->bersihkanEnv();

        foreach ($env as $kunci => $nilai) {
            putenv("{$kunci}={$nilai}");
            $_ENV[$kunci] = $nilai;
        }

        return require config_path('midtrans.php');
    }

    public function test_env_server_yang_sebenarnya_menyalakan_gerbang(): void
    {
        // Persis bentuk .env di server: dua kunci produksi dan
        // MIDTRANS_IS_PRODUCTION, tanpa MIDTRANS_AKTIF sama sekali.
        $config = $this->muat([
            'MIDTRANS_SERVER_KEY' => 'Mid-server-CONTOH',
            'MIDTRANS_CLIENT_KEY' => 'Mid-client-CONTOH',
            'MIDTRANS_IS_PRODUCTION' => 'true',
        ]);

        $this->assertTrue($config['aktif'], 'Kunci yang sudah terisi seharusnya cukup untuk menyalakan.');
        $this->assertTrue($config['produksi']);
    }

    public function test_kunci_saja_sudah_cukup_tanpa_saklar_apa_pun(): void
    {
        $config = $this->muat([
            'MIDTRANS_SERVER_KEY' => 'Mid-server-CONTOH',
            'MIDTRANS_CLIENT_KEY' => 'Mid-client-CONTOH',
        ]);

        $this->assertTrue($config['aktif']);
        // Kunci tanpa awalan SB- hanya berarti satu hal.
        $this->assertTrue($config['produksi']);
    }

    public function test_kunci_sandbox_disimpulkan_sebagai_sandbox(): void
    {
        $config = $this->muat([
            'MIDTRANS_SERVER_KEY' => 'SB-Mid-server-CONTOH',
            'MIDTRANS_CLIENT_KEY' => 'SB-Mid-client-CONTOH',
        ]);

        $this->assertTrue($config['aktif']);
        $this->assertFalse($config['produksi']);
    }

    public function test_tanpa_kunci_gerbang_tetap_mati(): void
    {
        $this->assertFalse($this->muat([])['aktif']);

        // Satu kunci saja tidak cukup — Snap menolak permintaan tanpa keduanya.
        $this->assertFalse($this->muat(['MIDTRANS_SERVER_KEY' => 'Mid-server-CONTOH'])['aktif']);
        $this->assertFalse($this->muat(['MIDTRANS_CLIENT_KEY' => 'Mid-client-CONTOH'])['aktif']);
    }

    public function test_saklar_mati_tetap_dihormati_walau_kuncinya_ada(): void
    {
        // Satu-satunya alasan menulis MIDTRANS_AKTIF: mematikan gerbang tanpa
        // harus menghapus kuncinya dari .env.
        $config = $this->muat([
            'MIDTRANS_SERVER_KEY' => 'Mid-server-CONTOH',
            'MIDTRANS_CLIENT_KEY' => 'Mid-client-CONTOH',
            'MIDTRANS_AKTIF' => 'false',
        ]);

        $this->assertFalse($config['aktif']);
    }

    public function test_mode_dapat_dipaksa_berbeda_dari_kuncinya(): void
    {
        $config = $this->muat([
            'MIDTRANS_SERVER_KEY' => 'Mid-server-CONTOH',
            'MIDTRANS_CLIENT_KEY' => 'Mid-client-CONTOH',
            'MIDTRANS_PRODUKSI' => 'false',
        ]);

        // Dipaksa sandbox meski kuncinya produksi. Sengaja dibiarkan mungkin —
        // sistem:cek yang akan menegur ketidakcocokannya.
        $this->assertFalse($config['produksi']);
    }

    public function test_nama_produksi_versi_arahinn_ikut_dibaca(): void
    {
        $config = $this->muat([
            'MIDTRANS_SERVER_KEY' => 'SB-Mid-server-CONTOH',
            'MIDTRANS_CLIENT_KEY' => 'SB-Mid-client-CONTOH',
            'MIDTRANS_IS_PRODUCTION' => 'true',
        ]);

        $this->assertTrue($config['produksi'], 'MIDTRANS_IS_PRODUCTION seharusnya ikut dihormati.');
    }

    public function test_nama_lokal_menang_atas_nama_arahinn(): void
    {
        $config = $this->muat([
            'MIDTRANS_SERVER_KEY' => 'Mid-server-CONTOH',
            'MIDTRANS_CLIENT_KEY' => 'Mid-client-CONTOH',
            'MIDTRANS_PRODUKSI' => 'false',
            'MIDTRANS_IS_PRODUCTION' => 'true',
        ]);

        $this->assertFalse($config['produksi']);
    }
}
