<?php

/*
|--------------------------------------------------------------------------
| Identitas Market ArahInn
|--------------------------------------------------------------------------
|
| Semua yang berkaitan dengan nama, logo, warna, dan kontak dikumpulkan di
| sini agar rebranding cukup dilakukan di satu berkas — tidak perlu menyisir
| view satu per satu.
|
*/

return [

    'nama'         => env('BRAND_NAMA', 'Market ArahInn'),
    'nama_pendek'  => env('BRAND_NAMA_PENDEK', 'ArahInn'),
    'tagline'      => env('BRAND_TAGLINE', 'Belanja Terarah, Belanja Terpercaya'),
    'deskripsi'    => env('BRAND_DESKRIPSI', 'Marketplace resmi ArahInn — ribuan produk pilihan, pembayaran fleksibel, pengiriman cepat ke seluruh Indonesia.'),

    'domain'       => env('BRAND_DOMAIN', 'market.arahinn.com'),
    'email'        => env('BRAND_EMAIL', 'halo@arahinn.com'),
    'telepon'      => env('BRAND_TELEPON', '+62 812-0000-0000'),
    'whatsapp'     => env('BRAND_WHATSAPP', '6281200000000'),
    'alamat'       => env('BRAND_ALAMAT', 'Indonesia'),

    'induk' => [
        'nama' => 'ArahInn',
        'url'  => env('BRAND_URL_INDUK', 'https://arahinn.com'),
    ],

    /*
    | Berkas logo di public/images. Versi landscape dipakai pada bilah navigasi
    | dan footer; versi portrait pada halaman autentikasi dan cetak.
    | Seluruhnya berlatar transparan sehingga aman di atas warna apa pun.
    */
    'logo' => [
        'landscape'     => 'images/logo-landscape.png',
        'landscape_160' => 'images/logo-landscape-160.png',
        'landscape_96'  => 'images/logo-landscape-96.png',
        'portrait'      => 'images/logo-portrait.png',
        'portrait_520'  => 'images/logo-portrait-520.png',
        'portrait_300'  => 'images/logo-portrait-300.png',
        'ikon'          => 'images/icon-512.png',
        'ikon_192'      => 'images/icon-192.png',
        'ikon_180'      => 'images/icon-180.png',
        'og'            => 'images/og-image.png',
    ],

    /*
    | Lencana metode pembayaran di footer. Warna diambil dari identitas resmi
    | tiap penyedia, dipakai sebagai latar saat kartunya disorot kursor.
    */
    'metode_bayar' => [
        ['nama' => 'BCA',     'warna' => '#0060AF'],
        ['nama' => 'Mandiri', 'warna' => '#003D79'],
        ['nama' => 'BRI',     'warna' => '#00529C'],
        ['nama' => 'BNI',     'warna' => '#F05A22'],
        ['nama' => 'GoPay',   'warna' => '#00AED6'],
        ['nama' => 'OVO',     'warna' => '#4C3494'],
        ['nama' => 'DANA',    'warna' => '#118EEA'],
        ['nama' => 'COD',     'warna' => '#0E9F6E'],
    ],

    /*
    | Pilihan ikon kategori. Kuncinya harus ada di komponen <x-ikon>.
    */
    'ikon_kategori' => [
        'ponsel'     => 'Elektronik / Gawai',
        'baju'       => 'Fashion Pria',
        'gaun'       => 'Fashion Wanita',
        'kecantikan' => 'Kesehatan & Kecantikan',
        'cangkir'    => 'Makanan & Minuman',
        'rumah'      => 'Peralatan Rumah',
        'bola'       => 'Olahraga',
        'mobil'      => 'Otomotif',
        'buku'       => 'Buku & Alat Tulis',
        'hadiah'     => 'Hadiah & Souvenir',
        'label'      => 'Umum / Lainnya',
        'toko'       => 'Kebutuhan Toko',
        'perisai'    => 'Keamanan',
        'uang'       => 'Keuangan',
        'petir'      => 'Listrik & Energi',
    ],

    /*
    | Warna diambil dari logo dan dicerminkan di tailwind.config.js.
    | Nilai di sini dipakai untuk hal-hal di luar Tailwind: theme-color,
    | manifest PWA, dan email.
    */
    'warna' => [
        'biru'   => '#0B5FB0',
        'oranye' => '#F59300',
        'gelap'  => '#06080C',
    ],

];
