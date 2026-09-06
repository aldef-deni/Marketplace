<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MetodePembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MetodePembayaranController extends Controller
{
    public function index()
    {
        $metodes = MetodePembayaran::withCount('pembayarans')->orderBy('tipe')->orderBy('nama')->get();

        return view('admin.metode-pembayaran.index', [
            'metodes' => $metodes,
            // Dihitung sekali di sini supaya tampilan tidak perlu mengulang
            // aturan "siap dipakai" yang sudah dimiliki modelnya.
            'jumlahSiap' => $metodes->filter->siapDipakai()->count(),
        ]);
    }

    public function store(Request $request)
    {
        $this->validated($request);

        MetodePembayaran::create($this->data($request));

        return back()->with('success', 'Metode pembayaran ditambahkan.');
    }

    public function update(Request $request, MetodePembayaran $metode)
    {
        /*
        | Divalidasi manual, bukan lewat $request->validate(), karena halaman ini
        | memuat satu formulir per metode. Kegagalan dari salah satu kartu akan
        | mengisi ulang seluruh kartu lewat old(), sehingga nilai yang barusan
        | diketik di satu kartu muncul di semua kartu yang lain.
        |
        | Penanda kartu yang disunting ikut dikirim agar tampilannya tahu old()
        | itu milik siapa.
        */
        $validator = Validator::make($request->all(), $this->aturan($metode), $this->pesan());

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'metode'.$metode->id)
                ->with('metode_disunting', $metode->id)
                ->withInput();
        }

        $metode->update($this->data($request, $metode));

        return back()->with('success', 'Metode pembayaran diperbarui.');
    }

    public function toggle(MetodePembayaran $metode)
    {
        $metode->update(['aktif' => ! $metode->aktif]);

        return back()->with('success', 'Status metode pembayaran diperbarui.');
    }

    public function destroy(MetodePembayaran $metode)
    {
        if ($metode->pembayarans()->exists()) {
            return back()->with('error', 'Metode ini sudah pernah digunakan, tidak dapat dihapus. Nonaktifkan saja.');
        }

        $metode->delete();

        return back()->with('success', 'Metode pembayaran dihapus.');
    }

    private function validated(Request $request): void
    {
        $request->validate($this->aturan(), $this->pesan());
    }

    /**
     * Tipe hanya wajib saat metode baru dibuat.
     *
     * Formulir per kartu sengaja tidak menampilkan pilihan tipe — tipe sudah
     * melekat pada metode itu dan mengubahnya jarang berarti. Mewajibkannya di
     * sana membuat setiap penyimpanan gagal tanpa pesan apa pun.
     */
    private function aturan(?MetodePembayaran $metode = null): array
    {
        return [
            'nama' => ['required', 'string', 'max:100'],
            'label_pendek' => ['nullable', 'string', 'max:30'],
            'tipe' => [$metode ? 'nullable' : 'required', 'in:transfer,ewallet,cod'],
            // Warna dipakai sebagai nilai CSS di lencana footer, jadi bentuknya
            // dibatasi hex agar tidak ada nilai sembarang yang ikut tersuntik.
            'warna' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'nomor_rekening' => ['nullable', 'string', 'max:50'],
            'atas_nama' => ['nullable', 'string', 'max:100'],
            'instruksi' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function pesan(): array
    {
        return [
            'warna.regex' => 'Warna harus berupa kode heksadesimal, misalnya #0060AF.',
        ];
    }

    private function data(Request $request, ?MetodePembayaran $metode = null): array
    {
        return [
            'nama' => $request->nama,
            'label_pendek' => $request->label_pendek,
            // Tipe lama dipertahankan bila formulirnya memang tidak mengirimnya.
            'tipe' => $request->tipe ?? $metode?->tipe,
            'warna' => $request->warna,
            'nomor_rekening' => $request->nomor_rekening,
            'atas_nama' => $request->atas_nama,
            'instruksi' => $request->instruksi,
            'aktif' => $request->boolean('aktif'),
        ];
    }
}