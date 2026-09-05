<div align="center">

# 🛍️ Marketplace Nusantara

**Sistem marketplace e-commerce lengkap berbasis Laravel 12** — belanja online dengan pembayaran fleksibel dan pengiriman ke seluruh Indonesia.

![Laravel](https://img.shields.io/badge/Laravel-12.x-red) ![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue) ![MySQL](https://img.shields.io/badge/MySQL-MariaDB-orange) ![Status](https://img.shields.io/badge/Status-Siap%20Dipakai-green)

</div>

## ✨ Fitur Utama

### 🏪 Toko (Sisi Pembeli)
- Halaman beranda dengan hero, kategori, produk terbaru, dan promo
- Katalog produk dengan pencarian, filter kategori, dan pengurutan harga
- Detail produk dengan galeri, diskon, dan stok
- Keranjang belanja dengan pengaturan jumlah
- Buku alamat lengkap (label, penerima, provinsi → kecamatan)
- Checkout dengan pilihan kurir & perhitungan ongkir otomatis

### 💳 Multi Metode Pembayaran
| Tipe | Metode |
|------|--------|
| 🏦 Transfer Bank | BCA, Mandiri, BRI |
| 📱 E-Wallet | GoPay, OVO, DANA |
| 💵 COD | Bayar di Tempat |

- Setiap metode menampilkan nomor rekening & instruksi pembayaran
- Unggah bukti pembayaran → diverifikasi admin
- Batas waktu pembayaran 24 jam

### 🚚 Alur Pesanan Sampai Pengiriman
```
Pesanan Dibuat → Menunggu Pembayaran → Menunggu Konfirmasi → Diproses → Dikirim → Selesai
```
- Kurir: **JNE, J&T Express, SiCepat, POS Indonesia**
- Ongkir dihitung otomatis dari berat produk (per kg)
- Admin input nomor resi → pengguna melacak pesanan
- Konfirmasi pesanan diterima / pembatalan dengan pengembalian stok
- Cetak invoice (PDF-ready)

### 🛠️ Dashboard Berdasarkan Role
| Role | Akses |
|------|-------|
| 👑 **Superadmin** | Semua akses + manajemen pengguna & role + kelola metode pembayaran |
| 🧑‍💼 **Admin** | Dashboard statistik, produk, kategori, pesanan, pembayaran, pengiriman |
| 👤 **Pengguna** | Belanja, keranjang, pesanan, alamat, profil |

- Dashboard admin: pendapatan, grafik penjualan 7 hari, produk terlaris, stok menipis
- Proteksi akses berbasis middleware `role`

## 🚀 Cara Menjalankan

### Prasyarat
- PHP ≥ 8.2 (dengan ekstensi: pdo_mysql, gd, fileinfo)
- Composer 2.x
- Node.js ≥ 20
- MySQL / MariaDB

### 1. Instalasi
```bash
# Install dependensi
composer install
npm install && npm run build

# Konfigurasi environment
cp .env.example .env
php artisan key:generate
```

### 2. Konfigurasi Database
Edit `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=marketplace
DB_USERNAME=root
DB_PASSWORD=
```

```bash
# Buat database & jalankan migrasi + seeder
php artisan migrate --seed
```

### 3. Jalankan Server
```bash
php artisan serve
```
Buka **http://127.0.0.1:8000**

> 💡 Jika menggunakan XAMPP, nyalakan MySQL melalui XAMPP Control Panel terlebih dahulu.

## 👤 Akun Demo

| Role | Email | Password |
|------|-------|----------|
| 👑 Superadmin | `superadmin@marketplace.test` | `password` |
| 🧑‍💼 Admin | `admin@marketplace.test` | `password` |
| 👤 Pengguna | `pengguna@marketplace.test` | `password` |

## 🗂️ Struktur Database

- `users` — pengguna dengan role (superadmin, admin, pengguna)
- `kategoris` — kategori produk
- `produks` — produk (harga, stok, berat, gambar, status)
- `keranjangs` — keranjang belanja
- `alamats` — buku alamat pengguna
- `pesanans` — pesanan (no. invoice, status, kurir, total)
- `pesanan_items` — detail item pesanan
- `metode_pembayarans` — transfer / e-wallet / COD
- `pembayarans` — pembayaran (bukti, status, verifikasi)
- `pengirimans` — pengiriman (kurir, resi, status)

## 📁 Route Utama

| URL | Deskripsi |
|-----|-----------|
| `/` | Beranda toko |
| `/toko` | Katalog produk |
| `/keranjang` | Keranjang belanja |
| `/checkout` | Checkout |
| `/pesanan` | Daftar pesanan saya |
| `/dashboard` | Dashboard pengguna |
| `/admin` | Dashboard admin |
| `/admin/produk` | Manajemen produk |
| `/admin/pesanan` | Manajemen pesanan |
| `/admin/pembayaran` | Verifikasi pembayaran |
| `/admin/pengiriman` | Manajemen pengiriman |
| `/admin/pengguna` | Manajemen pengguna (superadmin) |
| `/admin/metode-pembayaran` | Kelola metode bayar (superadmin) |

## 🛠️ Teknologi

- **Laravel 12** — framework backend
- **MySQL / MariaDB** — database
- **Tailwind CSS + Alpine.js** — frontend elegan tanpa framework berat
- **Blade Components** — layout modular (toko, pengguna, admin)

---

© 2026 Marketplace Nusantara — Dibuat dengan ❤️ untuk Indonesia