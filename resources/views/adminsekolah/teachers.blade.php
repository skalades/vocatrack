@extends('layouts.app')

@section('page_title', 'Master Data Guru Pembimbing')
@section('page_subtitle', 'Kelola akun guru pembimbing PKL sekolah dan pantau alokasi siswa binaan')

@section('content')
<div class="space-y-6">

    <x-page-header 
        title="Daftar Guru Pembimbing" 
        subtitle="Guru yang bertugas memonitor presensi harian, memvalidasi jurnal tingkat dua, dan memberi nilai sekolah."
    >
        <x-button type="button" variant="primary" size="sm" onclick="document.getElementById('modal-add-teacher').classList.remove('hidden')">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Tambah Guru Pembimbing
        </x-button>
    </x-page-header>

    <!-- Table Card -->
    <x-card noPadding>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6">Nama Guru</th>
                        <th class="py-3.5 px-4">NIP</th>
                        <th class="py-3.5 px-4">Email Login</th>
                        <th class="py-3.5 px-6 text-center">Alokasi Siswa</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($teachers as $t)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3.5 px-6">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ substr($t->user->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <span class="font-bold text-slate-900 block truncate">{{ $t->user->name }}</span>
                                        <span class="text-[11px] text-slate-400 font-medium">{{ $t->user->profile->phone ?? 'Kontak belum diisi' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-mono font-bold text-slate-800">{{ $t->nip ?? '-' }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-600">
                                {{ $t->user->email }}
                            </td>
                            <td class="py-3.5 px-6 text-center">
                                <x-badge variant="indigo" size="sm">
                                    {{ $t->studentPkls->count() }} Siswa Bimbingan
                                </x-badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-12">
                                <x-empty-state 
                                    title="Belum Ada Guru Pembimbing" 
                                    description="Tambahkan akun guru pembimbing untuk memandu dan mengevaluasi siswa PKL."
                                    actionLabel="+ Tambah Guru Sekarang"
                                    actionClick="document.getElementById('modal-add-teacher').classList.remove('hidden')"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($teachers->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $teachers->links() }}
            </div>
        @endif
    </x-card>

</div>

<!-- Modal Add Teacher -->
<div id="modal-add-teacher" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in fade-in duration-150">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Tambah Guru Pembimbing</h3>
                <p class="text-xs text-slate-500 font-medium">Buat akun akses dan data pokok guru</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-add-teacher').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('adminsekolah.teachers.store') }}" method="POST" class="space-y-3.5">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Lengkap & Gelar</label>
                <input type="text" name="name" required placeholder="Contoh: Siti Nurhaliza, M.Pd." 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">NIP (Nomor Induk Pegawai)</label>
                <input type="text" name="nip" placeholder="198501012010012001" 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-mono text-slate-900">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nomor Telepon / WhatsApp</label>
                <input type="text" name="phone" placeholder="08123456789" 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Email (Username Login)</label>
                <input type="email" name="email" required placeholder="guru@sekolah.sch.id" 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                <span class="text-[11px] text-slate-400 block mt-1 font-medium">Password default akun: <code class="text-slate-700 font-mono font-bold">password</code></span>
            </div>
            <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-add-teacher').classList.add('hidden')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md">
                    Simpan Guru
                </x-button>
            </div>
        </form>
    </div>
</div>
@endsection
