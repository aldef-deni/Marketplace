<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        // Akun yang mendaftar lewat Google belum punya kata sandi, sehingga
        // tidak ada sandi lama yang bisa diminta. Di situ formulir berfungsi
        // sebagai "buat kata sandi", bukan "ganti kata sandi".
        $aturan = ['password' => ['required', Password::defaults(), 'confirmed']];

        if ($request->user()->punyaKataSandi()) {
            $aturan['current_password'] = ['required', 'current_password'];
        }

        $validated = $request->validateWithBag('updatePassword', $aturan);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'password-updated');
    }
}
