@php
    // Tanpa kata sandi, konfirmasi penghapusan memakai pengetikan ulang email.
    $sudahPunyaSandi = $user->punyaKataSandi();
@endphp

<section class="space-y-5">
    <header>
        <h2 class="text-base font-extrabold text-rose-700">Hapus Akun</h2>
        <p class="mt-1 text-sm text-slate-500">
            Setelah akun dihapus, seluruh data beserta riwayat pesanan hilang permanen dan tidak
            dapat dipulihkan. Unduh dulu apa pun yang masih Anda perlukan.
        </p>
    </header>

    <button type="button" class="btn-danger"
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'konfirmasi-hapus-akun')">
        Hapus Akun Saya
    </button>

    <x-modal name="konfirmasi-hapus-akun" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-extrabold text-slate-900">Yakin ingin menghapus akun?</h2>

            <p class="mt-2 text-sm text-slate-500">
                @if ($sudahPunyaSandi)
                    Tindakan ini permanen. Masukkan kata sandi Anda untuk mengonfirmasi.
                @else
                    Tindakan ini permanen. Ketik ulang alamat email Anda
                    (<span class="font-semibold text-slate-700">{{ $user->email }}</span>) untuk mengonfirmasi.
                @endif
            </p>

            <div class="mt-6">
                @if ($sudahPunyaSandi)
                    <label for="password" class="sr-only">Kata Sandi</label>
                    <x-input-sandi id="password" name="password"
                                   placeholder="Kata sandi Anda" autocomplete="current-password" />
                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                @else
                    <label for="email_konfirmasi" class="sr-only">Alamat Email</label>
                    <input id="email_konfirmasi" name="email_konfirmasi" type="email" class="input-field"
                           placeholder="{{ $user->email }}" autocomplete="off">
                    <x-input-error :messages="$errors->userDeletion->get('email_konfirmasi')" class="mt-2" />
                @endif
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" class="btn-secondary" x-on:click="$dispatch('close')">Batal</button>
                <button class="btn-danger">Ya, Hapus Akun</button>
            </div>
        </form>
    </x-modal>
</section>
