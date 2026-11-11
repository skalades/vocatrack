@extends('layouts.app')

@section('page_title', 'Dashboard Platform Super Admin')
@section('page_subtitle', 'Monitoring agregat instalasi multi-sekolah VokaTrack')

@section('content')
<div class="space-y-6">

    <x-page-header 
        title="Dashboard Platform Multi-Sekolah" 
        subtitle="Monitoring status infrastruktur multi-tenancy, total sekolah mitra, dan total pengguna ekosistem VokaTrack."
    >
        <a href="{{ route('superadmin.schools') }}" 
           class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold text-xs shadow-xs shadow-indigo-600/20 transition-all cursor-pointer">
            <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span>Daftarkan Sekolah Baru</span>
        </a>
    </x-page-header>

    <!-- Stat Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <x-stat-card 
            title="Total Tenant Sekolah" 
            :value="$totalSchools" 
            :subtitle="$activeSchools . ' Sekolah Berstatus Aktif'"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Total Siswa Terdaftar" 
            :value="$totalStudents" 
            subtitle="Peserta PKL aktif platform"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Total Akun Pengguna" 
            :value="$totalUsers" 
            subtitle="Admin, Guru, DU/DI, Siswa"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Status Infrastruktur" 
            value="Optimal" 
            subtitle="Database multi-tenancy terisolasi"
            color="amber"
            badge="Healthy"
            badgeVariant="success"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

    </div>

    <!-- Recent Schools Table Card -->
    <x-card 
        title="Daftar Sekolah Mitra Terdaftar" 
        subtitle="Kelola instansi tenant sekolah dan kredensial administrator"
        noPadding
    >
        <x-slot:headerActions>
            <a href="{{ route('superadmin.schools') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors">
                Kelola Semua Sekolah &rarr;
            </a>
        </x-slot:headerActions>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6">Nama Sekolah & Alamat</th>
                        <th class="py-3.5 px-4">NPSN</th>
                        <th class="py-3.5 px-4">Kontak Resmi</th>
                        <th class="py-3.5 px-4 text-center">Kelas</th>
                        <th class="py-3.5 px-4 text-center">Mitra DU/DI</th>
                        <th class="py-3.5 px-6 text-center">Status Tenant</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($recentSchools as $sch)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3.5 px-6">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-700 font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ substr($sch->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <span class="font-bold text-slate-900 block truncate">{{ $sch->name }}</span>
                                        <span class="text-[11px] text-slate-400 block truncate max-w-xs font-normal mt-0.5">{{ $sch->address }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-800">{{ $sch->npsn }}</td>
                            <td class="py-3.5 px-4">
                                <span class="block text-slate-900 font-semibold">{{ $sch->email }}</span>
                                <span class="text-[11px] text-slate-400 block font-mono">{{ $sch->phone }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold text-slate-900">{{ $sch->classes_count }}</td>
                            <td class="py-3.5 px-4 text-center font-bold text-slate-900">{{ $sch->companies_count }}</td>
                            <td class="py-3.5 px-6 text-center">
                                <x-badge :variant="$sch->is_active ? 'success' : 'danger'" size="xs">
                                    {{ $sch->is_active ? 'Aktif' : 'Non-Aktif' }}
                                </x-badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12">
                                <x-empty-state 
                                    title="Belum Ada Sekolah Terdaftar" 
                                    description="Daftarkan tenant sekolah baru untuk memulai onboarding."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

</div>
@endsection
