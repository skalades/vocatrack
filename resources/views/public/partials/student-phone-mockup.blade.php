{{--
    Replika statis layar Beranda Siswa (resources/views/student/home.blade.php).
    Dirender pada lebar asli 360px lalu diperkecil, sehingga proporsi, warna, dan komponennya
    sama persis dengan aplikasi siswa. Data di dalamnya adalah contoh.
--}}
<div class="relative w-[300px] rounded-[46px] bg-[#0B2B25] p-[10px] shadow-2xl shadow-[#0B2B25]/30 rotate-[2deg] ring-1 ring-black/5">
    <div class="relative w-[280px] h-[690px] rounded-[38px] overflow-hidden bg-[#F1F4EC]">
        <div class="absolute left-0 top-0 w-[360px] origin-top-left text-[#10211D]" style="transform: scale(0.7778)">

            {{-- Header hijau melengkung --}}
            <div class="absolute left-0 top-0 right-0 h-[250px] bg-[#0B2B25] rounded-b-[36px] overflow-hidden">
                <svg width="390" height="250" viewBox="0 0 390 250" class="absolute left-0 top-0 w-full h-full opacity-60">
                    <circle cx="350" cy="40" r="60" fill="none" stroke="#12382F" stroke-width="2"/>
                    <circle cx="350" cy="40" r="100" fill="none" stroke="#12382F" stroke-width="2"/>
                    <circle cx="350" cy="40" r="140" fill="none" stroke="#12382F" stroke-width="2"/>
                    <circle cx="40" cy="220" r="80" fill="none" stroke="#12382F" stroke-width="2"/>
                    <circle cx="40" cy="220" r="120" fill="none" stroke="#12382F" stroke-width="2"/>
                </svg>
            </div>

            <div class="relative z-10 flex flex-col px-5 pt-6">
                {{-- Profil --}}
                <div class="flex items-center gap-3">
                    <span class="w-11 h-11 rounded-full bg-[#C6F26B] text-[#0B2B25] flex items-center justify-center font-space font-bold text-base shrink-0">RN</span>
                    <span class="flex-1 min-w-0">
                        <span class="text-[10px] font-extrabold text-[#C6F26B] uppercase tracking-wider block">Portal Siswa PKL</span>
                        <span class="font-bold text-[17px] leading-tight text-white block truncate">Halo, Rina</span>
                        <span class="text-[12px] leading-tight text-[#A9C2B9] block truncate mt-0.5">XII TKJ 1 · PT Mitra Teknik</span>
                    </span>
                    <span class="flex items-center gap-2 shrink-0">
                        <span class="relative flex items-center justify-center w-10 h-10 rounded-full bg-white/10 text-white">
                            <svg class="mk-bell" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                            <span class="absolute top-2.5 right-2.5 w-2 h-2 rounded-full bg-[#C6F26B] border-2 border-[#0B2B25] dot-ping"></span>
                        </span>
                        <span class="flex items-center justify-center w-10 h-10 rounded-full bg-white/10 text-white">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
                        </span>
                    </span>
                </div>

                {{-- Tanggal, jam & shift --}}
                <div class="flex items-end justify-between gap-2.5 mt-3.5">
                    <div class="flex flex-col">
                        <span class="text-[13px] leading-tight text-[#A9C2B9] font-medium">Senin, 3 Agustus 2026</span>
                        <div class="flex items-baseline gap-1.5 mt-0.5">
                            <span class="font-space font-semibold text-[60px] leading-none text-white tracking-tight">07<span class="mk-blink">.</span>12</span>
                            <span class="text-xs font-bold text-[#C6F26B]">WIB</span>
                        </div>
                    </div>
                    <div class="mk-pop bg-white/[0.09] rounded-2xl p-2.5 px-3 flex flex-col gap-0.5 mb-1.5 shrink-0 border border-white/10" style="--d: 800ms">
                        <span class="text-[11px] text-[#A9C2B9] font-bold">Shift hari ini</span>
                        <span class="text-[15px] font-bold text-white leading-tight">08.00 – 16.00</span>
                        <span class="text-xs font-bold text-[#C6F26B]">Mulai 48 menit lagi</span>
                    </div>
                </div>

                {{-- Status radius --}}
                <div class="flex mt-2.5 mk-pop" style="--d: 950ms">
                    <span class="inline-flex items-center gap-2 h-8 rounded-full bg-[#C6F26B] text-[#0B2B25] px-3.5 text-[13px] font-bold">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"/><circle cx="12" cy="10" r="3"/></svg>
                        Dalam radius PKL · 38 m
                    </span>
                </div>

                {{-- Kartu peta geofence --}}
                <div class="mk-pop mt-5 bg-white rounded-[26px] p-2 shadow-[0_10px_30px_rgba(11,43,37,0.12)] border border-slate-100" style="--d: 1050ms">
                    <div class="relative h-[185px] rounded-[19px] overflow-hidden bg-[#3E4A36]">
                        {{-- Ilustrasi peta satelit --}}
                        <svg viewBox="0 0 344 185" preserveAspectRatio="xMidYMid slice" class="absolute inset-0 w-full h-full">
                            <rect width="344" height="185" fill="#4B5A3E"/>
                            <path d="M0 40 L120 22 L210 48 L344 30 L344 0 L0 0Z" fill="#56664A"/>
                            <path d="M0 140 L90 120 L180 150 L260 128 L344 150 L344 185 L0 185Z" fill="#59684B"/>
                            <rect x="30" y="60" width="54" height="34" rx="3" fill="#6E7563"/>
                            <rect x="236" y="70" width="70" height="40" rx="3" fill="#71786A"/>
                            <rect x="96" y="128" width="46" height="26" rx="3" fill="#6B7360"/>
                            <rect x="262" y="132" width="40" height="30" rx="3" fill="#6E7563"/>
                            <rect x="120" y="58" width="36" height="22" rx="2" fill="#7A7F70"/>
                            <rect x="196" y="112" width="30" height="20" rx="2" fill="#787D6E"/>
                            <path d="M-10 108 C 80 96, 150 118, 230 100 S 330 84, 360 92" stroke="#C9C2A2" stroke-width="7" fill="none" opacity="0.85"/>
                            <path d="M168 -10 C 160 50, 182 110, 170 200" stroke="#C9C2A2" stroke-width="5" fill="none" opacity="0.75"/>
                            <path d="M40 -10 L70 200" stroke="#BDB696" stroke-width="3" fill="none" opacity="0.5"/>
                            <path d="M260 -10 C 250 60, 290 120, 300 200" stroke="#BDB696" stroke-width="3" fill="none" opacity="0.5"/>
                            <circle class="mk-radar" cx="172" cy="96" r="70" fill="rgba(198,242,107,0.22)"/>
                            <circle class="mk-radar delay" cx="172" cy="96" r="70" fill="rgba(198,242,107,0.22)"/>
                            <circle class="mk-fence" cx="172" cy="96" r="70" fill="rgba(198,242,107,0.16)" stroke="#C6F26B" stroke-width="2.5" stroke-dasharray="7 6"/>
                            <circle class="mk-halo" cx="150" cy="112" r="7" fill="#2D7FF9"/>
                            <circle cx="150" cy="112" r="7" fill="#2D7FF9" stroke="#fff" stroke-width="3"/>
                        </svg>

                        {{-- Label lokasi DU/DI --}}
                        <span class="absolute left-[150px] top-[76px] inline-flex items-center gap-1.5 bg-[#0B2B25]/90 text-white rounded-full py-1 pl-2 pr-2.5 text-[11px] font-bold border border-[#C6F26B]/70 shadow-md">
                            <span class="w-2 h-2 rounded-full bg-[#C6F26B]"></span>PT Mitra Teknik
                        </span>

                        <span class="absolute left-2.5 top-2.5 inline-flex items-center gap-1.5 bg-[#0B2B25]/90 text-white rounded-full py-1 px-2.5 text-[11px] font-bold shadow-md border border-white/10">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#C6F26B" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                            GPS asli
                        </span>
                        <span class="absolute right-2.5 top-2.5 bg-white/95 text-[#10211D] rounded-full py-1 px-2.5 text-[11px] font-bold shadow-md border border-slate-200">Akurasi ±8 m</span>
                        <span class="absolute left-2.5 bottom-2.5 h-8 px-2.5 rounded-full bg-white/95 text-[#0B2B25] flex items-center gap-1.5 shadow-md border border-slate-200">
                            <svg class="w-3.5 h-3.5 text-[#0B2B25]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 2a14.5 14.5 0 0 0 0 20M12 2a14.5 14.5 0 0 1 0 20M2 12h20"/>
                            </svg>
                            <span class="text-[11px] font-extrabold tracking-tight">Satelit</span>
                        </span>
                        <span class="absolute right-2.5 bottom-2.5 w-9 h-9 rounded-full bg-white/95 text-[#10211D] flex items-center justify-center shadow-md border border-slate-200">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="22" y1="12" x2="18" y2="12"/><line x1="6" y1="12" x2="2" y2="12"/><line x1="12" y1="6" x2="12" y2="2"/><line x1="12" y1="22" x2="12" y2="18"/></svg>
                        </span>
                    </div>

                    {{-- Stepper Lokasi → Selfie → Kirim --}}
                    <div class="flex items-center px-2.5 pt-3 pb-1.5 text-xs font-bold">
                        <span class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-[#C6F26B] text-[#0B2B25] flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span class="text-[12.5px] text-[#0B2B25]">Lokasi</span>
                        </span>
                        <span class="mk-line flex-1 h-0.5 bg-[#C6F26B] mx-2 rounded-full"></span>
                        <span class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full border-2 border-[#0B2B25] text-[#0B2B25] flex items-center justify-center font-extrabold text-[11px]">2</span>
                            <span class="text-[12.5px] text-[#0B2B25]">Selfie</span>
                        </span>
                        <span class="flex-1 h-0.5 bg-[#DCE3DB] mx-2 rounded-full"></span>
                        <span class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full border-2 border-[#A9B5AF] text-[#5A6B65] flex items-center justify-center font-extrabold text-[11px]">3</span>
                            <span class="text-[12.5px] text-[#5A6B65]">Kirim</span>
                        </span>
                    </div>
                </div>

                {{-- Tombol presensi --}}
                <span class="mk-pop mt-3.5 flex items-center justify-between h-[60px] rounded-[30px] bg-[#0B2B25] text-white px-2 pl-6 shadow-[0_8px_20px_rgba(11,43,37,0.25)]" style="--d: 1150ms">
                    <span class="font-bold text-base tracking-wide">Presensi Masuk</span>
                    <span class="mk-nudge w-11 h-11 rounded-full bg-[#C6F26B] text-[#0B2B25] flex items-center justify-center shrink-0">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </span>
                </span>

                {{-- Izin / Sakit / Dinas Luar --}}
                <div class="mk-pop mt-2.5 flex items-center gap-2" style="--d: 1250ms">
                    @foreach([
                        ['Izin', 'bg-[#DDF3EC] text-[#0B5E4E]', '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>'],
                        ['Sakit', 'bg-[#FFF1D6] text-[#7A4B00]', '<path d="M12 5v14M5 12h14"/>'],
                        ['Dinas Luar', 'bg-[#E0EBF5] text-[#1E5680]', '<rect width="18" height="13" x="3" y="6" rx="2"/><path d="M16 6V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>'],
                    ] as [$label, $tone, $icon])
                        <span class="flex-1 h-11 rounded-full bg-white border border-[#CBD5CA]/70 flex items-center justify-center gap-1.5 px-2 text-[#0B2B25] font-bold text-xs">
                            <span class="w-6 h-6 rounded-full {{ $tone }} flex items-center justify-center shrink-0">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">{!! $icon !!}</svg>
                            </span>
                            <span class="truncate">{{ $label }}</span>
                        </span>
                    @endforeach
                </div>

                {{-- Metrik --}}
                <div class="mk-pop mt-3 grid grid-cols-2 gap-3" style="--d: 1350ms">
                    <div class="bg-white rounded-[20px] p-3 px-3.5 flex items-center gap-3 shadow-sm border border-slate-100">
                        <svg width="52" height="52" viewBox="0 0 52 52" class="shrink-0 -rotate-90">
                            <circle cx="26" cy="26" r="22" fill="none" stroke="#E3E9E0" stroke-width="5"/>
                            <circle class="mk-ring" cx="26" cy="26" r="22" fill="none" stroke="#0F7A66" stroke-width="5" stroke-linecap="round" stroke-dasharray="138" stroke-dashoffset="11"/>
                        </svg>
                        <div class="flex flex-col">
                            <span class="text-xs text-[#5A6B65] font-semibold">Kehadiran</span>
                            <span class="font-space text-2xl font-bold leading-tight text-[#10211D]">92%</span>
                        </div>
                    </div>
                    <div class="bg-white rounded-[20px] p-3 px-3.5 flex flex-col justify-center gap-1.5 shadow-sm border border-slate-100">
                        <div class="flex items-baseline justify-between">
                            <span class="text-xs text-[#5A6B65] font-semibold">Jurnal terisi</span>
                            <span class="font-space text-[22px] font-bold leading-tight text-[#10211D]">18<span class="text-sm font-sans font-normal text-[#5A6B65]">/20</span></span>
                        </div>
                        <div class="h-2 rounded-full bg-[#E3E9E0] overflow-hidden"><div class="mk-bar h-2 rounded-full bg-[#0F7A66]" style="width: 90%"></div></div>
                    </div>
                </div>

                {{-- Pengingat jurnal --}}
                <span class="mk-pop mt-3 bg-white rounded-[20px] p-3 px-3.5 flex items-center gap-3 shadow-sm border border-slate-100" style="--d: 1450ms">
                    <span class="w-11 h-11 rounded-[14px] bg-[#FFF1D6] text-[#7A4B00] flex items-center justify-center shrink-0">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    </span>
                    <span class="flex-1 flex flex-col min-w-0">
                        <span class="text-sm font-bold text-[#10211D] truncate">Jurnal hari ini belum diisi</span>
                        <span class="text-xs text-[#5A6B65] truncate">Isi sebelum presensi pulang</span>
                    </span>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#5A6B65" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="m9 18 6-6-6-6"/></svg>
                </span>
            </div>

            {{-- Dock navigasi bawah (Beranda · SOS · Jurnal) --}}
            <nav class="absolute left-1/2 -translate-x-1/2 top-[805px] w-[328px] h-[68px] rounded-[34px] bg-[#0B2B25] flex items-center justify-between px-3 shadow-[0_12px_30px_rgba(11,43,37,0.35)] border border-white/10">
                <span class="flex items-center gap-2 h-12 px-4 rounded-[24px] text-[13.5px] font-extrabold bg-[#C6F26B] text-[#0B2B25]">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>
                    Beranda
                </span>
                <span class="relative flex items-center justify-center w-[52px] h-[52px] rounded-full bg-[#C8261A] text-white font-space font-bold text-[15px] tracking-wider mk-sos">SOS</span>
                <span class="flex items-center gap-2 h-12 px-4 rounded-[24px] text-[13.5px] font-extrabold text-[#B7CEC5]">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H19a1 1 0 0 1 1 1v18a1 1 0 0 1-1 1H6.5a2.5 2.5 0 0 1-2.5-2.5z"/><path d="M8 7h8M8 11h8M8 15h5"/></svg>
                    Jurnal
                </span>
            </nav>
        </div>
    </div>
</div>
