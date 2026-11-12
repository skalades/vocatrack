@extends('layouts.app')

@section('page_title', 'Manajemen Tenant Sekolah')
@section('page_subtitle', 'Tambah sekolah baru dan kelola hak akses tenant multi-sekolah')

@section('content')
<div class="w-full space-y-6 sm:space-y-8 animate-in fade-in duration-300">

    <!-- Enterprise Hero Header -->
    <x-page-header 
        title="Daftar Tenant Sekolah Mitra" 
        subtitle="Setiap sekolah memiliki database tenant scoped yang terisolasi secara aman dalam ekosistem platform."
        badge="SUPER ADMIN PLATFORM"
    >
        <x-button type="button" variant="primary" size="md" onclick="document.getElementById('modal-add-school').classList.remove('hidden')" class="shadow-lg shadow-indigo-600/25">
            <svg class="w-4 h-4 mr-1.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Daftarkan Sekolah Baru
        </x-button>
    </x-page-header>

    <!-- Top KPI Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <x-stat-card 
            label="Total Satuan Pendidikan" 
            :value="$schools->total()" 
            description="Tenant terdaftar di platform"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            label="Tenant Sekolah Aktif" 
            :value="$schools->where('is_active', true)->count()" 
            description="Operasional berjalan lancar"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            label="Tenant Non-Aktif" 
            :value="$schools->where('is_active', false)->count()" 
            description="Sekolah ditangguhkan / non-aktif"
            color="rose"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            label="Mitra DU/DI Terhubung" 
            :value="$schools->sum('companies_count')" 
            description="Total jalinan kemitraan industri"
            color="teal"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Search & Filter Bar -->
    <x-card>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="relative flex-1">
                <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" id="school-search" placeholder="Cari nama sekolah, NPSN, email atau kota..." 
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs sm:text-sm font-medium text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-colors">
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-500 whitespace-nowrap">Status:</span>
                <select id="status-filter" class="text-xs font-bold px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    <option value="all">Semua Status</option>
                    <option value="active">Hanya Aktif</option>
                    <option value="inactive">Hanya Non-Aktif</option>
                </select>
            </div>
        </div>
    </x-card>

    <!-- Table Card -->
    <x-card noPadding>
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs min-w-[860px]" id="schools-table">
                <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6 min-w-[240px]">Satuan Pendidikan & NPSN</th>
                        <th class="py-3.5 px-4 min-w-[160px]">Kontak Resmi</th>
                        <th class="py-3.5 px-4 text-center min-w-[140px]">Metrik Tenant</th>
                        <th class="py-3.5 px-4 text-center min-w-[110px]">Status</th>
                        <th class="py-3.5 px-6 text-center table-sticky-right">Aksi Manajemen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($schools as $sch)
                        <tr class="hover:bg-slate-50/70 transition-colors school-row group" data-name="{{ strtolower($sch->name) }}" data-npsn="{{ $sch->npsn }}" data-status="{{ $sch->is_active ? 'active' : 'inactive' }}">
                            <td class="py-4 px-6">
                                <div class="flex items-start space-x-3.5">
                                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-white font-extrabold text-sm flex items-center justify-center shrink-0 shadow-sm shadow-indigo-500/20">
                                        {{ substr($sch->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0 max-w-sm">
                                        <span class="font-bold text-slate-900 block truncate text-xs sm:text-sm">{{ $sch->name }}</span>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 text-slate-600 border border-slate-200/70">
                                                NPSN: {{ $sch->npsn }}
                                            </span>
                                        </div>
                                        <span class="text-[11px] text-slate-500 block truncate leading-snug font-normal mt-1">{{ $sch->address }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="space-y-1">
                                    <span class="flex items-center text-xs font-semibold text-slate-900">
                                        <svg class="w-3.5 h-3.5 mr-1.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        {{ $sch->email }}
                                    </span>
                                    <span class="flex items-center text-[11px] text-slate-500 font-mono">
                                        <svg class="w-3.5 h-3.5 mr-1.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        {{ $sch->phone }}
                                    </span>
                                </div>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200/80 text-[11px] font-bold text-slate-700">
                                    <span class="text-indigo-600 font-black">{{ $sch->users_count }}</span> Pengguna
                                    <span class="text-slate-300">•</span>
                                    <span class="text-emerald-600 font-black">{{ $sch->companies_count }}</span> Mitra
                                </div>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <x-badge :variant="$sch->is_active ? 'success' : 'danger'" size="xs">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $sch->is_active ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' }} mr-1"></span>
                                    {{ $sch->is_active ? 'Tenant Aktif' : 'Non-Aktif' }}
                                </x-badge>
                            </td>
                            <td class="py-4 px-6 text-center whitespace-nowrap table-sticky-right">
                                <form action="{{ route('superadmin.schools.toggle', $sch->id) }}" method="POST" onsubmit="return confirm('Ubah status aktifasi sekolah ini?')">
                                    @csrf
                                    <button type="submit" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shadow-xs cursor-pointer {{ $sch->is_active ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 hover:shadow-rose-600/10' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 hover:shadow-emerald-600/10' }}">
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
                                    description="Daftarkan tenant sekolah baru untuk memulai proses onboarding institusi pendidikan."
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
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 sm:p-7 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Registrasi Tenant Sekolah Baru</h3>
                <p class="text-xs text-slate-500 font-medium">Buat tenant terisolasi baru dan akun administrator pertamanya</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-add-school').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('superadmin.schools.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
                <x-button type="submit" variant="primary" size="md" class="shadow-md shadow-indigo-600/20">
                    Daftarkan Sekolah
                </x-button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('school-search');
        const statusFilter = document.getElementById('status-filter');
        const rows = document.querySelectorAll('.school-row');

        function filterSchools() {
            const query = (searchInput?.value || '').toLowerCase().trim();
            const status = statusFilter?.value || 'all';

            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const npsn = row.getAttribute('data-npsn') || '';
                const rowStatus = row.getAttribute('data-status') || '';

                const matchesQuery = !query || name.includes(query) || npsn.includes(query);
                const matchesStatus = status === 'all' || rowStatus === status;

                row.style.display = (matchesQuery && matchesStatus) ? '' : 'none';
            });
        }

        searchInput?.addEventListener('input', filterSchools);
        statusFilter?.addEventListener('change', filterSchools);
    });
</script>
@endsection
