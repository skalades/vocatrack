<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'VokaTrack Siswa' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="flex justify-center min-h-screen bg-slate-200 antialiased selection:bg-indigo-600 selection:text-white">
    <!-- Mobile App Container (360px - 430px max width for authentic mobile feel on desktop as well) -->
    <div class="w-full max-w-md min-h-screen flex flex-col bg-slate-50 shadow-2xl relative pb-20 border-x border-slate-200">

        <!-- Sticky Header -->
        <header class="sticky top-0 z-40 flex items-center justify-between px-4 py-3 bg-white/95 backdrop-blur-md border-b border-slate-200">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-500 flex items-center justify-center text-white font-black text-sm shadow-sm shadow-indigo-200">
                    VT
                </div>
                <div>
                    <h1 class="text-sm font-bold text-slate-900 leading-none">VokaTrack</h1>
                    <p class="text-[11px] text-slate-500 truncate max-w-[210px] mt-0.5 font-medium">
                        {{ auth()->user()->school->name ?? 'SMK Indonesia' }}
                    </p>
                </div>
            </div>
            <div class="flex items-center space-x-1.5">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    Siswa
                </span>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" title="Keluar" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </form>
            </div>
        </header>

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="mx-4 mt-3 p-3 text-xs rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center space-x-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="mx-4 mt-3 p-3 text-xs rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center space-x-2">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif
        @if($errors->any())
            <div class="mx-4 mt-3 p-3 text-xs rounded-xl bg-rose-50 border border-rose-200 text-rose-800 space-y-1">
                @foreach($errors->all() as $err)
                    <div class="flex items-center space-x-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                        <span>{{ $err }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Main Content Area -->
        <main class="flex-1 px-4 py-3 space-y-4">
            @yield('content')
        </main>

        <!-- Fixed Bottom Navigation Bar -->
        <nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md z-40 bg-white/95 backdrop-blur-md border-t border-slate-200" style="padding-bottom: env(safe-area-inset-bottom);">
            <div class="grid grid-cols-4 h-16">
                <!-- Beranda -->
                <a href="{{ route('student.home') }}" class="inline-flex flex-col items-center justify-center text-xs font-semibold {{ request()->routeIs('student.home') ? 'text-indigo-600' : 'text-slate-400 hover:text-slate-600' }}">
                    <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Beranda</span>
                </a>

                <!-- Absensi -->
                <a href="{{ route('student.attendance') }}" class="inline-flex flex-col items-center justify-center text-xs font-semibold {{ request()->routeIs('student.attendance*') ? 'text-indigo-600' : 'text-slate-400 hover:text-slate-600' }}">
                    <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Absensi</span>
                </a>

                <!-- Jurnal -->
                <a href="{{ route('student.journals') }}" class="inline-flex flex-col items-center justify-center text-xs font-semibold {{ request()->routeIs('student.journals*') ? 'text-indigo-600' : 'text-slate-400 hover:text-slate-600' }}">
                    <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Jurnal</span>
                </a>

                <!-- Profil -->
                <a href="{{ route('student.profile') }}" class="inline-flex flex-col items-center justify-center text-xs font-semibold {{ request()->routeIs('student.profile') ? 'text-indigo-600' : 'text-slate-400 hover:text-slate-600' }}">
                    <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span>Profil</span>
                </a>
            </div>
        </nav>
    </div>

    @stack('scripts')
</body>
</html>
