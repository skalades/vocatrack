@php
    $sbTeacher = auth()->user()?->teacher;
    $sbSpklIds = $sbTeacher ? $sbTeacher->studentPkls()->pluck('id') : collect();
    $sbPendingResets = $sbTeacher ? \App\Models\StudentPasswordResetRequest::where('teacher_id', $sbTeacher->id)->where('status', 'pending')->count() : 0;
    $sbPendingJournals = $sbSpklIds->isNotEmpty() ? \App\Models\Journal::whereIn('student_pkl_id', $sbSpklIds)->where('status', 'review_guru')->count() : 0;
    $sbPendingRequests = $sbSpklIds->isNotEmpty() ? \App\Models\AttendanceRequest::whereIn('student_pkl_id', $sbSpklIds)->where('status', 'pending')->count() : 0;
@endphp

@if(auth()->user()->isKajur() && request()->is('kajur*'))
    <!-- Context: Kepala Program Keahlian (Kajur) -->
    <div class="text-[10px] font-extrabold text-[#C6F26B] uppercase tracking-widest px-3 pt-2 pb-1.5 flex items-center justify-between">
        <span>Manajemen Jurusan ({{ auth()->user()->headedMajor()?->code }})</span>
        <span class="w-1.5 h-1.5 rounded-full bg-[#C6F26B]"></span>
    </div>
    <a href="{{ route('kajur.dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('kajur.dashboard') ? $activeClass : $inactiveClass }}">
        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        <span>Dashboard Jurusan</span>
    </a>
    <a href="{{ route('kajur.companies') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('kajur.companies*') ? $activeClass : $inactiveClass }}">
        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        <span>Mitra Perusahaan (DU/DI)</span>
    </a>
    <a href="{{ route('kajur.students') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('kajur.students*') ? $activeClass : $inactiveClass }}">
        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        <span>Siswa & Plotting PKL</span>
    </a>
    <a href="{{ route('kajur.competencies') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('kajur.competencies*') ? $activeClass : $inactiveClass }}">
        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
        <span>Capaian Pembelajaran (CP)</span>
    </a>
    <a href="{{ route('kajur.attendance') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('kajur.attendance*') ? $activeClass : $inactiveClass }}">
        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        <span>Monitoring Presensi</span>
    </a>
    <a href="{{ route('kajur.journals') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('kajur.journals*') ? $activeClass : $inactiveClass }}">
        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Monitoring Jurnal</span>
    </a>
    <a href="{{ route('kajur.supervisions') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('kajur.supervisions*') ? $activeClass : $inactiveClass }}">
        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        <span>Supervisi Guru Pembimbing</span>
    </a>
    <a href="{{ route('kajur.grades') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('kajur.grades*') ? $activeClass : $inactiveClass }}">
        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Rekapitulasi Nilai</span>
    </a>
    <div class="pt-2 mt-2 border-t border-slate-800/80">
        <a href="{{ route('guru.dashboard') }}" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-[#C6F26B] hover:text-white bg-[#0B2B25]/60 hover:bg-[#124239] border border-[#1D5A4D] transition-colors">
            <svg class="w-3.5 h-3.5 text-[#C6F26B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            <span>Buka Bimbingan Saya</span>
        </a>
    </div>
@else
    <!-- Context: Guru Pembimbing (Bimbingan Siswa) -->
    @if(auth()->user()->isKajur())
        <div class="pb-2 mb-2 border-b border-slate-800/80">
            <a href="{{ route('kajur.dashboard') }}" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-[#C6F26B] hover:text-white bg-[#0B2B25]/60 hover:bg-[#124239] border border-[#1D5A4D] transition-colors">
                <svg class="w-3.5 h-3.5 text-[#C6F26B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span>Panel Kajur ({{ auth()->user()->headedMajor()?->code }})</span>
            </a>
        </div>
    @endif
    <div class="text-[10px] font-extrabold text-[#A9C2B9] uppercase tracking-widest px-3 pt-2 pb-1.5 flex items-center justify-between">
        <span>Bimbingan PKL</span>
        <span class="w-1.5 h-1.5 rounded-full bg-[#C6F26B]"></span>
    </div>
    <a href="{{ route('guru.dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('guru.dashboard') ? $activeClass : $inactiveClass }}">
        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        <span>Dashboard Bimbingan</span>
    </a>
    <a href="{{ route('guru.announcements') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('guru.announcements*') ? $activeClass : $inactiveClass }}">
        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
        <span>Pengumuman</span>
    </a>
    <a href="{{ route('guru.monitoring') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('guru.monitoring*') ? $activeClass : $inactiveClass }}">
        <div class="flex items-center space-x-3 min-w-0">
            <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span class="truncate">Monitoring Absensi</span>
        </div>
        @if($sbPendingResets > 0)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-[#C6F26B] text-[#0B2B25] shrink-0" title="{{ $sbPendingResets }} permohonan reset sandi siswa">
                {{ $sbPendingResets }}
            </span>
        @endif
    </a>
    <a href="{{ route('guru.requests') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('guru.requests*') ? $activeClass : $inactiveClass }}">
        <div class="flex items-center space-x-3 min-w-0">
            <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>Izin & Sakit Siswa</span>
        </div>
        @if($sbPendingRequests > 0)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-[#C6F26B] text-[#0B2B25] shrink-0" title="{{ $sbPendingRequests }} permohonan izin/sakit menunggu">
                {{ $sbPendingRequests }}
            </span>
        @endif
    </a>
    <a href="{{ route('guru.journals') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('guru.journals*') ? $activeClass : $inactiveClass }}">
        <div class="flex items-center space-x-3 min-w-0">
            <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Review Jurnal (Tingkat 2)</span>
        </div>
        @if($sbPendingJournals > 0)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-[#C6F26B] text-[#0B2B25] shrink-0" title="{{ $sbPendingJournals }} jurnal menunggu review guru">
                {{ $sbPendingJournals }}
            </span>
        @endif
    </a>
    <a href="{{ route('guru.assessments') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('guru.assessments*') ? $activeClass : $inactiveClass }}">
        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        <span>Penilaian Sekolah</span>
    </a>
    <a href="{{ route('guru.reports') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('guru.reports*') ? $activeClass : $inactiveClass }}">
        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Ekspor Laporan PDF</span>
    </a>
    <a href="{{ route('guru.supervisions') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('guru.supervisions*') ? $activeClass : $inactiveClass }}">
        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Supervisi & Berita Acara</span>
    </a>
    <a href="{{ route('guru.incidents') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('guru.incidents*') ? $activeClass : $inactiveClass }}">
        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <span>Kendala & SOS Siswa</span>
    </a>
    <a href="{{ route('guru.parent-links') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('guru.parent-links*') ? $activeClass : $inactiveClass }}">
        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
        <span>Link Monitoring Ortu</span>
    </a>
    <a href="{{ route('guru.map') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('guru.map*') ? $activeClass : $inactiveClass }}">
        <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
        <span>Peta Sebaran PKL</span>
    </a>
@endif
