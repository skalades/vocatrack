<!-- Dialog sistem bertema VokaTrack (pengganti alert()/confirm() bawaan browser) -->
<div id="vt-dialog" class="hidden fixed inset-0 z-[70] font-sans" role="dialog" aria-modal="true" aria-labelledby="vt-dialog-title">
    <div id="vt-dialog-backdrop" class="absolute inset-0 bg-[#071915]/70 backdrop-blur-sm opacity-0 transition-opacity duration-200"></div>

    <div class="relative w-full max-w-[420px] h-full mx-auto flex items-end sm:items-center p-3 sm:p-5 box-border pointer-events-none">
        <div id="vt-dialog-panel"
             class="pointer-events-auto w-full bg-white text-[#10211D] rounded-[28px] shadow-[0_24px_60px_rgba(7,25,21,0.45)] overflow-hidden translate-y-6 opacity-0 transition-all duration-200 ease-out">

            <!-- Tone strip -->
            <div id="vt-dialog-strip" class="h-1.5 w-full bg-[#B42318]"></div>

            <div class="p-5 pt-6">
                <div class="flex items-start gap-3.5">
                    <div id="vt-dialog-icon" class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0"></div>
                    <div class="min-w-0 flex-1 pt-0.5">
                        <span id="vt-dialog-eyebrow" class="text-[10px] font-extrabold uppercase tracking-[0.12em] block"></span>
                        <h2 id="vt-dialog-title" class="font-space font-bold text-lg leading-snug mt-0.5"></h2>
                    </div>
                </div>

                <p id="vt-dialog-message" class="text-[13px] leading-relaxed text-[#4A5B55] mt-3.5 whitespace-pre-line"></p>

                <!-- Detail rows (label / value) -->
                <div id="vt-dialog-details" class="hidden grid grid-cols-2 gap-2 mt-3.5"></div>

                <!-- Hint box -->
                <div id="vt-dialog-hint" class="hidden mt-3.5 flex gap-2.5 p-3 rounded-2xl bg-[#F1F6F3] border border-[#DCE8E2] text-[12px] leading-relaxed text-[#36504A]">
                    <svg class="w-4 h-4 mt-0.5 shrink-0 text-[#0F7A66]" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 16v-4M12 8h.01"/></svg>
                    <span id="vt-dialog-hint-text"></span>
                </div>

                <div class="flex flex-col gap-2 mt-5">
                    <button type="button" id="vt-dialog-confirm"
                            class="w-full h-12 rounded-[22px] bg-[#0B2B25] hover:bg-[#12382F] text-white font-bold text-sm active:scale-[0.98] transition-all cursor-pointer"></button>
                    <button type="button" id="vt-dialog-cancel"
                            class="w-full h-11 rounded-[22px] text-[#4A5B55] hover:bg-slate-100 font-bold text-sm active:scale-[0.98] transition-all cursor-pointer"></button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const TONES = {
        danger:  { strip: 'bg-[#B42318]', icon: 'bg-[#FBE4E1] text-[#B42318]', eyebrow: 'text-[#B42318]', label: 'Perhatian',
                   svg: '<path d="M12 9v4M12 17h.01"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>' },
        warning: { strip: 'bg-[#E0A21B]', icon: 'bg-[#FFF1D6] text-[#7A4B00]', eyebrow: 'text-[#7A4B00]', label: 'Peringatan',
                   svg: '<circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>' },
        info:    { strip: 'bg-[#0F7A66]', icon: 'bg-[#DDF3EC] text-[#0B5E4E]', eyebrow: 'text-[#0B5E4E]', label: 'Informasi',
                   svg: '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>' },
        location:{ strip: 'bg-[#0F7A66]', icon: 'bg-[#DDF3EC] text-[#0B5E4E]', eyebrow: 'text-[#0B5E4E]', label: 'Lokasi',
                   svg: '<path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"/><circle cx="12" cy="10" r="3"/>' },
        logout:  { strip: 'bg-[#0B2B25]', icon: 'bg-[#EEF3F1] text-[#0B2B25]', eyebrow: 'text-[#5A6B65]', label: 'Akun',
                   svg: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>' },
    };

    const $ = (id) => document.getElementById(id);
    let resolver = null;

    function close(result) {
        const root = $('vt-dialog');
        if (!root || root.classList.contains('hidden')) return;
        $('vt-dialog-backdrop').classList.add('opacity-0');
        $('vt-dialog-panel').classList.add('translate-y-6', 'opacity-0');
        setTimeout(() => {
            root.classList.add('hidden');
            document.body.style.overflow = root.dataset.prevOverflow || '';
        }, 180);
        const r = resolver; resolver = null;
        if (r) r(result);
    }

    /**
     * window.vtDialog({ tone, eyebrow, title, message, details: [[label, value], ...], hint,
     *                   confirmText, cancelText }) → Promise<boolean>
     * cancelText: null → dialog informasi satu tombol (pengganti alert).
     */
    window.vtDialog = function (opts = {}) {
        const root = $('vt-dialog');
        if (!root) return Promise.resolve(window.confirm(opts.message || opts.title || ''));
        if (resolver) close(false);

        const tone = TONES[opts.tone] || TONES.info;
        $('vt-dialog-strip').className = 'h-1.5 w-full ' + tone.strip;
        $('vt-dialog-icon').className = 'w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 ' + tone.icon;
        $('vt-dialog-icon').innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">' + tone.svg + '</svg>';
        $('vt-dialog-eyebrow').className = 'text-[10px] font-extrabold uppercase tracking-[0.12em] block ' + tone.eyebrow;
        $('vt-dialog-eyebrow').textContent = opts.eyebrow || tone.label;
        $('vt-dialog-title').textContent = opts.title || '';

        const msg = $('vt-dialog-message');
        msg.textContent = opts.message || '';
        msg.classList.toggle('hidden', !opts.message);

        const details = $('vt-dialog-details');
        details.innerHTML = '';
        (opts.details || []).forEach(([label, value]) => {
            const cell = document.createElement('div');
            cell.className = 'rounded-2xl bg-[#F6F8F7] border border-slate-100 px-3 py-2.5';
            const l = document.createElement('span');
            l.className = 'text-[10px] font-bold text-[#5A6B65] uppercase tracking-wider block';
            l.textContent = label;
            const v = document.createElement('span');
            v.className = 'text-sm font-bold text-[#10211D] block mt-0.5 tabular-nums';
            v.textContent = value;
            cell.append(l, v);
            details.appendChild(cell);
        });
        details.classList.toggle('hidden', !(opts.details && opts.details.length));

        $('vt-dialog-hint-text').textContent = opts.hint || '';
        $('vt-dialog-hint').classList.toggle('hidden', !opts.hint);

        const confirmBtn = $('vt-dialog-confirm');
        confirmBtn.textContent = opts.confirmText || 'Mengerti';
        confirmBtn.className = 'w-full h-12 rounded-[22px] font-bold text-sm active:scale-[0.98] transition-all cursor-pointer ' +
            (opts.tone === 'danger' && opts.destructive ? 'bg-[#B42318] hover:bg-[#9A1D14] text-white' : 'bg-[#0B2B25] hover:bg-[#12382F] text-white');
        const cancelBtn = $('vt-dialog-cancel');
        const hasCancel = opts.cancelText !== null;
        cancelBtn.textContent = opts.cancelText || 'Batal';
        cancelBtn.classList.toggle('hidden', !hasCancel);

        root.dataset.prevOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        root.classList.remove('hidden');
        requestAnimationFrame(() => {
            $('vt-dialog-backdrop').classList.remove('opacity-0');
            $('vt-dialog-panel').classList.remove('translate-y-6', 'opacity-0');
            confirmBtn.focus({ preventScroll: true });
        });

        return new Promise((resolve) => { resolver = resolve; });
    };

    /** Pengganti alert(): satu tombol. */
    window.vtAlert = function (opts) {
        return window.vtDialog(Object.assign({ cancelText: null }, opts));
    };

    document.addEventListener('DOMContentLoaded', () => {
        $('vt-dialog-confirm').addEventListener('click', () => close(true));
        $('vt-dialog-cancel').addEventListener('click', () => close(false));
        $('vt-dialog-backdrop').addEventListener('click', () => close(false));
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(false); });

    /* Form dengan atribut data-confirm="Pesan" → konfirmasi lewat dialog bertema.
       Opsional: data-confirm-title, data-confirm-ok, data-confirm-tone. */
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirm || form.dataset.confirmed === '1') return;
        e.preventDefault();
        window.vtDialog({
            tone: form.dataset.confirmTone || 'warning',
            title: form.dataset.confirmTitle || 'Konfirmasi',
            message: form.dataset.confirm,
            confirmText: form.dataset.confirmOk || 'Ya, lanjutkan',
            cancelText: 'Batal',
            destructive: true,
        }).then((ok) => {
            if (!ok) return;
            form.dataset.confirmed = '1';
            form.requestSubmit ? form.requestSubmit(e.submitter || undefined) : form.submit();
        });
    }, true);
})();
</script>
