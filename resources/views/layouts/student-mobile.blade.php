<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-200">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'VokaTrack Siswa — Portal PKL' }}</title>
    <link rel="icon" type="image/png" href="{{ $systemLogo ?? asset('images/logo.png') }}">
    
    <!-- Google Fonts: Plus Jakarta Sans & Space Grotesk -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
        .font-space { font-family: 'Space Grotesk', system-ui, sans-serif; }
    </style>
    @stack('styles')
</head>
<body data-app="student" class="min-h-screen bg-slate-200/80 antialiased selection:bg-[#C6F26B] selection:text-[#0B2B25] flex justify-center">

    <!-- Mobile App Container (Viewport 390px - 420px, Centered on desktop) -->
    <div class="w-full max-w-[420px] min-h-screen flex flex-col bg-[#F1F4EC] text-[#10211D] relative shadow-2xl overflow-x-hidden pb-24">

        <!-- Sticky Impersonation Warning Header for Mobile -->
        @if(app(\App\Services\ImpersonationService::class)->isImpersonating())
            <div class="bg-amber-400 text-slate-950 px-3.5 py-2 text-[11px] font-bold flex items-center justify-between sticky top-0 z-50 border-b border-amber-500 shadow-md">
                <div class="flex items-center space-x-2 min-w-0">
                    <span class="flex h-2 w-2 relative shrink-0">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-slate-900 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-slate-950"></span>
                    </span>
                    <span class="truncate">Mode Impersonasi: <strong>{{ auth()->user()->name }}</strong></span>
                </div>
                <form action="{{ route('impersonate.leave') }}" method="POST" class="inline shrink-0 ml-2">
                    @csrf
                    <button type="submit" class="px-2.5 py-1 bg-slate-950 text-white rounded-lg hover:bg-slate-800 transition-colors cursor-pointer text-[10px] font-extrabold flex items-center gap-1 shadow-xs">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span>Kembali</span>
                    </button>
                </form>
            </div>
        @endif

        <!-- Sticky Generic Header for Secondary Pages (Profile, Announcements, Incidents) -->
        @if(!request()->routeIs(['student.home', 'student.attendance', 'student.journals']))
            <header class="sticky top-0 z-40 flex items-center justify-between px-4 py-3 bg-[#0B2B25] text-white shadow-sm">
                <div class="flex items-center space-x-3">
                    <a href="{{ route('student.home') }}" class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-white/20 transition-colors">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div class="min-w-0">
                        <h1 class="text-sm font-bold tracking-tight leading-none text-white">{{ $systemName ?? 'VokaTrack' }}</h1>
                        <p class="text-[11px] text-[#A9C2B9] truncate max-w-[200px] mt-0.5 font-medium">
                            {{ auth()->user()->school->name ?? 'SMK Indonesia' }}
                        </p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-1.5">
                    <x-notification-center :isMobile="true" />
                    <a href="{{ route('student.profile') }}" title="Profil Siswa" 
                       class="p-1.5 text-white/80 hover:text-[#C6F26B] hover:bg-white/10 rounded-xl transition-colors {{ request()->routeIs('student.profile') ? 'text-[#C6F26B] bg-white/10' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="8" r="4" stroke-width="2"/>
                            <path d="M20 21a8 8 0 1 0-16 0" stroke-width="2"/>
                        </svg>
                    </a>
                    <form action="{{ route('logout') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin keluar dari akun Siswa?');">
                        @csrf
                        <button type="submit" title="Keluar" class="p-1.5 text-white/60 hover:text-white hover:bg-white/10 rounded-xl transition-colors cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
            </header>
        @endif

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="mx-4 mt-3 p-3.5 text-xs rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-center justify-between shadow-2xs z-30">
                <div class="flex items-center space-x-2.5">
                    <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </span>
                    <span class="font-bold">{{ session('success') }}</span>
                </div>
            </div>
        @endif
        
        @if(session('error'))
            <div class="mx-4 mt-3 p-3.5 text-xs rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 flex items-center justify-between shadow-2xs z-30">
                <div class="flex items-center space-x-2.5">
                    <span class="w-6 h-6 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </span>
                    <span class="font-bold">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="mx-4 mt-3 p-3.5 text-xs rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 space-y-1 shadow-2xs z-30">
                @foreach($errors->all() as $err)
                    <div class="flex items-center space-x-2 font-semibold">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 shrink-0"></span>
                        <span>{{ $err }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Main Content Area -->
        <main class="flex-1">
            @yield('content')
        </main>

        <!-- Floating Modern Student Bottom Dock (Otomatis disembunyikan saat layar kamera presensi) -->
        @if(!request()->routeIs('student.attendance') || !empty($todayAttendance))
            <x-student.nav-dock />
        @endif

        <!-- Screen 6: Bantuan Darurat SOS Modal -->
        <x-student.sos-modal />

        <!-- Screen 5: Peringatan Fake GPS Modal -->
        <x-student.fake-gps-modal :spkl="$spkl ?? null" />

        <!-- Panduan Izin Akses Kamera & GPS (iOS Safari & Android) -->
        <x-student.permission-guide-modal />

        <!-- Quick Attendance Request Modal (Izin, Sakit, Dinas Luar) -->
        <x-attendance-request-modal />

        <x-incident-report-modal />
    </div>

    <x-image-lightbox />
    <x-change-password-modal />

    <script>
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('#checkout-modal, #request-modal, #mobile-notification-drawer, #home-announcement-modal, [id^="modal-"]').forEach(m => m.classList.add('hidden'));
                document.body.style.overflow = '';
                if (window.closeSosModal) window.closeSosModal();
                if (window.closeFakeGpsModal) window.closeFakeGpsModal();
                if (window.closePermissionGuideModal) window.closePermissionGuideModal();
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
