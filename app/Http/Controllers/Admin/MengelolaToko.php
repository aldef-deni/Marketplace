<?php

namespace App\Http\Controllers\Admin;

use App\Models\Toko;
use Illuminate\Database\Eloquent\Collection;

/**
 * Toko yang sedang dikelola pengguna yang masuk.
 *
 * Keikutsertaan kampanye selalu milik satu lapak, jadi kendalinya perlu tahu
 * lapak mana. Aturannya berbeda menurut peran:
 *
 * - Pemilik toko terkunci pada lapaknya sendiri.
 * - Superadmin bertindak atas nama sebuah toko, dipilih lewat parameter
 *   ?toko=<slug>. Pilihannya dibuat eksplisit dan terlihat di layar, bukan
 *   ditebak diam-diam — mengubah promo lapak orang tanpa sadar lapak mana yang
 *   sedang dibuka adalah kesalahan yang sulit ditelusuri.
 */
trait MengelolaToko
{
    protected function tokoSaya(): ?Toko
    {
        $daftar = $this->tokoTersedia();

        if ($daftar->isEmpty()) {
            return null;
        }

        // Pilihan disimpan di sesi supaya kiriman formulir sesudahnya tetap
        // mengenai toko yang sama; tanpa itu setiap tombol harus ikut membawa
        // parameternya, dan satu yang terlewat akan mengubah lapak yang salah.
        if ($pilihan = request('toko')) {
            if ($cocok = $daftar->firstWhere('slug', $pilihan)) {
                session(['toko_dikelola' => $cocok->slug]);

                return $cocok;
            }
        }

        if ($tersimpan = session('toko_dikelola')) {
            if ($cocok = $daftar->firstWhere('slug', $tersimpan)) {
                return $cocok;
            }
        }

        return $daftar->first();
    }

    /**
     * Toko yang boleh dipilih pengguna ini.
     */
    protected function tokoTersedia(): Collection
    {
        if (auth()->user()->isSuperadmin()) {
            return Toko::tampil()->orderBy('nama')->get();
        }

        return Toko::where('user_id', auth()->id())->orderBy('nama')->get();
    }
}
