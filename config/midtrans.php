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

    /*
     | Saklar utama. Selama false, metode pembayaran bergerbang tidak
     | ditawarkan sama sekali di checkout — bukan ditawarkan lalu gagal.
     */
    'aktif' => (bool) env('MIDTRANS_AKTIF', false),

    /*
     | Produksi berarti uang sungguhan. Sengaja false secara baku: salah setel
     | ke arah ini jauh lebih mahal daripada sebaliknya.
     */
    'produksi' => (bool) env('MIDTRANS_PRODUKSI', false),

    'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'server_key' => env('MIDTRANS_SERVER_KEY'),

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
