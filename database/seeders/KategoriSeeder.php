<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        $kategoris = [
            ['Elektronik', '📱', 'Berbagai gadget dan perangkat elektronik terkini.'],
            ['Fashion Pria', '👔', 'Pakaian, sepatu, dan aksesoris untuk pria.'],
            ['Fashion Wanita', '👗', 'Pakaian, tas, dan aksesoris untuk wanita.'],
            ['Kesehatan & Kecantikan', '💄', 'Produk perawatan tubuh dan kecantikan.'],
            ['Makanan & Minuman', '🍫', 'Makanan ringan, minuman, dan kebutuhan dapur.'],
            ['Peralatan Rumah', '🏠', 'Perlengkapan rumah tangga yang praktis.'],
            ['Olahraga', '⚽', 'Perlengkapan olahraga dan kebugaran.'],
            ['Otomotif', '🚗', 'Aksesoris dan perlengkapan kendaraan.'],
            ['Buku & Alat Tulis', '📚', 'Buku, alat tulis, dan perlengkapan kantor.'],
        ];

        foreach ($kategoris as [$nama, $ikon, $deskripsi]) {
            Kategori::updateOrCreate(
                ['slug' => Str::slug($nama)],
                ['nama' => $nama, 'ikon' => $ikon, 'deskripsi' => $deskripsi, 'aktif' => true],
            );
        }
    }
}