<!DOCTYPE html>
<html lang="id" class="h-full bg-[#F1F4EC] text-[#10211D]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') &bull; {{ $systemName ?? 'VokaTrack' }}</title>
    <link rel="icon" type="image/png" href="{{ $systemLogo ?? asset('images/logo.png') }}">

    <!-- Google Fonts: Plus Jakarta Sans & Space Grotesk -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
        .font-space { font-family: 'Space Grotesk', system-ui, sans-serif; letter-spacing: -0.02em; }
        .bg-pattern {
            background-color: #F1F4EC;
            background-image:
                radial-gradient(ellipse at 15% 0%, rgba(198, 242, 107, 0.28) 0, transparent 50%),
                radial-gradient(ellipse at 90% 100%, rgba(11, 43, 37, 0.08) 0, transparent 55%),
                linear-gradient(rgba(11, 43, 37, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(11, 43, 37, 0.03) 1px, transparent 1px);
            background-size: auto, auto, 28px 28px, 28px 28px;
        }
    </style>
</head>
<body class="min-h-screen bg-pattern flex flex-col items-center justify-center p-4 sm:p-6 relative overflow-hidden antialiased selection:bg-[#C6F26B] selection:text-[#0B2B25]">

    <!-- Ambient Rings Backdrop -->
    <div class="fixed inset-0 pointer-events-none -z-10 overflow-hidden flex items-center justify-center" aria-hidden="true">
        <div class="absolute w-[560px] h-[560px] rounded-full border border-[#0B2B25]/5 animate-pulse"></div>
        <div class="absolute w-[820px] h-[820px] rounded-full border border-[#0B2B25]/[0.03]"></div>
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-[#C6F26B]/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-[#0B2B25]/10 rounded-full blur-3xl"></div>
    </div>

    <!-- Error Card Container -->
    <div class="w-full max-w-lg bg-white border border-[#0B2B25]/10 rounded-3xl p-8 sm:p-10 text-center shadow-2xl shadow-[#0B2B25]/10 backdrop-blur-xl relative">
        <div class="flex items-center justify-center gap-3 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-[#0B2B25]/5 border border-[#0B2B25]/10 p-2 flex items-center justify-center shrink-0">
                <img src="{{ $systemLogo ?? asset('images/logo.png') }}" alt="{{ $systemName ?? 'VokaTrack' }} Logo" class="w-full h-full object-contain">
            </div>
            <div class="text-left">
                <span class="font-space font-extrabold text-[#0B2B25] text-lg tracking-tight block">{{ $systemName ?? 'VokaTrack' }}</span>
                <span class="text-[11px] font-bold text-[#0B2B25]/50 uppercase tracking-wider block">Sistem Monitoring PKL</span>
            </div>
        </div>

        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full border border-[#0B2B25]/10 bg-[#0B2B25]/5 text-xs font-mono font-bold text-[#0B2B25] mb-4">
            <span class="w-2 h-2 rounded-full @yield('dot_class', 'bg-amber-500 animate-pulse')"></span>
            <span>HTTP @yield('code')</span>
            <span class="text-[#0B2B25]/30">&bull;</span>
            <span class="@yield('badge_class', 'text-[#0B2B25]')">@yield('badge_text', 'System Notice')</span>
        </div>

        <h1 class="font-space text-2xl sm:text-3xl font-extrabold text-[#0B2B25] tracking-tight mb-3">
            @yield('heading')
        </h1>

        <p class="text-[#0B2B25]/70 text-sm leading-relaxed mb-8 max-w-md mx-auto">
            @yield('message')
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ url('/') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl bg-[#0B2B25] hover:bg-[#12382F] text-[#C6F26B] text-xs font-extrabold transition-all shadow-lg shadow-[#0B2B25]/15 active:scale-95 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Kembali ke Beranda</span>
            </a>
            <button type="button" onclick="window.history.back()" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl bg-white hover:bg-[#F1F4EC] text-[#0B2B25] border border-[#0B2B25]/15 text-xs font-extrabold transition-all active:scale-95 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Halaman Sebelumnya</span>
            </button>
        </div>
    </div>

    <footer class="mt-6">
        <x-creator-branding variant="light" class="justify-center text-[#0B2B25]/60" />
    </footer>

</body>
</html>
