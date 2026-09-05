@extends('admin.laporan._cetak-dasar')

@section('judul', 'Laporan Toko')

@section('isi')

    <h2>Kondisi Katalog Saat Ini</h2>

    <table class="kartu">
        <tr>
            <td>
                <div class="nilai">{{ number_format($ringkasanToko['total_produk']) }}</div>
                <div class="label">Total Produk</div>
            </td>
            <td>
                <div class="nilai">{{ number_format($ringkasanToko['total_kategori']) }}</div>
                <div class="label">Kategori</div>
            </td>
            <td>
                <div class="nilai">{{ number_format($ringkasanToko['stok_total']) }}</div>
                <div class="label">Stok Tersedia (unit)</div>
            </td>
            <td>
                <div class="nilai">{{ rp($ringkasanToko['nilai_stok']) }}</div>
                <div class="label">Nilai Stok</div>
            </td>
        </tr>
    </table>

    <table class="kartu">
        <tr>
            <td>
                <div class="nilai">{{ number_format($ringkasanToko['produk_aktif']) }}</div>
                <div class="label">Produk Aktif</div>
            </td>
            <td>
                <div class="nilai">{{ number_format($ringkasanToko['produk_nonaktif']) }}</div>
                <div class="label">Produk Nonaktif</div>
            </td>
            <td>
                <div class="nilai">{{ number_format($ringkasanToko['stok_menipis']) }}</div>
                <div class="label">Stok Menipis (1&ndash;5)</div>
            </td>
            <td>
                <div class="nilai">{{ number_format($ringkasanToko['stok_habis']) }}</div>
                <div class="label">Stok Habis</div>
            </td>
        </tr>
    </table>

    <h2>Penjualan pada Periode</h2>

    <table class="kartu">
        <tr>
            <td>
                <div class="nilai">{{ number_format($ringkasan['jumlah_pesanan']) }}</div>
                <div class="label">Pesanan</div>
            </td>
            <td>
                <div class="nilai">{{ number_format($ringkasan['unit_terjual']) }}</div>
                <div class="label">Unit Terjual</div>
            </td>
            <td colspan="2">
                <div class="nilai">{{ rp($ringkasan['total']) }}</div>
                <div class="label">Omzet Periode</div>
            </td>
        </tr>
    </table>

    <h2>Kinerja per Kategori</h2>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 44%">Kategori</th>
                <th class="tengah">Produk</th>
                <th class="tengah">Unit Terjual</th>
                <th class="kanan">Omzet</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($perKategori as $baris)
                <tr>
                    <td class="tebal">{{ $baris['kategori'] }}</td>
                    <td class="tengah">{{ $baris['produk'] }}</td>
                    <td class="tengah">{{ number_format($baris['unit']) }}</td>
                    <td class="kanan tebal">{{ rp($baris['omzet']) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="kosong">Belum ada kategori.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Kinerja Produk ({{ $kinerjaProduk->count() }})</h2>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 30%">Produk</th>
                <th style="width: 18%">Kategori</th>
                <th class="kanan">Harga</th>
                <th class="tengah">Stok</th>
                <th class="tengah">Status</th>
                <th class="tengah">Terjual</th>
                <th class="kanan">Omzet</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($kinerjaProduk as $baris)
                <tr>
                    <td>{{ $baris['produk'] }}</td>
                    <td>{{ $baris['kategori'] }}</td>
                    <td class="kanan">{{ rp($baris['harga']) }}</td>
                    <td class="tengah">{{ $baris['stok'] }}</td>
                    <td class="tengah">{{ $baris['status'] }}</td>
                    <td class="tengah">{{ number_format($baris['unit']) }}</td>
                    <td class="kanan tebal">{{ rp($baris['omzet']) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="kosong">Belum ada produk.</td></tr>
            @endforelse
        </tbody>
    </table>

@endsection
