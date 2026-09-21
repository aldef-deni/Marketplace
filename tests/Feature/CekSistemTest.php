<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Perintah sistem:cek.
 *
 * Alat ini dipakai justru ketika ada yang salah, jadi yang paling penting
 * bukan hasilnya saat semua baik — melainkan bahwa ia tetap selesai sampai
 * akhir ketika ada yang rusak.
 */
class CekSistemTest extends TestCase
{
    public function test_basis_data_mati_dilaporkan_dan_bukan_melempar_galat(): void
    {
        $this->matikanBasisData();

        // Dulu Schema::hasTable() yang pertama langsung melempar
        // QueryException, sehingga perintahnya berhenti dengan stack trace dan
        // bagian sesudahnya tidak sempat diperiksa sama sekali.
        $this->artisan('sistem:cek')
            ->expectsOutputToContain('Koneksi basis data')
            ->expectsOutputToContain('dilewati selama basis data tidak terhubung')
            ->assertFailed();
    }

    public function test_bagian_tanpa_basis_data_tetap_diperiksa_walau_koneksinya_mati(): void
    {
        $this->matikanBasisData();

        // Justru bagian inilah yang biasanya menjelaskan kenapa koneksinya
        // gagal — berkas .env yang belum disalin, misalnya.
        $this->artisan('sistem:cek')
            ->expectsOutputToContain('APP_URL')
            ->expectsOutputToContain('Dapat ditulis')
            ->assertFailed();
    }

    /**
     * Arahkan koneksi baku ke porta yang tidak ada yang mendengarkan.
     */
    private function matikanBasisData(): void
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => array_merge(
                config('database.connections.mysql'),
                ['host' => '127.0.0.1', 'port' => 59999, 'database' => 'tidak_ada'],
            ),
        ]);

        DB::purge('mysql');
    }
}
