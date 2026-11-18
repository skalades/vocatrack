@props(['systemLogo' => null])

<!-- Modal Detail Pengumuman (Floating Centered Modal Dialog) -->
<div id="announcement-modal" onclick="if(event.target === this) handleAnnouncementModalClose()"
     class="hidden fixed inset-0 z-[85] bg-slate-900/60 backdrop-blur-xs flex flex-col justify-start sm:justify-center items-center p-3 pt-14 sm:pt-16 transition-all"
     aria-modal="true" role="dialog">
    <div class="bg-white w-full max-w-[400px] rounded-3xl shadow-2xl border border-slate-200/80 max-h-[85vh] flex flex-col overflow-hidden animate-in zoom-in-95 duration-200 my-auto">

        <!-- Header -->
        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between shrink-0 bg-white">
            <div class="flex items-center space-x-2.5 min-w-0 flex-1 pr-2">
                <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-[#EBF6F0] text-[#0F7A66] flex items-center justify-center font-bold text-xs shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <h3 class="text-xs sm:text-sm font-extrabold text-[#10211D] truncate leading-tight font-space">Detail Pengumuman</h3>
                        <span id="modal-queue-indicator" class="hidden inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-[#DDF3EC] text-[#0B5E4E] shrink-0">
                            1 dari 1
                        </span>
                    </div>
                    <div class="flex items-center gap-1.5 mt-0.5 truncate">
                        <span id="modal-category-badge"></span>
                        <span id="modal-major-badge" class="inline-flex items-center h-4.5 px-2 rounded-full bg-[#EEF3F1] text-[#36504A] text-[9.5px] font-semibold truncate max-w-[140px]"></span>
                    </div>
                </div>
            </div>
            <button type="button" onclick="handleAnnouncementModalClose()" aria-label="Tutup" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="overflow-y-auto p-4 sm:p-5 space-y-3.5 flex-1 custom-scrollbar">
            <div id="modal-image-container" class="hidden relative rounded-2xl overflow-hidden bg-[#EEF3F1] cursor-zoom-in" onclick="if(window.openImageLightbox) window.openImageLightbox(document.getElementById('modal-image').src)" title="Ketuk untuk memperbesar">
                <img id="modal-image" src="" alt="Lampiran pengumuman" class="w-full h-auto max-h-60 object-contain mx-auto block"
                     onerror="document.getElementById('modal-image-container').classList.add('hidden')">
                <span class="absolute bottom-2.5 right-2.5 inline-flex items-center gap-1.5 h-7 px-2.5 rounded-full bg-[#0B2B25]/85 text-white text-[10.5px] font-bold pointer-events-none">
                    <svg class="w-3.5 h-3.5 text-[#C6F26B]" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                    Perbesar
                </span>
            </div>

            <h3 id="modal-title" class="font-space font-bold text-base leading-snug text-[#10211D] break-words"></h3>

            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-full bg-white border border-[#E3ECE8] p-1 flex items-center justify-center shrink-0">
                    <img src="{{ $systemLogo ?? asset('images/logo.png') }}" alt="" class="w-full h-full object-contain">
                </span>
                <div class="min-w-0 flex-1">
                    <span id="modal-author" class="block text-xs font-bold text-[#10211D] truncate"></span>
                    <span class="block text-[10.5px] text-[#5A6B65] truncate"><span id="modal-target"></span> · <span id="modal-date"></span></span>
                </div>
            </div>

            <div class="rounded-2xl bg-[#F4FAF6] border border-[#D5EADB] p-3.5">
                <p id="modal-content" class="text-xs leading-relaxed text-[#36504A] whitespace-pre-line break-words"></p>
            </div>
        </div>

        <div class="p-4 pt-3 border-t border-slate-100 shrink-0">
            <button type="button" id="modal-close-btn" onclick="handleAnnouncementModalClose()" class="w-full h-11 rounded-2xl bg-[#0B2B25] hover:bg-[#12382F] text-white text-xs sm:text-sm font-bold active:scale-[0.98] transition-all cursor-pointer flex items-center justify-center gap-1.5 shadow-sm">
                <span>Tutup</span>
            </button>
        </div>
    </div>
</div>
