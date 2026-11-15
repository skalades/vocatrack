@php
    $studentUser = auth()->user();
    $activeSos = null;
    if ($studentUser && $studentUser->student) {
        $spklIds = $studentUser->student->studentPkls()->pluck('id');
        $activeSos = \App\Models\IncidentReport::whereIn('student_pkl_id', $spklIds)
            ->whereIn('status', ['submitted', 'in_progress'])
            ->with(['followups.user', 'studentPkl.company', 'studentPkl.teacher.user'])
            ->latest()
            ->first();
    }
@endphp

<style>
    .sos-pulse-wave {
        position: absolute;
        top: 50%; left: 50%;
        width: 164px; height: 164px;
        margin: -82px 0 0 -82px;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.35);
        pointer-events: none;
        animation: sos-wave 2.4s cubic-bezier(0.2, 0.6, 0.35, 1) infinite;
    }
    @keyframes sos-wave {
        0%   { transform: scale(1);    opacity: .55; }
        100% { transform: scale(1.82); opacity: 0; }
    }
    .sos-heartbeat { animation: sos-heartbeat 1.6s ease-in-out infinite; }
    .sos-heartbeat:hover, .sos-heartbeat:active { animation: none; }
    @keyframes sos-heartbeat {
        0%, 100% { transform: scale(1);    box-shadow: 0 14px 34px rgba(60,6,2,.45), 0 0 0 0 rgba(255,255,255,.45); }
        15%      { transform: scale(1.06); }
        30%      { transform: scale(.98); }
        45%      { transform: scale(1.04); box-shadow: 0 14px 34px rgba(60,6,2,.45), 0 0 0 18px rgba(255,255,255,0); }
        60%      { transform: scale(1); }
    }
    @media (prefers-reduced-motion: reduce) {
        .sos-pulse-wave, .sos-heartbeat { animation: none; }
    }
</style>

<!-- Screen 6: Bantuan Darurat SOS (Full-screen Interactive Emergency Modal) -->
<div id="sos-emergency-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-[#B42318] text-white font-sans transition-all duration-300">
    <div class="relative w-full max-w-[420px] min-h-screen mx-auto flex flex-col justify-between p-5 box-border">

        @if($activeSos)
            <!-- ==========================================
                 PHASE 0: MONITORING SOS AKTIF (LIVE STATUS & FOLLOW-UP)
                 ========================================== -->
            <div id="sos-phase-active" class="flex flex-col gap-3.5">
                <!-- Header -->
                <div class="flex items-center justify-between h-12">
                    <button type="button" onclick="window.closeSosModal()" aria-label="Tutup Bantuan Darurat" 
                            class="flex items-center justify-center w-11 h-11 rounded-full bg-white/15 hover:bg-white/25 text-white transition-colors cursor-pointer">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18M6 6l12 12"/>
                        </svg>
                    </button>
                    <div class="flex flex-col items-center">
                        <h1 class="text-lg font-space font-bold tracking-tight">Status Darurat Aktif</h1>
                        <span class="text-[11px] text-[#FFE1DD] font-medium leading-none">Sedang Dipantau</span>
                    </div>
                    <span class="w-11"></span>
                </div>

                <!-- Active SOS Status Card (White Card) -->
                <div class="bg-white text-[#10211D] rounded-[24px] p-4.5 shadow-xl flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-2 pb-2.5 border-b border-slate-100">
                        <div>
                            <div class="flex items-center gap-1.5 mb-1 flex-wrap">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold {{ $activeSos->status === 'in_progress' ? 'bg-amber-100 text-amber-900 border border-amber-300/80' : 'bg-rose-100 text-rose-900 border border-rose-300/80' }}">
                                    <span class="w-2 h-2 rounded-full {{ $activeSos->status === 'in_progress' ? 'bg-amber-600 animate-pulse' : 'bg-rose-600 animate-pulse' }}"></span>
                                    <span>{{ $activeSos->status === 'in_progress' ? 'Sedang Ditindaklanjuti' : 'Menunggu Respon Guru' }}</span>
                                </span>
                                <span class="text-[10.5px] text-slate-400 font-medium">
                                    {{ $activeSos->created_at->diffForHumans() }}
                                </span>
                            </div>
                            <h2 class="text-base font-black text-slate-900 leading-snug">
                                {{ $activeSos->subject }}
                            </h2>
                            <span class="text-[11px] text-slate-500 font-medium block mt-0.5">
                                {{ $activeSos->studentPkl->company->name ?? 'Mitra DU/DI' }} · Guru: {{ $activeSos->studentPkl->teacher->user->name ?? 'Guru Pembimbing' }}
                            </span>
                        </div>
                    </div>

                    <p class="text-xs text-slate-600 leading-relaxed font-medium bg-slate-50 p-3 rounded-2xl border border-slate-100">
                        {{ $activeSos->description }}
                    </p>

                    <!-- Timeline Catatan Tindak Lanjut Guru -->
                    <div class="space-y-2 pt-1">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">
                            Catatan & Arahan Penanganan Guru:
                        </span>

                        @if($activeSos->followups->isEmpty())
                            <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200/80 text-xs text-amber-900 flex items-center gap-2.5">
                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping shrink-0"></span>
                                <span class="text-[11.5px] leading-relaxed">
                                    Notifikasi darurat telah disiarkan ke kontak darurat. Guru pembimbing sedang memeriksa keadaan Anda.
                                </span>
                            </div>
                        @else
                            <div class="space-y-2 max-h-[170px] overflow-y-auto pr-1">
                                @foreach($activeSos->followups as $f)
                                    @php $isStaff = $f->user_id !== $studentUser->id; @endphp
                                    <div class="p-3 rounded-2xl {{ $isStaff ? 'bg-[#DDF3EC]/90 border border-[#A3DFC8] text-slate-800' : 'bg-slate-100 border border-slate-200 text-slate-700 ml-3' }} text-xs space-y-1">
                                        <div class="flex items-center justify-between text-[10px]">
                                            <span class="font-extrabold flex items-center gap-1 {{ $isStaff ? 'text-[#0B5E4E]' : 'text-slate-600' }}">
                                                @if($isStaff)
                                                    <svg class="w-3 h-3 text-[#0B5E4E]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                    <span>{{ $f->user->name }} (Guru Pembimbing)</span>
                                                @else
                                                    <span>Anda (Siswa)</span>
                                                @endif
                                            </span>
                                            <span class="text-slate-400 font-mono text-[9.5px]">{{ $f->created_at->format('H.i') }} WIB</span>
                                        </div>
                                        <p class="font-medium leading-relaxed pl-1 text-[11.5px]">
                                            {{ $f->action_note }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Form Kirim Update Kondisi Siswa -->
                    <form action="{{ route('student.incident.followup', $activeSos->id) }}" method="POST" class="pt-2 border-t border-slate-100 flex flex-col gap-1.5">
                        @csrf
                        <label class="text-[11px] font-bold text-slate-700 flex items-center justify-between">
                            <span>Update Kondisi Anda:</span>
                            <span class="text-[10px] text-slate-400">Terkirim langsung ke guru</span>
                        </label>
                        <div class="flex items-center gap-1.5">
                            <input type="text" name="action_note" required placeholder="Tulis update terbaru (misal: sudah di klinik)..." 
                                   class="flex-1 h-10 rounded-xl bg-slate-100 border border-slate-200 px-3 text-xs text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-[#0F7A66] focus:bg-white">
                            <button type="submit" class="h-10 px-3.5 rounded-xl bg-[#0B2B25] hover:bg-[#12382F] text-white text-xs font-bold active:scale-95 transition-all shrink-0 cursor-pointer">
                                Kirim
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Emergency Actions & Selesaikan -->
                <div class="flex flex-col gap-2 mt-1">
                    <a href="tel:112" class="w-full h-12 rounded-[24px] bg-white text-[#B42318] font-extrabold text-xs flex items-center justify-center gap-2 shadow-md active:scale-95 transition-all">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                        <span>Telepon 112 (Panggilan Darurat)</span>
                    </a>

                    <form action="{{ route('student.incident.followup', $activeSos->id) }}" method="POST"
                          data-confirm="Guru pembimbing akan diberi tahu bahwa situasi darurat sudah selesai." data-confirm-title="Anda sudah aman?" data-confirm-ok="Ya, saya sudah aman" data-confirm-tone="info">
                        @csrf
                        <input type="hidden" name="action_note" value="Siswa telah mengonfirmasi bahwa keadaan sudah aman dan terkendali.">
                        <input type="hidden" name="status" value="resolved">
                        <button type="submit"
                                class="w-full h-11 rounded-[22px] bg-white/20 hover:bg-white/30 text-white font-bold text-xs active:scale-95 transition-all cursor-pointer">
                            ✓ Saya Sudah Aman (Tandai Selesai)
                        </button>
                    </form>

                    <button type="button" onclick="switchToNewSosPhase()" 
                            class="w-full py-1 text-center text-xs font-semibold text-white/70 hover:text-white underline cursor-pointer">
                        Butuh Kirim Sinyal SOS Baru?
                    </button>
                </div>
            </div>
        @endif

        <!-- ==========================================
             PHASE 1: IDLE / SELECT CATEGORY & PRESS
             ========================================== -->
        <div id="sos-phase-idle" class="{{ $activeSos ? 'hidden' : 'flex' }} flex-col gap-3">
            <!-- Header -->
            <div class="flex items-center justify-between h-12">
                <button type="button" onclick="window.closeSosModal()" aria-label="Tutup Bantuan Darurat" 
                        class="flex items-center justify-center w-11 h-11 rounded-full bg-white/15 hover:bg-white/25 text-white transition-colors cursor-pointer">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
                <h1 class="text-xl font-space font-bold tracking-tight">Bantuan Darurat</h1>
                @if($activeSos)
                    <button type="button" onclick="switchToActiveSosPhase()" class="text-xs font-bold text-[#FFE1DD] underline">
                        SOS Aktif
                    </button>
                @else
                    <span class="w-11"></span>
                @endif
            </div>

            <p class="text-center text-[13.5px] leading-relaxed text-[#FFE1DD] mt-1 max-w-[320px] mx-auto">
                Pilih keadaan, lalu tekan SOS. Ada hitung mundur 5 detik agar tidak salah tekan.
            </p>

            <!-- Category Selector (2x2 Grid) -->
            <div class="grid grid-cols-2 gap-2 mt-2">
                <button type="button" onclick="selectSosCategory('Kecelakaan kerja', 'safety_health')" id="sos-cat-kecelakaan"
                        class="sos-cat-btn h-12 rounded-[22px] px-4 font-bold text-[13px] transition-all bg-white text-[#B42318] shadow-sm flex items-center justify-center text-center">
                    Kecelakaan kerja
                </button>
                <button type="button" onclick="selectSosCategory('Merasa tidak aman', 'safety_health')" id="sos-cat-keamanan"
                        class="sos-cat-btn h-12 rounded-[22px] px-4 font-bold text-[13px] transition-all bg-white/20 hover:bg-white/30 text-white flex items-center justify-center text-center">
                    Merasa tidak aman
                </button>
                <button type="button" onclick="selectSosCategory('Sakit mendadak', 'safety_health')" id="sos-cat-sakit"
                        class="sos-cat-btn h-12 rounded-[22px] px-4 font-bold text-[13px] transition-all bg-white/20 hover:bg-white/30 text-white flex items-center justify-center text-center">
                    Sakit mendadak
                </button>
                <button type="button" onclick="selectSosCategory('Lainnya', 'emergency')" id="sos-cat-lainnya"
                        class="sos-cat-btn h-12 rounded-[22px] px-4 font-bold text-[13px] transition-all bg-white/20 hover:bg-white/30 text-white flex items-center justify-center text-center">
                    Lainnya
                </button>
            </div>

            <!-- Pulsing SOS Button Container -->
            <div class="relative w-[300px] h-[300px] mx-auto my-3 flex items-center justify-center">
                <!-- Static guide rings -->
                <svg width="300" height="300" viewBox="0 0 300 300" class="absolute inset-0 pointer-events-none" aria-hidden="true">
                    <circle cx="150" cy="150" r="140" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="2"/>
                    <circle cx="150" cy="150" r="115" fill="none" stroke="rgba(255,255,255,0.14)" stroke-width="2"/>
                    <circle cx="150" cy="150" r="92" fill="none" stroke="rgba(255,255,255,0.22)" stroke-width="2"/>
                </svg>

                <!-- Expanding pulse waves -->
                <span class="sos-pulse-wave" aria-hidden="true"></span>
                <span class="sos-pulse-wave" style="animation-delay:.8s" aria-hidden="true"></span>
                <span class="sos-pulse-wave" style="animation-delay:1.6s" aria-hidden="true"></span>

                <button type="button" onclick="startSosCountdown()" aria-label="Kirim SOS, hitung mundur 5 detik"
                        class="sos-heartbeat relative z-10 w-[164px] h-[164px] rounded-full bg-white text-[#B42318] flex flex-col items-center justify-center shadow-[0_14px_34px_rgba(60,6,2,0.45)] hover:scale-105 active:scale-95 transition-all cursor-pointer group">
                    <span class="font-space font-bold text-[50px] leading-none tracking-wider group-hover:scale-105 transition-transform">SOS</span>
                    <span class="font-sans font-extrabold text-[11px] tracking-wider text-[#7E1810] mt-1">TEKAN UNTUK KIRIM</span>
                </button>
            </div>

            <!-- Recipient Dispatch Info Card -->
            <div class="bg-white/15 rounded-[22px] p-4 flex flex-col gap-2.5 backdrop-blur-xs">
                <div class="flex items-center gap-3">
                    <div class="flex shrink-0">
                        <span class="w-[34px] h-[34px] rounded-full bg-white text-[#B42318] border-2 border-[#B42318] flex items-center justify-center text-xs font-bold">GP</span>
                        <span class="w-[34px] h-[34px] rounded-full bg-white text-[#B42318] border-2 border-[#B42318] flex items-center justify-center text-xs font-bold -ml-2.5">DU</span>
                        <span class="w-[34px] h-[34px] rounded-full bg-white text-[#B42318] border-2 border-[#B42318] flex items-center justify-center text-xs font-bold -ml-2.5">WL</span>
                    </div>
                    <div class="min-w-0">
                        <span class="text-[13.5px] font-bold block leading-tight">Dikirim ke 3 kontak darurat</span>
                        <span class="text-xs text-[#FFE1DD]">Guru pembimbing, DUDI, dan wali siswa</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 text-xs text-[#FFE1DD] pt-1 border-t border-white/10">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0">
                        <path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <span>Lokasi langsung dan koordinat GPS akan ikut terkirim</span>
                </div>
            </div>
        </div>

        <!-- ==========================================
             PHASE 2: 5-SECOND COUNTDOWN
             ========================================== -->
        <div id="sos-phase-countdown" class="hidden flex-col items-center justify-between min-h-[560px]">
            <div class="w-full text-center">
                <h1 class="font-space font-bold text-xl tracking-tight">SOS akan dikirim</h1>
                <p class="text-sm text-[#FFE1DD] mt-1">Tekan batalkan jika Anda tidak sengaja menyentuh tombol.</p>
            </div>

            <!-- Circular Progress & Giant Number -->
            <div class="relative w-[260px] h-[260px] my-6 flex items-center justify-center">
                <svg width="260" height="260" viewBox="0 0 260 260" class="absolute inset-0 -rotate-90">
                    <circle cx="130" cy="130" r="110" fill="none" stroke="rgba(255,255,255,0.15)" stroke-width="12"/>
                    <circle id="sos-countdown-circle" cx="130" cy="130" r="110" fill="none" stroke="#FFFFFF" stroke-width="12" 
                            stroke-linecap="round" stroke-dasharray="691" stroke-dashoffset="0" class="transition-all duration-1000 ease-linear"/>
                </svg>
                <div class="flex flex-col items-center justify-center text-center select-none">
                    <span id="sos-countdown-number" class="font-space font-bold text-[104px] leading-none text-white">5</span>
                    <span class="text-sm font-bold text-[#FFE1DD] tracking-widest mt-1">DETIK</span>
                </div>
            </div>

            <p class="text-center text-sm text-[#FFE1DD] max-w-[310px]">
                Kategori: <strong id="sos-display-cat" class="text-white font-bold">Kecelakaan kerja</strong>.<br>
                Lokasi langsung dan notifikasi darurat akan segera disiarkan.
            </p>

            <div class="w-full flex flex-col gap-2 mt-4">
                <button type="button" onclick="cancelSosCountdown()"
                        class="w-full h-[58px] rounded-[29px] bg-white text-[#B42318] font-extrabold text-base shadow-lg hover:bg-slate-100 active:scale-95 transition-all cursor-pointer">
                    Batalkan, ini salah tekan
                </button>
                <button type="button" onclick="executeSendSosNow()"
                        class="w-full h-12 rounded-[24px] border border-white/50 text-white font-bold text-sm hover:bg-white/10 active:scale-95 transition-all cursor-pointer">
                    Kirim sekarang tanpa menunggu
                </button>
            </div>
        </div>

        <!-- ==========================================
             PHASE 3: SOS TERKIRIM (CONFIRMATION & LIVE)
             ========================================== -->
        <div id="sos-phase-sent" class="hidden flex-col gap-4">
            <div class="text-center">
                <h1 class="font-space font-bold text-xl">Bantuan Darurat</h1>
            </div>

            <div class="flex flex-col items-center text-center mt-2">
                <div class="w-20 h-20 rounded-full bg-white text-[#B42318] flex items-center justify-center shadow-[0_0_0_10px_rgba(255,255,255,0.18)]">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>
                <h2 class="font-space font-bold text-3xl mt-4">SOS terkirim</h2>
                <p class="text-sm text-[#FFE1DD] mt-1 max-w-[320px]">
                    Kategori: <strong id="sos-sent-cat-label" class="text-white">Kecelakaan kerja</strong>. Tetap di tempat yang aman, pihak sekolah dan DU/DI sedang dihubungi.
                </p>
            </div>

            <!-- Delivery Receipts Card (White background) -->
            <div class="bg-white text-[#10211D] rounded-[24px] p-4 flex flex-col gap-3 shadow-lg">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100">
                    <span class="relative flex h-3 w-3 shrink-0">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#0F7A66] opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-[#0F7A66]"></span>
                    </span>
                    <span class="text-[13.5px] font-bold flex-1">Lokasi langsung dibagikan</span>
                    <span class="text-xs text-[#5A6B65]">Baru saja</span>
                </div>

                <!-- Recipient 1: Guru -->
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-full bg-[#FBE4E1] text-[#B42318] flex items-center justify-center font-bold text-xs shrink-0">GP</span>
                    <div class="flex-1 min-w-0">
                        <span class="font-bold text-sm block leading-tight">Guru Pembimbing</span>
                        <span class="text-xs text-[#5A6B65]">Sekolah asal</span>
                    </div>
                    <span class="bg-[#DDF3EC] text-[#0B5E4E] rounded-full px-2.5 py-1 text-xs font-bold">Terkirim</span>
                </div>

                <!-- Recipient 2: DU/DI -->
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-full bg-[#FBE4E1] text-[#B42318] flex items-center justify-center font-bold text-xs shrink-0">DU</span>
                    <div class="flex-1 min-w-0">
                        <span class="font-bold text-sm block leading-tight">Pembimbing DU/DI</span>
                        <span class="text-xs text-[#5A6B65]">Mitra industri</span>
                    </div>
                    <span class="bg-[#DDF3EC] text-[#0B5E4E] rounded-full px-2.5 py-1 text-xs font-bold">Terkirim</span>
                </div>

                <!-- Recipient 3: Wali -->
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-full bg-[#FBE4E1] text-[#B42318] flex items-center justify-center font-bold text-xs shrink-0">WL</span>
                    <div class="flex-1 min-w-0">
                        <span class="font-bold text-sm block leading-tight">Wali Siswa</span>
                        <span class="text-xs text-[#5A6B65]">Orang tua</span>
                    </div>
                    <span class="bg-[#DDF3EC] text-[#0B5E4E] rounded-full px-2.5 py-1 text-xs font-bold">Terkirim</span>
                </div>
            </div>

            <!-- Emergency Actions -->
            <div class="flex flex-col gap-2 mt-2">
                <a href="tel:112" class="w-full h-14 rounded-[28px] bg-white text-[#B42318] font-extrabold text-base flex items-center justify-center gap-2 shadow-lg active:scale-95 transition-all">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    <span>Telepon 112 (Darurat)</span>
                </a>
                <button type="button" onclick="resetSosState()"
                        class="w-full h-12 rounded-[24px] border border-white/50 text-white font-bold text-sm hover:bg-white/10 active:scale-95 transition-all cursor-pointer">
                    Saya sudah aman (Selesai)
                </button>
            </div>
        </div>

    </div>
</div>

<script>
    let currentSosCategory = 'Kecelakaan kerja';
    let currentSosCategoryKey = 'safety_health';
    let sosTimer = null;
    let sosSecondsLeft = 5;
    const hasActiveSos = {{ $activeSos ? 'true' : 'false' }};

    window.openSosModal = function() {
        const modal = document.getElementById('sos-emergency-modal');
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            resetSosViews();
        }
    };

    window.closeSosModal = function() {
        const modal = document.getElementById('sos-emergency-modal');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            if (sosTimer) clearInterval(sosTimer);
        }
    };

    function resetSosViews() {
        const activePhase = document.getElementById('sos-phase-active');
        const idlePhase = document.getElementById('sos-phase-idle');
        const countPhase = document.getElementById('sos-phase-countdown');
        const sentPhase = document.getElementById('sos-phase-sent');

        if (countPhase) { countPhase.classList.add('hidden'); countPhase.classList.remove('flex'); }
        if (sentPhase) { sentPhase.classList.add('hidden'); sentPhase.classList.remove('flex'); }

        if (hasActiveSos && activePhase) {
            activePhase.classList.remove('hidden');
            if (idlePhase) idlePhase.classList.add('hidden');
        } else {
            if (activePhase) activePhase.classList.add('hidden');
            if (idlePhase) {
                idlePhase.classList.remove('hidden');
                idlePhase.classList.add('flex');
            }
            selectSosCategory('Kecelakaan kerja', 'safety_health');
        }
    }

    window.switchToNewSosPhase = function() {
        const activePhase = document.getElementById('sos-phase-active');
        const idlePhase = document.getElementById('sos-phase-idle');
        if (activePhase) activePhase.classList.add('hidden');
        if (idlePhase) {
            idlePhase.classList.remove('hidden');
            idlePhase.classList.add('flex');
            selectSosCategory('Kecelakaan kerja', 'safety_health');
        }
    };

    window.switchToActiveSosPhase = function() {
        const activePhase = document.getElementById('sos-phase-active');
        const idlePhase = document.getElementById('sos-phase-idle');
        if (idlePhase) idlePhase.classList.add('hidden');
        if (activePhase) activePhase.classList.remove('hidden');
    };

    function selectSosCategory(label, backendKey) {
        currentSosCategory = label;
        currentSosCategoryKey = backendKey;
        
        document.querySelectorAll('.sos-cat-btn').forEach(btn => {
            btn.className = 'sos-cat-btn h-12 rounded-[22px] px-4 font-bold text-[13px] transition-all bg-white/20 hover:bg-white/30 text-white flex items-center justify-center text-center';
        });

        const activeId = label === 'Kecelakaan kerja' ? 'sos-cat-kecelakaan' :
                         label === 'Merasa tidak aman' ? 'sos-cat-keamanan' :
                         label === 'Sakit mendadak' ? 'sos-cat-sakit' : 'sos-cat-lainnya';
        const activeBtn = document.getElementById(activeId);
        if (activeBtn) {
            activeBtn.className = 'sos-cat-btn h-12 rounded-[22px] px-4 font-bold text-[13px] transition-all bg-white text-[#B42318] shadow-sm flex items-center justify-center text-center';
        }
    }

    function startSosCountdown() {
        const activePhase = document.getElementById('sos-phase-active');
        if (activePhase) activePhase.classList.add('hidden');
        document.getElementById('sos-phase-idle').classList.add('hidden');

        const countPhase = document.getElementById('sos-phase-countdown');
        countPhase.classList.remove('hidden');
        countPhase.classList.add('flex');

        document.getElementById('sos-display-cat').textContent = currentSosCategory;

        sosSecondsLeft = 5;
        updateCountdownDisplay();

        const circle = document.getElementById('sos-countdown-circle');
        const circumference = 691;
        circle.style.strokeDashoffset = '0';

        if (sosTimer) clearInterval(sosTimer);
        sosTimer = setInterval(() => {
            sosSecondsLeft--;
            updateCountdownDisplay();
            
            const offset = circumference - ((5 - sosSecondsLeft) / 5) * circumference;
            circle.style.strokeDashoffset = offset;

            if (sosSecondsLeft <= 0) {
                clearInterval(sosTimer);
                executeSendSosNow();
            }
        }, 1000);
    }

    function updateCountdownDisplay() {
        const num = document.getElementById('sos-countdown-number');
        if (num) num.textContent = Math.max(0, sosSecondsLeft);
    }

    function cancelSosCountdown() {
        if (sosTimer) clearInterval(sosTimer);
        resetSosViews();
    }

    function executeSendSosNow() {
        if (sosTimer) clearInterval(sosTimer);

        // Transition immediately to Phase 3 (Sent)
        document.getElementById('sos-phase-countdown').classList.add('hidden');
        document.getElementById('sos-phase-countdown').classList.remove('flex');
        const sentPhase = document.getElementById('sos-phase-sent');
        sentPhase.classList.remove('hidden');
        sentPhase.classList.add('flex');
        document.getElementById('sos-sent-cat-label').textContent = currentSosCategory;

        // Fetch user location in background
        let userLat = null;
        let userLng = null;
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition((pos) => {
                userLat = pos.coords.latitude;
                userLng = pos.coords.longitude;
                dispatchSosToBackend(userLat, userLng);
            }, () => {
                dispatchSosToBackend(null, null);
            }, { timeout: 3000 });
        } else {
            dispatchSosToBackend(null, null);
        }
    }

    function dispatchSosToBackend(lat, lng) {
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('category', currentSosCategoryKey || 'emergency');
        formData.append('urgency', 'critical');
        formData.append('subject', 'DARURAT SOS: ' + currentSosCategory);
        let desc = 'Siswa menekan tombol Bantuan Darurat SOS PKL. Kategori keadaan: ' + currentSosCategory + '.';
        if (lat && lng) {
            desc += ' Koordinat GPS saat darurat: ' + lat + ', ' + lng;
        }
        formData.append('description', desc);

        fetch('{{ route("student.incident.store") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(r => r.json()).catch(() => {});
    }

    function resetSosState() {
        window.closeSosModal();
        window.location.reload();
    }
</script>
