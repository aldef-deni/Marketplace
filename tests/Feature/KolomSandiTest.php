<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tombol lihat/sembunyi pada setiap kolom kata sandi.
 *
 * Yang dijaga bukan sekadar "tombolnya ada", melainkan bahwa tidak ada satu
 * pun kolom sandi yang terlewat — mudah sekali menambah formulir baru dan
 * lupa memakai komponennya.
 */
class KolomSandiTest extends TestCase
{
    use RefreshDatabase;

    private const PENANDA = 'Lihat kata sandi';

    private function hitungTombol(string $html): int
    {
        return substr_count($html, self::PENANDA);
    }

    public function test_halaman_masuk_punya_satu_tombol_lihat_sandi(): void
    {
        $html = $this->get(route('login'))->assertOk()->getContent();

        $this->assertSame(1, $this->hitungTombol($html));
    }

    public function test_halaman_daftar_punya_tombol_pada_kedua_kolom(): void
    {
        $html = $this->get(route('register'))->assertOk()->getContent();

        $this->assertSame(2, $this->hitungTombol($html));
    }

    public function test_halaman_atur_ulang_sandi_punya_tombol_pada_kedua_kolom(): void
    {
        $html = $this->get(route('password.reset', ['token' => 'token-uji']))
            ->assertOk()->getContent();

        $this->assertSame(2, $this->hitungTombol($html));
    }

    public function test_halaman_konfirmasi_sandi_punya_tombol(): void
    {
        $html = $this->actingAs(User::factory()->create())
            ->get(route('password.confirm'))->assertOk()->getContent();

        $this->assertSame(1, $this->hitungTombol($html));
    }

    public function test_halaman_profil_punya_tombol_pada_seluruh_kolom_sandinya(): void
    {
        // Tiga di formulir ubah sandi, satu di konfirmasi hapus akun.
        $html = $this->actingAs(User::factory()->create())
            ->get(route('profile.edit'))->assertOk()->getContent();

        $this->assertSame(4, $this->hitungTombol($html));
    }

    /* ---------- Perilaku komponennya ---------- */

    public function test_kolom_tetap_tersamar_pada_html_mentah(): void
    {
        // Alpine baru menimpanya setelah tombolnya ditekan. Kalau nilai awal
        // ini ikut berubah, sandi akan terbaca sekilas saat halaman dimuat —
        // dan tetap terbaca bila skripnya gagal dimuat sama sekali.
        $html = $this->get(route('login'))->assertOk()->getContent();

        $this->assertStringContainsString('type="password"', $html);
        $this->assertStringContainsString(':type="tampil ? \'text\' : \'password\'', $html);
    }

    public function test_tombol_tidak_ikut_mengirim_formulir(): void
    {
        // Tanpa type="button", tombol di dalam <form> berlaku sebagai submit —
        // menekan mata justru akan mencoba masuk.
        $komponen = file_get_contents(resource_path('views/components/input-sandi.blade.php'));

        $this->assertStringContainsString('<button type="button"', $komponen);
    }

    /* ---------- Tidak ada yang terlewat ---------- */

    public function test_tidak_ada_kolom_sandi_yang_dibuat_di_luar_komponennya(): void
    {
        $terlewat = [];

        foreach ($this->berkasTampilan() as $berkas) {
            if (str_ends_with($berkas, 'input-sandi.blade.php')) {
                continue;   // Komponennya sendiri memang menulis type="password".
            }

            if (str_contains(file_get_contents($berkas), 'type="password"')) {
                $terlewat[] = str_replace(resource_path('views').DIRECTORY_SEPARATOR, '', $berkas);
            }
        }

        $this->assertSame([], $terlewat,
            'Kolom sandi berikut belum memakai <x-input-sandi>: '.implode(', ', $terlewat));
    }

    /**
     * @return array<int, string>
     */
    private function berkasTampilan(): array
    {
        $berkas = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if ($item->isFile() && str_ends_with($item->getFilename(), '.blade.php')) {
                $berkas[] = $item->getPathname();
            }
        }

        return $berkas;
    }
}
