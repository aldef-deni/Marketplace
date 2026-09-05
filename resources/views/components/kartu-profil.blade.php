@props([
    'aktivitas' => null,
])

@php
    $pengguna = auth()->user();
    $bergabung = $pengguna->created_at?->translatedFormat('F Y') ?? '-';
@endphp

{{-- Kartu profil untuk dashboard semua peran: menampilkan identitas sekaligus
     menjadi jalan tercepat mengganti foto, tanpa berpindah halaman.
     Tombolnya diserahkan ke pemanggil lewat slot, karena yang relevan bagi
     pembeli berbeda dengan yang relevan bagi admin. --}}
<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-3xl bg-gradient-to-br from-ink-950 via-brand-950 to-brand-900 shadow-elevate']) }}>
    <div class="pointer-events-none absolute inset-0 pola-grid opacity-60"></div>
    <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-accent-500/20 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 -left-12 h-64 w-64 rounded-full bg-brand-600/30 blur-3xl"></div>
    <div class="pointer-events-none absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-accent-500/60 to-transparent"></div>

    <div class="relative p-6 sm:p-8">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center">

            {{-- Foto profil: klik untuk mengganti --}}
            <form action="{{ route('profile.avatar') }}" method="POST" enctype="multipart/form-data"
                  x-data="{ mengirim: false }"
                  class="group relative shrink-0 self-center sm:self-start">
                @csrf

                <label class="relative block cursor-pointer" title="Ganti foto profil">
                    <x-avatar :user="$pengguna" ukuran="h-24 w-24" teks="text-2xl"
                              cincin="ring-4 ring-white/10" />

                    <span class="absolute inset-0 flex items-center justify-center rounded-full bg-ink-950/65 opacity-0 backdrop-blur-[1px] transition group-hover:opacity-100"
                          x-show="! mengirim">
                        <x-ikon nama="pensil" kelas="h-6 w-6 text-white" />
                    </span>

                    <span x-show="mengirim" x-cloak
                          class="absolute inset-0 flex items-center justify-center rounded-full bg-ink-950/70 text-[10px] font-bold text-white">
                        Mengunggah…
                    </span>

                    {{-- Formulir dikirim begitu berkas dipilih; tidak perlu tombol
                         terpisah untuk satu berkas tunggal. --}}
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
                           class="sr-only"
                           @change="mengirim = true; $el.form.submit()">
                </label>

                <span class="absolute -bottom-1 -right-1 flex h-8 w-8 items-center justify-center rounded-full bg-accent-500 text-ink-950 shadow-accent ring-4 ring-brand-950">
                    <x-ikon nama="tambah" kelas="h-4 w-4" />
                </span>
            </form>

            {{-- Identitas --}}
            <div class="min-w-0 flex-1 text-center sm:text-left">
                <div class="flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                    <h2 class="truncate text-2xl font-extrabold text-white">{{ $pengguna->name }}</h2>
                    <span class="badge bg-white/10 text-ink-200 ring-white/15">{{ $pengguna->role_label }}</span>
                    @if ($pengguna->tertautGoogle())
                        <span class="badge bg-white/10 text-ink-200 ring-white/15">Google</span>
                    @endif
                </div>

                <p class="mt-1 truncate text-sm text-ink-300">{{ $pengguna->email }}</p>

                @if ($aktivitas)
                    <p class="mt-2 text-sm text-ink-300">{{ $aktivitas }}</p>
                @endif

                <div class="mt-4 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-xs text-ink-400 sm:justify-start">
                    <span class="inline-flex items-center gap-1.5">
                        <x-ikon nama="ponsel" kelas="h-4 w-4" />
                        {{ $pengguna->phone ?: 'Nomor belum diisi' }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <x-ikon nama="jam" kelas="h-4 w-4" />
                        Bergabung {{ $bergabung }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <x-ikon :nama="$pengguna->email_verified_at ? 'centang' : 'peringatan'" kelas="h-4 w-4" />
                        {{ $pengguna->email_verified_at ? 'Email terverifikasi' : 'Email belum terverifikasi' }}
                    </span>
                </div>
            </div>

            {{-- Tindakan, ditentukan pemanggil --}}
            <div class="flex shrink-0 flex-col gap-2 sm:self-center">
                {{ $slot }}
            </div>
        </div>

        @error('avatar')
            <p class="mt-4 rounded-xl bg-rose-500/15 px-4 py-2.5 text-xs font-semibold text-rose-200 ring-1 ring-rose-500/30">
                {{ $message }}
            </p>
        @enderror
    </div>
</div>
