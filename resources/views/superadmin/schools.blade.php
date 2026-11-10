@extends('layouts.app')

@section('page_title', 'Manajemen Tenant Sekolah')
@section('page_subtitle', 'Tambah sekolah baru dan kelola hak akses tenant multi-sekolah')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-base font-bold text-slate-800">Daftar Sekolah Mitra</h3>
            <p class="text-xs text-slate-500">Masing-masing sekolah terisolasi datanya secara ketat.</p>
        </div>
        <button onclick="document.getElementById('modal-add-school').classList.remove('hidden')" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-200 flex items-center space-x-1.5 transition-transform active:scale-[0.98]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Daftarkan Sekolah Baru</span>
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200/80 font-bold uppercase text-[10px]">
                    <tr>
                        <th class="py-3 px-5">Nama Sekolah & NPSN</th>
                        <th class="py-3 px-4">Kontak</th>
                        <th class="py-3 px-4 text-center">Total Akun</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @foreach($schools as $sch)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-5">
                                <span class="font-bold text-slate-900 block">{{ $sch->name }}</span>
                                <span class="text-[11px] text-slate-400 block font-mono">NPSN: {{ $sch->npsn }}</span>
                                <span class="text-[11px] text-slate-500 block truncate max-w-sm mt-0.5">{{ $sch->address }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="block">{{ $sch->email }}</span>
                                <span class="text-[11px] text-slate-400 block">{{ $sch->phone }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold">{{ $sch->users_count }}</td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold {{ $sch->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                    {{ $sch->is_active ? 'Aktif' : 'Non-Aktif' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-5 text-center">
                                <form action="{{ route('superadmin.schools.toggle', $sch->id) }}" method="POST" onsubmit="return confirm('Ubah status aktifasi sekolah ini?')">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-bold {{ $sch->is_active ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200' }}">
                                        {{ $sch->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $schools->links() }}
        </div>
    </div>

</div>

<!-- Modal Add School -->
<div id="modal-add-school" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <h3 class="text-sm font-bold text-slate-900">Registrasi Sekolah Baru</h3>
            <button type="button" onclick="document.getElementById('modal-add-school').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
        </div>

        <form action="{{ route('superadmin.schools.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">NPSN (8 Digit)</label>
                    <input type="text" name="npsn" maxlength="8" required placeholder="Contoh: 20108899" class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200 font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Resmi Sekolah</label>
                    <input type="text" name="name" required placeholder="SMK Negeri ..." class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Lengkap</label>
                <textarea name="address" rows="2" required placeholder="Jl. Raya..." class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Telepon Resmi</label>
                    <input type="text" name="phone" required placeholder="021-..." class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Email Resmi Sekolah</label>
                    <input type="email" name="email" required placeholder="info@sekolah.sch.id" class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                </div>
            </div>

            <div class="p-3.5 rounded-2xl bg-indigo-50/70 border border-indigo-100 space-y-3">
                <span class="text-xs font-bold text-indigo-900 block">Akun Admin Sekolah Pertama</span>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-indigo-950 mb-1">Nama Petugas Admin</label>
                        <input type="text" name="admin_name" required placeholder="Nama Admin" class="w-full text-xs p-2 rounded-xl bg-white border border-indigo-200">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-indigo-950 mb-1">Email Login Admin</label>
                        <input type="email" name="admin_email" required placeholder="admin@sekolah.sch.id" class="w-full text-xs p-2 rounded-xl bg-white border border-indigo-200">
                    </div>
                </div>
                <p class="text-[10px] text-indigo-700 font-medium">Kata sandi default awal akun admin adalah: <code>password</code></p>
            </div>

            <div class="flex space-x-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-add-school').classList.add('hidden')" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600">
                    Batal
                </button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-200">
                    Daftarkan Sekolah
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
