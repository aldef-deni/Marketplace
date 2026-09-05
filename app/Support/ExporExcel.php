<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menyusun berkas Excel laporan.
 *
 * Ditulis langsung di atas PhpSpreadsheet, bukan lewat pembungkus tambahan,
 * supaya hanya ada satu dependensi dan pemakaian memorinya bisa dikendalikan —
 * hal yang penting pada hosting bersama.
 */
class ExporExcel
{
    private const BIRU = '0B5FB0';

    private const ORANYE = 'F59300';

    private Spreadsheet $buku;

    private int $lembarKe = 0;

    public function __construct(
        private string $judul,
        private array $kriteria,
    ) {
        $this->buku = new Spreadsheet;
        $this->buku->getProperties()
            ->setCreator(config('brand.nama'))
            ->setTitle($judul);
    }

    /**
     * Tambahkan satu lembar berisi tabel.
     *
     * @param  array<int, string>  $kolom  Judul kolom
     * @param  iterable<int, array>  $baris  Isi baris, urut sesuai $kolom
     * @param  array<int, string>  $format  Kolom yang diformat rupiah, misalnya ['F', 'G']
     */
    public function lembar(string $nama, array $kolom, iterable $baris, array $format = []): self
    {
        $lembar = $this->lembarKe === 0
            ? $this->buku->getActiveSheet()
            : $this->buku->createSheet();

        $this->lembarKe++;

        // Nama lembar Excel dibatasi 31 karakter dan menolak sebagian tanda baca.
        $lembar->setTitle(mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '', $nama), 0, 31));

        $kolomTerakhir = $this->hurufKolom(count($kolom));
        $baris0 = $this->tulisKepala($lembar, $kolomTerakhir);

        // Judul kolom
        $lembar->fromArray($kolom, null, 'A'.$baris0);
        $lembar->getStyle("A{$baris0}:{$kolomTerakhir}{$baris0}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::BIRU]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $lembar->getRowDimension($baris0)->setRowHeight(22);

        // Isi
        $nomor = $baris0 + 1;
        foreach ($baris as $satu) {
            $lembar->fromArray(array_values($satu), null, 'A'.$nomor);
            $nomor++;
        }

        $barisAkhir = max($nomor - 1, $baris0 + 1);

        $lembar->getStyle("A{$baris0}:{$kolomTerakhir}{$barisAkhir}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDE3EC']]],
        ]);

        foreach ($format as $huruf) {
            $lembar->getStyle("{$huruf}".($baris0 + 1).":{$huruf}{$barisAkhir}")
                ->getNumberFormat()->setFormatCode('#,##0');
        }

        foreach (range(1, count($kolom)) as $i) {
            $lembar->getColumnDimension($this->hurufKolom($i))->setAutoSize(true);
        }

        $lembar->freezePane('A'.($baris0 + 1));

        return $this;
    }

    /**
     * Kepala berkas: identitas laporan dan kriteria yang dipakai.
     *
     * Berkas unduhan sering beredar terpisah dari layar yang membuatnya, jadi
     * pembacanya harus bisa memastikan sendiri data mana yang dilihatnya.
     */
    private function tulisKepala(Worksheet $lembar, string $kolomTerakhir): int
    {
        $lembar->setCellValue('A1', config('brand.nama').' — '.$this->judul);
        $lembar->mergeCells("A1:{$kolomTerakhir}1");
        $lembar->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => self::BIRU]],
        ]);
        $lembar->getRowDimension(1)->setRowHeight(24);

        $nomor = 2;
        foreach ($this->kriteria as $label => $nilai) {
            $lembar->setCellValue('A'.$nomor, $label.':');
            $lembar->setCellValue('B'.$nomor, $nilai);
            $lembar->getStyle('A'.$nomor)->getFont()->setBold(true);
            $nomor++;
        }

        $lembar->setCellValue('A'.$nomor, 'Dibuat:');
        $lembar->setCellValue('B'.$nomor, now()->translatedFormat('d F Y H:i'));
        $lembar->getStyle('A'.$nomor)->getFont()->setBold(true);
        $lembar->getStyle('A'.$nomor)->getFont()->getColor()->setRGB(self::ORANYE);

        return $nomor + 2;
    }

    /**
     * Kirim sebagai unduhan.
     *
     * Dialirkan langsung, bukan disimpan sementara ke disk, supaya tidak ada
     * berkas yatim yang tertinggal di server bila unduhan terputus.
     */
    public function unduh(string $namaBerkas): StreamedResponse
    {
        $penulis = new Xlsx($this->buku);
        $buku = $this->buku;

        return response()->streamDownload(function () use ($penulis, $buku) {
            $penulis->save('php://output');
            $buku->disconnectWorksheets();
        }, $namaBerkas.'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    private function hurufKolom(int $nomor): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($nomor);
    }
}
