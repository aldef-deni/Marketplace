<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\Produk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Keikutsertaan toko pada kampanye flash sale — untuk admin.
 *
 * Admin tidak menyusun kampanye; ia memutuskan ikut atau tidak, lalu memilih
 * produk mana yang disertakan beserta harga dan kuotanya.
 */
class FlashSalePartisipasiController extends Controller
{
    public function index()
    {
        // Draf milik superadmin sengaja tidak ditampilkan: kampanye yang belum
        // diterbitkan belum tentu jadi, dan menampilkannya hanya membingungkan.
        $kampanyes = FlashSale::terbit()
            ->withCount('produks')
            ->orderByRaw('CASE WHEN selesai_at >= ? THEN 0 ELSE 1 END', [now()])
            ->orderBy('mulai_at')
            ->paginate(12);

        $jumlah = [
            'berlangsung' => FlashSale::berlangsung()->count(),
            'perlu_keputusan' => FlashSale::terbit()->where('diikuti', false)
                ->where('selesai_at', '>=', now())->count(),
            'diikuti' => FlashSale::terbit()->where('diikuti', true)->count(),
        ];

        return view('admin.flash-sale.partisipasi-index', compact('kampanyes', 'jumlah'));
    }

    public function show(FlashSale $flashSale)
    {
        abort_unless($flashSale->aktif, 404);

        $flashSale->load(['produks.produk.kategori']);

        $terpilih = $flashSale->produks->keyBy('produk_id');

        $produks = Produk::with('kategori')
            ->orderBy('nama')
            ->get()
            ->map(fn (Produk $p) => [
                'model' => $p,
                'baris' => $terpilih[$p->id] ?? null,
            ]);

        return view('admin.flash-sale.partisipasi-show', compact('flashSale', 'produks'));
    }

    /**
     * Ikut atau berhenti mengikuti kampanye.
     */
    public function toggleIkut(FlashSale $flashSale)
    {
        abort_unless($flashSale->aktif, 404);

        if ($flashSale->sudahBerakhir()) {
            return back()->with('error', 'Kampanye sudah berakhir, keikutsertaannya tidak dapat diubah.');
        }

        $ikut = ! $flashSale->diikuti;

        $flashSale->update([
            'diikuti' => $ikut,
            'diikuti_at' => $ikut ? now() : null,
            'diikuti_oleh' => $ikut ? auth()->id() : null,
        ]);

        return back()->with('success', $ikut
            ? 'Toko mengikuti kampanye ini. Pilih produk yang disertakan.'
            : 'Toko berhenti mengikuti kampanye. Harga flash tidak lagi berlaku.');
    }

    /**
     * Simpan satu produk pada kampanye — menambahkan, memperbarui, atau melepas.
     *
     * Disimpan per baris, bukan sekaligus satu tabel, agar admin dengan ratusan
     * produk tidak perlu menggulung ke tombol simpan di ujung halaman.
     */
    public function simpanBaris(Request $request, FlashSale $flashSale, Produk $produk)
    {
        abort_unless($flashSale->aktif, 404);

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
            // Galat dan masukan dikantongi per produk agar baris lain tidak ikut
            // menampilkan pesan atau nilai yang bukan miliknya.
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

    /**
     * Kembali ke halaman kelola tepat pada baris yang barusan disentuh.
     */
    private function kembaliKeBaris(FlashSale $flashSale, Produk $produk): RedirectResponse
    {
        return redirect()->to(route('admin.flash-sale.kelola', $flashSale).'#produk-'.$produk->id);
    }
}
