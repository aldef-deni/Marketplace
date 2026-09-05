<x-layouts.app>
    <x-slot name="title">Profil Saya</x-slot>

    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold tracking-tight text-slate-900">Profil Saya</h1>
                <p class="mt-0.5 text-sm text-slate-500">Kelola data akun, kata sandi, dan keamanan.</p>
            </div>

            <div class="flex items-center gap-2.5 rounded-full bg-slate-50 py-1.5 pl-1.5 pr-4 ring-1 ring-slate-200">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-brand-600 to-accent-500 text-xs font-bold text-white">
                    {{ initials($user->name) }}
                </span>
                <div>
                    <p class="text-xs font-bold text-slate-800">{{ $user->name }}</p>
                    <p class="text-[10px] font-medium text-slate-400">{{ $user->role_label }}</p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
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
</x-layouts.app>
