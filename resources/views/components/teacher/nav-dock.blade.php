@props([
    'pendingJournals' => null,
    'pendingRequests' => null,
    'openIncidents' => null,
])

@php
    $currentRoute = request()->route() ? request()->route()->getName() : '';
    $isDashboard = str_starts_with($currentRoute, 'guru.dashboard');
    $isMonitoring = str_starts_with($currentRoute, 'guru.monitoring');
    $isJournals = str_starts_with($currentRoute, 'guru.journals');
    $isRequests = str_starts_with($currentRoute, 'guru.requests');
    $isMore = str_starts_with($currentRoute, 'guru.supervisions') 
        || str_starts_with($currentRoute, 'guru.assessments') 
        || str_starts_with($currentRoute, 'guru.reports')
        || str_starts_with($currentRoute, 'guru.announcements')
        || str_starts_with($currentRoute, 'guru.incidents')
        || str_starts_with($currentRoute, 'guru.parent-links')
        || str_starts_with($currentRoute, 'guru.map');

    $teacher = auth()->user()?->teacher;
    if ($teacher && ($pendingJournals === null || $pendingRequests === null || $openIncidents === null)) {
        $spklIds = $teacher->studentPkls()->pluck('id');
        if ($spklIds->isNotEmpty()) {
            if ($pendingJournals === null) {
                $pendingJournals = \App\Models\Journal::whereIn('student_pkl_id', $spklIds)->where('status', 'review_guru')->count();
            }
            if ($pendingRequests === null) {
                $pendingRequests = \App\Models\AttendanceRequest::whereIn('student_pkl_id', $spklIds)->where('status', 'pending')->count();
            }
            if ($openIncidents === null) {
                $openIncidents = \App\Models\IncidentReport::whereIn('student_pkl_id', $spklIds)->where('status', 'open')->count();
            }
        }
    }
    $pendingJournals = (int) ($pendingJournals ?? 0);
    $pendingRequests = (int) ($pendingRequests ?? 0);
    $openIncidents = (int) ($openIncidents ?? 0);
@endphp

<!-- Responsive Bottom Navigation Dock (Visible on Mobile Screens only, Hidden on Desktop/Laptop) -->
<nav aria-label="Navigasi Cepat Guru" class="fixed bottom-3 inset-x-3 sm:bottom-4 sm:inset-x-auto sm:left-1/2 sm:-translate-x-1/2 sm:w-full sm:max-w-md h-[64px] rounded-[32px] bg-[#0B2B25]/95 backdrop-blur-xl flex items-center justify-between px-2.5 shadow-[0_12px_36px_rgba(11,43,37,0.45)] z-40 border border-white/10 lg:hidden transition-all duration-300">
    
    <!-- 1. Beranda / Dashboard -->
    <a href="{{ route('guru.dashboard') }}" 
       data-ripple
       class="flex-1 flex flex-col items-center justify-center h-12 rounded-[24px] text-[10.5px] font-bold transition-all duration-200 active:scale-95 {{ $isDashboard ? 'bg-[#C6F26B] text-[#0B2B25] shadow-xs font-extrabold' : 'text-[#A9C2B9] hover:text-white' }}"
       @if($isDashboard) aria-current="page" @endif
       title="Dashboard Bimbingan">
        <svg class="w-5 h-5 {{ $isDashboard ? 'stroke-[2.4]' : 'stroke-[1.8]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        <span class="mt-0.5 tracking-tight">Beranda</span>
    </a>

    <!-- 2. Monitoring Absensi -->
    <a href="{{ route('guru.monitoring') }}" 
       data-ripple
       class="flex-1 flex flex-col items-center justify-center h-12 rounded-[24px] text-[10.5px] font-bold transition-all duration-200 active:scale-95 {{ $isMonitoring ? 'bg-[#C6F26B] text-[#0B2B25] shadow-xs font-extrabold' : 'text-[#A9C2B9] hover:text-white' }}"
       @if($isMonitoring) aria-current="page" @endif
       title="Monitoring Presensi">
        <svg class="w-5 h-5 {{ $isMonitoring ? 'stroke-[2.4]' : 'stroke-[1.8]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <span class="mt-0.5 tracking-tight">Presensi</span>
    </a>

    <!-- 3. Review Jurnal (Tingkat 2) -->
    <a href="{{ route('guru.journals') }}" 
       data-ripple
       class="flex-1 flex flex-col items-center justify-center h-12 rounded-[24px] text-[10.5px] font-bold relative transition-all duration-200 active:scale-95 {{ $isJournals ? 'bg-[#C6F26B] text-[#0B2B25] shadow-xs font-extrabold' : 'text-[#A9C2B9] hover:text-white' }}"
       @if($isJournals) aria-current="page" @endif
       title="Review Jurnal Siswa">
        <div class="relative">
            <svg class="w-5 h-5 {{ $isJournals ? 'stroke-[2.4]' : 'stroke-[1.8]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            @if($pendingJournals > 0)
                <span class="absolute -top-1 -right-2 min-w-[16px] h-4 px-1 rounded-full bg-rose-500 text-white text-[9px] font-black flex items-center justify-center border border-[#0B2B25] animate-pulse">
                    {{ $pendingJournals > 99 ? '99+' : $pendingJournals }}
                </span>
            @endif
        </div>
        <span class="mt-0.5 tracking-tight">Jurnal</span>
    </a>

    <!-- 4. Izin & Sakit -->
    <a href="{{ route('guru.requests') }}" 
       data-ripple
       class="flex-1 flex flex-col items-center justify-center h-12 rounded-[24px] text-[10.5px] font-bold relative transition-all duration-200 active:scale-95 {{ $isRequests ? 'bg-[#C6F26B] text-[#0B2B25] shadow-xs font-extrabold' : 'text-[#A9C2B9] hover:text-white' }}"
       @if($isRequests) aria-current="page" @endif
       title="Permohonan Izin & Sakit">
        <div class="relative">
            <svg class="w-5 h-5 {{ $isRequests ? 'stroke-[2.4]' : 'stroke-[1.8]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            @if($pendingRequests > 0)
                <span class="absolute -top-1 -right-2 min-w-[16px] h-4 px-1 rounded-full bg-amber-400 text-amber-950 text-[9px] font-black flex items-center justify-center border border-[#0B2B25]">
                    {{ $pendingRequests > 99 ? '99+' : $pendingRequests }}
                </span>
            @endif
        </div>
        <span class="mt-0.5 tracking-tight">Izin/Sakit</span>
    </a>

    <!-- 5. Menu / Lebih Banyak (Membuka Sidebar Drawer Mobile) -->
    <button type="button" 
            onclick="toggleMobileSidebar()" 
            class="flex-1 flex flex-col items-center justify-center h-12 rounded-[24px] text-[10.5px] font-bold relative transition-all duration-200 active:scale-95 cursor-pointer {{ $isMore ? 'bg-[#C6F26B] text-[#0B2B25] shadow-xs font-extrabold' : 'text-[#A9C2B9] hover:text-white' }}"
            title="Menu & Fitur Lainnya">
        <div class="relative">
            <svg class="w-5 h-5 {{ $isMore ? 'stroke-[2.4]' : 'stroke-[1.8]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            @if($openIncidents > 0)
                <span class="absolute -top-1 -right-1.5 w-2.5 h-2.5 rounded-full bg-rose-500 border border-[#0B2B25] animate-ping"></span>
                <span class="absolute -top-1 -right-1.5 w-2.5 h-2.5 rounded-full bg-rose-500 border border-[#0B2B25]"></span>
            @endif
        </div>
        <span class="mt-0.5 tracking-tight">Menu</span>
    </button>

</nav>
