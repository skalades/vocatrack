require('dotenv').config();

const express = require('express');
const { Client, LocalAuth } = require('whatsapp-web.js');
const QRCode = require('qrcode');
const path = require('path');
const fs = require('fs');

const app = express();
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

const PORT = process.env.PORT || 3005;
const API_KEY = process.env.API_KEY || 'vokatrack-secret-key-2026';

// ─── State Global ───────────────────────────────────────────────────────────
let connectionStatus = 'connecting'; // connecting | waiting_scan | connected
let qrCodeDataUrl    = null;
let connectedUser    = null;
let lastError        = null;

// ─── Helpers ─────────────────────────────────────────────────────────────────
function formatPhone(phone) {
    if (!phone) return null;
    let n = phone.toString().replace(/[^0-9]/g, '');
    if (n.startsWith('0'))    n = '62' + n.substring(1);
    else if (n.startsWith('8')) n = '62' + n;
    return n + '@c.us';
}

// ─── WhatsApp Client (whatsapp-web.js) ───────────────────────────────────────
const client = new Client({
    authStrategy: new LocalAuth({
        dataPath: path.join(__dirname, 'auth_info_wwebjs'),
    }),
    puppeteer: {
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-accelerated-2d-canvas',
            '--no-first-run',
            '--no-zygote',
            '--single-process',
            '--disable-gpu',
        ],
    },
});

// QR siap di-scan
client.on('qr', async (qr) => {
    connectionStatus = 'waiting_scan';
    lastError = null;
    try {
        qrCodeDataUrl = await QRCode.toDataURL(qr, { width: 300, margin: 2 });

        // Cetak QR di terminal agar bisa langsung scan via SSH
        const qrText = await QRCode.toString(qr, { type: 'terminal', small: true });
        console.log('\n╔══════════════ SCAN QR CODE INI ══════════════╗');
        console.log(qrText);
        console.log('╚══════════════════════════════════════════════╝');
        console.log('[VokaTrack WA] Atau buka: https://vocatrack.nadirlabs.net/super-admin/whatsapp\n');
    } catch (err) {
        console.error('[VokaTrack WA] Gagal generate QR:', err.message);
    }
});

// Auth berhasil (sesi terbaca / QR berhasil di-scan)
client.on('authenticated', () => {
    console.log('[VokaTrack WA] ✔ Autentikasi berhasil. Memuat sesi...');
    connectionStatus = 'connecting';
    qrCodeDataUrl = null;
});

// Siap kirim pesan
client.on('ready', () => {
    connectionStatus = 'connected';
    qrCodeDataUrl    = null;
    lastError        = null;
    connectedUser    = client.info;
    const phone = client.info?.wid?.user || '-';
    console.log('======================================================');
    console.log('✅ [VokaTrack WA] WhatsApp BERHASIL TERHUBUNG!');
    console.log(`   Nomor Pengirim: +${phone}`);
    console.log(`   REST API Siap : http://localhost:${PORT}/send-message`);
    console.log('======================================================\n');
});

// Gagal autentikasi
client.on('auth_failure', (msg) => {
    console.error('[VokaTrack WA] ✘ Autentikasi gagal:', msg);
    connectionStatus = 'connecting';
    lastError = msg;
});

// Koneksi terputus — reinit otomatis
client.on('disconnected', (reason) => {
    console.log('[VokaTrack WA] Terputus:', reason);
    connectionStatus = 'connecting';
    connectedUser    = null;
    lastError        = reason;
    setTimeout(() => {
        console.log('[VokaTrack WA] Menginisialisasi ulang koneksi...');
        client.initialize().catch(() => {});
    }, 5000);
});

// Mulai engine
client.initialize().catch((err) => {
    console.error('[VokaTrack WA] Gagal inisialisasi:', err.message);
});

// ─── Middleware Auth ──────────────────────────────────────────────────────────
function authMiddleware(req, res, next) {
    if (!API_KEY) return next();
    const token = (req.headers['authorization'] || '').replace(/^Bearer\s+/i, '');
    if (token !== API_KEY) {
        return res.status(401).json({ success: false, error: 'Unauthorized: API Key tidak valid.' });
    }
    next();
}

// ─── REST API ─────────────────────────────────────────────────────────────────

// POST /send-message
app.post('/send-message', authMiddleware, async (req, res) => {
    const { phone, message } = req.body;
    if (!phone || !message) {
        return res.status(422).json({ success: false, error: 'Parameter "phone" dan "message" wajib diisi.' });
    }
    if (connectionStatus !== 'connected') {
        return res.status(503).json({ success: false, error: 'Gateway belum terhubung. Silakan scan QR terlebih dahulu.' });
    }
    const jid = formatPhone(phone);
    if (!jid) return res.status(400).json({ success: false, error: 'Nomor telepon tidak valid.' });

    try {
        const result = await client.sendMessage(jid, message);
        return res.json({ success: true, message: 'Pesan berhasil dikirim.', id: result?.id?._serialized });
    } catch (err) {
        console.error('[VokaTrack WA] Gagal kirim ke', jid, ':', err.message);
        return res.status(500).json({ success: false, error: err.message });
    }
});

// GET /status
app.get('/status', (req, res) => {
    const phone = connectedUser?.wid?.user || null;
    res.json({
        success: true,
        status: connectionStatus,
        user: phone ? { phone, name: connectedUser?.pushname || '-' } : null,
        error: lastError,
        engine: 'whatsapp-web.js',
    });
});

// GET /qr-data
app.get('/qr-data', (req, res) => {
    res.json({ status: connectionStatus, qr: qrCodeDataUrl });
});

// POST /logout
app.post('/logout', async (req, res) => {
    try {
        await client.logout();
        res.json({ success: true, message: 'Logout berhasil. Reinisialisasi...' });
        setTimeout(() => client.initialize().catch(() => {}), 3000);
    } catch (e) {
        res.status(500).json({ success: false, error: e.message });
    }
});

// GET / — Dashboard Web UI
app.get('/', (req, res) => {
    const phone = connectedUser?.wid?.user || '-';
    const name  = connectedUser?.pushname  || '-';
    res.send(`<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VokaTrack — WhatsApp Gateway</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; background: #0B2B25; color: #fff; }</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-lg w-full bg-[#12382F] border border-white/10 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-white/10">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-2xl bg-[#C6F26B] text-[#0B2B25] font-extrabold flex items-center justify-center text-lg" style="font-family:'Space Grotesk',sans-serif">V</span>
                <div>
                    <h1 class="font-extrabold text-base text-white leading-none">VokaTrack WA Gateway</h1>
                    <p class="text-[11px] text-[#A9C2B9] mt-1">Engine: whatsapp-web.js · Port ${PORT}</p>
                </div>
            </div>
            <span id="badge" class="px-3 py-1 rounded-full text-xs font-bold ${connectionStatus === 'connected' ? 'bg-[#C6F26B] text-[#0B2B25]' : 'bg-amber-400 text-amber-950'}">
                ${connectionStatus === 'connected' ? '● TERHUBUNG' : connectionStatus === 'waiting_scan' ? 'Scan QR' : 'Menghubungkan...'}
            </span>
        </div>

        <!-- Koneksi Perangkat -->
        <div class="pb-2 border-b border-white/10">
            <p class="text-xs font-bold text-[#A9C2B9] mb-1">Koneksi Perangkat WhatsApp</p>
            <p class="text-[11px] text-white/50">Scan QR Code dengan WhatsApp di HP Administrator/Sekolah.</p>
            <p class="text-xs text-white/40 mt-1">Port: ${PORT}</p>
        </div>

        <!-- Content Area -->
        <div id="content-area" class="text-center space-y-4">
            ${connectionStatus === 'connected' ? `
                <div class="p-5 rounded-2xl bg-white/[0.05] border border-white/10 space-y-3">
                    <div class="w-16 h-16 rounded-full bg-[#C6F26B]/20 text-[#C6F26B] flex items-center justify-center mx-auto">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h2 class="text-lg font-bold text-white">WhatsApp Terhubung</h2>
                    <p class="text-xs text-[#A9C2B9]">Nama: <span class="font-semibold text-white">${name}</span></p>
                    <p class="text-xs text-[#A9C2B9]">Nomor: <span class="font-mono font-bold text-white">+${phone}</span></p>
                </div>
                <div class="p-4 rounded-2xl bg-black/20 border border-white/5 text-left space-y-2">
                    <label class="block text-xs font-bold text-[#A9C2B9]">Uji Coba Kirim Pesan:</label>
                    <p class="text-[11px] text-white/50">Kirimkan pesan uji coba ke nomor tujuan.</p>
                    <label class="block text-xs text-[#A9C2B9] mt-2">Nomor WhatsApp</label>
                    <div class="flex gap-2">
                        <input type="text" id="test-phone" placeholder="Contoh: 08123456789" class="flex-1 bg-white/10 border border-white/20 rounded-xl px-3 py-2 text-xs text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-[#C6F26B]">
                        <button onclick="sendTest()" class="px-4 py-2 rounded-xl bg-[#C6F26B] text-[#0B2B25] font-extrabold text-xs hover:bg-[#b8e65e] transition-colors cursor-pointer">Kirim</button>
                    </div>
                    <label class="block text-xs text-[#A9C2B9] mt-2">Isi Pesan</label>
                    <textarea id="test-msg" rows="2" placeholder="Pesan uji coba..." class="w-full bg-white/10 border border-white/20 rounded-xl px-3 py-2 text-xs text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-[#C6F26B]">Halo! Ini pesan uji coba dari VokaTrack Gateway.</textarea>
                    <p id="test-result" class="text-[11px]"></p>
                </div>
                <form action="/logout" method="POST" onsubmit="return confirm('Yakin ingin logout?')">
                    <button type="submit" class="text-xs text-rose-300 hover:text-rose-200 underline font-semibold cursor-pointer">Ganti Nomor (Logout)</button>
                </form>
            ` : `
                <div class="py-10 space-y-4" id="qr-wrapper">
                    <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center mx-auto">
                        <svg class="w-8 h-8 text-[#C6F26B] animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    </div>
                    <h2 class="text-base font-bold text-white">Engine Aktif — Menyiapkan QR Code...</h2>
                    <p class="text-xs text-[#A9C2B9]">Sedang menghubungkan ke server WhatsApp dan mengambil QR code baru.</p>
                    <p class="text-[11px] text-white/50">Mohon tunggu beberapa saat. Halaman ini akan otomatis menampilkan QR Code begitu server WhatsApp siap.</p>
                </div>
            `}
        </div>

        <!-- Footer -->
        <div class="pt-4 border-t border-white/10 space-y-1">
            <div class="flex justify-between text-[10px] text-white/40 font-mono">
                <span>● Engine: whatsapp-web.js</span>
                <span>Provider: CUSTOM</span>
            </div>
            <div class="flex justify-between text-[10px] text-white/30">
                <span>Cara penggunaan:</span>
            </div>
            <ol class="text-[10px] text-white/30 list-decimal list-inside space-y-0.5">
                <li>Siswa aktifkan SOS → notifikasi dikirim otomatis via gateway ini</li>
                <li>Admin menerima pesan WhatsApp dari nomor terdaftar</li>
            </ol>
        </div>
    </div>

    <script>
        const initStatus = '${connectionStatus}';

        async function pollQR() {
            try {
                const res  = await fetch('/qr-data');
                const data = await res.json();

                if (data.status === 'connected' && initStatus !== 'connected') {
                    location.reload(); return;
                }

                const wrapper = document.getElementById('qr-wrapper');
                if (!wrapper) return;

                if (data.status === 'waiting_scan' && data.qr) {
                    const img = document.getElementById('qr-img');
                    if (img) {
                        img.src = data.qr;
                    } else {
                        wrapper.innerHTML = \`
                            <p class="text-xs text-[#A9C2B9]">Scan QR Code ini dengan WhatsApp:<br>
                                <span class="text-white font-semibold">WhatsApp › Perangkat Tertaut › Tautkan Perangkat</span></p>
                            <div class="inline-block p-3 bg-white rounded-2xl shadow-xl mt-2">
                                <img id="qr-img" src="\${data.qr}" alt="QR Code" class="w-64 h-64 mx-auto">
                            </div>
                            <p class="text-[11px] text-white/50">QR diperbarui otomatis tiap 5 detik. Halaman akan menyambung setelah berhasil di-scan.</p>
                        \`;
                    }
                    document.getElementById('badge').textContent = 'Scan QR';
                } else if (data.status === 'connecting') {
                    document.getElementById('badge').textContent = 'Menghubungkan...';
                }
            } catch (e) {}
        }

        if (initStatus !== 'connected') {
            pollQR();
            setInterval(pollQR, 5000);
        }

        async function sendTest() {
            const phone  = document.getElementById('test-phone').value;
            const msg    = document.getElementById('test-msg')?.value || 'Halo! Ini pesan uji coba dari VokaTrack Gateway.';
            const resEl  = document.getElementById('test-result');
            if (!phone) return alert('Masukkan nomor tujuan!');
            resEl.textContent = 'Mengirim...';
            resEl.className = 'text-[11px] text-amber-300';
            try {
                const r    = await fetch('/send-message', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ${API_KEY}' },
                    body: JSON.stringify({ phone, message: msg }),
                });
                const data = await r.json();
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

// ─── Error Handlers ───────────────────────────────────────────────────────────
process.on('uncaughtException',  (err) => console.error('[VokaTrack WA] Uncaught Exception:', err.message));
process.on('unhandledRejection', (r)   => console.error('[VokaTrack WA] Unhandled Rejection:', r));

// ─── Start Server ─────────────────────────────────────────────────────────────
const server = app.listen(PORT, '0.0.0.0', () => {
    console.log(`[VokaTrack WA Gateway] Web UI aktif di http://localhost:${PORT}`);
});
server.on('error', (err) => {
    console.error(`[VokaTrack WA] Server error port ${PORT}:`, err.message);
});
