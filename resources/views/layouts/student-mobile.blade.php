<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'VokaTrack Siswa — Portal PKL' }}</title>
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
<body class="min-h-screen bg-slate-100/90 antialiased selection:bg-indigo-600 selection:text-white flex justify-center">

    <!-- Mobile App Container (Optimized for modern smartphones, centered on desktop) -->
    <div class="w-full max-w-md min-h-screen flex flex-col bg-slate-50/70 border-x-0 sm:border-x border-slate-200/80 shadow-none sm:shadow-2xl relative pb-24">

        <!-- Glassmorphic Sticky Header -->
        <header class="sticky top-0 z-40 flex items-center justify-between px-4 py-3 bg-white/90 backdrop-blur-md border-b border-slate-200/80 shadow-2xs">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-white p-1 flex items-center justify-center shadow-xs border border-slate-200 shrink-0">
                    <img src="{{ asset('images/logo.png') }}" alt="VokaTrack Logo" class="w-full h-full object-contain">
                </div>
                <div class="min-w-0">
                    <h1 class="text-sm font-extrabold text-slate-900 tracking-tight leading-none">VokaTrack</h1>
                    <p class="text-[11px] text-slate-500 truncate max-w-[200px] mt-0.5 font-medium">
                        {{ auth()->user()->school->name ?? 'SMK Indonesia' }}
                    </p>
                </div>
            </div>
            
            <div class="flex items-center space-x-1.5">
                <x-notification-center :isMobile="true" />
                <a href="{{ route('student.announcements') }}" title="Papan Pengumuman" class="relative p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-colors {{ request()->routeIs('student.announcements*') ? 'text-indigo-600 bg-indigo-50' : '' }}">
                    <svg class="w-5 h-5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                    </svg>
                </a>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200/80">
                    Siswa
                </span>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" title="Keluar" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-colors cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </form>
            </div>
        </header>

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="mx-4 mt-3 p-3.5 text-xs rounded-2xl bg-emerald-50 border border-emerald-200/80 text-emerald-900 flex items-center justify-between shadow-2xs">
                <div class="flex items-center space-x-2.5">
                    <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </span>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            </div>
        @endif
        
        @if(session('error'))
            <div class="mx-4 mt-3 p-3.5 text-xs rounded-2xl bg-rose-50 border border-rose-200/80 text-rose-900 flex items-center justify-between shadow-2xs">
                <div class="flex items-center space-x-2.5">
                    <span class="w-6 h-6 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </span>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="mx-4 mt-3 p-3.5 text-xs rounded-2xl bg-rose-50 border border-rose-200/80 text-rose-900 space-y-1 shadow-2xs">
                @foreach($errors->all() as $err)
                    <div class="flex items-center space-x-2 font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 shrink-0"></span>
                        <span>{{ $err }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Main Content Area -->
        <main class="flex-1 px-4 py-4 space-y-4 animate-fade-in">
            @yield('content')
        </main>

        <!-- Floating Modern Bottom Navigation Dock -->
        <nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md z-40 bg-white/95 backdrop-blur-md border-t border-slate-200/80 shadow-lg" style="padding-bottom: env(safe-area-inset-bottom, 8px);">
            <div class="grid grid-cols-4 h-16 items-center px-2">
                
                <!-- Beranda -->
                <a href="{{ route('student.home') }}" class="group inline-flex flex-col items-center justify-center py-1 transition-all active:scale-90 {{ request()->routeIs('student.home') ? 'text-indigo-600' : 'text-slate-400 hover:text-slate-600' }}">
                    <div class="relative p-1 rounded-xl transition-all duration-200 {{ request()->routeIs('student.home') ? 'bg-indigo-50/80 text-indigo-600 scale-110' : 'group-hover:scale-105' }}">
                        <svg class="w-5 h-5 stroke-[2] transition-transform duration-200 group-hover:-translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold tracking-tight mt-0.5">Beranda</span>
                    @if(request()->routeIs('student.home'))
                        <span class="w-1 h-1 rounded-full bg-indigo-600 mt-0.5 animate-pulse"></span>
                    @endif
                </a>

                <!-- Absensi -->
                <a href="{{ route('student.attendance') }}" class="group inline-flex flex-col items-center justify-center py-1 transition-all active:scale-90 {{ request()->routeIs('student.attendance*') ? 'text-indigo-600' : 'text-slate-400 hover:text-slate-600' }}">
                    <div class="relative p-1 rounded-xl transition-all duration-200 {{ request()->routeIs('student.attendance*') ? 'bg-indigo-50/80 text-indigo-600 scale-110' : 'group-hover:scale-105' }}">
                        <svg class="w-5 h-5 stroke-[2] transition-transform duration-200 group-hover:-translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold tracking-tight mt-0.5">Absensi</span>
                    @if(request()->routeIs('student.attendance*'))
                        <span class="w-1 h-1 rounded-full bg-indigo-600 mt-0.5 animate-pulse"></span>
                    @endif
                </a>

                <!-- Jurnal -->
                <a href="{{ route('student.journals') }}" class="group inline-flex flex-col items-center justify-center py-1 transition-all active:scale-90 {{ request()->routeIs('student.journals*') ? 'text-indigo-600' : 'text-slate-400 hover:text-slate-600' }}">
                    <div class="relative p-1 rounded-xl transition-all duration-200 {{ request()->routeIs('student.journals*') ? 'bg-indigo-50/80 text-indigo-600 scale-110' : 'group-hover:scale-105' }}">
                        <svg class="w-5 h-5 stroke-[2] transition-transform duration-200 group-hover:-translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold tracking-tight mt-0.5">Jurnal</span>
                    @if(request()->routeIs('student.journals*'))
                        <span class="w-1 h-1 rounded-full bg-indigo-600 mt-0.5 animate-pulse"></span>
                    @endif
                </a>

                <!-- Profil -->
                <a href="{{ route('student.profile') }}" class="group inline-flex flex-col items-center justify-center py-1 transition-all active:scale-90 {{ request()->routeIs('student.profile') ? 'text-indigo-600' : 'text-slate-400 hover:text-slate-600' }}">
                    <div class="relative p-1 rounded-xl transition-all duration-200 {{ request()->routeIs('student.profile') ? 'bg-indigo-50/80 text-indigo-600 scale-110' : 'group-hover:scale-105' }}">
                        <svg class="w-5 h-5 stroke-[2] transition-transform duration-200 group-hover:-translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold tracking-tight mt-0.5">Profil</span>
                    @if(request()->routeIs('student.profile'))
                        <span class="w-1 h-1 rounded-full bg-indigo-600 mt-0.5 animate-pulse"></span>
                    @endif
                </a>

            </div>
        </nav>

        <x-incident-report-modal />
    </div>

    <x-image-lightbox />

    <script>
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('#checkout-modal, #request-modal, [id^="modal-"]').forEach(m => m.classList.add('hidden'));
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
