<?php

namespace Database\Seeders;

use App\Models\MetodePembayaran;
use Illuminate\Database\Seeder;

class MetodePembayaranSeeder extends Seeder
{
    public function run(): void
    {
        $metodes = [
            [
                'nama' => 'Transfer Bank BCA',
                'tipe' => 'transfer',
                'nomor_rekening' => '8830 1234 5678',
                'atas_nama' => 'PT Marketplace Nusantara',
                'instruksi' => 'Transfer ke rekening BCA, lalu unggah bukti pembayaran pada halaman pesanan.',
            ],
            [
                'nama' => 'Transfer Bank Mandiri',
                'tipe' => 'transfer',
                'nomor_rekening' => '1370 0098 7654',
                'atas_nama' => 'PT Marketplace Nusantara',
                'instruksi' => 'Transfer ke rekening Mandiri, lalu unggah bukti pembayaran pada halaman pesanan.',
            ],
            [
                'nama' => 'Transfer Bank BRI',
                'tipe' => 'transfer',
                'nomor_rekening' => '0021 01 001234 56 7',
                'atas_nama' => 'PT Marketplace Nusantara',
                'instruksi' => 'Transfer ke rekening BRI, lalu unggah bukti pembayaran pada halaman pesanan.',
            ],
            [
                'nama' => 'GoPay',
                'tipe' => 'ewallet',
                'nomor_rekening' => '0812 3456 7890',
                'atas_nama' => 'Marketplace Nusantara',
                'instruksi' => 'Bayar melalui GoPay dengan nomor di atas, lalu unggah bukti pembayaran.',
            ],
            [
                'nama' => 'OVO',
                'tipe' => 'ewallet',
                'nomor_rekening' => '0812 3456 7890',
                'atas_nama' => 'Marketplace Nusantara',
                'instruksi' => 'Bayar melalui OVO dengan nomor di atas, lalu unggah bukti pembayaran.',
            ],
            [
                'nama' => 'DANA',
                'tipe' => 'ewallet',
                'nomor_rekening' => '0812 3456 7890',
                'atas_nama' => 'Marketplace Nusantara',
                'instruksi' => 'Bayar melalui DANA dengan nomor di atas, lalu unggah bukti pembayaran.',
            ],
            [
                'nama' => 'COD (Bayar di Tempat)',
                'tipe' => 'cod',
                'instruksi' => 'Bayar tunai saat pesanan tiba. Siapkan uang pas ya!',
            ],
        ];

        foreach ($metodes as $metode) {
            MetodePembayaran::updateOrCreate(
                ['nama' => $metode['nama']],
                [...$metode, 'aktif' => true],
            );
        }
    }
}