@extends('layouts.app')

@section('page_title', 'WhatsApp Gateway Mandiri')
@section('page_subtitle', 'Pusat integrasi bot WhatsApp mandiri untuk notifikasi darurat SOS dan transaksional VokaTrack')

@section('content')
<div class="w-full space-y-6 sm:space-y-8 animate-in fade-in duration-300">

    <!-- Hero Header -->
    <x-page-header 
        title="WhatsApp Gateway & Bot SOS" 
        subtitle="Kelola koneksi engine WhatsApp mandiri (self-hosted). Bot ini bertindak sebagai pengirim otomatis peringatan darurat SOS siswa ke guru pembimbing."
        badge="WHATSAPP ENGINE (SELF-HOSTED)"
    >
        <div class="flex items-center gap-2">
            <span id="header-status-badge" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold font-mono {{ $connectionStatus === 'connected' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($connectionStatus === 'waiting_scan' ? 'bg-amber-50 text-amber-800 border border-amber-200 animate-pulse' : 'bg-rose-50 text-rose-700 border border-rose-200') }}">
                <span class="w-2 h-2 rounded-full {{ $connectionStatus === 'connected' ? 'bg-emerald-500' : ($connectionStatus === 'waiting_scan' ? 'bg-amber-500' : 'bg-rose-500') }}"></span>
                <span id="header-status-text">
                    {{ $connectionStatus === 'connected' ? 'TERHUBUNG' : ($connectionStatus === 'waiting_scan' ? 'SCAN QR CODE' : 'OFFLINE') }}
                </span>
            </span>
        </div>
    </x-page-header>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Kolom Kiri: Live QR Code & Status Perangkat (7 Cols) -->
        <div class="lg:col-span-7 space-y-6">
            <x-card>
                <div class="pb-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Koneksi Perangkat WhatsApp</h3>
                        <p class="text-xs text-slate-500 font-medium">Scan QR Code dengan WhatsApp di HP Administrator/Sekolah.</p>
                    </div>
                    <span class="text-[11px] font-bold font-mono text-slate-400">Port: 3000</span>
                </div>

                <div class="py-4">
                    <div id="status-card-connected" class="{{ $connectionStatus === 'connected' ? '' : 'hidden' }} p-6 rounded-2xl bg-emerald-50/70 border border-emerald-200 text-center space-y-4">
                        <div class="w-16 h-16 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto shadow-sm">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <h4 class="text-base font-extrabold text-emerald-950">WhatsApp Aktif & Terhubung</h4>
                            <p class="text-xs text-emerald-800 mt-1">
                                Nomor Pengirim Bot: 
                                <span id="connected-phone-display" class="font-mono font-bold text-emerald-950 text-sm">
                                    +{{ $connectedUser['id'] ?? '-' }}
                                </span>
                            </p>
                        </div>
                        <p class="text-xs text-emerald-800/80 max-w-md mx-auto">
                            Engine WhatsApp Gateway mandiri siap mengirimkan notifikasi darurat SOS, permohonan reset sandi, dan notifikasi sistem VokaTrack secara real-time.
                        </p>
                        <div class="pt-2">
                            <form action="{{ route('superadmin.whatsapp.logout') }}" method="POST" onsubmit="return confirm('Putus sesi WhatsApp ini dan buat QR Code baru?')">
                                @csrf
                                <button type="submit" class="px-3.5 py-1.5 rounded-xl border border-rose-200 bg-white hover:bg-rose-50 text-rose-700 font-bold text-xs transition-colors cursor-pointer inline-flex items-center gap-1.5 shadow-2xs">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    <span>Ganti Nomor / Putus Sesi</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div id="status-card-qr" class="{{ $connectionStatus === 'waiting_scan' ? '' : 'hidden' }} p-6 rounded-2xl bg-amber-50/70 border border-amber-200 text-center space-y-4">
                        <div class="space-y-1">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-amber-200 text-amber-950">
                                Scan QR Code Sekarang
                            </span>
                            <h4 class="text-sm sm:text-base font-extrabold text-amber-950">Buka WhatsApp di Ponsel Pengirim</h4>
                            <p class="text-xs text-amber-900/80 max-w-sm mx-auto">
                                Masuk ke menu <strong>Perangkat Tertaut (Linked Devices) &gt; Tautkan Perangkat</strong> di aplikasi WhatsApp Anda, lalu arahkan kamera ke QR code di bawah:
                            </p>
                        </div>

                        <div class="inline-block p-4 bg-white rounded-2xl border border-amber-200 shadow-md">
                            <img id="qr-image" src="{{ $qrCodeDataUrl ?? '' }}" alt="WhatsApp QR Code" class="w-64 h-64 object-contain mx-auto">
                        </div>

                        <div class="flex items-center justify-center gap-2 text-[11px] text-amber-800 font-medium">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                            <span>Menunggu pemindaian... Halaman ini akan otomatis terhubung begitu di-scan.</span>
                        </div>
                    </div>

                    <div id="status-card-offline" class="{{ (!$isServiceRunning || $connectionStatus === 'disconnected') ? '' : 'hidden' }} p-6 rounded-2xl bg-rose-50/70 border border-rose-200 text-center space-y-4">
                        <div class="w-16 h-16 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center mx-auto shadow-sm">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <h4 class="text-base font-extrabold text-rose-950">Engine WhatsApp Belum Aktif</h4>
                            <p class="text-xs text-rose-800 mt-1">Microservice Node.js di server belum berjalan pada port 3000.</p>
                        </div>
                        <div class="p-3 bg-white/80 rounded-xl border border-rose-200 text-left font-mono text-xs text-slate-800 space-y-1">
                            <p class="text-[10px] uppercase font-bold text-slate-400">Jalankan di terminal server:</p>
                            <p class="text-indigo-600 font-bold select-all">cd services/whatsapp-gateway && npm start</p>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        Engine: <strong>@whiskeysockets/baileys</strong>
                    </span>
                    <span class="font-mono text-[11px]">Provider: {{ strtoupper($provider) }}</span>
                </div>
            </x-card>
        </div>

        <!-- Kolom Kanan: Uji Coba Pengiriman & Petunjuk (5 Cols) -->
        <div class="lg:col-span-5 space-y-6">
            
            <!-- Card Uji Coba Kirim -->
            <x-card>
                <div class="pb-3 border-b border-slate-100">
                    <h3 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">Uji Coba Kirim Pesan Langsung</h3>
                    <p class="text-xs text-slate-500 font-medium">Kirimkan pesan WhatsApp simulasi untuk memverifikasi koneksi.</p>
                </div>

                <form action="{{ route('superadmin.whatsapp.test') }}" method="POST" class="mt-4 space-y-3.5">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nomor WhatsApp Tujuan</label>
                        <input type="text" name="phone" placeholder="Contoh: 081234567890" required
                               class="w-full text-xs font-mono px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-slate-800 font-semibold">
                        <span class="text-[10px] text-slate-400 mt-1 block">Format otomatis diubah ke standar 628xxxxxxxxxx.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Isi Pesan Uji Coba (Opsional)</label>
                        <textarea name="message" rows="3" placeholder="Pesan tes otomatis VokaTrack..."
                                  class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-slate-800"></textarea>
                    </div>

                    <button type="submit" class="w-full py-2.5 rounded-xl bg-[#0B2B25] hover:bg-[#12382F] text-[#C6F26B] font-extrabold text-xs transition-colors shadow-md shadow-[#0B2B25]/20 cursor-pointer flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        <span>Kirim Pesan WhatsApp Sekarang</span>
                    </button>
                </form>
            </x-card>

            <!-- Card Informasi & Panduan -->
            <x-card>
                <div class="pb-3 border-b border-slate-100 flex items-center gap-2 text-indigo-700 font-extrabold text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Cara Kerja Notifikasi SOS VokaTrack</span>
                </div>
                <div class="mt-3 space-y-2.5 text-xs text-slate-600 leading-relaxed font-medium">
                    <p>
                        <strong>1. Siswa Lapor Darurat:</strong> Saat siswa menekan tombol SOS di DU/DI, VokaTrack memicu notifikasi darurat.
                    </p>
                    <p>
                        <strong>2. Pengiriman Instan:</strong> Engine gateway ini langsung mengirimkan pesan detail insiden ke nomor WhatsApp Guru Pembimbing.
                    </p>
                    <p>
                        <strong>3. Bebas Biaya Pihak Ketiga:</strong> Sistem ini 100% berjalan di server Anda sendiri tanpa biaya langganan bulanan Fonnte/Wablas.
                    </p>
                </div>
            </x-card>

        </div>

    </div>

</div>

@push('scripts')
<script>
    // Live Auto-Polling Status & QR Code (Real-time tanpa refresh halaman)
    let isConnected = {{ $connectionStatus === 'connected' ? 'true' : 'false' }};

    function checkLiveStatus() {
        fetch('{{ route("superadmin.whatsapp.status") }}')
            .then(res => res.json())
            .then(data => {
                const headerBadge = document.getElementById('header-status-badge');
                const headerText = document.getElementById('header-status-text');
                const cardConnected = document.getElementById('status-card-connected');
                const cardQr = document.getElementById('status-card-qr');
                const cardOffline = document.getElementById('status-card-offline');
                const qrImage = document.getElementById('qr-image');
                const phoneDisplay = document.getElementById('connected-phone-display');

                if (!data.running || data.status === 'offline' || data.status === 'disconnected') {
                    headerBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold font-mono bg-rose-50 text-rose-700 border border-rose-200';
                    headerText.textContent = 'OFFLINE';
                    cardConnected.classList.add('hidden');
                    cardQr.classList.add('hidden');
                    cardOffline.classList.remove('hidden');
                } else if (data.status === 'connected') {
                    headerBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold font-mono bg-emerald-50 text-emerald-700 border border-emerald-200';
                    headerText.textContent = 'TERHUBUNG';
                    cardConnected.classList.remove('hidden');
                    cardQr.classList.add('hidden');
                    cardOffline.classList.add('hidden');
                    if (data.user && data.user.id) {
                        phoneDisplay.textContent = '+' + data.user.id;
                    }
                } else if (data.status === 'waiting_scan') {
                    headerBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold font-mono bg-amber-50 text-amber-800 border border-amber-200 animate-pulse';
                    headerText.textContent = 'SCAN QR CODE';
                    cardConnected.classList.add('hidden');
                    cardQr.classList.remove('hidden');
                    cardOffline.classList.add('hidden');
                    if (data.qr && qrImage) {
                        qrImage.src = data.qr;
                    }
                }
            })
            .catch(() => {});
    }

    // Polling setiap 3 detik
    setInterval(checkLiveStatus, 3000);
</script>
@endpush
@endsection
