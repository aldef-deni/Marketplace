<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CekGoogleSsoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://market.arahinn.com',
            'services.google.client_id' => '1234567890-abcdef.apps.googleusercontent.com',
            'services.google.client_secret' => 'GOCSPX-rahasia-uji',
            'services.google.redirect' => 'https://market.arahinn.com/auth/google/callback',
        ]);
    }

    /**
     * Google mengembalikan galat lewat pengalihan, bukan badan respons.
     * Nilai authError adalah protobuf ber-base64url.
     */
    private function pengalihanGalat(string $pesan): void
    {
        $sandi = rtrim(strtr(base64_encode("\x0a".chr(strlen($pesan)).$pesan), '+/', '-_'), '=');

        Http::fake([
            'accounts.google.com/*' => Http::response('<html>Moved Temporarily</html>', 302, [
                'Location' => 'https://accounts.google.com/signin/oauth/error?authError='.$sandi.'&flowName=GeneralOAuthFlow',
            ]),
        ]);
    }

    public function test_konfigurasi_kosong_gagal_sebelum_menghubungi_google(): void
    {
        Http::fake();
        config(['services.google.client_id' => '', 'services.google.client_secret' => '']);

        $this->artisan('google:cek')
            ->expectsOutputToContain('Konfigurasi belum lengkap')
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_redirect_uri_yang_tidak_cocok_dengan_app_url_terdeteksi(): void
    {
        Http::fake();
        config(['services.google.redirect' => 'http://localhost:8000/auth/google/callback']);

        $this->artisan('google:cek')
            ->expectsOutputToContain('tidak berawalan APP_URL')
            ->assertFailed();
    }

    public function test_client_id_tidak_dikenali_dilaporkan(): void
    {
        $this->pengalihanGalat('invalid_client The OAuth client was not found.');

        $this->artisan('google:cek')
            ->expectsOutputToContain('Client ID tidak dikenali')
            ->assertFailed();
    }

    public function test_redirect_uri_mismatch_dilaporkan(): void
    {
        $this->pengalihanGalat('redirect_uri_mismatch');

        $this->artisan('google:cek')
            ->expectsOutputToContain('redirect_uri_mismatch')
            ->assertFailed();
    }

    public function test_aplikasi_masih_berstatus_testing_dilaporkan(): void
    {
        $this->pengalihanGalat('access_blocked');

        $this->artisan('google:cek')
            ->expectsOutputToContain('Testing')
            ->assertFailed();
    }

    /**
     * Halaman "Moved Temporarily" tanpa authError berarti Google menerima
     * permintaannya — inilah kasus yang dulu keliru terbaca sebagai galat.
     */
    public function test_pengalihan_ke_halaman_masuk_dianggap_berhasil(): void
    {
        Http::fake([
            'accounts.google.com/*' => Http::response('<html>Moved Temporarily</html>', 302, [
                'Location' => 'https://accounts.google.com/v3/signin/identifier?opparams=abc',
            ]),
        ]);

        $this->artisan('google:cek')
            ->expectsOutputToContain('Google menerima Client ID')
            ->assertSuccessful();
    }

    public function test_opsi_tanpa_jaringan_melewati_panggilan_keluar(): void
    {
        Http::fake();

        $this->artisan('google:cek --tanpa-jaringan')->assertSuccessful();

        Http::assertNothingSent();
    }

    /**
     * Host sama tetapi skema berbeda: yang salah hampir pasti APP_URL yang
     * tertinggal di http, bukan redirect URI-nya. Petunjuknya harus mengarah
     * ke sana, bukan menyarankan menurunkan redirect URI ke http.
     */
    public function test_app_url_masih_http_diarahkan_untuk_dinaikkan_ke_https(): void
    {
        Http::fake();
        config(['app.url' => 'http://market.arahinn.com']);

        $this->artisan('google:cek')
            ->expectsOutputToContain('APP_URL memakai http://, redirect URI memakai https://')
            ->expectsOutputToContain('APP_URL=https://market.arahinn.com')
            ->assertFailed();

        Http::assertNothingSent();
    }
}
