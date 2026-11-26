/**
 * VokaTrack PWA Guru Pembimbing
 *
 * Menggunakan engine bersama pwa-core.js untuk:
 * - Registrasi service worker (/sw.js) & instalasi aplikasi PWA Guru.
 * - Aktivasi Web Push Notification (SOS darurat, tindak lanjut kendala, reset sandi siswa).
 * - Auto-refresh CSRF token agar halaman yang lama terbuka tidak terkena error 419.
 */

import { initPwaCore } from './pwa-core';

const appName = document.body.dataset.appName || 'VokaTrack';

const core = initPwaCore({
    cardId: 'teacher-pwa-card',
    installPromptTitle: `Pasang ${appName} Guru di HP`,
    installPromptSubtitle: 'Pantau siswa lebih cepat & terima notifikasi SOS real-time.',
    installedCardText: 'Mode PWA Guru Aktif · Notifikasi Siap',
    installedDialogMessage: `${appName} Guru sudah terpasang dan berjalan dalam mode PWA standalone di perangkat Anda.`,
    readyPushDesc: 'Aktifkan agar laporan SOS siswa, tindak lanjut kendala, & permintaan reset sandi muncul di lock screen walau aplikasi ditutup.',
});

window.VokaTrackPwa = core;
