<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gerbang pembayaran Midtrans
    |--------------------------------------------------------------------------
    |
    | Kredensial tidak pernah ditulis di berkas ini — hanya dibaca dari .env
    | yang tidak ikut masuk repositori maupun paket rilis. Server Key setara
    | kunci brankas: pemegangnya dapat membuat dan membatalkan transaksi atas
    | nama merchant.
    |
    */

    'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'server_key' => env('MIDTRANS_SERVER_KEY'),

    /*
     | Saklar utama — tapi mengisi kuncinya sudah berarti menyalakan.
     |
     | Semula ini saklar tersendiri yang baku mati. Rancangan itu keliru:
     | kunci yang sudah terisi tidak punya arti lain selain "pakai gerbangnya",
     | sementara saklar yang lupa diisi terlihat persis sama dengan kunci yang
     | belum dipasang — dan pesan di panel pun menuduh hal yang salah.
     |
     | MIDTRANS_AKTIF sekarang hanya perlu ditulis untuk mematikan gerbang
     | tanpa menghapus kuncinya.
     */
    'aktif' => (bool) env('MIDTRANS_AKTIF', filled(env('MIDTRANS_SERVER_KEY')) && filled(env('MIDTRANS_CLIENT_KEY'))),

    /*
     | Sandbox atau produksi.
     |
     | Bakunya disimpulkan dari kuncinya sendiri: kunci sandbox selalu berawalan
     | "SB-Mid-", kunci produksi tidak pernah. Kesimpulan itu lebih sulit salah
     | daripada saklar terpisah yang harus diingat — dan bila keduanya berbeda,
     | sistem:cek akan menyebutnya.
     |
     | MIDTRANS_IS_PRODUCTION ikut dibaca karena itu nama yang dipakai ArahInn;
     | menyamakan .env kedua aplikasi lebih murah daripada mengingat dua nama.
     */
    'produksi' => (bool) env('MIDTRANS_PRODUKSI', env('MIDTRANS_IS_PRODUCTION',
        filled(env('MIDTRANS_SERVER_KEY')) && ! str_starts_with((string) env('MIDTRANS_SERVER_KEY'), 'SB-Mid-'))),

    /*
     | Masa berlaku tagihan. Disamakan dengan batas_pembayaran pesanan (24 jam)
     | supaya halaman pesanan dan halaman Midtrans tidak memberi tenggat yang
     | berbeda kepada pembeli yang sama.
     */
    'kedaluwarsa_menit' => (int) env('MIDTRANS_KEDALUWARSA_MENIT', 1440),

    /*
     | Batas waktu memanggil Midtrans, dalam detik. Checkout menunggu panggilan
     | ini, jadi lebih baik gagal cepat dan jelas daripada menggantung.
     */
    'timeout' => (int) env('MIDTRANS_TIMEOUT', 15),

    /*
    |--------------------------------------------------------------------------
    | Alamat layanan
    |--------------------------------------------------------------------------
    */

    'url' => [
        'produksi' => [
            'snap' => 'https://app.midtrans.com/snap/v1/transactions',
            'api' => 'https://api.midtrans.com/v2',
        ],
        'sandbox' => [
            'snap' => 'https://app.sandbox.midtrans.com/snap/v1/transactions',
            'api' => 'https://api.sandbox.midtrans.com/v2',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Kanal yang boleh muncul
    |--------------------------------------------------------------------------
    |
    | Market ArahInn hanya melayani QRIS dan e-wallet. Daftar ini adalah batas
    | terluarnya: kanal apa pun di luar sini ditolak sebelum dikirim, sehingga
    | satu baris data yang salah di tabel metode pembayaran tidak bisa diam-diam
    | memunculkan kartu kredit atau virtual account di halaman Midtrans.
    |
    | Nilainya mengikuti penamaan enabled_payments pada Snap API. QRIS memakai
    | "other_qris", bukan "qris".
    |
    */

    'saluran_diizinkan' => [
        'other_qris',
        'gopay',
        'shopeepay',
        'dana',
        'ovo',
    ],

];
