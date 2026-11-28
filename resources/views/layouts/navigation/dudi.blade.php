<div class="text-[10px] font-extrabold text-[#A9C2B9] uppercase tracking-widest px-3 pt-2 pb-1.5 flex items-center justify-between">
    <span>Mentor Industri DU/DI</span>
    <span class="w-1.5 h-1.5 rounded-full bg-[#C6F26B]"></span>
</div>
<a href="{{ route('dudi.dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('dudi.dashboard') ? $activeClass : $inactiveClass }}">
    <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
    <span>Dashboard Industri</span>
</a>
<a href="{{ route('dudi.attendances') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('dudi.attendances*') ? $activeClass : $inactiveClass }}">
    <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
    <span>Validasi Absensi Harian</span>
</a>
<a href="{{ route('dudi.requests') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('dudi.requests*') ? $activeClass : $inactiveClass }}">
    <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    <span>Dinas Luar & WFH</span>
</a>
<a href="{{ route('dudi.journals') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('dudi.journals*') ? $activeClass : $inactiveClass }}">
    <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    <span>Approval Jurnal (Tingkat 1)</span>
</a>
<a href="{{ route('dudi.assessments') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('dudi.assessments*') ? $activeClass : $inactiveClass }}">
    <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
    <span>Penilaian Kompetensi</span>
</a>
<a href="{{ route('dudi.announcements') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('dudi.announcements*') ? $activeClass : $inactiveClass }}">
    <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
    <span>Pengumuman</span>
</a>
