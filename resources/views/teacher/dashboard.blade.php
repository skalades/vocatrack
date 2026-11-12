@extends('layouts.app')

@section('title', 'Dashboard Guru Pembimbing — VokaTrack')
@section('hide_header', true)

@section('content')
<div class="w-full space-y-6">

    <h1 class="sr-only">Dashboard Guru Pembimbing</h1>

    <!-- Hero Welcome Banner -->
    <div class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-7 text-white shadow-xl shadow-slate-950/20 border border-slate-800">
        <div class="absolute -right-10 -bottom-10 w-60 h-60 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>

        <div class="relative space-y-4">
            <!-- Top Banner Meta: Badge + School + Live Date & Time -->
            <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-white/10">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-500/20 text-indigo-300 border border-indigo-400/30 uppercase tracking-wider">
                        GURU PEMBIMBING
                    </span>
                    <span class="text-xs text-slate-500">&bull;</span>
                    <span class="text-xs font-semibold text-slate-300 truncate">{{ auth()->user()->school->name ?? 'SMK Terpadu' }}</span>
                </div>

                <!-- Live Date & Clock Widget -->
                <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 text-xs text-slate-200 shadow-inner">
                    <div class="flex items-center gap-1.5 font-semibold text-slate-200">
                        <svg class="w-3.5 h-3.5 text-indigo-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span id="banner-live-date">{{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}</span>
                    </div>
                    <span class="text-white/30">&bull;</span>
                    <div class="flex items-center gap-1.5 font-mono font-bold text-amber-300">
                        <svg class="w-3.5 h-3.5 text-amber-300 shrink-0 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span id="banner-live-clock">{{ \Carbon\Carbon::now()->format('H:i:s') }} WIB</span>
                    </div>
                </div>
            </div>

            <!-- Main Greeting & Action Buttons -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
                <div class="space-y-1.5 min-w-0">
                    <h2 class="text-xl sm:text-2xl font-black tracking-tight text-white">
                        Selamat Datang, {{ auth()->user()->name }}
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-300 font-medium max-w-2xl leading-relaxed">
                        Pantau real-time absensi harian, tinjau jurnal kegiatan harian, dan evaluasi capaian kompetensi <strong class="text-white">{{ $totalStudents }} siswa binaan</strong> Anda di mitra industri.
                    </p>
                </div>

                <!-- Header Quick Actions -->
                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    <x-button type="button" variant="primary" size="sm" onclick="window.location.href='{{ route('guru.monitoring') }}'">
                        <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <span>Monitoring Absensi</span>
                    </x-button>
                    <x-button type="button" variant="secondary" size="sm" onclick="window.location.href='{{ route('guru.announcements') }}'">
                        <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        <span>Pengumuman</span>
                    </x-button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <x-stat-card 
            title="Siswa Binaan" 
            :value="$totalStudents" 
            :subtitle="$totalCompanies . ' Mitra Industri DU/DI'"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Presensi Masuk Hari Ini" 
            :value="$todayPresentCount . ' / ' . $totalStudents" 
            :subtitle="$todayLateCount > 0 ? $todayLateCount . ' Siswa Terlambat' : ($todayPresentCount > 0 ? 'Semua Tepat Waktu' : 'Belum Ada yang Absen')"
            :color="$todayPresentCount > 0 ? 'emerald' : 'indigo'"
            :badge="$totalStudents > 0 && $todayPresentCount === $totalStudents ? '100% Hadir' : null"
            :badgeVariant="'success'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Permohonan Izin / Sakit" 
            :value="$pendingRequests" 
            subtitle="Menunggu verifikasi guru"
            :color="$pendingRequests > 0 ? 'amber' : 'indigo'"
            :badge="$pendingRequests > 0 ? 'Perlu Tindakan' : 'Tuntas'"
            :badgeVariant="$pendingRequests > 0 ? 'warning' : 'neutral'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Review Jurnal Tingkat 2" 
            :value="$pendingJournals" 
            subtitle="Lolos verifikasi industri"
            :color="$pendingJournals > 0 ? 'blue' : 'indigo'"
            :badge="$pendingJournals > 0 ? 'Review Guru' : 'Tuntas'"
            :badgeVariant="$pendingJournals > 0 ? 'info' : 'neutral'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

    </div>

    <!-- Main Asymmetric Dashboard Grid -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
        
        <!-- Left Column: Attendance Monitor & Mentored Students (8 Cols) -->
        <div class="xl:col-span-8 space-y-6">

            <!-- Real-time Attendance Tracker for Today -->
            <x-card 
                title="Presensi Siswa Hari Ini" 
                :subtitle="\Carbon\Carbon::today()->isoFormat('dddd, D MMMM Y')"
                noPadding
            >
                <x-slot:headerActions>
                    <a href="{{ route('guru.monitoring') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-1 transition-colors">
                        <span>Semua Log Presensi</span>
                        <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </x-slot:headerActions>

                @if($studentPkls->isEmpty())
                    <div class="p-8 text-center">
                        <x-empty-state 
                            title="Belum Ada Siswa Binaan" 
                            description="Anda belum memiliki siswa yang diplot untuk dibimbing."
                        />
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($studentPkls as $sp)
                            @php
                                $att = $todayAttendances->get($sp->id);
                            @endphp
                            <div class="p-4 sm:p-4.5 flex items-center justify-between gap-4 hover:bg-slate-50/70 transition-colors">
                                <div class="flex items-center space-x-3.5 min-w-0">
                                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-50 to-indigo-100/70 border border-indigo-200/80 text-indigo-700 font-extrabold text-sm flex items-center justify-center shrink-0 shadow-2xs">
                                        {{ substr($sp->student->user->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-900 text-xs sm:text-sm truncate">{{ $sp->student->user->name }}</span>
                                            <span class="text-[10px] text-slate-400 font-medium hidden sm:inline">&bull; {{ $sp->student->class->name }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 text-[11px] text-slate-500 font-medium truncate mt-0.5">
                                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                            <span class="truncate font-semibold text-slate-600">{{ $sp->company->name }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Attendance Status Badge & Check-in Photo -->
                                <div class="flex items-center gap-3 shrink-0">
                                    @if($att)
                                        @if($att->photo_in_path)
                                            <div class="relative group/selfie cursor-zoom-in" onclick="openImageLightbox('{{ asset('storage/' . $att->photo_in_path) }}', 'Check-in: {{ $sp->student->user->name }}')" title="Lihat Foto Selfie Check-in">
                                                <img src="{{ asset('storage/' . $att->photo_in_path) }}" alt="Check-in Photo" class="w-8 h-8 rounded-xl object-cover border border-slate-200 shadow-2xs group-hover/selfie:scale-110 transition-transform">
                                                <span class="absolute -bottom-1 -right-1 w-3 h-3 bg-emerald-500 rounded-full border-2 border-white"></span>
                                            </div>
                                        @endif

                                        <div class="text-right">
                                            <x-badge :variant="$att->status_badge_variant ?? ($att->status === 'hadir' ? 'success' : ($att->status === 'terlambat' ? 'warning' : 'info'))" size="xs">
                                                {{ strtoupper(str_replace('_', ' ', $att->status)) }}
                                            </x-badge>
                                            @if($att->time_in)
                                                <span class="text-[10px] text-slate-400 block font-mono font-medium mt-0.5">
                                                    {{ \Carbon\Carbon::parse($att->time_in)->format('H:i') }} WIB
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <div class="text-right">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                                Belum Presensi
                                            </span>
                                            <span class="text-[9px] text-slate-400 block mt-0.5">Menunggu rekam</span>
                                        </div>
                                    @endif

                                    <a href="{{ route('guru.monitoring', ['date' => \Carbon\Carbon::today()->toDateString()]) }}" class="p-1.5 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors" title="Lihat Presensi">
                                        <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>

            <!-- Mentored Students Cards -->
            <x-card 
                title="Daftar Siswa Bimbingan Aktif" 
                :subtitle="$totalStudents . ' Siswa Dimonitor dan Dinilai Selama Masa PKL'"
            >
                <x-slot:headerActions>
                    <a href="{{ route('guru.reports') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-1 transition-colors">
                        <span>Ekspor Laporan PDF</span>
                        <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </x-slot:headerActions>

                @if($studentPkls->isEmpty())
                    <x-empty-state 
                        title="Belum Ada Siswa Binaan" 
                        description="Hubungi Admin Sekolah untuk melakukan plotting penugasan siswa ke bimbingan Anda."
                    />
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($studentPkls as $sp)
                            <div class="rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:shadow-md hover:border-indigo-300 transition-all p-4.5 flex flex-col justify-between group">
                                
                                <div class="space-y-3">
                                    <!-- Student Header -->
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-center space-x-3 min-w-0">
                                            <div class="w-11 h-11 rounded-2xl bg-indigo-600 text-white font-black text-sm flex items-center justify-center shrink-0 shadow-xs shadow-indigo-600/30">
                                                {{ substr($sp->student->user->name, 0, 1) }}
                                            </div>
                                            <div class="min-w-0">
                                                <h4 class="font-bold text-slate-900 text-sm truncate group-hover:text-indigo-600 transition-colors">
                                                    {{ $sp->student->user->name }}
                                                </h4>
                                                <p class="text-[11px] text-slate-500 font-medium truncate mt-0.5">
                                                    {{ $sp->student->class->name }} &bull; NISN: <span class="font-mono text-slate-600">{{ $sp->student->nisn }}</span>
                                                </p>
                                            </div>
                                        </div>
                                        <x-badge variant="success" size="xs">
                                            Aktif PKL
                                        </x-badge>
                                    </div>

                                    <!-- Company & Placement Box -->
                                    <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-100 flex items-center justify-between gap-2 text-xs">
                                        <div class="min-w-0 flex items-center space-x-2">
                                            <svg class="w-4 h-4 text-indigo-600 shrink-0 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                            <div class="min-w-0">
                                                <span class="font-bold text-slate-900 block truncate">{{ $sp->company->name }}</span>
                                                <span class="text-[10px] text-slate-400 block truncate">Pembimbing: {{ $sp->supervisor->user->name ?? 'Belum Ditentukan' }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Micro Metrics Row -->
                                    <div class="grid grid-cols-3 gap-2 text-center pt-0.5">
                                        <div class="p-2 rounded-xl bg-slate-50 border border-slate-100">
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tight block">Presensi</span>
                                            <span class="text-xs font-black text-slate-800 mt-0.5 block">{{ $sp->attendances_count }} Hari</span>
                                        </div>
                                        <div class="p-2 rounded-xl bg-slate-50 border border-slate-100">
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tight block">Jurnal</span>
                                            <span class="text-xs font-black text-indigo-700 mt-0.5 block">{{ $sp->approved_journals_count }} Disetujui</span>
                                        </div>
                                        <div class="p-2 rounded-xl bg-slate-50 border border-slate-100">
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tight block">Nilai PKL</span>
                                            <span class="text-xs font-black {{ $sp->assessment ? 'text-emerald-700' : 'text-slate-400' }} mt-0.5 block">
                                                {{ $sp->assessment ? number_format($sp->assessment->final_score, 1) : 'Belum' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Action Footer -->
                                <div class="pt-3.5 mt-3.5 border-t border-slate-100 flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <a href="{{ route('guru.monitoring') }}" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200/80 text-slate-700 font-bold text-[11px] transition-colors" title="Lihat Catatan Absensi">
                                            Absensi
                                        </a>
                                        <a href="{{ route('guru.journals') }}" class="px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-[11px] transition-colors" title="Review Jurnal">
                                            Jurnal
                                        </a>
                                        <a href="{{ route('guru.assessments') }}" class="px-2.5 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 font-bold text-[11px] transition-colors" title="Input Penilaian">
                                            Nilai
                                        </a>
                                    </div>

                                    <a href="{{ route('guru.reports.download', $sp->id) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-rose-600 font-bold text-[11px] shadow-2xs transition-colors shrink-0" title="Unduh Lembar Laporan PDF">
                                        <svg class="w-3.5 h-3.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span>PDF</span>
                                    </a>
                                </div>

                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>

        </div>

        <!-- Right Column: Action Center & Announcements Feed (4 Cols) -->
        <div class="xl:col-span-4 space-y-6">

            <!-- Tindakan Perlu Diproses (Action Center) -->
            <x-card 
                title="Tindakan Perlu Diproses" 
                subtitle="Verifikasi permohonan dan persetujuan jurnal"
                noPadding
            >
                @if($pendingRequests === 0 && $pendingJournals === 0)
                    <div class="p-6 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h4 class="text-xs font-extrabold text-slate-800">Semua Tugas Selesai</h4>
                        <p class="text-[11px] text-slate-500 font-medium mt-1 leading-relaxed">
                            Tidak ada permohonan izin atau review jurnal yang menunggu verifikasi Anda saat ini.
                        </p>
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        <!-- Pending Requests -->
                        @if($pendingRequestsList->count() > 0)
                            <div class="p-4 bg-amber-50/40">
                                <div class="flex items-center justify-between mb-2.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                        <span class="text-[11px] font-extrabold text-amber-900 uppercase tracking-wider">Permohonan Izin / Sakit ({{ $pendingRequests }})</span>
                                    </div>
                                    <a href="{{ route('guru.requests') }}" class="text-[11px] font-bold text-amber-700 hover:text-amber-800 underline">Proses</a>
                                </div>
                                <div class="space-y-2">
                                    @foreach($pendingRequestsList as $pr)
                                        <div class="p-2.5 rounded-xl bg-white border border-amber-200/80 shadow-2xs flex items-center justify-between gap-2">
                                            <div class="min-w-0">
                                                <span class="text-xs font-bold text-slate-900 block truncate">{{ $pr->studentPkl->student->user->name }}</span>
                                                <span class="text-[10px] text-slate-500 block truncate capitalize">{{ $pr->type }} &bull; {{ \Carbon\Carbon::parse($pr->start_date)->format('d M') }}</span>
                                            </div>
                                            <a href="{{ route('guru.requests') }}" class="px-2 py-1 rounded-lg bg-amber-100 text-amber-800 font-bold text-[10px] hover:bg-amber-200 transition-colors shrink-0">
                                                Verifikasi
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Pending Journals -->
                        @if($pendingJournalsList->count() > 0)
                            <div class="p-4 bg-blue-50/40">
                                <div class="flex items-center justify-between mb-2.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                        <span class="text-[11px] font-extrabold text-blue-900 uppercase tracking-wider">Review Jurnal Siswa ({{ $pendingJournals }})</span>
                                    </div>
                                    <a href="{{ route('guru.journals') }}" class="text-[11px] font-bold text-blue-700 hover:text-blue-800 underline">Review</a>
                                </div>
                                <div class="space-y-2">
                                    @foreach($pendingJournalsList as $pj)
                                        <div class="p-2.5 rounded-xl bg-white border border-blue-200/80 shadow-2xs flex items-center justify-between gap-2">
                                            <div class="min-w-0">
                                                <span class="text-xs font-bold text-slate-900 block truncate">{{ $pj->studentPkl->student->user->name }}</span>
                                                <span class="text-[10px] text-slate-500 block truncate">{{ Str::limit($pj->activity_description, 35) }}</span>
                                            </div>
                                            <a href="{{ route('guru.journals') }}" class="px-2 py-1 rounded-lg bg-blue-100 text-blue-800 font-bold text-[10px] hover:bg-blue-200 transition-colors shrink-0">
                                                Tinjau
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </x-card>

            <!-- Pengumuman Bimbingan (Clean Feed) -->
            <x-card 
                title="Pengumuman Bimbingan" 
                subtitle="Siaran informasi aktif untuk siswa PKL"
                noPadding
            >
                <x-slot:headerActions>
                    <a href="{{ route('guru.announcements') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-1 transition-colors">
                        <span>Kelola</span>
                        <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </x-slot:headerActions>

                @if(isset($recentAnnouncements) && $recentAnnouncements->count() > 0)
                    <div class="divide-y divide-slate-100">
                        @foreach($recentAnnouncements as $ann)
                            <div onclick="openDashboardAnnouncementModal({{ json_encode($ann) }})" class="p-4 hover:bg-slate-50/80 transition-colors cursor-pointer group">
                                <div class="flex items-start gap-3 justify-between">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5 mb-1.5 flex-wrap">
                                            <x-badge :variant="$ann->category_badge_variant" size="xs">
                                                {{ strtoupper($ann->category_label) }}
                                            </x-badge>
                                            @if($ann->is_pinned)
                                                <span class="inline-flex items-center gap-1 text-[9px] font-extrabold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded-full border border-amber-200/80">
                                                    <svg class="w-2.5 h-2.5 fill-current" viewBox="0 0 24 24"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg>
                                                    Pinned
                                                </span>
                                            @endif
                                        </div>
                                        <h4 class="text-xs font-bold text-slate-900 group-hover:text-indigo-600 transition-colors line-clamp-2 leading-snug">
                                            {{ $ann->title }}
                                        </h4>
                                        <p class="text-[11px] text-slate-500 line-clamp-2 mt-1 leading-relaxed font-medium">
                                            {{ Str::limit(strip_tags($ann->content), 80) }}
                                        </p>
                                        <div class="pt-2 mt-2 flex items-center justify-between text-[10px] text-slate-400">
                                            <span class="font-semibold text-slate-600 truncate max-w-[120px]">{{ $ann->author->name ?? 'Sekolah' }}</span>
                                            <span>{{ $ann->published_at ? $ann->published_at->diffForHumans() : $ann->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>

                                    @if($ann->image_path)
                                        <div class="w-14 h-14 rounded-xl bg-slate-100 border border-slate-200/80 overflow-hidden shrink-0 shadow-2xs group/thumb cursor-zoom-in" onclick="event.stopPropagation(); openImageLightbox('{{ $ann->image_url }}', '{{ $ann->title }}')" title="Perbesar gambar">
                                            <img src="{{ $ann->image_url }}" alt="{{ $ann->title }}" class="w-full h-full object-cover group-hover/thumb:scale-105 transition-transform">
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-6 text-center text-slate-400 text-xs">
                        Belum ada siaran pengumuman aktif.
                        <div class="mt-2">
                            <a href="{{ route('guru.announcements') }}" class="font-bold text-indigo-600 hover:underline">Tulis pengumuman baru</a>
                        </div>
                    </div>
                @endif
            </x-card>

            <!-- Quick Links & Resources -->
            <div class="rounded-2xl bg-indigo-50/60 border border-indigo-100/90 p-4.5 space-y-3">
                <span class="text-[11px] font-extrabold text-indigo-950 uppercase tracking-wider block">
                    Pintasan Cepat Pembimbing
                </span>
                <div class="space-y-1.5">
                    <a href="{{ route('guru.assessments') }}" class="flex items-center justify-between p-2.5 rounded-xl bg-white hover:bg-indigo-50 border border-indigo-100/80 text-xs font-bold text-slate-800 transition-colors shadow-2xs">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            <span>Instrumen Penilaian Siswa</span>
                        </span>
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <a href="{{ route('guru.reports') }}" class="flex items-center justify-between p-2.5 rounded-xl bg-white hover:bg-indigo-50 border border-indigo-100/80 text-xs font-bold text-slate-800 transition-colors shadow-2xs">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Rekap & Unduh Dokumen PDF</span>
                        </span>
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <a href="{{ route('guru.monitoring') }}" class="flex items-center justify-between p-2.5 rounded-xl bg-white hover:bg-indigo-50 border border-indigo-100/80 text-xs font-bold text-slate-800 transition-colors shadow-2xs">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>Filter Arsip Presensi Siswa</span>
                        </span>
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- Modal Detail Pengumuman Dashboard -->
<div id="modal-dashboard-announcement" onclick="if(event.target === this) closeDashboardAnnouncementModal()" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-2xl max-h-[90vh] overflow-y-auto border border-slate-100 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <span id="modal-dash-category" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200">
                PENGUMUMAN
            </span>
            <button type="button" onclick="closeDashboardAnnouncementModal()" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div id="modal-dash-img-container" class="hidden rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 cursor-zoom-in max-h-60" onclick="zoomModalImage()">
            <img id="modal-dash-img" src="" alt="Lampiran" class="w-full h-full object-cover">
        </div>

        <div class="space-y-2">
            <h3 id="modal-dash-title" class="text-base font-extrabold text-slate-900 tracking-tight leading-snug"></h3>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <span id="modal-dash-author" class="font-bold text-slate-700"></span>
                <span>&bull;</span>
                <span id="modal-dash-time"></span>
            </div>
        </div>

        <div id="modal-dash-content" class="text-xs text-slate-700 leading-relaxed font-medium bg-slate-50/70 p-4 rounded-2xl border border-slate-100 whitespace-pre-wrap"></div>

        <div class="pt-2 flex justify-end">
            <x-button type="button" variant="outline" size="sm" onclick="closeDashboardAnnouncementModal()">Tutup</x-button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let currentModalImgUrl = null;

    function openDashboardAnnouncementModal(ann) {
        document.getElementById('modal-dash-title').textContent = ann.title;
        document.getElementById('modal-dash-content').textContent = ann.content ? ann.content.replace(/<[^>]*>?/gm, '') : '';
        document.getElementById('modal-dash-category').textContent = (ann.category || 'UMUM').toUpperCase();
        document.getElementById('modal-dash-author').textContent = ann.author ? ann.author.name : 'Sekolah';
        document.getElementById('modal-dash-time').textContent = ann.published_at || ann.created_at || '';

        const imgContainer = document.getElementById('modal-dash-img-container');
        const imgElem = document.getElementById('modal-dash-img');

        if (ann.image_path) {
            currentModalImgUrl = ann.image_url || ('/storage/' + ann.image_path);
            imgElem.src = currentModalImgUrl;
            imgContainer.classList.remove('hidden');
        } else {
            currentModalImgUrl = null;
            imgContainer.classList.add('hidden');
            imgElem.src = '';
        }

        document.getElementById('modal-dashboard-announcement').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeDashboardAnnouncementModal() {
        document.getElementById('modal-dashboard-announcement').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function zoomModalImage() {
        if (currentModalImgUrl && typeof openImageLightbox === 'function') {
            openImageLightbox(currentModalImgUrl, document.getElementById('modal-dash-title').textContent);
        }
    }

    // Real-time Live Clock Updater
    function updateDashboardClock() {
        const clockElem = document.getElementById('banner-live-clock');
        const dateElem = document.getElementById('banner-live-date');
        if (!clockElem) return;

        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        clockElem.textContent = `${hours}:${minutes}:${seconds} WIB`;

        if (dateElem) {
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const dayName = days[now.getDay()];
            const dateNum = now.getDate();
            const monthName = months[now.getMonth()];
            const year = now.getFullYear();
            dateElem.textContent = `${dayName}, ${dateNum} ${monthName} ${year}`;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateDashboardClock();
        setInterval(updateDashboardClock, 1000);
    });
</script>
@endpush
@endsection
