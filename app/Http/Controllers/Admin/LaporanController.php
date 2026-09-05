<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Controller;
use App\Models\MetodePembayaran;
use App\Models\Pesanan;
use App\Support\ExporExcel;
use App\Support\FilterLaporan;
use App\Support\PenyusunLaporan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Laporan transaksi dan kondisi toko.
 *
 * Laporan transaksi terbuka bagi admin maupun superadmin. Laporan toko —
 * kondisi katalog, stok, dan kinerja produk — dibatasi superadmin lewat
 * middleware pada berkas rute.
 */
class LaporanController extends Controller
{
    public function transaksi(Request $request)
    {
        [$filter, $laporan] = $this->siapkan($request);

        return view('admin.laporan.transaksi', [
            'filter' => $filter,
            'ringkasan' => $laporan->ringkasan(),
            'perStatus' => $laporan->perStatus(),
            'perMetode' => $laporan->perMetodePembayaran(),
            'perKurir' => $laporan->perKurir(),
            'perHari' => $laporan->perHari(),
            'pelangganTeratas' => $laporan->pelangganTeratas(),
            'transaksi' => $laporan->daftarTransaksi(),
            ...$this->pilihanFilter(),
        ]);
    }

    public function transaksiPdf(Request $request)
    {
        [$filter, $laporan] = $this->siapkan($request);

        $pdf = Pdf::loadView('admin.laporan.cetak-transaksi', [
            'filter' => $filter,
            'ringkasan' => $laporan->ringkasan(),
            'perStatus' => $laporan->perStatus(),
            'perMetode' => $laporan->perMetodePembayaran(),
            'perKurir' => $laporan->perKurir(),
            'pelangganTeratas' => $laporan->pelangganTeratas(),
            'transaksi' => $laporan->daftarTransaksi(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filter->namaBerkas('laporan-transaksi').'.pdf');
    }

    public function transaksiExcel(Request $request): StreamedResponse
    {
        [$filter, $laporan] = $this->siapkan($request);
        $ringkasan = $laporan->ringkasan();

        $berkas = new ExporExcel('Laporan Transaksi', $filter->ringkasanKriteria());

        $berkas->lembar('Ringkasan', ['Keterangan', 'Nilai'], [
            ['Jumlah pesanan', $ringkasan['jumlah_pesanan']],
            ['Unit terjual', $ringkasan['unit_terjual']],
            ['Subtotal produk', $ringkasan['subtotal']],
            ['Ongkos kirim', $ringkasan['ongkir']],
            ['Total transaksi', $ringkasan['total']],
            ['Rata-rata per pesanan', round($ringkasan['rata_rata'])],
        ], ['B']);

        $berkas->lembar('Per Status', ['Status', 'Jumlah', 'Nilai'],
            $perStatus = $laporan->perStatus()->map(fn ($b) => [$b['label'], $b['jumlah'], $b['nilai']]), ['C']);

        $berkas->lembar('Per Metode Bayar', ['Metode', 'Jumlah', 'Nilai'],
            $laporan->perMetodePembayaran()->map(fn ($b) => [$b['label'], $b['jumlah'], $b['nilai']]), ['C']);

        $berkas->lembar('Per Kurir', ['Kurir', 'Jumlah', 'Ongkir'],
            $laporan->perKurir()->map(fn ($b) => [$b['label'], $b['jumlah'], $b['nilai']]), ['C']);

        $berkas->lembar('Per Hari', ['Tanggal', 'Jumlah', 'Nilai'],
            $laporan->perHari()->map(fn ($b) => [$b['tanggal'], $b['jumlah'], $b['nilai']]), ['C']);

        $berkas->lembar('Pelanggan Teratas', ['Nama', 'Email', 'Pesanan', 'Nilai'],
            $laporan->pelangganTeratas(25)->map(fn ($b) => [$b['nama'], $b['email'], $b['jumlah'], $b['nilai']]), ['D']);

        $berkas->lembar('Daftar Transaksi',
            ['Invoice', 'Tanggal', 'Pelanggan', 'Email', 'Item', 'Metode', 'Kurir', 'Status', 'Subtotal', 'Ongkir', 'Total'],
            $laporan->daftarTransaksi()->map(fn ($b) => [
                $b['invoice'],
                $b['tanggal']?->format('Y-m-d H:i'),
                $b['pelanggan'], $b['email'], $b['item'],
                $b['metode'], $b['kurir'], $b['status'],
                $b['subtotal'], $b['ongkir'], $b['total'],
            ]),
            ['I', 'J', 'K'],
        );

        unset($perStatus);

        return $berkas->unduh($filter->namaBerkas('laporan-transaksi'));
    }

    public function toko(Request $request)
    {
        [$filter, $laporan] = $this->siapkan($request);

        return view('admin.laporan.toko', [
            'filter' => $filter,
            'ringkasanToko' => $laporan->ringkasanToko(),
            'ringkasan' => $laporan->ringkasan(),
            'perKategori' => $laporan->perKategori(),
            'kinerjaProduk' => $laporan->kinerjaProduk(),
            ...$this->pilihanFilter(),
        ]);
    }

    public function tokoPdf(Request $request)
    {
        [$filter, $laporan] = $this->siapkan($request);

        $pdf = Pdf::loadView('admin.laporan.cetak-toko', [
            'filter' => $filter,
            'ringkasanToko' => $laporan->ringkasanToko(),
            'ringkasan' => $laporan->ringkasan(),
            'perKategori' => $laporan->perKategori(),
            'kinerjaProduk' => $laporan->kinerjaProduk(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filter->namaBerkas('laporan-toko').'.pdf');
    }

    public function tokoExcel(Request $request): StreamedResponse
    {
        [$filter, $laporan] = $this->siapkan($request);
        $toko = $laporan->ringkasanToko();

        $berkas = new ExporExcel('Laporan Toko', $filter->ringkasanKriteria());

        $berkas->lembar('Ringkasan Toko', ['Keterangan', 'Nilai'], [
            ['Total produk', $toko['total_produk']],
            ['Produk aktif', $toko['produk_aktif']],
            ['Produk nonaktif', $toko['produk_nonaktif']],
            ['Total kategori', $toko['total_kategori']],
            ['Stok tersedia (unit)', $toko['stok_total']],
            ['Nilai stok', $toko['nilai_stok']],
            ['Produk stok menipis', $toko['stok_menipis']],
            ['Produk stok habis', $toko['stok_habis']],
            ['Total pelanggan', $toko['total_pelanggan']],
        ], ['B']);

        $berkas->lembar('Per Kategori', ['Kategori', 'Jumlah Produk', 'Unit Terjual', 'Omzet'],
            $laporan->perKategori()->map(fn ($b) => [$b['kategori'], $b['produk'], $b['unit'], $b['omzet']]), ['D']);

        $berkas->lembar('Kinerja Produk',
            ['Produk', 'Kategori', 'Harga', 'Stok', 'Status', 'Unit Terjual', 'Omzet'],
            $laporan->kinerjaProduk()->map(fn ($b) => [
                $b['produk'], $b['kategori'], $b['harga'], $b['stok'], $b['status'], $b['unit'], $b['omzet'],
            ]),
            ['C', 'G'],
        );

        return $berkas->unduh($filter->namaBerkas('laporan-toko'));
    }

    /**
     * @return array{0: FilterLaporan, 1: PenyusunLaporan}
     */
    private function siapkan(Request $request): array
    {
        $filter = new FilterLaporan($request);

        return [$filter, new PenyusunLaporan($filter)];
    }

    private function pilihanFilter(): array
    {
        return [
            'pilihanStatus' => Pesanan::STATUS,
            'pilihanKurir' => array_keys(CheckoutController::KURIR),
            'pilihanMetode' => MetodePembayaran::orderBy('nama')->get(['id', 'nama']),
        ];
    }
}
