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
                        <h2 class="mt-10 text-3xl font-extrabold leading-tight text-white">Bergabung<br>dengan kami.</h2>
                        <p class="mx-auto mt-3 max-w-sm text-sm leading-relaxed text-ink-300">Buat akun gratis dan mulai pengalaman belanja online yang mudah, aman, dan menyenangkan.</p>
                    </div>
                    <div class="space-y-3">
                        @foreach (['Daftar gratis, tanpa biaya', 'Dapatkan promo eksklusif', 'Lacak pesanan secara real-time'] as $fitur)
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
                {{-- Tanpa pelat gelap: berkas logonya sudah transparan, jadi
                     kotak hitam itu kini murni tempelan yang justru memotong
                     kartu putihnya. --}}
                <div class="mb-8 flex justify-center lg:hidden">
                    <a href="{{ route('beranda') }}" class="inline-flex transition hover:opacity-90"
                       aria-label="{{ config('brand.nama') }} — beranda">
                        <x-logo varian="landscape" kelas="h-16 w-auto sm:h-20" />
                    </a>
                </div>

                <h1 class="text-2xl font-extrabold text-slate-900">Buat Akun Baru</h1>
                <p class="mt-1.5 text-sm text-slate-500">Sudah punya akun?
                    <a href="{{ route('login') }}" class="font-bold text-brand-600 hover:text-brand-800">Masuk di sini</a>
                </p>

                <div class="mt-8">
                    <x-tombol-google teks="Daftar dengan Google" />
                </div>

                <form method="POST" action="{{ route('register') }}" class="space-y-5">
                    @csrf

                    <div>
                        <x-input-label for="name" :value="__('Name')" class="!mb-1.5 !text-sm !font-semibold !text-slate-700" />
                        <x-text-input id="name" class="input-field mt-1" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Nama lengkap Anda" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Email')" class="!mb-1.5 !text-sm !font-semibold !text-slate-700" />
                        <x-text-input id="email" class="input-field mt-1" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="nama@email.com" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" :value="__('Password')" class="!mb-1.5 !text-sm !font-semibold !text-slate-700" />
                        <x-text-input id="password" class="input-field mt-1" type="password" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="!mb-1.5 !text-sm !font-semibold !text-slate-700" />
                        <x-text-input id="password_confirmation" class="input-field mt-1" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Ulangi kata sandi" />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>

                    <button type="submit" class="btn-primary w-full py-3.5 text-base">
                        {{ __('Register') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>