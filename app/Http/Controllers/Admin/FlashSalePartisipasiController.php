<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\FlashSaleProduk;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
     * Simpan pilihan produk beserta harga dan kuotanya.
     */
    public function simpanProduk(Request $request, FlashSale $flashSale)
    {
        abort_unless($flashSale->aktif, 404);

        if ($flashSale->sudahBerakhir()) {
            return back()->with('error', 'Kampanye sudah berakhir, produknya tidak dapat diubah.');
        }

        $data = $request->validate([
            'produk' => ['array'],
            'produk.*.ikut' => ['nullable', 'boolean'],
            'produk.*.harga_flash' => ['nullable', 'numeric', 'min:0'],
            'produk.*.kuota' => ['nullable', 'integer', 'min:1'],
        ]);

        $dipilih = collect($data['produk'] ?? [])->filter(fn ($b) => ! empty($b['ikut']));

        // Harga dan kuota divalidasi di sini, bukan lewat aturan bersyarat,
        // agar pesannya bisa menyebut produk mana yang bermasalah.
        $galat = [];
        $produks = Produk::whereIn('id', $dipilih->keys())->get()->keyBy('id');

        foreach ($dipilih as $id => $baris) {
            $produk = $produks[$id] ?? null;

            if (! $produk) {
                continue;
            }

            $harga = (float) ($baris['harga_flash'] ?? 0);
            $kuota = (int) ($baris['kuota'] ?? 0);

            if ($harga <= 0) {
                $galat["produk.{$id}.harga_flash"] = "Harga flash {$produk->nama} wajib diisi.";
            } elseif ($harga >= (float) $produk->harga) {
                $galat["produk.{$id}.harga_flash"] = "Harga flash {$produk->nama} harus lebih murah dari harga normal.";
            }

            if ($kuota < 1) {
                $galat["produk.{$id}.kuota"] = "Kuota {$produk->nama} minimal 1.";
            } elseif ($kuota > $produk->stok) {
                $galat["produk.{$id}.kuota"] = "Kuota {$produk->nama} melebihi stok tersedia ({$produk->stok}).";
            }
        }

        if ($galat !== []) {
            return back()->withErrors($galat)->withInput();
        }

        DB::transaction(function () use ($flashSale, $dipilih) {
            // Produk yang tidak lagi dicentang dilepas dari kampanye.
            $flashSale->produks()->whereNotIn('produk_id', $dipilih->keys())->delete();

            foreach ($dipilih as $id => $baris) {
                $flashSale->produks()->updateOrCreate(
                    ['produk_id' => (int) $id],
                    [
                        'harga_flash' => (float) $baris['harga_flash'],
                        'kuota' => (int) $baris['kuota'],
                    ],
                );
            }
        });

        return back()->with('success', $dipilih->count().' produk disimpan pada kampanye ini.');
    }

    /**
     * Lepas satu produk dari kampanye.
     */
    public function hapusProduk(FlashSale $flashSale, FlashSaleProduk $baris)
    {
        abort_unless($flashSale->aktif && $baris->flash_sale_id === $flashSale->id, 404);

        $baris->delete();

        return back()->with('success', 'Produk dilepas dari kampanye.');
    }
}
