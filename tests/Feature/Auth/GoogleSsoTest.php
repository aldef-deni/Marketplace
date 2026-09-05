<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as PenggunaGoogle;
use Mockery;
use Tests\TestCase;

class GoogleSsoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'uji-client-id',
            'services.google.client_secret' => 'uji-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);
    }

    /**
     * Pasang jawaban palsu dari Google agar alur callback bisa diuji tanpa
     * benar-benar menghubungi server Google.
     */
    private function palsukanGoogle(
        string $id = '1122334455',
        string $email = 'budi@gmail.com',
        string $nama = 'Budi Santoso',
        bool $terverifikasi = true,
    ): void {
        $akun = new PenggunaGoogle;
        $akun->id = $id;
        $akun->name = $nama;
        $akun->email = $email;
        $akun->avatar = 'https://lh3.googleusercontent.com/contoh';
        $akun->user = ['email_verified' => $terverifikasi];

        $penyedia = Mockery::mock(Provider::class);
        $penyedia->shouldReceive('user')->andReturn($akun);

        Socialite::shouldReceive('driver')->with('google')->andReturn($penyedia);
    }

    public function test_tombol_google_tampil_saat_kredensial_terisi(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk dengan Google');
        $this->get('/register')->assertOk()->assertSee('Daftar dengan Google');
    }

    public function test_tombol_google_disembunyikan_saat_kredensial_kosong(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);

        $this->get('/login')->assertOk()->assertDontSee('Masuk dengan Google');
    }

    public function test_rute_redirect_menolak_saat_kredensial_kosong(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);

        $this->get(route('google.redirect'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
    }

    public function test_pengguna_baru_dibuat_dan_langsung_masuk(): void
    {
        $this->palsukanGoogle();

        $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

        $pengguna = User::where('email', 'budi@gmail.com')->firstOrFail();

        $this->assertAuthenticatedAs($pengguna);
        $this->assertSame('1122334455', $pengguna->google_id);
        $this->assertSame('pengguna', $pengguna->role);
        $this->assertNotNull($pengguna->email_verified_at, 'Email dari Google sudah terverifikasi.');
        $this->assertNull($pengguna->password, 'Akun SSO tidak diberi kata sandi acak.');
    }

    public function test_akun_lama_dengan_email_sama_ditautkan_bukan_diduplikasi(): void
    {
        $lama = User::factory()->create([
            'email' => 'budi@gmail.com',
            'name' => 'Budi Lama',
            'password' => Hash::make('rahasia123'),
        ]);

        $this->palsukanGoogle();
        $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

        $this->assertSame(1, User::where('email', 'budi@gmail.com')->count());

        $lama->refresh();
        $this->assertSame('1122334455', $lama->google_id);
        $this->assertSame('Budi Lama', $lama->name, 'Nama yang sudah disunting sendiri tidak ditimpa.');
        $this->assertAuthenticatedAs($lama);
    }

    public function test_email_google_yang_belum_terverifikasi_ditolak(): void
    {
        $this->palsukanGoogle(terverifikasi: false);

        $this->get(route('google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
        $this->assertSame(0, User::where('email', 'budi@gmail.com')->count());
    }

    public function test_pembatalan_di_layar_google_tidak_dianggap_galat_sistem(): void
    {
        $this->get(route('google.callback', ['error' => 'access_denied']))
            ->assertRedirect(route('login'))
            ->assertSessionHas('info');

        $this->assertGuest();
    }

    public function test_akun_sso_dapat_membuat_kata_sandi_tanpa_sandi_lama(): void
    {
        $pengguna = User::factory()->create(['password' => null, 'google_id' => '999']);

        $this->actingAs($pengguna)
            ->from(route('profile.edit'))
            ->put(route('password.update'), [
                'password' => 'SandiBaru#2026',
                'password_confirmation' => 'SandiBaru#2026',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertTrue(Hash::check('SandiBaru#2026', $pengguna->refresh()->password));
    }

    public function test_akun_sso_menghapus_akun_dengan_konfirmasi_email(): void
    {
        $pengguna = User::factory()->create([
            'email' => 'hapus@gmail.com',
            'password' => null,
            'google_id' => '777',
        ]);

        $this->actingAs($pengguna)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), ['email_konfirmasi' => 'salah@gmail.com'])
            ->assertSessionHasErrorsIn('userDeletion');

        $this->assertNotNull($pengguna->fresh());

        $this->actingAs($pengguna)
            ->delete(route('profile.destroy'), ['email_konfirmasi' => 'hapus@gmail.com'])
            ->assertRedirect('/');

        $this->assertNull($pengguna->fresh());
        $this->assertGuest();
    }

    /**
     * Reproduksi kegagalan di server: cache rute masih versi lama sehingga
     * google.redirect belum terdaftar, sementara kredensialnya sudah terisi.
     * Halaman masuk harus tetap tampil, hanya tanpa tombolnya.
     */
    public function test_halaman_masuk_tetap_tampil_saat_rute_sso_belum_terdaftar(): void
    {
        \Illuminate\Support\Facades\Route::setRoutes(
            tap(new \Illuminate\Routing\RouteCollection, function ($koleksi) {
                foreach (\Illuminate\Support\Facades\Route::getRoutes() as $rute) {
                    if ($rute->getName() !== 'google.redirect') {
                        $koleksi->add($rute);
                    }
                }
            })
        );

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('Masuk dengan Google');
    }
}
