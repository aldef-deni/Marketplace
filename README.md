<div align="center">

<img src="public/images/logo-landscape-160.png" alt="Market ArahInn" width="360">

# Market ArahInn

**Belanja Terarah, Belanja Terpercaya**

Marketplace e-commerce berbasis Laravel 12 untuk **https://market.arahinn.com**

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20) ![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4) ![MySQL](https://img.shields.io/badge/MySQL%20%2F%20MariaDB-siap-0B5FB0) ![Status](https://img.shields.io/badge/Status-Siap%20Produksi-F59300)

</div>

---

## Identitas Merek

| Aspek | Nilai |
|-------|-------|
| Nama | Market ArahInn |
| Domain | `market.arahinn.com` |
| Warna utama | Biru `#0B5FB0` — diambil dari huruf "A" pada logo |
| Warna aksen | Oranye `#F59300` — diambil dari sapuan panah pada logo |
| Permukaan gelap | `#06080C` — sama dengan latar berkas logo |
| Tipografi | Plus Jakarta Sans |

Seluruh teks merek, kontak, dan berkas logo terpusat di **`config/brand.php`**.
Untuk mengganti nama, email, atau nomor WhatsApp, cukup ubah berkas itu (atau
variabel `BRAND_*` di `.env`) — tidak perlu menyisir view satu per satu.

### Berkas Logo

Semua berkas di `public/images/` berlatar **transparan**, sehingga aman
dipasang di atas warna apa pun.

| Berkas | Pemakaian |
|--------|-----------|
| `logo-landscape*.png` / `.webp` | Bilah navigasi, footer, sidebar admin, invoice |
| `logo-portrait*.png` / `.webp` | Materi promosi dan tata letak vertikal |
| `icon-{16,32,180,192,512}.png` | Favicon, ikon PWA, ikon layar utama iOS |
| `og-image.png` | Pratinjau tautan di WhatsApp, Facebook, X |
| `favicon.ico` | Favicon peramban lama |

Komponen `<x-logo>` memilih ukuran yang tepat secara otomatis:

```blade
<x-logo varian="landscape" kelas="h-10 w-auto" />
<x-logo varian="portrait" kelas="h-40 w-auto" />
```

Ikon antarmuka memakai satu set SVG di `<x-ikon>` — bukan emoji — agar tampilan
konsisten di semua sistem operasi:

```blade
<x-ikon nama="keranjang" kelas="h-5 w-5" />
```

---

## Fitur

### Toko (sisi pembeli)
- Beranda: hero, kategori, produk terbaru, dan pita promo
- Katalog dengan pencarian, filter kategori, dan pengurutan harga
- Detail produk dengan diskon dan status stok
- Keranjang belanja dengan pengaturan jumlah
- Buku alamat (label, penerima, provinsi sampai kecamatan)
- Checkout dengan pilihan kurir dan ongkir otomatis dari berat produk

### Pembayaran
| Tipe | Metode |
|------|--------|
| Transfer bank | BCA, Mandiri, BRI |
| E-wallet | GoPay, OVO, DANA |
| COD | Bayar di tempat |

Tiap metode menampilkan nomor rekening dan instruksi. Pembeli mengunggah bukti
transfer, admin memverifikasi. Batas waktu pembayaran 24 jam.

### Alur pesanan
```
Dibuat → Menunggu Pembayaran → Menunggu Konfirmasi → Diproses → Dikirim → Selesai
```
Kurir: JNE, J&T Express, SiCepat, POS Indonesia. Admin memasukkan nomor resi,
pembeli melacak pesanan, lalu mengonfirmasi penerimaan. Pembatalan
mengembalikan stok. Invoice dapat dicetak langsung dari peramban.

### Masuk & mendaftar
- Email dan kata sandi, atau **Masuk dengan Google** (Laravel Socialite)
- Akun dicocokkan lewat `google_id` lalu email, sehingga pengguna lama yang
  beralih ke tombol Google tetap masuk ke akun yang sama
- Surel Google yang belum terverifikasi ditolak, untuk mencegah pengambilalihan akun
- Akun hasil pendaftaran Google bisa membuat kata sandi kapan saja lewat **Profil**
- Tombol Google otomatis tersembunyi bila kredensialnya belum diisi;
  penyiapannya ada di [DEPLOY.md](DEPLOY.md#7-masuk-dengan-google-sso)

### Flash Sale
| Peran | Wewenang |
|---|---|
| Superadmin | Menyusun kampanye: nama, jadwal, saran diskon; menerbitkan atau menariknya |
| Admin | Memutuskan toko ikut serta, lalu memilih produk beserta harga flash dan kuotanya |

Harga flash baru berlaku bagi pembeli bila **tiga syarat** terpenuhi sekaligus:
kampanye sudah diterbitkan superadmin, diikuti admin toko, dan berada dalam
rentang waktunya. Kuota membatasi berapa unit yang dijual dengan harga promo —
setelah habis, harga kembali normal alih-alih produknya menghilang.

Kampanye yang berjalan tampil di beranda lengkap dengan hitung mundur, dan
harganya dipakai konsisten dari katalog sampai checkout lewat satu sumber:
`Produk::hargaEfektif()`.

### Laporan
| Laporan | Akses | Isi |
|---|---|---|
| Transaksi | Admin & Superadmin | Ringkasan nilai, rincian per status/metode/kurir/hari, pelanggan teratas, dan daftar transaksi |
| Toko | Superadmin | Kondisi katalog dan stok, kinerja per kategori, serta kinerja tiap produk |

Filter: rentang tanggal, status pesanan, metode pembayaran, kurir, pencarian
invoice/pelanggan, dan pilihan menyertakan pesanan batal. Setiap laporan dapat
diunduh sebagai **PDF** dan **Excel** dengan kriteria yang sedang aktif.

> Catatan: aplikasi ini bertipe satu toko — produk bertaut ke kategori, bukan ke
> penjual. "Laporan Toko" berarti laporan kondisi dan kinerja katalog, bukan
> perbandingan antar-merchant.

### Peran pengguna
| Peran | Akses |
|-------|-------|
| Superadmin | Semua akses, termasuk manajemen pengguna dan metode pembayaran |
| Admin | Dashboard, produk, kategori, pesanan, pembayaran, pengiriman |
| Pengguna | Belanja, keranjang, pesanan, alamat, profil |

Dibatasi middleware `role`. Dashboard admin menampilkan pendapatan, grafik
penjualan tujuh hari, produk terlaris, dan peringatan stok menipis.

## Teknologi

- **Laravel 12** — kerangka backend
- **MySQL / MariaDB** — basis data
- **Tailwind CSS 3 + Alpine.js** — antarmuka ringan tanpa framework berat
- **Vite** — kompilasi aset
- **Laravel Socialite** — masuk dengan akun Google
- **dompdf** dan **PhpSpreadsheet** — unduhan laporan PDF dan Excel
- **Blade Components** — layout modular untuk toko, pengguna, dan admin

---

<div align="center">

<strong>DEVELOPED BY</strong>

<br>

<a href="https://aldeftech.com" target="_blank">
  <img src="https://raw.githubusercontent.com/aldef-deni/aldeftech.com/master/public/images/logo.png" width="360" alt="Logo Aldef Tech">
</a>

</div>

<p align="center">
  <strong>JIKA BERMINAT UNTUK KUSTOMISASI BISA MENGHUBUNGI DENI AFRIZAL</strong>
</p>

<p align="center">
  <a href="https://wa.me/628128968609" target="_blank">
    <img src="https://img.shields.io/badge/WhatsApp-Hubungi_Deni_Afrizal-25D366?style=for-the-badge&logo=whatsapp&logoColor=white" alt="Hubungi Deni Afrizal melalui WhatsApp">
  </a>
</p>

## Kontak

Punya kebutuhan sistem, aplikasi, SaaS, integrasi, atau otomasi AI? Kunjungi [aldeftech.com/contact](https://aldeftech.com/contact) untuk mendiskusikan kebutuhan bisnis Anda bersama Aldef Tech.

---

<p align="center">
  &copy; Aldef Tech. Seluruh hak cipta dilindungi.
</p>
