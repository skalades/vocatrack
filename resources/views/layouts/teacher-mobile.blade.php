<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-200 theme-forest">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', (View::hasSection('page_title') ? View::yieldContent('page_title') . ' — ' . ($systemName ?? 'VokaTrack') : 'Guru Pembimbing — ' . ($systemName ?? 'VokaTrack')))</title>
    <link rel="icon" type="image/png" href="{{ $systemLogo ?? asset('images/logo.png') }}">

    <!-- PWA Settings for Teacher Mobile -->
    <link rel="manifest" href="/manifest-guru.webmanifest?v={{ substr(md5($systemLogo ?? 'v1'), 0, 8) }}">
    <meta name="theme-color" content="#0B2B25">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $systemName ?? 'VokaTrack' }} Guru">
    <link rel="apple-touch-icon" href="{{ $systemLogo ?? asset('icons/apple-touch-icon.png') }}">

    <!-- Google Fonts: Plus Jakarta Sans & Space Grotesk -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/teacher-pwa.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
        .font-space { font-family: 'Space Grotesk', system-ui, sans-serif; }
        /* Animated decorative background rings (consistent with student mobile header) */
        .hdr-rings { opacity: .7; }
        .hdr-ring-group { transform-box: view-box; }
        .hdr-ring-group--a { animation: hdr-spin 40s linear infinite; }
        .hdr-ring-group--b { animation: hdr-spin 55s linear infinite reverse; }
        .hdr-ring-group circle:not(.hdr-ring-wave) { animation: hdr-breathe 6s ease-in-out infinite; }
        .hdr-ring-wave { transform-box: fill-box; transform-origin: center; opacity: 0; animation: hdr-wave 5s ease-out infinite; }
        .hdr-ring-wave--late { animation-delay: 2.5s; }
        @keyframes hdr-spin    { to { transform: rotate(360deg); } }
        @keyframes hdr-breathe { 0%, 100% { stroke-opacity: .55; } 50% { stroke-opacity: 1; } }
        @keyframes hdr-wave    { 0% { transform: scale(1); opacity: .35; } 100% { transform: scale(2.4); opacity: 0; } }
        @media (prefers-reduced-motion: reduce) {
            .hdr-ring-group, .hdr-ring-group circle, .hdr-ring-wave { animation: none !important; }
        }
    </style>
    @stack('styles')
</head>
<body data-app="teacher"
      data-user-id="{{ auth()->id() }}"
      data-app-logo="{{ $systemLogo ?? asset('images/logo.png') }}"
      data-app-name="{{ $systemName ?? 'VokaTrack' }}"
      data-vapid-public-key="{{ config('webpush.vapid.public_key') }}"
      data-push-subscribe-url="{{ route('push-subscriptions.store') }}"
      data-push-unsubscribe-url="{{ route('push-subscriptions.destroy') }}"
      data-push-test-url="{{ route('push-subscriptions.test') }}"
      data-app-timezone="{{ config('app.timezone') }}"
      class="min-h-screen bg-slate-200/90 antialiased selection:bg-[#C6F26B] selection:text-[#0B2B25] flex justify-center">

    <!-- Mobile App Container (Centered on desktop, full-width on mobile) -->
    <div data-teacher-shell class="w-full max-w-[440px] min-h-screen flex flex-col bg-[#F8FAF9] text-[#10211D] relative shadow-2xl overflow-x-hidden pb-28">

        <!-- Impersonation Warning Header for Mobile -->
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

        <!-- Global Teacher Mobile Hero Header (Style Identik Dashboard Guru & Siswa) -->
        <x-teacher.mobile-hero-header />

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="mx-3.5 mt-3 p-3 text-xs rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-center justify-between shadow-2xs z-30 animate-fade-in">
                <div class="flex items-center space-x-2">
                    <span class="w-5 h-5 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </span>
                    <span class="font-bold text-[11px]">{{ session('success') }}</span>
                </div>
            </div>
        @endif
        
        @if(session('error'))
            <div class="mx-3.5 mt-3 p-3 text-xs rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 flex items-center justify-between shadow-2xs z-30 animate-fade-in">
                <div class="flex items-center space-x-2">
                    <span class="w-5 h-5 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </span>
                    <span class="font-bold text-[11px]">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="mx-3.5 mt-3 p-3 text-xs rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 space-y-1 shadow-2xs z-30 animate-fade-in">
                @foreach($errors->all() as $err)
                    <div class="flex items-center space-x-2 font-semibold text-[11px]">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 shrink-0"></span>
                        <span>{{ $err }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Main Content Area -->
        <main class="flex-1 px-3.5 pt-3.5 space-y-3.5 pb-4">
            @yield('content')
        </main>

        <footer class="px-3.5 pt-2 pb-4 mt-auto">
            <x-creator-branding variant="light" class="justify-center text-[#5A6B65]" />
        </footer>

        <!-- Floating Bottom Dock Navigation -->
        <x-teacher.nav-dock />

    </div>

    <!-- Modals -->
    <x-image-lightbox />
    <x-change-password-modal />
    <x-whatsapp-contact-modal />
    <x-student.app-dialog />

    <script>
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('[id^="modal-"], #image-lightbox-modal, #change-password-modal, #whatsapp-contact-modal').forEach(m => m.classList.add('hidden'));
                document.body.style.overflow = '';
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
