<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'VokaTrack — Portal PKL SMK' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="h-full antialiased text-slate-800 bg-slate-50">
    <div class="min-h-full flex flex-col lg:flex-row">
        <!-- Sidebar Navigation -->
        <aside class="w-full lg:w-64 bg-slate-900 text-slate-300 flex-shrink-0 flex flex-col border-r border-slate-800">
            <!-- Sidebar Header / Brand -->
            <div class="h-16 flex items-center justify-between px-6 bg-slate-950/40 border-b border-slate-800">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-indigo-600 flex items-center justify-center text-white font-black text-sm shadow-md shadow-indigo-900/50">
                        VT
                    </div>
                    <div>
                        <span class="text-white font-extrabold text-base tracking-tight block">VokaTrack</span>
                        <span class="text-[10px] text-indigo-400 font-semibold tracking-wider uppercase block">PKL SMK Cloud</span>
                    </div>
                </div>
            </div>

            <!-- School Context Banner -->
            @if(auth()->user()->school)
                <div class="px-5 py-3 bg-slate-800/40 border-b border-slate-800/60 flex items-center space-x-3">
                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-white truncate">{{ auth()->user()->school->name }}</p>
                        <p class="text-[10px] text-slate-400">NPSN: {{ auth()->user()->school->npsn }}</p>
                    </div>
                </div>
            @endif

            <!-- Navigation Links by Role -->
            <nav class="flex-1 px-4 py-4 space-y-1.5 overflow-y-auto">
                @php
                    $role = auth()->user()->role;
                @endphp

                <!-- 1. Super Admin Nav -->
                @if($role === 'super-admin')
                    <div class="text-[11px] font-bold text-slate-500 px-3 uppercase tracking-wider mb-2">Super Admin</div>
                    <a href="{{ route('superadmin.dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold {{ request()->routeIs('superadmin.dashboard') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span>Dashboard Platform</span>
                    </a>
                    <a href="{{ route('superadmin.schools') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold {{ request()->routeIs('superadmin.schools*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>Manajemen Sekolah</span>
                    </a>
                @endif

                <!-- 2. Admin Sekolah Nav -->
                @if($role === 'admin-sekolah')
                    <div class="text-[11px] font-bold text-slate-500 px-3 uppercase tracking-wider mb-2">Administrasi Sekolah</div>
                    <a href="{{ route('adminsekolah.dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold {{ request()->routeIs('adminsekolah.dashboard') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('adminsekolah.settings') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold {{ request()->routeIs('adminsekolah.settings') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Konfigurasi & Jam Kerja</span>
                    </a>
                    <a href="{{ route('adminsekolah.majors') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold {{ request()->routeIs('adminsekolah.majors*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        <span>Jurusan & Kelas</span>
                    </a>
                    <a href="{{ route('adminsekolah.students') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold {{ request()->routeIs('adminsekolah.students*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span>Siswa & Impor Excel</span>
                    </a>
                    <a href="{{ route('adminsekolah.teachers') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold {{ request()->routeIs('adminsekolah.teachers*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span>Guru Pembimbing</span>
                    </a>
                    <a href="{{ route('adminsekolah.companies') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold {{ request()->routeIs('adminsekolah.companies*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Mitra DU/DI & GPS</span>
                    </a>
                    <a href="{{ route('adminsekolah.plotting') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold {{ request()->routeIs('adminsekolah.plotting*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        <span>Plotting Penugasan PKL</span>
                    </a>
                @endif

                <!-- 3. Guru Pembimbing Nav -->
                @if($role === 'guru-pembimbing')
                    <div class="text-[11px] font-bold text-slate-500 px-3 uppercase tracking-wider mb-2">Guru Pembimbing</div>
                    <a href="{{ route('guru.dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold {{ request()->routeIs('guru.dashboard') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span>Dashboard Bimbingan</span>
                    </a>
                    <a href="{{ route('guru.monitoring') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold {{ request()->routeIs('guru.monitoring*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <span>Monitoring Absensi</span>
                    </a>
                    <a href="{{ route('guru.requests') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold {{ request()->routeIs('guru.requests*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Izin & Sakit Siswa</span>
                    </a>
                    <a href="{{ route('guru.journals') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold {{ request()->routeIs('guru.journals*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Review Jurnal (Tingkat 2)</span>
                    </a>
                    <a href="{{ route('guru.assessments') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold {{ request()->routeIs('guru.assessments*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <span>Penilaian Sekolah</span>
                    </a>
                    <a href="{{ route('guru.reports') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold {{ request()->routeIs('guru.reports*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Ekspor Laporan PDF</span>
                    </a>
                @endif

                <!-- 4. Pembimbing DU/DI Nav -->
                @if($role === 'pembimbing-dudi')
                    <div class="text-[11px] font-bold text-slate-500 px-3 uppercase tracking-wider mb-2">Mentor Industri DU/DI</div>
                    <a href="{{ route('dudi.dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold {{ request()->routeIs('dudi.dashboard') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span>Dashboard Industri</span>
                    </a>
                    <a href="{{ route('dudi.attendances') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold {{ request()->routeIs('dudi.attendances*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        <span>Validasi Absensi Harian</span>
                    </a>
                    <a href="{{ route('dudi.requests') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold {{ request()->routeIs('dudi.requests*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Dinas Luar & WFH</span>
                    </a>
                    <a href="{{ route('dudi.journals') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold {{ request()->routeIs('dudi.journals*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Approval Jurnal (Tingkat 1)</span>
                    </a>
                    <a href="{{ route('dudi.assessments') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold {{ request()->routeIs('dudi.assessments*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                        <span>Penilaian Kompetensi</span>
                    </a>
                @endif

                <!-- 5. Kepala Sekolah Nav -->
                @if($role === 'kepala-sekolah')
                    <div class="text-[11px] font-bold text-slate-500 px-3 uppercase tracking-wider mb-2">Kepala Sekolah</div>
                    <a href="{{ route('kepapsekolah.dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold {{ request()->routeIs('kepapsekolah.dashboard') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <span>Analitik Agregat PKL</span>
                    </a>
                    <a href="{{ route('kepapsekolah.rekap') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold {{ request()->routeIs('kepapsekolah.rekap*') ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Rekapitulasi Kehadiran</span>
                    </a>
                @endif
            </nav>

            <!-- User Profile & Logout Footbar -->
            <div class="p-4 border-t border-slate-800 bg-slate-950/40">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-700 text-white flex items-center justify-center font-bold text-xs">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-white truncate">{{ auth()->user()->name }}</p>
                            <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-semibold bg-indigo-900/80 text-indigo-300">
                                {{ strtoupper(str_replace('-', ' ', auth()->user()->role)) }}
                            </span>
                        </div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" title="Keluar" class="p-1.5 text-slate-400 hover:text-rose-400 rounded-lg hover:bg-slate-800 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Top Header -->
            <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-800">@yield('page_title', 'Dashboard')</h2>
                    <p class="text-xs text-slate-500">@yield('page_subtitle', 'Sistem Manajemen PKL SMK Terintegrasi')</p>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                        {{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}
                    </span>
                </div>
            </header>

            <!-- Flash Alerts -->
            <div class="px-6 pt-4">
                @if(session('success'))
                    <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center space-x-2">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                @if(session('error'))
                    <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium flex items-center space-x-2">
                        <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
                @if($errors->any())
                    <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
                        @foreach($errors->all() as $err)
                            <div class="flex items-center space-x-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                <span>{{ $err }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Content Area -->
            <main class="flex-1 p-6 overflow-y-auto">
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
