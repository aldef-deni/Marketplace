<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class PenggunaController extends Controller
{
    public function index(Request $request)
    {
        $query = User::withCount('pesanans');

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(fn ($qq) => $qq->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
        }
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $penggunas = $query->latest()->paginate(15)->withQueryString();

        return view('admin.pengguna.index', compact('penggunas'));
    }

    public function updateRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', 'in:superadmin,admin,pengguna'],
        ]);

        if ($user->id === auth()->id() && $validated['role'] !== 'superadmin') {
            return back()->with('error', 'Anda tidak dapat menurunkan role Anda sendiri.');
        }

        $user->update(['role' => $validated['role']]);

        return back()->with('success', "Role {$user->name} diubah menjadi {$user->role_label}.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return back()->with('success', 'Pengguna dihapus.');
    }
}