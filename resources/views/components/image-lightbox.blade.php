<!-- Global High-Resolution Image Lightbox with Zoom & Pan for Mobile & Desktop -->
<div id="image-lightbox-modal" class="hidden fixed inset-0 z-[100] bg-slate-950/95 backdrop-blur-md flex flex-col justify-between select-none transition-opacity duration-200" style="touch-action: none;">
    
    <!-- Top Toolbar -->
    <div class="px-4 py-3 bg-slate-900/90 border-b border-white/10 flex items-center justify-between gap-3 text-white shrink-0 z-10">
        <div class="flex items-center gap-2 min-w-0">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="text-xs font-extrabold tracking-tight truncate">Resolusi Penuh</span>
            <span id="lightbox-zoom-level" class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-white/10 text-slate-300 font-bold">100%</span>
        </div>

        <!-- Zoom Control Buttons -->
        <div class="flex items-center gap-1.5 sm:gap-2">
            <!-- Zoom In -->
            <button type="button" onclick="lightboxZoomIn()" title="Perbesar (+)" class="p-2 rounded-xl bg-white/10 hover:bg-white/20 active:scale-95 text-white transition-all cursor-pointer">
                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            </button>

            <!-- Zoom Out -->
            <button type="button" onclick="lightboxZoomOut()" title="Perkecil (-)" class="p-2 rounded-xl bg-white/10 hover:bg-white/20 active:scale-95 text-white transition-all cursor-pointer">
                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
            </button>

            <!-- Reset Zoom -->
            <button type="button" onclick="lightboxResetZoom()" title="Reset Ukuran" class="p-2 rounded-xl bg-white/10 hover:bg-white/20 active:scale-95 text-white transition-all cursor-pointer text-xs font-bold">
                <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
            </button>

            <!-- Open Original in New Tab -->
            <a id="lightbox-download-link" href="#" target="_blank" title="Buka Tab Baru" class="p-2 rounded-xl bg-white/10 hover:bg-white/20 active:scale-95 text-white transition-all cursor-pointer">
                <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>

            <!-- Close Lightbox -->
            <button type="button" onclick="closeImageLightbox()" title="Tutup (Esc)" class="p-2 ml-1 rounded-xl bg-rose-600/80 hover:bg-rose-600 active:scale-95 text-white transition-all cursor-pointer">
                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    <!-- Center Viewport (Pannable & Zoomable) -->
    <div id="lightbox-viewport" class="relative flex-1 w-full h-full overflow-hidden flex items-center justify-center p-2 cursor-grab active:cursor-grabbing">
        <img id="lightbox-image" 
             src="" 
             alt="Gambar Diperbesar" 
             class="max-w-full max-h-full object-contain pointer-events-none transition-transform duration-75 ease-out rounded-lg shadow-2xl"
             style="transform: translate(0px, 0px) scale(1);">
    </div>

    <!-- Bottom Hint Bar -->
    <div class="px-4 py-2.5 bg-slate-900/90 border-t border-white/10 flex items-center justify-between text-[11px] text-slate-300 shrink-0 z-10">
        <span class="flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Ketuk 2x untuk zoom • Geser untuk menggeser teks gambar</span>
        </span>
        <button type="button" onclick="closeImageLightbox()" class="text-xs font-bold text-indigo-300 hover:text-white transition-colors cursor-pointer">
            Kembali
        </button>
    </div>
</div>

<script>
    let currentLightboxScale = 1;
    let lightboxTranslateX = 0;
    let lightboxTranslateY = 0;
    let isLightboxDragging = false;
    let lightboxStartX = 0;
    let lightboxStartY = 0;
    let lastTapTime = 0;

    function openImageLightbox(src) {
        if (!src) return;

        const modal = document.getElementById('image-lightbox-modal');
        const img = document.getElementById('lightbox-image');
        const downloadLink = document.getElementById('lightbox-download-link');

        img.src = src;
        downloadLink.href = src;

        currentLightboxScale = 1;
        lightboxTranslateX = 0;
        lightboxTranslateY = 0;
        updateLightboxTransform();

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeImageLightbox() {
        const modal = document.getElementById('image-lightbox-modal');
        modal.classList.add('hidden');
        
        // Restore parent modal overflow if open
        const parentModal = document.getElementById('home-announcement-modal') || document.getElementById('announcement-modal');
        if (parentModal && !parentModal.classList.contains('hidden')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }

    function updateLightboxTransform() {
        const img = document.getElementById('lightbox-image');
        const zoomLabel = document.getElementById('lightbox-zoom-level');
        if (img) {
            img.style.transform = `translate(${lightboxTranslateX}px, ${lightboxTranslateY}px) scale(${currentLightboxScale})`;
        }
        if (zoomLabel) {
            zoomLabel.textContent = `${Math.round(currentLightboxScale * 100)}%`;
        }
    }

    function lightboxZoomIn() {
        if (currentLightboxScale < 4.0) {
            currentLightboxScale = Math.min(4.0, parseFloat((currentLightboxScale + 0.35).toFixed(2)));
            updateLightboxTransform();
        }
    }

    function lightboxZoomOut() {
        if (currentLightboxScale > 0.6) {
            currentLightboxScale = Math.max(0.6, parseFloat((currentLightboxScale - 0.35).toFixed(2)));
            if (currentLightboxScale <= 1) {
                lightboxTranslateX = 0;
                lightboxTranslateY = 0;
            }
            updateLightboxTransform();
        }
    }

    function lightboxResetZoom() {
        currentLightboxScale = 1;
        lightboxTranslateX = 0;
        lightboxTranslateY = 0;
        updateLightboxTransform();
    }

    // Viewport drag & touch handler
    document.addEventListener('DOMContentLoaded', function() {
        const viewport = document.getElementById('lightbox-viewport');
        if (!viewport) return;

        // Mouse events
        viewport.addEventListener('mousedown', function(e) {
            isLightboxDragging = true;
            lightboxStartX = e.clientX - lightboxTranslateX;
            lightboxStartY = e.clientY - lightboxTranslateY;
        });

        window.addEventListener('mousemove', function(e) {
            if (!isLightboxDragging) return;
            lightboxTranslateX = e.clientX - lightboxStartX;
            lightboxTranslateY = e.clientY - lightboxStartY;
            updateLightboxTransform();
        });

        window.addEventListener('mouseup', function() {
            isLightboxDragging = false;
        });

        // Wheel to zoom
        viewport.addEventListener('wheel', function(e) {
            e.preventDefault();
            if (e.deltaY < 0) {
                lightboxZoomIn();
            } else {
                lightboxZoomOut();
            }
        }, { passive: false });

        // Touch events for mobile drag
        viewport.addEventListener('touchstart', function(e) {
            const currentTime = new Date().getTime();
            const tapGap = currentTime - lastTapTime;

            // Double tap to toggle zoom 1x <-> 2.2x
            if (tapGap < 300 && tapGap > 0) {
                e.preventDefault();
                if (currentLightboxScale > 1.2) {
                    lightboxResetZoom();
                } else {
                    currentLightboxScale = 2.2;
                    updateLightboxTransform();
                }
                lastTapTime = 0;
                return;
            }
            lastTapTime = currentTime;

            if (e.touches.length === 1) {
                isLightboxDragging = true;
                lightboxStartX = e.touches[0].clientX - lightboxTranslateX;
                lightboxStartY = e.touches[0].clientY - lightboxTranslateY;
            }
        }, { passive: false });

        viewport.addEventListener('touchmove', function(e) {
            if (!isLightboxDragging || e.touches.length !== 1) return;
            e.preventDefault();
            lightboxTranslateX = e.touches[0].clientX - lightboxStartX;
            lightboxTranslateY = e.touches[0].clientY - lightboxStartY;
            updateLightboxTransform();
        }, { passive: false });

        viewport.addEventListener('touchend', function() {
            isLightboxDragging = false;
        });

        // Close on ESC
        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('image-lightbox-modal');
                if (modal && !modal.classList.contains('hidden')) {
                    e.stopImmediatePropagation();
                    closeImageLightbox();
                }
            }
        });
    });
</script>
