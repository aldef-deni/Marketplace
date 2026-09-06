<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promo;
use App\Support\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Penyusunan promo.
 *
 * Satu controller melayani dua asal promo karena bentuknya sama persis; yang
 * berbeda hanya siapa pemiliknya dan siapa yang boleh menyentuhnya:
 *
 * - Superadmin menyusun promo platform (toko_id kosong) yang ditawarkan ke
 *   seluruh toko dan menunggu masing-masing memutuskan ikut.
 * - Pemilik toko menyusun promo untuk produknya sendiri, yang langsung berlaku
 *   begitu diterbitkan tanpa perlu diikuti siapa pun.
 */
class PromoController extends Controller
{
    use MengelolaToko;

    public function index(Request $request)
    {
        $superadmin = auth()->user()->isSuperadmin();
        $toko = $this->tokoSaya();

        $query = Promo::with('pembuat', 'toko')->withCount('produks');

        // Superadmin melihat promo platform; pemilik toko melihat promonya
        // sendiri. Keduanya tidak saling mengintip daftar milik yang lain.
        $superadmin
            ? $query->platform()
            : $query->where('toko_id', $toko?->id ?? 0);

        if ($request->filled('status')) {
            $query->where('aktif', $request->status === 'terbit');
        }

        $promos = $query->orderByDesc('mulai_at')->paginate(12)->withQueryString();

        $dasar = fn () => $superadmin
            ? Promo::query()->platform()
            : Promo::query()->where('toko_id', $toko?->id ?? 0);

        $jumlah = [
            'semua' => $dasar()->count(),
            'berlangsung' => $dasar()->berlangsung()->count(),
            'terjadwal' => $dasar()->terbit()->where('mulai_at', '>', now())->count(),
            'draf' => $dasar()->where('aktif', false)->count(),
        ];

        return view('admin.promo.index', compact('promos', 'jumlah', 'superadmin', 'toko'));
    }

    public function create()
    {
        $this->pastikanBolehMenyusun();

        return view('admin.promo.form', ['promo' => new Promo]);
    }

    public function store(Request $request)
    {
        $this->pastikanBolehMenyusun();

        $data = $this->validasi($request);

        $data['slug'] = Promo::slugUnik($data['nama']);
        $data['dibuat_oleh'] = auth()->id();
        $data['toko_id'] = auth()->user()->isSuperadmin() ? null : $this->tokoSaya()?->id;

        Promo::create($data);

        return redirect()->route('admin.promo.index')->with('success', auth()->user()->isSuperadmin()
            ? 'Promo dibuat. Terbitkan agar pemilik toko diberi tahu dan dapat mengikutinya.'
            : 'Promo dibuat. Terbitkan lalu pilih produk yang disertakan.');
    }

    public function edit(Promo $promo)
    {
        $this->pastikanBoleh($promo);

        return view('admin.promo.form', compact('promo'));
    }

    public function update(Request $request, Promo $promo)
    {
        $this->pastikanBoleh($promo);

        $data = $this->validasi($request, $promo);

        if ($data['nama'] !== $promo->nama) {
            $data['slug'] = Promo::slugUnik($data['nama'], $promo->id);
        }

        $promo->update($data);

        return redirect()->route('admin.promo.index')->with('success', 'Promo diperbarui.');
    }

    /**
     * Terbitkan atau tarik kembali sebuah promo.
     */
    public function toggleAktif(Promo $promo)
    {
        $this->pastikanBoleh($promo);

        $promo->update(['aktif' => ! $promo->aktif]);

        // Hanya promo platform yang perlu dikabarkan: promo milik toko sendiri
        // diterbitkan oleh orang yang sama yang akan menerima kabarnya.
        if ($promo->milikPlatform()) {
            Notifikasi::kePemilikToko($promo, $promo->aktif ? 'promo_baru' : 'promo_ditarik');
        }

        return back()->with('success', $promo->aktif
            ? ($promo->milikPlatform()
                ? 'Promo diterbitkan. Pemilik toko sudah diberi tahu.'
                : 'Promo diterbitkan dan potongannya mulai berlaku.')
            : 'Promo ditarik. Potongannya tidak lagi berlaku.');
    }

    public function destroy(Promo $promo)
    {
        $this->pastikanBoleh($promo);

        if ($promo->sedangBerlangsung()) {
            return back()->with('error', 'Promo yang sedang berjalan tidak dapat dihapus. Tarik dulu penerbitannya.');
        }

        $promo->delete();

        return redirect()->route('admin.promo.index')->with('success', 'Promo dihapus.');
    }

    /* ---------- Penopang ---------- */

    private function pastikanBolehMenyusun(): void
    {
        abort_unless(
            auth()->user()->isSuperadmin() || $this->tokoSaya() !== null,
            403,
            'Hanya superadmin atau pemilik toko yang dapat menyusun promo.',
        );
    }

    private function pastikanBoleh(Promo $promo): void
    {
        if ($promo->milikPlatform()) {
            abort_unless(auth()->user()->isSuperadmin(), 403);

            return;
        }

        abort_unless($promo->toko_id === $this->tokoSaya()?->id, 403);
    }

    private function validasi(Request $request, ?Promo $abaikan = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:120', Rule::unique('promos', 'nama')->ignore($abaikan?->id)],
            'deskripsi' => ['nullable', 'string', 'max:500'],
            'mulai_at' => ['required', 'date'],
            // Jadwal yang berakhir sebelum dimulai membuat promo tidak pernah
            // berjalan tanpa pesan galat apa pun.
            'selesai_at' => ['required', 'date', 'after:mulai_at'],
            'tipe_diskon' => ['required', 'in:persen,nominal'],
            // Persentase di atas 90 menyisakan harga yang praktis nol, sedangkan
            // potongan nominal wajar bernilai ratusan ribu.
            'nilai_diskon' => ['required', 'numeric', 'min:1',
                $request->input('tipe_diskon') === 'persen' ? 'max:90' : 'max:1000000000'],
        ], [
            'selesai_at.after' => 'Waktu selesai harus setelah waktu mulai.',
            'nama.unique' => 'Sudah ada promo dengan nama tersebut.',
            'nilai_diskon.max' => 'Potongan persentase maksimal 90%.',
        ]);
    }
}
