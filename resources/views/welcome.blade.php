<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VokaTrack - Platform PKL SMK Multi-Sekolah Terintegrasi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-100 font-sans antialiased min-h-screen relative overflow-x-hidden selection:bg-brand-500 selection:text-white">

    <!-- Ambient Mesh Glow Background -->
    <div class="fixed inset-0 pointer-events-none -z-10 overflow-hidden">
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-brand-600/20 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute top-1/3 -right-40 w-[30rem] h-[30rem] bg-indigo-500/15 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 left-1/3 w-96 h-96 bg-purple-600/15 rounded-full blur-3xl"></div>
        <div class="absolute inset-0 bg-[radial-gradient(#1e293b_1px,transparent_1px)] [background-size:24px_24px] opacity-25"></div>
    </div>

    <!-- Navigation Header -->
    <header class="border-b border-slate-800/80 bg-slate-950/70 backdrop-blur-xl sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-500 flex items-center justify-center font-black text-white text-lg shadow-lg shadow-brand-500/25 ring-1 ring-white/20">
                    VT
                </div>
                <div>
                    <span class="text-xl font-bold tracking-tight text-white block">VokaTrack</span>
                    <span class="text-[10px] uppercase font-semibold tracking-wider text-slate-400 block -mt-1">SMK PKL Ecosystem</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold shadow-lg shadow-brand-600/30 transition-all transform hover:-translate-y-0.5 active:translate-y-0">
                    <span>Masuk ke Sistem</span>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-28 relative">
        <div class="text-center max-w-3xl mx-auto space-y-6">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-slate-900/90 border border-slate-800 text-xs font-medium text-slate-300 shadow-sm backdrop-blur-md">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                <span>Multi-Sekolah Multi-Tenant PKL SaaS Ready</span>
            </div>

            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white tracking-tight leading-[1.15]">
                Monitoring & Penilaian <br>
                <span class="bg-gradient-to-r from-brand-400 via-indigo-300 to-purple-400 bg-clip-text text-transparent">PKL Vokasi Modern</span>
            </h1>

            <p class="text-slate-400 text-base sm:text-lg max-w-2xl mx-auto leading-relaxed">
                Kelola seluruh ekosistem Praktek Kerja Lapangan SMK dari multi-tenant sekolah, absensi berbasis GPS Geofencing & swafoto kamera, jurnal dwi-verifikasi (DU/DI & Guru), hingga konversi nilai otomatis standar DU/DI.
            </p>

            <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('login') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-7 py-3.5 rounded-xl bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white font-semibold shadow-xl shadow-brand-600/25 transition-all transform hover:-translate-y-0.5">
                    <span>Mulai Menggunakan VokaTrack</span>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Role Badges Matrix -->
        <div class="mt-20 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-sm text-center">
                <div class="text-brand-400 text-xs font-bold uppercase tracking-wider mb-1">Super Admin</div>
                <div class="text-slate-300 text-xs font-medium">Tenant & Sekolah</div>
            </div>
            <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-sm text-center">
                <div class="text-indigo-400 text-xs font-bold uppercase tracking-wider mb-1">Admin Sekolah</div>
                <div class="text-slate-300 text-xs font-medium">Plotting & Master</div>
            </div>
            <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-sm text-center">
                <div class="text-teal-400 text-xs font-bold uppercase tracking-wider mb-1">Siswa PKL</div>
                <div class="text-slate-300 text-xs font-medium">PWA & Absen GPS</div>
            </div>
            <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-sm text-center">
                <div class="text-sky-400 text-xs font-bold uppercase tracking-wider mb-1">Guru Pembimbing</div>
                <div class="text-slate-300 text-xs font-medium">Monitoring & Verifikasi</div>
            </div>
            <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-sm text-center">
                <div class="text-amber-400 text-xs font-bold uppercase tracking-wider mb-1">Pembimbing DU/DI</div>
                <div class="text-slate-300 text-xs font-medium">Approval & Rubrik</div>
            </div>
            <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-sm text-center">
                <div class="text-purple-400 text-xs font-bold uppercase tracking-wider mb-1">Kepala Sekolah</div>
                <div class="text-slate-300 text-xs font-medium">Rekapitulasi Eksekutif</div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/60 py-8 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} VokaTrack Enterprise. Platform Praktik Kerja Lapangan Vokasi Indonesia.
    </footer>
</body>
</html>
