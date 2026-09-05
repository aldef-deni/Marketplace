<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Notifikasi milik pengguna yang sedang masuk, untuk semua peran.
 *
 * Pembaruan langsung dilakukan lewat polling ringan ke endpoint JSON, bukan
 * websocket: hosting yang dipakai tidak menjalankan proses daemon, sehingga
 * polling adalah satu-satunya cara yang benar-benar berjalan di sana.
 */
class NotifikasiController extends Controller
{
    private const JUMLAH_HALAMAN = 20;

    public function index(Request $request): View
    {
        $notifikasis = $request->user()
            ->notifications()
            ->paginate(self::JUMLAH_HALAMAN);

        return view('notifikasi.index', compact('notifikasis'));
    }

    /**
     * Ringkasan untuk lonceng di bilah navigasi.
     */
    public function data(Request $request): JsonResponse
    {
        $pengguna = $request->user();

        return response()->json([
            'belum_dibaca' => $pengguna->unreadNotifications()->count(),
            'daftar' => $pengguna->notifications()->take(8)->get()->map(fn ($n) => [
                'id' => $n->id,
                'judul' => $n->data['judul'] ?? 'Notifikasi',
                'pesan' => $n->data['pesan'] ?? '',
                'ikon' => $n->data['ikon'] ?? 'info',
                'nada' => $n->data['nada'] ?? 'brand',
                'url' => $n->data['url'] ?? route('notifikasi.index'),
                'dibaca' => $n->read_at !== null,
                'waktu' => $n->created_at->diffForHumans(),
            ]),
        ]);
    }

    /**
     * Tandai satu notifikasi terbaca, lalu antar ke halaman tujuannya.
     */
    public function baca(Request $request, string $id): RedirectResponse
    {
        $notifikasi = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notifikasi->markAsRead();

        return redirect($notifikasi->data['url'] ?? route('notifikasi.index'));
    }

    public function bacaSemua(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Semua notifikasi ditandai terbaca.');
    }

    public function hapusTerbaca(Request $request): RedirectResponse
    {
        $request->user()->readNotifications()->delete();

        return back()->with('success', 'Notifikasi yang sudah dibaca dibersihkan.');
    }
}
