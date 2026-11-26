@props([
    'teacherName' => null,
    'totalStudents' => null,
    'totalCompanies' => null,
    'todayPresentCount' => null,
    'expectedToday' => null,
    'attendancePct' => null,
    'pendingJournals' => null,
    'pendingRequests' => null,
    'periodProgress' => null,
])

@php
    $shared = $__env->getShared();
    $teacherUser = auth()->user();
    $teacher = $teacherUser?->teacher;
    $teacherName = $teacherName ?? ($teacherUser?->name ?? 'Guru Pembimbing');
    $nameParts = explode(' ', trim($teacherName));
    $teacherFirstName = $nameParts[0];
    $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));

    // Detect Page Route Mode
    $isDashboard = request()->routeIs('guru.dashboard');
    $isMonitoring = request()->routeIs('guru.monitoring');
    $isJournals = request()->routeIs('guru.journals');
    $isRequests = request()->routeIs('guru.requests');

    // 1. DASHBOARD DATA
    if ($isDashboard || (!$isMonitoring && !$isJournals && !$isRequests)) {
        if ($teacher) {
            $spkls = null;
            if ($totalStudents === null || $totalCompanies === null || $periodProgress === null || $expectedToday === null || $todayPresentCount === null) {
                $spkls = $teacher->studentPkls()->with(['company', 'pklPeriod'])->get();
                $spklIds = $spkls->pluck('id');
                
                if ($totalStudents === null) {
                    $totalStudents = $spkls->count();
                }
                if ($totalCompanies === null) {
                    $totalCompanies = $spkls->pluck('company_id')->filter()->unique()->count();
                }
                if ($periodProgress === null) {
                    $activePeriod = $spkls->pluck('pklPeriod')->filter()->unique('id')->sortByDesc(fn ($p) => $p->is_active)->first();
                    if ($activePeriod && $activePeriod->start_date && $activePeriod->end_date) {
                        $today = \Carbon\Carbon::today();
                        $totalDays = max(1, $activePeriod->start_date->diffInDays($activePeriod->end_date) + 1);
                        $elapsed = $today->lt($activePeriod->start_date) ? 0 : min($totalDays, $activePeriod->start_date->diffInDays($today) + 1);
                        $periodProgress = [
                            'name' => $activePeriod->name,
                            'remaining' => max(0, $totalDays - $elapsed),
                            'percent' => (int) round($elapsed / $totalDays * 100),
                        ];
                    }
                }
                if ($expectedToday === null) {
                    $expectedToday = $totalStudents;
                }
                if ($todayPresentCount === null && $spklIds->isNotEmpty()) {
                    $todayPresentCount = \App\Models\Attendance::whereIn('student_pkl_id', $spklIds)
                        ->whereDate('date', \Carbon\Carbon::today()->toDateString())
                        ->whereIn('status', ['hadir', 'terlambat', 'dinas_luar', 'luar_kantor'])
                        ->count();
                }
            }

            $spklIds = $spkls ? $spkls->pluck('id') : ($teacher->studentPkls()->pluck('id'));
            if ($spklIds->isNotEmpty()) {
                if ($pendingJournals === null) {
                    $pendingJournals = \App\Models\Journal::whereIn('student_pkl_id', $spklIds)->where('status', 'review_guru')->count();
                }
                if ($pendingRequests === null) {
                    $pendingRequests = \App\Models\AttendanceRequest::whereIn('student_pkl_id', $spklIds)->where('status', 'pending')->count();
                }
            }
        }

        $totalStudents = (int) ($totalStudents ?? 0);
        $totalCompanies = (int) ($totalCompanies ?? 0);
        $todayPresentCount = (int) ($todayPresentCount ?? 0);
        $expectedToday = (int) ($expectedToday ?? 0);
        $attendancePct = $attendancePct ?? ($expectedToday > 0 ? (int) round($todayPresentCount / $expectedToday * 100) : 0);
        $pendingJournals = (int) ($pendingJournals ?? 0);
        $pendingRequests = (int) ($pendingRequests ?? 0);
        $verifTotal = $pendingRequests + $pendingJournals;
    }

    // 2. MONITORING DATA
    if ($isMonitoring) {
        $monitoringStats = $shared['stats'] ?? [];
        $attendances = $shared['attendances'] ?? null;
        $totalLog = $monitoringStats['total'] ?? ($attendances ? $attendances->total() : 0);
        $hadirCount = $monitoringStats['hadir'] ?? 0;
        $lateCount = $monitoringStats['late'] ?? 0;
        $leaveCount = $monitoringStats['leave'] ?? 0;
        $currentDate = request('date', '');
        $currentStatus = request('status', '');
        $verifTotal = (int) ($pendingRequests ?? 0) + (int) ($pendingJournals ?? 0);
    }

    // 3. JOURNALS DATA
    if ($isJournals) {
        $journalStats = $shared['stats'] ?? [];
        $journalsObj = $shared['journals'] ?? null;
        $studentPklsList = $shared['studentPkls'] ?? collect();
        $totalJournals = $journalsObj ? $journalsObj->total() : 0;
        $pendingGuru = $journalStats['pending_guru'] ?? 0;
        $approvedCount = $journalStats['approved'] ?? 0;
        $revisionCount = $journalStats['revision'] ?? 0;
        $verifTotal = $pendingGuru;
    }

    // 4. REQUESTS DATA
    if ($isRequests) {
        $requestStats = $shared['stats'] ?? [];
        $requestsObj = $shared['requests'] ?? null;
        $totalRequests = $requestStats['total'] ?? ($requestsObj ? $requestsObj->total() : 0);
        $pendingCount = $requestStats['pending'] ?? 0;
        $approvedCount = $requestStats['approved'] ?? 0;
        $rejectedCount = $requestStats['rejected'] ?? 0;
        $verifTotal = $pendingCount;
    }
@endphp

<!-- ============================================================== -->
<!-- GURU MOBILE HERO HEADER (UNIVERSAL REUSABLE COMMAND CENTER)   -->
<!-- ============================================================== -->
<header class="relative px-5 pt-6 pb-6 bg-[#0B2B25] rounded-b-[32px] overflow-hidden shadow-lg shadow-[#0B2B25]/20 select-none">
    <!-- Animated decorative background rings (Identik VokaTrack Forest) -->
    <svg viewBox="0 0 390 260" preserveAspectRatio="xMaxYMin slice" aria-hidden="true" class="hdr-rings absolute inset-0 w-full h-full pointer-events-none">
        <g class="hdr-ring-group hdr-ring-group--a" style="transform-origin:350px 40px">
            <circle cx="350" cy="40" r="60" fill="none" stroke="#1C4A3E" stroke-width="2"/>
            <circle cx="350" cy="40" r="100" fill="none" stroke="#1C4A3E" stroke-width="2"/>
            <circle cx="350" cy="40" r="140" fill="none" stroke="#1C4A3E" stroke-width="2" stroke-dasharray="6 10"/>
            <circle class="hdr-ring-wave" cx="350" cy="40" r="60" fill="none" stroke="#C6F26B" stroke-width="1.5"/>
        </g>
        <g class="hdr-ring-group hdr-ring-group--b" style="transform-origin:40px 240px">
            <circle cx="40" cy="240" r="80" fill="none" stroke="#1C4A3E" stroke-width="2"/>
            <circle cx="40" cy="240" r="120" fill="none" stroke="#1C4A3E" stroke-width="2" stroke-dasharray="4 12"/>
            <circle class="hdr-ring-wave hdr-ring-wave--late" cx="40" cy="240" r="80" fill="none" stroke="#C6F26B" stroke-width="1.5"/>
        </g>
    </svg>

    <div class="relative flex flex-col gap-4">

        {{-- ========================================================== --}}
        {{-- TOP ROW: Back / Avatar + Page Title + Header Action Capsule --}}
        {{-- ========================================================== --}}
        <div class="flex items-center gap-3">
            @if(!$isDashboard)
                <!-- Tombol Kembali ke Dashboard untuk Sub-halaman -->
                <a href="{{ route('guru.dashboard') }}" 
                   class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 active:scale-95 flex items-center justify-center text-white shrink-0 border border-white/10 transition-all shadow-xs"
                   title="Kembali ke Dashboard">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>
            @endif

            <!-- Avatar in Neon Lime -->
            <div class="w-11 h-11 rounded-full bg-[#C6F26B] text-[#0B2B25] flex items-center justify-center font-space font-bold text-base shrink-0 shadow-sm ring-2 ring-white/10" aria-label="Profil {{ $teacherName }}">
                {{ $initials }}
            </div>

            <!-- Identity / Page Information Tailored Per Page -->
            <div class="flex-1 min-w-0">
                @if($isDashboard)
                    <span class="text-[10px] font-extrabold text-[#C6F26B] uppercase tracking-[0.12em] block leading-none">
                        Portal Guru Pembimbing
                    </span>
                    <span class="font-bold text-[17px] leading-tight text-white block truncate mt-1">
                        Halo, {{ $teacherFirstName }}
                    </span>
                    <span class="text-[11.5px] leading-tight text-[#A9C2B9] block truncate mt-0.5">
                        {{ $totalStudents }} Siswa Bimbingan &bull; {{ $totalCompanies }} DU/DI
                    </span>
                @elseif($isMonitoring)
                    <span class="text-[10px] font-extrabold text-[#C6F26B] uppercase tracking-[0.12em] block leading-none">
                        Portal Guru Pembimbing
                    </span>
                    <span class="font-bold text-[17px] leading-tight text-white block truncate mt-1">
                        Monitoring Presensi
                    </span>
                    <span class="text-[11.5px] leading-tight text-[#A9C2B9] block truncate mt-0.5">
                        {{ $currentDate ? \Carbon\Carbon::parse($currentDate)->isoFormat('dddd, D MMMM Y') : 'Rekam Kehadiran Real-time' }}
                    </span>
                @elseif($isJournals)
                    <span class="text-[10px] font-extrabold text-[#C6F26B] uppercase tracking-[0.12em] block leading-none">
                        Review Jurnal (Tingkat 2)
                    </span>
                    <span class="font-bold text-[17px] leading-tight text-white block truncate mt-1">
                        Logbook Jurnal PKL
                    </span>
                    <span class="text-[11.5px] leading-tight text-[#A9C2B9] block truncate mt-0.5">
                        {{ $studentPklsList->count() }} Siswa Bimbingan &bull; Validasi Kurikulum
                    </span>
                @elseif($isRequests)
                    <span class="text-[10px] font-extrabold text-[#C6F26B] uppercase tracking-[0.12em] block leading-none">
                        Verifikasi Presensi Khusus
                    </span>
                    <span class="font-bold text-[17px] leading-tight text-white block truncate mt-1">
                        Izin & Sakit Siswa
                    </span>
                    <span class="text-[11.5px] leading-tight text-[#A9C2B9] block truncate mt-0.5">
                        Validasi Berkas Surat & Dispensasi
                    </span>
                @else
                    <span class="text-[10px] font-extrabold text-[#C6F26B] uppercase tracking-[0.12em] block leading-none">
                        Portal Guru Pembimbing
                    </span>
                    <span class="font-bold text-[17px] leading-tight text-white block truncate mt-1">
                        @yield('page_title', $title ?? 'Menu Guru')
                    </span>
                    <span class="text-[11.5px] leading-tight text-[#A9C2B9] block truncate mt-0.5">
                        {{ auth()->user()->school->name ?? 'SMK Indonesia' }}
                    </span>
                @endif
            </div>

            <!-- Header Actions Capsule (Desktop, Bell, WhatsApp, Logout) -->
            <div class="flex items-center gap-0.5 p-1 rounded-full bg-white/[0.08] border border-white/10 shrink-0">
                <!-- Switch to Desktop View Button -->
                <a href="{{ request()->fullUrlWithQuery(['view' => 'desktop']) }}" 
                   title="Beralih ke Tampilan Laptop/Desktop" 
                   class="flex items-center justify-center w-8 h-8 rounded-full text-white/80 hover:bg-white/15 hover:text-[#C6F26B] transition-colors">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </a>

                <!-- Notifications / Announcements -->
                <a href="{{ route('guru.announcements') }}" 
                   title="Pengumuman & Notifikasi"
                   class="relative flex items-center justify-center w-8 h-8 rounded-full text-white/80 hover:bg-white/15 hover:text-[#C6F26B] transition-colors">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                        <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                    </svg>
                    @if($verifTotal > 0)
                        <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-[#C6F26B] border border-[#0B2B25]"></span>
                    @endif
                </a>

                <!-- Teacher Self-Service Profile Button -->
                <button type="button"
                        onclick="openTeacherProfileModal()"
                        title="Profil & NIP Guru"
                        class="flex items-center justify-center w-8 h-8 rounded-full text-white/80 hover:bg-white/15 hover:text-[#C6F26B] transition-colors cursor-pointer">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="8" r="4"/>
                        <path stroke-linecap="round" d="M20 21a8 8 0 1 0-16 0"/>
                    </svg>
                </button>

                <!-- WhatsApp Modal Button -->
                <button type="button" 
                        onclick="openWhatsAppContactModal()" 
                        title="Kontak Darurat DU/DI"
                        class="flex items-center justify-center w-8 h-8 rounded-full text-white/80 hover:bg-white/15 hover:text-emerald-400 transition-colors cursor-pointer">
                    <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2zm.01 1.67c4.54 0 8.24 3.7 8.24 8.24 0 2.2-.86 4.28-2.42 5.84a8.19 8.19 0 0 1-5.82 2.41c-1.46 0-2.9-.38-4.17-1.11l-.3-.17-3.1.81.83-3.02-.2-.31a8.16 8.16 0 0 1-1.25-4.45c0-4.54 3.7-8.24 8.24-8.24zm4.51 11.66c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.24-.75-.67-1.26-1.49-1.41-1.74-.15-.25-.02-.39.11-.51.11-.11.25-.29.37-.43.13-.15.17-.25.25-.42.08-.17.04-.32-.02-.45-.06-.13-.56-1.35-.77-1.84-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1 0 1.24.9 2.44 1.03 2.61.13.17 1.77 2.7 4.29 3.79.6.26 1.07.41 1.43.53.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.15-1.18-.06-.11-.22-.18-.47-.31z"/>
                    </svg>
                </button>

                <!-- Logout Form -->
                <form action="{{ route('logout') }}" method="POST" class="contents" data-confirm="Keluar dari akun Guru Pembimbing?" data-confirm-ok="Ya, keluar">
                    @csrf
                    <button type="submit" title="Keluar" class="flex items-center justify-center w-8 h-8 rounded-full text-white/75 hover:bg-white/15 hover:text-rose-400 transition-colors cursor-pointer">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <path d="m16 17 5-5-5-5M21 12H9"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        {{-- ========================================================== --}}
        {{-- MIDDLE FOCUS CARD: Disesuaikan per Halaman                  --}}
        {{-- ========================================================== --}}
        <div class="flex items-stretch rounded-[22px] bg-white/[0.07] border border-white/10 backdrop-blur-xs">
            @if($isDashboard)
                <!-- Left: Live Clock & Date -->
                <div class="flex-1 min-w-0 px-4 py-3">
                    <span id="live-date-header" class="text-[12px] leading-tight text-[#A9C2B9] font-medium block truncate">
                        {{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}
                    </span>
                    <div class="flex items-baseline gap-1.5 mt-1">
                        <span id="live-clock-digits" class="font-space font-semibold text-[38px] sm:text-[44px] leading-none text-white tracking-tight tabular-nums">
                            {{ \Carbon\Carbon::now()->format('H.i') }}
                        </span>
                        <span class="text-[11px] font-bold text-[#C6F26B]">WIB</span>
                    </div>
                </div>

                <!-- Vertical Divider -->
                <div class="w-px my-3 bg-white/10"></div>

                <!-- Right: Teacher KPI / Kehadiran Siswa Hari Ini -->
                <div class="shrink-0 px-4 py-3 flex flex-col justify-center gap-0.5">
                    <span class="text-[11px] text-[#A9C2B9] font-bold">Hadir Hari Ini</span>
                    <span class="text-[15px] font-bold text-white leading-tight tabular-nums">
                        {{ $todayPresentCount }}<span class="text-white/50 text-xs">/{{ $expectedToday }}</span> Siswa
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-[11.5px] font-bold text-[#C6F26B]">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#C6F26B] animate-pulse"></span>
                        {{ $attendancePct }}% Hadir
                    </span>
                </div>

            @elseif($isMonitoring)
                <!-- Left: Date Picker Form & Status Pulse -->
                <div class="flex-1 min-w-0 px-4 py-3 flex flex-col justify-center gap-1.5">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-[#C6F26B] animate-pulse"></span>
                        <span class="text-[11px] font-bold text-[#C6F26B] uppercase tracking-wider">Live Presensi</span>
                    </div>

                    <!-- Quick Date Filter Form with Native Date Picker -->
                    <form method="GET" action="{{ route('guru.monitoring') }}" id="mobileDateFilterForm" class="relative inline-flex items-center">
                        @if(request('status'))
                            <input type="hidden" name="status" value="{{ request('status') }}">
                        @endif
                        <label class="flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-white/[0.12] hover:bg-white/[0.2] border border-white/20 text-white text-[11px] font-bold transition-all cursor-pointer">
                            <svg class="w-3.5 h-3.5 text-[#C6F26B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>{{ $currentDate ? \Carbon\Carbon::parse($currentDate)->format('d M Y') : 'Semua Tanggal' }}</span>
                            <input type="date" name="date" value="{{ $currentDate }}" onchange="document.getElementById('mobileDateFilterForm').submit()" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                        </label>
                        @if($currentDate)
                            <a href="{{ route('guru.monitoring', array_filter(['status' => request('status')])) }}" 
                               class="w-6 h-6 rounded-lg bg-white/10 hover:bg-white/20 text-[#A9C2B9] hover:text-white flex items-center justify-center transition-colors ml-1" 
                               title="Hapus filter tanggal">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            </a>
                        @endif
                    </form>
                </div>

                <!-- Vertical Divider -->
                <div class="w-px my-3 bg-white/10"></div>

                <!-- Right: Monitoring Presence Stats -->
                <div class="shrink-0 px-4 py-3 flex flex-col justify-center gap-0.5">
                    <span class="text-[11px] text-[#A9C2B9] font-bold">Hadir Tepat</span>
                    <span class="text-[15px] font-bold text-white leading-tight tabular-nums">
                        {{ $hadirCount }}<span class="text-white/50 text-xs">/{{ $totalLog }}</span> Siswa
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-[11.5px] font-bold text-[#C6F26B]">
                        {{ $totalLog > 0 ? (int) round($hadirCount / $totalLog * 100) : 0 }}% Disiplin
                    </span>
                </div>

            @elseif($isJournals)
                <!-- Left: Antrean Validasi Guru -->
                <div class="flex-1 min-w-0 px-4 py-3">
                    <span class="text-[11.5px] leading-tight text-[#A9C2B9] font-medium block">
                        Antrean Butuh Validasi
                    </span>
                    <div class="flex items-baseline gap-1.5 mt-1">
                        <span class="font-space font-semibold text-[34px] leading-none text-white tracking-tight tabular-nums">
                            {{ $pendingGuru }}
                        </span>
                        <span class="text-[11px] font-bold {{ $pendingGuru > 0 ? 'text-amber-400' : 'text-[#C6F26B]' }}">Logbook</span>
                    </div>
                </div>

                <!-- Vertical Divider -->
                <div class="w-px my-3 bg-white/10"></div>

                <!-- Right: Jurnal Disetujui -->
                <div class="shrink-0 px-4 py-3 flex flex-col justify-center gap-0.5">
                    <span class="text-[11px] text-[#A9C2B9] font-bold">Telah Disetujui</span>
                    <span class="text-[15px] font-bold text-white leading-tight tabular-nums">
                        {{ $approvedCount }}<span class="text-white/50 text-xs">/{{ $totalJournals }}</span> Jurnal
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-[11.5px] font-bold text-[#C6F26B]">
                        {{ $totalJournals > 0 ? (int) round($approvedCount / max(1, $totalJournals) * 100) : 0 }}% Lolos
                    </span>
                </div>

            @elseif($isRequests)
                <!-- Left: Menunggu Review Izin/Sakit -->
                <div class="flex-1 min-w-0 px-4 py-3">
                    <span class="text-[11.5px] leading-tight text-[#A9C2B9] font-medium block">
                        Menunggu Keputusan
                    </span>
                    <div class="flex items-baseline gap-1.5 mt-1">
                        <span class="font-space font-semibold text-[34px] leading-none text-white tracking-tight tabular-nums">
                            {{ $pendingCount }}
                        </span>
                        <span class="text-[11px] font-bold {{ $pendingCount > 0 ? 'text-amber-400' : 'text-[#C6F26B]' }}">Permohonan</span>
                    </div>
                </div>

                <!-- Vertical Divider -->
                <div class="w-px my-3 bg-white/10"></div>

                <!-- Right: Disetujui -->
                <div class="shrink-0 px-4 py-3 flex flex-col justify-center gap-0.5">
                    <span class="text-[11px] text-[#A9C2B9] font-bold">Telah Disetujui</span>
                    <span class="text-[15px] font-bold text-white leading-tight tabular-nums">
                        {{ $approvedCount }}<span class="text-white/50 text-xs">/{{ $totalRequests }}</span> Data
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-[11.5px] font-bold text-emerald-400">
                        Presensi Otomatis
                    </span>
                </div>

            @else
                <!-- Generic Left: Clock & Date -->
                <div class="flex-1 min-w-0 px-4 py-3">
                    <span class="text-[12px] leading-tight text-[#A9C2B9] font-medium block truncate">
                        {{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}
                    </span>
                    <div class="flex items-baseline gap-1.5 mt-1">
                        <span id="live-clock-digits" class="font-space font-semibold text-[36px] leading-none text-white tracking-tight tabular-nums">
                            {{ \Carbon\Carbon::now()->format('H.i') }}
                        </span>
                        <span class="text-[11px] font-bold text-[#C6F26B]">WIB</span>
                    </div>
                </div>

                <!-- Vertical Divider -->
                <div class="w-px my-3 bg-white/10"></div>

                <!-- Generic Right: Guided Student Info -->
                <div class="shrink-0 px-4 py-3 flex flex-col justify-center gap-0.5">
                    <span class="text-[11px] text-[#A9C2B9] font-bold">Siswa Binaan</span>
                    <span class="text-[15px] font-bold text-white leading-tight tabular-nums">
                        {{ $totalStudents }} Siswa
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-[11.5px] font-bold text-[#C6F26B]">
                        {{ $totalCompanies }} DU/DI Mitra
                    </span>
                </div>
            @endif
        </div>

        {{-- ========================================================== --}}
        {{-- BOTTOM STRIP: Disesuaikan per Halaman                      --}}
        {{-- ========================================================== --}}
        @if($isDashboard)
            <!-- Mini Summary Strip inside Header (Pending Verification Counters) -->
            <div class="grid grid-cols-2 gap-2 pt-0.5">
                <a href="{{ route('guru.journals') }}" class="flex items-center justify-between p-2.5 rounded-xl bg-white/[0.06] hover:bg-white/10 border border-white/10 transition-colors">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="w-2 h-2 rounded-full {{ $pendingJournals > 0 ? 'bg-[#C6F26B]' : 'bg-white/30' }}"></span>
                        <span class="text-[11px] text-[#A9C2B9] font-medium truncate">Jurnal Menunggu</span>
                    </div>
                    <span class="font-space font-bold text-xs text-[#C6F26B] shrink-0">{{ $pendingJournals }}</span>
                </a>

                <a href="{{ route('guru.requests') }}" class="flex items-center justify-between p-2.5 rounded-xl bg-white/[0.06] hover:bg-white/10 border border-white/10 transition-colors">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="w-2 h-2 rounded-full {{ $pendingRequests > 0 ? 'bg-amber-400' : 'bg-white/30' }}"></span>
                        <span class="text-[11px] text-[#A9C2B9] font-medium truncate">Izin / Sakit</span>
                    </div>
                    <span class="font-space font-bold text-xs text-amber-300 shrink-0">{{ $pendingRequests }}</span>
                </a>
            </div>

            <!-- Slim Period Progress (Academic Timeline) -->
            @if($periodProgress)
                <div class="space-y-1.5 pt-0.5">
                    <div class="flex items-center justify-between text-[10.5px] text-[#A9C2B9] font-medium">
                        <span class="text-white font-semibold truncate">{{ $periodProgress['name'] }}</span>
                        <span>Sisa <strong class="text-[#C6F26B] font-space">{{ $periodProgress['remaining'] }}</strong> hari</span>
                    </div>
                    <div class="h-1.5 rounded-full bg-white/10 overflow-hidden">
                        <div class="h-full rounded-full bg-[#C6F26B]" style="width: {{ $periodProgress['percent'] }}%"></div>
                    </div>
                </div>
            @endif

        @elseif($isMonitoring)
            <!-- Monitoring 4 KPI Quick Filters in Header Strip -->
            <div class="grid grid-cols-4 gap-1.5 pt-0.5">
                <a href="{{ route('guru.monitoring', array_filter(['date' => $currentDate])) }}" 
                   class="flex flex-col items-center justify-center py-2 px-1 rounded-xl transition-all {{ !$currentStatus ? 'bg-[#C6F26B] text-[#0B2B25] shadow-xs' : 'bg-white/[0.08] hover:bg-white/[0.14] text-white border border-white/10' }}">
                    <span class="font-space text-sm font-bold leading-none tabular-nums">{{ $totalLog }}</span>
                    <span class="text-[9px] uppercase tracking-wider font-extrabold mt-1 {{ !$currentStatus ? 'text-[#0B2B25]' : 'text-[#A9C2B9]' }}">Total</span>
                </a>

                <a href="{{ route('guru.monitoring', array_filter(['date' => $currentDate, 'status' => 'hadir'])) }}" 
                   class="flex flex-col items-center justify-center py-2 px-1 rounded-xl transition-all {{ $currentStatus === 'hadir' ? 'bg-[#C6F26B] text-[#0B2B25] shadow-xs' : 'bg-white/[0.08] hover:bg-white/[0.14] text-white border border-white/10' }}">
                    <span class="font-space text-sm font-bold leading-none tabular-nums {{ $currentStatus === 'hadir' ? 'text-[#0B2B25]' : 'text-emerald-400' }}">{{ $hadirCount }}</span>
                    <span class="text-[9px] uppercase tracking-wider font-extrabold mt-1 {{ $currentStatus === 'hadir' ? 'text-[#0B2B25]' : 'text-[#A9C2B9]' }}">Hadir</span>
                </a>

                <a href="{{ route('guru.monitoring', array_filter(['date' => $currentDate, 'status' => 'terlambat'])) }}" 
                   class="flex flex-col items-center justify-center py-2 px-1 rounded-xl transition-all {{ $currentStatus === 'terlambat' ? 'bg-[#C6F26B] text-[#0B2B25] shadow-xs' : 'bg-white/[0.08] hover:bg-white/[0.14] text-white border border-white/10' }}">
                    <span class="font-space text-sm font-bold leading-none tabular-nums {{ $currentStatus === 'terlambat' ? 'text-[#0B2B25]' : 'text-amber-300' }}">{{ $lateCount }}</span>
                    <span class="text-[9px] uppercase tracking-wider font-extrabold mt-1 {{ $currentStatus === 'terlambat' ? 'text-[#0B2B25]' : 'text-[#A9C2B9]' }}">Telat</span>
                </a>

                <a href="{{ route('guru.monitoring', array_filter(['date' => $currentDate, 'status' => 'izin'])) }}" 
                   class="flex flex-col items-center justify-center py-2 px-1 rounded-xl transition-all {{ in_array($currentStatus, ['izin', 'sakit', 'dinas_luar']) ? 'bg-[#C6F26B] text-[#0B2B25] shadow-xs' : 'bg-white/[0.08] hover:bg-white/[0.14] text-white border border-white/10' }}">
                    <span class="font-space text-sm font-bold leading-none tabular-nums {{ in_array($currentStatus, ['izin', 'sakit', 'dinas_luar']) ? 'text-[#0B2B25]' : 'text-sky-300' }}">{{ $leaveCount }}</span>
                    <span class="text-[9px] uppercase tracking-wider font-extrabold mt-1 {{ in_array($currentStatus, ['izin', 'sakit', 'dinas_luar']) ? 'text-[#0B2B25]' : 'text-[#A9C2B9]' }}">Izin</span>
                </a>
            </div>

        @elseif($isJournals)
            <!-- Journals 3 Quick Status Pills in Header Strip -->
            <div class="grid grid-cols-3 gap-2 pt-0.5">
                <button type="button" onclick="switchMobileJournalView('pending')" class="flex items-center justify-between p-2.5 rounded-xl border transition-all cursor-pointer {{ $pendingGuru > 0 ? 'bg-amber-400 text-amber-950 border-amber-300 shadow-xs' : 'bg-white/[0.06] hover:bg-white/10 border-white/10 text-white' }}">
                    <div class="flex items-center gap-1.5 min-w-0">
                        <span class="w-2 h-2 rounded-full {{ $pendingGuru > 0 ? 'bg-amber-950 animate-ping' : 'bg-white/30' }}"></span>
                        <span class="text-[11px] font-bold truncate">Butuh Validasi</span>
                    </div>
                    <span class="font-space font-bold text-xs shrink-0">{{ $pendingGuru }}</span>
                </button>

                <div class="flex items-center justify-between p-2.5 rounded-xl bg-white/[0.06] border border-white/10 text-white">
                    <span class="text-[11px] text-[#A9C2B9] font-medium truncate">Disetujui</span>
                    <span class="font-space font-bold text-xs text-emerald-400 shrink-0">{{ $approvedCount }}</span>
                </div>

                <div class="flex items-center justify-between p-2.5 rounded-xl bg-white/[0.06] border border-white/10 text-white">
                    <span class="text-[11px] text-[#A9C2B9] font-medium truncate">Revisi</span>
                    <span class="font-space font-bold text-xs text-rose-300 shrink-0">{{ $revisionCount }}</span>
                </div>
            </div>

        @elseif($isRequests)
            <!-- Requests 4 Filter Pills in Header Strip -->
            <div class="grid grid-cols-4 gap-1.5 pt-0.5">
                <button type="button" onclick="filterMobileRequests('all')" id="m-kpi-all" 
                        class="m-kpi-filter flex flex-col items-center justify-center py-2 px-1 rounded-xl transition-all bg-[#C6F26B] text-[#0B2B25] shadow-xs cursor-pointer">
                    <span class="font-space text-sm font-bold leading-none tabular-nums">{{ $totalRequests }}</span>
                    <span class="text-[9px] uppercase tracking-wider font-extrabold mt-1 text-[#0B2B25]">Semua</span>
                </button>

                <button type="button" onclick="filterMobileRequests('pending')" id="m-kpi-pending" 
                        class="m-kpi-filter flex flex-col items-center justify-center py-2 px-1 rounded-xl transition-all cursor-pointer border {{ $pendingCount > 0 ? 'bg-amber-400 text-amber-950 border-amber-300 shadow-xs' : 'bg-white/[0.08] hover:bg-white/[0.14] text-white border-white/10' }}">
                    <span class="font-space text-sm font-bold leading-none tabular-nums">{{ $pendingCount }}</span>
                    <span class="text-[9px] uppercase tracking-wider font-extrabold mt-1 {{ $pendingCount > 0 ? 'text-amber-950' : 'text-[#A9C2B9]' }}">Menunggu</span>
                </button>

                <button type="button" onclick="filterMobileRequests('approved')" id="m-kpi-approved" 
                        class="m-kpi-filter flex flex-col items-center justify-center py-2 px-1 rounded-xl bg-white/[0.08] hover:bg-white/[0.14] text-white border border-white/10 transition-all cursor-pointer">
                    <span class="font-space text-sm font-bold leading-none tabular-nums text-emerald-400">{{ $approvedCount }}</span>
                    <span class="text-[9px] uppercase tracking-wider font-extrabold mt-1 text-[#A9C2B9]">Disetujui</span>
                </button>

                <button type="button" onclick="filterMobileRequests('rejected')" id="m-kpi-rejected" 
                        class="m-kpi-filter flex flex-col items-center justify-center py-2 px-1 rounded-xl bg-white/[0.08] hover:bg-white/[0.14] text-white border border-white/10 transition-all cursor-pointer">
                    <span class="font-space text-sm font-bold leading-none tabular-nums text-rose-400">{{ $rejectedCount }}</span>
                    <span class="text-[9px] uppercase tracking-wider font-extrabold mt-1 text-[#A9C2B9]">Ditolak</span>
                </button>
            </div>
        @endif

    </div>
</header>

@if($isDashboard || (!$isMonitoring && !$isJournals && !$isRequests))
<!-- Real-time Live Clock Updater for Teacher Header -->
<script>
    (function() {
        function updateTeacherLiveClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const el = document.getElementById('live-clock-digits');
            if (el) el.textContent = `${hours}.${minutes}`;
        }
        updateTeacherLiveClock();
        setInterval(updateTeacherLiveClock, 1000);
    })();
</script>
@endif
