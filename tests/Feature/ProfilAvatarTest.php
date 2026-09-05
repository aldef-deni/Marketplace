<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengguna_dapat_mengunggah_foto_profil(): void
    {
        Storage::fake('uploads');
        $pengguna = User::factory()->create(['avatar' => null]);

        $this->actingAs($pengguna)
            ->post(route('profile.avatar'), ['avatar' => UploadedFile::fake()->image('foto.jpg', 400, 400)])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $pengguna->refresh();

        $this->assertNotNull($pengguna->avatar);
        $this->assertStringStartsWith('uploads/avatar/', $pengguna->avatar,
            'Awalan uploads/ harus ikut tersimpan agar asset() menghasilkan URL benar.');
        Storage::disk('uploads')->assertExists(str_replace('uploads/', '', $pengguna->avatar));
    }

    public function test_foto_lama_dibuang_saat_diganti(): void
    {
        Storage::fake('uploads');
        $pengguna = User::factory()->create();

        $this->actingAs($pengguna)->post(route('profile.avatar'), [
            'avatar' => UploadedFile::fake()->image('pertama.jpg'),
        ]);
        $pertama = $pengguna->refresh()->avatar;

        $this->actingAs($pengguna)->post(route('profile.avatar'), [
            'avatar' => UploadedFile::fake()->image('kedua.jpg'),
        ]);
        $kedua = $pengguna->refresh()->avatar;

        $this->assertNotSame($pertama, $kedua);
        Storage::disk('uploads')->assertMissing(str_replace('uploads/', '', $pertama));
        Storage::disk('uploads')->assertExists(str_replace('uploads/', '', $kedua));
    }

    public function test_berkas_bukan_gambar_ditolak(): void
    {
        Storage::fake('uploads');
        $pengguna = User::factory()->create(['avatar' => null]);

        $this->actingAs($pengguna)
            ->post(route('profile.avatar'), ['avatar' => UploadedFile::fake()->create('dokumen.pdf', 100)])
            ->assertSessionHasErrors('avatar');

        $this->assertNull($pengguna->refresh()->avatar);
    }

    public function test_foto_dapat_dihapus(): void
    {
        Storage::fake('uploads');
        $pengguna = User::factory()->create();

        $this->actingAs($pengguna)->post(route('profile.avatar'), [
            'avatar' => UploadedFile::fake()->image('foto.jpg'),
        ]);
        $jalur = $pengguna->refresh()->avatar;

        $this->actingAs($pengguna)
            ->delete(route('profile.avatar.hapus'))
            ->assertSessionHasNoErrors();

        $this->assertNull($pengguna->refresh()->avatar);
        Storage::disk('uploads')->assertMissing(str_replace('uploads/', '', $jalur));
    }

    /**
     * Avatar Google berupa URL penuh dan tidak tersimpan di server ini, jadi
     * tidak boleh diperlakukan sebagai berkas yang bisa dihapus.
     */
    public function test_avatar_google_dipakai_apa_adanya(): void
    {
        $pengguna = User::factory()->create([
            'avatar' => 'https://lh3.googleusercontent.com/contoh',
        ]);

        $this->assertSame('https://lh3.googleusercontent.com/contoh', $pengguna->avatar_url);
        $this->assertFalse($pengguna->avatarDiunggahSendiri());

        $this->actingAs($pengguna)->delete(route('profile.avatar.hapus'))->assertSessionHasNoErrors();
        $this->assertNull($pengguna->refresh()->avatar);
    }

    public function test_avatar_lokal_menjadi_url_penuh(): void
    {
        $pengguna = User::factory()->create(['avatar' => 'uploads/avatar/foto.jpg']);

        $this->assertSame(asset('uploads/avatar/foto.jpg'), $pengguna->avatar_url);
        $this->assertTrue($pengguna->avatarDiunggahSendiri());
    }

    public function test_tanpa_avatar_tidak_menghasilkan_url(): void
    {
        $pengguna = User::factory()->create(['avatar' => null]);

        $this->assertNull($pengguna->avatar_url);
    }

    public function test_dashboard_menampilkan_kartu_profil(): void
    {
        $pengguna = User::factory()->create(['role' => 'pengguna', 'name' => 'Budi Uji']);

        $this->actingAs($pengguna)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Budi Uji')
            ->assertSee($pengguna->email)
            ->assertSee('Edit Profil')
            ->assertSee(route('profile.avatar'), escape: false);
    }

    public function test_halaman_profil_menyediakan_pengelolaan_foto(): void
    {
        $pengguna = User::factory()->create();

        $this->actingAs($pengguna)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Foto Profil')
            ->assertSee(route('profile.avatar'), escape: false);
    }

    public function test_dashboard_admin_menampilkan_kartu_profil(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Uji']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Admin Uji')
            ->assertSee('Edit Profil')
            ->assertSee('Kelola Pesanan')
            ->assertSee(route('profile.avatar'), escape: false);
    }

    public function test_superadmin_mendapat_pintasan_kelola_pengguna(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin']);

        $this->actingAs($superadmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Kelola Pengguna')
            ->assertSee(route('profile.avatar'), escape: false);
    }

    /**
     * Pengelola tetap berada di panel admin saat menyunting profil; berpindah
     * ke tampilan toko akan terasa seperti keluar dari areanya sendiri.
     */
    public function test_halaman_profil_admin_memakai_layout_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Panel Admin')
            ->assertSee('Foto Profil');
    }

    public function test_admin_dapat_mengunggah_foto_profil(): void
    {
        Storage::fake('uploads');
        $admin = User::factory()->create(['role' => 'admin', 'avatar' => null]);

        $this->actingAs($admin)
            ->post(route('profile.avatar'), ['avatar' => UploadedFile::fake()->image('admin.jpg')])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($admin->refresh()->avatar);
    }
}
