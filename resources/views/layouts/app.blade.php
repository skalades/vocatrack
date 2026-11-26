@php
    // Tema forest (senada portal siswa) untuk seluruh level staf
    $forestRoles = ['guru-pembimbing' => 'teacher', 'super-admin' => 'admin', 'pembimbing-dudi' => 'industry', 'admin-sekolah' => 'school', 'kepala-sekolah' => 'principal'];
    $currentRole = auth()->check() ? auth()->user()->role : null;
    $isForestTheme = isset($forestRoles[$currentRole]);
    $appKey = $forestRoles[$currentRole] ?? 'staff';
@endphp
<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50 theme-forest">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', (View::hasSection('page_title') ? View::yieldContent('page_title') . ' — ' . ($systemName ?? 'VokaTrack') : ($title ?? ($systemName ?? 'VokaTrack') . ' — Platform PKL SMK Terpadu')))</title>
    <link rel="icon" type="image/png" href="{{ $systemLogo ?? asset('images/logo.png') }}">
    @if($currentRole === 'guru-pembimbing')
        <link rel="manifest" href="/manifest-guru.webmanifest?v={{ substr(md5($systemLogo ?? 'v1'), 0, 8) }}">
        <meta name="theme-color" content="#0B2B25">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="{{ $systemName ?? 'VokaTrack' }} Guru">
        <link rel="apple-touch-icon" href="{{ $systemLogo ?? asset('icons/apple-touch-icon.png') }}">
    @endif
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    
    @if($currentRole === 'guru-pembimbing')
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/teacher-pwa.js'])
    @else
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body data-app="{{ $appKey }}"
      @if($currentRole === 'guru-pembimbing')
          data-user-id="{{ auth()->id() }}"
          data-app-logo="{{ $systemLogo ?? asset('images/logo.png') }}"
          data-app-name="{{ $systemName ?? 'VokaTrack' }}"
          data-vapid-public-key="{{ config('webpush.vapid.public_key') }}"
          data-push-subscribe-url="{{ route('push-subscriptions.store') }}"
          data-push-unsubscribe-url="{{ route('push-subscriptions.destroy') }}"
          data-push-test-url="{{ route('push-subscriptions.test') }}"
      @endif
      class="h-full antialiased text-slate-800 bg-slate-50 {{ $isForestTheme ? 'selection:bg-[#C6F26B] selection:text-[#0B2B25]' : 'selection:bg-indigo-600 selection:text-white' }}">
    <div class="min-h-full flex flex-col lg:flex-row">

        <!-- Mobile Drawer Backdrop -->
        <div id="mobile-sidebar-backdrop" onclick="toggleMobileSidebar()" class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-xs hidden lg:hidden opacity-0 transition-opacity duration-300"></div>

        <!-- Sidebar Navigation -->
        <aside id="sidebar-menu" class="fixed inset-y-0 left-0 z-50 w-72 lg:w-64 {{ $isForestTheme ? 'bg-[#0B2B25]' : 'bg-[#090D16]' }} text-slate-300 flex-shrink-0 flex flex-col border-r border-slate-800/80 -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen transition-transform duration-300 ease-out">
            
            <!-- Sidebar Header / Brand -->
            <div class="h-16 flex items-center justify-between px-5 bg-slate-950/60 border-b border-slate-800/80">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-white p-1 flex items-center justify-center shadow-md shadow-indigo-600/20 border border-slate-700/60 shrink-0">
                        <img src="{{ $systemLogo ?? asset('images/logo.png') }}" alt="{{ $systemName ?? 'VokaTrack' }} Logo" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <span class="text-white font-extrabold text-base tracking-tight leading-none block">{{ $systemName ?? 'VokaTrack' }}</span>
                        <span class="text-[10px] text-indigo-400 font-bold tracking-wider uppercase block mt-1">{{ $systemTagline ?? 'Enterprise Cloud' }}</span>
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
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold text-slate-100 truncate">{{ auth()->user()->supervisor->company->name }}</p>
                        <p class="text-[10px] text-indigo-400 font-mono tracking-tight uppercase">Mitra Industri DU/DI</p>
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
                    if ($isForestTheme) {
                        $activeClass = 'bg-[#C6F26B] text-[#0B2B25] font-extrabold shadow-md shadow-black/20';
                        $inactiveClass = 'text-[#A9C2B9] hover:text-white hover:bg-white/10 transition-all duration-150 group font-semibold';
                    }
                @endphp

                <!-- Role-Based Navigation Submodules -->
                @if($role === 'super-admin')
                    @include('layouts.navigation.superadmin')
                @elseif($role === 'admin-sekolah')
                    @include('layouts.navigation.adminsekolah')
                @elseif($role === 'guru-pembimbing')
                    @include('layouts.navigation.guru')
                @elseif($role === 'pembimbing-dudi')
                    @include('layouts.navigation.dudi')
                @elseif($role === 'kepala-sekolah')
                    @include('layouts.navigation.kepsek')
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
                    <div class="flex items-center gap-1 shrink-0">
                        <button type="button" onclick="openWhatsAppContactModal()" title="Nomor WhatsApp Notifikasi Darurat SOS" class="p-2 text-slate-400 hover:text-emerald-400 rounded-xl hover:bg-slate-800/80 transition-colors cursor-pointer">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2zm.01 1.67c4.54 0 8.24 3.7 8.24 8.24 0 2.2-.86 4.28-2.42 5.84a8.19 8.19 0 0 1-5.82 2.41c-1.46 0-2.9-.38-4.17-1.11l-.3-.17-3.1.81.83-3.02-.2-.31a8.16 8.16 0 0 1-1.25-4.45c0-4.54 3.7-8.24 8.24-8.24zm4.51 11.66c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.24-.75-.67-1.26-1.49-1.41-1.74-.15-.25-.02-.39.11-.51.11-.11.25-.29.37-.43.13-.15.17-.25.25-.42.08-.17.04-.32-.02-.45-.06-.13-.56-1.35-.77-1.84-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1 0 1.24.9 2.44 1.03 2.61.13.17 1.77 2.7 4.29 3.79.6.26 1.07.41 1.43.53.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.15-1.18-.06-.11-.22-.18-.47-.31z"/>
                            </svg>
                        </button>
                        <button type="button" onclick="openChangePasswordModal()" title="Ubah Kata Sandi Saya" class="p-2 text-slate-400 hover:text-indigo-400 rounded-xl hover:bg-slate-800/80 transition-colors cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                        </button>
                        <form action="{{ route('logout') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin keluar dari sistem?');">
                            @csrf
                            <button type="submit" title="Keluar dari Sistem" class="p-2 text-slate-400 hover:text-rose-400 rounded-xl hover:bg-slate-800/80 transition-colors cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Workspace Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <!-- Sticky Impersonation Warning Header -->
            @if(app(\App\Services\ImpersonationService::class)->isImpersonating())
                <div class="bg-amber-400 text-slate-950 px-4 sm:px-6 py-2.5 text-xs font-bold flex items-center justify-between sticky top-0 z-40 border-b border-amber-500 shadow-md animate-fade-in">
                    <div class="flex items-center space-x-2.5 min-w-0">
                        <span class="flex h-2.5 w-2.5 relative shrink-0">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-slate-900 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-slate-950"></span>
                        </span>
                        <span class="truncate">Mode Impersonasi: Anda sedang masuk sebagai <strong>{{ auth()->user()->name }}</strong> ({{ strtoupper(str_replace('-', ' ', auth()->user()->role)) }}).</span>
                    </div>
                    <form action="{{ route('impersonate.leave') }}" method="POST" class="inline shrink-0 ml-3">
                        @csrf
                        <button type="submit" class="px-3 py-1 bg-slate-950 text-white rounded-lg hover:bg-slate-800 transition-colors cursor-pointer text-xs font-extrabold flex items-center gap-1.5 shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            <span>Kembali ke Akun Saya</span>
                        </button>
                    </form>
                </div>
            @endif

            <!-- Global Maintenance / Platform Broadcast Alert -->
            @php
                $isMaintenanceNoticeActive = (bool) \App\Models\PlatformSetting::get('maintenance_active', false);
                $maintenanceNoticeText = \App\Models\PlatformSetting::get('maintenance_notice', '');
            @endphp
            @if($isMaintenanceNoticeActive && !empty($maintenanceNoticeText))
                <div class="bg-indigo-950 text-indigo-100 px-4 sm:px-6 py-2.5 text-xs font-semibold flex items-center justify-between border-b border-indigo-800 shadow-sm">
                    <div class="flex items-center space-x-2.5 min-w-0">
                        <span class="w-6 h-6 rounded-lg bg-indigo-800/80 text-amber-300 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div class="truncate">
                            <span class="font-extrabold text-amber-300 mr-1.5 uppercase text-[10px] tracking-wider px-1.5 py-0.5 rounded bg-indigo-900 border border-indigo-700">Pemberitahuan Sistem</span>
                            <span>{{ $maintenanceNoticeText }}</span>
                        </div>
                    </div>
                </div>
            @endif
            
            <!-- Top Navigation Bar (Responsive Sticky Header) -->
            <header class="h-14 bg-white/95 backdrop-blur-md border-b border-slate-200/80 px-4 sm:px-6 lg:px-8 flex items-center justify-between sticky top-0 z-30 shadow-2xs">
                <div class="flex items-center space-x-3">
                    <button type="button" onclick="toggleMobileSidebar()" class="lg:hidden p-2 -ml-2 rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="flex items-center space-x-2 lg:hidden">
                        <img src="{{ $systemLogo ?? asset('images/logo.png') }}" alt="{{ $systemName ?? 'VokaTrack' }}" class="w-5 h-5 object-contain">
                        <span class="text-xs font-extrabold text-slate-900">{{ $systemName ?? 'VokaTrack' }}</span>
                    </div>
                    <div class="hidden lg:flex items-center space-x-2 text-xs font-semibold text-slate-500">
                        <span>{{ auth()->user()->school->name ?? 'VokaTrack Cloud Platform' }}</span>
                    </div>

                    @if(auth()->user()->isKajur() || auth()->user()->isWaliKelas())
                        <div class="hidden sm:inline-flex items-center p-0.5 rounded-xl bg-slate-100 border border-slate-200">
                            @if(auth()->user()->isKajur())
                                <a href="{{ route('kajur.dashboard') }}" class="flex items-center gap-1.5 px-2.5 py-1 text-xs rounded-lg transition-all {{ request()->is('kajur*') ? 'bg-white text-emerald-800 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium' }}">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span>Kajur {{ auth()->user()->headedMajor()?->code }}</span>
                                </a>
                            @endif
                            @if(auth()->user()->isWaliKelas())
                                <a href="{{ route('walikelas.dashboard') }}" class="flex items-center gap-1.5 px-2.5 py-1 text-xs rounded-lg transition-all {{ request()->is('wali-kelas*') ? 'bg-white text-teal-800 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium' }}">
                                    <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                                    <span>Wali Kelas {{ auth()->user()->primaryHomeroomClass()?->name }}</span>
                                </a>
                            @endif
                            <a href="{{ route('guru.dashboard') }}" class="flex items-center gap-1.5 px-2.5 py-1 text-xs rounded-lg transition-all {{ request()->is('guru*') ? 'bg-white text-indigo-800 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium' }}">
                                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                <span>Bimbingan ({{ auth()->user()->teacher?->studentPkls()->where('status', 'active')->count() ?? 0 }})</span>
                            </a>
                        </div>
                    @endif
                </div>

                <div class="flex items-center space-x-2 sm:space-x-3">
                    @if($role === 'guru-pembimbing')
                        <a href="{{ request()->fullUrlWithQuery(['view' => 'mobile']) }}" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-bold text-[#0B2B25] bg-[#C6F26B]/80 hover:bg-[#C6F26B] border border-[#0B2B25]/20 shadow-2xs transition-all" title="Beralih ke Tampilan Mobile Guru">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            <span class="hidden sm:inline">Mode Mobile</span>
                        </a>
                    @endif
                    <x-notification-center :isMobile="false" />
                    @if($role === 'guru-pembimbing')
                        <button type="button" onclick="openTeacherProfileModal()" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition-colors cursor-pointer" title="Ubah Profil, NIP & Nomor HP">
                            <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="8" r="4" stroke-width="2"/>
                                <path stroke-linecap="round" stroke-width="2" d="M20 21a8 8 0 1 0-16 0"/>
                            </svg>
                            <span class="hidden sm:inline">Profil</span>
                        </button>
                    @endif
                    <button type="button" onclick="openWhatsAppContactModal()" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-colors cursor-pointer" title="Nomor WhatsApp Notifikasi Darurat SOS">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2zm.01 1.67c4.54 0 8.24 3.7 8.24 8.24 0 2.2-.86 4.28-2.42 5.84a8.19 8.19 0 0 1-5.82 2.41c-1.46 0-2.9-.38-4.17-1.11l-.3-.17-3.1.81.83-3.02-.2-.31a8.16 8.16 0 0 1-1.25-4.45c0-4.54 3.7-8.24 8.24-8.24zm4.51 11.66c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.24-.75-.67-1.26-1.49-1.41-1.74-.15-.25-.02-.39.11-.51.11-.11.25-.29.37-.43.13-.15.17-.25.25-.42.08-.17.04-.32-.02-.45-.06-.13-.56-1.35-.77-1.84-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1 0 1.24.9 2.44 1.03 2.61.13.17 1.77 2.7 4.29 3.79.6.26 1.07.41 1.43.53.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.15-1.18-.06-.11-.22-.18-.47-.31z"/>
                        </svg>
                        <span class="hidden sm:inline">Kontak WA</span>
                    </button>
                    <button type="button" onclick="openChangePasswordModal()" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-semibold text-slate-600 hover:text-indigo-600 hover:bg-slate-100 transition-colors cursor-pointer" title="Ubah Kata Sandi Saya">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        <span class="hidden sm:inline">Ubah Sandi</span>
                    </button>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200 uppercase tracking-wider">
                        {{ strtoupper(str_replace('-', ' ', auth()->user()->role)) }}
                    </span>
                </div>
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

                @if(is_array(session('temporary_credential')) && auth()->user()?->hasRole(['super-admin', 'admin-sekolah', 'guru-pembimbing']))
                    @php
                        $tmpCred = session('temporary_credential');
                    @endphp
                    <div id="flash-credential" class="mt-3 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-950 text-xs shadow-2xs space-y-2">
                        <div class="font-bold">Kredensial sementara {{ $tmpCred['context'] ?? '' }} — hanya ditampilkan sekali</div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span>Email: <code class="font-mono font-bold">{{ $tmpCred['email'] ?? '-' }}</code></span>
                            <span>Kata sandi: <code id="flash-credential-password" class="font-mono font-bold px-1.5 py-0.5 rounded bg-white border border-amber-200">{{ $tmpCred['password'] ?? '' }}</code></span>
                            <button type="button" onclick="navigator.clipboard?.writeText(document.getElementById('flash-credential-password').textContent.trim()); this.textContent='Tersalin';" class="px-2 py-1 rounded-lg bg-amber-100 hover:bg-amber-200 font-bold">Salin</button>
                        </div>
                        <p class="text-[11px] text-amber-800">Sampaikan lewat kanal aman. Pengguna wajib mengganti kata sandi ini saat login pertama.</p>
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
                    <div id="flash-validation-errors" class="p-4 rounded-2xl bg-rose-50 border border-rose-200/80 text-rose-900 text-xs shadow-2xs space-y-1.5 flex items-start justify-between">
                        <div class="space-y-1.5 flex-1 pr-2">
                            <div class="flex items-center space-x-2 font-bold text-rose-950 mb-1">
                                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>Terdapat kendala pada input Anda:</span>
                            </div>
                            @foreach($errors->all() as $err)
                                <div class="flex items-center space-x-2 text-rose-800">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 shrink-0"></span>
                                    <span>{{ $err }}</span>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" onclick="document.getElementById('flash-validation-errors').remove()" class="text-rose-700 hover:text-rose-900 p-1 rounded-lg hover:bg-rose-100/50 shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @endif
            </div>

            <!-- Content Area -->
            <main class="flex-1 flex flex-col p-4 sm:p-6 lg:p-8 overflow-y-auto animate-fade-in {{ $role === 'guru-pembimbing' ? 'pb-28 lg:pb-8' : '' }}">
                <div class="max-w-[1600px] mx-auto w-full flex-1 flex flex-col">
                    <!-- Global Platform Announcements / Broadcasts -->
                    @php
                        $activeBroadcasts = \Illuminate\Support\Facades\Schema::hasTable('platform_announcements')
                            ? \App\Models\PlatformAnnouncement::active()->forRole(auth()->user()?->role)->get()
                            : collect();
                    @endphp
                    @foreach($activeBroadcasts as $bc)
                        <div id="broadcast-banner-{{ $bc->id }}" class="mb-5 p-4 rounded-2xl border flex items-start justify-between gap-3 shadow-xs transition-all {{ $bc->type === 'emergency' ? 'bg-rose-50 border-rose-200 text-rose-900' : ($bc->type === 'warning' ? 'bg-amber-50 border-amber-200 text-amber-900' : ($bc->type === 'update' ? 'bg-indigo-50 border-indigo-200 text-indigo-900' : 'bg-blue-50 border-blue-200 text-blue-900')) }}">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 mt-0.5 {{ $bc->type === 'emergency' ? 'bg-rose-100 text-rose-700' : ($bc->type === 'warning' ? 'bg-amber-100 text-amber-700' : ($bc->type === 'update' ? 'bg-indigo-100 text-indigo-700' : 'bg-blue-100 text-blue-700')) }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-xs sm:text-sm font-bold">{{ $bc->title }}</h4>
                                    <p class="text-xs mt-0.5 opacity-90 leading-relaxed">{{ $bc->content }}</p>
                                </div>
                            </div>
                            @if($bc->is_dismissible)
                                <button type="button" onclick="dismissBroadcast({{ $bc->id }})" class="p-1 rounded-lg hover:bg-black/5 opacity-60 hover:opacity-100 transition-opacity" title="Tutup pengumuman">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            @endif
                        </div>
                    @endforeach

                    <div class="flex-1">
                        @yield('content')
                    </div>

                    <footer class="mt-10 pt-5 border-t border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-2 text-[11px] text-slate-500">
                        <span>&copy; {{ date('Y') }} {{ $systemName ?? 'VokaTrack' }} &middot; {{ $systemTagline ?? 'Enterprise Cloud' }}</span>
                        <x-creator-branding variant="light" class="justify-center sm:justify-end" />
                    </footer>
                </div>
            </main>

            @if($role === 'guru-pembimbing')
                <x-teacher.nav-dock :pendingJournals="$sbPendingJournals ?? 0" :pendingRequests="$sbPendingRequests ?? 0" />
            @endif
        </div>
    </div>

    <script>
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar-menu');
            const backdrop = document.getElementById('mobile-sidebar-backdrop');
            if (sidebar.classList.contains('-translate-x-full')) {
                backdrop.classList.remove('hidden');
                requestAnimationFrame(() => {
                    backdrop.classList.remove('opacity-0');
                    backdrop.classList.add('opacity-100');
                    sidebar.classList.remove('-translate-x-full');
                });
                document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
            } else {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.remove('opacity-100');
                backdrop.classList.add('opacity-0');
                setTimeout(() => {
                    backdrop.classList.add('hidden');
                }, 300);
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

            // Hide already dismissed broadcasts
            document.querySelectorAll('[id^="broadcast-banner-"]').forEach(function(el) {
                const id = el.id.replace('broadcast-banner-', '');
                if (localStorage.getItem('dismissed_broadcast_' + id) === '1') {
                    el.style.display = 'none';
                }
            });
        });

        function dismissBroadcast(id) {
            const el = document.getElementById('broadcast-banner-' + id);
            if (el) {
                el.style.display = 'none';
                localStorage.setItem('dismissed_broadcast_' + id, '1');
            }
        }

        // Global Searchable Select Enhancer for <select data-searchable-select>
        (function () {
            const protoDesc = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');

            function enhanceSelect(select) {
                if (!select || select.dataset.searchableInit === '1') return;
                select.dataset.searchableInit = '1';

                const variant = select.getAttribute('data-searchable-variant') === 'emerald' ? 'emerald' : 'indigo';
                const focusRing = variant === 'emerald'
                    ? 'focus:ring-emerald-500/20 focus:border-emerald-600'
                    : 'focus:ring-indigo-500/20 focus:border-indigo-600';
                const activeItemClass = variant === 'emerald'
                    ? 'bg-emerald-50 text-emerald-800 font-bold'
                    : 'bg-indigo-50 text-indigo-700 font-bold';

                const wrapper = document.createElement('div');
                wrapper.className = 'relative w-full';
                wrapper.setAttribute('data-searchable-wrapper', '1');
                select.parentNode.insertBefore(wrapper, select);
                wrapper.appendChild(select);

                select.classList.add('sr-only');
                select.tabIndex = -1;

                const trigger = document.createElement('button');
                trigger.type = 'button';
                trigger.className = `w-full text-xs font-semibold px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 flex items-center justify-between gap-2 text-left cursor-pointer hover:bg-white focus:bg-white focus:outline-none focus:ring-2 ${focusRing} transition-all`;

                const labelSpan = document.createElement('span');
                labelSpan.className = 'truncate flex-1';
                const chevron = document.createElement('span');
                chevron.className = 'shrink-0 text-slate-400';
                chevron.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
                trigger.appendChild(labelSpan);
                trigger.appendChild(chevron);

                const panel = document.createElement('div');
                panel.className = 'hidden absolute left-0 z-50 mt-1.5 w-full bg-white border border-slate-200 rounded-2xl shadow-xl overflow-hidden';
                panel.setAttribute('data-searchable-panel', '1');
                const minWidth = select.getAttribute('data-dropdown-min-width');
                if (minWidth) {
                    panel.style.minWidth = minWidth;
                }

                const searchWrap = document.createElement('div');
                searchWrap.className = 'p-2 border-b border-slate-100 bg-slate-50/80';
                const searchInner = document.createElement('div');
                searchInner.className = 'relative';
                searchInner.innerHTML = '<svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>';

                const searchInput = document.createElement('input');
                searchInput.type = 'text';
                searchInput.autocomplete = 'off';
                searchInput.placeholder = select.getAttribute('data-search-placeholder') || 'Cari...';
                searchInput.className = `w-full text-xs font-medium pl-8 pr-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 ${focusRing}`;
                searchInner.appendChild(searchInput);
                searchWrap.appendChild(searchInner);

                const listEl = document.createElement('div');
                listEl.className = 'max-h-56 overflow-y-auto py-1 divide-y divide-slate-50';

                panel.appendChild(searchWrap);
                panel.appendChild(listEl);
                wrapper.appendChild(trigger);
                wrapper.appendChild(panel);

                function syncLabel() {
                    const idx = select.selectedIndex;
                    const opt = idx >= 0 ? select.options[idx] : select.options[0];
                    labelSpan.textContent = opt ? opt.textContent.trim() : (select.getAttribute('data-search-placeholder') || 'Pilih...');
                }

                function renderList(term) {
                    const q = (term || '').trim().toLowerCase();
                    listEl.innerHTML = '';
                    const opts = Array.from(select.options).filter(o => o.style.display !== 'none');
                    const matches = opts.filter(o => !q || o.textContent.toLowerCase().includes(q));

                    if (matches.length === 0) {
                        const empty = document.createElement('div');
                        empty.className = 'px-3.5 py-3 text-[11px] text-slate-400 italic text-center';
                        empty.textContent = 'Tidak ada data yang cocok';
                        listEl.appendChild(empty);
                        return;
                    }

                    matches.forEach(opt => {
                        const item = document.createElement('button');
                        item.type = 'button';
                        const isSelected = opt.value === select.value;
                        item.className = `w-full text-left px-3 py-2 text-xs transition-colors flex items-center justify-between gap-2 ${
                            opt.disabled
                                ? 'text-slate-400 cursor-not-allowed bg-slate-50/50'
                                : (isSelected ? activeItemClass + ' cursor-pointer' : 'text-slate-700 hover:bg-slate-50 font-medium cursor-pointer')
                        }`;
                        const textSpan = document.createElement('span');
                        textSpan.className = 'truncate';
                        textSpan.textContent = opt.textContent.trim();
                        item.appendChild(textSpan);

                        if (isSelected && !opt.disabled) {
                            const check = document.createElement('span');
                            check.className = 'shrink-0 text-[10px]';
                            check.innerHTML = '&#10003;';
                            item.appendChild(check);
                        }

                        if (!opt.disabled) {
                            item.addEventListener('click', (e) => {
                                e.stopPropagation();
                                protoDesc.set.call(select, opt.value);
                                syncLabel();
                                panel.classList.add('hidden');
                                select.dispatchEvent(new Event('change', { bubbles: true }));
                            });
                        }

                        listEl.appendChild(item);
                    });
                }

                function closePanel() {
                    panel.classList.add('hidden');
                }

                trigger.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const willOpen = panel.classList.contains('hidden');
                    document.querySelectorAll('[data-searchable-panel]').forEach(p => p.classList.add('hidden'));
                    if (willOpen) {
                        searchInput.value = '';
                        renderList('');
                        panel.classList.remove('hidden');
                        setTimeout(() => searchInput.focus(), 10);
                    }
                });

                searchInput.addEventListener('input', () => renderList(searchInput.value));
                searchInput.addEventListener('click', (e) => e.stopPropagation());
                searchInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        e.stopPropagation();
                        closePanel();
                        trigger.focus();
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        const firstBtn = listEl.querySelector('button:not([disabled])');
                        if (firstBtn) firstBtn.click();
                    }
                });

                if (protoDesc && protoDesc.configurable) {
                    Object.defineProperty(select, 'value', {
                        get() {
                            return protoDesc.get.call(this);
                        },
                        set(val) {
                            protoDesc.set.call(this, val);
                            syncLabel();
                            if (!panel.classList.contains('hidden')) {
                                renderList(searchInput.value);
                            }
                        },
                        configurable: true,
                    });
                }

                select.addEventListener('change', syncLabel);

                const observer = new MutationObserver(() => {
                    syncLabel();
                    if (!panel.classList.contains('hidden')) {
                        renderList(searchInput.value);
                    }
                });
                observer.observe(select, { childList: true, subtree: true, attributes: true, attributeFilter: ['style', 'disabled', 'selected'] });

                syncLabel();
            }

            window.initSearchableSelects = function (root = document) {
                root.querySelectorAll('select[data-searchable-select]').forEach(enhanceSelect);
            };

            document.addEventListener('click', (e) => {
                if (!e.target.closest('[data-searchable-wrapper]')) {
                    document.querySelectorAll('[data-searchable-panel]').forEach(p => p.classList.add('hidden'));
                }
            });

            document.addEventListener('DOMContentLoaded', () => window.initSearchableSelects());
        })();
    </script>
    <x-image-lightbox />
    <x-change-password-modal />
    <x-whatsapp-contact-modal />
    @if($role === 'guru-pembimbing')
        <x-teacher.profile-modal />
    @endif

    @stack('scripts')
</body>
</html>
