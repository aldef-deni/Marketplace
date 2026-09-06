<x-guest-layout>
    <x-kartu-auth judul="Lupa Kata Sandi"
                  keterangan="Masukkan alamat email akun Anda. Kami kirimkan tautan untuk membuat kata sandi baru.">

        <x-auth-session-status class="mb-5" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="label-field">Email</label>
                <input id="email" name="email" type="email" class="input-field" required autofocus
                       value="{{ old('email') }}" autocomplete="username" placeholder="nama@email.com">
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <button type="submit" class="btn-primary w-full py-3.5 text-base">
                Kirim Tautan Atur Ulang
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-500">
            Ingat kata sandinya?
            <a href="{{ route('login') }}" class="font-bold text-brand-600 hover:text-brand-800">Kembali ke halaman masuk</a>
        </p>
    </x-kartu-auth>
</x-guest-layout>
