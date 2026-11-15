<!-- Screen 6: Bantuan Darurat SOS (Full-screen Interactive Emergency Modal) -->
<div id="sos-emergency-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-[#B42318] text-white font-sans transition-all duration-300">
    <div class="relative w-full max-w-[420px] min-h-screen mx-auto flex flex-col justify-between p-5 box-border">

        <!-- ==========================================
             PHASE 1: IDLE / SELECT CATEGORY & PRESS
             ========================================== -->
        <div id="sos-phase-idle" class="flex flex-col gap-3">
            <!-- Header -->
            <div class="flex items-center justify-between h-12">
                <button type="button" onclick="window.closeSosModal()" aria-label="Tutup Bantuan Darurat" 
                        class="flex items-center justify-center w-11 h-11 rounded-full bg-white/15 hover:bg-white/25 text-white transition-colors cursor-pointer">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
                <h1 class="text-xl font-space font-bold tracking-tight">Bantuan Darurat</h1>
                <span class="w-11"></span>
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
                <!-- Concentric animated ripple rings -->
                <svg width="300" height="300" viewBox="0 0 300 300" class="absolute inset-0 pointer-events-none" aria-hidden="true">
                    <circle cx="150" cy="150" r="140" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="2"/>
                    <circle cx="150" cy="150" r="115" fill="none" stroke="rgba(255,255,255,0.14)" stroke-width="2"/>
                    <circle cx="150" cy="150" r="92" fill="none" stroke="rgba(255,255,255,0.22)" stroke-width="2"/>
                </svg>

                <button type="button" onclick="startSosCountdown()" aria-label="Kirim SOS, hitung mundur 5 detik"
                        class="relative z-10 w-[164px] h-[164px] rounded-full bg-white text-[#B42318] flex flex-col items-center justify-center shadow-[0_14px_34px_rgba(60,6,2,0.45)] hover:scale-105 active:scale-95 transition-all cursor-pointer group">
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
        document.getElementById('sos-phase-idle').classList.remove('hidden');
        document.getElementById('sos-phase-countdown').classList.add('hidden');
        document.getElementById('sos-phase-countdown').classList.remove('flex');
        document.getElementById('sos-phase-sent').classList.add('hidden');
        document.getElementById('sos-phase-sent').classList.remove('flex');
        selectSosCategory('Kecelakaan kerja', 'safety_health');
    }

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
        resetSosViews();
    }
</script>
