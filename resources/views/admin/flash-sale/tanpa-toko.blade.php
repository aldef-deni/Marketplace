<x-layouts.admin>
    <x-slot name="title">Flash Sale</x-slot>

    {{-- Keikutsertaan selalu milik sebuah lapak. Pengelola platform yang tidak
         memiliki toko tidak punya apa pun untuk diikutkan di sini. --}}
    <div class="rounded-3xl border-2 border-dashed border-slate-300 bg-white/60 p-14 text-center">
        <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
            <x-ikon nama="toko" kelas="h-7 w-7" />
        </span>

        <h1 class="mt-5 text-lg font-extrabold text-slate-800">Halaman ini untuk pemilik toko</h1>
        <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-slate-500">
            Keikutsertaan kampanye flash sale ditentukan per toko, dan akun Anda belum memiliki toko.
            Buat toko lebih dulu, atau minta pengelola menetapkan Anda sebagai pemiliknya.
        </p>

        <a href="{{ route('admin.toko.index') }}" class="btn-primary mt-7">Buka Daftar Toko</a>
    </div>
</x-layouts.admin>
