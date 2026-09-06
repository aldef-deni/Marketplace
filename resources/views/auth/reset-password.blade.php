<x-guest-layout>
    <x-kartu-auth judul="Buat Kata Sandi Baru"
                  keterangan="Pilih kata sandi yang panjang dan unik. Minimal 8 karakter.">

        <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <label for="email" class="label-field">Email</label>
                <input id="email" name="email" type="email" class="input-field" required autofocus
                       value="{{ old('email', $request->email) }}" autocomplete="username">
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <label for="password" class="label-field">Kata Sandi Baru</label>
                <x-input-sandi id="password" name="password" required autocomplete="new-password"
                               placeholder="Minimal 8 karakter" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <label for="password_confirmation" class="label-field">Ulangi Kata Sandi Baru</label>
                <x-input-sandi id="password_confirmation" name="password_confirmation" required
                               autocomplete="new-password" placeholder="Ketik ulang kata sandi" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <button type="submit" class="btn-primary w-full py-3.5 text-base">
                Simpan Kata Sandi Baru
            </button>
        </form>
    </x-kartu-auth>
</x-guest-layout>
