@extends('layouts.app')

@section('page_title', 'Master Data Guru Pembimbing')
@section('page_subtitle', 'Kelola akun guru pembimbing PKL sekolah')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-base font-bold text-slate-800">Daftar Guru Pembimbing</h3>
            <p class="text-xs text-slate-500">Guru yang bertugas memonitor dan memberi penilaian akademik</p>
        </div>
        <button onclick="document.getElementById('modal-add-teacher').classList.remove('hidden')" class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs flex items-center space-x-1.5 shadow-md shadow-indigo-200">
            <span>+ Tambah Guru</span>
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200/80 font-bold uppercase text-[10px]">
                    <tr>
                        <th class="py-3 px-5">Nama Guru</th>
                        <th class="py-3 px-4">NIP</th>
                        <th class="py-3 px-4">Email</th>
                        <th class="py-3 px-4 text-center">Siswa Binaan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($teachers as $t)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-5">
                                <span class="font-bold text-slate-900 block">{{ $t->user->name }}</span>
                                <span class="text-[11px] text-slate-400 block">{{ $t->user->profile->phone ?? '-' }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-800">{{ $t->nip ?? '-' }}</td>
                            <td class="py-3.5 px-4 font-mono text-[11px]">{{ $t->user->email }}</td>
                            <td class="py-3.5 px-4 text-center font-bold text-indigo-600">
                                {{ $t->studentPkls->count() }} Siswa
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-slate-400">Belum ada guru pembimbing terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $teachers->links() }}
        </div>
    </div>

</div>

<!-- Modal Add Teacher -->
<div id="modal-add-teacher" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-sm rounded-3xl p-5 shadow-2xl">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-slate-900">Tambah Guru Pembimbing</h3>
            <button type="button" onclick="document.getElementById('modal-add-teacher').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
        </div>
        <form action="{{ route('adminsekolah.teachers.store') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap & Gelar</label>
                <input type="text" name="name" required placeholder="Contoh: Siti Nurhaliza, M.T." class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">NIP (Nomor Induk Pegawai)</label>
                <input type="text" name="nip" placeholder="1985..." class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200 font-mono">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Telepon / WhatsApp</label>
                <input type="text" name="phone" placeholder="08..." class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Email Login</label>
                <input type="email" name="email" required placeholder="guru@sekolah.sch.id" class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                <span class="text-[10px] text-slate-400 block mt-0.5">Password awal otomatis diset: <code>password</code></span>
            </div>
            <div class="flex space-x-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-add-teacher').classList.add('hidden')" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600">Batal</button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold">Simpan Guru</button>
            </div>
        </form>
    </div>
</div>
@endsection
