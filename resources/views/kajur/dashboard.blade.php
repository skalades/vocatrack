@extends('layouts.app')

@section('page_title', 'Dashboard Kepala Jurusan')
@section('page_subtitle', 'Manajemen operasional PKL kompetensi keahlian ' . $major->name)

@section('content')
<div class="w-full space-y-6">

    <!-- Hero Banner -->
    <div class="relative overflow-hidden bg-gradient-to-r from-emerald-950 via-slate-900 to-slate-950 rounded-3xl p-6 sm:p-7 text-white shadow-xl shadow-emerald-950/20 border border-emerald-900/40">
        <div class="relative space-y-4">
            <!-- Top Banner Meta -->
            <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-white/10">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-400 text-emerald-950 uppercase tracking-wider">
                        KEPALA PROGRAM KEAHLIAN
                    </span>
                    <span class="text-xs text-white/30">&bull;</span>
                    <span class="text-xs font-bold text-emerald-300">{{ $major->code }} - {{ $major->name }}</span>
                    <span class="text-xs text-white/30">&bull;</span>
                    <span class="text-xs font-semibold text-slate-300">{{ auth()->user()->school->name ?? 'SMK' }}</span>
                </div>

                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-xl bg-white/[0.06] border border-white/10 text-xs text-slate-200">
                    <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>{{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}</span>
                </div>
            </div>

            <!-- Greeting & Quick Actions -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
                <div class="space-y-1.5 min-w-0">
                    <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-white">
                        Selamat Datang, {{ auth()->user()->name }}
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-300 font-medium max-w-2xl leading-relaxed">
                        Pantau kedisiplinan industri, kelola kemitraan DU/DI kejuruan, dan distribusikan penugasan guru pembimbing untuk peserta didik jurusan <span class="text-emerald-300 font-bold">{{ $major->name }}</span>.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <x-button type="button" variant="primary" size="sm" onclick="window.location.href='{{ route('kajur.companies') }}'">
                        <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        <span>+ Tambah Mitra DU/DI</span>
                    </x-button>
                    <x-button type="button" variant="secondary" size="sm" onclick="window.location.href='{{ route('kajur.students') }}'">
                        <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span>Plotting Siswa</span>
                    </x-button>
                </div>
            </div>
        </div>
    </div>

    <!-- Dual-Role Alert (Rangkap Guru Pembimbing) -->
    @if($myMenteesCount > 0)
        <div class="p-4 rounded-2xl bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-200/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-xs">
            <div class="flex items-center space-x-3.5">
                <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-indigo-950">Anda Merangkap sebagai Guru Pembimbing</h4>
                    <p class="text-xs text-indigo-700">
                        Anda membimbing langsung <strong>{{ $myMenteesCount }} siswa</strong>. 
                        @if($myPendingJournalsCount > 0)
                            Terdapat <span class="font-bold text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded">{{ $myPendingJournalsCount }} jurnal</span> yang menunggu verifikasi Anda.
                        @else
                            Seluruh jurnal siswa binaan Anda telah diverifikasi.
                        @endif
                    </p>
                </div>
            </div>
            <a href="{{ route('guru.dashboard') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-indigo-700 bg-white hover:bg-indigo-50 border border-indigo-200 transition-colors shadow-2xs shrink-0">
                <span>Buka Portal Bimbingan Saya</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    @endif

    <!-- Key Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card 
            title="Total Siswa Jurusan" 
            :value="$totalStudents" 
            :subtitle="$totalPlotted . ' Terplotting • ' . $totalUnplotted . ' Belum'"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Mitra DU/DI Jurusan" 
            :value="$totalCompanies" 
            subtitle="Perusahaan mitra terhubung"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Presensi Siswa Hari Ini" 
            :value="$hadirToday" 
            :subtitle="$izinToday . ' Izin/Sakit • ' . $alfaToday . ' Alfa'"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Jurnal Pending Verifikasi" 
            :value="$pendingJournalsCount" 
            subtitle="Menunggu approval guru pembimbing"
            :color="$pendingJournalsCount > 0 ? 'amber' : 'emerald'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Quick Management Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Panel Navigasi Cepat -->
        <div class="lg:col-span-1 space-y-4">
            <x-card title="Menu Cepat Kepala Jurusan" subtitle="Pintasan administrasi & supervisi">
                <div class="space-y-2">
                    <a href="{{ route('kajur.companies') }}" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-emerald-50 border border-slate-200/60 hover:border-emerald-200 transition-colors group">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                            </div>
                            <div>
                                <h5 class="text-xs font-bold text-slate-900 group-hover:text-emerald-700 transition-colors">Kelola Mitra DU/DI</h5>
                                <p class="text-[11px] text-slate-500">Tambah perusahaan & mentor industri</p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-emerald-600 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>

                    <a href="{{ route('kajur.students') }}" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-emerald-50 border border-slate-200/60 hover:border-emerald-200 transition-colors group">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
                            </div>
                            <div>
                                <h5 class="text-xs font-bold text-slate-900 group-hover:text-indigo-700 transition-colors">Plotting & Penugasan PKL</h5>
                                <p class="text-[11px] text-slate-500">Pasangkan siswa ke DU/DI & guru</p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-indigo-600 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>

                    <a href="{{ route('kajur.attendance') }}" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-emerald-50 border border-slate-200/60 hover:border-emerald-200 transition-colors group">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </div>
                            <div>
                                <h5 class="text-xs font-bold text-slate-900 group-hover:text-blue-700 transition-colors">Monitoring Presensi</h5>
                                <p class="text-[11px] text-slate-500">Pantau kehadiran harian per kelas</p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-blue-600 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>

                    <a href="{{ route('kajur.grades') }}" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-emerald-50 border border-slate-200/60 hover:border-emerald-200 transition-colors group">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </div>
                            <div>
                                <h5 class="text-xs font-bold text-slate-900 group-hover:text-teal-700 transition-colors">Rekapitulasi Nilai Jurusan</h5>
                                <p class="text-[11px] text-slate-500">Nilai akhir DU/DI + Guru & ekspor CSV</p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-teal-600 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </x-card>
        </div>

        <!-- Laporan Insiden & Kendala Siswa -->
        <div class="lg:col-span-2">
            <x-card title="Laporan Kendala / Insiden Siswa Jurusan" subtitle="Laporan darurat & kendala kerja industri">
                @if($recentIncidents->isEmpty())
                    <x-empty-state 
                        title="Tidak Ada Laporan Insiden" 
                        description="Seluruh kegiatan PKL siswa jurusan berjalan aman dan lancar."
                    />
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($recentIncidents as $inc)
                            <div class="py-3.5 first:pt-0 last:pb-0 flex items-start justify-between gap-3">
                                <div class="min-w-0 space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-900 text-xs sm:text-sm">{{ $inc->student->name }}</span>
                                        <x-badge :variant="$inc->status === 'resolved' ? 'emerald' : 'rose'" size="xs">
                                            {{ strtoupper($inc->status) }}
                                        </x-badge>
                                    </div>
                                    <p class="text-xs text-slate-600 line-clamp-1 font-medium">{{ $inc->title }}</p>
                                    <div class="text-[11px] text-slate-400">
                                        {{ $inc->company?->name ?? 'DU/DI' }} &bull; {{ $inc->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>
    </div>

</div>
@endsection
