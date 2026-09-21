<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Jalur normal sistem:cek.
 *
 * Dipisah dari CekSistemTest karena hanya pengujian ini yang butuh skema
 * lengkap, sedangkan kelas itu sengaja merusak koneksinya.
 */
class CekSistemSehatTest extends TestCase
{
    use RefreshDatabase;

    public function test_seluruh_bagian_dilaporkan_saat_basis_data_sehat(): void
    {
        $this->artisan('sistem:cek')
            ->expectsOutputToContain('Koneksi basis data')
            ->expectsOutputToContain('Tabel')
            ->expectsOutputToContain('Peran pengguna')
            ->expectsOutputToContain('Aset frontend');
    }
}
