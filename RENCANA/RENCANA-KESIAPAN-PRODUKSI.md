# Rencana Kesiapan Produksi Massal VokaTrack

| Parameter | Keterangan |
|---|---|
| **Dokumen** | Rencana penutupan celah sebelum rilis massal (multi-sekolah) |
| **Tanggal** | 16 November 2026 |
| **Status Saat Ini** | Fitur lengkap untuk pilot (1–5 sekolah); 176 test lolos (1015 assertions) |
| **Target** | Siap onboarding massal (puluhan–ratusan sekolah) |
| **Estimasi Total** | ±4 sprint (P0: 2 sprint, P1: 1–2 sprint, P2: berjalan paralel / setelah rilis) |
| **Dokumen Acuan** | [PRD-sistem-pkl-smk.md](../PRD-sistem-pkl-smk.md), [DOKUMENTASI-FITUR.md](../DOKUMENTASI-FITUR.md), [PLAN-INTEGRASI-LANJUTAN.md](../PLAN-INTEGRASI-LANJUTAN.md) |

---

## 1. Ringkasan Penilaian

**Yang sudah kuat**
- Fitur per level (Super Admin, Admin Sekolah, Kepsek, Guru, DU/DI, Siswa, Orang Tua, Publik) sesuai PRD.
- Isolasi tenant (`school_id` global scope), uji IDOR, race condition, dan alur approval sudah tercakup test.
- Login di-throttle, berkas pribadi siswa di disk privat, session terenkripsi, cron auto-alfa & evaluasi langganan terjadwal.

**Celah utama** — bukan pada kelengkapan fitur, melainkan pada **operasional, ketahanan, dan skala**:

| # | Celah | Prioritas | Dampak Jika Diabaikan |
|---|---|:---:|---|
| 1 | Tidak ada "Lupa Password" mandiri | P0 | Admin sekolah kebanjiran permintaan reset dari ribuan siswa |
| 2 | Notifikasi SOS hanya channel `database` | P0 | Laporan darurat siswa tidak terbaca bila guru tidak membuka aplikasi |
| 3 | Backup tidak terjadwal & tidak off-site | P0 | Kehilangan data seluruh sekolah bila server rusak |
| 4 | Tidak ada error monitoring | P0 | Error produksi tidak diketahui sampai pengguna mengeluh |
| 5 | Deploy tanpa CI & rollback | P0 | Satu deploy gagal menjatuhkan semua sekolah saat jam check-in |
| 6 | Notifikasi & batch proses sinkron | P1 | Timeout saat batch sertifikat / broadcast besar |
| 7 | Session/cache/queue di MySQL | P1 | Bottleneck database saat check-in serentak 06:00–08:00 |
| 8 | Pembayaran invoice manual | P1 | Beban verifikasi pembayaran manual oleh tim platform |
| 9 | Tidak ada PWA / mode offline siswa | P1 | Siswa di lokasi minim sinyal (mis. tambang) gagal presensi/jurnal |
| 10 | Dashboard Kepsek tipis, DU/DI tanpa profil, tanpa 2FA | P2 | Nilai jual & keamanan akun privileged kurang |

---

## Status Sprint 1 (P0-3, P0-4, P0-5) — kode selesai, menunggu setup server

| Item | Di kode (selesai) | Tindakan di server (belum) |
|---|---|---|
| **P0-3 Backup** | `vokatrack:backup` terjadwal 02:00; unggah ke disk `backup_s3` + verifikasi ukuran; retensi lokal 7 hari & GFS off-site 7/4/3; notifikasi ke super admin saat gagal; backup pra-deploy | Buat bucket R2/B2 + isi `BACKUP_S3_*`; simpan `APP_KEY` di password manager; rclone untuk berkas siswa; uji restore pertama |
| **P0-4 Monitoring** | Sentry SDK + tag `school_id`/role/user ID (tanpa PII); `LOG_STACK=daily`; heartbeat ping untuk 3 cron; `vokatrack:check-failed-jobs` tiap jam | Isi `SENTRY_LARAVEL_DSN`, `HEARTBEAT_*_URL`; pasang uptime monitor `/up` |
| **P0-5 CI/CD** | `.github/workflows/ci.yml` (test + build); `scripts/deploy-release.sh` (rilis atomik, health check, rollback otomatis/manual); `deploy.sh` lama diberi backup pra-migrasi & auto-`up` saat gagal | Aktifkan branch protection `main` (CI wajib hijau); migrasi struktur server ke `releases/` + `current`; siapkan staging |

Langkah-langkah server ada di [RUNBOOK-OPERASIONAL.md](RUNBOOK-OPERASIONAL.md). Test: 190 lolos (14 baru).

## Status P1-4 PWA Siswa — selesai (dikerjakan lebih awal)

| Bagian | Implementasi |
|---|---|
| Installable | `public/manifest.webmanifest`, ikon 192/512/maskable/apple, tombol "Pasang VokaTrack di HP" (Android/Chrome) |
| Service worker | `public/sw.js` — halaman siswa network-first (timeout 6 detik) dengan cadangan cache, aset `/build` cache-first, `/offline.html` untuk halaman yang belum tersimpan. POST, berkas privat, dan PDF tidak pernah di-cache |
| Jurnal offline | `resources/js/student-pwa.js` — jurnal (teks + 1 foto) disimpan di IndexedDB saat tanpa sinyal atau koneksi putus, dikirim otomatis saat online/halaman dibuka/tiap 60 detik; tanggal asli dipertahankan; jurnal ditolak server ditandai "Ditolak" + tombol Perbaiki |
| Draft otomatis | Teks jurnal tersimpan di perangkat saat diketik, pulih bila halaman tertutup |
| Presensi & form lain | Diblokir dengan pesan jelas saat offline; SOS menyarankan telepon 112 / guru |
| Privasi perangkat bersama | Antrean diikat ke ID pengguna; cache halaman dihapus saat logout/ganti akun |
| Server | `storeJournal` membalas JSON (200/422) bila `Accept: application/json`; form biasa tetap redirect |

Diuji di browser: simpan saat koneksi putus → buka halaman saat server mati (dari cache) → server hidup → terkirim otomatis ke database. Test: 197 lolos (7 baru).

> Setiap mengubah `public/sw.js`, naikkan konstanta `VERSION` agar perangkat siswa memuat versi baru.

---

## 2. Fase P0 — Wajib Sebelum Rilis Massal (Sprint 1–2)

### P0-1. Lupa Password Mandiri
**Tujuan:** Pengguna dapat mereset password sendiri tanpa admin.

**Tugas**
- [ ] Rute `GET/POST /forgot-password` dan `GET/POST /reset-password/{token}` memakai `Password` broker Laravel (tabel `password_reset_tokens`).
- [ ] Throttle permintaan reset (mis. `throttle:5,15` per IP + per email).
- [ ] Respon generik ("jika email terdaftar, tautan dikirim") agar tidak membocorkan keberadaan akun.
- [ ] Siswa tanpa email: alur alternatif — reset oleh guru pembimbing (bukan hanya admin), mengikuti pola `TeacherPasswordResetTest`.
- [ ] Invalidasi semua sesi aktif setelah reset (`Auth::logoutOtherDevices` / hapus baris `sessions`).
- [ ] Link "Lupa password?" di halaman login.

**File terkait:** `routes/web.php`, `app/Http/Controllers/AuthController.php` (atau controller baru `PasswordResetController`), `resources/views/auth/*`, `config/auth.php`.

**Definition of Done**
- Feature test: request reset, token valid/kedaluwarsa, throttle, enumerasi akun tidak bocor.
- SMTP produksi terverifikasi mengirim email (bukan `MAIL_USERNAME=null`).

---

### P0-2. Notifikasi Multi-Channel (Prioritas SOS)
**Tujuan:** Notifikasi kritis sampai ke penerima walau tidak membuka aplikasi.

**Tugas**
- [ ] Buat channel WhatsApp (gateway lokal, mis. Fonnte/Wablas) sebagai `NotificationChannel` kustom; kredensial di `PlatformSetting` / `.env`.
- [ ] `EmergencyIncidentReportedNotification`: `via()` → `['database', 'mail', WhatsAppChannel::class]`.
- [ ] Notifikasi lain (`JournalStatusUpdated`, `RequestStatusUpdated`, `SubscriptionLifecycle`) → tambah `mail` secara opsional sesuai preferensi pengguna.
- [ ] Preferensi notifikasi per pengguna (opt-in/opt-out channel non-kritis; SOS tidak bisa dimatikan).
- [ ] Semua notifikasi eksternal `implements ShouldQueue` (lihat P1-1).
- [ ] Simpan nomor WA guru/admin tervalidasi format `62xxxxxxxxxx`.

**File terkait:** `app/Notifications/*`, `app/Services/NotificationService.php`, `app/Services/IncidentReportService.php`, migrasi kolom preferensi.

**Definition of Done**
- Test dengan `Notification::fake()` memastikan SOS dikirim ke semua channel.
- Uji nyata: SOS dari akun siswa trial sampai ke WA guru < 1 menit.

---

### P0-3. Backup Otomatis & Off-Site
**Tujuan:** Data dapat dipulihkan walau server hilang.

**Tugas**
- [ ] Artisan command `vokatrack:backup` membungkus `BackupService::createDatabaseBackup()`.
- [ ] Jadwalkan di `routes/console.php`: harian 02:00, `->withoutOverlapping()->onOneServer()`.
- [ ] Upload hasil backup ke penyimpanan eksternal (S3-compatible: Cloudflare R2 / Backblaze / IDCloudHost OSS).
- [ ] Backup juga disk privat `storage/app/private` (selfie, foto jurnal, lampiran) — inkremental mingguan.
- [ ] Retensi: 7 harian, 4 mingguan, 3 bulanan; hapus otomatis yang lama.
- [ ] Notifikasi ke super admin jika backup gagal.
- [ ] **Uji restore** terjadwal bulanan ke database staging (backup yang tidak pernah diuji = tidak ada backup).
- [ ] Simpan `APP_KEY` di tempat aman terpisah — backup terenkripsi tidak bisa dibuka tanpa kunci ini.

**File terkait:** `app/Console/Commands/`, `routes/console.php`, `app/Services/BackupService.php`, `config/filesystems.php`.

**Definition of Done**
- Backup harian muncul di storage eksternal 3 hari berturut-turut.
- Restore berhasil di staging, terdokumentasi langkahnya (runbook).

---

### P0-4. Error Monitoring & Observability
**Tujuan:** Tim mengetahui error sebelum pengguna melapor.

**Tugas**
- [ ] Pasang Sentry (`sentry/sentry-laravel`) atau Flare; set `SENTRY_LARAVEL_DSN` di produksi.
- [ ] Tag konteks: `school_id`, `user_id`, role (tanpa data pribadi seperti nama/NIS/email).
- [ ] Ganti `LOG_STACK=single` → `daily` dengan retensi 14 hari agar log tidak membengkak.
- [ ] Uptime monitor eksternal (UptimeRobot/BetterStack) untuk `/up` dan halaman login.
- [ ] Heartbeat cron: `->pingOnSuccess()` untuk `vokatrack:evaluate-absence`, `saas:evaluate-subscriptions`, `vokatrack:backup`.
- [ ] Alert ke super admin bila `failed_jobs` > 0 (dashboard sudah menghitung — tambah notifikasi).

**File terkait:** `composer.json`, `bootstrap/app.php`, `config/logging.php`, `.env.production.example`, `routes/console.php`.

**Definition of Done**
- Exception uji di staging muncul di Sentry dengan tag tenant.
- Cron yang dimatikan sengaja memicu alert heartbeat.

---

### P0-5. CI/CD Aman & Rollback
**Tujuan:** Deploy tidak pernah menjatuhkan semua sekolah tanpa jalan kembali.

**Tugas**
- [ ] GitHub Actions: `composer install`, `php artisan test`, `npm run build` di setiap PR & push ke `main`. Merge diblokir jika gagal.
- [ ] Ganti `deploy.sh` ke pola **release directory** (`releases/<timestamp>` + symlink `current`), atau gunakan Deployer/Envoy:
  - Build di direktori baru → migrate → cache → switch symlink → reload PHP-FPM.
  - Simpan 5 rilis terakhir; `rollback` = pindah symlink ke rilis sebelumnya.
- [ ] Hapus `git reset --hard` di direktori live.
- [ ] Migrasi wajib *backward compatible* (expand → migrate → contract) agar rollback kode tidak rusak.
- [ ] Backup database otomatis sebelum `migrate --force`.
- [ ] Jendela deploy di luar jam sibuk (hindari 05:30–09:00 & 15:30–17:30 WIB).
- [ ] Lingkungan **staging** terpisah (subdomain `staging.`) dengan data dummy untuk uji sebelum produksi.

**File terkait:** `.github/workflows/ci.yml` (baru), `deploy.sh`, dokumentasi runbook.

**Definition of Done**
- PR dengan test gagal tidak bisa di-merge.
- Simulasi rollback di staging < 1 menit.

---

## 3. Fase P1 — Skala & Ketahanan (Sprint 3–4)

### P1-1. Queue untuk Pekerjaan Berat
- [ ] Semua notifikasi `implements ShouldQueue`.
- [ ] `CertificateService::generateBatchForSchool()` dipecah menjadi job per siswa (`Bus::batch`) dengan progres di UI admin.
- [ ] Broadcast pengumuman & export rekap besar (Kepsek, parent-links) via job, hasil diunduh dari notifikasi.
- [ ] Worker dijalankan Supervisor (`php artisan queue:work --tries=3 --max-time=3600`), restart otomatis saat deploy (`queue:restart`).
- **DoD:** Batch 500 sertifikat selesai tanpa timeout HTTP; job gagal tercatat & bisa di-retry.

### P1-2. Redis untuk Session, Cache, Queue
- [ ] Pasang Redis di server; `SESSION_DRIVER=redis`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`.
- [ ] Cache pengaturan sekolah (`school_settings`, jam check-in, geofence) per tenant dengan invalidasi saat update.
- [ ] Load test check-in serentak (k6/JMeter): target 500 check-in/menit, p95 < 1 detik.
- [ ] Review indeks database untuk query panas: `attendances(school_id, student_id, date)`, `journals(school_id, status)`.
- **DoD:** Hasil load test terdokumentasi; tidak ada query > 200 ms di jalur check-in.

### P1-3. Payment Gateway Invoice
- [ ] Integrasi Midtrans/Xendit/Tripay (VA, QRIS) untuk `Invoice`.
- [ ] Webhook terverifikasi signature → tandai invoice lunas → perpanjang `SchoolSubscription` otomatis.
- [ ] Idempotensi webhook (event ID unik) & log audit pembayaran.
- [ ] Gunakan mode sandbox di staging.
- **DoD:** Alur bayar → aktif otomatis berjalan end-to-end di sandbox; test webhook duplikat aman.

### P1-4. PWA & Mode Offline Siswa
- [ ] `manifest.json` + service worker (installable di Android).
- [ ] Cache aset statis & halaman beranda/jurnal.
- [ ] Antrian offline (IndexedDB) untuk **draft jurnal**; sinkron otomatis saat online.
- [ ] Presensi tetap wajib online (validasi waktu & GPS server-side), tapi tampilkan pesan jelas + retry otomatis saat sinyal kembali.
- **DoD:** Draft jurnal yang ditulis saat mode pesawat tersinkron setelah online; Lighthouse PWA lolos.

---

## 4. Fase P2 — Nilai Tambah (Paralel / Pasca Rilis)

| Item | Tugas Utama |
|---|---|
| **Dashboard Kepsek** | Tren kehadiran mingguan/bulanan, perbandingan antar jurusan & DU/DI, siswa berisiko (alfa beruntun), ringkasan supervisi guru |
| **Portal DU/DI** | Halaman profil & ganti data kontak, rekap kehadiran siswa per bulan (PDF), riwayat penilaian |
| **2FA** | TOTP wajib untuk Super Admin, opsional untuk Admin Sekolah & Kepsek |
| **Audit & Kepatuhan** | Kebijakan privasi & persetujuan data siswa (UU PDP), retensi & penghapusan data setelah periode PKL berakhir |
| **Onboarding Mandiri** | Wizard setup sekolah baru (jam, geofence, import Excel siswa/guru/DU/DI) + template Excel resmi |
| **Dokumentasi Pengguna** | Panduan per role (PDF/video singkat), FAQ, kanal dukungan |

---

## 5. Jadwal Ringkas

| Sprint | Fokus | Keluaran |
|---|---|---|
| **Sprint 1** | P0-3 Backup, P0-4 Monitoring, P0-5 CI + staging | Server aman, terpantau, deploy bisa di-rollback |
| **Sprint 2** | P0-1 Lupa Password, P0-2 Notifikasi WA/Email SOS | Beban admin turun, SOS andal |
| **Sprint 3** | P1-1 Queue, P1-2 Redis + load test | Siap beban check-in serentak |
| **Sprint 4** | P1-3 Payment Gateway, P1-4 PWA | Billing otomatis, siswa tahan sinyal lemah |
| **Berjalan** | P2 | Peningkatan nilai jual |

> Trial **SMKN 04 Bombana** dapat tetap berjalan selama Sprint 1–2. Masukan dari trial (terutama soal sinyal & notifikasi) dijadikan input Sprint 3–4.

---

## 6. Gerbang Rilis Massal (Go/No-Go Checklist)

Rilis massal **hanya** dibuka bila seluruh poin berikut tercentang:

- [ ] Semua item P0 selesai & lolos Definition of Done.
- [ ] CI hijau; cakupan test mencakup fitur baru (reset password, notifikasi multi-channel, backup).
- [ ] Backup harian off-site berjalan ≥ 7 hari & uji restore berhasil.
- [ ] Error monitoring aktif; tidak ada error kritis terbuka > 48 jam.
- [ ] Load test check-in memenuhi target (setelah P1-2).
- [ ] Trial SMKN 04 Bombana berjalan ≥ 2 minggu tanpa insiden kehilangan data / downtime jam sibuk.
- [ ] Runbook tersedia: deploy, rollback, restore backup, penanganan insiden.
- [ ] Label versi di `DOKUMENTASI-FITUR.md` diperbarui dari "2.1.0-Production Ready" sesuai status sebenarnya.
