@extends('admin.laporan._cetak-dasar')

@section('judul', 'Laporan Transaksi')

@section('isi')

    <table class="kartu">
        <tr>
            <td>
                <div class="nilai">{{ number_format($ringkasan['jumlah_pesanan']) }}</div>
                <div class="label">Jumlah Pesanan</div>
            </td>
            <td>
                <div class="nilai">{{ number_format($ringkasan['unit_terjual']) }}</div>
                <div class="label">Unit Terjual</div>
            </td>
            <td>
                <div class="nilai">{{ rp($ringkasan['total']) }}</div>
                <div class="label">Total Transaksi</div>
            </td>
            <td>
                <div class="nilai">{{ rp($ringkasan['rata_rata']) }}</div>
                <div class="label">Rata-rata / Pesanan</div>
            </td>
        </tr>
    </table>

    <table class="kartu">
        <tr>
            <td colspan="2">
                <div class="nilai">{{ rp($ringkasan['subtotal']) }}</div>
                <div class="label">Subtotal Produk</div>
            </td>
            <td colspan="2">
                <div class="nilai">{{ rp($ringkasan['ongkir']) }}</div>
                <div class="label">Ongkos Kirim Terkumpul</div>
            </td>
        </tr>
    </table>

    {{-- Tiga rincian disandingkan dalam satu baris agar tidak memakan halaman
         sendiri-sendiri; dompdf tidak mengenal grid, jadi dipakai tabel. --}}
    <table width="100%" style="border-collapse: separate; border-spacing: 6px 0;">
        <tr>
            @foreach ([['Per Status', $perStatus, 'Nilai'], ['Per Metode Bayar', $perMetode, 'Nilai'], ['Per Kurir', $perKurir, 'Ongkir']] as [$judulRincian, $data, $kolomNilai])
                <td width="33%" style="vertical-align: top; padding: 0;">
                    <h2>{{ $judulRincian }}</h2>
                    <table class="data">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th class="tengah">Jml</th>
                                <th class="kanan">{{ $kolomNilai }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $baris)
                                <tr>
                                    <td>{{ $baris['label'] }}</td>
                                    <td class="tengah">{{ $baris['jumlah'] }}</td>
                                    <td class="kanan tebal">{{ rp($baris['nilai']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="kosong">Tidak ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </td>
            @endforeach
        </tr>
    </table>

    @if ($pelangganTeratas->isNotEmpty())
        <h2>Pelanggan Teratas</h2>
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 34%">Nama</th>
                    <th style="width: 36%">Email</th>
                    <th class="tengah">Pesanan</th>
                    <th class="kanan">Nilai</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pelangganTeratas as $baris)
                    <tr>
                        <td>{{ $baris['nama'] }}</td>
                        <td>{{ $baris['email'] }}</td>
                        <td class="tengah">{{ $baris['jumlah'] }}</td>
                        <td class="kanan tebal">{{ rp($baris['nilai']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Daftar Transaksi ({{ $transaksi->count() }})</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Tanggal</th>
                <th>Pelanggan</th>
                <th class="tengah">Item</th>
                <th>Metode</th>
                <th>Kurir</th>
                <th>Status</th>
                <th class="kanan">Subtotal</th>
                <th class="kanan">Ongkir</th>
                <th class="kanan">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transaksi as $baris)
                <tr>
                    <td class="tebal">{{ $baris['invoice'] }}</td>
                    <td>{{ $baris['tanggal']?->format('d/m/Y H:i') }}</td>
                    <td>{{ $baris['pelanggan'] }}</td>
                    <td class="tengah">{{ $baris['item'] }}</td>
                    <td>{{ $baris['metode'] }}</td>
                    <td>{{ $baris['kurir'] }}</td>
                    <td>{{ $baris['status'] }}</td>
                    <td class="kanan">{{ rp($baris['subtotal']) }}</td>
                    <td class="kanan">{{ rp($baris['ongkir']) }}</td>
                    <td class="kanan tebal">{{ rp($baris['total']) }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="kosong">Tidak ada transaksi pada kriteria ini.</td></tr>
            @endforelse
        </tbody>
    </table>

@endsection
