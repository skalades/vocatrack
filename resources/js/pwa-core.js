/**
 * VokaTrack Shared PWA & Web Push Core
 *
 * Modul bersama untuk Siswa dan Guru Pembimbing:
 * - Registrasi service worker (/sw.js) & isolasi cache per user saat ganti akun/logout.
 * - Auto-refresh CSRF token (/csrf-token) saat aplikasi dibuka kembali dari background.
 * - Prompt instalasi PWA (Android/Desktop Chrome & panduan iOS Add to Home Screen).
 * - Manajemen Web Push Notification (Android lock screen & iOS 16.4+ PWA standalone).
 */

export function safeStorage(fn, fallback = null) {
    try { return fn(); } catch (e) { return fallback; }
}

export function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

export function alertDialog(opts) {
    if (window.vtDialog) return window.vtDialog(opts);
    window.alert(`${opts.title}\n\n${opts.message || ''}`);
    return Promise.resolve(true);
}

export function showToast(message, tone = 'success') {
    const body = document.body;
    const el = document.createElement('div');
    el.setAttribute('role', 'status');
    el.className = `fixed left-1/2 -translate-x-1/2 bottom-28 z-[70] max-w-[360px] w-[calc(100%-32px)] px-4 py-3 rounded-2xl text-xs font-bold shadow-xl transition-opacity duration-300 ${tone === 'success' ? 'bg-[#0B2B25] text-white' : tone === 'info' ? 'bg-slate-800 text-white' : 'bg-rose-600 text-white'}`;
    el.textContent = message;
    body.appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; }, 3500);
    setTimeout(() => el.remove(), 3900);
}

export function isIosDevice() {
    return (/iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
}

export function isStandaloneMode() {
    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
}

export function supportsWebPush() {
    return window.isSecureContext
        && 'serviceWorker' in navigator
        && 'PushManager' in window
        && 'Notification' in window;
}

export function initPwaCore(options = {}) {
    const body = document.body;
    const USER_ID = body.dataset.userId || '';
    const VAPID_PUBLIC_KEY = body.dataset.vapidPublicKey || '';
    const PUSH_SUBSCRIBE_URL = body.dataset.pushSubscribeUrl || '/push-subscriptions';
    const PUSH_UNSUBSCRIBE_URL = body.dataset.pushUnsubscribeUrl || '/push-subscriptions';
    const PUSH_TEST_URL = body.dataset.pushTestUrl || '/push-subscriptions/test';

    const cardId = options.cardId || 'student-pwa-card';
    const installPromptSubtitle = options.installPromptSubtitle || 'Buka lebih cepat & terima notifikasi meski aplikasi ditutup.';
    const installedCardText = options.installedCardText || 'Mode PWA Aktif · Siap Digunakan';
    const installedDialogMessage = options.installedDialogMessage || 'Aplikasi sudah terpasang dan berjalan dalam mode PWA standalone di perangkat Anda.';
    const readyPushDesc = options.readyPushDesc || 'Aktifkan agar pembaruan penting muncul di lock screen walau aplikasi ditutup.';
    const iosReadyPushDesc = options.iosReadyPushDesc || 'Aplikasi PWA sudah terpasang. Ketuk tombol di bawah untuk mengizinkan notifikasi iOS.';

    // -----------------------------------------------------------------------
    // Service Worker & Cache Isolation
    // -----------------------------------------------------------------------

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
        const doRegister = () => {
            navigator.serviceWorker.register('/sw.js', { scope: '/', updateViaCache: 'none' }).catch(() => {});
        };
        if (document.readyState === 'complete') {
            doRegister();
        } else {
            window.addEventListener('load', doRegister);
        }
    }

    function bindCacheToUser() {
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

    // -----------------------------------------------------------------------
    // CSRF Auto-Refresh (mencegah error 419 saat PWA lama di background)
    // -----------------------------------------------------------------------

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
            // abaikan kegagalan jaringan sementara
        }
        return csrfToken();
    }

    // -----------------------------------------------------------------------
    // Instalasi PWA & Kartu Status
    // -----------------------------------------------------------------------

    let deferredInstallPrompt = null;

    function syncPwaCardState() {
        const card = document.getElementById(cardId);
        if (!card) return;

        if (isStandaloneMode()) {
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
                        <span>${installedCardText}</span>
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

        if (isStandaloneMode()) {
            await alertDialog({
                tone: 'info',
                eyebrow: 'Aplikasi Terpasang',
                title: 'Aplikasi Sudah Aktif di HP',
                message: installedDialogMessage,
                confirmText: 'Tutup',
            });
            return;
        }

        if (isIosDevice()) {
            await alertDialog({
                tone: 'info',
                eyebrow: 'Panduan iOS (iPhone / iPad)',
                title: 'Pasang di Layar Utama',
                message: '1. Ketuk tombol Bagikan (ikon Share ⎋) di bagian bawah Safari.\n2. Gulir menu dan pilih "Tambah ke Layar Utama" (Add to Home Screen).\n3. Ketuk "Tambah" di sudut kanan atas.',
                confirmText: 'Saya Mengerti',
            });
            return;
        }

        await alertDialog({
            tone: 'info',
            eyebrow: 'Panduan Pasang PWA',
            title: 'Pasang di Layar Utama HP',
            message: 'Buka menu browser Anda (ikon titik tiga ⋮ di sudut kanan atas), lalu pilih "Pasang aplikasi" (Install app) atau "Tambahkan ke Layar Utama".',
            confirmText: 'Saya Mengerti',
        });
    }

    function bindInstallPrompt() {
        const dismissed = () => safeStorage(() => localStorage.getItem('vt-install-dismissed') === '1', false);

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredInstallPrompt = e;
            if (dismissed() || document.getElementById('vt-install-chip')) return;

            const appLogo = body.dataset.appLogo || '/icons/icon-192.png';
            const appName = body.dataset.appName || 'VokaTrack';
            const chipTitle = options.installPromptTitle || `Pasang ${appName} di HP`;

            const chip = document.createElement('div');
            chip.id = 'vt-install-chip';
            chip.className = 'fixed top-4 left-1/2 -translate-x-1/2 z-35 w-[calc(100%-32px)] max-w-[380px] bg-white text-[#10211D] border border-slate-200/90 rounded-2xl shadow-[0_12px_32px_rgba(0,0,0,0.18)] p-3 flex items-center gap-3 backdrop-blur-md transition-all duration-300 animate-in fade-in slide-in-from-top-2';
            chip.innerHTML = `
                <div class="w-10 h-10 rounded-xl bg-[#0B2B25]/5 p-1 flex items-center justify-center shrink-0 border border-slate-200/60 overflow-hidden">
                    <img src="${appLogo}" alt="${appName}" class="w-full h-full object-contain">
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-[#10211D]">${chipTitle}</p>
                    <p class="text-[11px] text-[#5A6B65]">${installPromptSubtitle}</p>
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

    // -----------------------------------------------------------------------
    // Web Push Notification (Android & iOS 16.4+ PWA)
    // -----------------------------------------------------------------------

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

        badge.className = 'bg-[#C6F26B]/50 text-[#0B2B25] text-[10px] font-extrabold px-2 py-0.5 rounded-full shrink-0';
        badge.textContent = 'Siap Diaktifkan';
        desc.textContent = info.isIos ? iosReadyPushDesc : readyPushDesc;
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

            await getActiveRegistration();
            const activeReg = await navigator.serviceWorker.ready;

            let sub = await activeReg.pushManager.getSubscription();
            if (!sub) {
                sub = await activeReg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
                });
            }

            await savePushSubscriptionToServer(sub);
            await syncPushUiState();
            showToast('Notifikasi background aktif! Pemberitahuan akan muncul walau aplikasi ditutup.', 'success');
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

    registerServiceWorker();
    bindCacheToUser();
    bindInstallPrompt();
    syncPwaCardState();
    syncPushUiState();
    syncExistingPushSubscription();

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            if (navigator.onLine && Date.now() - lastCsrfRefreshAt > 5 * 60 * 1000) {
                refreshCsrfToken();
            }
            syncPushUiState();
            options.onVisible?.();
        }
    });

    return {
        promptInstall,
        enableNotifications,
        disableNotifications,
        sendTestNotification,
        showPermissionHelp,
        getPushState,
        refreshCsrfToken,
        isOnline: () => navigator.onLine,
        isStandalone: isStandaloneMode,
    };
}
