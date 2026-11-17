@php
    $appName = $systemName ?? 'VokaTrack';
    $logo = $systemLogo ?? asset('images/logo.png');
@endphp

<div id="student-pwa-card" class="mt-3.5 bg-white rounded-[24px] p-4 sm:p-5 border border-[#0B2B25]/10 shadow-[0_4px_20px_rgba(11,43,37,0.04)] relative overflow-hidden transition-all">
    {{-- Ambient subtle accent --}}
    <div class="absolute -right-6 -bottom-6 w-28 h-28 rounded-full bg-[#C6F26B]/15 pointer-events-none blur-xl" aria-hidden="true"></div>

    <div class="relative z-10 flex flex-col gap-3">
        {{-- Header row --}}
        <div class="flex items-start gap-3">
            <div class="w-11 h-11 rounded-2xl bg-[#0B2B25]/5 border border-[#0B2B25]/10 p-1.5 flex items-center justify-center shrink-0">
                <img src="{{ $logo }}" alt="{{ $appName }} Logo" class="w-full h-full object-contain">
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <h3 class="font-bold text-sm text-[#10211D] truncate">Aplikasi {{ $appName }} di HP</h3>
                    <span id="pwa-card-badge" class="bg-[#C6F26B]/50 text-[#0B2B25] text-[10px] font-extrabold px-2 py-0.5 rounded-full shrink-0">
                        PWA Ready
                    </span>
                </div>
                <p class="text-xs text-[#5A6B65] mt-0.5 leading-snug">
                    Buka lebih cepat dari layar utama &amp; tetap bisa mengisi jurnal tanpa sinyal.
                </p>
            </div>
        </div>

        {{-- Keunggulan ringkas --}}
        <div class="grid grid-cols-2 gap-2 pt-1 text-[11px] font-semibold text-[#0B2B25]/80">
            <div class="flex items-center gap-1.5 bg-[#F1F4EC] rounded-xl px-2.5 py-1.5 border border-[#0B2B25]/5">
                <svg class="w-3.5 h-3.5 text-[#0F7A66] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span class="truncate">Jurnal tersimpan offline</span>
            </div>
            <div class="flex items-center gap-1.5 bg-[#F1F4EC] rounded-xl px-2.5 py-1.5 border border-[#0B2B25]/5">
                <svg class="w-3.5 h-3.5 text-[#0F7A66] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span class="truncate">Tanpa unduh Play Store</span>
            </div>
        </div>

        {{-- Tombol aksi pasang --}}
        <div id="pwa-card-action" class="pt-1">
            <button type="button"
                    id="btn-install-pwa-card"
                    onclick="if(window.VokaTrackPwa?.promptInstall){window.VokaTrackPwa.promptInstall()}else{location.reload()}"
                    class="w-full h-11 rounded-2xl bg-[#0B2B25] hover:bg-[#12382F] active:scale-[0.98] text-[#C6F26B] font-bold text-xs flex items-center justify-center gap-2 shadow-sm transition-all cursor-pointer">
                <svg class="w-4 h-4 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Pasang Aplikasi di Layar Utama HP</span>
            </button>
        </div>
    </div>
</div>
