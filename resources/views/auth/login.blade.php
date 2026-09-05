<x-guest-layout>
    <div class="mx-auto flex min-h-[70vh] max-w-5xl items-center justify-center px-4 py-12 sm:px-6">
        <div class="grid w-full overflow-hidden rounded-3xl bg-white shadow-2xl shadow-brand-100 ring-1 ring-slate-200/70 lg:grid-cols-2">

            {{-- Panel brand --}}
            <div class="relative hidden overflow-hidden bg-gradient-to-b from-ink-950 via-brand-950 to-brand-900 p-10 lg:block">
                <div class="pointer-events-none absolute inset-0 pola-grid opacity-70"></div>
                <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-accent-500/20 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-24 -left-16 h-72 w-72 rounded-full bg-brand-600/35 blur-3xl"></div>
                <div class="relative flex h-full flex-col justify-between">
                    <div class="text-center">
                        <a href="{{ route('beranda') }}" class="inline-flex transition hover:opacity-90"
                           aria-label="{{ config('brand.nama') }} — beranda">
                            <x-logo varian="landscape" kelas="h-16 w-auto xl:h-20" />
                        </a>
                        <h2 class="mt-10 text-3xl font-extrabold leading-tight text-white">Selamat datang<br>kembali.</h2>
                        <p class="mx-auto mt-3 max-w-sm text-sm leading-relaxed text-ink-300">Masuk untuk melanjutkan belanja, memantau pesanan, dan menikmati promo terbaik kami.</p>
                    </div>
                    <div class="space-y-3">
                        @foreach (['Ribuan produk pilihan berkualitas', 'Pembayaran fleksibel & aman', 'Pengiriman cepat ke seluruh Indonesia'] as $fitur)
                            <div class="flex items-center gap-3 rounded-2xl bg-white/[0.06] px-4 py-3 text-sm font-semibold text-white ring-1 ring-white/10 backdrop-blur">
                                <x-ikon nama="centang" kelas="h-4 w-4 shrink-0 text-accent-400" />
                                <span>{{ $fitur }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Form --}}
            <div class="p-8 sm:p-12">
                <div class="mb-8 flex justify-center lg:hidden">
                    <a href="{{ route('beranda') }}" class="logo-plate px-6 py-3.5 transition hover:opacity-90"
                       aria-label="{{ config('brand.nama') }} — beranda">
                        <x-logo varian="landscape" kelas="h-11 w-auto" />
                    </a>
                </div>

                <h1 class="text-2xl font-extrabold text-slate-900">Masuk Akun</h1>
                <p class="mt-1.5 text-sm text-slate-500">Belum punya akun?
                    <a href="{{ route('register') }}" class="font-bold text-brand-600 hover:text-brand-800">Daftar sekarang</a>
                </p>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                    @csrf

                    <div>
                        <x-input-label for="email" :value="__('Email')" class="!mb-1.5 !text-sm !font-semibold !text-slate-700" />
                        <x-text-input id="email" class="input-field mt-1" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="nama@email.com" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <x-input-label for="password" :value="__('Password')" class="!mb-1.5 !text-sm !font-semibold !text-slate-700" />
                            @if (Route::has('password.request'))
                                <a class="text-xs font-bold text-brand-600 hover:text-brand-800" href="{{ route('password.request') }}">
                                    {{ __('Forgot your password?') }}
                                </a>
                            @endif
                        </div>
                        <x-text-input id="password" class="input-field mt-1" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-between">
                        <label for="remember_me" class="flex items-center">
                            <input id="remember_me" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500" name="remember">
                            <span class="ms-2 text-sm font-medium text-slate-600">{{ __('Remember me') }}</span>
                        </label>
                    </div>

                    <button type="submit" class="btn-primary w-full py-3.5 text-base">
                        {{ __('Log in') }}
                    </button>
                </form>

                <div class="mt-8 rounded-2xl bg-slate-50 p-4 text-center text-xs text-slate-500 ring-1 ring-slate-200/70">
                    Akun demo: <span class="font-bold">superadmin@arahinn.com</span> / <span class="font-bold">admin@arahinn.com</span> / <span class="font-bold">pengguna@arahinn.com</span> — password: <span class="font-bold">password</span>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>