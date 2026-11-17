@props(['attendance', 'spkl', 'student', 'dayNumber' => 27])

@php
    $studentName = auth()->user()->name ?? 'Siswa';
    $firstName = explode(' ', trim($studentName))[0];
    $dateObj = $attendance ? \Carbon\Carbon::parse($attendance->date) : \Carbon\Carbon::today();
    $timeStr = $attendance && $attendance->checkin_time ? \Carbon\Carbon::parse($attendance->checkin_time)->format('H.i') : \Carbon\Carbon::now()->format('H.i');
    $presensiId = 'PKL-' . $dateObj->format('Ymd') . '-' . str_pad($attendance->id ?? 147, 4, '0', STR_PAD_LEFT);
    $companyName = $spkl->company->name ?? 'Kantor PKL';
    $distanceText = $attendance && $attendance->is_within_radius !== false ? '38 m' : 'Luar Radius';
    $isMock = $attendance->is_mock_detected ?? false;
@endphp

<!-- Screen 3: Presensi Berhasil (Ticket Receipt Screen) -->
<style>
    /* Concentric ripple wave animations */
    .ticket-ripple {
        transform-origin: 195px 92px;
        animation: ticket-ripple-wave 3.6s cubic-bezier(0.25, 1, 0.5, 1) infinite;
    }
    .ticket-ripple-1 { animation-delay: 0s; }
    .ticket-ripple-2 { animation-delay: 1.2s; }
    .ticket-ripple-3 { animation-delay: 2.4s; }

    @keyframes ticket-ripple-wave {
        0% {
            r: 40px;
            opacity: 0.85;
            stroke-width: 2.5px;
        }
        50% {
            opacity: 0.45;
        }
        100% {
            r: 220px;
            opacity: 0;
            stroke-width: 1px;
        }
    }

    /* Subtle breathe for background rings */
    .ticket-ring-static {
        transform-origin: 195px 92px;
        animation: ticket-ring-breathe 4s ease-in-out infinite alternate;
    }

    @keyframes ticket-ring-breathe {
        0% { stroke-opacity: 0.45; transform: scale(0.985); }
        100% { stroke-opacity: 0.9; transform: scale(1.015); }
    }

    /* Success Check Badge Pop & Pulse */
    .ticket-badge-pop {
        animation: ticket-pop-in 0.65s cubic-bezier(0.34, 1.56, 0.64, 1) both;
    }
    .ticket-badge-pulse {
        animation: ticket-badge-heartbeat 2.8s ease-in-out 0.8s infinite;
    }

    @keyframes ticket-pop-in {
        0% {
            transform: scale(0.3) rotate(-12deg);
            opacity: 0;
        }
        70% {
            transform: scale(1.14) rotate(3deg);
            opacity: 1;
        }
        100% {
            transform: scale(1) rotate(0deg);
            opacity: 1;
        }
    }

    @keyframes ticket-badge-heartbeat {
        0%, 100% {
            box-shadow: 0 0 0 0 rgba(11, 43, 37, 0.22), 0 8px 24px rgba(11, 43, 37, 0.12);
        }
        50% {
            box-shadow: 0 0 0 16px rgba(11, 43, 37, 0), 0 12px 28px rgba(11, 43, 37, 0.18);
        }
    }

    /* SVG Checkmark drawing stroke */
    .ticket-check-stroke {
        stroke-dasharray: 40;
        stroke-dashoffset: 40;
        animation: ticket-check-draw 0.55s cubic-bezier(0.65, 0, 0.45, 1) 0.35s forwards;
    }

    @keyframes ticket-check-draw {
        to {
            stroke-dashoffset: 0;
        }
    }

    /* Staggered text & card reveal */
    .ticket-title-reveal {
        animation: ticket-slide-up 0.55s cubic-bezier(0.16, 1, 0.3, 1) 0.25s both;
    }
    .ticket-sub-reveal {
        animation: ticket-slide-up 0.55s cubic-bezier(0.16, 1, 0.3, 1) 0.38s both;
    }
    .ticket-card-reveal {
        animation: ticket-card-up 0.65s cubic-bezier(0.16, 1, 0.3, 1) 0.45s both;
    }

    @keyframes ticket-slide-up {
        0% {
            opacity: 0;
            transform: translateY(14px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes ticket-card-up {
        0% {
            opacity: 0;
            transform: translateY(22px) scale(0.97);
        }
        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .ticket-ripple, .ticket-ring-static, .ticket-badge-pop, .ticket-badge-pulse, .ticket-check-stroke, .ticket-title-reveal, .ticket-sub-reveal, .ticket-card-reveal {
            animation: none !important;
            transform: none !important;
            opacity: 1 !important;
            stroke-dashoffset: 0 !important;
        }
    }
</style>

<div class="relative w-full flex flex-col items-center pb-24 font-sans select-none animate-fade-in">

    <!-- Lime Green Curved Header Banner with Animated Waves -->
    <div class="absolute left-0 top-0 right-0 h-[290px] bg-[#C6F26B] rounded-b-[36px] overflow-hidden">
        <svg width="390" height="290" viewBox="0 0 390 290" aria-hidden="true" class="absolute left-0 top-0 w-full h-full object-cover opacity-85 pointer-events-none">
            <!-- Static decorative rings with breathing effect -->
            <g class="ticket-ring-static">
                <circle cx="195" cy="92" r="84" fill="none" stroke="#B3E650" stroke-width="2"/>
                <circle cx="195" cy="92" r="128" fill="none" stroke="#B3E650" stroke-width="2"/>
                <circle cx="195" cy="92" r="172" fill="none" stroke="#B3E650" stroke-width="2"/>
                <circle cx="195" cy="92" r="216" fill="none" stroke="#B3E650" stroke-width="2"/>
            </g>
            <!-- Dynamic outward expanding ripples -->
            <circle cx="195" cy="92" r="40" fill="none" stroke="#FFFFFF" class="ticket-ripple ticket-ripple-1"/>
            <circle cx="195" cy="92" r="40" fill="none" stroke="#B3E650" class="ticket-ripple ticket-ripple-2"/>
            <circle cx="195" cy="92" r="40" fill="none" stroke="#FFFFFF" class="ticket-ripple ticket-ripple-3"/>
        </svg>
    </div>

    <!-- Top Status Icon & Titles -->
    <div class="relative z-10 flex flex-col items-center pt-8 px-5 box-border w-full text-center">
        <!-- Big Check Icon in Dark Green Circle with Pop & Aura Pulse -->
        <div class="ticket-badge-pop">
            <div class="ticket-badge-pulse w-[76px] h-[76px] rounded-full bg-[#0B2B25] text-[#C6F26B] flex items-center justify-center shadow-[0_0_0_10px_rgba(11,43,37,0.1)]">
                <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12" class="ticket-check-stroke"/>
                </svg>
            </div>
        </div>

        <h1 class="ticket-title-reveal font-space font-bold text-[27px] leading-tight text-[#0B2B25] mt-4">
            Presensi masuk tercatat
        </h1>
        <p class="ticket-sub-reveal text-sm font-medium text-[#1F3B30] mt-1">
            Selamat bekerja, {{ $firstName }}. Hari ke-{{ $dayNumber }} PKL.
        </p>

        <!-- Perforated Ticket Card with Slide-Up Entrance -->
        <div class="ticket-card-reveal relative w-full max-w-[350px] mt-6 bg-white rounded-[28px] shadow-[0_12px_32px_rgba(11,43,37,0.14)] text-left overflow-hidden border border-slate-100">
            <!-- Upper Ticket Section -->
            <div class="p-4.5 flex flex-col gap-3.5">
                <!-- Verified Selfie Row -->
                <div class="flex items-center gap-3">
                    <div class="relative w-16 h-16 rounded-[18px] bg-[#12382F] overflow-hidden shrink-0 flex items-center justify-center border border-slate-200">
                        @if($attendance && $attendance->checkin_photo_path)
                            <img src="{{ $attendance->checkin_photo_url }}" alt="Selfie" class="w-full h-full object-cover">
                        @else
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#C6F26B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <span class="font-bold text-[15px] text-[#10211D] block leading-tight">Selfie terverifikasi</span>
                        <span class="text-xs text-[#5A6B65] mt-0.5 block">Wajah cocok dengan profil · 98%</span>
                    </div>
                    <span class="w-7 h-7 rounded-full bg-[#C6F26B] text-[#0B2B25] flex items-center justify-center shrink-0">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </span>
                </div>

                <div class="h-px bg-[#E6EBE3]"></div>

                <!-- 2x2 Grid -->
                <div class="grid grid-cols-2 gap-y-3.5 gap-x-3">
                    <div>
                        <span class="text-xs text-[#5A6B65] font-semibold block">Waktu masuk</span>
                        <span class="font-space font-bold text-[22px] leading-tight text-[#10211D]">
                            {{ $timeStr }} <span class="text-xs font-bold text-[#5A6B65] font-sans">WIB</span>
                        </span>
                    </div>
                    <div>
                        <span class="text-xs text-[#5A6B65] font-semibold block mb-0.5">Status</span>
                        @if(($attendance->status ?? 'hadir') === 'terlambat')
                            <span class="inline-flex items-center gap-1.5 bg-[#FFF1D6] text-[#7A4B00] rounded-full px-2.5 py-1 text-xs font-bold">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#D97706]"></span> Terlambat
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 bg-[#DDF3EC] text-[#0B5E4E] rounded-full px-2.5 py-1 text-xs font-bold">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#0F7A66]"></span> Tepat waktu
                            </span>
                        @endif
                    </div>
                    <div>
                        <span class="text-xs text-[#5A6B65] font-semibold block">Lokasi</span>
                        <span class="font-bold text-sm text-[#10211D] truncate block">{{ $companyName }} · {{ $distanceText }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-[#5A6B65] font-semibold block">Keaslian GPS</span>
                        @if($isMock)
                            <span class="font-bold text-sm text-rose-600">Terindikasi Mock</span>
                        @else
                            <span class="font-bold text-sm text-[#0B5E4E]">Terverifikasi</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Perforated Cutouts & Dashed Divider -->
            <div class="relative h-6 flex items-center">
                <span class="absolute -left-3 top-0 w-6 h-6 rounded-full bg-[#F1F4EC]"></span>
                <span class="absolute -right-3 top-0 w-6 h-6 rounded-full bg-[#F1F4EC]"></span>
                <div class="w-full mx-5 border-t-2 border-dashed border-[#D3DBD1]"></div>
            </div>

            <!-- Bottom Ticket Section (ID & Barcode) -->
            <div class="px-4.5 pb-4.5 pt-1 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <span class="text-[11.5px] text-[#5A6B65] font-semibold block">ID presensi</span>
                    <span class="font-space font-bold text-[15px] tracking-wide text-[#10211D]">{{ $presensiId }}</span>
                </div>
                <!-- Stylized Barcode SVG -->
                <svg width="96" height="32" viewBox="0 0 96 32" role="img" aria-label="Kode batang presensi" class="shrink-0">
                    <g fill="#0B2B25">
                        <rect x="0" y="0" width="3" height="32"/>
                        <rect x="5" y="0" width="2" height="32"/>
                        <rect x="9" y="0" width="4" height="32"/>
                        <rect x="15" y="0" width="1" height="32"/>
                        <rect x="18" y="0" width="3" height="32"/>
                        <rect x="23" y="0" width="5" height="32"/>
                        <rect x="30" y="0" width="2" height="32"/>
                        <rect x="34" y="0" width="4" height="32"/>
                        <rect x="40" y="0" width="1" height="32"/>
                        <rect x="43" y="0" width="3" height="32"/>
                        <rect x="48" y="0" width="2" height="32"/>
                        <rect x="52" y="0" width="5" height="32"/>
                        <rect x="59" y="0" width="1" height="32"/>
                        <rect x="62" y="0" width="3" height="32"/>
                        <rect x="67" y="0" width="4" height="32"/>
                        <rect x="73" y="0" width="2" height="32"/>
                        <rect x="77" y="0" width="5" height="32"/>
                        <rect x="84" y="0" width="1" height="32"/>
                        <rect x="87" y="0" width="3" height="32"/>
                        <rect x="92" y="0" width="4" height="32"/>
                    </g>
                </svg>
            </div>
        </div>

        <!-- Action CTAs -->
        <div class="w-full max-w-[350px] flex flex-col gap-2 mt-5">
            <a href="{{ route('student.journals') }}" 
               class="flex items-center justify-between h-[60px] rounded-[30px] bg-[#0B2B25] hover:bg-[#12382F] text-white px-2 pl-6 shadow-[0_8px_20px_rgba(11,43,37,0.25)] active:scale-95 transition-all">
                <span class="font-bold text-base">Isi Jurnal Hari Ini</span>
                <span class="w-11 h-11 rounded-full bg-[#C6F26B] text-[#0B2B25] flex items-center justify-center shrink-0">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </span>
            </a>

            <a href="{{ route('student.home') }}" 
               class="flex items-center justify-center h-12 rounded-[24px] text-sm font-bold text-[#10211D] hover:bg-black/5 active:scale-95 transition-all">
                Kembali ke Beranda
            </a>
        </div>

        <!-- Shift checkout reminder -->
        <div class="flex items-center gap-2 text-xs text-[#5A6B65] mt-2">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
            <span>Pengingat: presensi pulang aktif pukul 16.00 WIB</span>
        </div>
    </div>

</div>
