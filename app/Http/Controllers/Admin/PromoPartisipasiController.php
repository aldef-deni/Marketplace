<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\Promo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Keikutsertaan toko pada promo, dan pemilihan produknya.
 *
 * Melayani dua hal sekaligus karena halamannya memang sama: promo platform yang
 * perlu diputuskan ikut atau tidak, dan promo milik toko sendiri yang tinggal
 * dipilihkan produknya.
 */
class PromoPartisipasiController extends Controller
{
    use MengelolaToko;

    public function index()
    {
        $toko = $this->tokoSaya();

        if (! $toko) {
            return view('admin.promo.tanpa-toko');
        }

        // Draf pengelola sengaja tidak ditampilkan: promo yang belum diterbitkan
        // belum tentu jadi, dan menampilkannya hanya membingungkan.
        $platform = Promo::platform()->terbit()
            ->with(['tokos' => fn ($q) => $q->where('tokos.id', $toko->id)])
            ->withCount(['produks as produks_count' => fn ($q) => $q->whereHas('produk', fn ($p) => $p->where('toko_id', $toko->id))])
            ->orderByRaw('CASE WHEN selesai_at >= ? THEN 0 ELSE 1 END', [now()])
            ->orderBy('mulai_at')
            ->get();

        $sendiri = $toko->promos()
            ->withCount('produks')
            ->orderByDesc('mulai_at')
            ->get();

        $diikutiIds = $toko->promosDiikuti()->pluck('promos.id');

        $jumlah = [
            'berlangsung' => Promo::berlangsung()
                ->where(fn ($q) => $q->whereIn('id', $diikutiIds)->orWhere('toko_id', $toko->id))
                ->count(),
            'perlu_keputusan' => Promo::platform()->terbit()->where('selesai_at', '>=', now())
                ->whereNotIn('id', $diikutiIds)->count(),
            'sendiri' => $sendiri->count(),
        ];

        return view('admin.promo.partisipasi-index', compact('platform', 'sendiri', 'jumlah', 'toko'));
    }

    public function show(Promo $promo)
    {
        $toko = $this->tokoSaya();
        abort_unless($toko !== null, 403, 'Halaman ini untuk pemilik toko.');
        $this->pastikanTerjangkau($promo, $toko->id);

        $promo->load(['produks.produk.kategori']);

        $terpilih = $promo->produks
            ->filter(fn ($b) => $b->produk?->toko_id === $toko->id)
            ->keyBy('produk_id');

        $produks = Produk::with('kategori')
            ->where('toko_id', $toko->id)
            ->orderBy('nama')
            ->get()
            ->map(fn (Produk $p) => ['model' => $p, 'baris' => $terpilih[$p->id] ?? null]);

        $diikuti = $promo->berlakuUntukToko($toko);

        return view('admin.promo.partisipasi-show', compact('promo', 'produks', 'toko', 'diikuti'));
    }

    public function toggleIkut(Promo $promo)
    {
        $toko = $this->tokoSaya();
        abort_unless($toko !== null, 403, 'Halaman ini untuk pemilik toko.');
        abort_unless($promo->milikPlatform() && $promo->aktif, 404);

        if ($promo->sudahBerakhir()) {
            return back()->with('error', 'Promo sudah berakhir, keikutsertaannya tidak dapat diubah.');
        }

        if ($promo->berlakuUntukToko($toko)) {
            $promo->tokos()->detach($toko->id);

            return back()->with('success', 'Toko berhenti mengikuti promo. Potongannya tidak lagi berlaku.');
        }

        $promo->tokos()->attach($toko->id, [
            'diikuti_at' => now(),
            'diikuti_oleh' => auth()->id(),
        ]);

        return back()->with('success', 'Toko mengikuti promo ini. Pilih produk yang disertakan.');
    }

    /**
     * Sertakan, perbarui, atau lepas satu produk dari promo.
     */
    public function simpanBaris(Request $request, Promo $promo, Produk $produk)
    {
        $toko = $this->tokoSaya();
        abort_unless($toko !== null && $produk->toko_id === $toko->id, 403);
        $this->pastikanTerjangkau($promo, $toko->id);

        if ($promo->sudahBerakhir()) {
            return $this->kembaliKeBaris($promo, $produk)
                ->with('error', 'Promo sudah berakhir, produknya tidak dapat diubah.');
        }

        if ($request->input('tindakan') === 'lepas') {
            $promo->produks()->where('produk_id', $produk->id)->delete();

            return $this->kembaliKeBaris($promo, $produk)->with('baris_dilepas', $produk->id);
        }

        $data = $request->validate([
            // Kuota kosong berarti tak dibatasi — promo biasa sering dijalankan
            // tanpa jatah, berbeda dari flash sale yang memang dagang kilat.
            'kuota' => ['nullable', 'integer', 'min:1'],
        ]);

        $kuota = $data['kuota'] ?? null;

        if ($kuota !== null && $kuota > $produk->stok) {
            return $this->kembaliKeBaris($promo, $produk)
                ->withErrors(['kuota' => "Melebihi stok tersedia ({$produk->stok})."], 'baris'.$produk->id)
                ->with('masukan_baris', ['id' => $produk->id, 'kuota' => $request->input('kuota')]);
        }

        $promo->produks()->updateOrCreate(
            ['produk_id' => $produk->id],
            ['kuota' => $kuota],
        );

        return $this->kembaliKeBaris($promo, $produk)->with('baris_tersimpan', $produk->id);
    }

    /* ---------- Penopang ---------- */

    /**
     * Promo yang boleh disentuh toko ini: promo platform yang sudah terbit,
     * atau promo miliknya sendiri.
     */
    private function pastikanTerjangkau(Promo $promo, int $tokoId): void
    {
        abort_unless(
            ($promo->milikPlatform() && $promo->aktif) || $promo->toko_id === $tokoId,
            404,
        );
    }

    private function kembaliKeBaris(Promo $promo, Produk $produk): RedirectResponse
    {
        return redirect()->to(route('admin.promo.kelola', $promo).'#produk-'.$produk->id);
    }
}
