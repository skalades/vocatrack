require('dotenv').config();
const express = require('express');
const { default: makeWASocket, useMultiFileAuthState, DisconnectReason } = require('@whiskeysockets/baileys');
const pino = require('pino');
const QRCode = require('qrcode');
const path = require('path');
const fs = require('fs');

const app = express();
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

const PORT = process.env.PORT || 3000;
const API_KEY = process.env.API_KEY || 'vokatrack-secret-key-2026';
const AUTH_DIR = path.join(__dirname, 'auth_info_baileys');

let sock = null;
let connectionStatus = 'initializing';
let qrCodeDataUrl = null;
let connectedUser = null;
let lastError = null;

// Normalisasi nomor telepon ke format WhatsApp (62xxxxxxxxxx@s.whatsapp.net)
function formatWhatsAppJid(phone) {
    if (!phone) return null;
    let cleaned = phone.toString().replace(/[^0-9]/g, '');
    if (cleaned.startsWith('0')) {
        cleaned = '62' + cleaned.substring(1);
    } else if (cleaned.startsWith('8')) {
        cleaned = '62' + cleaned;
    }
    return cleaned + '@s.whatsapp.net';
}

// Inisialisasi Koneksi Baileys
async function connectToWhatsApp() {
    connectionStatus = 'connecting';
    const { state, saveCreds } = await useMultiFileAuthState(AUTH_DIR);

    sock = makeWASocket({
        auth: state,
        logger: pino({ level: 'silent' }), // Silent agar terminal tetap bersih dan rapi
        printQRInTerminal: false,
        browser: ['VokaTrack SOS Gateway', 'Chrome', '1.0.0']
    });

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            connectionStatus = 'waiting_scan';
            try {
                qrCodeDataUrl = await QRCode.toDataURL(qr, { width: 300, margin: 2 });
                console.log('\n[VokaTrack WA Gateway] Silakan buka http://localhost:' + PORT + ' untuk scan QR Code!');
                // Juga tampilkan di console terminal
                QRCode.toString(qr, { type: 'terminal', small: true }, (err, str) => {
                    if (!err) console.log(str);
                });
            } catch (err) {
                console.error('[VokaTrack WA Gateway] Gagal generate QR:', err);
            }
        }

        if (connection === 'close') {
            const statusCode = lastDisconnect?.error?.output?.statusCode;
            const shouldReconnect = statusCode !== DisconnectReason.loggedOut;
            lastError = lastDisconnect?.error?.message || 'Koneksi terputus';
            connectionStatus = 'disconnected';
            qrCodeDataUrl = null;
            connectedUser = null;

            console.log(`[VokaTrack WA Gateway] Koneksi terputus. Alasan: ${statusCode}. Reconnect: ${shouldReconnect}`);

            if (shouldReconnect) {
                setTimeout(connectToWhatsApp, 5000);
            } else {
                console.log('[VokaTrack WA Gateway] Sesi telah logout. Silakan scan ulang.');
                try {
                    fs.rmSync(AUTH_DIR, { recursive: true, force: true });
                } catch (e) {}
                setTimeout(connectToWhatsApp, 3000);
            }
        } else if (connection === 'open') {
            connectionStatus = 'connected';
            qrCodeDataUrl = null;
            lastError = null;
            connectedUser = sock.user;
            const phone = sock.user?.id ? sock.user.id.split(':')[0] : 'Aktif';
            console.log(`\n======================================================`);
            console.log(`✅ [VokaTrack WA Gateway] WhatsApp BERHASIL TERHUBUNG!`);
            console.log(`   Nomor Pengirim: ${phone}`);
            console.log(`   REST API Siap: http://localhost:${PORT}/send-message`);
            console.log(`======================================================\n`);
        }
    });
}

// Middleware Autentikasi API Key (Opsional / Bearer Token)
function authMiddleware(req, res, next) {
    if (!API_KEY) return next();
    const authHeader = req.headers['authorization'];
    if (!authHeader) {
        // Jika tidak ada header, cek query atau izinkan jika dari localhost
        return res.status(401).json({ success: false, error: 'Unauthorized: Header Authorization Bearer diperlukan.' });
    }
    const token = authHeader.replace(/^Bearer\s+/i, '');
    if (token !== API_KEY) {
        return res.status(403).json({ success: false, error: 'Forbidden: API Key tidak valid.' });
    }
    next();
}

// ---------------- REST API ROUTES ----------------

// 1. Endpoint Kirim Pesan (Dipanggil oleh VokaTrack Laravel)
app.post('/send-message', authMiddleware, async (req, res) => {
    const { phone, message } = req.body;

    if (!phone || !message) {
        return res.status(422).json({ success: false, error: 'Parameter "phone" dan "message" wajib diisi.' });
    }

    if (connectionStatus !== 'connected' || !sock) {
        return res.status(503).json({
            success: false,
            error: 'Gateway belum terhubung ke WhatsApp. Silakan scan QR terlebih dahulu di http://localhost:' + PORT
        });
    }

    const jid = formatWhatsAppJid(phone);
    if (!jid) {
        return res.status(400).json({ success: false, error: 'Nomor telepon tidak valid.' });
    }

    try {
        const sent = await sock.sendMessage(jid, { text: message });
        return res.json({
            success: true,
            message: 'Pesan berhasil dikirim via Gateway Mandiri',
            target: jid,
            id: sent?.key?.id
        });
    } catch (err) {
        console.error('[VokaTrack WA Gateway] Gagal kirim pesan ke ' + jid + ':', err);
        return res.status(500).json({ success: false, error: err.message || 'Gagal mengirim pesan.' });
    }
});

// 2. Status Endpoint (JSON)
app.get('/status', (req, res) => {
    res.json({
        success: true,
        status: connectionStatus,
        user: connectedUser ? { id: connectedUser.id?.split(':')[0], name: connectedUser.name } : null,
        error: lastError
    });
});

// 3. QR Endpoint (JSON)
app.get('/qr-data', (req, res) => {
    res.json({
        status: connectionStatus,
        qr: qrCodeDataUrl
    });
});

// 4. Logout Endpoint
app.post('/logout', (req, res) => {
    try {
        if (sock) sock.logout();
        fs.rmSync(AUTH_DIR, { recursive: true, force: true });
        connectionStatus = 'disconnected';
        setTimeout(connectToWhatsApp, 2000);
        res.json({ success: true, message: 'Berhasil logout. Silakan scan ulang.' });
    } catch (e) {
        res.status(500).json({ success: false, error: e.message });
    }
});

// 5. Dashboard Web UI (VokaTrack Aesthetic)
app.get('/', (req, res) => {
    const phone = connectedUser?.id ? connectedUser.id.split(':')[0] : '-';
    res.send(`<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VokaTrack — WhatsApp Gateway Mandiri</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #0B2B25; color: #fff; }
        .font-space { font-family: 'Space Grotesk', sans-serif; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-lg w-full bg-[#12382F] border border-white/10 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
        <div class="flex items-center justify-between pb-4 border-b border-white/10">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-2xl bg-[#C6F26B] text-[#0B2B25] font-extrabold flex items-center justify-center font-space text-lg">V</span>
                <div>
                    <h1 class="font-extrabold text-base tracking-tight text-white leading-none">VokaTrack WA Gateway</h1>
                    <p class="text-[11px] text-[#A9C2B9] font-medium mt-1">Self-Hosted WhatsApp Gateway Engine</p>
                </div>
            </div>
            <span id="badge-status" class="px-3 py-1 rounded-full text-xs font-bold ${connectionStatus === 'connected' ? 'bg-[#C6F26B] text-[#0B2B25]' : 'bg-amber-400 text-amber-950'}">
                ${connectionStatus === 'connected' ? '● TERHUBUNG' : (connectionStatus === 'waiting_scan' ? 'Scan QR' : 'Menghubungkan...')}
            </span>
        </div>

        <div id="content-area" class="text-center space-y-4">
            ${connectionStatus === 'connected' ? `
                <div class="p-5 rounded-2xl bg-white/[0.05] border border-white/10 space-y-3">
                    <div class="w-16 h-16 rounded-full bg-[#C6F26B]/20 text-[#C6F26B] flex items-center justify-center mx-auto">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-white">WhatsApp Terhubung</h2>
                        <p class="text-xs text-[#A9C2B9]">Nomor Pengirim: <span class="font-mono font-bold text-white">+${phone}</span></p>
                    </div>
                    <p class="text-[11px] text-white/70">Gateway mandiri ini siap mengirimkan notifikasi darurat SOS dari VokaTrack.</p>
                </div>

                <div class="p-4 rounded-2xl bg-black/20 border border-white/5 text-left space-y-2">
                    <label class="block text-xs font-bold text-[#A9C2B9]">Uji Kirim Pesan:</label>
                    <div class="flex gap-2">
                        <input type="text" id="test-phone" placeholder="08xxxxxxxxxx" class="flex-1 bg-white/10 border border-white/20 rounded-xl px-3 py-2 text-xs text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-[#C6F26B]">
                        <button onclick="sendTest()" class="px-4 py-2 rounded-xl bg-[#C6F26B] text-[#0B2B25] font-extrabold text-xs hover:bg-[#b8e65e] transition-colors cursor-pointer">Kirim</button>
                    </div>
                    <p id="test-result" class="text-[11px]"></p>
                </div>

                <form action="/logout" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin logout dari nomor WhatsApp ini?')">
                    <button type="submit" class="text-xs text-rose-300 hover:text-rose-200 underline font-semibold cursor-pointer">Ganti Nomor (Logout)</button>
                </form>
            ` : (connectionStatus === 'waiting_scan' && qrCodeDataUrl ? `
                <div class="space-y-4">
                    <p class="text-xs text-[#A9C2B9]">Scan QR Code ini menggunakan WhatsApp di HP Anda:<br><span class="text-white font-semibold">WhatsApp &gt; Perangkat Tertaut &gt; Tautkan Perangkat</span></p>
                    <div class="inline-block p-3 bg-white rounded-2xl shadow-xl">
                        <img src="${qrCodeDataUrl}" alt="QR Code WhatsApp" class="w-64 h-64 mx-auto">
                    </div>
                    <p class="text-[11px] text-white/50">Halaman ini akan otomatis menyambung begitu QR Code berhasil di-scan.</p>
                </div>
            ` : `
                <div class="py-12 space-y-3">
                    <div class="w-8 h-8 border-2 border-[#C6F26B] border-t-transparent rounded-full animate-spin mx-auto"></div>
                    <p class="text-xs text-[#A9C2B9]">Sedang memuat engine WhatsApp... Mohon tunggu sebentar.</p>
                </div>
            `)}
        </div>

        <div class="pt-4 border-t border-white/10 text-center">
            <p class="text-[10px] text-white/40 font-mono">VokaTrack Independent Notification Engine · Port: ${PORT}</p>
        </div>
    </div>

    <script>
        // Auto refresh saat menunggu scan
        let currentStatus = '${connectionStatus}';
        if (currentStatus !== 'connected') {
            const timer = setInterval(async () => {
                try {
                    const res = await fetch('/status');
                    const data = await res.json();
                    if (data.status !== currentStatus) {
                        location.reload();
                    }
                } catch (e) {}
            }, 3000);
        }

        async function sendTest() {
            const phone = document.getElementById('test-phone').value;
            const resEl = document.getElementById('test-result');
            if (!phone) return alert('Masukkan nomor tujuan!');
            resEl.textContent = 'Mengirim...';
            resEl.className = 'text-[11px] text-amber-300';
            try {
                const res = await fetch('/send-message', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ${API_KEY}'
                    },
                    body: JSON.stringify({
                        phone: phone,
                        message: 'Halo! Ini adalah pesan uji coba dari Gateway WhatsApp Mandiri VokaTrack.'
                    })
                });
                const data = await res.json();
                if (data.success) {
                    resEl.textContent = '✅ Pesan berhasil terkirim!';
                    resEl.className = 'text-[11px] text-[#C6F26B] font-bold';
                } else {
                    resEl.textContent = '❌ Gagal: ' + (data.error || 'Terjadi kesalahan');
                    resEl.className = 'text-[11px] text-rose-300';
                }
            } catch (err) {
                resEl.textContent = '❌ Error: ' + err.message;
                resEl.className = 'text-[11px] text-rose-300';
            }
        }
    </script>
</body>
</html>`);
});

// Mulai Server dan Jalankan Baileys
app.listen(PORT, () => {
    console.log(`[VokaTrack WA Gateway] Web UI aktif di http://localhost:${PORT}`);
    connectToWhatsApp();
});
