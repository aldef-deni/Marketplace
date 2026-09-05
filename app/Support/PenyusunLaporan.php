<?php

namespace App\Support;

use App\Models\Pesanan;
use App\Models\Produk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Menghitung isi laporan.
 *
 * Seluruh format — halaman, PDF, dan Excel — memakai penyusun yang sama agar
 * angkanya tidak mungkin berbeda antar keluaran.
 */
class PenyusunLaporan
{
    public function __construct(private FilterLaporan $filter) {}

    /**
     * Ringkasan sekilas transaksi pada rentang terpilih.
     */
    public function ringkasan(): array
    {
        $agregat = $this->filter->kueri()
            ->selectRaw('COUNT(*) as jumlah, COALESCE(SUM(subtotal),0) as subtotal, COALESCE(SUM(ongkir),0) as ongkir, COALESCE(SUM(total),0) as total')
            ->first();

        $jumlah = (int) ($agregat->jumlah ?? 0);

        // Unit terjual dihitung dari baris pesanan, bukan dari kolom pesanan,
        // sebab satu pesanan bisa memuat banyak produk.
        $unit = (int) DB::table('pesanan_items')
            ->whereIn('pesanan_id', $this->filter->kueri()->select('id'))
            ->sum('qty');

        return [
            'jumlah_pesanan' => $jumlah,
            'subtotal' => (float) ($agregat->subtotal ?? 0),
            'ongkir' => (float) ($agregat->ongkir ?? 0),
            'total' => (float) ($agregat->total ?? 0),
            'rata_rata' => $jumlah > 0 ? (float) $agregat->total / $jumlah : 0.0,
            'unit_terjual' => $unit,
        ];
    }

    /**
     * Rincian jumlah dan nilai per status pesanan.
     */
    public function perStatus(): Collection
    {
        $data = $this->filter->kueri()
            ->reorder()
            ->selectRaw('status, COUNT(*) as jumlah, COALESCE(SUM(total),0) as nilai')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $nilai = $this->filter->kueri()
            ->reorder()
            ->selectRaw('status, COALESCE(SUM(total),0) as nilai')
            ->groupBy('status')
            ->pluck('nilai', 'status');

        return collect(Pesanan::STATUS)
            ->map(fn ($label, $kunci) => [
                'label' => $label,
                'jumlah' => (int) ($data[$kunci] ?? 0),
                'nilai' => (float) ($nilai[$kunci] ?? 0),
            ])
            ->filter(fn ($b) => $b['jumlah'] > 0)
            ->values();
    }

    public function perMetodePembayaran(): Collection
    {
        return collect(DB::table('pesanans')
            ->join('pembayarans', 'pembayarans.pesanan_id', '=', 'pesanans.id')
            ->join('metode_pembayarans', 'metode_pembayarans.id', '=', 'pembayarans.metode_pembayaran_id')
            ->whereIn('pesanans.id', $this->filter->kueri()->select('id'))
            ->selectRaw('metode_pembayarans.nama as label, COUNT(*) as jumlah, COALESCE(SUM(pesanans.total),0) as nilai')
            ->groupBy('metode_pembayarans.nama')
            ->orderByDesc('nilai')
            ->get())
            ->map(fn ($b) => ['label' => $b->label, 'jumlah' => (int) $b->jumlah, 'nilai' => (float) $b->nilai]);
    }

    public function perKurir(): Collection
    {
        return collect(DB::table('pesanans')
            ->whereIn('id', $this->filter->kueri()->select('id'))
            ->selectRaw('kurir as label, COUNT(*) as jumlah, COALESCE(SUM(ongkir),0) as nilai')
            ->groupBy('kurir')
            ->orderByDesc('jumlah')
            ->get())
            ->map(fn ($b) => ['label' => $b->label, 'jumlah' => (int) $b->jumlah, 'nilai' => (float) $b->nilai]);
    }

    /**
     * Nilai transaksi per hari, untuk grafik dan lampiran laporan.
     */
    public function perHari(): Collection
    {
        return collect(DB::table('pesanans')
            ->whereIn('id', $this->filter->kueri()->select('id'))
            ->selectRaw('DATE(created_at) as tanggal, COUNT(*) as jumlah, COALESCE(SUM(total),0) as nilai')
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get())
            ->map(fn ($b) => [
                'tanggal' => $b->tanggal,
                'jumlah' => (int) $b->jumlah,
                'nilai' => (float) $b->nilai,
            ]);
    }

    /**
     * Daftar transaksi, siap dipaparkan sebagai tabel.
     */
    public function daftarTransaksi(): Collection
    {
        return $this->filter->kueri()
            ->reorder()
            ->withCount('items')
            ->latest()
            ->get()
            ->map(fn (Pesanan $p) => [
                'invoice' => $p->no_invoice,
                'tanggal' => $p->created_at,
                'pelanggan' => $p->user?->name ?? '(akun terhapus)',
                'email' => $p->user?->email ?? '-',
                'item' => (int) $p->items_count,
                'metode' => $p->pembayaran?->metodePembayaran?->nama ?? '-',
                'kurir' => trim($p->kurir.' '.$p->layanan_kurir),
                'status' => $p->status_label,
                'subtotal' => (float) $p->subtotal,
                'ongkir' => (float) $p->ongkir,
                'total' => (float) $p->total,
            ]);
    }

    /**
     * Pelanggan dengan nilai belanja terbesar pada rentang terpilih.
     */
    public function pelangganTeratas(int $batas = 10): Collection
    {
        return collect(DB::table('pesanans')
            ->join('users', 'users.id', '=', 'pesanans.user_id')
            ->whereIn('pesanans.id', $this->filter->kueri()->select('id'))
            ->selectRaw('users.name as nama, users.email, COUNT(*) as jumlah, COALESCE(SUM(pesanans.total),0) as nilai')
            ->groupBy('users.name', 'users.email')
            ->orderByDesc('nilai')
            ->limit($batas)
            ->get())
            ->map(fn ($b) => [
                'nama' => $b->nama,
                'email' => $b->email,
                'jumlah' => (int) $b->jumlah,
                'nilai' => (float) $b->nilai,
            ]);
    }

    /* =====================================================================
     | Laporan toko — kondisi katalog dan kinerja produk
     ===================================================================== */

    public function ringkasanToko(): array
    {
        $produk = Produk::query();

        return [
            'total_produk' => (clone $produk)->count(),
            'produk_aktif' => (clone $produk)->where('status', 'aktif')->count(),
            'produk_nonaktif' => (clone $produk)->where('status', 'nonaktif')->count(),
            'stok_total' => (int) (clone $produk)->sum('stok'),
            'nilai_stok' => (float) (clone $produk)->selectRaw('COALESCE(SUM(stok * harga),0) as n')->value('n'),
            'stok_habis' => (clone $produk)->where('stok', '<=', 0)->count(),
            'stok_menipis' => (clone $produk)->whereBetween('stok', [1, 5])->count(),
            'total_kategori' => \App\Models\Kategori::count(),
            'total_pelanggan' => \App\Models\User::where('role', 'pengguna')->count(),
        ];
    }

    /**
     * Kinerja tiap kategori pada rentang terpilih.
     */
    public function perKategori(): Collection
    {
        $penjualan = collect(DB::table('pesanan_items')
            ->join('produks', 'produks.id', '=', 'pesanan_items.produk_id')
            ->whereIn('pesanan_items.pesanan_id', $this->filter->kueri()->select('id'))
            ->selectRaw('produks.kategori_id, COALESCE(SUM(pesanan_items.qty),0) as unit, COALESCE(SUM(pesanan_items.subtotal),0) as omzet')
            ->groupBy('produks.kategori_id')
            ->get())
            ->keyBy('kategori_id');

        return \App\Models\Kategori::withCount('produks')->orderBy('nama')->get()
            ->map(fn ($k) => [
                'kategori' => $k->nama,
                'produk' => (int) $k->produks_count,
                'unit' => (int) ($penjualan[$k->id]->unit ?? 0),
                'omzet' => (float) ($penjualan[$k->id]->omzet ?? 0),
            ])
            ->sortByDesc('omzet')
            ->values();
    }

    /**
     * Kinerja tiap produk: terjual, omzet, dan sisa stok.
     *
     * Produk tanpa penjualan tetap disertakan — justru itu yang perlu
     * diperhatikan pengelola katalog.
     */
    public function kinerjaProduk(): Collection
    {
        $penjualan = collect(DB::table('pesanan_items')
            ->whereIn('pesanan_id', $this->filter->kueri()->select('id'))
            ->selectRaw('produk_id, COALESCE(SUM(qty),0) as unit, COALESCE(SUM(subtotal),0) as omzet')
            ->groupBy('produk_id')
            ->get())
            ->keyBy('produk_id');

        return Produk::with('kategori')->orderBy('nama')->get()
            ->map(fn (Produk $p) => [
                'produk' => $p->nama,
                'kategori' => $p->kategori?->nama ?? '-',
                'harga' => (float) $p->harga,
                'stok' => (int) $p->stok,
                'status' => $p->status === 'aktif' ? 'Aktif' : 'Nonaktif',
                'unit' => (int) ($penjualan[$p->id]->unit ?? 0),
                'omzet' => (float) ($penjualan[$p->id]->omzet ?? 0),
            ])
            ->sortByDesc('omzet')
            ->values();
    }
}
