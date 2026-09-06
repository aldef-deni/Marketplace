<x-guest-layout>
    <x-kartu-auth judul="Konfirmasi Kata Sandi"
                  keterangan="Ini area yang dilindungi. Masukkan kata sandi Anda sekali lagi untuk melanjutkan.">

        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
            @csrf

            <div>
                <label for="password" class="label-field">Kata Sandi</label>
                <x-input-sandi id="password" name="password" required autofocus
                               autocomplete="current-password" placeholder="••••••••" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <button type="submit" class="btn-primary w-full py-3.5 text-base">
                Konfirmasi
            </button>
        </form>
    </x-kartu-auth>
</x-guest-layout>
