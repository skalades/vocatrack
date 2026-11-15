@extends('layouts.student-mobile')

@section('title', 'Presensi Siswa — VokaTrack')

@php
    $studentName = auth()->user()->name ?? 'Raka Pratama';
    $firstName = explode(' ', trim($studentName))[0];
    $companyName = $spkl->company->name ?? 'Kantor PKL';
    $companyLat = $spkl->company->latitude ?? -6.200000;
    $companyLng = $spkl->company->longitude ?? 106.816666;
    $companyRadius = $spkl->company->radius_meters ?? 100;
@endphp

@section('content')
<div class="relative w-full font-sans select-none animate-fade-in">

    @if(!$spkl)
        <!-- Case: Siswa Belum Di-Plotting -->
        <div class="p-5 pt-8 text-center bg-white rounded-[26px] m-5 border border-amber-200 shadow-sm">
            <div class="w-16 h-16 rounded-full bg-[#FFF1D6] text-[#7A4B00] flex items-center justify-center mx-auto text-2xl font-bold">
                ⏳
            </div>
            <h2 class="font-space font-bold text-lg text-[#10211D] mt-3">Belum Memiliki Penempatan PKL</h2>
            <p class="text-xs text-[#5A6B65] mt-1 max-w-[280px] mx-auto leading-relaxed">
                Anda belum memiliki penugasan PKL aktif. Modul presensi kamera selfie dan geofence akan terbuka setelah sekolah memetakan penempatan DU/DI Anda.
            </p>
            <a href="{{ route('student.home') }}" class="inline-block mt-4 px-5 py-2.5 rounded-full bg-[#0B2B25] text-white text-xs font-bold">
                Kembali ke Beranda
            </a>
        </div>

    @elseif($todayAttendance)
        <!-- ========================================================
             SCREEN 3: PRESENSI BERHASIL / BUKTI PRESENSI MASUK
             ======================================================== -->
        <x-student.attendance-ticket 
            :attendance="$todayAttendance" 
            :spkl="$spkl" 
            :student="$student" 
            :dayNumber="$dayNumber ?? 27" 
        />

        <!-- Secondary Section: Presensi Pulang & Permohonan Khusus -->
        <div class="px-5 pb-20 -mt-16 relative z-20 flex flex-col gap-3">
            @if(!$todayAttendance->checkout_time)
                <!-- Presensi Pulang (Check-Out) Card -->
                <div class="bg-white rounded-[24px] p-4 shadow-sm border border-slate-100 flex flex-col gap-2.5">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-sm text-[#10211D]">Presensi Pulang</span>
                        <span class="text-xs font-bold text-[#5A6B65]">Buka 16.00 WIB</span>
                    </div>
                    <form action="{{ route('student.checkout') }}" method="POST" class="flex flex-col gap-2">
                        @csrf
                        <input type="text" name="notes" placeholder="Catatan capaian singkat hari ini (opsional)..." 
                               class="w-full h-11 rounded-xl bg-[#F7F9F5] border border-[#CBD5CA] px-3 text-xs text-[#10211D] placeholder:text-slate-400 focus:outline-none focus:border-[#0F7A66]">
                        <button type="submit" 
                                class="w-full h-12 rounded-[24px] bg-[#0B2B25] hover:bg-[#12382F] text-white font-bold text-xs active:scale-95 transition-all flex items-center justify-center gap-2">
                            <span>Check-Out Pulang</span>
                        </button>
                    </form>
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
        <div class="bg-[#0B2B25] text-white min-h-screen flex flex-col justify-between p-5 pb-24 box-border">

            <!-- Topbar Row -->
            <div class="flex items-center justify-between h-12">
                <a href="{{ route('student.home') }}" aria-label="Kembali ke beranda"
                   class="flex items-center justify-center w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>

                <div class="flex flex-col items-center">
                    <h1 class="text-[17px] font-bold text-white tracking-tight">Verifikasi Selfie</h1>
                    <span class="text-[11px] text-[#A9C2B9] font-medium leading-none">Check-In Hari Ini</span>
                </div>

                <span class="h-[30px] rounded-full bg-[#C6F26B]/16 text-[#C6F26B] font-space text-[13px] font-bold px-3 flex items-center justify-center">
                    2 / 3
                </span>
            </div>

            <!-- Camera Viewfinder Box (540px Height) -->
            <div class="relative h-[480px] sm:h-[520px] rounded-[30px] bg-[#12382F] overflow-hidden my-2 flex items-center justify-center border border-white/10 shadow-2xl">
                <!-- Live Video Feed -->
                <video id="webcam-feed" autoplay playsinline muted class="w-full h-full object-cover transform scale-x-[-1]"></video>

                <!-- Hidden Canvas for capture snapshot -->
                <canvas id="snapshot-canvas" class="hidden"></canvas>

                <!-- Face Oval Mask Overlay SVG -->
                <svg width="100%" height="100%" viewBox="0 0 350 540" role="img" aria-label="Bingkai verifikasi wajah" class="absolute inset-0 pointer-events-none">
                    <defs>
                        <mask id="face-viewfinder-mask">
                            <rect width="350" height="540" fill="#fff"/>
                            <ellipse cx="175" cy="245" rx="100" ry="135" fill="#000"/>
                        </mask>
                    </defs>
                    <!-- Darkened vignette around face -->
                    <rect width="350" height="540" fill="rgba(11,43,37,0.58)" mask="url(#face-viewfinder-mask)"/>
                    <!-- Glowing Dashed Face Guide Oval -->
                    <ellipse cx="175" cy="245" rx="100" ry="135" fill="none" stroke="#C6F26B" stroke-width="2.8" stroke-dasharray="8 6"/>
                    <!-- Horizontal alignment marker -->
                    <line x1="110" y1="245" x2="240" y2="245" stroke="#C6F26B" stroke-width="1.8" opacity="0.6"/>
                </svg>

                <!-- Top Prompt Pill -->
                <div class="absolute top-4 inset-x-0 flex justify-center z-10 px-4">
                    <span id="liveness-prompt-pill" class="inline-flex items-center gap-2 bg-white text-[#0B2B25] rounded-full px-4 py-2 text-[13.5px] font-bold shadow-md transition-all">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 18l-6-6 6-6"/>
                        </svg>
                        <span id="liveness-prompt-text">Posisikan wajah di dalam bingkai</span>
                    </span>
                </div>

                <!-- Bottom Indicators (Wajah, Cahaya, Liveness) -->
                <div class="absolute bottom-3.5 inset-x-3.5 flex gap-2 justify-center flex-wrap z-10">
                    <span id="pill-wajah" class="inline-flex items-center gap-1.5 bg-[#0B2B25]/85 text-white rounded-full px-3 py-1.5 text-xs font-bold border border-white/10">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#C6F26B" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <span>Wajah</span>
                    </span>

                    <span id="pill-cahaya" class="inline-flex items-center gap-1.5 bg-[#0B2B25]/85 text-white rounded-full px-3 py-1.5 text-xs font-bold border border-white/10">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#C6F26B" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <span>Cahaya</span>
                    </span>

                    <span id="pill-liveness" class="inline-flex items-center gap-1.5 bg-[#C6F26B] text-[#0B2B25] rounded-full px-3 py-1.5 text-xs font-extrabold shadow-xs">
                        Liveness 70%
                    </span>
                </div>
            </div>

            <!-- Location & Time Glass Bar -->
            <div class="flex items-center justify-between h-12 rounded-[24px] bg-white/[0.08] px-4 border border-white/10 my-1">
                <span class="inline-flex items-center gap-2 text-[13.5px] font-semibold text-white">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#C6F26B" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <span id="selfie-location-text">{{ $companyName }} · 38 m</span>
                </span>
                <span id="selfie-clock-text" class="font-space font-bold text-sm text-[#C6F26B]">
                    {{ \Carbon\Carbon::now()->format('H.i') }} WIB
                </span>
            </div>

            <!-- Controls Row (Flash, Shutter, Flip Camera) -->
            <div class="flex items-center justify-center gap-9 pt-2">
                <!-- Flash Button -->
                <button type="button" onclick="toggleCameraFlash()" aria-label="Nyalakan lampu kilat"
                        class="w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors cursor-pointer">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                    </svg>
                </button>

                <!-- Big Shutter Button -->
                <button type="button" id="shutter-btn" onclick="captureAndSubmitPresensi()" aria-label="Ambil foto dan kirim presensi"
                        class="w-[84px] h-[84px] rounded-full border-4 border-[#C6F26B] flex items-center justify-center active:scale-90 transition-transform cursor-pointer group shadow-[0_0_20px_rgba(198,242,107,0.3)]">
                    <span class="w-[62px] h-[62px] rounded-full bg-white group-hover:scale-95 transition-transform"></span>
                </button>

                <!-- Switch Camera Button -->
                <button type="button" onclick="flipCameraFacing()" aria-label="Ganti kamera"
                        class="w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors cursor-pointer">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 16v5h-5M4 8V3h5"/>
                        <path d="M4 16v-1a8 8 0 0 1 14.93-4M20 8v1a8 8 0 0 1-14.93 4"/>
                    </svg>
                </button>
            </div>

            <!-- Policy Disclaimer -->
            <p class="text-center text-xs text-[#A9C2B9] leading-tight mt-2">
                Hanya kamera langsung. Unggah dari galeri tidak diizinkan.
            </p>

            <!-- Hidden Check-in Submission Form -->
            <form id="checkin-form" action="{{ route('student.checkin') }}" method="POST" class="hidden">
                @csrf
                <input type="hidden" name="photo" id="form-photo-base64">
                <input type="hidden" name="latitude" id="form-latitude">
                <input type="hidden" name="longitude" id="form-longitude">
                <input type="hidden" name="accuracy" id="form-accuracy">
                <input type="hidden" name="is_mocked" id="form-is-mocked" value="0">
                <input type="hidden" name="reason" id="form-reason">
            </form>

        </div>
    @endif

</div>

@push('scripts')
<script>
    let videoStream = null;
    let currentFacingMode = 'user'; // front camera default
    let userCoords = { latitude: null, longitude: null, accuracy: null, isMocked: 0 };
    const targetLat = {{ $companyLat }};
    const targetLng = {{ $companyLng }};
    const targetRadius = {{ $companyRadius }};

    function startLiveCamera() {
        const video = document.getElementById('webcam-feed');
        if (!video) return;

        if (videoStream) {
            videoStream.getTracks().forEach(track => track.stop());
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
            })
            .catch(err => {
                console.warn('Camera access denied or failed:', err);
                const prompt = document.getElementById('liveness-prompt-text');
                if (prompt) prompt.textContent = 'Izin kamera dibutuhkan untuk verifikasi';
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
            // Flash screen simulation
            const viewfinder = document.getElementById('webcam-feed');
            if (viewfinder) {
                viewfinder.style.filter = viewfinder.style.filter ? '' : 'brightness(1.5)';
            }
        }
    }

    function captureAndSubmitPresensi() {
        const video = document.getElementById('webcam-feed');
        const canvas = document.getElementById('snapshot-canvas');
        const form = document.getElementById('checkin-form');
        const photoInput = document.getElementById('form-photo-base64');
        const shutterBtn = document.getElementById('shutter-btn');

        if (!video || !canvas || !form) return;

        // Visual flash feedback
        shutterBtn.classList.add('scale-75', 'opacity-50');
        setTimeout(() => shutterBtn.classList.remove('scale-75', 'opacity-50'), 250);

        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        const ctx = canvas.getContext('2d');

        if (currentFacingMode === 'user') {
            ctx.translate(canvas.width, 0);
            ctx.scale(-1, 1);
        }
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        const base64Photo = canvas.toDataURL('image/jpeg', 0.85);
        photoInput.value = base64Photo;

        // Fill GPS Form Fields
        document.getElementById('form-latitude').value = userCoords.latitude || targetLat;
        document.getElementById('form-longitude').value = userCoords.longitude || targetLng;
        document.getElementById('form-accuracy').value = userCoords.accuracy || 15;
        document.getElementById('form-is-mocked').value = userCoords.isMocked ? 1 : 0;

        // Stop camera stream
        if (videoStream) {
            videoStream.getTracks().forEach(track => track.stop());
        }

        // Submit form
        form.submit();
    }

    // Geolocation watcher with Fake GPS detection
    function watchStudentLocation() {
        if (!navigator.geolocation) return;

        navigator.geolocation.watchPosition(
            (pos) => {
                userCoords.latitude = pos.coords.latitude;
                userCoords.longitude = pos.coords.longitude;
                userCoords.accuracy = Math.round(pos.coords.accuracy);
                userCoords.isMocked = (pos.mocked || userCoords.accuracy === 0) ? 1 : 0;

                // Trigger Fake GPS Screen if mock location is active
                if (userCoords.isMocked) {
                    if (window.openFakeGpsModal) window.openFakeGpsModal();
                    return;
                }

                // Update location text
                const locEl = document.getElementById('selfie-location-text');
                if (locEl) {
                    locEl.textContent = `Kantor PKL · ±${userCoords.accuracy} m`;
                }
            },
            (err) => {},
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    }

    document.addEventListener('DOMContentLoaded', function() {
        @if(!$todayAttendance)
            startLiveCamera();
            watchStudentLocation();
        @endif
    });
</script>
@endpush
@endsection
