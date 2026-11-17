<!-- Dialog sistem bertema VokaTrack Mobile (pengganti alert()/confirm() bawaan browser) -->
<div id="vt-dialog" class="hidden fixed inset-0 z-[85] font-sans flex flex-col justify-start sm:justify-center items-center p-3 pt-14 sm:pt-16 transition-all" role="dialog" aria-modal="true" aria-labelledby="vt-dialog-title">
    <div id="vt-dialog-backdrop" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs opacity-0 transition-opacity duration-200"></div>

    <div id="vt-dialog-panel"
         class="relative bg-white rounded-3xl max-h-[85vh] flex flex-col w-full max-w-[400px] shadow-2xl border border-slate-200/80 overflow-hidden transform -translate-y-4 opacity-0 transition-all duration-200 ease-out z-10">

        <!-- Header -->
        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between shrink-0 bg-white">
            <div class="flex items-center space-x-2.5 min-w-0 flex-1 pr-2">
                <div id="vt-dialog-icon" class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg flex items-center justify-center font-bold text-xs shrink-0 transition-colors"></div>
                <div class="min-w-0 flex-1">
                    <h3 id="vt-dialog-title" class="text-xs sm:text-sm font-extrabold text-[#10211D] truncate leading-tight font-space"></h3>
                    <p id="vt-dialog-eyebrow" class="text-[10px] sm:text-[11px] text-slate-400 font-medium truncate mt-0.5 leading-tight"></p>
                </div>
            </div>
            <button type="button" id="vt-dialog-close-btn" aria-label="Tutup"
                    class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Body / Content -->
        <div class="p-4 sm:p-5 overflow-y-auto space-y-3 flex-1 custom-scrollbar">
            <!-- Card Konten (mirip kartu pusat pemberitahuan) -->
            <div id="vt-dialog-card" class="p-3.5 rounded-2xl bg-[#F4FAF6] border border-[#D5EADB] transition-colors space-y-1.5">
                <div id="vt-dialog-card-header" class="hidden flex items-start justify-between gap-2">
                    <span id="vt-dialog-card-title" class="text-xs font-bold text-[#0B2B25] font-black leading-snug"></span>
                    <span id="vt-dialog-badge" class="hidden text-[10px] text-slate-400 shrink-0 font-medium whitespace-nowrap"></span>
                </div>
                
                <p id="vt-dialog-message" class="text-[11.5px] sm:text-xs leading-relaxed text-[#4A5B55] whitespace-pre-line"></p>

                <div id="vt-dialog-action-wrapper" class="hidden pt-1.5 flex items-center justify-between">
                    <a href="javascript:void(0)" id="vt-dialog-action-link" class="text-[11px] font-bold text-[#0F7A66] hover:text-[#0B2B25] inline-flex items-center gap-1 cursor-pointer">
                        <span id="vt-dialog-action-text">Buka Detail</span>
                        <svg class="w-3 h-3 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>

            <!-- Detail rows (grid 2 cols) -->
            <div id="vt-dialog-details" class="hidden grid grid-cols-2 gap-2"></div>

            <!-- Hint box -->
            <div id="vt-dialog-hint" class="hidden flex gap-2.5 p-3 rounded-2xl bg-[#F1F6F3] border border-[#DCE8E2] text-[11.5px] leading-relaxed text-[#36504A]">
                <svg class="w-4 h-4 mt-0.5 shrink-0 text-[#0F7A66]" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 16v-4M12 8h.01"/></svg>
                <span id="vt-dialog-hint-text"></span>
            </div>
        </div>

        <!-- Action Buttons / Footer -->
        <div id="vt-dialog-footer" class="p-4 pt-3 border-t border-slate-100 shrink-0 flex items-center gap-2.5 bg-white">
            <button type="button" id="vt-dialog-cancel"
                    class="flex-1 h-11 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs sm:text-sm active:scale-[0.98] transition-all cursor-pointer">
                Batal
            </button>
            <button type="button" id="vt-dialog-confirm"
                    class="flex-1 h-11 rounded-2xl bg-[#0B2B25] hover:bg-[#12382F] text-white font-bold text-xs sm:text-sm shadow-xs active:scale-[0.98] transition-all cursor-pointer">
                Lanjutkan
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    const TONES = {
        danger: {
            icon: 'bg-[#FBE4E1] text-[#B42318]',
            card: 'bg-rose-50/70 border border-rose-200/80',
            cardTitle: 'text-rose-950',
            eyebrow: 'text-rose-600',
            action: 'text-[#B42318]',
            label: 'Perhatian',
            svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>'
        },
        warning: {
            icon: 'bg-[#FFF1D6] text-[#7A4B00]',
            card: 'bg-amber-50/70 border border-amber-200/80',
            cardTitle: 'text-amber-950',
            eyebrow: 'text-amber-700',
            action: 'text-amber-700',
            label: 'Peringatan',
            svg: '<circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01"/>'
        },
        info: {
            icon: 'bg-[#EBF6F0] text-[#0F7A66]',
            card: 'bg-[#F4FAF6] border border-[#D5EADB]',
            cardTitle: 'text-[#0B2B25]',
            eyebrow: 'text-[#0F7A66]',
            action: 'text-[#0F7A66]',
            label: 'Informasi',
            svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>'
        },
        location: {
            icon: 'bg-[#EBF6F0] text-[#0F7A66]',
            card: 'bg-[#F4FAF6] border border-[#D5EADB]',
            cardTitle: 'text-[#0B2B25]',
            eyebrow: 'text-[#0F7A66]',
            action: 'text-[#0F7A66]',
            label: 'Lokasi',
            svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><circle cx="12" cy="11" r="3" stroke-width="2"/>'
        },
        logout: {
            icon: 'bg-slate-100 text-[#0B2B25]',
            card: 'bg-slate-50 border border-slate-200/80',
            cardTitle: 'text-slate-900',
            eyebrow: 'text-slate-500',
            action: 'text-[#0B2B25]',
            label: 'Akun',
            svg: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>'
        },
    };

    const $ = (id) => document.getElementById(id);
    let resolver = null;

    function close(result) {
        const root = $('vt-dialog');
        if (!root || root.classList.contains('hidden')) return;
        $('vt-dialog-backdrop').classList.add('opacity-0');
        $('vt-dialog-panel').classList.remove('translate-y-0', 'opacity-100');
        $('vt-dialog-panel').classList.add('-translate-y-4', 'opacity-0');
        setTimeout(() => {
            root.classList.add('hidden');
            document.body.style.overflow = root.dataset.prevOverflow || '';
        }, 180);
        const r = resolver; resolver = null;
        if (r) r(result);
    }

    /**
     * window.vtDialog({ tone, eyebrow, title, message, cardTitle, badge, actionText, actionUrl, onAction,
     *                   details: [[label, value], ...], hint, confirmText, cancelText, destructive }) → Promise<boolean>
     * cancelText: null → dialog informasi satu tombol (pengganti alert).
     */
    window.vtDialog = function (opts = {}) {
        const root = $('vt-dialog');
        if (!root) return Promise.resolve(window.confirm(opts.message || opts.title || ''));
        if (resolver) close(false);

        const tone = TONES[opts.tone] || TONES.info;

        // Set Icon
        const iconEl = $('vt-dialog-icon');
        iconEl.className = 'w-7 h-7 sm:w-8 sm:h-8 rounded-lg flex items-center justify-center font-bold text-xs shrink-0 transition-colors ' + tone.icon;
        iconEl.innerHTML = '<svg class="w-4 h-4 sm:w-4.5 sm:h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">' + tone.svg + '</svg>';

        // Set Eyebrow & Title
        const eyebrowEl = $('vt-dialog-eyebrow');
        eyebrowEl.className = 'text-[10px] sm:text-[11px] font-medium truncate mt-0.5 leading-tight ' + (opts.eyebrowColor || tone.eyebrow || 'text-slate-400');
        eyebrowEl.textContent = opts.eyebrow || tone.label;

        $('vt-dialog-title').textContent = opts.title || '';

        // Card Styling & Content
        const cardEl = $('vt-dialog-card');
        cardEl.className = 'p-3.5 rounded-2xl transition-colors space-y-1.5 ' + (tone.card || 'bg-[#F4FAF6] border border-[#D5EADB]');

        const cardHeader = $('vt-dialog-card-header');
        const cardTitle = $('vt-dialog-card-title');
        const badgeEl = $('vt-dialog-badge');

        if (opts.cardTitle) {
            cardTitle.textContent = opts.cardTitle;
            cardTitle.className = 'text-xs font-bold leading-snug ' + (tone.cardTitle || 'text-[#0B2B25]');
            if (opts.badge) {
                badgeEl.textContent = opts.badge;
                badgeEl.classList.remove('hidden');
            } else {
                badgeEl.classList.add('hidden');
            }
            cardHeader.classList.remove('hidden');
            cardHeader.classList.add('flex');
        } else {
            cardHeader.classList.add('hidden');
            cardHeader.classList.remove('flex');
        }

        const msg = $('vt-dialog-message');
        msg.textContent = opts.message || '';
        msg.classList.toggle('hidden', !opts.message);

        // Action link inside card
        const actionWrapper = $('vt-dialog-action-wrapper');
        const actionLink = $('vt-dialog-action-link');
        const actionText = $('vt-dialog-action-text');
        if (opts.actionText) {
            actionText.textContent = opts.actionText;
            actionLink.className = 'text-[11px] font-bold hover:opacity-80 inline-flex items-center gap-1 cursor-pointer ' + tone.action;
            actionLink.onclick = function (e) {
                if (typeof opts.onAction === 'function') {
                    e.preventDefault();
                    opts.onAction();
                } else if (opts.actionUrl) {
                    window.location.href = opts.actionUrl;
                }
            };
            actionWrapper.classList.remove('hidden');
        } else {
            actionWrapper.classList.add('hidden');
        }

        // Details Grid
        const details = $('vt-dialog-details');
        details.innerHTML = '';
        (opts.details || []).forEach(([label, value]) => {
            const cell = document.createElement('div');
            cell.className = 'rounded-2xl bg-slate-50 border border-slate-200/80 px-3 py-2.5';
            const l = document.createElement('span');
            l.className = 'text-[10px] font-bold text-slate-400 uppercase tracking-wider block';
            l.textContent = label;
            const v = document.createElement('span');
            v.className = 'text-xs font-bold text-[#10211D] block mt-0.5 tabular-nums';
            v.textContent = value;
            cell.append(l, v);
            details.appendChild(cell);
        });
        details.classList.toggle('hidden', !(opts.details && opts.details.length));

        // Hint Box
        $('vt-dialog-hint-text').textContent = opts.hint || '';
        $('vt-dialog-hint').classList.toggle('hidden', !opts.hint);

        // Action Buttons
        const confirmBtn = $('vt-dialog-confirm');
        confirmBtn.textContent = opts.confirmText || 'Mengerti';
        const cancelBtn = $('vt-dialog-cancel');
        const hasCancel = opts.cancelText !== null && opts.cancelText !== false;
        
        cancelBtn.textContent = opts.cancelText || 'Batal';
        cancelBtn.classList.toggle('hidden', !hasCancel);

        const isDestructive = (opts.tone === 'danger' && opts.destructive) || opts.destructive;
        const confirmColor = isDestructive ? 'bg-[#B42318] hover:bg-[#9A1D14] text-white' : 'bg-[#0B2B25] hover:bg-[#12382F] text-white';

        if (hasCancel) {
            confirmBtn.className = 'flex-1 h-11 rounded-2xl font-bold text-xs sm:text-sm shadow-xs active:scale-[0.98] transition-all cursor-pointer ' + confirmColor;
            cancelBtn.className = 'flex-1 h-11 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs sm:text-sm active:scale-[0.98] transition-all cursor-pointer';
        } else {
            confirmBtn.className = 'w-full h-11 rounded-2xl font-bold text-xs sm:text-sm shadow-xs active:scale-[0.98] transition-all cursor-pointer ' + confirmColor;
        }

        root.dataset.prevOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        root.classList.remove('hidden');

        requestAnimationFrame(() => {
            $('vt-dialog-backdrop').classList.remove('opacity-0');
            $('vt-dialog-panel').classList.remove('-translate-y-4', 'opacity-0');
            $('vt-dialog-panel').classList.add('translate-y-0', 'opacity-100');
            confirmBtn.focus({ preventScroll: true });
        });

        return new Promise((resolve) => { resolver = resolve; });
    };

    /** Pengganti alert(): satu tombol informasi. */
    window.vtAlert = function (opts) {
        return window.vtDialog(Object.assign({ cancelText: null }, opts));
    };

    document.addEventListener('DOMContentLoaded', () => {
        const confirmBtn = $('vt-dialog-confirm');
        const cancelBtn = $('vt-dialog-cancel');
        const closeBtn = $('vt-dialog-close-btn');
        const backdrop = $('vt-dialog-backdrop');

        if (confirmBtn) confirmBtn.addEventListener('click', () => close(true));
        if (cancelBtn) cancelBtn.addEventListener('click', () => close(false));
        if (closeBtn) closeBtn.addEventListener('click', () => close(false));
        if (backdrop) backdrop.addEventListener('click', () => close(false));
    });

    document.addEventListener('keydown', (e) => { 
        if (e.key === 'Escape') close(false); 
    });

    /* Form dengan atribut data-confirm="Pesan" → konfirmasi lewat dialog bertema.
       Opsional: data-confirm-title, data-confirm-ok, data-confirm-tone. */
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirm || form.dataset.confirmed === '1') return;
        e.preventDefault();
        window.vtDialog({
            tone: form.dataset.confirmTone || 'warning',
            title: form.dataset.confirmTitle || 'Konfirmasi Tindakan',
            message: form.dataset.confirm,
            confirmText: form.dataset.confirmOk || 'Ya, lanjutkan',
            cancelText: form.dataset.confirmCancel || 'Batal',
            destructive: form.dataset.confirmTone === 'danger' || form.dataset.confirmTone === 'logout',
        }).then((ok) => {
            if (!ok) return;
            form.dataset.confirmed = '1';
            form.requestSubmit ? form.requestSubmit(e.submitter || undefined) : form.submit();
        });
    }, true);
})();
</script>
