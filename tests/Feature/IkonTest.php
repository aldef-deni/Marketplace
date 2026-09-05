<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class IkonTest extends TestCase
{
    /**
     * Setiap ikon yang boleh dipilih admin untuk kategori harus benar-benar
     * ada di komponen <x-ikon>; kalau tidak, kategori tampil sebagai titik.
     */
    public function test_seluruh_ikon_kategori_tersedia_di_komponen(): void
    {
        $komponen = file_get_contents(resource_path('views/components/ikon.blade.php'));

        foreach (array_keys(config('brand.ikon_kategori')) as $nama) {
            $this->assertStringContainsString("'{$nama}'", $komponen, "Ikon '{$nama}' belum terdaftar.");
        }
    }

    public function test_komponen_ikon_merender_svg(): void
    {
        $html = Blade::render('<x-ikon nama="keranjang" kelas="h-5 w-5" />');

        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('h-5 w-5', $html);
    }

    public function test_nama_ikon_tak_dikenal_tidak_membuat_galat(): void
    {
        $html = Blade::render('<x-ikon nama="entah-apa" />');

        $this->assertStringContainsString('<svg', $html);
    }
}
