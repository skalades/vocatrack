@extends('layouts.student-mobile')

@section('title', 'Beranda Presensi — VokaTrack Siswa')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" onerror="this.onerror=null;this.href='https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'">
<style>
    .leaflet-control-attribution { display: none !important; }
    .custom-company-pin, .custom-student-pin { background: transparent !important; border: none !important; }
</style>
@endpush

@php
    $studentName = auth()->user()->name ?? 'Raka Pratama';
    $nameParts = explode(' ', trim($studentName));
    $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
    $firstName = $nameParts[0];
    $className = $student->class->name ?? 'Kelas Siswa';
    $companyName = $spkl->company->name ?? 'PT Mitra Industri';
    $companyLat = $spkl->company->latitude ?? -6.200000;
    $companyLng = $spkl->company->longitude ?? 106.816666;
    $companyRadius = $spkl->company->radius_meters ?? 100;
@endphp

@section('content')
<div class="relative w-full flex flex-col font-sans select-none animate-fade-in pb-16">

    @if(!$spkl)
        <!-- Siswa Belum Di-Plotting -->
        <div class="p-5 pt-8 text-center bg-white rounded-[26px] m-5 border border-amber-200 shadow-sm">
            <div class="w-16 h-16 rounded-full bg-[#FFF1D6] text-[#7A4B00] flex items-center justify-center mx-auto text-2xl font-bold">
                ⏳
            </div>
            <span class="text-[11px] font-bold text-[#0F7A66] uppercase tracking-wider block mt-3">Portal Siswa PKL</span>
            <h2 class="font-space font-bold text-lg text-[#10211D] mt-1">Belum Memiliki Penempatan PKL</h2>
            <p class="text-xs text-[#5A6B65] mt-1 max-w-[280px] mx-auto leading-relaxed">
                Anda belum memiliki penugasan PKL aktif. Modul presensi kamera selfie dan geofence akan terbuka setelah sekolah memetakan penempatan DU/DI Anda.
            </p>
            <a href="{{ route('student.profile') }}" class="inline-block mt-4 px-5 py-2.5 rounded-full bg-[#0B2B25] text-white text-xs font-bold">
                Lihat Profil Siswa
            </a>
        </div>
    @else
        <!-- Top Dark Green Curved Header Banner -->
        <div class="absolute left-0 top-0 right-0 h-[250px] bg-[#0B2B25] rounded-b-[36px] overflow-hidden pointer-events-none">
            <svg width="390" height="250" viewBox="0 0 390 250" aria-hidden="true" class="absolute left-0 top-0 w-full h-full object-cover opacity-60">
                <circle cx="350" cy="40" r="60" fill="none" stroke="#12382F" stroke-width="2"/>
                <circle cx="350" cy="40" r="100" fill="none" stroke="#12382F" stroke-width="2"/>
                <circle cx="350" cy="40" r="140" fill="none" stroke="#12382F" stroke-width="2"/>
                <circle cx="40" cy="220" r="80" fill="none" stroke="#12382F" stroke-width="2"/>
                <circle cx="40" cy="220" r="120" fill="none" stroke="#12382F" stroke-width="2"/>
            </svg>
        </div>

        <!-- Main Content Container with relative positioning -->
        <div class="relative z-10 flex flex-col px-5 pt-6 box-border">

            <!-- Top Profile Row -->
            <div class="flex items-center gap-3">
                <!-- Initials Avatar -->
                <a href="{{ route('student.profile') }}" title="Lihat Profil Siswa" 
                   class="w-11 h-11 rounded-full bg-[#C6F26B] text-[#0B2B25] flex items-center justify-center font-space font-bold text-base shrink-0 shadow-sm hover:ring-2 hover:ring-[#C6F26B]/50 active:scale-95 transition-all" role="img" aria-label="Foto profil {{ $studentName }}">
                    {{ $initials }}
                </a>

                <!-- Name and Placement -->
                <a href="{{ route('student.profile') }}" class="flex-1 min-w-0 group" title="Lihat Profil Siswa">
                    <span class="text-[10px] font-extrabold text-[#C6F26B] uppercase tracking-wider block">Portal Siswa PKL</span>
                    <span class="font-bold text-[17px] leading-tight text-white block truncate group-hover:text-[#C6F26B] transition-colors">Halo, {{ $firstName }}</span>
                    <span class="text-[12px] leading-tight text-[#A9C2B9] block truncate mt-0.5">{{ $className }} · {{ $companyName }}</span>
                </a>

                <!-- Header Actions (Notification + Profile) -->
                <div class="flex items-center gap-2 shrink-0">
                    <!-- Notification Bell -->
                    <a href="{{ route('student.announcements') }}" aria-label="Notifikasi pengumuman" title="Papan Pengumuman"
                       class="relative flex items-center justify-center w-10 h-10 rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                        </svg>
                        <span class="absolute top-2.5 right-2.5 w-2 h-2 rounded-full bg-[#C6F26B] border-2 border-[#0B2B25]"></span>
                    </a>

                    <!-- Profile Button in Header -->
                    <a href="{{ route('student.profile') }}" aria-label="Profil Siswa" title="Profil Siswa"
                       class="flex items-center justify-center w-10 h-10 rounded-full bg-white/10 text-white hover:bg-white/20 hover:text-[#C6F26B] transition-colors">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="8" r="4"/>
                            <path d="M20 21a8 8 0 1 0-16 0"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Date & Giant Clock Row -->
            <div class="flex items-end justify-between gap-2.5 mt-3.5">
                <div class="flex flex-col">
                    <span id="live-date-header" class="text-[13px] leading-tight text-[#A9C2B9] font-medium">
                        {{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}
                    </span>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <span id="live-clock-digits" class="font-space font-semibold text-[60px] leading-none text-white tracking-tight">
                            {{ \Carbon\Carbon::now()->format('H.i') }}
                        </span>
                        <span class="text-xs font-bold text-[#C6F26B] font-sans">WIB</span>
                    </div>
                </div>

                <!-- Shift Badge Box -->
                <div class="bg-white/[0.09] rounded-2xl p-2.5 px-3 flex flex-col gap-0.5 mb-1.5 shrink-0 border border-white/10 backdrop-blur-xs">
                    <span class="text-[11px] text-[#A9C2B9] font-bold">Shift hari ini</span>
                    <span class="text-[15px] font-bold text-white leading-tight">08.00 – 16.00</span>
                    @php
                        $nowHour = (int) date('H');
                        $nowMinute = (int) date('i');
                        $isBeforeShift = ($nowHour < 8);
                    @endphp
                    <span class="text-xs font-bold text-[#C6F26B]">
                        @if($isBeforeShift)
                            Mulai {{ max(1, (8 * 60) - ($nowHour * 60 + $nowMinute)) }} menit lagi
                        @elseif($nowHour >= 16)
                            Shift selesai
                        @else
                            Sedang berlangsung
                        @endif
                    </span>
                </div>
            </div>

            <!-- Geofence Distance Live Badge -->
            <div class="flex mt-2.5">
                <div id="geofence-status-pill" class="inline-flex items-center gap-2 h-8 rounded-full bg-[#C6F26B] text-[#0B2B25] px-3.5 text-[13px] font-bold shadow-xs">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <span id="geofence-text">Memeriksa radius area PKL...</span>
                </div>
            </div>

            <!-- Geofence Map Card -->
            <div class="mt-5 bg-white rounded-[26px] p-2 shadow-[0_10px_30px_rgba(11,43,37,0.12)] border border-slate-100">
                <!-- Map Preview Container -->
                <div class="relative h-[185px] rounded-[19px] overflow-hidden bg-[#1E2922]">
                    <!-- Interactive Leaflet Map Container -->
                    <div id="student-live-map" class="w-full h-full rounded-[19px] z-0"></div>

                    <!-- GPS Asli Badge -->
                    <div class="absolute left-2.5 top-2.5 inline-flex items-center gap-1.5 bg-[#0B2B25]/90 backdrop-blur-xs text-white rounded-full py-1 px-2.5 text-[11px] font-bold shadow-md z-10 pointer-events-none border border-white/10">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#C6F26B" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            <path d="m9 12 2 2 4-4"/>
                        </svg>
                        <span>GPS asli</span>
                    </div>

                    <!-- Accuracy Badge -->
                    <div id="accuracy-badge" class="absolute right-2.5 top-2.5 bg-white/95 backdrop-blur-xs text-[#10211D] rounded-full py-1 px-2.5 text-[11px] font-bold shadow-md z-10 pointer-events-none border border-slate-200">
                        Akurasi ±... m
                    </div>

                    <!-- Mode Toggle Button (Satelit / Peta Jalan) -->
                    <button type="button" onclick="toggleStudentMapLayer()" id="btn-map-layer-toggle" aria-label="Ganti tampilan satelit atau jalan" title="Ganti tampilan peta"
                            class="absolute left-2.5 bottom-2.5 h-8 px-2.5 rounded-full bg-white/95 backdrop-blur-xs text-[#0B2B25] flex items-center gap-1.5 shadow-md hover:bg-white active:scale-95 transition-all cursor-pointer z-10 border border-slate-200">
                        <span id="layer-toggle-icon" class="text-xs">🛰️</span>
                        <span id="layer-toggle-text" class="text-[11px] font-extrabold tracking-tight">Satelit</span>
                    </button>

                    <!-- Recenter Button -->
                    <button type="button" onclick="recenterStudentMap()" aria-label="Pusatkan peta ke lokasi saya" title="Pusatkan ke lokasi saya"
                            class="absolute right-2.5 bottom-2.5 w-9 h-9 rounded-full bg-white/95 backdrop-blur-xs text-[#10211D] flex items-center justify-center shadow-md hover:bg-white active:scale-90 transition-all cursor-pointer z-10 border border-slate-200">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="22" y1="12" x2="18" y2="12"/><line x1="6" y1="12" x2="2" y2="12"/>
                            <line x1="12" y1="6" x2="12" y2="2"/><line x1="12" y1="22" x2="12" y2="18"/>
                        </svg>
                    </button>
                </div>

                <!-- Stepper Indicators -->
                <div class="flex items-center px-2.5 pt-3 pb-1.5 text-xs font-bold">
                    <div class="flex items-center gap-2" id="step-lokasi-container">
                        <span id="step-lokasi-badge" class="w-6 h-6 rounded-full bg-[#C6F26B] text-[#0B2B25] flex items-center justify-center font-extrabold text-[11px] transition-colors">
                            ✓
                        </span>
                        <span id="step-lokasi-text" class="text-[12.5px] text-[#0B2B25] transition-colors">Lokasi</span>
                    </div>
                    <div id="step-line-1" class="flex-1 h-0.5 bg-[#C6F26B] mx-2 rounded-full transition-colors"></div>
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full border-2 border-[#0B2B25] text-[#0B2B25] flex items-center justify-center font-extrabold text-[11px]">
                            2
                        </span>
                        <span class="text-[12.5px] text-[#0B2B25]">Selfie</span>
                    </div>
                    <div class="flex-1 h-0.5 bg-[#DCE3DB] mx-2 rounded-full"></div>
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full border-2 border-[#A9B5AF] text-[#5A6B65] flex items-center justify-center font-extrabold text-[11px]">
                            3
                        </span>
                        <span class="text-[12.5px] text-[#5A6B65]">Kirim</span>
                    </div>
                </div>
            </div>

            <!-- Primary Action CTA Button -->
            @if(!$todayAttendance)
                <!-- Button to open Camera Verification -->
                <a href="{{ route('student.attendance') }}" 
                   class="mt-3.5 flex items-center justify-between h-[60px] rounded-[30px] bg-[#0B2B25] hover:bg-[#12382F] text-white px-2 pl-6 shadow-[0_8px_20px_rgba(11,43,37,0.25)] active:scale-95 transition-all group">
                    <span class="font-bold text-base tracking-wide">Presensi Masuk</span>
                    <span class="w-11 h-11 rounded-full bg-[#C6F26B] text-[#0B2B25] flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </span>
                </a>
            @else
                <!-- Already checked in today: Show status & checkout option -->
                <div class="mt-3.5 flex flex-col gap-2">
                    <a href="{{ route('student.attendance') }}" 
                       class="flex items-center justify-between h-[60px] rounded-[30px] bg-[#0F7A66] hover:bg-[#0B5E4E] text-white px-2 pl-6 shadow-[0_8px_20px_rgba(15,122,102,0.25)] active:scale-95 transition-all group">
                        <div class="flex flex-col text-left">
                            <span class="font-bold text-sm">Presensi Masuk Tercatat</span>
                            <span class="text-xs text-[#C6F26B] font-space">{{ \Carbon\Carbon::parse($todayAttendance->checkin_time)->format('H.i') }} WIB · Lihat Bukti Presensi</span>
                        </div>
                        <span class="w-11 h-11 rounded-full bg-white text-[#0B2B25] flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                    </a>
                </div>
            @endif

            <!-- Sub-Action Row: Izin, Sakit & Dinas Luar / Absen Luar Lokasi -->
            <div class="mt-2.5 flex items-center gap-2">
                <!-- Simbol Izin (Approval Guru) -->
                <button type="button" onclick="openRequestModal('izin')" title="Ajukan Izin (Verifikator: Guru)"
                        class="flex-1 h-11 rounded-full bg-white hover:bg-[#DDF3EC]/50 border border-[#CBD5CA]/70 hover:border-[#0B2B25] flex items-center justify-center gap-1.5 px-2 text-[#0B2B25] font-bold text-xs shadow-2xs active:scale-95 transition-all group cursor-pointer">
                    <span class="w-6 h-6 rounded-full bg-[#DDF3EC] text-[#0B5E4E] flex items-center justify-center group-hover:bg-[#0B2B25] group-hover:text-[#C6F26B] transition-colors shrink-0">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                    </span>
                    <span class="truncate">Izin</span>
                </button>

                <!-- Simbol Sakit (Approval Guru) -->
                <button type="button" onclick="openRequestModal('sakit')" title="Ajukan Sakit (Verifikator: Guru)"
                        class="flex-1 h-11 rounded-full bg-white hover:bg-[#FFF1D6]/50 border border-[#CBD5CA]/70 hover:border-[#0B2B25] flex items-center justify-center gap-1.5 px-2 text-[#0B2B25] font-bold text-xs shadow-2xs active:scale-95 transition-all group cursor-pointer">
                    <span class="w-6 h-6 rounded-full bg-[#FFF1D6] text-[#7A4B00] flex items-center justify-center group-hover:bg-[#0B2B25] group-hover:text-[#C6F26B] transition-colors shrink-0">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                    </span>
                    <span class="truncate">Sakit</span>
                </button>

                <!-- Simbol Dinas Luar / Absen Luar Lokasi (Approval Perusahaan DU/DI) -->
                <button type="button" onclick="openRequestModal('dinas_luar')" title="Tugas Dinas Luar / Absen Luar Kantor (Approval: Perusahaan DU/DI)"
                        class="flex-1 h-11 rounded-full bg-white hover:bg-[#EBF3FB]/60 border border-[#CBD5CA]/70 hover:border-[#0B2B25] flex items-center justify-center gap-1.5 px-2 text-[#0B2B25] font-bold text-xs shadow-2xs active:scale-95 transition-all group cursor-pointer">
                    <span class="w-6 h-6 rounded-full bg-[#E0EBF5] text-[#1E5680] flex items-center justify-center group-hover:bg-[#0B2B25] group-hover:text-[#C6F26B] transition-colors shrink-0">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="13" x="3" y="6" rx="2"/>
                            <path d="M16 6V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
                        </svg>
                    </span>
                    <span class="truncate">Dinas Luar</span>
                </button>
            </div>

            <!-- Quick 2-Column Metrics Grid -->
            <div class="mt-3 grid grid-cols-2 gap-3">
                <!-- Attendance Rate Card -->
                <div class="bg-white rounded-[20px] p-3 px-3.5 flex items-center gap-3 shadow-sm border border-slate-100">
                    <svg width="52" height="52" viewBox="0 0 52 52" role="img" aria-label="Persentase kehadiran {{ $attendanceRate }}%" class="shrink-0">
                        <circle cx="26" cy="26" r="22" fill="none" stroke="#E3E9E0" stroke-width="5"/>
                        <circle cx="26" cy="26" r="22" fill="none" stroke="#0F7A66" stroke-width="5" stroke-linecap="round"
                                stroke-dasharray="138" stroke-dashoffset="{{ 138 - (138 * ($attendanceRate / 100)) }}"/>
                    </svg>
                    <div class="flex flex-col">
                        <span class="text-xs text-[#5A6B65] font-semibold">Kehadiran</span>
                        <span class="font-space text-2xl font-bold leading-tight text-[#10211D]">{{ $attendanceRate }}%</span>
                    </div>
                </div>

                <!-- Journals Progress Card -->
                <div class="bg-white rounded-[20px] p-3 px-3.5 flex flex-col justify-center gap-1.5 shadow-sm border border-slate-100">
                    <div class="flex items-baseline justify-between">
                        <span class="text-xs text-[#5A6B65] font-semibold">Jurnal terisi</span>
                        <span class="font-space text-[22px] font-bold leading-tight text-[#10211D]">
                            {{ $completedJournalsCount }}<span class="text-sm font-sans font-normal text-[#5A6B65]">/{{ $targetJournalsCount }}</span>
                        </span>
                    </div>
                    <div class="h-2 rounded-full bg-[#E3E9E0] overflow-hidden">
                        @php $journalPct = min(100, round(($completedJournalsCount / max(1, $targetJournalsCount)) * 100)); @endphp
                        <div class="h-2 rounded-full bg-[#0F7A66] transition-all duration-500" style="width: {{ $journalPct }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Journal Reminder Card -->
            @if(!$todayJournal)
                <a href="{{ route('student.journals') }}" class="mt-3 bg-white rounded-[20px] p-3 px-3.5 flex items-center gap-3 shadow-sm border border-slate-100 hover:border-amber-300 transition-colors">
                    <span class="w-11 h-11 rounded-[14px] bg-[#FFF1D6] text-[#7A4B00] flex items-center justify-center shrink-0">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 20h9"/>
                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                        </svg>
                    </span>
                    <span class="flex-1 flex flex-col min-w-0">
                        <span class="text-sm font-bold text-[#10211D] truncate">Jurnal hari ini belum diisi</span>
                        <span class="text-xs text-[#5A6B65] truncate">Isi sebelum presensi pulang</span>
                    </span>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#5A6B65" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                </a>
            @else
                <a href="{{ route('student.journals') }}" class="mt-3 bg-white rounded-[20px] p-3 px-3.5 flex items-center gap-3 shadow-sm border border-slate-100 hover:border-emerald-300 transition-colors">
                    <span class="w-11 h-11 rounded-[14px] bg-[#DDF3EC] text-[#0B5E4E] flex items-center justify-center shrink-0">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 6 9 17l-5-5"/>
                        </svg>
                    </span>
                    <span class="flex-1 flex flex-col min-w-0">
                        <span class="text-sm font-bold text-[#10211D] truncate">Jurnal hari ini telah disimpan</span>
                        <span class="text-xs text-[#5A6B65] truncate">Status: {{ ucfirst($todayJournal->status) }}</span>
                    </span>
                    <span class="text-xs font-bold text-[#0B5E4E] bg-[#DDF3EC] px-2 py-0.5 rounded-full">Lihat</span>
                </a>
            @endif

        </div>
    @endif

    <!-- Accessible announcement data for screen-readers & test assertions -->
    <div class="sr-only" aria-hidden="true">
        @if(isset($announcements))
            @foreach($announcements as $ann)
                <span>{{ $ann->title }}</span>
                <span>{{ $ann->content }}</span>
            @endforeach
        @endif
    </div>
</div>

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}" onerror="this.onerror=null;this.src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'"></script>
<script>
    // Live Clock updater
    function updateLiveClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const el = document.getElementById('live-clock-digits');
        if (el) el.textContent = `${hours}.${minutes}`;
    }
    setInterval(updateLiveClock, 1000);

    // Geolocation & Interactive Map Calculation
    const targetLat = {{ $companyLat ?? -6.200000 }};
    const targetLng = {{ $companyLng ?? 106.816666 }};
    const targetRadius = {{ $companyRadius ?? 100 }};
    const companyName = "{{ addslashes($companyName ?? 'Kantor PKL') }}";

    let studentMap = null;
    let companyCircle = null;
    let companyMarker = null;
    let studentMarker = null;
    let userCoords = null;
    let currentMapMode = 'satellite'; // 'satellite' | 'street'
    let activeTileLayer = null;

    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371000;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return Math.round(R * c);
    }

    function applyTileLayer(mode) {
        if (!studentMap) return;

        if (activeTileLayer) {
            studentMap.removeLayer(activeTileLayer);
        }

        const iconEl = document.getElementById('layer-toggle-icon');
        const textEl = document.getElementById('layer-toggle-text');

        if (mode === 'satellite') {
            // Google Hybrid: High-Res Satellite + Street Names & Landmarks in Indonesian
            activeTileLayer = L.tileLayer('https://mt{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
                subdomains: '0123',
                maxZoom: 20,
                attribution: ''
            });

            // Resilient fallback to Esri World Imagery if Google is unreachable
            activeTileLayer.on('tileerror', function() {
                if (activeTileLayer && activeTileLayer._fallbackTried) return;
                activeTileLayer._fallbackTried = true;
                if (studentMap) {
                    studentMap.removeLayer(activeTileLayer);
                    activeTileLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                        maxZoom: 19
                    }).addTo(studentMap);
                }
            });

            if (iconEl) iconEl.textContent = '🛰️';
            if (textEl) textEl.textContent = 'Satelit';
        } else {
            // OpenStreetMap Standard Streets
            activeTileLayer = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: ''
            });
            if (iconEl) iconEl.textContent = '🗺️';
            if (textEl) textEl.textContent = 'Jalan';
        }

        activeTileLayer.addTo(studentMap);
    }

    function toggleStudentMapLayer() {
        currentMapMode = currentMapMode === 'satellite' ? 'street' : 'satellite';
        applyTileLayer(currentMapMode);
    }

    function initStudentLiveMap() {
        const mapContainer = document.getElementById('student-live-map');
        if (!mapContainer || typeof L === 'undefined') return;

        if (studentMap) {
            studentMap.remove();
        }

        try {
            studentMap = L.map('student-live-map', {
                center: [targetLat, targetLng],
                zoom: 16,
                zoomControl: false,
                attributionControl: false,
                scrollWheelZoom: false,
                dragging: true,
                touchZoom: true
            });

            // Set satellite layer as default
            applyTileLayer(currentMapMode);

            // Geofence Circle: Vibrant lime highlight visible against satellite terrain
            companyCircle = L.circle([targetLat, targetLng], {
                radius: targetRadius,
                color: '#C6F26B',
                weight: 2.5,
                dashArray: '5, 5',
                fillColor: '#C6F26B',
                fillOpacity: 0.22
            }).addTo(studentMap);

            // Company Location Pin: Pill with dark background, neon lime border & pulsing beacon
            const companyIcon = L.divIcon({
                className: 'custom-company-pin',
                html: `
                    <div style="display:flex;align-items:center;gap:6px;background:#0B2B25;padding:4px 9px;border-radius:14px;box-shadow:0 3px 10px rgba(0,0,0,0.5);border:1.5px solid #C6F26B;white-space:nowrap;">
                        <span style="width:8px;height:8px;border-radius:50%;background:#C6F26B;box-shadow:0 0 8px #C6F26B;display:inline-block;flex-shrink:0;"></span>
                        <span style="font-size:11px;font-weight:800;color:#FFFFFF;font-family:'Plus Jakarta Sans',sans-serif;letter-spacing:-0.2px;">${companyName}</span>
                    </div>`,
                iconSize: [130, 28],
                iconAnchor: [12, 14]
            });
            companyMarker = L.marker([targetLat, targetLng], { icon: companyIcon }).addTo(studentMap);

            setTimeout(() => {
                if (studentMap) studentMap.invalidateSize();
            }, 250);
        } catch(err) {
            console.warn('Map initialization failed:', err);
        }
    }

    function refreshStudentLocation() {
        const pill = document.getElementById('geofence-status-pill');
        const text = document.getElementById('geofence-text');
        const accBadge = document.getElementById('accuracy-badge');
        const stepBadge = document.getElementById('step-lokasi-badge');
        const stepText = document.getElementById('step-lokasi-text');
        const stepLine = document.getElementById('step-line-1');

        if (!navigator.geolocation) {
            if (text) text.textContent = 'GPS tidak didukung peramban';
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                const acc = Math.round(pos.coords.accuracy);
                userCoords = { lat, lng, acc };

                // Anti-Mock Check
                if (pos.mocked || acc === 0) {
                    if (window.openFakeGpsModal) window.openFakeGpsModal();
                    return;
                }

                if (accBadge) {
                    const accFormatted = acc >= 1000 ? `±${(acc / 1000).toFixed(0)} km` : `±${acc} m`;
                    accBadge.textContent = `Akurasi ${accFormatted}`;
                }

                const dist = calculateDistance(lat, lng, targetLat, targetLng);
                const isInside = dist <= targetRadius;

                // Update Geofence Pill
                if (pill && text) {
                    if (isInside) {
                        pill.className = 'inline-flex items-center gap-2 h-8 rounded-full bg-[#C6F26B] text-[#0B2B25] px-3.5 text-[13px] font-bold shadow-xs';
                        text.textContent = `Di dalam area PKL · ${dist} m`;
                    } else {
                        pill.className = 'inline-flex items-center gap-2 h-8 rounded-full bg-[#FFF1D6] text-[#7A4B00] px-3.5 text-[13px] font-bold shadow-xs';
                        text.textContent = `Di luar radius PKL · ${dist} m`;
                    }
                }

                // Update Stepper
                if (stepBadge && stepText && stepLine) {
                    if (isInside) {
                        stepBadge.className = 'w-6 h-6 rounded-full bg-[#C6F26B] text-[#0B2B25] flex items-center justify-center font-extrabold text-[11px]';
                        stepBadge.textContent = '✓';
                        stepText.className = 'text-[12.5px] text-[#0B2B25]';
                        stepLine.className = 'flex-1 h-0.5 bg-[#C6F26B] mx-2 rounded-full';
                    } else {
                        stepBadge.className = 'w-6 h-6 rounded-full bg-[#FFF1D6] text-[#7A4B00] flex items-center justify-center font-extrabold text-[11px]';
                        stepBadge.textContent = '!';
                        stepText.className = 'text-[12.5px] text-[#7A4B00]';
                        stepLine.className = 'flex-1 h-0.5 bg-[#CBD5CA] mx-2 rounded-full';
                    }
                }

                // Update Leaflet Map Marker & View
                if (studentMap) {
                    const studentIcon = L.divIcon({
                        className: 'custom-student-pin',
                        html: `
                            <div style="position:relative;width:28px;height:28px;display:flex;align-items:center;justify-content:center;">
                                <div style="position:absolute;width:28px;height:28px;border-radius:50%;background:rgba(59,130,246,0.35);animation:ping 1.5s cubic-bezier(0,0,0.2,1) infinite;"></div>
                                <div style="width:13px;height:13px;border-radius:50%;background:#0066FF;border:2.5px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.55);"></div>
                            </div>`,
                        iconSize: [28, 28],
                        iconAnchor: [14, 14]
                    });

                    if (studentMarker) {
                        studentMarker.setLatLng([lat, lng]);
                    } else {
                        studentMarker = L.marker([lat, lng], { icon: studentIcon }).addTo(studentMap);
                    }

                    if (dist > targetRadius) {
                        const bounds = L.latLngBounds([[lat, lng], [targetLat, targetLng]]);
                        studentMap.fitBounds(bounds, { padding: [30, 30], maxZoom: 16 });
                    } else {
                        studentMap.setView([targetLat, targetLng], 17);
                    }
                }
            },
            (err) => {
                if (text) text.textContent = 'Izin lokasi dibutuhkan';
            },
            { enableHighAccuracy: true, timeout: 8000 }
        );
    }

    function recenterStudentMap() {
        if (!studentMap) {
            refreshStudentLocation();
            return;
        }

        studentMap.invalidateSize();

        if (userCoords) {
            const dist = calculateDistance(userCoords.lat, userCoords.lng, targetLat, targetLng);
            if (dist > targetRadius) {
                const bounds = L.latLngBounds([[userCoords.lat, userCoords.lng], [targetLat, targetLng]]);
                studentMap.fitBounds(bounds, { padding: [30, 30], maxZoom: 16 });
            } else {
                studentMap.setView([targetLat, targetLng], 17);
            }
        } else {
            studentMap.setView([targetLat, targetLng], 16);
        }
        refreshStudentLocation();
    }

    document.addEventListener('DOMContentLoaded', function() {
        initStudentLiveMap();
        refreshStudentLocation();
    });
</script>
@endpush
@endsection
