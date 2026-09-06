<?php

namespace App\Http\Controllers\Admin;

use App\Models\Toko;

/**
 * Toko yang sedang dikelola pengguna yang masuk.
 *
 * Keikutsertaan kampanye selalu milik satu lapak, jadi kendalinya perlu tahu
 * lapak mana. Pengelola platform yang kebetulan juga memiliki toko diperlakukan
 * sama seperti penjual di sini — yang menentukan kepemilikan, bukan perannya.
 */
trait MengelolaToko
{
    protected function tokoSaya(): ?Toko
    {
        return Toko::where('user_id', auth()->id())->orderBy('id')->first();
    }
}
