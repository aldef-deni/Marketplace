<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProdukSeeder extends Seeder
{
    public function run(): void
    {
        $produks = [
            // Elektronik
            ['Smart TV 43 Inch Ultra HD', 'Elektronik', 3299000, 3899000, 12, 9000, '📺', 'Televisi smart dengan resolusi Ultra HD, koneksi WiFi, dan berbagai aplikasi streaming.' ],
            ['TWS Earbuds Bluetooth 5.3', 'Elektronik', 249000, 349000, 45, 60, '🎧', 'Earbuds nirkabel dengan noise cancelling, tahan air IPX5, dan baterai tahan 24 jam.' ],
            ['Smartphone 5G 8/256 GB', 'Elektronik', 4599000, 5299000, 8, 210, '📱', 'Smartphone dengan layar AMOLED 6.7 inch, kamera 108 MP, dan baterai 5000 mAh.' ],
            ['Smartwatch Sport GPS', 'Elektronik', 749000, 999000, 20, 50, '⌚', 'Jam tangan pintar dengan GPS, monitor detak jantung, dan tahan air 5 ATM.' ],
            ['Laptop Gaming RTX 4060', 'Elektronik', 15999000, 17999000, 5, 2500, '💻', 'Laptop gaming dengan prosesor Intel i7 dan kartu grafis RTX 4060.' ],
            ['Kamera Mirrorless 24MP', 'Elektronik', 8799000, 9999000, 6, 650, '📷', 'Kamera mirrorless dengan sensor APS-C 24 MP dan perekaman video 4K.' ],

            // Fashion Pria
            ['Kemeja Flanel Pria Premium', 'Fashion Pria', 129000, 189000, 30, 350, '👔', 'Kemeja flanel berbahan katun lembut, nyaman dipakai sehari-hari.' ],
            ['Sepatu Sneakers Pria Casual', 'Fashion Pria', 289000, 399000, 25, 750, '👟', 'Sepatu sneakers dengan sol empuk dan desain kasual yang stylish.' ],
            ['Topi Baseball Embroidery', 'Fashion Pria', 69000, 99000, 40, 120, '🧢', 'Topi baseball dengan bordir rapi dan tali belakang yang bisa diatur.' ],
            ['Jaket Hoodie Parasut', 'Fashion Pria', 159000, 229000, 22, 500, '🧥', 'Jaket hoodie parasut ringan, anti air, dan cocok untuk cuaca dingin.' ],
            ['Jam Tangan Analog Kulit', 'Fashion Pria', 199000, 299000, 18, 150, '⌚', 'Jam tangan analog dengan strap kulit asli dan desain elegan.' ],

            // Fashion Wanita
            ['Dress Floral Motif Batik', 'Fashion Wanita', 189000, 259000, 15, 300, '👗', 'Dress dengan motif batik floral, bahan rayon premium yang jatuh.' ],
            ['Handbag Kulit Sintetis', 'Fashion Wanita', 249000, 329000, 14, 600, '👜', 'Tas tangan kulit sintetis dengan banyak kompartemen dan resleting kuat.' ],
            ['Flat Shoes Kulit Lembut', 'Fashion Wanita', 159000, 219000, 28, 400, '👠', 'Sepatu flat nyaman untuk aktivitas harian, bahan kulit lembut.' ],
            ['Scarf Sutra Premium', 'Fashion Wanita', 99000, 149000, 35, 100, '🧣', 'Scarf sutra dengan motif elegan, bahan lembut dan adem.' ],

            // Kesehatan & Kecantikan
            ['Serum Vitamin C 30ml', 'Kesehatan & Kecantikan', 129000, 169000, 50, 80, '💧', 'Serum vitamin C 20% untuk mencerahkan dan menyamarkan noda hitam.' ],
            ['Sunscreen SPF 50 PA++++', 'Kesehatan & Kecantikan', 89000, 119000, 60, 60, '☀️', 'Tabir surya ringan dengan SPF 50, tidak lengket, dan aman untuk kulit sensitif.' ],
            ['Lipstik Matte Velvet', 'Kesehatan & Kecantikan', 79000, 99000, 55, 30, '💄', 'Lipstik matte dengan warna pigmented dan formula tahan lama.' ],
            ['Vitamin Kecantikan Collagen', 'Kesehatan & Kecantikan', 149000, 199000, 38, 120, '💊', 'Suplemen kolagen untuk kesehatan kulit, kuku, dan rambut.' ],

            // Makanan & Minuman
            ['Kopi Arabika Gayo 250gr', 'Makanan & Minuman', 68000, 89000, 70, 300, '☕', 'Biji kopi arabika Gayo grade 1, aroma kuat dengan rasa yang khas.' ],
            ['Madu Murni Hutan 500ml', 'Makanan & Minuman', 119000, 149000, 42, 700, '🍯', 'Madu murni asli hutan, tanpa campuran gula, kaya manfaat.' ],
            ['Cokelat Premium Dark 70%', 'Makanan & Minuman', 49000, 69000, 65, 120, '🍫', 'Cokelat dark 70% kakao dengan rasa yang smooth dan tidak terlalu pahit.' ],
            ['Teh Melati Premium 50 Kantong', 'Makanan & Minuman', 39000, 55000, 80, 200, '🍵', 'Teh melati premium dengan daun teh pilihan dan aroma bunga yang harum.' ],
            ['Keripik Tempe Renyah 100gr', 'Makanan & Minuman', 25000, 35000, 90, 90, '🍟', 'Keripik tempe renyah dengan bumbu original, camilan sehat favorit.' ],

            // Peralatan Rumah
            ['Rice Cooker 1.8L Digital', 'Peralatan Rumah', 349000, 459000, 16, 2200, '🍚', 'Rice cooker digital dengan fungsi memasak nasi, bubur, dan penghangat otomatis.' ],
            ['Set Pisau Dapur 5 Pcs', 'Peralatan Rumah', 199000, 279000, 24, 1500, '🔪', 'Set pisau dapur stainless steel dengan gagang ergonomis dan talenan.' ],
            ['Vacuum Cleaner Portable', 'Peralatan Rumah', 429000, 599000, 10, 1600, '🌀', 'Vacuum cleaner portable nirkabel dengan daya hisap kuat dan baterai tahan lama.' ],
            ['Lampu Meja LED Eye Care', 'Peralatan Rumah', 149000, 199000, 32, 900, '💡', 'Lampu meja LED dengan 3 mode warna dan pengaturan kecerahan tanpa kedip.' ],

            // Olahraga
            ['Bola Sepak Ukuran 5', 'Olahraga', 159000, 219000, 26, 450, '⚽', 'Bola sepak ukuran 5 dengan bahan PU berkualitas dan jahitan rapi.' ],
            ['Raket Badminton Carbon', 'Olahraga', 289000, 379000, 19, 120, '🏸', 'Raket badminton carbon ringan, kaku, dan seimbang untuk permainan cepat.' ],
            ['Matras Yoga Anti Slip 6mm', 'Olahraga', 139000, 189000, 33, 900, '🧘', 'Matras yoga tebal 6mm dengan permukaan anti slip dan tas bawaan.' ],
            ['Dumbbell Set 2 x 5kg', 'Olahraga', 219000, 299000, 15, 11000, '🏋️', 'Set dumbbell 2 x 5 kg dengan lapisan karet agar tidak merusak lantai.' ],
            ['Sepeda Lipat 20 Inch', 'Olahraga', 2899000, 3499000, 7, 13500, '🚲', 'Sepeda lipat 20 inch dengan rangka aluminium ringan dan 6 kecepatan.' ],

            // Otomotif
            ['Helm Full Face SNI', 'Otomotif', 349000, 449000, 13, 1500, '🪖', 'Helm full face standar SNI dengan visor anti gores dan ventilasi udara.' ],
            ['Ban Luar Motor 90/80-17', 'Otomotif', 289000, 349000, 20, 4200, '🛞', 'Ban luar motor dengan cengkraman kuat dan daya tahan lama.' ],
            ['Kaca Film Solar Gard', 'Otomotif', 399000, 549000, 9, 300, '🚗', 'Kaca film premium dengan proteksi UV 99% dan garansi 5 tahun.' ],
            ['Aksesoris Spion Racing', 'Otomotif', 89000, 129000, 27, 500, '🪞', 'Spion racing universal dengan desain aerodinamis dan sudut pandang luas.' ],

            // Buku & Alat Tulis
            ['Novel Bumi Manusia', 'Buku & Alat Tulis', 89000, 119000, 40, 400, '📖', 'Novel klasik karya Pramoedya Ananta Toer, kisah cinta dan perjuangan.' ],
            ['Buku Manajemen Keuangan', 'Buku & Alat Tulis', 129000, 169000, 25, 550, '📘', 'Panduan lengkap mengelola keuangan pribadi dan bisnis untuk pemula.' ],
            ['Set Pulpen Gel 0.5mm (12 pcs)', 'Buku & Alat Tulis', 49000, 69000, 60, 250, '🖊️', 'Paket 12 pulpen gel 0.5mm dengan tinta smooth dan tidak tembus.' ],
            ['Buku Tulis Sinar 38 Lembar (10 pcs)', 'Buku & Alat Tulis', 59000, 79000, 75, 1000, '📓', 'Paket 10 buku tulis 38 lembar kertas HVS tebal dan halus.' ],
            ['Tas Ransel Sekolah Anti Air', 'Buku & Alat Tulis', 219000, 299000, 17, 800, '🎒', 'Tas ransel anti air dengan banyak kantong dan sandaran empuk.' ],
        ];

        foreach ($produks as [$nama, $kategoriNama, $harga, $hargaCoret, $stok, $berat, $emoji, $deskripsi]) {
            $kategori = Kategori::where('nama', $kategoriNama)->first();
            if (! $kategori) {
                continue;
            }

            $slug = Str::slug($nama);
            $gambar = $this->buatGambar($slug, $nama, $emoji, $kategoriNama);

            Produk::updateOrCreate(
                ['slug' => $slug],
                [
                    'kategori_id' => $kategori->id,
                    'nama' => $nama,
                    'deskripsi' => $deskripsi,
                    'harga' => $harga,
                    'harga_coret' => $hargaCoret,
                    'stok' => $stok,
                    'berat' => $berat,
                    'gambar' => $gambar,
                    'status' => 'aktif',
                ],
            );
        }
    }

    /**
     * Buat gambar SVG placeholder yang elegan untuk produk.
     */
    private function buatGambar(string $slug, string $nama, string $emoji, string $kategori): string
    {
        // Sembilan gradasi dalam keluarga warna Market ArahInn: biru ke oranye.
        $palet = [
            'Elektronik' => ['#0B5FB0', '#1E7AD6'],
            'Fashion Pria' => ['#1E7AD6', '#0B5FB0'],
            'Fashion Wanita' => ['#D97400', '#B45509'],
            'Kesehatan & Kecantikan' => ['#0A3D72', '#1E7AD6'],
            'Makanan & Minuman' => ['#F59300', '#D97400'],
            'Peralatan Rumah' => ['#FBAA24', '#F59300'],
            'Olahraga' => ['#06203E', '#0B5FB0'],
            'Otomotif' => ['#0B5FB0', '#F59300'],
            'Buku & Alat Tulis' => ['#084B8E', '#0B5FB0'],
        ];
        [$warna1, $warna2] = $palet[$kategori] ?? ['#0B5FB0', '#1E7AD6'];

        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="600" height="600" viewBox="0 0 600 600">
          <defs>
            <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="{$warna1}"/>
              <stop offset="100%" stop-color="{$warna2}"/>
            </linearGradient>
            <linearGradient id="sh" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#ffffff" stop-opacity="0.25"/>
              <stop offset="100%" stop-color="#ffffff" stop-opacity="0"/>
            </linearGradient>
          </defs>
          <rect width="600" height="600" fill="url(#bg)"/>
          <circle cx="520" cy="80" r="160" fill="url(#sh)"/>
          <circle cx="60" cy="540" r="120" fill="url(#sh)"/>
          <circle cx="300" cy="260" r="170" fill="#ffffff" fill-opacity="0.12"/>
          <text x="300" y="300" font-size="190" text-anchor="middle" dominant-baseline="central">{$emoji}</text>
        </svg>
        SVG;

        $path = 'produk/'.$slug.'.svg';
        Storage::disk('uploads')->put($path, $svg);

        return 'uploads/'.$path;
    }

}