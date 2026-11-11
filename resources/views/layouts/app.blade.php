<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', (View::hasSection('page_title') ? View::yieldContent('page_title') . ' — VokaTrack' : ($title ?? 'VokaTrack — Platform PKL SMK Terpadu')))</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="h-full antialiased text-slate-800 bg-slate-50 selection:bg-indigo-600 selection:text-white">
    <div class="min-h-full flex flex-col lg:flex-row">

        <!-- Mobile Drawer Backdrop -->
        <div id="mobile-sidebar-backdrop" onclick="toggleMobileSidebar()" class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-xs hidden lg:hidden transition-opacity"></div>

        <!-- Sidebar Navigation -->
        <aside id="sidebar-menu" class="fixed inset-y-0 left-0 z-50 w-72 lg:w-64 bg-[#090D16] text-slate-300 flex-shrink-0 flex flex-col border-r border-slate-800/80 -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen transition-transform duration-200 ease-in-out">
            
            <!-- Sidebar Header / Brand -->
            <div class="h-16 flex items-center justify-between px-5 bg-slate-950/60 border-b border-slate-800/80">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-white p-1 flex items-center justify-center shadow-md shadow-indigo-600/20 border border-slate-700/60 shrink-0">
                        <img src="{{ asset('images/logo.png') }}" alt="VokaTrack Logo" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <span class="text-white font-extrabold text-base tracking-tight leading-none block">VokaTrack</span>
                        <span class="text-[10px] text-indigo-400 font-bold tracking-wider uppercase block mt-1">Enterprise Cloud</span>
                    </div>
                </div>

                <!-- Close Drawer Button for Mobile -->
                <button type="button" onclick="toggleMobileSidebar()" class="lg:hidden p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- School Context Banner -->
            @if(auth()->user()->school)
                <div class="px-5 py-3.5 bg-slate-900/60 border-b border-slate-800/70 flex items-center space-x-3">
                    <div class="relative flex items-center justify-center w-2.5 h-2.5 shrink-0">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold text-slate-100 truncate">{{ auth()->user()->school->name }}</p>
                        <p class="text-[10px] text-slate-400 font-mono tracking-tight">NPSN: {{ auth()->user()->school->npsn }}</p>
                    </div>
                </div>
            @elseif(auth()->user()->supervisor && auth()->user()->supervisor->company)
                <div class="px-5 py-3.5 bg-slate-900/60 border-b border-slate-800/70 flex items-center space-x-3">
                    <div class="relative flex items-center justify-center w-2.5 h-2.5 shrink-0">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-cyan-500"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold text-slate-100 truncate">{{ auth()->user()->supervisor->company->name }}</p>
                        <p class="text-[10px] text-cyan-400 font-mono tracking-tight uppercase">Mitra Industri DU/DI</p>
                    </div>
                </div>
            @elseif(auth()->user()->role === 'super-admin')
                <div class="px-5 py-3.5 bg-slate-900/60 border-b border-slate-800/70 flex items-center space-x-3">
                    <div class="relative flex items-center justify-center w-2.5 h-2.5 shrink-0">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold text-slate-100 truncate">VokaTrack Multi-Tenant</p>
                        <p class="text-[10px] text-indigo-400 font-mono tracking-tight uppercase">Platform Core Cloud</p>
                    </div>
                </div>
            @endif

            <!-- Navigation Links by Role -->
            <nav class="flex-1 px-3.5 py-4 space-y-1 overflow-y-auto custom-scrollbar">
                @php
                    $role = auth()->user()->role;
                    $activeClass = 'bg-gradient-to-r from-indigo-600 to-indigo-700 text-white font-bold shadow-md shadow-indigo-600/30 border-l-4 border-indigo-400';
                    $inactiveClass = 'text-slate-300 hover:text-white hover:bg-slate-800/70 transition-all duration-150 group font-semibold';
                @endphp

                <!-- 1. Super Admin Nav -->
                @if($role === 'super-admin')
                    <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest px-3 pt-2 pb-1.5 flex items-center justify-between">
                        <span>Super Admin Platform</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                    </div>
                    <a href="{{ route('superadmin.dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('superadmin.dashboard') ? $activeClass : $inactiveClass }}">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span>Dashboard Platform</span>
                    </a>
                    <a href="{{ route('superadmin.schools') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('superadmin.schools*') ? $activeClass : $inactiveClass }}">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>Manajemen Sekolah</span>
                    </a>
                @endif

                <!-- 2. Admin Sekolah Nav -->
                @if($role === 'admin-sekolah')
                    <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest px-3 pt-2 pb-1.5 flex items-center justify-between">
                        <span>Manajemen Sekolah</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                    </div>
                    <a href="{{ route('adminsekolah.dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('adminsekolah.dashboard') ? $activeClass : $inactiveClass }}">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('adminsekolah.settings') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('adminsekolah.settings') ? $activeClass : $inactiveClass }}">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Konfigurasi & Jam Kerja</span>
                    </a>
                    <a href="{{ route('adminsekolah.majors') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('adminsekolah.majors*') ? $activeClass : $inactiveClass }}">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        <span>Jurusan & Kelas</span>
                    </a>
                    <a href="{{ route('adminsekolah.students') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('adminsekolah.students*') ? $activeClass : $inactiveClass }}">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span>Siswa & Impor Excel</span>
                    </a>
                    <a href="{{ route('adminsekolah.teachers') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('adminsekolah.teachers*') ? $activeClass : $inactiveClass }}">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span>Guru Pembimbing</span>
                    </a>
                    <a href="{{ route('adminsekolah.companies') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('adminsekolah.companies*') ? $activeClass : $inactiveClass }}">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Mitra DU/DI & GPS</span>
                    </a>
                    <a href="{{ route('adminsekolah.plotting') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('adminsekolah.plotting*') ? $activeClass : $inactiveClass }}">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        <span>Plotting Penugasan PKL</span>
                    </a>
                    <a href="{{ route('adminsekolah.announcements') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('adminsekolah.announcements*') ? $activeClass : $inactiveClass }}">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        <span>Pengumuman & Siaran</span>
                    </a>
                @endif

                <!-- 3. Guru Pembimbing Nav -->
                @if($role === 'guru-pembimbing')
                    <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest px-3 pt-2 pb-1.5 flex items-center justify-between">
                        <span>Bimbingan PKL</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    </div>
                    <a href="{{ route('guru.dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('guru.dashboard') ? $activeClass : $inactiveClass }}">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span>Dashboard Bimbingan</span>
                    </a>
                    <a href="{{ route('guru.announcements') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('guru.announcements*') ? $activeClass : $inactiveClass }}">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        <span>Pengumuman</span>
                    </a>
                    <a href="{{ route('guru.monitoring') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('guru.monitoring*') ? $activeClass : $inactiveClass }}">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <span>Monitoring Absensi</span>
                    </a>
                    <a href="{{ route('guru.requests') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('guru.requests*') ? $activeClass : $inactiveClass }}">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Izin & Sakit Siswa</span>
                    </a>
                    <a href="{{ route('guru.journals') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('guru.journals*') ? $activeClass : $inactiveClass }}">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Review Jurnal (Tingkat 2)</span>
                    </a>
                    <a href="{{ route('guru.assessments') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('guru.assessments*') ? $activeClass : $inactiveClass }}">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <span>Penilaian Sekolah</span>
                    </a>
                    <a href="{{ route('guru.reports') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('guru.reports*') ? $activeClass : $inactiveClass }}">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Ekspor Laporan PDF</span>
                    </a>
                @endif

                <!-- 4. Pembimbing DU/DI Nav -->
                @if($role === 'pembimbing-dudi')
                    <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest px-3 pt-2 pb-1.5 flex items-center justify-between">
                        <span>Mentor Industri DU/DI</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
                    </div>
                    <a href="{{ route('dudi.dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('dudi.dashboard') ? $activeClass : $inactiveClass }}">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span>Dashboard Industri</span>
                    </a>
                    <a href="{{ route('dudi.attendances') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('dudi.attendances*') ? $activeClass : $inactiveClass }}">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        <span>Validasi Absensi Harian</span>
                    </a>
                    <a href="{{ route('dudi.requests') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('dudi.requests*') ? $activeClass : $inactiveClass }}">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Dinas Luar & WFH</span>
                    </a>
                    <a href="{{ route('dudi.journals') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('dudi.journals*') ? $activeClass : $inactiveClass }}">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Approval Jurnal (Tingkat 1)</span>
                    </a>
                    <a href="{{ route('dudi.assessments') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('dudi.assessments*') ? $activeClass : $inactiveClass }}">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                        <span>Penilaian Kompetensi</span>
                    </a>
                @endif

                <!-- 5. Kepala Sekolah Nav -->
                @if($role === 'kepala-sekolah')
                    <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest px-3 pt-2 pb-1.5 flex items-center justify-between">
                        <span>Pimpinan Sekolah</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                    </div>
                    <a href="{{ route('kepsek.dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('kepsek.dashboard') ? $activeClass : $inactiveClass }}">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <span>Analitik Agregat PKL</span>
                    </a>
                    <a href="{{ route('kepsek.rekap') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('kepsek.rekap*') ? $activeClass : $inactiveClass }}">
                        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Rekapitulasi Kehadiran</span>
                    </a>
                @endif
            </nav>

            <!-- User Profile & Logout Footbar -->
            <div class="p-3.5 border-t border-slate-800/80 bg-slate-950/60">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0 flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs shadow-indigo-600/30">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-white truncate">{{ auth()->user()->name }}</p>
                            <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 mt-0.5">
                                {{ strtoupper(str_replace('-', ' ', auth()->user()->role)) }}
                            </span>
                        </div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" title="Keluar dari Sistem" class="p-2 text-slate-400 hover:text-rose-400 rounded-xl hover:bg-slate-800/80 transition-colors cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Workspace Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- Mobile Navigation Bar (Sticky on mobile, hidden on desktop) -->
            <header class="lg:hidden h-14 bg-white/95 backdrop-blur-md border-b border-slate-200/80 px-4 flex items-center justify-between sticky top-0 z-30 shadow-2xs">
                <button type="button" onclick="toggleMobileSidebar()" class="p-2 -ml-2 rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="flex items-center space-x-2">
                    <img src="{{ asset('images/logo.png') }}" alt="VokaTrack" class="w-5 h-5 object-contain">
                    <span class="text-xs font-extrabold text-slate-900">VokaTrack</span>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200 uppercase tracking-wider">
                    {{ strtoupper(str_replace('-', ' ', auth()->user()->role)) }}
                </span>
            </header>

            <!-- Dismissible Flash Alerts -->
            <div class="px-4 sm:px-6 lg:px-8 pt-4">
                @if(session('success'))
                    <div id="flash-success" class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200/80 text-emerald-900 text-xs font-medium flex items-center justify-between shadow-2xs">
                        <div class="flex items-center space-x-2.5 min-w-0">
                            <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span class="truncate">{{ session('success') }}</span>
                        </div>
                        <button type="button" onclick="document.getElementById('flash-success').remove()" class="text-emerald-700 hover:text-emerald-900 p-1 rounded-lg hover:bg-emerald-100/50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div id="flash-error" class="p-4 rounded-2xl bg-rose-50 border border-rose-200/80 text-rose-900 text-xs font-medium flex items-center justify-between shadow-2xs">
                        <div class="flex items-center space-x-2.5 min-w-0">
                            <span class="w-7 h-7 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </span>
                            <span class="truncate">{{ session('error') }}</span>
                        </div>
                        <button type="button" onclick="document.getElementById('flash-error').remove()" class="text-rose-700 hover:text-rose-900 p-1 rounded-lg hover:bg-rose-100/50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200/80 text-rose-900 text-xs shadow-2xs space-y-1.5">
                        <div class="flex items-center space-x-2 font-bold text-rose-950 mb-1">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>Terdapat kendala pada input Anda:</span>
                        </div>
                        @foreach($errors->all() as $err)
                            <div class="flex items-center space-x-2 text-rose-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 shrink-0"></span>
                                <span>{{ $err }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Content Area -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto">
                <div class="max-w-7xl mx-auto w-full">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <script>
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar-menu');
            const backdrop = document.getElementById('mobile-sidebar-backdrop');
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
                document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
            } else {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
                document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
            }
        }

        // Global Escape Key Listener for Modals & Mobile Sidebar
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('[id^="modal-"]').forEach(modal => {
                    modal.classList.add('hidden');
                });
                const backdrop = document.getElementById('mobile-sidebar-backdrop');
                const sidebar = document.getElementById('sidebar-menu');
                if (backdrop && !backdrop.classList.contains('hidden')) {
                    backdrop.classList.add('hidden');
                    sidebar.classList.add('-translate-x-full');
                    document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
                }
            }
        });

        // Global Synchronized Live Clock Updater for All Pages
        function updateGlobalLiveClocks() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const timeStr = `${hours}:${minutes}:${seconds} WIB`;

            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const dateStr = `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;

            document.querySelectorAll('.live-clock-time, #banner-live-clock').forEach(el => el.textContent = timeStr);
            document.querySelectorAll('.live-clock-date, #banner-live-date').forEach(el => el.textContent = dateStr);
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateGlobalLiveClocks();
            setInterval(updateGlobalLiveClocks, 1000);
        });
    </script>
    <x-image-lightbox />

    @stack('scripts')
</body>
</html>
