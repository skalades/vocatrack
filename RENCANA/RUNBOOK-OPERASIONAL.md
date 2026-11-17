# Runbook Operasional VokaTrack

Panduan langkah demi langkah untuk deploy, rollback, backup/restore, pemantauan, dan
penanganan insiden di server produksi. Lihat juga [RENCANA-KESIAPAN-PRODUKSI.md](RENCANA-KESIAPAN-PRODUKSI.md).

---

## 1. Prasyarat Server (sekali saja)

### 1.1 Cron scheduler Laravel
Tanpa ini, auto-alfa, evaluasi langganan, **backup harian**, dan cek job gagal tidak berjalan.

```cron
* * * * * cd /www/wwwroot/vocatrack/current && php artisan schedule:run >> /dev/null 2>&1
```

Jadwal yang terdaftar (`php artisan schedule:list`):

| Perintah | Waktu | Fungsi |
|---|---|---|
| `vokatrack:evaluate-absence` | 23:55 | Tandai Alfa siswa yang tidak presensi |
| `saas:evaluate-subscriptions` | 00:05 | Siklus langganan & tagihan |
| `vokatrack:backup` | 02:00 (`BACKUP_SCHEDULE_TIME`) | Backup terenkripsi + unggah off-site + retensi |
| `vokatrack:check-failed-jobs` | tiap jam | Peringatan bila ada job antrean gagal |

### 1.2 Queue worker (Supervisor)
```ini
[program:vokatrack-worker]
command=php /www/wwwroot/vocatrack/current/artisan queue:work --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www
numprocs=1
stdout_logfile=/www/wwwroot/vocatrack/shared/storage/logs/worker.log
```

### 1.3 Penyimpanan backup off-site
1. Buat bucket privat di Cloudflare R2 / Backblaze B2 / IDCloudHost OSS.
2. Buat access key yang **hanya** punya akses ke bucket itu.
3. Isi `BACKUP_S3_*` di `.env` produksi dan set `BACKUP_OFFSITE_DISK=backup_s3`.
   - R2: `BACKUP_S3_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com`, `BACKUP_S3_REGION=auto`.
   - Backblaze: `BACKUP_S3_ENDPOINT=https://s3.<region>.backblazeb2.com`.
4. Uji: `php artisan vokatrack:backup --no-prune` → pastikan berkas muncul di bucket.

> **PENTING:** Backup dienkripsi dengan `APP_KEY`. Simpan salinan `APP_KEY` di password
> manager tim, **terpisah** dari server. Tanpa kunci ini backup tidak bisa dibuka.

### 1.4 Backup berkas siswa (selfie, foto jurnal, lampiran)
Backup database tidak mencakup berkas. Sinkronkan `shared/storage/app/private` ke bucket
yang sama dengan [rclone](https://rclone.org) (inkremental, hanya berkas baru yang dikirim):

```cron
30 3 * * 0 rclone sync /www/wwwroot/vocatrack/shared/storage/app/private r2:vokatrack-backup/files --fast-list
```

### 1.5 Pemantauan
- **Sentry:** isi `SENTRY_LARAVEL_DSN`. Uji dengan `php artisan sentry:test`.
- **Uptime:** pantau `https://vocatrack.nadirlabs.net/up` tiap 1 menit (UptimeRobot/BetterStack).
- **Heartbeat cron:** buat check di Healthchecks.io untuk tiap cron, isi `HEARTBEAT_*_URL`.
  Jadwal grace: absence & subscriptions 24 jam, backup 24 jam.

---

## 2. Deploy

### 2.1 Deploy berbasis rilis (direkomendasikan)
```bash
scripts/deploy-release.sh deploy
```

Urutan: clone ke `releases/<waktu>` → `composer install` → build aset → **backup pra-deploy**
→ `migrate` → cache → switch symlink atomik → reload PHP-FPM → health check `/up`.
Jika langkah mana pun gagal **sebelum** switch, folder rilis dibuang dan situs tidak tersentuh.
Jika health check gagal **setelah** switch, otomatis kembali ke rilis sebelumnya.

Isi `/www/wwwroot/vocatrack/deploy.env`:
```bash
DEPLOY_REPO=git@github.com:skalades/vocatrack.git
DEPLOY_BRANCH=main
PHP_FPM_RELOAD="/etc/init.d/php-fpm-82 reload"
HEALTHCHECK_URL=https://vocatrack.nadirlabs.net/up
WEB_USER=www
```

### 2.2 Migrasi satu kali dari struktur lama (`deploy.sh` in-place)
Lakukan di luar jam sibuk, setelah backup manual.

1. `mkdir -p /www/wwwroot/vocatrack/shared`
2. Salin `.env` lama → `/www/wwwroot/vocatrack/shared/.env`.
3. Salin `storage/` lama → `/www/wwwroot/vocatrack/shared/storage/` (pertahankan izin).
4. Pasang deploy key GitHub (read-only) untuk user server.
5. Jalankan `scripts/deploy-release.sh deploy` (bisa dari salinan repo lama).
6. Di aaPanel, ubah **Site directory** ke `/www/wwwroot/vocatrack/current` dan
   **Running directory** ke `/public`. Reload Nginx.
7. Ubah cron & Supervisor agar menunjuk ke `.../current`.
8. Setelah stabil 1 minggu, arsipkan folder lama `vocatrack.nadirlabs.net`.

### 2.3 Aturan deploy
- **Jangan deploy** pukul 05:30–09:00 dan 15:30–17:30 WIB (jam check-in/check-out).
- Hanya deploy commit yang **CI-nya hijau** di GitHub Actions.
- Migrasi harus *backward compatible* (expand → migrate → contract):
  - Tambah kolom baru sebagai nullable, isi data, baru jadikan wajib di rilis berikutnya.
  - Jangan rename/hapus kolom yang masih dipakai rilis sebelumnya.
  Ini yang membuat rollback kode aman tanpa rollback database.

---

## 3. Rollback

```bash
scripts/deploy-release.sh list       # lihat rilis
scripts/deploy-release.sh rollback   # kembali ke rilis sebelumnya (< 10 detik)
```

Rollback hanya mengganti **kode**. Jika migrasi rilis baru merusak data, lanjut ke §4 (restore).

---

## 4. Restore Database

### 4.1 Dari backup lokal (server masih utuh)
Panel Super Admin → **Backup** → pilih snapshot → **Restore**. Sistem otomatis membuat
snapshot pengaman `pre_restore_safety_*` sebelum restore.

### 4.2 Dari off-site (server hilang/rusak)
1. Siapkan server baru, deploy kode (§2), isi `.env` dengan **`APP_KEY` yang sama**.
2. `php artisan migrate --force` (skema kosong).
3. Unduh snapshot terbaru dari bucket ke `shared/storage/app/backups/`.
4. Buat akun super admin sementara (`php artisan tinker`), login, lalu restore dari panel.
5. Pulihkan berkas siswa: `rclone sync r2:vokatrack-backup/files shared/storage/app/private`.

### 4.3 Uji restore bulanan (wajib)
Setiap awal bulan, restore snapshot terbaru ke **staging** dan cek: login, dashboard admin
sekolah, jumlah siswa & presensi. Catat hasilnya di log operasional.

---

## 5. Penanganan Insiden

| Gejala | Langkah Pertama |
|---|---|
| Situs down (`/up` gagal) | Cek `shared/storage/logs/laravel-*.log` & Sentry → jika setelah deploy, `rollback` |
| Notifikasi "Cadangan otomatis gagal" | Lihat pesan error di notifikasi; cek ruang disk (`df -h`) & kredensial bucket |
| Notifikasi "Job antrean gagal" | `php artisan queue:failed` → perbaiki → `php artisan queue:retry all` |
| Heartbeat cron tidak masuk | Cek crontab & `php artisan schedule:list`; jalankan perintah manual |
| Siswa massal gagal check-in | Cek Sentry (filter tag `school_id`), jam server (`date`), pengaturan GPS sekolah |

Setiap insiden yang berdampak ke sekolah: catat waktu, dampak, penyebab, dan tindakan
pencegahan di log operasional tim.

---

## 6. Staging

Siapkan subdomain `staging.vocatrack.nadirlabs.net` dengan struktur yang sama (§2.1),
database terpisah, `APP_ENV=staging`, `SENTRY_ENVIRONMENT=staging`, dan `MAIL_MAILER=log`.
Deploy ke staging dulu, lakukan uji cepat (login tiap role, check-in, jurnal), baru produksi.
