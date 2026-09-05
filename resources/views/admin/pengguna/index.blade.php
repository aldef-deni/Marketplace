<x-layouts.admin>
    <x-slot name="title">Manajemen Pengguna</x-slot>

    <div class="card overflow-hidden">
        <div class="p-6 pb-4">
            <h3 class="text-base font-extrabold text-slate-900">Manajemen Pengguna</h3>
            <p class="mt-0.5 text-xs text-slate-400">Kelola role pengguna (Superadmin / Admin / Pengguna)</p>
        </div>

        <form method="GET" class="flex flex-wrap gap-3 px-6 pb-4">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama / email..." class="input-field w-64">
            <select name="role" class="input-field w-44">
                <option value="">Semua Role</option>
                @foreach (['superadmin' => 'Superadmin', 'admin' => 'Admin', 'pengguna' => 'Pengguna'] as $nilai => $label)
                    <option value="{{ $nilai }}" @selected(request('role') === $nilai)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn-secondary">Filter</button>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px]">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="table-head">Pengguna</th>
                        <th class="table-head">No. HP</th>
                        <th class="table-head">Pesanan</th>
                        <th class="table-head">Terdaftar</th>
                        <th class="table-head">Role</th>
                        <th class="table-head text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($penggunas as $pengguna)
                        <tr class="transition hover:bg-slate-50/60">
                            <td class="table-cell">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-600 to-accent-500 text-xs font-bold text-white">
                                        {{ initials($pengguna->name) }}
                                    </span>
                                    <div>
                                        <p class="font-bold text-slate-800">{{ $pengguna->name }} {!! $pengguna->id === auth()->id() ? '<span class="text-xs text-brand-500">(Anda)</span>' : '' !!}</p>
                                        <p class="text-xs text-slate-400">{{ $pengguna->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="table-cell text-xs text-slate-500">{{ $pengguna->phone ?? '-' }}</td>
                            <td class="table-cell font-extrabold">{{ $pengguna->pesanans_count }}</td>
                            <td class="table-cell text-xs text-slate-500">{{ tanggalIndo($pengguna->created_at) }}</td>
                            <td class="table-cell"><span class="badge {{ $pengguna->role_warna }}">{{ $pengguna->role_label }}</span></td>
                            <td class="table-cell">
                                <div class="flex items-center justify-end gap-2">
                                    <form action="{{ route('admin.pengguna.role', $pengguna) }}" method="POST" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role" class="input-field !w-32 !py-1.5 text-xs"
                                                onchange="if(confirm('Ubah role {{ $pengguna->name }}?')) this.form.submit(); else this.value='{{ $pengguna->role }}';">
                                            @foreach (['superadmin' => 'Superadmin', 'admin' => 'Admin', 'pengguna' => 'Pengguna'] as $nilai => $label)
                                                <option value="{{ $nilai }}" @selected($pengguna->role === $nilai)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                    @if ($pengguna->id !== auth()->id())
                                        <form action="{{ route('admin.pengguna.destroy', $pengguna) }}" method="POST" onsubmit="return confirm('Hapus pengguna ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-600 transition hover:bg-rose-100"><x-ikon nama="sampah" kelas="h-3.5 w-3.5" /></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center">
                                <p class="text-4xl"><x-ikon nama="pengguna" kelas="h-9 w-9" /></p>
                                <p class="mt-3 text-sm font-semibold text-slate-500">Tidak ada pengguna ditemukan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-6">{{ $penggunas->links() }}</div>
    </div>

</x-layouts.admin>