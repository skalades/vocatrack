<!-- Screen 5: Peringatan Fake GPS (Full-screen Security Alert Modal) -->
<div id="fake-gps-alert-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-[#2A0A08] text-white font-sans transition-all duration-300">
    <div class="relative w-full max-w-[420px] min-h-screen mx-auto flex flex-col justify-between p-5 box-border">

        <div class="flex flex-col gap-2">
            <!-- Topbar -->
            <div class="flex items-center justify-between h-12">
                <button type="button" onclick="window.closeFakeGpsModal()" aria-label="Kembali"
                        class="flex items-center justify-center w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors cursor-pointer">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </button>

                <span class="inline-flex items-center gap-2 bg-[#FF5A4D] text-[#2A0A08] rounded-full px-3.5 py-1.5 text-xs font-extrabold tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-[#2A0A08]"></span>
                    PRESENSI DIBLOKIR
                </span>

                <span class="w-11"></span>
            </div>

            <!-- Radar Graphic -->
            <div class="flex justify-center my-1">
                <svg width="190" height="150" viewBox="0 0 190 150" role="img" aria-label="Radar lokasi dengan penanda pin dicoret" class="block">
                    <circle cx="95" cy="75" r="66" fill="none" stroke="rgba(255,90,77,0.18)" stroke-width="2"/>
                    <circle cx="95" cy="75" r="44" fill="none" stroke="rgba(255,90,77,0.32)" stroke-width="2"/>
                    <circle cx="95" cy="75" r="22" fill="none" stroke="rgba(255,90,77,0.5)" stroke-width="2"/>
                    <line x1="95" y1="9" x2="95" y2="141" stroke="rgba(255,90,77,0.4)" stroke-width="1.5" stroke-dasharray="3 3"/>
                    <line x1="29" y1="75" x2="161" y2="75" stroke="rgba(255,90,77,0.4)" stroke-width="1.5" stroke-dasharray="3 3"/>
                    <g transform="translate(73, 53)">
                        <circle cx="22" cy="22" r="22" fill="#FF5A4D"/>
                        <path d="m14 14 16 16m0-16L14 30" stroke="#2A0A08" stroke-width="3.2" stroke-linecap="round"/>
                    </g>
                </svg>
            </div>

            <h1 class="text-center font-space font-bold text-[28px] leading-tight">Lokasi palsu terdeteksi</h1>
            <p class="text-center text-sm leading-relaxed text-[#F0C4BF] max-w-[320px] mx-auto mt-1">
                Presensi tidak bisa dilanjutkan. Nonaktifkan lokasi palsu di perangkat ini, lalu coba lagi.
            </p>
            <p id="fake-gps-reason-text" class="hidden text-center text-xs font-semibold text-[#FF8A80] mt-1.5 bg-black/30 px-3 py-1.5 rounded-xl border border-red-500/25 max-w-[340px] mx-auto"></p>

            <!-- Card 1: Hasil Pemeriksaan Perangkat -->
            <div class="mt-3 bg-white/7 border border-white/14 rounded-[22px] p-3.5 flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <span class="text-[13px] font-extrabold">Hasil pemeriksaan perangkat</span>
                    <span class="text-xs font-bold text-[#FF8A80]">3 dari 3 gagal</span>
                </div>

                <div class="flex items-center gap-3">
                    <span class="w-[30px] h-[30px] rounded-full bg-[#FF5A4D] text-[#2A0A08] flex items-center justify-center shrink-0">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <span class="text-[13.5px] font-bold block leading-tight">Aplikasi lokasi palsu aktif</span>
                        <span class="text-xs text-[#F0C4BF]">Terdeteksi sebagai penyedia lokasi tiruan</span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="w-[30px] h-[30px] rounded-full bg-[#FF5A4D] text-[#2A0A08] flex items-center justify-center shrink-0">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <span class="text-[13.5px] font-bold block leading-tight">Opsi pengembang: lokasi tiruan</span>
                        <span class="text-xs text-[#F0C4BF]">Aplikasi lokasi tiruan dipilih di pengaturan</span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="w-[30px] h-[30px] rounded-full bg-[#FF5A4D] text-[#2A0A08] flex items-center justify-center shrink-0">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <span class="text-[13.5px] font-bold block leading-tight">Lompatan posisi tidak wajar</span>
                        <span class="text-xs text-[#F0C4BF]">Berpindah tidak realistis dalam hitungan detik</span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Cara Memperbaiki -->
            <div class="bg-white/7 border border-white/14 rounded-[22px] p-3.5 flex flex-col gap-2 text-[13px] leading-snug">
                <span class="font-extrabold text-[13px] mb-0.5">Cara memperbaiki:</span>
                <div class="flex items-center gap-2.5">
                    <span class="w-[22px] h-[22px] rounded-full bg-white text-[#2A0A08] flex items-center justify-center text-xs font-extrabold shrink-0">1</span>
                    <span>Tutup dan hapus aplikasi lokasi palsu (Fake GPS).</span>
                </div>
                <div class="flex items-center gap-2.5">
                    <span class="w-[22px] h-[22px] rounded-full bg-white text-[#2A0A08] flex items-center justify-center text-xs font-extrabold shrink-0">2</span>
                    <span>Opsi pengembang: kosongkan aplikasi lokasi tiruan.</span>
                </div>
                <div class="flex items-center gap-2.5">
                    <span class="w-[22px] h-[22px] rounded-full bg-white text-[#2A0A08] flex items-center justify-center text-xs font-extrabold shrink-0">3</span>
                    <span>Nyalakan lokasi akurasi tinggi (GPS perangkat), lalu coba lagi.</span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col gap-2 mt-4">
            <button type="button" onclick="retryGpsCheck()"
                    class="w-full h-14 rounded-[28px] bg-white text-[#2A0A08] font-extrabold text-[15.5px] shadow-lg hover:bg-slate-100 active:scale-95 transition-all cursor-pointer">
                Sudah dinonaktifkan, coba lagi
            </button>
            <a href="tel:{{ $spkl?->teacher?->user?->formatted_wa_number ?: ($spkl?->teacher?->user?->profile?->phone ?? '08123456789') }}"
               class="w-full h-12 rounded-[25px] border-1.5 border-white/40 text-white font-bold text-[14.5px] flex items-center justify-center gap-2 hover:bg-white/10 active:scale-95 transition-all">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
                <span>Hubungi guru pembimbing</span>
            </a>
            <p class="text-center text-xs text-[#F0C4BF] mt-1">
                Percobaan presensi tidak valid tercatat dan dilaporkan ke sistem.
            </p>
        </div>

    </div>
</div>

<script>
    window.openFakeGpsModal = function(reason) {
        const modal = document.getElementById('fake-gps-alert-modal');
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            const reasonEl = document.getElementById('fake-gps-reason-text');
            if (reasonEl) {
                if (reason) {
                    reasonEl.textContent = 'Alasan: ' + reason;
                    reasonEl.classList.remove('hidden');
                } else {
                    reasonEl.classList.add('hidden');
                }
            }
        }
    };

    window.closeFakeGpsModal = function() {
        const modal = document.getElementById('fake-gps-alert-modal');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    };

    function retryGpsCheck() {
        window.closeFakeGpsModal();
        if (typeof window.triggerLocationCheck === 'function') {
            window.triggerLocationCheck();
        } else {
            window.location.reload();
        }
    }
</script>
