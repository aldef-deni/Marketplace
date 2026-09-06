<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AkunArahInn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Jembatan akun ke sistem induk ArahInn.
 *
 * Basis data induk ditiru dengan koneksi SQLite terpisah supaya pengujiannya
 * tidak menuntut server MySQL kedua, namun tetap melewati jalur kode yang sama.
 */
class AkunArahInnTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.arahinn' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        Schema::connection('arahinn')->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
        });
    }

    private function akunInduk(array $ubah = []): void
    {
        DB::connection('arahinn')->table('users')->insert(array_merge([
            'name' => 'Budi ArahInn',
            'email' => 'budi@arahinn.com',
            'password' => Hash::make('rahasia123'),
            'phone' => '081234567890',
            'is_active' => true,
            'email_verified_at' => now(),
        ], $ubah));
    }

    /* ---------- Jalur berhasil ---------- */

    public function test_akun_arahinn_dapat_masuk_tanpa_mendaftar(): void
    {
        $this->akunInduk();

        $this->post(route('login'), [
            'email' => 'budi@arahinn.com',
            'password' => 'rahasia123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();

        // Akunnya disalin sekali; sesudah ini ia pengguna biasa di marketplace.
        $this->assertDatabaseHas('users', [
            'email' => 'budi@arahinn.com',
            'name' => 'Budi ArahInn',
            'role' => 'pengguna',
        ]);
    }

    public function test_surel_yang_sudah_disahkan_di_induk_tidak_diminta_verifikasi_ulang(): void
    {
        $this->akunInduk();

        AkunArahInn::masuk('budi@arahinn.com', 'rahasia123');

        $this->assertNotNull(User::where('email', 'budi@arahinn.com')->first()->email_verified_at);
    }

    public function test_peran_selalu_pengguna_apa_pun_jabatannya_di_induk(): void
    {
        // Kolom peran di induk sengaja tidak dibaca sama sekali; menyalinnya
        // berarti menyerahkan panel marketplace pada keputusan sistem lain.
        $this->akunInduk(['email' => 'bos@arahinn.com']);

        $pengguna = AkunArahInn::masuk('bos@arahinn.com', 'rahasia123');

        $this->assertSame('pengguna', $pengguna->role);
    }

    /* ---------- Jalur ditolak ---------- */

    public function test_kata_sandi_salah_tetap_ditolak(): void
    {
        $this->akunInduk();

        $this->assertNull(AkunArahInn::masuk('budi@arahinn.com', 'salah'));
        $this->assertDatabaseMissing('users', ['email' => 'budi@arahinn.com']);
    }

    public function test_akun_induk_yang_dinonaktifkan_tidak_dapat_masuk(): void
    {
        $this->akunInduk(['is_active' => false]);

        $this->assertNull(AkunArahInn::masuk('budi@arahinn.com', 'rahasia123'));
    }

    public function test_akun_oauth_tanpa_kata_sandi_tidak_dapat_masuk(): void
    {
        // Akun ini mendaftar lewat Google di induk; tidak ada kata sandi yang
        // bisa dicocokkan, dan pemiliknya memang harus memakai tombol Google.
        $this->akunInduk(['password' => null]);

        $this->assertNull(AkunArahInn::masuk('budi@arahinn.com', 'apa pun'));
    }

    /* ---------- Ketahanan ---------- */

    public function test_pengguna_lokal_tetap_masuk_saat_induk_tidak_dapat_dihubungi(): void
    {
        config(['database.connections.arahinn.database' => '/jalur/tidak/ada.sqlite']);

        $lokal = User::factory()->create(['password' => Hash::make('kata-sandi-lokal')]);

        $this->post(route('login'), [
            'email' => $lokal->email,
            'password' => 'kata-sandi-lokal',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_jembatan_mati_saat_databasenya_tidak_disetel(): void
    {
        config(['database.connections.arahinn.database' => null]);

        $this->assertFalse(AkunArahInn::aktif());
        $this->assertNull(AkunArahInn::masuk('budi@arahinn.com', 'rahasia123'));
    }

    /* ---------- Penyelarasan kata sandi ---------- */

    public function test_kata_sandi_yang_berganti_di_induk_ikut_diperbarui(): void
    {
        $this->akunInduk();
        AkunArahInn::masuk('budi@arahinn.com', 'rahasia123');

        // Pemiliknya mengganti kata sandi di sistem induk.
        DB::connection('arahinn')->table('users')
            ->where('email', 'budi@arahinn.com')
            ->update(['password' => Hash::make('rahasia-baru')]);

        $this->post(route('login'), [
            'email' => 'budi@arahinn.com',
            'password' => 'rahasia-baru',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertTrue(Hash::check('rahasia-baru', User::where('email', 'budi@arahinn.com')->first()->password));
    }

    public function test_akun_lokal_diutamakan_daripada_akun_induk(): void
    {
        // Surel yang sama ada di dua tempat dengan kata sandi berbeda. Yang
        // dipakai adalah milik marketplace, karena percobaan lokal berjalan
        // lebih dulu — pengguna di sini tidak boleh dikunci oleh sistem lain.
        $this->akunInduk(['password' => Hash::make('sandi-induk')]);
        User::factory()->create([
            'email' => 'budi@arahinn.com',
            'password' => Hash::make('sandi-lokal'),
        ]);

        $this->post(route('login'), [
            'email' => 'budi@arahinn.com',
            'password' => 'sandi-lokal',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }
}
