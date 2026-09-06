@php
    // Akun yang mendaftar lewat Google belum punya kata sandi, sehingga
    // formulir ini berperan sebagai "buat kata sandi", bukan "ganti".
    $sudahPunyaSandi = $user->punyaKataSandi();
@endphp

<section>
    <header>
        <h2 class="text-base font-extrabold text-slate-900">
            {{ $sudahPunyaSandi ? 'Ganti Kata Sandi' : 'Buat Kata Sandi' }}
        </h2>
        <p class="mt-1 text-sm text-slate-500">
            @if ($sudahPunyaSandi)
                Gunakan kata sandi yang panjang dan unik agar akun tetap aman.
            @else
                Akun Anda masuk lewat Google dan belum memiliki kata sandi. Buat satu agar bisa
                masuk memakai email walau tanpa Google.
            @endif
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('put')

        @if ($sudahPunyaSandi)
            <div>
                <label for="update_password_current_password" class="label-field">Kata Sandi Saat Ini</label>
                <x-input-sandi id="update_password_current_password" name="current_password"
                               autocomplete="current-password" />
                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
            </div>
        @endif

        <div>
            <label for="update_password_password" class="label-field">Kata Sandi Baru</label>
            <x-input-sandi id="update_password_password" name="password"
                           autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <label for="update_password_password_confirmation" class="label-field">Ulangi Kata Sandi Baru</label>
            <x-input-sandi id="update_password_password_confirmation" name="password_confirmation"
                           autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4 pt-1">
            <button class="btn-primary">{{ $sudahPunyaSandi ? 'Perbarui Kata Sandi' : 'Simpan Kata Sandi' }}</button>

            @if (session('status') === 'password-updated')
                <p x-data="{ tampil: true }" x-show="tampil" x-transition
                   x-init="setTimeout(() => tampil = false, 2500)"
                   class="text-sm font-semibold text-emerald-600">Tersimpan.</p>
            @endif
        </div>
    </form>
</section>
