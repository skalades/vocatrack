/**
 * VokaTrack PWA Siswa
 *
 * - Daftarkan service worker (/sw.js) agar aplikasi bisa dipasang & dibuka tanpa sinyal.
 * - Banner status koneksi + jumlah jurnal yang menunggu dikirim.
 * - Antrean jurnal offline (IndexedDB): jurnal yang disimpan saat tanpa sinyal dikirim
 *   otomatis begitu perangkat kembali online.
 * - Draft teks jurnal disimpan otomatis di perangkat agar tidak hilang saat sinyal putus.
 * - Form lain (presensi, izin, SOS) diblokir dengan pesan jelas saat offline.
 *
 * Data offline selalu diikat ke ID pengguna, sehingga perangkat bersama tidak
 * mengirim jurnal siswa lain.
 */

const body = document.body;
const USER_ID = body.dataset.userId || '';
const STORE_URL = body.dataset.journalStoreUrl || '';
const VAPID_PUBLIC_KEY = body.dataset.vapidPublicKey || '';
const PUSH_SUBSCRIBE_URL = body.dataset.pushSubscribeUrl || '/push-subscriptions';
const PUSH_UNSUBSCRIBE_URL = body.dataset.pushUnsubscribeUrl || '/push-subscriptions';
const PUSH_TEST_URL = body.dataset.pushTestUrl || '/push-subscriptions/test';
const APP_TZ = body.dataset.appTimezone || 'Asia/Jakarta';

const DB_NAME = 'vokatrack-pwa';
const DB_STORE = 'journal-queue';
const SYNC_TIMEOUT_MS = 45000;

// ---------------------------------------------------------------------------
// Utilitas
// ---------------------------------------------------------------------------

function safeStorage(fn, fallback = null) {
    try { return fn(); } catch (e) { return fallback; }
}

function todayInAppTz() {
    // en-CA menghasilkan format YYYY-MM-DD.
    return new Intl.DateTimeFormat('en-CA', { timeZone: APP_TZ }).format(new Date());
}

function formatDate(isoDate) {
    try {
        return new Date(`${isoDate}T00:00:00`).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long' });
    } catch (e) {
        return isoDate;
    }
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function alertDialog(opts) {
    if (window.vtAlert) return window.vtAlert(opts);
    window.alert(`${opts.title}\n\n${opts.message || ''}`);
    return Promise.resolve(true);
}

function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

// ---------------------------------------------------------------------------
// Service worker & cache halaman per pengguna
// ---------------------------------------------------------------------------

function postToServiceWorker(message) {
    navigator.serviceWorker?.controller?.postMessage(message);
}

async function clearPageCache() {
    postToServiceWorker({ type: 'clear-pages' });
    if (window.caches) {
        const keys = await caches.keys();
        await Promise.all(keys.filter((k) => k.startsWith('vt-pages-')).map((k) => caches.delete(k)));
    }
}

function registerServiceWorker() {
    if (!('serviceWorker' in navigator) || !window.isSecureContext) return;
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/', updateViaCache: 'none' }).catch(() => {});
    });
}

function bindCacheToUser() {
    // Halaman yang tersimpan milik pengguna sebelumnya dibuang saat akun berganti.
    const previous = safeStorage(() => localStorage.getItem('vt-pwa-user'));
    if (USER_ID && previous && previous !== USER_ID) {
        clearPageCache();
    }
    if (USER_ID) safeStorage(() => localStorage.setItem('vt-pwa-user', USER_ID));

    document.addEventListener('submit', (e) => {
        const action = e.target?.getAttribute?.('action') || '';
        if (/\/(logout|impersonate\/leave)$/.test(action)) {
            clearPageCache();
            safeStorage(() => localStorage.removeItem('vt-pwa-user'));
        }
    }, true);
}

// ---------------------------------------------------------------------------
// IndexedDB: antrean jurnal
// ---------------------------------------------------------------------------

let dbPromise = null;

function openDb() {
    if (!('indexedDB' in window)) return Promise.reject(new Error('IndexedDB tidak tersedia'));
    dbPromise ??= new Promise((resolve, reject) => {
        const req = indexedDB.open(DB_NAME, 1);
        req.onupgradeneeded = () => {
            const store = req.result.createObjectStore(DB_STORE, { keyPath: 'id', autoIncrement: true });
            store.createIndex('userId', 'userId');
        };
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
    return dbPromise;
}

async function tx(mode, fn) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const t = db.transaction(DB_STORE, mode);
        const result = fn(t.objectStore(DB_STORE));
        t.oncomplete = () => resolve(result?.result ?? result);
        t.onerror = () => reject(t.error);
        t.onabort = () => reject(t.error);
    });
}

async function queueAll() {
    if (!USER_ID) return [];
    try {
        const all = await tx('readonly', (s) => s.index('userId').getAll(USER_ID));
        return (all || []).sort((a, b) => a.createdAt - b.createdAt);
    } catch (e) {
        return [];
    }
}

const queuePut = (item) => tx('readwrite', (s) => s.put(item));
const queueDelete = (id) => tx('readwrite', (s) => s.delete(id));

async function enqueueJournal(form) {
    const fd = new FormData(form);
    const photo = fd.getAll('photos[]').find((f) => f instanceof File && f.size > 0) || null;
    const rawCompressed = fd.getAll('compressed_photos[]').find((v) => typeof v === 'string' && v.startsWith('data:image/')) || null;

    // Satu entri per tanggal: entri baru menggantikan antrean lama di tanggal yang sama.
    const date = todayInAppTz();
    const existing = (await queueAll()).find((q) => q.date === date);

    await queuePut({
        ...(existing ? { id: existing.id } : {}),
        userId: USER_ID,
        url: form.getAttribute('action') || STORE_URL,
        date,
        activity_description: String(fd.get('activity_description') || ''),
        action_type: String(fd.get('action_type') || 'submit'),
        photo: photo ? new Blob([await photo.arrayBuffer()], { type: photo.type }) : (existing?.photo ?? null),
        photoName: photo ? photo.name : (existing?.photoName ?? null),
        compressedPhoto: rawCompressed || (existing?.compressedPhoto ?? null),
        status: 'pending',
        error: null,
        createdAt: existing?.createdAt ?? Date.now(),
    });
}

// ---------------------------------------------------------------------------
// Sinkronisasi
// ---------------------------------------------------------------------------

let syncing = false;
let lastCsrfRefreshAt = Date.now();

function applyCsrfToken(token) {
    if (!token) return;
    document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', token);
    document.querySelectorAll('input[name="_token"]').forEach((input) => {
        input.value = token;
    });
    lastCsrfRefreshAt = Date.now();
}

async function refreshCsrfToken() {
    if (!navigator.onLine) return csrfToken();
    try {
        const res = await fetch('/csrf-token', {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) return csrfToken();
        const data = await res.json().catch(() => null);
        if (data?.token) {
            applyCsrfToken(data.token);
            return data.token;
        }
    } catch (e) {
        // biarkan token saat ini jika jaringan belum stabil
    }
    return csrfToken();
}

async function postJournal(item, token) {
    const fd = new FormData();
    fd.append('_token', token);
    fd.append('date', item.date);
    fd.append('activity_description', item.activity_description);
    fd.append('action_type', item.action_type);
    if (item.compressedPhoto) {
        fd.append('compressed_photos[]', item.compressedPhoto);
    } else if (item.photo) {
        fd.append('photos[]', item.photo, item.photoName || 'jurnal.jpg');
    }

    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), SYNC_TIMEOUT_MS);
    try {
        return await fetch(item.url || STORE_URL, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token },
            signal: controller.signal,
        });
    } finally {
        clearTimeout(timer);
    }
}

/**
 * @returns {Promise<{sent: number, failed: number, stopped: string|null}>}
 */
async function syncQueue({ includeFailed = false } = {}) {
    const result = { sent: 0, failed: 0, stopped: null };
    if (syncing || !navigator.onLine || !USER_ID) return result;
    syncing = true;
    renderQueueUi();

    try {
        const items = (await queueAll()).filter((q) => q.status === 'pending' || includeFailed);
        let token = csrfToken();

        for (const item of items) {
            let res;
            try {
                res = await postJournal(item, token);
                if (res.status === 419) {
                    token = await refreshCsrfToken();
                    res = await postJournal(item, token);
                }
            } catch (e) {
                result.stopped = 'network';
                break; // sinyal masih lemah — coba lagi nanti
            }

            const isJson = (res.headers.get('Content-Type') || '').includes('application/json');

            if (res.ok && isJson) {
                await queueDelete(item.id);
                safeStorage(() => localStorage.removeItem(draftKey(item.date)));
                result.sent++;
            } else if (res.status === 422 && isJson) {
                const data = await res.json().catch(() => ({}));
                await queuePut({ ...item, status: 'failed', error: data.message || 'Jurnal ditolak server.' });
                result.failed++;
            } else if (res.status === 401 || res.status === 419 || !isJson) {
                // Sesi habis / perlu login ulang / dialihkan (mis. wajib ganti kata sandi).
                result.stopped = 'auth';
                break;
            } else {
                result.stopped = 'server';
                break;
            }
        }
    } finally {
        syncing = false;
        await renderQueueUi();
    }

    if (result.sent > 0) {
        showToast(`${result.sent} jurnal offline berhasil dikirim.`, 'success');
        if (document.getElementById('journal-main-form')) {
            setTimeout(() => window.location.reload(), 1200);
        }
    }
    if (result.failed > 0) {
        showToast(`${result.failed} jurnal ditolak server. Buka menu Jurnal untuk melihat alasannya.`, 'error');
    }
    if (result.stopped === 'auth') {
        showToast('Sesi berakhir. Masuk kembali agar jurnal offline bisa dikirim.', 'error');
    }
    return result;
}

// ---------------------------------------------------------------------------
// UI: banner koneksi, toast, panel antrean
// ---------------------------------------------------------------------------

let bannerDismissed = false;

function ensureBanner() {
    let el = document.getElementById('vt-connection-banner');
    if (!el) {
        el = document.createElement('div');
        el.id = 'vt-connection-banner';
        el.setAttribute('role', 'status');
        el.className = 'hidden';
        const container = document.querySelector('[data-student-shell]') || body;
        container.appendChild(el);
    }
    return el;
}

async function renderQueueUi() {
    const items = await queueAll();
    const pending = items.filter((q) => q.status === 'pending').length;
    const banner = ensureBanner();

    if (bannerDismissed) {
        banner.className = 'hidden';
        banner.innerHTML = '';
        renderQueuePanel(items);
        return;
    }

    const onCamera = Boolean(document.getElementById('shutter-btn') && !document.getElementById('shutter-btn').closest('.hidden'));
    const posClass = onCamera
        ? 'fixed top-4 left-1/2 -translate-x-1/2 z-40'
        : 'fixed bottom-24 left-1/2 -translate-x-1/2 z-40';
    const baseClass = `${posClass} w-[calc(100%-32px)] max-w-[360px] rounded-2xl px-3.5 py-2.5 text-[11.5px] font-bold flex items-center justify-between gap-2.5 shadow-[0_12px_32px_rgba(0,0,0,0.2)] transition-all duration-300 backdrop-blur-md`;

    if (!navigator.onLine) {
        banner.className = `${baseClass} bg-amber-400 text-slate-950 border border-amber-500/40`;
        banner.innerHTML = `<div class="flex items-center gap-2.5 min-w-0 flex-1">
            <span class="w-2.5 h-2.5 rounded-full bg-slate-950 shrink-0"></span>
            <span class="leading-tight flex-1">Offline — jurnal tetap bisa ditulis${pending ? ` · <strong>${pending}</strong> antrean` : ''}. Presensi perlu internet.</span>
        </div>
        <button type="button" data-close-banner aria-label="Tutup notifikasi" class="shrink-0 w-6 h-6 rounded-full bg-slate-950/10 hover:bg-slate-950/20 active:scale-90 flex items-center justify-center transition-colors cursor-pointer">
            <svg class="w-3.5 h-3.5 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>`;
    } else if (pending > 0) {
        banner.className = `${baseClass} bg-[#0B2B25] text-white border border-[#C6F26B]/30`;
        banner.innerHTML = `<div class="flex items-center gap-2.5 min-w-0 flex-1">
            <span class="w-2.5 h-2.5 rounded-full bg-[#C6F26B] shrink-0 ${syncing ? 'animate-ping' : ''}"></span>
            <span class="leading-tight flex-1 text-[#F1F4EC]">${syncing ? 'Mengirim' : 'Menunggu kirim'} ${pending} jurnal offline…</span>
        </div>
        <button type="button" data-close-banner aria-label="Tutup notifikasi" class="shrink-0 w-6 h-6 rounded-full bg-white/10 hover:bg-white/20 active:scale-90 flex items-center justify-center transition-colors cursor-pointer">
            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>`;
    } else {
        banner.className = 'hidden';
        banner.innerHTML = '';
    }

    renderQueuePanel(items);
}

function renderQueuePanel(items) {
    const panel = document.getElementById('offline-journal-queue');
    if (!panel) return;

    if (items.length === 0) {
        panel.classList.add('hidden');
        panel.innerHTML = '';
        return;
    }

    panel.classList.remove('hidden');
    panel.innerHTML = `
        <div class="flex items-center justify-between h-6">
            <h2 class="font-bold text-base text-[#10211D]">Tersimpan di perangkat</h2>
            <button type="button" data-queue-action="sync" class="text-xs font-bold text-[#0B5E4E] cursor-pointer disabled:opacity-50" ${navigator.onLine && !syncing ? '' : 'disabled'}>
                ${syncing ? 'Mengirim…' : 'Kirim sekarang'}
            </button>
        </div>
        <div class="flex flex-col gap-2 mt-2">
            ${items.map((q) => `
                <div class="bg-white rounded-[18px] p-3 border ${q.status === 'failed' ? 'border-rose-200' : 'border-amber-200'} flex flex-col gap-1.5">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold text-[#10211D]">${escapeHtml(formatDate(q.date))}</span>
                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold ${q.status === 'failed' ? 'bg-rose-50 text-rose-700' : 'bg-[#FFF1D6] text-[#7A4B00]'}">
                            ${q.status === 'failed' ? 'Ditolak' : 'Menunggu sinyal'}
                        </span>
                    </div>
                    <p class="text-xs text-[#5A6B65] leading-relaxed line-clamp-2">${escapeHtml(q.activity_description)}</p>
                    ${(q.photo || q.compressedPhoto) ? '<span class="inline-flex items-center gap-1 text-[11px] text-[#5A6B65]"><svg class="w-3 h-3 text-[#5A6B65]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>1 foto terlampir</span>' : ''}
                    ${q.error ? `<p class="text-[11px] font-semibold text-rose-700">${escapeHtml(q.error)}</p>` : ''}
                    <div class="flex gap-2 pt-1">
                        ${q.status === 'failed' ? `<button type="button" data-queue-action="edit" data-id="${q.id}" class="flex-1 min-h-[36px] rounded-xl bg-[#0B2B25] text-white text-[11px] font-bold cursor-pointer">Perbaiki</button>` : ''}
                        <button type="button" data-queue-action="delete" data-id="${q.id}" class="flex-1 min-h-[36px] rounded-xl bg-slate-100 text-[#10211D] text-[11px] font-bold cursor-pointer">Hapus</button>
                    </div>
                </div>`).join('')}
        </div>`;
}

function bindQueuePanelActions() {
    document.addEventListener('click', async (e) => {
        const closeBtn = e.target.closest?.('[data-close-banner]');
        if (closeBtn) {
            bannerDismissed = true;
            const banner = document.getElementById('vt-connection-banner');
            if (banner) {
                banner.className = 'hidden';
                banner.innerHTML = '';
            }
            return;
        }

        const btn = e.target.closest?.('[data-queue-action]');
        if (!btn) return;
        const action = btn.dataset.queueAction;
        const id = Number(btn.dataset.id);

        if (action === 'sync') {
            await syncQueue({ includeFailed: false });
        } else if (action === 'delete') {
            const ok = window.vtDialog
                ? await window.vtDialog({ tone: 'danger', title: 'Hapus jurnal offline?', message: 'Jurnal ini belum terkirim ke server dan akan hilang dari perangkat.', confirmText: 'Ya, hapus', cancelText: 'Batal' })
                : window.confirm('Hapus jurnal offline ini?');
            if (ok) {
                await queueDelete(id);
                await renderQueueUi();
            }
        } else if (action === 'edit') {
            const item = (await queueAll()).find((q) => q.id === id);
            const textarea = document.getElementById('kegiatan-input');
            if (item && textarea) {
                textarea.value = item.activity_description;
                textarea.dispatchEvent(new Event('input'));
                textarea.scrollIntoView({ behavior: 'smooth', block: 'center' });
                textarea.focus();
                await queueDelete(id);
                await renderQueueUi();
            }
        }
    });
}

function showToast(message, tone = 'success') {
    const el = document.createElement('div');
    el.setAttribute('role', 'status');
    el.className = `fixed left-1/2 -translate-x-1/2 bottom-28 z-[70] max-w-[360px] w-[calc(100%-32px)] px-4 py-3 rounded-2xl text-xs font-bold shadow-xl transition-opacity duration-300 ${tone === 'success' ? 'bg-[#0B2B25] text-white' : 'bg-rose-600 text-white'}`;
    el.textContent = message;
    body.appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; }, 3500);
    setTimeout(() => el.remove(), 3900);
}

// ---------------------------------------------------------------------------
// Form jurnal: kirim via fetch, simpan ke antrean bila tidak ada sinyal
// ---------------------------------------------------------------------------

function draftKey(date) {
    return `vt-journal-draft:${USER_ID}:${date}`;
}

function bindJournalForm() {
    const form = document.getElementById('journal-main-form');
    const textarea = document.getElementById('kegiatan-input');
    if (!form || !textarea || !USER_ID) return;

    // Simpan draft otomatis; pulihkan bila server belum punya isi untuk hari ini.
    const key = draftKey(todayInAppTz());
    const saved = safeStorage(() => localStorage.getItem(key));
    if (saved && textarea.value.trim() === '') {
        textarea.value = saved;
        textarea.dispatchEvent(new Event('input'));
    }
    let timer;
    textarea.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => safeStorage(() => localStorage.setItem(key, textarea.value)), 400);
    });

    const flash = safeStorage(() => sessionStorage.getItem('vt-journal-flash'));
    if (flash) {
        safeStorage(() => sessionStorage.removeItem('vt-journal-flash'));
        showToast(flash, 'success');
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;

        const saveOffline = async () => {
            await enqueueJournal(form);
            safeStorage(() => localStorage.removeItem(key));
            form.reset();
            textarea.value = '';
            textarea.dispatchEvent(new Event('input'));
            await renderQueueUi();
            alertDialog({
                tone: 'warning',
                title: 'Jurnal disimpan di perangkat',
                message: 'Tidak ada koneksi internet. Jurnal akan dikirim otomatis ke pembimbing begitu sinyal kembali — jangan hapus data aplikasi/browser.',
                confirmText: 'Mengerti',
            });
        };

        try {
            if (!navigator.onLine) {
                await saveOffline();
                return;
            }

            let res;
            try {
                const controller = new AbortController();
                const t = setTimeout(() => controller.abort(), SYNC_TIMEOUT_MS);
                res = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: controller.signal,
                });
                clearTimeout(t);
            } catch (networkError) {
                await saveOffline();
                return;
            }

            const isJson = (res.headers.get('Content-Type') || '').includes('application/json');
            if (res.ok && isJson) {
                const data = await res.json();
                safeStorage(() => localStorage.removeItem(key));
                safeStorage(() => sessionStorage.setItem('vt-journal-flash', data.message || 'Jurnal tersimpan.'));
                window.location.reload();
                return;
            }
            if (res.status === 422 && isJson) {
                const data = await res.json();
                alertDialog({ tone: 'warning', title: 'Jurnal belum bisa disimpan', message: data.message || 'Periksa kembali isian jurnal.' });
                return;
            }
            // Sesi habis atau respons tak terduga: serahkan ke submit form biasa agar server menanganinya.
            HTMLFormElement.prototype.submit.call(form);
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    });
}

// ---------------------------------------------------------------------------
// Blokir form lain saat offline (presensi, izin, SOS, dll.)
// ---------------------------------------------------------------------------

function bindOfflineFormGuard() {
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (navigator.onLine || !(form instanceof HTMLFormElement)) return;
        if ((form.method || 'get').toLowerCase() !== 'post' || form.id === 'journal-main-form') return;

        e.preventDefault();
        e.stopImmediatePropagation();

        const action = form.getAttribute('action') || '';
        const isSos = /lapor-kendala|kendala|incident/.test(action);
        alertDialog({
            tone: isSos ? 'danger' : 'warning',
            title: 'Tidak ada koneksi internet',
            message: isSos
                ? 'Laporan tidak bisa dikirim tanpa sinyal. Jika darurat, segera telepon 112 atau hubungi guru pembimbing lewat panggilan biasa.'
                : 'Aksi ini memerlukan internet. Cari lokasi bersinyal lalu coba lagi.',
        });
    }, true);
}

// ---------------------------------------------------------------------------
// Tombol pasang aplikasi (Android/Chrome)
// ---------------------------------------------------------------------------
// Instalasi PWA & Banner Pintasan
// ---------------------------------------------------------------------------

let deferredInstallPrompt = null;

function syncPwaCardState() {
    const card = document.getElementById('student-pwa-card');
    if (!card) return;

    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    if (isStandalone) {
        const badge = card.querySelector('#pwa-card-badge');
        if (badge) {
            badge.className = 'bg-[#DDF3EC] text-[#0F7A66] text-[10px] font-extrabold px-2 py-0.5 rounded-full shrink-0';
            badge.textContent = 'Terpasang (PWA)';
        }
        const actionWrap = card.querySelector('#pwa-card-action');
        if (actionWrap) {
            actionWrap.innerHTML = `
                <div class="w-full h-11 rounded-2xl bg-[#F4FAF6] border border-[#D5EADB] text-[#0F7A66] font-bold text-xs flex items-center justify-center gap-1.5 shadow-2xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <span>Mode PWA Aktif · Siap Digunakan Offline</span>
                </div>
            `;
        }
    }
}

async function promptInstall() {
    if (deferredInstallPrompt) {
        deferredInstallPrompt.prompt();
        const choice = await deferredInstallPrompt.userChoice.catch(() => {});
        deferredInstallPrompt = null;
        document.getElementById('vt-install-chip')?.remove();
        syncPwaCardState();
        return choice;
    }

    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    if (isStandalone) {
        if (window.vtDialog) {
            window.vtDialog({
                tone: 'info',
                eyebrow: 'Aplikasi Terpasang',
                title: 'Aplikasi Sudah Aktif di HP',
                message: 'VokaTrack sudah terpasang dan berjalan dalam mode PWA standalone di perangkat Anda. Anda dapat mengisi jurnal kapan saja bahkan saat offline.',
                confirmText: 'Tutup'
            });
        }
        return;
    }

    const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    if (isIos) {
        if (window.vtDialog) {
            window.vtDialog({
                tone: 'info',
                eyebrow: 'Panduan iOS (iPhone / iPad)',
                title: 'Pasang VokaTrack di Layar Utama',
                message: '1. Ketuk tombol Bagikan (ikon Share ⎋) di bagian bawah Safari.\n2. Gulir menu dan pilih "Tambah ke Layar Utama" (Add to Home Screen).\n3. Ketuk "Tambah" di sudut kanan atas.',
                confirmText: 'Saya Mengerti'
            });
        } else {
            alert('Ketuk tombol Share di Safari, lalu pilih "Tambah ke Layar Utama" (Add to Home Screen).');
        }
        return;
    }

    // Android / Desktop Chrome jika prompt belum siap atau otomatis dicegah
    if (window.vtDialog) {
        window.vtDialog({
            tone: 'info',
            eyebrow: 'Panduan Pasang PWA',
            title: 'Pasang di Layar Utama HP',
            message: 'Buka menu browser Anda (ikon titik tiga ⋮ di sudut kanan atas), lalu pilih "Pasang aplikasi" (Install app) atau "Tambahkan ke Layar Utama".',
            confirmText: 'Saya Mengerti'
        });
    } else {
        alert('Buka menu browser Anda (titik tiga ⋮), lalu pilih "Pasang aplikasi" atau "Tambahkan ke Layar Utama".');
    }
}

function bindInstallPrompt() {
    const dismissed = () => safeStorage(() => localStorage.getItem('vt-install-dismissed') === '1', false);

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredInstallPrompt = e;
        if (dismissed() || document.getElementById('vt-install-chip')) return;

        const appLogo = document.body?.dataset?.appLogo || '/icons/icon-192.png';
        const appName = document.body?.dataset?.appName || 'VokaTrack';

        const chip = document.createElement('div');
        chip.id = 'vt-install-chip';
        chip.className = 'fixed top-4 left-1/2 -translate-x-1/2 z-35 w-[calc(100%-32px)] max-w-[380px] bg-white text-[#10211D] border border-slate-200/90 rounded-2xl shadow-[0_12px_32px_rgba(0,0,0,0.18)] p-3 flex items-center gap-3 backdrop-blur-md transition-all duration-300 animate-in fade-in slide-in-from-top-2';
        chip.innerHTML = `
            <div class="w-10 h-10 rounded-xl bg-[#0B2B25]/5 p-1 flex items-center justify-center shrink-0 border border-slate-200/60 overflow-hidden">
                <img src="${appLogo}" alt="${appName}" class="w-full h-full object-contain">
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-[#10211D]">Pasang ${appName} di HP</p>
                <p class="text-[11px] text-[#5A6B65]">Buka lebih cepat & tetap bisa isi jurnal tanpa sinyal.</p>
            </div>
            <button type="button" data-install="yes" class="px-3 h-9 rounded-xl bg-[#0B2B25] hover:bg-[#12382F] active:scale-95 text-[#C6F26B] text-[11px] font-bold transition-all cursor-pointer">Pasang</button>
            <button type="button" data-install="no" aria-label="Tutup" class="w-7 h-7 rounded-full text-slate-400 hover:text-slate-700 hover:bg-slate-100 active:scale-90 flex items-center justify-center transition-all cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>`;
        body.appendChild(chip);

        const syncModalVisibility = () => {
            const hasOpenModal = document.querySelector(
                '#mobile-notification-drawer:not(.hidden), #vt-dialog:not(.hidden), #sos-modal:not(.hidden), #fake-gps-alert-modal:not(.hidden), [id^="modal-"]:not(.hidden)'
            );
            chip.style.display = hasOpenModal ? 'none' : '';
        };
        const modalObs = new MutationObserver(syncModalVisibility);
        modalObs.observe(document.body, { attributes: true, subtree: true, attributeFilter: ['class', 'style'] });

        chip.addEventListener('click', async (ev) => {
            const choice = ev.target.closest('[data-install]')?.dataset.install;
            if (!choice) return;
            if (choice === 'yes') {
                await promptInstall();
            } else {
                safeStorage(() => localStorage.setItem('vt-install-dismissed', '1'));
            }
            modalObs.disconnect();
            chip.remove();
        });
    });

    window.addEventListener('appinstalled', () => {
        document.getElementById('vt-install-chip')?.remove();
        syncPwaCardState();
        syncPushUiState();
    });
}

// ---------------------------------------------------------------------------
// Web Push Notification (Android Chrome/Edge/Samsung Internet & iOS 16.4+ PWA)
// ---------------------------------------------------------------------------

function isIosDevice() {
    return (/iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
}

function isStandaloneMode() {
    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
}

function supportsWebPush() {
    return window.isSecureContext
        && 'serviceWorker' in navigator
        && 'PushManager' in window
        && 'Notification' in window;
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = window.atob(base64);
    const output = new Uint8Array(raw.length);
    for (let i = 0; i < raw.length; i += 1) {
        output[i] = raw.charCodeAt(i);
    }
    return output;
}

function detectPushEncoding() {
    const supported = window.PushManager?.supportedContentEncodings;
    if (Array.isArray(supported) && supported.length > 0) {
        return supported.includes('aes128gcm') ? 'aes128gcm' : supported[0];
    }
    return 'aes128gcm';
}

async function getActiveRegistration() {
    if (!('serviceWorker' in navigator) || !window.isSecureContext) return null;
    const existing = await navigator.serviceWorker.getRegistration('/');
    if (existing) return existing;
    return navigator.serviceWorker.register('/sw.js', { scope: '/', updateViaCache: 'none' });
}

async function savePushSubscriptionToServer(subscription) {
    const json = subscription.toJSON ? subscription.toJSON() : {};
    const keys = json.keys || {};
    if (!subscription.endpoint || !keys.p256dh || !keys.auth) {
        throw new Error('Data langganan push tidak lengkap.');
    }

    const res = await fetch(PUSH_SUBSCRIBE_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            endpoint: subscription.endpoint,
            keys: {
                p256dh: keys.p256dh,
                auth: keys.auth,
            },
            content_encoding: detectPushEncoding(),
        }),
    });

    if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        throw new Error(data.message || 'Gagal menyimpan langganan notifikasi.');
    }

    if (USER_ID) {
        safeStorage(() => localStorage.setItem('vt-push-synced-user', USER_ID));
    }
}

async function getPushState() {
    const isIos = isIosDevice();
    const isStandalone = isStandaloneMode();

    if (isIos && !isStandalone) {
        return { state: 'ios-install-required', subscribed: false, isIos, isStandalone };
    }

    if (!supportsWebPush() || !VAPID_PUBLIC_KEY) {
        return { state: 'unsupported', subscribed: false, isIos, isStandalone };
    }

    const permission = Notification.permission;
    if (permission === 'denied') {
        return { state: 'denied', subscribed: false, isIos, isStandalone };
    }

    let subscribed = false;
    try {
        const reg = await navigator.serviceWorker.getRegistration('/');
        const sub = await reg?.pushManager?.getSubscription();
        subscribed = Boolean(sub);
    } catch (e) {
        subscribed = false;
    }

    if (permission === 'granted' && subscribed) {
        return { state: 'active', subscribed: true, isIos, isStandalone };
    }

    return { state: 'ready', subscribed: false, isIos, isStandalone };
}

async function syncPushUiState() {
    const section = document.getElementById('pwa-push-section');
    if (!section) return;

    const badge = document.getElementById('pwa-push-badge');
    const desc = document.getElementById('pwa-push-desc');
    const actions = document.getElementById('pwa-push-actions');
    if (!badge || !desc || !actions) return;

    const info = await getPushState();

    if (info.state === 'active') {
        badge.className = 'bg-[#DDF3EC] text-[#0F7A66] text-[10px] font-extrabold px-2 py-0.5 rounded-full shrink-0';
        badge.textContent = 'Aktif';
        desc.textContent = 'Notifikasi akan muncul di bar notifikasi & lock screen walau aplikasi ditutup.';
        actions.innerHTML = `
            <button type="button" onclick="window.VokaTrackPwa?.sendTestNotification()"
                    class="flex-1 h-10 rounded-xl bg-[#0B2B25] hover:bg-[#12382F] active:scale-[0.98] text-[#C6F26B] font-bold text-xs flex items-center justify-center gap-1.5 transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span>Kirim Tes Notifikasi</span>
            </button>
            <button type="button" onclick="window.VokaTrackPwa?.disableNotifications()"
                    class="h-10 px-3.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs transition-all cursor-pointer">
                Matikan
            </button>`;
        return;
    }

    if (info.state === 'ios-install-required') {
        badge.className = 'bg-amber-100 text-amber-800 text-[10px] font-extrabold px-2 py-0.5 rounded-full shrink-0';
        badge.textContent = 'Pasang PWA Dulu';
        desc.textContent = 'Di iPhone/iPad (iOS 16.4+), pasang ke Layar Utama dulu (Share ⎋ → Tambah ke Layar Utama), lalu buka dari ikon aplikasi untuk mengaktifkan notifikasi.';
        actions.innerHTML = `
            <button type="button" onclick="window.VokaTrackPwa?.promptInstall()"
                    class="flex-1 h-10 rounded-xl bg-amber-500 hover:bg-amber-600 active:scale-[0.98] text-white font-bold text-xs flex items-center justify-center gap-1.5 transition-all cursor-pointer">
                <span>Panduan Pasang di iPhone/iPad</span>
            </button>`;
        return;
    }

    if (info.state === 'denied') {
        badge.className = 'bg-rose-100 text-rose-700 text-[10px] font-extrabold px-2 py-0.5 rounded-full shrink-0';
        badge.textContent = 'Diblokir';
        desc.textContent = 'Izin notifikasi diblokir di browser. Buka Setelan Situs browser → Notifikasi → Izinkan.';
        actions.innerHTML = `
            <button type="button" onclick="window.VokaTrackPwa?.showPermissionHelp()"
                    class="flex-1 h-10 rounded-xl bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 font-bold text-xs flex items-center justify-center gap-1.5 transition-all cursor-pointer">
                <span>Cara Mengizinkan Notifikasi</span>
            </button>`;
        return;
    }

    if (info.state === 'unsupported') {
        badge.className = 'bg-slate-100 text-slate-600 text-[10px] font-extrabold px-2 py-0.5 rounded-full shrink-0';
        badge.textContent = !VAPID_PUBLIC_KEY ? 'Belum Aktif' : 'Tidak Didukung';
        desc.textContent = !VAPID_PUBLIC_KEY
            ? 'Kunci notifikasi push server belum dikonfigurasi oleh admin.'
            : 'Perangkat/browser ini belum mendukung Web Push. Gunakan Android (Chrome/Edge/Samsung Internet) atau iOS 16.4+ mode PWA lewat HTTPS.';
        actions.innerHTML = '';
        return;
    }

    // state === 'ready'
    badge.className = 'bg-[#C6F26B]/50 text-[#0B2B25] text-[10px] font-extrabold px-2 py-0.5 rounded-full shrink-0';
    badge.textContent = 'Siap Diaktifkan';
    desc.textContent = info.isIos
        ? 'Aplikasi PWA sudah terpasang. Ketuk tombol di bawah untuk mengizinkan notifikasi iOS.'
        : 'Aktifkan agar status jurnal, izin, & pengumuman muncul di lock screen walau aplikasi ditutup.';
    actions.innerHTML = `
        <button type="button" onclick="window.VokaTrackPwa?.enableNotifications()"
                class="flex-1 h-10 rounded-xl bg-[#0F7A66] hover:bg-[#0B5E4E] active:scale-[0.98] text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-xs transition-all cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            <span>Aktifkan Notifikasi</span>
        </button>`;
}

function showPermissionHelp() {
    alertDialog({
        tone: 'warning',
        eyebrow: 'Izin Notifikasi Diblokir',
        title: 'Cara Mengaktifkan Notifikasi',
        message: isIosDevice()
            ? 'Buka Pengaturan iPhone/iPad → cari aplikasi VokaTrack → Notifikasi → aktifkan Izinkan Notifikasi.'
            : 'Di Chrome/Edge/Samsung Internet: ketuk ikon gembok/setelan di sebelah URL → Izin / Notifikasi → pilih Izinkan, lalu muat ulang halaman.',
        confirmText: 'Mengerti',
    });
}

async function enableNotifications() {
    if (isIosDevice() && !isStandaloneMode()) {
        await promptInstall();
        return false;
    }

    if (!supportsWebPush()) {
        await alertDialog({
            tone: 'warning',
            eyebrow: 'Browser Tidak Mendukung',
            title: 'Web Push Belum Tersedia',
            message: 'Pastikan Anda mengakses melalui HTTPS menggunakan Android (Chrome, Edge, Samsung Internet) atau iPhone/iPad (iOS 16.4+ yang sudah dipasang ke Layar Utama).',
            confirmText: 'Tutup',
        });
        return false;
    }

    if (!VAPID_PUBLIC_KEY) {
        showToast('Konfigurasi VAPID server belum tersedia.', 'error');
        return false;
    }

    try {
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            await syncPushUiState();
            if (permission === 'denied') {
                showPermissionHelp();
            } else {
                showToast('Izin notifikasi belum disetujui.', 'info');
            }
            return false;
        }

        const reg = await getActiveRegistration();
        await navigator.serviceWorker.ready;
        const activeReg = await navigator.serviceWorker.ready || reg;

        let sub = await activeReg.pushManager.getSubscription();
        if (!sub) {
            sub = await activeReg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
            });
        }

        await savePushSubscriptionToServer(sub);
        await syncPushUiState();
        showToast('Notifikasi aktif! Pemberitahuan akan muncul walau aplikasi ditutup.', 'success');
        return true;
    } catch (err) {
        showToast(err?.message || 'Gagal mengaktifkan notifikasi push.', 'error');
        await syncPushUiState();
        return false;
    }
}

async function disableNotifications() {
    try {
        const reg = await navigator.serviceWorker?.getRegistration('/');
        const sub = await reg?.pushManager?.getSubscription();
        if (sub) {
            await fetch(PUSH_UNSUBSCRIBE_URL, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ endpoint: sub.endpoint }),
            }).catch(() => {});
            await sub.unsubscribe();
        }
        safeStorage(() => localStorage.removeItem('vt-push-synced-user'));
        await syncPushUiState();
        showToast('Notifikasi background dimatikan untuk perangkat ini.', 'info');
    } catch (e) {
        showToast('Gagal mematikan notifikasi.', 'error');
    }
}

async function sendTestNotification() {
    try {
        const res = await fetch(PUSH_TEST_URL, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            showToast(data.message || 'Gagal mengirim tes notifikasi.', 'error');
            return false;
        }
        showToast(data.message || 'Tes notifikasi dikirim!', 'success');
        return true;
    } catch (e) {
        showToast('Tidak dapat menghubungi server untuk tes notifikasi.', 'error');
        return false;
    }
}

async function syncExistingPushSubscription() {
    if (!USER_ID || !VAPID_PUBLIC_KEY || !supportsWebPush()) return;
    if (Notification.permission !== 'granted') return;

    try {
        const reg = await navigator.serviceWorker.ready;
        const sub = await reg?.pushManager?.getSubscription();
        if (!sub) return;

        const lastSyncedUser = safeStorage(() => localStorage.getItem('vt-push-synced-user'));
        if (lastSyncedUser !== USER_ID) {
            await savePushSubscriptionToServer(sub);
        }
    } catch (e) {
        // abaikan sinkronisasi latar yang gagal sementara
    }
}

// ---------------------------------------------------------------------------
// Inisialisasi
// ---------------------------------------------------------------------------

registerServiceWorker();
bindCacheToUser();
bindOfflineFormGuard();
bindJournalForm();
bindQueuePanelActions();
bindInstallPrompt();

renderQueueUi().then(() => syncQueue());
syncPwaCardState();
syncPushUiState();
syncExistingPushSubscription();

window.addEventListener('online', () => { bannerDismissed = false; renderQueueUi(); syncQueue(); });
window.addEventListener('offline', () => { bannerDismissed = false; renderQueueUi(); });
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        if (navigator.onLine && Date.now() - lastCsrfRefreshAt > 5 * 60 * 1000) {
            refreshCsrfToken();
        }
        syncQueue();
        syncPushUiState();
    }
});
setInterval(() => syncQueue(), 60000);

window.VokaTrackPwa = {
    syncQueue,
    queueAll,
    promptInstall,
    enableNotifications,
    disableNotifications,
    sendTestNotification,
    showPermissionHelp,
    getPushState,
    isOnline: () => navigator.onLine,
    isStandalone: isStandaloneMode,
};
