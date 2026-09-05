@include('errors.layout', [
    'kode' => 403,
    'judul' => 'Akses Ditolak',
    'pesan' => 'Anda tidak berhak membuka halaman ini. Tautannya mungkin milik akun lain, atau Anda sudah berganti akun sejak tautan itu dibuka.',
])
