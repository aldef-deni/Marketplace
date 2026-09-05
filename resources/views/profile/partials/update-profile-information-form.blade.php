<section>
    <header>
        <h2 class="text-base font-extrabold text-slate-900">Informasi Akun</h2>
        <p class="mt-1 text-sm text-slate-500">Perbarui nama, email, dan nomor telepon Anda.</p>
    </header>

    @if ($user->tertautGoogle())
        <div class="mt-5 flex items-start gap-3 rounded-2xl bg-brand-50 px-4 py-3 ring-1 ring-brand-100">
            <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#4285F4" d="M23.52 12.27c0-.79-.07-1.54-.2-2.27H12v4.51h6.47a5.53 5.53 0 0 1-2.4 3.63v3h3.88c2.27-2.09 3.57-5.17 3.57-8.87z"/>
                <path fill="#34A853" d="M12 24c3.24 0 5.96-1.08 7.95-2.91l-3.88-3.01c-1.08.72-2.45 1.15-4.07 1.15-3.13 0-5.78-2.11-6.73-4.95H1.28v3.11A11.995 11.995 0 0 0 12 24z"/>
                <path fill="#FBBC05" d="M5.27 14.28a7.2 7.2 0 0 1 0-4.56V6.61H1.28a12.01 12.01 0 0 0 0 10.78l3.99-3.11z"/>
                <path fill="#EA4335" d="M12 4.76c1.77 0 3.35.61 4.6 1.8l3.44-3.44C17.95 1.19 15.24 0 12 0 7.31 0 3.26 2.69 1.28 6.61l3.99 3.11C6.22 6.87 8.87 4.76 12 4.76z"/>
            </svg>
            <p class="text-sm text-brand-800">
                Akun ini tertaut dengan <span class="font-bold">Google</span>, jadi Anda bisa masuk lewat tombol
                &ldquo;Masuk dengan Google&rdquo; tanpa mengetik kata sandi.
            </p>
        </div>
    @endif

    <form id="kirim-verifikasi" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="label-field">Nama Lengkap</label>
            <input id="name" name="name" type="text" class="input-field" value="{{ old('name', $user->name) }}"
                   required autofocus autocomplete="name">
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <label for="email" class="label-field">Email</label>
            <input id="email" name="email" type="email" class="input-field" value="{{ old('email', $user->email) }}"
                   required autocomplete="username">
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <p class="mt-2 text-sm text-slate-600">
                    Email Anda belum terverifikasi.
                    <button form="kirim-verifikasi" class="font-semibold text-brand-600 underline hover:text-brand-800">
                        Kirim ulang tautan verifikasi
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="mt-2 text-sm font-semibold text-emerald-600">
                        Tautan verifikasi baru sudah dikirim ke email Anda.
                    </p>
                @endif
            @endif
        </div>

        <div>
            <label for="phone" class="label-field">Nomor Telepon <span class="font-normal text-slate-400">(opsional)</span></label>
            <input id="phone" name="phone" type="tel" class="input-field" value="{{ old('phone', $user->phone) }}"
                   placeholder="08xxxxxxxxxx" autocomplete="tel">
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        <div class="flex items-center gap-4 pt-1">
            <button class="btn-primary">Simpan Perubahan</button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ tampil: true }" x-show="tampil" x-transition
                   x-init="setTimeout(() => tampil = false, 2500)"
                   class="text-sm font-semibold text-emerald-600">Tersimpan.</p>
            @endif
        </div>
    </form>
</section>
