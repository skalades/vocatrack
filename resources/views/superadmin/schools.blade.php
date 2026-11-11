@extends('layouts.app')

@section('page_title', 'Manajemen Tenant Sekolah')
@section('page_subtitle', 'Tambah sekolah baru dan kelola hak akses tenant multi-sekolah')

@section('content')
<div class="space-y-6">

    <x-page-header 
        title="Daftar Tenant Sekolah Mitra" 
        subtitle="Setiap sekolah memiliki database tenant scoped yang terisolasi secara aman."
    >
        <x-button type="button" variant="primary" size="sm" onclick="document.getElementById('modal-add-school').classList.remove('hidden')">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Daftarkan Sekolah Baru
        </x-button>
    </x-page-header>

    <!-- Table Card -->
    <x-card noPadding>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6">Nama Sekolah & NPSN</th>
                        <th class="py-3.5 px-4">Kontak Resmi</th>
                        <th class="py-3.5 px-4 text-center">Total Akun</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-6 text-center">Aksi Manajemen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($schools as $sch)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3.5 px-6">
                                <div class="flex items-start space-x-3">
                                    <div class="w-9 h-9 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-700 font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">
                                        {{ substr($sch->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0 max-w-sm">
                                        <span class="font-bold text-slate-900 block truncate">{{ $sch->name }}</span>
                                        <span class="text-[11px] text-slate-400 font-mono block">NPSN: {{ $sch->npsn }}</span>
                                        <span class="text-[11px] text-slate-500 block truncate leading-snug font-normal mt-0.5">{{ $sch->address }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="block font-semibold text-slate-900">{{ $sch->email }}</span>
                                <span class="text-[11px] text-slate-400 font-mono block">{{ $sch->phone }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold text-slate-900">
                                {{ $sch->users_count }} Pengguna
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <x-badge :variant="$sch->is_active ? 'success' : 'danger'" size="xs">
                                    {{ $sch->is_active ? 'Aktif' : 'Non-Aktif' }}
                                </x-badge>
                            </td>
                            <td class="py-3.5 px-6 text-center">
                                <form action="{{ route('superadmin.schools.toggle', $sch->id) }}" method="POST" onsubmit="return confirm('Ubah status aktifasi sekolah ini?')">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-colors cursor-pointer {{ $sch->is_active ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200' }}">
                                        {{ $sch->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12">
                                <x-empty-state 
                                    title="Belum Ada Sekolah Terdaftar" 
                                    description="Daftarkan tenant sekolah baru untuk memulai proses onboarding."
                                    actionLabel="+ Daftarkan Sekolah Sekarang"
                                    actionClick="document.getElementById('modal-add-school').classList.remove('hidden')"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($schools->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $schools->links() }}
            </div>
        @endif
    </x-card>

</div>

<!-- Modal Add School -->
<div id="modal-add-school" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in fade-in duration-150">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Registrasi Sekolah Baru</h3>
                <p class="text-xs text-slate-500 font-medium">Buat tenant baru dan akun administrator pertamanya</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-add-school').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('superadmin.schools.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">NPSN (8 Digit)</label>
                    <input type="text" name="npsn" maxlength="8" required placeholder="Contoh: 20108899" 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-mono text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Resmi Sekolah</label>
                    <input type="text" name="name" required placeholder="SMK Negeri 1 Surabaya" 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Lengkap Sekolah</label>
                <textarea name="address" rows="2" required placeholder="Jl. Raya Pendidikan No. 123..." 
                          class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900 leading-relaxed"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Telepon Resmi Sekolah</label>
                    <input type="text" name="phone" required placeholder="031-1234567" 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Email Resmi Sekolah</label>
                    <input type="email" name="email" required placeholder="info@smkn1.sch.id" 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-indigo-50/70 border border-indigo-100 space-y-3">
                <span class="text-xs font-bold text-indigo-950 block">Akun Administrator Sekolah Pertama</span>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-indigo-900 mb-1">Nama Petugas Admin</label>
                        <input type="text" name="admin_name" required placeholder="Nama Admin" 
                               class="w-full text-xs p-2.5 rounded-xl bg-white border border-indigo-200 focus:ring-2 focus:ring-indigo-500/20 font-medium text-slate-900">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-indigo-900 mb-1">Email Login Admin</label>
                        <input type="email" name="admin_email" required placeholder="admin@smkn1.sch.id" 
                               class="w-full text-xs p-2.5 rounded-xl bg-white border border-indigo-200 focus:ring-2 focus:ring-indigo-500/20 font-medium text-slate-900">
                    </div>
                </div>
                <p class="text-[10px] text-indigo-700 font-medium">Kata sandi default login admin sekolah: <code class="bg-white/80 px-1.5 py-0.5 rounded font-mono font-bold text-indigo-900">password</code></p>
            </div>

            <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-add-school').classList.add('hidden')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md">
                    Daftarkan Sekolah
                </x-button>
            </div>
        </form>
    </div>
</div>
@endsection
