<!-- Modal Panduan Perizinan Kamera & Lokasi (iOS Safari & Android) -->
<div id="permission-guide-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-[#071915]/95 text-white font-sans backdrop-blur-md transition-all duration-300">
    <div class="relative w-full max-w-[420px] min-h-screen mx-auto flex flex-col justify-between p-5 box-border">

        <div class="flex flex-col gap-3">
            <!-- Topbar Row -->
            <div class="flex items-center justify-between h-12">
                <button type="button" onclick="window.closePermissionGuideModal()" aria-label="Tutup panduan"
                        class="flex items-center justify-center w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors cursor-pointer">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>

                <span id="perm-modal-badge" class="inline-flex items-center gap-1.5 bg-[#FFF1D6] text-[#7A4B00] rounded-full px-3.5 py-1 text-xs font-extrabold tracking-wide">
                    <span class="w-2 h-2 rounded-full bg-[#7A4B00]"></span>
                    PANDUAN PERIZINAN
                </span>

                <span class="w-11"></span>
            </div>

            <!-- Header Icon & Title -->
            <div class="text-center mt-1">
                <div id="perm-modal-icon-container" class="w-16 h-16 rounded-2xl bg-white/10 border border-white/15 flex items-center justify-center mx-auto mb-3 shadow-inner">
                    <!-- Dynamic Icon loaded via JS -->
                    <svg id="perm-modal-icon-camera" class="w-8 h-8 text-[#C6F26B]" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                        <circle cx="12" cy="13" r="4"/>
                    </svg>
                    <svg id="perm-modal-icon-location" class="hidden w-8 h-8 text-[#C6F26B]" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                </div>

                <h1 id="perm-modal-title" class="font-space font-bold text-2xl leading-tight text-white">
                    Izin Akses Dibutuhkan
                </h1>
                <p id="perm-modal-subtitle" class="text-xs text-[#A9C2B9] leading-relaxed max-w-[320px] mx-auto mt-1">
                    Aktifkan izin untuk memastikan keabsahan kehadiran selfie dan radius kantor PKL Anda.
                </p>
            </div>

            <!-- Device Selector Pill Tabs -->
            <div class="flex p-1 bg-white/[0.08] rounded-2xl border border-white/10 mt-1">
                <button type="button" id="tab-btn-ios" onclick="window.switchPermDeviceTab('ios')"
                        class="flex-1 py-2 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 bg-[#C6F26B] text-[#0B2B25] shadow-xs">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.37c.58-.71.97-1.7.86-2.69-.84.03-1.85.56-2.45 1.27-.53.61-.99 1.62-.87 2.59.94.07 1.88-.47 2.46-1.17z"/>
                    </svg>
                    <span>iPhone (Safari)</span>
                </button>
                <button type="button" id="tab-btn-android" onclick="window.switchPermDeviceTab('android')"
                        class="flex-1 py-2 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 text-[#A9C2B9] hover:text-white">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-1.0003s.4482-.9993.9993-.9993c.551 0 .9993.4476.9993.9993s-.4483 1.0003-.9993 1.0003m-11.046 0c-.5511 0-.9993-.4486-.9993-1.0003s.4482-.9993.9993-.9993c.5511 0 .9993.4476.9993.9993s-.4482 1.0003-.9993 1.0003m11.4045-6.02l1.9973-3.4592a.416.416 0 00-.1521-.5676.416.416 0 00-.5676.1521l-2.0223 3.503C15.5902 8.4116 13.8533 8.0833 12 8.0833c-1.8533 0-3.5902.3283-5.1368.8664L4.8409 5.4467a.4161.4161 0 00-.5677-.1521.4157.4157 0 00-.1521.5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589 0 18.761h24c-.3432-4.1021-2.6889-7.5743-6.1185-9.4396"/>
                    </svg>
                    <span>Android (Chrome)</span>
                </button>
            </div>

            <!-- Content Panel: iPhone Safari Guide -->
            <div id="panel-ios-guide" class="flex flex-col gap-2.5">
                <!-- Step 1: Ikon aA -->
                <div class="bg-white/7 border border-white/12 rounded-[20px] p-3.5 flex items-start gap-3">
                    <span class="w-7 h-7 rounded-xl bg-[#C6F26B] text-[#0B2B25] flex items-center justify-center font-extrabold text-xs shrink-0 mt-0.5">
                        1
                    </span>
                    <div class="flex-1 min-w-0">
                        <span class="text-[13px] font-bold block text-white">Ketuk ikon "aA" di Address Bar Safari</span>
                        <p class="text-[11.5px] text-[#A9C2B9] leading-snug mt-0.5">
                            Ikon berada di sebelah kiri atau kanan kolom alamat web Safari (URL bar).
                        </p>
                    </div>
                </div>

                <!-- Step 2: Website Settings -->
                <div class="bg-white/7 border border-white/12 rounded-[20px] p-3.5 flex items-start gap-3">
                    <span class="w-7 h-7 rounded-xl bg-[#C6F26B] text-[#0B2B25] flex items-center justify-center font-extrabold text-xs shrink-0 mt-0.5">
                        2
                    </span>
                    <div class="flex-1 min-w-0">
                        <span class="text-[13px] font-bold block text-white">Pilih "Pengaturan Situs Web" (Website Settings)</span>
                        <p class="text-[11.5px] text-[#A9C2B9] leading-snug mt-0.5">
                            Ubah pengaturan <strong class="text-white">Kamera (Camera)</strong> dan <strong class="text-white">Lokasi (Location)</strong> menjadi <strong class="text-[#C6F26B]">"Izinkan" (Allow)</strong>.
                        </p>
                    </div>
                </div>

                <!-- Step 3: Precise Location Highlight (Crucial for iOS) -->
                <div class="bg-[#FFF1D6]/10 border border-[#FFF1D6]/25 rounded-[20px] p-3.5 flex items-start gap-3">
                    <span class="w-7 h-7 rounded-xl bg-[#FFF1D6] text-[#7A4B00] flex items-center justify-center font-extrabold text-xs shrink-0 mt-0.5">
                        ★
                    </span>
                    <div class="flex-1 min-w-0">
                        <span class="text-[13px] font-bold block text-[#FFE2A4]">Wajib: Aktifkan "Lokasi Tepat" (Precise Location)</span>
                        <p class="text-[11.5px] text-[#F0DBC5] leading-snug mt-0.5">
                            Jika opsi ini mati, iPhone hanya mengirim koordinat perkiraan radius 1–5 km sehingga geofence kantor gagal. Pastikan toggle <em>Precise Location</em> dalam posisi <strong>ON</strong>.
                        </p>
                    </div>
                </div>

                <!-- Step 4: Refresh Page -->
                <div class="bg-white/7 border border-white/12 rounded-[20px] p-3.5 flex items-start gap-3">
                    <span class="w-7 h-7 rounded-xl bg-[#C6F26B] text-[#0B2B25] flex items-center justify-center font-extrabold text-xs shrink-0 mt-0.5">
                        3
                    </span>
                    <div class="flex-1 min-w-0">
                        <span class="text-[13px] font-bold block text-white">Muat Ulang / Refresh Halaman</span>
                        <p class="text-[11.5px] text-[#A9C2B9] leading-snug mt-0.5">
                            Tutup menu pengaturan dan muat ulang halaman ini agar kamera & GPS aktif kembali.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Content Panel: Android Chrome Guide -->
            <div id="panel-android-guide" class="hidden flex flex-col gap-2.5">
                <!-- Step 1: Ikon Gembok / Setelan -->
                <div class="bg-white/7 border border-white/12 rounded-[20px] p-3.5 flex items-start gap-3">
                    <span class="w-7 h-7 rounded-xl bg-[#C6F26B] text-[#0B2B25] flex items-center justify-center font-extrabold text-xs shrink-0 mt-0.5">
                        1
                    </span>
                    <div class="flex-1 min-w-0">
                        <span class="text-[13px] font-bold block text-white">Ketuk ikon Gembok / Opsi Situs di Chrome</span>
                        <p class="text-[11.5px] text-[#A9C2B9] leading-snug mt-0.5">
                            Terletak di sebelah kiri kolom URL browser Chrome Anda.
                        </p>
                    </div>
                </div>

                <!-- Step 2: Izin Situs -->
                <div class="bg-white/7 border border-white/12 rounded-[20px] p-3.5 flex items-start gap-3">
                    <span class="w-7 h-7 rounded-xl bg-[#C6F26B] text-[#0B2B25] flex items-center justify-center font-extrabold text-xs shrink-0 mt-0.5">
                        2
                    </span>
                    <div class="flex-1 min-w-0">
                        <span class="text-[13px] font-bold block text-white">Pilih "Izin" (Permissions)</span>
                        <p class="text-[11.5px] text-[#A9C2B9] leading-snug mt-0.5">
                            Nyalakan izin untuk <strong class="text-[#C6F26B]">Kamera</strong> dan <strong class="text-[#C6F26B]">Lokasi</strong> menjadi Izinkan / Diizinkan.
                        </p>
                    </div>
                </div>

                <!-- Step 3: Akurasi Tinggi GPS -->
                <div class="bg-white/7 border border-white/12 rounded-[20px] p-3.5 flex items-start gap-3">
                    <span class="w-7 h-7 rounded-xl bg-[#C6F26B] text-[#0B2B25] flex items-center justify-center font-extrabold text-xs shrink-0 mt-0.5">
                        3
                    </span>
                    <div class="flex-1 min-w-0">
                        <span class="text-[13px] font-bold block text-white">Aktifkan GPS Akurasi Tinggi (Google Location Accuracy)</span>
                        <p class="text-[11.5px] text-[#A9C2B9] leading-snug mt-0.5">
                            Tarik notifikasi bar Android, pastikan tombol Lokasi aktif dan gunakan mode akurasi tinggi.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Bottom Buttons -->
        <div class="flex flex-col gap-2 pt-4">
            <button type="button" onclick="window.location.reload()"
                    class="w-full h-12 rounded-[24px] bg-[#C6F26B] hover:bg-[#b8e85c] text-[#0B2B25] font-bold text-xs active:scale-95 transition-all flex items-center justify-center gap-2 shadow-md cursor-pointer">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                </svg>
                <span>Sudah Mengizinkan, Muat Ulang Halaman</span>
            </button>
            <button type="button" onclick="window.closePermissionGuideModal()"
                    class="w-full h-11 rounded-[24px] bg-white/10 hover:bg-white/20 text-white font-bold text-xs active:scale-95 transition-all flex items-center justify-center cursor-pointer">
                Tutup Panduan
            </button>
        </div>

    </div>
</div>

<script>
    (function() {
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) || 
                      (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

        window.showPermissionGuideModal = function(topic = 'all') {
            const modal = document.getElementById('permission-guide-modal');
            if (!modal) return;

            const iconCamera = document.getElementById('perm-modal-icon-camera');
            const iconLoc = document.getElementById('perm-modal-icon-location');
            const titleEl = document.getElementById('perm-modal-title');
            const subtitleEl = document.getElementById('perm-modal-subtitle');
            const badgeEl = document.getElementById('perm-modal-badge');

            if (topic === 'camera') {
                if (iconCamera) iconCamera.classList.remove('hidden');
                if (iconLoc) iconLoc.classList.add('hidden');
                if (titleEl) titleEl.textContent = 'Izin Kamera Dibutuhkan';
                if (subtitleEl) subtitleEl.textContent = 'Safari/Browser membutuhkan izin kamera untuk verifikasi wajah selfie presensi.';
                if (badgeEl) badgeEl.innerHTML = '<span class="w-2 h-2 rounded-full bg-[#7A4B00]"></span> KAMERA DIBLOKIR';
            } else if (topic === 'location' || topic === 'location-accuracy') {
                if (iconCamera) iconCamera.classList.add('hidden');
                if (iconLoc) iconLoc.classList.remove('hidden');
                if (titleEl) titleEl.textContent = topic === 'location-accuracy' ? 'Akurasi GPS Belum Tepat' : 'Izin Lokasi Dibutuhkan';
                if (subtitleEl) subtitleEl.textContent = topic === 'location-accuracy' 
                    ? 'Akurasi GPS melebihi batas 80m. Pastikan opsi "Lokasi Tepat (Precise Location)" aktif di iPhone Anda.' 
                    : 'Safari/Browser membutuhkan izin lokasi untuk memeriksa radius penugasan PKL Anda.';
                if (badgeEl) badgeEl.innerHTML = '<span class="w-2 h-2 rounded-full bg-[#7A4B00]"></span> GPS MEMBUTUHKAN IZIN';
            }

            // Auto-select tab according to detected device
            window.switchPermDeviceTab(isIOS ? 'ios' : 'android');

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        window.closePermissionGuideModal = function() {
            const modal = document.getElementById('permission-guide-modal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        };

        window.switchPermDeviceTab = function(type) {
            const tabIos = document.getElementById('tab-btn-ios');
            const tabAndroid = document.getElementById('tab-btn-android');
            const panelIos = document.getElementById('panel-ios-guide');
            const panelAndroid = document.getElementById('panel-android-guide');

            if (type === 'ios') {
                tabIos.className = 'flex-1 py-2 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 bg-[#C6F26B] text-[#0B2B25] shadow-xs';
                tabAndroid.className = 'flex-1 py-2 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 text-[#A9C2B9] hover:text-white';
                panelIos.classList.remove('hidden');
                panelAndroid.classList.add('hidden');
            } else {
                tabAndroid.className = 'flex-1 py-2 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 bg-[#C6F26B] text-[#0B2B25] shadow-xs';
                tabIos.className = 'flex-1 py-2 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 text-[#A9C2B9] hover:text-white';
                panelAndroid.classList.remove('hidden');
                panelIos.classList.add('hidden');
            }
        };
    })();
</script>
