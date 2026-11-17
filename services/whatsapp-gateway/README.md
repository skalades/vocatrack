# VokaTrack — WhatsApp Gateway Mandiri (Self-Hosted)

Microservice pengiriman notifikasi WhatsApp darurat (SOS) dan transaksional untuk VokaTrack secara mandiri tanpa pihak ketiga. Dibangun menggunakan Node.js dan engine `@whiskeysockets/baileys` yang sangat ringan, hemat memori, dan andal.

---

## 🚀 Keunggulan
- **100% Gratis & Mandiri:** Berjalan di server/laptop Anda sendiri, tanpa biaya langganan bulanan pihak ketiga.
- **Privasi Data Penuh:** Pesan dan nomor HP siswa/guru tidak melewati server perantara.
- **Hemat Sumber Daya:** Menggunakan protokol WebSocket langsung (hanya ~40MB RAM), tidak membutuhkan Chromium/Puppeteer.
- **Web UI & Auto QR:** Tersedia antarmuka web modern di `http://localhost:3000` untuk scan QR dan uji coba kirim pesan.
- **Terintegrasi Otomatis:** Langsung tersambung dengan sistem SOS VokaTrack Laravel.

---

## 🛠️ Cara Menjalankan

### 1. Masuk ke Direktori
```bash
cd services/whatsapp-gateway
```

### 2. Jalankan Microservice
```bash
npm start
```
*(Atau gunakan `node index.js`)*

### 3. Scan QR Code WhatsApp
- Buka peramban di komputer Anda: **`http://localhost:3000`** (atau scan langsung dari terminal).
- Buka aplikasi WhatsApp di HP pengirim (bisa nomor sekolah atau nomor bot).
- Masuk ke **Pengaturan > Perangkat Tertaut > Tautkan Perangkat** dan scan QR Code.
- Status akan otomatis berubah menjadi **TERHUBUNG ✅**.

---

## ⚙️ Konfigurasi di VokaTrack Laravel (`.env`)
Di berkas `.env` root VokaTrack, atur variabel berikut:
```env
WHATSAPP_GATEWAY_PROVIDER=custom
WHATSAPP_GATEWAY_URL=http://127.0.0.1:3000/send-message
WHATSAPP_GATEWAY_TOKEN=vokatrack-secret-key-2026
```

---

## 🧪 Uji Coba dari Terminal Laravel
Jalankan perintah berikut di root project:
```bash
php artisan vokatrack:test-whatsapp 08xxxxxxxxxx
```
Pesan akan langsung terkirim ke nomor target melalui WhatsApp Gateway Mandiri Anda.
