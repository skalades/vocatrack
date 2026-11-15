@props(['active' => null])

@php
    $currentRoute = request()->route() ? request()->route()->getName() : '';
    // Sembunyikan bottom dock saat siswa berada di layar kamera presensi aktif
    if (str_starts_with($currentRoute, 'student.attendance') && empty($todayAttendance)) {
        return;
    }
    $isHome = $active === 'home' || $currentRoute === 'student.home';
    $isJournal = $active === 'journal' || str_starts_with($currentRoute, 'student.journals');
    $isAttendance = $active === 'attendance' || str_starts_with($currentRoute, 'student.attendance');
    $isProfile = $active === 'profile' || str_starts_with($currentRoute, 'student.profile');
@endphp

<!-- Modern Floating Bottom Dock (Fixed to mobile container width) -->
<nav aria-label="Navigasi Siswa" class="fixed bottom-4 left-1/2 -translate-x-1/2 w-[calc(100%-32px)] max-w-[340px] h-[68px] rounded-[34px] bg-[#0B2B25] flex items-center justify-between px-3 shadow-[0_12px_30px_rgba(11,43,37,0.35)] z-40 border border-white/10 backdrop-blur-md">
    
    <!-- Beranda -->
    <a href="{{ route('student.home') }}" 
       data-ripple class="flex items-center gap-2 h-12 px-4 rounded-[24px] text-[13.5px] font-extrabold transition-all duration-200 {{ $isHome ? 'bg-[#C6F26B] text-[#0B2B25] shadow-xs' : 'text-[#B7CEC5] hover:text-white' }}"
       @if($isHome) aria-current="page" @endif>
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>
        </svg>
        <span>Beranda</span>
    </a>

    <!-- SOS Center Trigger Button -->
    <button type="button" 
            onclick="window.openSosModal && window.openSosModal()" 
            aria-label="Bantuan Darurat SOS" 
            class="group relative flex items-center justify-center w-[52px] h-[52px] rounded-full bg-[#C8261A] text-white font-space font-bold text-[15px] tracking-wider shadow-[0_0_0_4px_rgba(200,38,26,0.3)] hover:shadow-[0_0_0_6px_rgba(200,38,26,0.4)] active:scale-90 transition-all cursor-pointer">
        <span class="absolute inset-0 rounded-full animate-ping bg-[#C8261A] opacity-25 pointer-events-none"></span>
        <span class="relative z-10">SOS</span>
    </button>

    <!-- Jurnal -->
    <a href="{{ route('student.journals') }}" 
       data-ripple class="flex items-center gap-2 h-12 px-4 rounded-[24px] text-[13.5px] font-extrabold transition-all duration-200 {{ $isJournal ? 'bg-[#C6F26B] text-[#0B2B25] shadow-xs' : 'text-[#B7CEC5] hover:text-white' }}"
       @if($isJournal) aria-current="page" @endif>
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H19a1 1 0 0 1 1 1v18a1 1 0 0 1-1 1H6.5a2.5 2.5 0 0 1-2.5-2.5z"/>
            <path d="M8 7h8M8 11h8M8 15h5"/>
        </svg>
        <span>Jurnal</span>
    </a>
</nav>
