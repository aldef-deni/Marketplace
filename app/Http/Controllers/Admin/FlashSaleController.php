<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Penyusunan kampanye flash sale — khusus superadmin.
 *
 * Superadmin menentukan nama, jadwal, dan saran diskon; keputusan ikut serta
 * beserta pemilihan produknya berada di tangan admin toko.
 */
class FlashSaleController extends Controller
{
    public function index()
    {
        $kampanyes = FlashSale::withCount('produks')
            ->with('pembuat', 'penyetuju')
            ->orderByDesc('mulai_at')
            ->paginate(12);

        $jumlah = [
            'semua' => FlashSale::count(),
            'berlangsung' => FlashSale::berlangsung()->count(),
            'terjadwal' => FlashSale::terbit()->where('mulai_at', '>', now())->count(),
            'draf' => FlashSale::where('aktif', false)->count(),
        ];

        return view('admin.flash-sale.index', compact('kampanyes', 'jumlah'));
    }

    public function create()
    {
        return view('admin.flash-sale.form', ['kampanye' => new FlashSale]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);

        $data['slug'] = $this->slugUnik($data['nama']);
        $data['dibuat_oleh'] = auth()->id();

        FlashSale::create($data);

        return redirect()->route('admin.flash-sale.index')
            ->with('success', 'Kampanye flash sale dibuat. Terbitkan agar admin toko dapat mengikutinya.');
    }

    public function edit(FlashSale $flashSale)
    {
        return view('admin.flash-sale.form', ['kampanye' => $flashSale]);
    }

    public function update(Request $request, FlashSale $flashSale)
    {
        $data = $this->validasi($request, $flashSale);

        if ($data['nama'] !== $flashSale->nama) {
            $data['slug'] = $this->slugUnik($data['nama'], $flashSale->id);
        }

        $flashSale->update($data);

        return redirect()->route('admin.flash-sale.index')->with('success', 'Kampanye diperbarui.');
    }

    /**
     * Terbitkan atau tarik kembali kampanye.
     *
     * Kampanye yang ditarik saat sedang berjalan langsung menghentikan harga
     * flash-nya, sebab scope berlangsung() mensyaratkan aktif bernilai benar.
     */
    public function toggleAktif(FlashSale $flashSale)
    {
        $flashSale->update(['aktif' => ! $flashSale->aktif]);

        return back()->with('success', $flashSale->aktif
            ? 'Kampanye diterbitkan. Admin toko kini dapat mengikutinya.'
            : 'Kampanye ditarik. Harga flash tidak lagi berlaku.');
    }

    public function destroy(FlashSale $flashSale)
    {
        if ($flashSale->sedangBerlangsung()) {
            return back()->with('error', 'Kampanye yang sedang berjalan tidak dapat dihapus. Tarik dulu penerbitannya.');
        }

        $flashSale->delete();

        return redirect()->route('admin.flash-sale.index')->with('success', 'Kampanye dihapus.');
    }

    private function validasi(Request $request, ?FlashSale $abaikan = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:120', Rule::unique('flash_sales', 'nama')->ignore($abaikan?->id)],
            'deskripsi' => ['nullable', 'string', 'max:500'],
            'mulai_at' => ['required', 'date'],
            // Jadwal yang berakhir sebelum dimulai membuat kampanye tidak
            // pernah berjalan tanpa pesan galat apa pun.
            'selesai_at' => ['required', 'date', 'after:mulai_at'],
            'diskon_persen' => ['required', 'integer', 'min:0', 'max:90'],
        ], [
            'selesai_at.after' => 'Waktu selesai harus setelah waktu mulai.',
            'nama.unique' => 'Sudah ada kampanye dengan nama tersebut.',
        ]);
    }

    private function slugUnik(string $nama, ?int $abaikan = null): string
    {
        $dasar = Str::slug($nama);
        $slug = $dasar;
        $urutan = 1;

        while (FlashSale::where('slug', $slug)->when($abaikan, fn ($q) => $q->where('id', '!=', $abaikan))->exists()) {
            $slug = $dasar.'-'.(++$urutan);
        }

        return $slug;
    }
}
