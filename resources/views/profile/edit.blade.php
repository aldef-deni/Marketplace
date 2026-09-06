@php
    // Admin dan superadmin tetap berada di panelnya sendiri saat menyunting
    // profil, supaya navigasinya tidak berpindah konteks ke tampilan toko.
    // Judul ditaruh di badan karena layout admin tidak punya slot header.
    $layout = $user->isPengelola() ? 'layouts.admin' : 'layouts.app';
@endphp

<x-dynamic-component :component="$layout">
    <x-slot name="title">Profil Saya</x-slot>

    <div class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">

        {{-- Panel admin sudah menampilkan judul halaman dan identitas pengguna
             di bilah atasnya, jadi kepala ini hanya untuk tampilan pembeli. --}}
        @unless ($user->isPengelola())
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-extrabold tracking-tight text-slate-900">Profil Saya</h1>
                    <p class="mt-0.5 text-sm text-slate-500">Kelola foto, data akun, kata sandi, dan keamanan.</p>
                </div>

                <div class="flex items-center gap-2.5 rounded-full bg-slate-50 py-1.5 pl-1.5 pr-4 ring-1 ring-slate-200">
                    <x-avatar :user="$user" cincin="ring-1 ring-slate-200" />
                    <div>
                        <p class="text-xs font-bold text-slate-800">{{ $user->name }}</p>
                        <p class="text-[10px] font-medium text-slate-400">{{ $user->role_label }}</p>
                    </div>
                </div>
            </div>
        @endunless

        {{-- Foto profil --}}
        <div class="card p-6 sm:p-8">
            <header>
                <h2 class="text-base font-extrabold text-slate-900">Foto Profil</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Tampil di bilah navigasi dan pada pesanan Anda. Format JPG, PNG, atau WebP, maksimal 2 MB.
                </p>
            </header>

            <div class="mt-6 flex flex-col items-center gap-6 sm:flex-row sm:items-start">
                <x-avatar :user="$user" ukuran="h-28 w-28" teks="text-3xl"
                          cincin="ring-4 ring-slate-100" />

                <div class="flex-1">
                    <form action="{{ route('profile.avatar') }}" method="POST" enctype="multipart/form-data"
                          x-data="{ nama: '', mengirim: false }">
                        @csrf

                        <label class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 px-6 py-8 text-center transition hover:border-brand-400 hover:bg-brand-50/40">
                            <x-ikon nama="unggah" kelas="h-6 w-6 text-slate-400" />
                            <span class="mt-2 text-sm font-semibold text-slate-700">
                                <span x-show="! nama">Pilih foto dari perangkat</span>
                                <span x-show="nama" x-cloak x-text="nama"></span>
                            </span>
                            <span class="mt-1 text-xs text-slate-400">Gambar persegi memberi hasil terbaik</span>

                            <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
                                   class="sr-only"
                                   @change="nama = $el.files[0]?.name ?? ''">
                        </label>

                        <x-input-error :messages="$errors->get('avatar')" class="mt-2" />

                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <button class="btn-primary" x-bind:disabled="! nama || mengirim"
                                    @click="mengirim = true"
                                    x-text="mengirim ? 'Mengunggah…' : 'Simpan Foto'">Simpan Foto</button>

                            @if ($user->avatar)
                                {{-- Atribut form dipakai agar tombol ini mengirim formulir
                                     penghapusan yang berdiri sendiri: formulir tidak boleh
                                     bersarang, dan $refs Alpine tidak menjangkau ke luar
                                     komponennya. --}}
                                <button type="submit" form="form-hapus-foto" class="btn-secondary !text-rose-600"
                                        onclick="return confirm('Hapus foto profil?')">Hapus Foto</button>
                            @endif
                        </div>
                    </form>

                    @if ($user->avatar)
                        <form id="form-hapus-foto" method="POST" action="{{ route('profile.avatar.hapus') }}" class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="card p-6 sm:p-8">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="card p-6 sm:p-8">
            @include('profile.partials.update-password-form')
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-rose-200/70 sm:p-8">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-dynamic-component>
