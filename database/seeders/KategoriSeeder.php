<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        // Kolom ikon menyimpan nama ikon dari komponen <x-ikon>, bukan emoji.
        $kategoris = [
            ['Elektronik', 'ponsel', 'Berbagai gadget dan perangkat elektronik terkini.'],
            ['Fashion Pria', 'baju', 'Pakaian, sepatu, dan aksesoris untuk pria.'],
            ['Fashion Wanita', 'gaun', 'Pakaian, tas, dan aksesoris untuk wanita.'],
            ['Kesehatan & Kecantikan', 'kecantikan', 'Produk perawatan tubuh dan kecantikan.'],
            ['Makanan & Minuman', 'cangkir', 'Makanan ringan, minuman, dan kebutuhan dapur.'],
            ['Peralatan Rumah', 'rumah', 'Perlengkapan rumah tangga yang praktis.'],
            ['Olahraga', 'bola', 'Perlengkapan olahraga dan kebugaran.'],
            ['Otomotif', 'mobil', 'Aksesoris dan perlengkapan kendaraan.'],
            ['Buku & Alat Tulis', 'buku', 'Buku, alat tulis, dan perlengkapan kantor.'],
        ];

        foreach ($kategoris as [$nama, $ikon, $deskripsi]) {
            Kategori::updateOrCreate(
                ['slug' => Str::slug($nama)],
                ['nama' => $nama, 'ikon' => $ikon, 'deskripsi' => $deskripsi, 'aktif' => true],
            );
        }
    }
}