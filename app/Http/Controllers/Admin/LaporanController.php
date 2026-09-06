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
        if ($belumSiap = $this->pustakaSiap('pdf')) {
            return $belumSiap;
        }

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

    public function transaksiExcel(Request $request)
    {
        if ($belumSiap = $this->pustakaSiap('excel')) {
            return $belumSiap;
        }

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

    /**
     * Pastikan pustaka pembuat berkas benar-benar terpasang.
     *
     * Tanpa ini, "composer install" yang terlewat di server berujung galat 500
     * berisi nama kelas — tidak berguna bagi pengguna dan membocorkan detail
     * internal bila APP_DEBUG sempat menyala.
     */
    private function pustakaSiap(string $jenis): ?\Illuminate\Http\RedirectResponse
    {
        if (unduhanLaporanSiap()[$jenis]) {
            return null;
        }

        $paket = $jenis === 'pdf' ? 'barryvdh/laravel-dompdf' : 'phpoffice/phpspreadsheet';

        \Illuminate\Support\Facades\Log::error(
            "Paket {$paket} belum terpasang. Jalankan \"composer install --no-dev\" di server."
        );

        return back()->with('error', 'Modul unduhan belum terpasang di server. Hubungi pengelola sistem.');
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
