<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Batas ukuran avatar dalam kilobita.
     *
     * Dijaga kecil karena foto ini selalu ditampilkan berukuran mungil di bilah
     * navigasi; berkas besar hanya membebani unggahan dan penyimpanan.
     */
    private const MAKS_AVATAR_KB = 2048;

    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Ganti foto profil.
     *
     * Dipisahkan dari pembaruan data agar bisa dipanggil langsung dari
     * dashboard tanpa ikut mengirimkan nama dan email.
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAKS_AVATAR_KB],
        ], [
            'avatar.max' => 'Ukuran foto maksimal '.(self::MAKS_AVATAR_KB / 1024).' MB.',
            'avatar.image' => 'Berkas harus berupa gambar.',
        ]);

        $user = $request->user();
        $lama = $user->avatarDiunggahSendiri() ? $user->avatar : null;

        // Awalan "uploads/" ikut disimpan supaya asset() menghasilkan URL benar,
        // sama seperti konvensi pada gambar produk dan bukti pembayaran.
        $user->forceFill([
            'avatar' => 'uploads/'.$request->file('avatar')->store('avatar', 'uploads'),
        ])->save();

        // Berkas lama dibuang setelah yang baru tersimpan, supaya kegagalan
        // penyimpanan tidak meninggalkan pengguna tanpa foto sama sekali.
        if ($lama) {
            Storage::disk('uploads')->delete(str_replace('uploads/', '', $lama));
        }

        return back()->with('success', 'Foto profil diperbarui.');
    }

    public function hapusAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatarDiunggahSendiri()) {
            Storage::disk('uploads')->delete(str_replace('uploads/', '', $user->avatar));
        }

        $user->forceFill(['avatar' => null])->save();

        return back()->with('success', 'Foto profil dihapus.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Akun tanpa kata sandi (masuk lewat Google) dikonfirmasi dengan
        // mengetik ulang alamat emailnya.
        if ($user->punyaKataSandi()) {
            $request->validateWithBag('userDeletion', [
                'password' => ['required', 'current_password'],
            ]);
        } else {
            $request->validateWithBag('userDeletion', [
                'email_konfirmasi' => ['required', 'string', 'in:'.$user->email],
            ], [
                'email_konfirmasi.in' => 'Alamat email tidak cocok dengan akun ini.',
            ]);
        }

        if ($user->avatarDiunggahSendiri()) {
            Storage::disk('uploads')->delete(str_replace('uploads/', '', $user->avatar));
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
