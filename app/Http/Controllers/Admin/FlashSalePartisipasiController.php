<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\Produk;
use App\Models\Toko;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Keikutsertaan toko pada kampanye flash sale.
 *
 * Pemilik toko tidak menyusun kampanye; ia memutuskan tokonya ikut atau tidak,
 * lalu memilih produk mana yang disertakan beserta harga dan kuotanya.
 *
 * Seluruh kendali di sini bekerja pada satu toko — toko milik pengguna yang
 * sedang masuk. Sejak katalog dimiliki banyak lapak, "toko ikut kampanye" tidak
 * lagi punya jawaban tunggal.
 */
class FlashSalePartisipasiController extends Controller
{
    use MengelolaToko;

    public function index()
    {
        $toko = $this->tokoSaya();

        if (! $toko) {
            return view('admin.flash-sale.tanpa-toko');
        }

        // Draf milik superadmin sengaja tidak ditampilkan: kampanye yang belum
        // diterbitkan belum tentu jadi, dan menampilkannya hanya membingungkan.
        $kampanyes = FlashSale::terbit()
            ->withCount(['produks as produks_count' => fn ($q) => $q->whereHas('produk', fn ($p) => $p->where('toko_id', $toko->id))])
            ->with(['tokos' => fn ($q) => $q->where('tokos.id', $toko->id)])
            ->orderByRaw('CASE WHEN selesai_at >= ? THEN 0 ELSE 1 END', [now()])
            ->orderBy('mulai_at')
            ->paginate(12);

        $diikutiIds = $toko->flashSales()->pluck('flash_sales.id');

        $jumlah = [
            'berlangsung' => FlashSale::berlangsung()->whereIn('id', $diikutiIds)->count(),
            'perlu_keputusan' => FlashSale::terbit()->where('selesai_at', '>=', now())
                ->whereNotIn('id', $diikutiIds)->count(),
            'diikuti' => $diikutiIds->count(),
        ];

        return view('admin.flash-sale.partisipasi-index', [
            'kampanyes' => $kampanyes,
            'jumlah' => $jumlah,
            'toko' => $toko,
            'tokos' => $this->tokoTersedia(),
        ]);
    }

    public function show(FlashSale $flashSale)
    {
        abort_unless($flashSale->aktif, 404);

        $toko = $this->tokoSaya();
        abort_unless($toko !== null, 403, 'Halaman ini untuk pemilik toko.');

        $flashSale->load(['produks.produk.kategori']);

        $terpilih = $flashSale->produks
            ->filter(fn ($b) => $b->produk?->toko_id === $toko->id)
            ->keyBy('produk_id');

        $produks = Produk::with('kategori')
            ->where('toko_id', $toko->id)
            ->orderBy('nama')
            ->get()
            ->map(fn (Produk $p) => ['model' => $p, 'baris' => $terpilih[$p->id] ?? null]);

        $diikuti = $flashSale->diikutiOleh($toko);

        return view('admin.flash-sale.partisipasi-show', [
            'flashSale' => $flashSale,
            'produks' => $produks,
            'toko' => $toko,
            'diikuti' => $diikuti,
            'tokos' => $this->tokoTersedia(),
        ]);
    }

    /**
     * Ikut atau berhenti mengikuti kampanye, untuk toko milik pengguna ini.
     */
    public function toggleIkut(FlashSale $flashSale)
    {
        abort_unless($flashSale->aktif, 404);

        $toko = $this->tokoSaya();
        abort_unless($toko !== null, 403, 'Halaman ini untuk pemilik toko.');

        if ($flashSale->sudahBerakhir()) {
            return back()->with('error', 'Kampanye sudah berakhir, keikutsertaannya tidak dapat diubah.');
        }

        if ($flashSale->diikutiOleh($toko)) {
            $flashSale->tokos()->detach($toko->id);

            return back()->with('success', 'Toko berhenti mengikuti kampanye. Harga flash tidak lagi berlaku.');
        }

        $flashSale->tokos()->attach($toko->id, [
            'diikuti_at' => now(),
            'diikuti_oleh' => auth()->id(),
        ]);

        return back()->with('success', 'Toko mengikuti kampanye ini. Pilih produk yang disertakan.');
    }

    /**
     * Simpan satu produk pada kampanye — menambahkan, memperbarui, atau melepas.
     *
     * Disimpan per baris, bukan sekaligus satu tabel, agar toko dengan ratusan
     * produk tidak perlu menggulung ke tombol simpan di ujung halaman.
     */
    public function simpanBaris(Request $request, FlashSale $flashSale, Produk $produk)
    {
        abort_unless($flashSale->aktif, 404);

        $toko = $this->tokoSaya();
        abort_unless($toko !== null && $produk->toko_id === $toko->id, 403);

        if ($flashSale->sudahBerakhir()) {
            return $this->kembaliKeBaris($flashSale, $produk)
                ->with('error', 'Kampanye sudah berakhir, produknya tidak dapat diubah.');
        }

        if ($request->input('tindakan') === 'lepas') {
            $flashSale->produks()->where('produk_id', $produk->id)->delete();

            return $this->kembaliKeBaris($flashSale, $produk)->with('baris_dilepas', $produk->id);
        }

        $data = $request->validate([
            'harga_flash' => ['nullable', 'numeric'],
            'kuota' => ['nullable', 'integer'],
        ]);

        $harga = (float) ($data['harga_flash'] ?? 0);
        $kuota = (int) ($data['kuota'] ?? 0);

        // Divalidasi manual, bukan lewat aturan bersyarat, supaya batasannya
        // dapat menyebut angka nyata milik produk ini.
        $galat = [];

        if ($harga <= 0) {
            $galat['harga_flash'] = 'Harga flash wajib diisi.';
        } elseif ($harga >= (float) $produk->harga) {
            $galat['harga_flash'] = 'Harus lebih murah dari '.rp($produk->harga).'.';
        }

        if ($kuota < 1) {
            $galat['kuota'] = 'Kuota minimal 1.';
        } elseif ($kuota > $produk->stok) {
            $galat['kuota'] = "Melebihi stok tersedia ({$produk->stok}).";
        }

        if ($galat !== []) {
            return $this->kembaliKeBaris($flashSale, $produk)
                ->withErrors($galat, 'baris'.$produk->id)
                ->with('masukan_baris', [
                    'id' => $produk->id,
                    'harga_flash' => $request->input('harga_flash'),
                    'kuota' => $request->input('kuota'),
                ]);
        }

        $flashSale->produks()->updateOrCreate(
            ['produk_id' => $produk->id],
            ['harga_flash' => $harga, 'kuota' => $kuota],
        );

        return $this->kembaliKeBaris($flashSale, $produk)->with('baris_tersimpan', $produk->id);
    }

    private function kembaliKeBaris(FlashSale $flashSale, Produk $produk): RedirectResponse
    {
        return redirect()->to(route('admin.flash-sale.kelola', $flashSale).'#produk-'.$produk->id);
    }
}
