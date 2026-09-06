<?php

namespace Database\Seeders;

use App\Models\Toko;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Toko contoh beserta penjualnya.
 *
 * Tiap toko punya pemilik sendiri berperan penjual, supaya pembatasan akses
 * antar-lapak bisa benar-benar dicoba, bukan sekadar diasumsikan bekerja.
 */
class TokoSeeder extends Seeder
{
    public function run(): void
    {
        $tokos = [
            [
                'nama' => 'Sentra Elektronik Bekasi',
                'pemilik' => ['Rizky Pratama', 'rizky@arahinn.com'],
                'deskripsi' => 'Gawai, audio, dan perangkat rumah pintar dengan garansi resmi.',
                'provinsi' => 'Jawa Barat', 'kota' => 'Bekasi', 'kecamatan' => 'Pondok Gede',
                'no_hp' => '081211220001',
            ],
            [
                'nama' => 'Rumah Mode Nusantara',
                'pemilik' => ['Dewi Anggraini', 'dewi@arahinn.com'],
                'deskripsi' => 'Busana pria dan wanita, dari kasual harian sampai acara formal.',
                'provinsi' => 'DKI Jakarta', 'kota' => 'Jakarta Selatan', 'kecamatan' => 'Kebayoran Baru',
                'no_hp' => '081211220002',
            ],
            [
                'nama' => 'Dapur & Griya Sejahtera',
                'pemilik' => ['Agus Nugroho', 'agus@arahinn.com'],
                'deskripsi' => 'Peralatan dapur, perabot, dan kebutuhan rumah tangga sehari-hari.',
                'provinsi' => 'Jawa Tengah', 'kota' => 'Semarang', 'kecamatan' => 'Banyumanik',
                'no_hp' => '081211220003',
            ],
            [
                'nama' => 'Arena Sport & Hobi',
                'pemilik' => ['Fajar Maulana', 'fajar@arahinn.com'],
                'deskripsi' => 'Perlengkapan olahraga, buku, dan hobi untuk mengisi akhir pekan.',
                'provinsi' => 'Jawa Timur', 'kota' => 'Surabaya', 'kecamatan' => 'Gubeng',
                'no_hp' => '081211220004',
            ],
        ];

        foreach ($tokos as $data) {
            [$namaPemilik, $emailPemilik] = $data['pemilik'];
            unset($data['pemilik']);

            $pemilik = User::firstOrCreate(
                ['email' => $emailPemilik],
                [
                    'name' => $namaPemilik,
                    'password' => Hash::make('password'),
                    'role' => 'penjual',
                    'email_verified_at' => now(),
                ],
            );

            // Peran diperbarui juga bagi akun yang sudah ada dari seeding lama.
            if (! $pemilik->isPenjual()) {
                $pemilik->update(['role' => 'penjual']);
            }

            Toko::firstOrCreate(
                ['nama' => $data['nama']],
                $data + [
                    'user_id' => $pemilik->id,
                    'slug' => Toko::slugUnik($data['nama']),
                    'status' => 'aktif',
                    'disetujui_at' => now(),
                ],
            );
        }
    }
}
