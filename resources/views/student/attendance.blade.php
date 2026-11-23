@extends('layouts.student-mobile')

@section('title', 'Presensi Siswa — VokaTrack')

@php
    $studentName = auth()->user()->name ?? 'Raka Pratama';
    $firstName = explode(' ', trim($studentName))[0];
    $companyName = $spkl->company->name ?? 'Kantor PKL';

    // Tenant feature policies from school
    $school = $spkl?->school;
    $isSchoolCheckinSelfie = $school ? $school->isFeatureEnabled('feature_checkin_selfie', true) : true;
    $isSchoolCheckinGeofence = $school ? $school->isFeatureEnabled('feature_checkin_geofence', true) : true;
    $isSchoolCheckoutSelfie = $school ? $school->isFeatureEnabled('feature_checkout_selfie', true) : true;
    $isSchoolCheckoutGeofence = $school ? $school->isFeatureEnabled('feature_checkout_geofence', true) : true;

    $isCheckoutMode = ($todayAttendance && !$todayAttendance->checkout_time && ($mode === 'checkout' || request('mode') === 'checkout'));

    $isTestStudent = (bool) ($spkl?->isTestStudent());

    $isSelfieEnabled = $isCheckoutMode ? $isSchoolCheckoutSelfie : $isSchoolCheckinSelfie;
    $isGpsEnabled = $isCheckoutMode
        ? ($isSchoolCheckoutGeofence && (bool) ($spkl->company->gps_enabled ?? false))
        : ($isSchoolCheckinGeofence && (bool) ($spkl->company->gps_enabled ?? false));

    // Jika siswa dalam mode simulasi uji coba (stress test), bebaskan geofencing GPS
    if ($isTestStudent) {
        $isGpsEnabled = false;
    }

    $companyLat = $spkl->company->latitude ?? -6.200000;
    $companyLng = $spkl->company->longitude ?? 106.816666;
    $companyRadius = $spkl->company->radius_meters ?? 100;
@endphp

@section('content')
<div class="relative w-full font-sans select-none animate-fade-in">

    @if(!$spkl)
        <!-- Case: Siswa Belum Di-Plotting -->
        <div class="p-5 pt-8 text-center bg-white rounded-[26px] m-5 border border-amber-200 shadow-sm">
            <div class="w-16 h-16 rounded-full bg-[#FFF1D6] text-[#7A4B00] flex items-center justify-center mx-auto shadow-xs">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h2 class="font-space font-bold text-lg text-[#10211D] mt-3">Belum Memiliki Penempatan PKL</h2>
            <p class="text-xs text-[#5A6B65] mt-1 max-w-[280px] mx-auto leading-relaxed">
                Anda belum memiliki penugasan PKL aktif. Modul presensi kamera selfie dan geofence akan terbuka setelah sekolah memetakan penempatan DU/DI Anda.
            </p>
            <a href="{{ route('student.home') }}" class="inline-block mt-4 px-5 py-2.5 rounded-full bg-[#0B2B25] text-white text-xs font-bold">
                Kembali ke Beranda
            </a>
        </div>

    @elseif(!$isPklStarted)
        <!-- Case: Pelaksanaan PKL Belum Dimulai -->
        <div class="p-6 pt-10 text-center bg-white rounded-[26px] m-5 border border-slate-200 shadow-sm">
            <div class="w-16 h-16 rounded-full bg-[#E0EBF5] text-[#1E5680] flex items-center justify-center mx-auto shadow-xs">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/>
                </svg>
            </div>
            <h2 class="font-space font-bold text-lg text-[#10211D] mt-4">Pelaksanaan PKL Belum Dimulai</h2>
            <p class="text-xs text-[#5A6B65] mt-1.5 max-w-[290px] mx-auto leading-relaxed">
                Jadwal PKL di <strong>{{ $companyName }}</strong> belum resmi dimulai oleh pihak sekolah/industri. Anda <strong>dianulir dari kewajiban presensi</strong> dan tidak akan tercatat sebagai alfa.
            </p>
            <a href="{{ route('student.home') }}" class="inline-block mt-5 px-6 py-2.5 rounded-full bg-[#0B2B25] text-white text-xs font-bold shadow-xs">
                Kembali ke Beranda
            </a>
        </div>

    @elseif($todayAttendance && !$isCheckoutMode)
        <!-- ========================================================
             SCREEN 3: PRESENSI BERHASIL / BUKTI PRESENSI MASUK & PULANG
             ======================================================== -->
        <x-student.attendance-ticket 
            :attendance="$todayAttendance" 
            :spkl="$spkl" 
            :student="$student" 
            :dayNumber="$dayNumber ?? 27" 
            :todayJournal="$todayJournal ?? null"
        />

        <!-- Secondary Section: Presensi Pulang & Permohonan Khusus -->
        <div class="px-5 pb-20 -mt-16 relative z-20 flex flex-col gap-3">
            @if(!$todayAttendance->checkout_time)
                <!-- Presensi Pulang (Check-Out) Card -->
                <div class="bg-white rounded-[24px] p-4 shadow-sm border border-slate-100 flex flex-col gap-2.5">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="font-bold text-sm text-[#10211D]">Presensi Pulang</span>
                            <span class="text-[11px] text-[#5A6B65]">Wajib swafoto kamera & verifikasi geofencing GPS</span>
                        </div>
                        <span class="text-xs font-bold {{ !empty($dayStatus['can_checkout']) ? 'text-[#0B5E4E] bg-[#DDF3EC]' : 'text-[#7A4B00] bg-[#FFF1D6]' }} px-2.5 py-1 rounded-full">
                            {{ !empty($dayStatus['can_checkout']) ? 'Waktu Pulang Dibuka' : 'Buka ' . ($dayStatus['checkout_start_time'] ?? '16.00') . ' WIB' }}
                        </span>
                    </div>

                    @if(!empty($dayStatus['can_checkout']))
                        <a href="{{ route('student.attendance', ['mode' => 'checkout']) }}" 
                           class="w-full h-12 rounded-[24px] bg-[#0B2B25] hover:bg-[#12382F] text-[#C6F26B] font-bold text-xs active:scale-95 transition-all flex items-center justify-center gap-2 shadow-xs">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                            <span>Ambil Swafoto & Presensi Pulang</span>
                        </a>
                    @else
                        <div class="p-3 rounded-2xl bg-[#F7F9F5] border border-[#CBD5CA]/70 text-center">
                            <span class="text-xs text-[#5A6B65] font-medium leading-relaxed">
                                Presensi pulang dapat diambil mulai pukul <strong>{{ $dayStatus['checkout_start_time'] ?? '16.00' }} WIB</strong> setelah jam kerja selesai.
                            </span>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Riwayat Kehadiran Accordion / Button -->
            <button type="button" onclick="document.getElementById('attendance-history-drawer').classList.toggle('hidden')"
                    class="w-full h-12 rounded-[24px] bg-white border border-slate-200 text-[#10211D] font-bold text-xs flex items-center justify-between px-4 shadow-xs">
                <span>Riwayat Kehadiran Saya</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
            </button>

            <!-- Collapsible Attendance History -->
            <div id="attendance-history-drawer" class="hidden flex flex-col gap-2 bg-white rounded-[24px] p-4 border border-slate-100 shadow-sm">
                <span class="font-bold text-xs text-[#5A6B65] uppercase tracking-wider mb-1 block">Catatan Kehadiran Terakhir</span>
                @forelse($history as $item)
                    <div class="flex items-center justify-between py-2 border-b border-slate-100 last:border-0 text-xs">
                        <div>
                            <span class="font-bold text-[#10211D] block">{{ \Carbon\Carbon::parse($item->date)->isoFormat('dddd, D MMM') }}</span>
                            <span class="text-[#5A6B65] text-[11px]">Masuk: {{ $item->checkin_time ? \Carbon\Carbon::parse($item->checkin_time)->format('H.i') : '-' }} WIB</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $item->status === 'hadir' ? 'bg-[#DDF3EC] text-[#0B5E4E]' : ($item->status === 'terlambat' ? 'bg-[#FFF1D6] text-[#7A4B00]' : 'bg-slate-100 text-slate-700') }}">
                            {{ ucfirst($item->status) }}
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 text-center py-2">Belum ada riwayat presensi.</p>
                @endforelse
            </div>
        </div>

    @else
        <!-- ========================================================
             SCREEN 2: VERIFIKASI SELFIE PRESENSI (ACTIVE CAMERA)
             ======================================================== -->
        <div class="bg-[#0B2B25] text-white min-h-screen flex flex-col justify-between p-4 pb-8 box-border">

            <!-- Topbar Row -->
            <div class="flex items-center justify-between h-12">
                <a href="{{ $isCheckoutMode ? route('student.attendance') : route('student.home') }}" aria-label="Kembali"
                   class="flex items-center justify-center w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors cursor-pointer">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>

                <div class="flex flex-col items-center">
                    <h1 class="text-[17px] font-bold text-white tracking-tight">
                        @if($isSelfieEnabled)
                            {{ $isCheckoutMode ? 'Verifikasi Selfie Pulang' : 'Verifikasi Selfie' }}
                        @else
                            {{ $isCheckoutMode ? 'Presensi Pulang' : 'Presensi Masuk' }}
                        @endif
                    </h1>
                    <span class="text-[11px] text-[#A9C2B9] font-medium leading-none">
                        {{ $isCheckoutMode ? 'Check-Out Pulang' : 'Check-In Hari Ini' }}
                    </span>
                </div>

                <a href="{{ $isCheckoutMode ? route('student.attendance') : route('student.home') }}" title="Batal & Kembali" class="text-xs font-bold text-[#A9C2B9] hover:text-white px-2 py-1">
                    Batal
                </a>
            </div>

            @if($spkl?->isTestStudent())
                <div class="my-1.5 px-4 py-2 rounded-2xl bg-amber-500/20 border border-amber-400/40 text-amber-200 text-xs flex items-center justify-between shadow-xs">
                    <span class="flex items-center gap-2 font-bold text-[11px]">
                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                        Mode Simulasi Uji Coba Aktif
                    </span>
                    <span class="text-[10.5px] text-amber-300 font-semibold bg-amber-400/10 px-2 py-0.5 rounded-full border border-amber-300/30">Bebas Radius GPS</span>
                </div>
            @endif

            @if($isSelfieEnabled)
                <!-- Camera Viewfinder Box (Compact & Responsive for Mobile Viewport) -->
                <div class="relative h-[380px] sm:h-[430px] rounded-[28px] bg-[#12382F] overflow-hidden my-1 flex items-center justify-center border border-white/10 shadow-2xl">
                    <!-- Live Video Feed -->
                    <video id="webcam-feed" autoplay playsinline muted class="w-full h-full object-cover transform scale-x-[-1]"></video>

                    <!-- Hidden Canvas for capture snapshot -->
                    <canvas id="snapshot-canvas" class="hidden"></canvas>

                    <!-- Camera Blocked / Permission Denied Overlay -->
                    <div id="camera-blocked-overlay" class="hidden absolute inset-0 z-20 bg-[#071915]/92 backdrop-blur-xs flex flex-col items-center justify-center p-6 text-center animate-fade-in">
                        <div class="relative w-16 h-16 rounded-2xl bg-white/[0.08] border border-white/15 flex items-center justify-center mb-3 shadow-inner">
                            <svg class="w-8 h-8 text-[#C6F26B]" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M1 1l22 22m-2-2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h3m3-3h6l2 3h4a2 2 0 0 1 2 2v9.34m-7.72-2.06a4 4 0 1 1-5.56-5.56"/>
                            </svg>
                            <span class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-rose-500 text-white flex items-center justify-center border-2 border-[#071915]">
                                <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </span>
                        </div>
                        <h3 class="font-space font-bold text-base text-white">Kamera Belum Diizinkan</h3>
                        <p class="text-xs text-[#A9C2B9] mt-1 max-w-[260px] leading-relaxed">
                            Safari / Peramban belum diberikan akses ke kamera selfie Anda.
                        </p>
                        <button type="button" onclick="window.showPermissionGuideModal('camera')" 
                                class="mt-3.5 px-4 py-2 rounded-full bg-[#C6F26B] hover:bg-[#b8e85c] text-[#0B2B25] text-xs font-bold active:scale-95 transition-all shadow-xs cursor-pointer flex items-center gap-1.5">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14L21 3"/></svg>
                            <span>Buka Panduan Izin Kamera</span>
                        </button>
                    </div>

                    <!-- Face Oval Mask Overlay SVG -->
                    <svg width="100%" height="100%" viewBox="0 0 350 480" role="img" aria-label="Bingkai verifikasi wajah" class="absolute inset-0 pointer-events-none">
                        <defs>
                            <mask id="face-viewfinder-mask">
                                <rect width="350" height="480" fill="#fff"/>
                                <ellipse cx="175" cy="220" rx="95" ry="128" fill="#000"/>
                            </mask>
                        </defs>
                        <!-- Darkened vignette around face -->
                        <rect width="350" height="480" fill="rgba(11,43,37,0.58)" mask="url(#face-viewfinder-mask)"/>
                        <!-- Glowing Dashed Face Guide Oval -->
                        <ellipse cx="175" cy="220" rx="95" ry="128" fill="none" stroke="#C6F26B" stroke-width="2.8" stroke-dasharray="8 6"/>
                        <!-- Horizontal alignment marker -->
                        <line x1="110" y1="220" x2="240" y2="220" stroke="#C6F26B" stroke-width="1.8" opacity="0.6"/>
                    </svg>

                    <!-- Top Prompt Pill -->
                    <div class="absolute top-3.5 inset-x-0 flex justify-center z-10 px-4">
                        <span id="liveness-prompt-pill" class="inline-flex items-center gap-2 bg-white text-[#0B2B25] rounded-full px-4 py-1.5 text-xs font-bold shadow-md transition-all">
                            <span id="liveness-prompt-text">Posisikan wajah di dalam bingkai oval</span>
                        </span>
                    </div>

                    <!-- Bottom Functional Indicators (Kamera Siap & GPS Terkunci) -->
                    <div class="absolute bottom-3 inset-x-3 flex gap-2 justify-center z-10">
                        <!-- Status Kamera -->
                        <span id="badge-camera" class="inline-flex items-center gap-1.5 bg-[#071915]/85 backdrop-blur-xs text-white rounded-full px-3 py-1 text-[11px] font-bold border border-white/10 shadow-xs">
                            <span id="badge-camera-dot" class="w-2 h-2 rounded-full bg-amber-400"></span>
                            <span id="badge-camera-text">Kamera: Memuat...</span>
                        </span>

                        <!-- Status GPS -->
                        <span id="badge-gps" class="inline-flex items-center gap-1.5 bg-[#071915]/85 backdrop-blur-xs text-white rounded-full px-3 py-1 text-[11px] font-bold border border-white/10 shadow-xs">
                            <span id="badge-gps-dot" class="w-2 h-2 rounded-full bg-amber-400"></span>
                            <span id="badge-gps-text">GPS: Mencari...</span>
                        </span>
                    </div>
                </div>
            @else
                <!-- No-Selfie Direct Attendance Card -->
                <div class="relative min-h-[300px] sm:min-h-[340px] rounded-[28px] bg-[#12382F] p-6 my-1 flex flex-col items-center justify-center text-center border border-white/10 shadow-2xl">
                    <div class="w-16 h-16 rounded-3xl bg-[#C6F26B]/15 text-[#C6F26B] border border-[#C6F26B]/30 flex items-center justify-center mb-3 shadow-inner">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span class="px-3 py-1 rounded-full bg-white/10 text-[#C6F26B] text-[10.5px] font-bold uppercase tracking-wider mb-2">
                        Presensi Cepat · Bebas Kamera
                    </span>
                    <h2 class="font-space font-bold text-lg text-white">
                        {{ $isCheckoutMode ? 'Presensi Pulang Mandiri' : 'Presensi Masuk Mandiri' }}
                    </h2>
                    <p class="text-xs text-[#A9C2B9] mt-1 max-w-[270px] leading-relaxed">
                        Sekolah menonaktifkan kewajiban swafoto. Anda dapat langsung mencatat {{ $isCheckoutMode ? 'kepulangan kerja' : 'kehadiran' }} dengan tombol di bawah.
                    </p>

                    <!-- Bottom Functional GPS Indicator Only -->
                    <div class="flex gap-2 justify-center mt-4">
                        <span id="badge-gps" class="inline-flex items-center gap-1.5 bg-[#071915]/85 backdrop-blur-xs text-white rounded-full px-3 py-1 text-[11px] font-bold border border-white/10 shadow-xs">
                            <span id="badge-gps-dot" class="w-2 h-2 rounded-full bg-amber-400"></span>
                            <span id="badge-gps-text">GPS: Mencari...</span>
                        </span>
                    </div>
                </div>
            @endif

            <!-- Location & Realtime Distance Bar -->
            <div id="location-bar-container" onclick="handleLocationBarClick()" 
                 class="flex items-center justify-between min-h-[52px] rounded-[22px] bg-white/[0.08] px-4 py-2 border border-white/10 my-1 transition-all cursor-pointer hover:bg-white/[0.12]">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span id="location-bar-icon-wrap" class="w-8 h-8 rounded-full bg-[#C6F26B]/15 text-[#C6F26B] flex items-center justify-center shrink-0">
                        <svg id="location-bar-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </span>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[13px] font-bold text-white truncate">{{ $companyName }}</span>
                        <span id="selfie-distance-text" class="text-[11px] text-[#A9C2B9] truncate">
                            {{ $isGpsEnabled ? 'Menghitung jarak ke kantor...' : 'Bebas Radius (Geofence Nonaktif)' }}
                        </span>
                    </div>
                </div>

                <div class="flex flex-col items-end shrink-0 pl-2">
                    <span id="selfie-radius-badge" class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide {{ $isGpsEnabled ? 'bg-white/10 text-white' : 'bg-[#C6F26B] text-[#0B2B25]' }}">
                        {{ $isGpsEnabled ? 'Memeriksa' : 'Bebas Radius' }}
                    </span>
                    <span id="selfie-accuracy-text" class="text-[10px] text-[#A9C2B9] mt-0.5">±-- m</span>
                </div>
            </div>

            @if($isCheckoutMode)
                <!-- Optional Checkout Notes Input -->
                <div class="px-1 my-1">
                    <input type="text" id="checkout-notes-input" placeholder="Catatan kepulangan / ringkasan kegiatan hari ini (opsional)..." 
                           class="w-full h-11 rounded-2xl bg-white/10 border border-white/15 px-3.5 text-xs text-white placeholder:text-[#A9C2B9] focus:outline-none focus:border-[#C6F26B] transition-colors">
                </div>
            @endif

            <!-- Controls Row (Flash, Big Shutter, Flip Camera) -->
            <div class="flex items-center justify-center gap-8 pt-2 pb-1">
                @if($isSelfieEnabled)
                    <!-- Flash Button -->
                    <button type="button" onclick="toggleCameraFlash()" aria-label="Nyalakan lampu kilat"
                            class="w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors cursor-pointer">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                        </svg>
                    </button>
                @endif

                <!-- Big Shutter Button -->
                <button type="button" id="shutter-btn" onclick="captureAndSubmitPresensi()" aria-label="{{ $isCheckoutMode ? 'Presensi pulang sekarang' : 'Kirim presensi masuk' }}"
                        class="w-[84px] h-[84px] rounded-full border-4 border-[#C6F26B] flex items-center justify-center active:scale-90 transition-all cursor-pointer group shadow-[0_0_20px_rgba(198,242,107,0.3)]">
                    <span id="shutter-btn-core" class="w-[62px] h-[62px] rounded-full bg-white group-hover:scale-95 transition-transform flex items-center justify-center text-[#0B2B25]">
                        @if(!$isSelfieEnabled)
                            <svg class="w-7 h-7 text-[#0B2B25]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        @endif
                    </span>
                </button>

                @if($isSelfieEnabled)
                    <!-- Switch Camera Button -->
                    <button type="button" onclick="flipCameraFacing()" aria-label="Ganti kamera"
                            class="w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors cursor-pointer">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 16v5h-5M4 8V3h5"/>
                            <path d="M4 16v-1a8 8 0 0 1 14.93-4M20 8v1a8 8 0 0 1-14.93 4"/>
                        </svg>
                    </button>
                @endif
            </div>

            <!-- Disclaimer Info -->
            <p class="text-center text-[11px] text-[#A9C2B9] leading-tight mb-1">
                @if($isGpsEnabled)
                    Presensi hanya berlaku di lokasi DU/DI. Untuk tugas luar, gunakan menu <strong>Dinas Luar</strong> di Beranda.
                @else
                    Presensi bebas radius aktif untuk DU/DI ini. Anda dapat presensi dari lokasi penugasan mana pun.
                @endif
            </p>

            <!-- Hidden Check-in / Check-out Submission Form -->
            <form id="attendance-submission-form" action="{{ $isCheckoutMode ? route('student.checkout') : route('student.checkin') }}" method="POST" class="hidden">
                @csrf
                <input type="hidden" name="photo" id="form-photo-base64">
                <input type="hidden" name="latitude" id="form-latitude">
                <input type="hidden" name="longitude" id="form-longitude">
                <input type="hidden" name="accuracy" id="form-accuracy">
                <input type="hidden" name="is_mocked" id="form-is-mocked" value="0">
                <input type="hidden" name="reason" id="form-reason" value="">
                <input type="hidden" name="is_within_radius" id="form-is-within-radius" value="1">
                @if($isCheckoutMode)
                    <input type="hidden" name="checkout_notes" id="form-checkout-notes" value="">
                @endif
            </form>

        </div>
    @endif

</div>

@push('scripts')
<script>
    let videoStream = null;
    let currentFacingMode = 'user'; // front camera default
    let userCoords = { latitude: null, longitude: null, accuracy: null, isMocked: 0, mockReason: null };
    let cameraStatus = 'loading'; // 'loading', 'ready', 'denied'
    let gpsStatus = 'searching';   // 'searching', 'locked', 'weak_accuracy', 'denied', 'unavailable', 'fake_gps'
    
    const isGpsEnabled = {{ $isGpsEnabled ? 'true' : 'false' }};
    const isSelfieEnabled = {{ $isSelfieEnabled ? 'true' : 'false' }};
    const isCheckoutMode = {{ $isCheckoutMode ? 'true' : 'false' }};
    const targetLat = {{ $companyLat }};
    const targetLng = {{ $companyLng }};
    const targetRadius = {{ $companyRadius }};

    // Haversine Distance in meters (matches backend GeolocationGuardService)
    function calculateDistanceM(lat1, lon1, lat2, lon2) {
        const R = 6371000; // Earth's radius in meters
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return Math.round(R * c);
    }

    // Update Shutter button visual feedback depending on Readiness, Distance, & Mock GPS
    function updateShutterVisuals() {
        const shutterBtn = document.getElementById('shutter-btn');
        const shutterCore = document.getElementById('shutter-btn-core');
        if (!shutterBtn || !shutterCore) return;

        const hasRealCoords = userCoords.latitude !== null && userCoords.longitude !== null;
        let isWithin = true;
        if (isGpsEnabled) {
            if (hasRealCoords) {
                const dist = calculateDistanceM(userCoords.latitude, userCoords.longitude, targetLat, targetLng);
                isWithin = (dist <= targetRadius);
            } else {
                isWithin = false;
            }
        }

        const isGpsReady = !isGpsEnabled || (gpsStatus === 'locked' && isWithin);
        const isReady = (cameraStatus === 'ready') && isGpsReady && !userCoords.isMocked;

        if (userCoords.isMocked || gpsStatus === 'fake_gps') {
            shutterBtn.disabled = true;
            shutterBtn.classList.add('opacity-50', 'border-rose-500', 'shadow-[0_0_20px_rgba(255,90,77,0.3)]');
            shutterBtn.classList.remove('opacity-40', 'border-slate-500', 'border-[#C6F26B]', 'shadow-none', 'shadow-[0_0_20px_rgba(198,242,107,0.3)]');
            shutterCore.classList.add('bg-rose-500');
            shutterCore.classList.remove('bg-white', 'bg-slate-400');
        } else if (isReady) {
            shutterBtn.disabled = false;
            shutterBtn.classList.remove('opacity-40', 'opacity-50', 'border-slate-500', 'border-rose-500', 'shadow-none');
            shutterBtn.classList.add('border-[#C6F26B]', 'shadow-[0_0_20px_rgba(198,242,107,0.3)]');
            shutterCore.classList.remove('bg-slate-400', 'bg-rose-500');
            shutterCore.classList.add('bg-white');
        } else {
            shutterBtn.disabled = false;
            shutterBtn.classList.add('opacity-40', 'border-slate-500', 'shadow-none');
            shutterBtn.classList.remove('opacity-50', 'border-rose-500', 'border-[#C6F26B]', 'shadow-[0_0_20px_rgba(198,242,107,0.3)]');
            shutterCore.classList.add('bg-slate-400');
            shutterCore.classList.remove('bg-white', 'bg-rose-500');
        }
    }

    // Update realtime Camera and GPS status badges inside viewfinder
    function updateStatusBadges() {
        const camDot = document.getElementById('badge-camera-dot');
        const camText = document.getElementById('badge-camera-text');
        const gpsDot = document.getElementById('badge-gps-dot');
        const gpsText = document.getElementById('badge-gps-text');

        // Camera Badge
        if (camDot && camText) {
            if (!isSelfieEnabled) {
                camDot.className = 'w-2 h-2 rounded-full bg-[#C6F26B]';
                camText.textContent = 'Kamera: Nonaktif';
            } else if (cameraStatus === 'ready') {
                camDot.className = 'w-2 h-2 rounded-full bg-[#C6F26B]';
                camText.textContent = 'Kamera: Siap';
            } else if (cameraStatus === 'denied') {
                camDot.className = 'w-2 h-2 rounded-full bg-rose-500';
                camText.textContent = 'Kamera: Diblokir';
            } else {
                camDot.className = 'w-2 h-2 rounded-full bg-amber-400 animate-pulse';
                camText.textContent = 'Kamera: Memuat...';
            }
        }

        // GPS Badge
        if (gpsDot && gpsText) {
            if (gpsStatus === 'fake_gps' || userCoords.isMocked) {
                gpsDot.className = 'w-2 h-2 rounded-full bg-rose-500 animate-pulse';
                gpsText.textContent = 'GPS: Lokasi Palsu';
            } else if (!isGpsEnabled) {
                gpsDot.className = 'w-2 h-2 rounded-full bg-[#C6F26B]';
                gpsText.textContent = userCoords.accuracy ? `GPS: Bebas Radius (±${userCoords.accuracy}m)` : 'GPS: Bebas Radius';
            } else if (gpsStatus === 'locked') {
                gpsDot.className = 'w-2 h-2 rounded-full bg-[#C6F26B]';
                gpsText.textContent = `GPS: Terkunci (±${userCoords.accuracy}m)`;
            } else if (gpsStatus === 'weak_accuracy') {
                gpsDot.className = 'w-2 h-2 rounded-full bg-amber-400';
                gpsText.textContent = `GPS: Lemah (±${userCoords.accuracy}m)`;
            } else if (gpsStatus === 'denied') {
                gpsDot.className = 'w-2 h-2 rounded-full bg-rose-500';
                gpsText.textContent = 'GPS: Ditolak';
            } else if (gpsStatus === 'unavailable') {
                gpsDot.className = 'w-2 h-2 rounded-full bg-rose-500';
                gpsText.textContent = 'GPS: Tidak Aktif';
            } else {
                gpsDot.className = 'w-2 h-2 rounded-full bg-amber-400 animate-pulse';
                gpsText.textContent = 'GPS: Mencari...';
            }
        }
    }

    // Handle clicks on location status bar to guide users
    function handleLocationBarClick() {
        if (gpsStatus === 'fake_gps' || userCoords.isMocked) {
            if (window.openFakeGpsModal) {
                window.openFakeGpsModal(userCoords.mockReason || 'Lokasi palsu terdeteksi pada perangkat');
            }
        } else if (gpsStatus === 'denied') {
            window.showPermissionGuideModal('location');
        } else if (gpsStatus === 'weak_accuracy') {
            window.showPermissionGuideModal('location-accuracy');
        } else if (gpsStatus === 'searching') {
            window.vtAlert({
                tone: 'warning',
                title: 'Mencari sinyal GPS…',
                message: 'Pastikan Anda berada di area dengan langit terbuka agar sinyal satelit cepat terkunci.',
            });
        } else if (gpsStatus === 'unavailable') {
            window.vtAlert({
                tone: 'danger',
                title: 'GPS tidak aktif',
                message: 'Sensor GPS perangkat tidak ditemukan. Buka Pengaturan iPhone/Android dan aktifkan Layanan Lokasi.',
            });
        } else if (gpsStatus === 'locked') {
            const dist = calculateDistanceM(userCoords.latitude, userCoords.longitude, targetLat, targetLng);
            const inside = dist <= targetRadius;
            window.vtAlert({
                tone: inside ? 'location' : 'warning',
                eyebrow: 'Informasi lokasi',
                title: inside ? 'Di dalam radius kantor' : 'Di luar radius kantor',
                details: [
                    ['Jarak ke DU/DI', `±${Number(dist).toLocaleString('id-ID')} m`],
                    ['Toleransi', `${Number(targetRadius).toLocaleString('id-ID')} m`],
                    ['Akurasi GPS', `±${Math.round(userCoords.accuracy)} m`],
                    ['Status', inside ? 'Bisa presensi' : 'Belum bisa'],
                ],
            });
        }
    }

    function startLiveCamera() {
        if (!isSelfieEnabled) {
            cameraStatus = 'ready';
            updateStatusBadges();
            updateShutterVisuals();
            return;
        }

        const video = document.getElementById('webcam-feed');
        const overlay = document.getElementById('camera-blocked-overlay');
        const promptText = document.getElementById('liveness-prompt-text');
        if (!video) return;

        if (videoStream) {
            videoStream.getTracks().forEach(track => track.stop());
        }

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            cameraStatus = 'denied';
            if (overlay) overlay.classList.remove('hidden');
            if (promptText) promptText.textContent = 'Kamera tidak didukung peramban ini';
            updateStatusBadges();
            updateShutterVisuals();
            return;
        }

        const constraints = {
            video: {
                facingMode: currentFacingMode,
                width: { ideal: 720 },
                height: { ideal: 960 }
            },
            audio: false
        };

        navigator.mediaDevices.getUserMedia(constraints)
            .then(stream => {
                videoStream = stream;
                video.srcObject = stream;
                cameraStatus = 'ready';
                if (overlay) overlay.classList.add('hidden');
                if (promptText) promptText.textContent = 'Posisikan wajah di dalam bingkai oval';
                updateStatusBadges();
                updateShutterVisuals();
            })
            .catch(err => {
                console.warn('Camera access denied or failed:', err);
                cameraStatus = 'denied';
                if (overlay) overlay.classList.remove('hidden');
                if (promptText) promptText.textContent = 'Izin kamera dibutuhkan untuk verifikasi';
                updateStatusBadges();
                updateShutterVisuals();
            });
    }

    function flipCameraFacing() {
        currentFacingMode = (currentFacingMode === 'user') ? 'environment' : 'user';
        const video = document.getElementById('webcam-feed');
        if (video) {
            video.style.transform = (currentFacingMode === 'user') ? 'scaleX(-1)' : 'scaleX(1)';
        }
        startLiveCamera();
    }

    function toggleCameraFlash() {
        if (!videoStream) return;
        const track = videoStream.getVideoTracks()[0];
        if (track && track.getCapabilities && track.getCapabilities().torch) {
            const currentSetting = track.getSettings().torch || false;
            track.applyConstraints({ advanced: [{ torch: !currentSetting }] }).catch(() => {});
        } else {
            // Flash screen brightness simulation (Fallback for Safari/iOS)
            const viewfinder = document.getElementById('webcam-feed');
            if (viewfinder) {
                viewfinder.style.filter = viewfinder.style.filter ? '' : 'brightness(1.5)';
            }
        }
    }

    function captureAndSubmitPresensi() {
        const form = document.getElementById('attendance-submission-form') || document.getElementById('checkin-form');
        const photoInput = document.getElementById('form-photo-base64');
        const shutterBtn = document.getElementById('shutter-btn');
        const shutterCore = document.getElementById('shutter-btn-core');

        if (!form || !shutterBtn) return;

        // 0. Guard: Koneksi internet. Presensi tidak bisa diantrekan offline karena waktu
        //    & lokasi wajib diverifikasi server saat itu juga. Kamera tetap menyala agar
        //    siswa bisa langsung mencoba lagi begitu sinyal kembali.
        if (!navigator.onLine) {
            window.vtAlert({
                tone: 'warning',
                title: 'Tidak ada koneksi internet',
                message: 'Presensi memerlukan internet karena waktu dan lokasi diverifikasi server. Cari titik bersinyal lalu tekan tombol presensi lagi.',
                hint: 'Jika seharian tidak ada sinyal di lokasi PKL, laporkan ke guru pembimbing agar dicatat manual.',
            });
            return;
        }

        // 1. Guard: Camera Readiness (Hanya diverifikasi jika kewajiban swafoto aktif di sekolah)
        if (isSelfieEnabled) {
            const video = document.getElementById('webcam-feed');
            const canvas = document.getElementById('snapshot-canvas');
            if (!video || !canvas || cameraStatus !== 'ready' || !videoStream || video.videoWidth === 0) {
                window.showPermissionGuideModal('camera');
                return;
            }
        }

        // 2. Guard: Geolocation Real Readings
        if (isGpsEnabled && (userCoords.latitude === null || userCoords.longitude === null)) {
            if (gpsStatus === 'denied') {
                window.showPermissionGuideModal('location');
            } else {
                window.vtAlert({
                    tone: 'warning',
                    title: 'Menunggu sinyal GPS',
                    message: 'Koordinat perangkat belum terkunci. Pastikan izin lokasi aktif dan sinyal satelit terhubung, lalu coba lagi.',
                });
            }
            return;
        }

        // 3. Guard: GPS Accuracy Threshold (iOS Precise Location check)
        if (isGpsEnabled && userCoords.accuracy > 80) {
            window.showPermissionGuideModal('location-accuracy');
            return;
        }

        // 4. Guard: Anti-Fake GPS
        if (userCoords.isMocked || gpsStatus === 'fake_gps') {
            if (window.openFakeGpsModal) {
                window.openFakeGpsModal(userCoords.mockReason || 'Lokasi palsu terdeteksi pada perangkat');
            }
            return;
        }

        // 5. Guard: Outside Radius Check
        if (isGpsEnabled) {
            const dist = calculateDistanceM(userCoords.latitude, userCoords.longitude, targetLat, targetLng);
            if (dist > targetRadius) {
                if (isCheckoutMode) {
                    const reason = prompt(`Anda berada di luar radius lokasi kantor DU/DI (${Number(dist).toLocaleString('id-ID')} m). Tuliskan alasan kepulangan dari luar kantor (minimal 5 karakter):`);
                    if (!reason || reason.trim().length < 5) {
                        window.vtAlert({
                            tone: 'warning',
                            title: 'Alasan Wajib Diisi',
                            message: 'Presensi pulang di luar radius penugasan DU/DI wajib menyertakan alasan minimal 5 karakter.',
                        });
                        return;
                    }
                    document.getElementById('form-reason').value = reason.trim();
                    const radiusEl = document.getElementById('form-is-within-radius');
                    if (radiusEl) radiusEl.value = '0';
                } else {
                    window.vtDialog({
                        tone: 'danger',
                        eyebrow: 'Presensi ditolak',
                        title: 'Anda di luar radius DU/DI',
                        message: 'Presensi selfie hanya berlaku jika Anda hadir langsung di lokasi penugasan.',
                        details: [
                            ['Jarak Anda', `${Number(dist).toLocaleString('id-ID')} m`],
                            ['Batas toleransi', `${Number(targetRadius).toLocaleString('id-ID')} m`],
                        ],
                        hint: 'Sedang ditugaskan di luar kantor (Dinas Luar / WFH)? Ajukan lewat menu "Dinas Luar" di Beranda.',
                        confirmText: 'Kembali ke Beranda',
                        cancelText: 'Tetap di sini',
                    }).then((ok) => {
                        if (ok) window.location.href = "{{ route('student.home') }}";
                    });
                    return;
                }
            } else {
                const radiusEl = document.getElementById('form-is-within-radius');
                if (radiusEl) radiusEl.value = '1';
            }
        }

        // Visual flash feedback
        shutterBtn.classList.add('scale-75', 'opacity-50');
        setTimeout(() => shutterBtn.classList.remove('scale-75', 'opacity-50'), 250);

        if (isSelfieEnabled) {
            const video = document.getElementById('webcam-feed');
            const canvas = document.getElementById('snapshot-canvas');
            if (video && canvas) {
                // Kompresi di sisi klien agar server cukup menyimpan byte apa adanya (tanpa re-encode).
                // Sisi terpanjang dibatasi 800px, lalu JPEG 0.72; turunkan kualitas bila masih > ~250KB.
                const MAX_SIDE = 800;
                const srcW = video.videoWidth || 640;
                const srcH = video.videoHeight || 480;
                const scale = Math.min(1, MAX_SIDE / Math.max(srcW, srcH));
                canvas.width = Math.round(srcW * scale);
                canvas.height = Math.round(srcH * scale);
                const ctx = canvas.getContext('2d');
                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';

                if (currentFacingMode === 'user') {
                    ctx.translate(canvas.width, 0);
                    ctx.scale(-1, 1);
                }
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                const MAX_BASE64_LENGTH = 340 * 1024; // ≈ 250KB biner
                let quality = 0.72;
                let base64Photo = canvas.toDataURL('image/jpeg', quality);
                while (base64Photo.length > MAX_BASE64_LENGTH && quality > 0.4) {
                    quality -= 0.1;
                    base64Photo = canvas.toDataURL('image/jpeg', quality);
                }
                photoInput.value = base64Photo;
            }
        } else {
            if (photoInput) photoInput.value = '';
        }

        // Fill GPS Form Fields SECURELY from verified device coordinates
        const latEl = document.getElementById('form-latitude');
        const lngEl = document.getElementById('form-longitude');
        const accEl = document.getElementById('form-accuracy');
        const mockEl = document.getElementById('form-is-mocked');
        const reasonEl = document.getElementById('form-reason');

        if (latEl) latEl.value = (userCoords.latitude !== null && userCoords.latitude !== undefined) ? userCoords.latitude : '';
        if (lngEl) lngEl.value = (userCoords.longitude !== null && userCoords.longitude !== undefined) ? userCoords.longitude : '';
        if (accEl) accEl.value = (userCoords.accuracy !== null && userCoords.accuracy !== undefined) ? userCoords.accuracy : '';
        if (mockEl) mockEl.value = userCoords.isMocked ? 1 : 0;
        if (reasonEl && !reasonEl.value) {
            reasonEl.value = '';
        }

        if (isCheckoutMode) {
            const notesInput = document.getElementById('checkout-notes-input');
            const formNotes = document.getElementById('form-checkout-notes');
            if (notesInput && formNotes) {
                formNotes.value = notesInput.value.trim();
            }
        }

        // Stop camera stream
        if (videoStream) {
            videoStream.getTracks().forEach(track => track.stop());
        }

        // Disable button & show submit status with visual spinner
        shutterBtn.disabled = true;
        shutterBtn.classList.add('cursor-not-allowed', 'opacity-70');
        if (shutterCore) {
            shutterCore.innerHTML = `<svg class="animate-spin w-7 h-7 text-[#0B2B25]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`;
        }
        const promptText = document.getElementById('liveness-prompt-text');
        if (promptText) promptText.textContent = 'Memproses data presensi...';

        // Submit form
        form.submit();
    }

    // Anti-Fake GPS & Geolocation Spoofing Detector
    const AntiFakeGpsGuard = {
        sampleHistory: [],
        storageKey: 'vt_last_geo_fix',

        evaluate(pos) {
            const now = Date.now();
            const coords = (pos && pos.coords) ? pos.coords : {};
            const lat = coords.latitude;
            const lng = coords.longitude;
            const accuracy = Math.round(coords.accuracy !== undefined ? coords.accuracy : 0);

            // Vector 0: Manual / Test Query Parameter (?test_fake_gps=1 or ?mock=1)
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('test_fake_gps') === '1' || urlParams.get('mock') === '1') {
                return {
                    isMock: true,
                    reason: 'Mode Simulasi Fake GPS aktif (?test_fake_gps=1)',
                    code: 'SIMULATED_TEST'
                };
            }

            // Vector 1: Native Mock Location / Android Mock Provider flags
            const hasMockFlag = Boolean(
                pos.mocked ||
                pos.isMocked ||
                coords.mocked ||
                coords.isMock ||
                coords.isFromMockProvider
            );
            if (hasMockFlag) {
                return {
                    isMock: true,
                    reason: 'Penyedia lokasi tiruan (Mock Location Provider) terdeteksi aktif pada perangkat',
                    code: 'MOCK_PROVIDER_FLAG'
                };
            }

            // Vector 2: Zero or Negative Accuracy Sensor Anomaly
            if (accuracy <= 0 && (lat !== 0 || lng !== 0)) {
                return {
                    isMock: true,
                    reason: 'Akurasi sensor GPS tidak wajar (0 meter), terindikasi perangkat lunak tiruan',
                    code: 'ZERO_ACCURACY'
                };
            }

            // Vector 3: Automation / DevTools Webdriver Emulation
            if (navigator.webdriver) {
                return {
                    isMock: true,
                    reason: 'Otomatisasi peramban (Webdriver / DevTools sensor override) terdeteksi',
                    code: 'WEBDRIVER_AUTOMATION'
                };
            }

            // Vector 4: Null Island check (0,0)
            if (lat === 0 && lng === 0) {
                return {
                    isMock: true,
                    reason: 'Koordinat lokasi tidak valid (Null Island 0,0)',
                    code: 'NULL_ISLAND'
                };
            }

            // Vector 5: Teleportation / Impossible Speed Anomaly (Δd / Δt)
            const prevSample = this.sampleHistory.length > 0
                ? this.sampleHistory[this.sampleHistory.length - 1]
                : this.getLastKnownFromStorage();

            if (prevSample && prevSample.lat !== null && prevSample.lng !== null && prevSample.time) {
                const dtSeconds = Math.max(1, (now - prevSample.time) / 1000);
                // Only inspect if previous sample was within last 30 minutes (1800s)
                if (dtSeconds < 1800) {
                    const distJumpM = calculateDistanceM(prevSample.lat, prevSample.lng, lat, lng);
                    const speedMps = distJumpM / dtSeconds;

                    // If distance jump > 250m and speed > 45 m/s (~162 km/h), flag instant teleportation
                    if (distJumpM > 250 && speedMps > 45) {
                        const speedKmh = Math.round(speedMps * 3.6);
                        return {
                            isMock: true,
                            reason: `Lompatan posisi tidak realistis terdeteksi (${distJumpM}m dalam ${Math.round(dtSeconds)} detik, kecepatan ${speedKmh} km/jam)`,
                            code: 'TELEPORTATION'
                        };
                    }
                }
            }

            // Record clean sample to history (keep last 10 samples)
            this.sampleHistory.push({ lat, lng, accuracy, time: now });
            if (this.sampleHistory.length > 10) {
                this.sampleHistory.shift();
            }

            // Store clean sample in localStorage for cross-session teleportation guard
            this.saveLastKnownToStorage(lat, lng, now);

            return { isMock: false, reason: null, code: 'CLEAN' };
        },

        getLastKnownFromStorage() {
            try {
                const raw = localStorage.getItem(this.storageKey);
                if (raw) return JSON.parse(raw);
            } catch (e) {
                // Ignore storage read errors
            }
            return null;
        },

        saveLastKnownToStorage(lat, lng, time) {
            try {
                localStorage.setItem(this.storageKey, JSON.stringify({ lat, lng, time }));
            } catch (e) {
                // Ignore storage write errors
            }
        },

        reset() {
            this.sampleHistory = [];
        }
    };

    function handleLocationUpdate(pos) {
        const distEl = document.getElementById('selfie-distance-text');
        const radiusBadge = document.getElementById('selfie-radius-badge');
        const accEl = document.getElementById('selfie-accuracy-text');
        const locContainer = document.getElementById('location-bar-container');
        const locIcon = document.getElementById('location-bar-icon');
        const formIsMocked = document.getElementById('form-is-mocked');

        const evalResult = AntiFakeGpsGuard.evaluate(pos);
        if (evalResult.isMock) {
            userCoords.latitude = pos.coords ? pos.coords.latitude : null;
            userCoords.longitude = pos.coords ? pos.coords.longitude : null;
            userCoords.accuracy = Math.round((pos.coords && pos.coords.accuracy) ? pos.coords.accuracy : 0);
            userCoords.isMocked = 1;
            userCoords.mockReason = evalResult.reason;

            gpsStatus = 'fake_gps';
            if (formIsMocked) formIsMocked.value = '1';

            if (distEl) distEl.textContent = 'Lokasi palsu terdeteksi · Presensi diblokir';
            if (radiusBadge) {
                radiusBadge.textContent = 'Fake GPS';
                radiusBadge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-rose-500 text-white shadow-xs';
            }
            if (locContainer) locContainer.className = 'flex items-center justify-between min-h-[52px] rounded-[22px] bg-rose-950/40 px-4 py-2 border border-rose-500/40 my-1 transition-all cursor-pointer hover:bg-rose-950/50';
            if (locIcon) locIcon.setAttribute('stroke', '#FF5A4D');
            if (accEl) accEl.textContent = `±${userCoords.accuracy} m (Tiruan)`;

            updateStatusBadges();
            updateShutterVisuals();

            if (window.openFakeGpsModal) {
                window.openFakeGpsModal(evalResult.reason);
            }
            return;
        }

        // Clean real GPS fix
        userCoords.latitude = pos.coords.latitude;
        userCoords.longitude = pos.coords.longitude;
        userCoords.accuracy = Math.round(pos.coords.accuracy);
        userCoords.isMocked = 0;
        userCoords.mockReason = null;
        if (formIsMocked) formIsMocked.value = '0';

        if (accEl) accEl.textContent = `±${userCoords.accuracy} m`;

        // Check iPhone / WebKit Precise Location Threshold (80m)
        if (isGpsEnabled && userCoords.accuracy > 80) {
            gpsStatus = 'weak_accuracy';
            if (distEl) distEl.textContent = `Akurasi Rendah (±${userCoords.accuracy}m) · Ketuk Bantuan`;
            if (radiusBadge) {
                radiusBadge.textContent = 'Akurasi Lemah';
                radiusBadge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-[#FFF1D6] text-[#7A4B00] shadow-xs';
            }
            if (locContainer) locContainer.className = 'flex items-center justify-between min-h-[52px] rounded-[22px] bg-[#FFF1D6]/15 px-4 py-2 border border-[#FFF1D6]/35 my-1 transition-all cursor-pointer hover:bg-[#FFF1D6]/20';
            if (locIcon) locIcon.setAttribute('stroke', '#F59E0B');
        } else {
            gpsStatus = 'locked';
            const dist = calculateDistanceM(userCoords.latitude, userCoords.longitude, targetLat, targetLng);

            if (!isGpsEnabled) {
                if (distEl) distEl.textContent = `Bebas Radius (${dist} m dari kantor DU/DI)`;
                if (radiusBadge) {
                    radiusBadge.textContent = 'Bebas Radius';
                    radiusBadge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-[#C6F26B] text-[#0B2B25] shadow-xs';
                }
                if (locContainer) locContainer.className = 'flex items-center justify-between min-h-[52px] rounded-[22px] bg-white/[0.08] px-4 py-2 border border-white/10 my-1 transition-all cursor-pointer hover:bg-white/[0.12]';
                if (locIcon) locIcon.setAttribute('stroke', '#C6F26B');
            } else if (dist <= targetRadius) {
                if (distEl) distEl.textContent = `${dist} m dari kantor (Batas: ${targetRadius} m)`;
                if (radiusBadge) {
                    radiusBadge.textContent = 'Di Dalam Radius';
                    radiusBadge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-[#C6F26B] text-[#0B2B25] shadow-xs';
                }
                if (locContainer) locContainer.className = 'flex items-center justify-between min-h-[52px] rounded-[22px] bg-white/[0.08] px-4 py-2 border border-white/10 my-1 transition-all cursor-pointer hover:bg-white/[0.12]';
                if (locIcon) locIcon.setAttribute('stroke', '#C6F26B');
            } else {
                if (distEl) distEl.textContent = `${dist} m dari kantor (Batas: ${targetRadius} m)`;
                if (radiusBadge) {
                    radiusBadge.textContent = `Luar Radius (${dist}m)`;
                    radiusBadge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-[#FFF1D6] text-[#7A4B00] shadow-xs';
                }
                if (locContainer) locContainer.className = 'flex items-center justify-between min-h-[52px] rounded-[22px] bg-[#FFF1D6]/15 px-4 py-2 border border-[#FFF1D6]/35 my-1 transition-all cursor-pointer hover:bg-[#FFF1D6]/20';
                if (locIcon) locIcon.setAttribute('stroke', '#F59E0B');
            }
        }

        updateStatusBadges();
        updateShutterVisuals();
    }

    function handleLocationError(err) {
        const distEl = document.getElementById('selfie-distance-text');
        const radiusBadge = document.getElementById('selfie-radius-badge');
        const locContainer = document.getElementById('location-bar-container');
        const locIcon = document.getElementById('location-bar-icon');

        userCoords.latitude = null;
        userCoords.longitude = null;

        if (!isGpsEnabled) {
            gpsStatus = 'locked';
            if (distEl) distEl.textContent = 'Bebas Radius · Presensi Siap';
            if (radiusBadge) {
                radiusBadge.textContent = 'Bebas Radius';
                radiusBadge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-[#C6F26B] text-[#0B2B25] shadow-xs';
            }
            if (locContainer) locContainer.className = 'flex items-center justify-between min-h-[52px] rounded-[22px] bg-white/[0.08] px-4 py-2 border border-white/10 my-1 transition-all cursor-pointer hover:bg-white/[0.12]';
            if (locIcon) locIcon.setAttribute('stroke', '#C6F26B');
        } else if (err.code === 1) { // PERMISSION_DENIED
            gpsStatus = 'denied';
            if (distEl) distEl.textContent = 'Izin Lokasi Ditolak · Ketuk Bantuan';
            if (radiusBadge) {
                radiusBadge.textContent = 'Izin Ditolak';
                radiusBadge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide bg-rose-500 text-white shadow-xs';
            }
            if (locContainer) locContainer.className = 'flex items-center justify-between min-h-[52px] rounded-[22px] bg-rose-950/40 px-4 py-2 border border-rose-500/40 my-1 transition-all cursor-pointer hover:bg-rose-950/50';
            if (locIcon) locIcon.setAttribute('stroke', '#FF5A4D');
        } else if (err.code === 2) { // POSITION_UNAVAILABLE
            gpsStatus = 'unavailable';
            if (distEl) distEl.textContent = 'Sinyal GPS Tidak Tersedia';
        } else { // TIMEOUT
            gpsStatus = 'searching';
            if (distEl) distEl.textContent = 'Mencari sinyal satelit GPS...';
        }

        updateStatusBadges();
        updateShutterVisuals();
    }

    let locationWatchId = null;

    // Geolocation watcher with realtime distance calculation & iPhone Precise Location detection
    function watchStudentLocation() {
        const distEl = document.getElementById('selfie-distance-text');

        if (!navigator.geolocation) {
            if (!isGpsEnabled) {
                gpsStatus = 'locked';
                if (distEl) distEl.textContent = 'Bebas Radius · Presensi Siap';
            } else {
                gpsStatus = 'unavailable';
                if (distEl) distEl.textContent = 'GPS tidak didukung peramban ini';
            }
            updateStatusBadges();
            updateShutterVisuals();
            return;
        }

        if (locationWatchId !== null) {
            navigator.geolocation.clearWatch(locationWatchId);
        }

        locationWatchId = navigator.geolocation.watchPosition(
            (pos) => handleLocationUpdate(pos),
            (err) => handleLocationError(err),
            { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
        );
    }

    // Exposed helpers for modal retry and tester simulation
    window.triggerLocationCheck = function() {
        AntiFakeGpsGuard.reset();
        userCoords.isMocked = 0;
        userCoords.mockReason = null;
        gpsStatus = 'searching';

        const distEl = document.getElementById('selfie-distance-text');
        if (distEl) distEl.textContent = 'Mengalibrasi ulang sinyal GPS...';
        updateStatusBadges();
        updateShutterVisuals();

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (pos) => handleLocationUpdate(pos),
                (err) => handleLocationError(err),
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }
    };

    window.simulateFakeGps = function(reason = 'Simulasi pengujian Fake GPS aktif') {
        const dummyPos = {
            coords: {
                latitude: targetLat,
                longitude: targetLng,
                accuracy: 0,
                mocked: true
            },
            mocked: true
        };
        handleLocationUpdate(dummyPos);
    };

    document.addEventListener('DOMContentLoaded', function() {
        @if(!$todayAttendance || $isCheckoutMode)
            startLiveCamera();
            watchStudentLocation();

            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('test_fake_gps') === '1' || urlParams.get('mock') === '1') {
                window.simulateFakeGps('Mode Uji / Simulasi Deteksi Fake GPS Aktif (?test_fake_gps=1)');
            } else {
                updateStatusBadges();
                updateShutterVisuals();
            }
        @endif
    });
</script>
@endpush
@endsection
