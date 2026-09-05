<?php

namespace App\Support;

use App\Models\Pesanan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Menerjemahkan parameter permintaan menjadi kriteria laporan.
 *
 * Dipisahkan ke kelasnya sendiri supaya halaman, unduhan PDF, dan unduhan
 * Excel membaca kriteria yang sama persis. Kalau logikanya diulang di tiga
 * tempat, angka pada berkas unduhan cepat atau lambat berbeda dari yang
 * dilihat di layar.
 */
class FilterLaporan
{
    public readonly Carbon $dari;

    public readonly Carbon $sampai;

    public readonly ?string $status;

    public readonly ?int $metodePembayaranId;

    public readonly ?string $kurir;

    public readonly ?string $cari;

    /** Sertakan pesanan batal dalam perhitungan. */
    public readonly bool $sertakanBatal;

    public function __construct(Request $request)
    {
        // Rentang baku: bulan berjalan. Laporan tanpa batas waktu tumbuh tanpa
        // henti dan menjadi tidak berguna begitu transaksinya banyak.
        $this->dari = $this->tanggal($request->input('dari')) ?? Carbon::now()->startOfMonth();
        $this->sampai = $this->tanggal($request->input('sampai')) ?? Carbon::now();

        $this->status = $this->pilihan($request->input('status'), array_keys(Pesanan::STATUS));
        $this->kurir = $this->pilihan($request->input('kurir'), array_keys(\App\Http\Controllers\CheckoutController::KURIR));
        $this->metodePembayaranId = $request->filled('metode') ? (int) $request->input('metode') : null;
        $this->cari = $request->filled('cari') ? trim((string) $request->input('cari')) : null;
        $this->sertakanBatal = $request->boolean('sertakan_batal');
    }

    /**
     * Pertanyaan dasar untuk seluruh laporan transaksi.
     */
    public function kueri(): Builder
    {
        return Pesanan::query()
            ->with('user', 'pembayaran.metodePembayaran')
            ->whereBetween('created_at', [$this->dari->copy()->startOfDay(), $this->sampai->copy()->endOfDay()])
            ->when(! $this->sertakanBatal, fn ($q) => $q->where('status', '!=', 'dibatalkan'))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->kurir, fn ($q) => $q->where('kurir', $this->kurir))
            ->when($this->metodePembayaranId, fn ($q) => $q->whereHas(
                'pembayaran',
                fn ($p) => $p->where('metode_pembayaran_id', $this->metodePembayaranId),
            ))
            ->when($this->cari, fn ($q) => $q->where(function ($qq) {
                $qq->where('no_invoice', 'like', "%{$this->cari}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$this->cari}%")
                        ->orWhere('email', 'like', "%{$this->cari}%"));
            }));
    }

    /**
     * Kriteria dalam bahasa manusia, untuk kepala berkas unduhan.
     *
     * Berkas laporan sering beredar terlepas dari layar yang membuatnya, jadi
     * pembacanya harus bisa tahu persis data mana yang sedang dilihatnya.
     */
    public function ringkasanKriteria(): array
    {
        $baris = [
            'Periode' => tanggalIndo($this->dari).' — '.tanggalIndo($this->sampai),
        ];

        if ($this->status) {
            $baris['Status'] = Pesanan::STATUS[$this->status];
        }

        if ($this->kurir) {
            $baris['Kurir'] = $this->kurir;
        }

        if ($this->metodePembayaranId) {
            $baris['Metode Bayar'] = \App\Models\MetodePembayaran::find($this->metodePembayaranId)?->nama ?? '-';
        }

        if ($this->cari) {
            $baris['Pencarian'] = $this->cari;
        }

        $baris['Pesanan Batal'] = $this->sertakanBatal ? 'Disertakan' : 'Tidak disertakan';

        return $baris;
    }

    /**
     * Parameter aktif, untuk mempertahankan filter pada tautan unduhan.
     */
    public function sebagaiParameter(): array
    {
        return array_filter([
            'dari' => $this->dari->toDateString(),
            'sampai' => $this->sampai->toDateString(),
            'status' => $this->status,
            'kurir' => $this->kurir,
            'metode' => $this->metodePembayaranId,
            'cari' => $this->cari,
            'sertakan_batal' => $this->sertakanBatal ? 1 : null,
        ], fn ($n) => $n !== null && $n !== '');
    }

    public function namaBerkas(string $awalan): string
    {
        return sprintf(
            '%s-%s-sd-%s',
            $awalan,
            $this->dari->format('Ymd'),
            $this->sampai->format('Ymd'),
        );
    }

    private function tanggal(?string $nilai): ?Carbon
    {
        if (blank($nilai)) {
            return null;
        }

        try {
            return Carbon::parse($nilai);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Terima nilai hanya bila termasuk pilihan yang sah.
     *
     * Nilai dari URL tidak bisa dipercaya; menyaringnya di sini membuat
     * seluruh pemanggil aman tanpa perlu memvalidasi ulang.
     */
    private function pilihan(mixed $nilai, array $sah): ?string
    {
        $nilai = is_string($nilai) ? trim($nilai) : null;

        return $nilai !== null && in_array($nilai, $sah, true) ? $nilai : null;
    }
}
