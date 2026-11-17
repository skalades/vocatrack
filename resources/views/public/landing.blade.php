<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0B2B25">
    <title>{{ $systemName ?? 'VokaTrack' }} — Platform Monitoring PKL untuk SMK</title>
    <meta name="description" content="Presensi selfie berbasis lokasi, jurnal harian dua tingkat, penilaian, dan e-sertifikat PKL dalam satu platform untuk SMK, guru pembimbing, DU/DI, dan orang tua.">
    <link rel="icon" type="image/png" href="{{ $systemLogo ?? asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
        .font-space { font-family: 'Space Grotesk', system-ui, sans-serif; letter-spacing: -0.025em; }
        .forest {
            background-color: #0B2B25;
            background-image:
                radial-gradient(ellipse at 10% 0%, rgba(198, 242, 107, 0.16) 0, transparent 50%),
                radial-gradient(ellipse at 100% 100%, rgba(46, 107, 79, 0.6) 0, transparent 55%),
                linear-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.035) 1px, transparent 1px);
            background-size: auto, auto, 32px 32px, 32px 32px;
        }
        .ring-pulse { position: absolute; inset: 0; border-radius: 9999px; border: 2px solid rgba(198, 242, 107, 0.5); animation: lpPulse 3.2s cubic-bezier(.2,.6,.3,1) infinite; }
        .ring-pulse.delay { animation-delay: 1.6s; }
        @keyframes lpPulse { 0% { transform: scale(.3); opacity: .9 } 100% { transform: scale(1.15); opacity: 0 } }
        .js .reveal { opacity: 0; transform: translateY(18px); transition: opacity .6s cubic-bezier(.16,1,.3,1), transform .7s cubic-bezier(.16,1,.3,1); }
        .js .reveal.in { opacity: 1; transform: none; }
        .reveal-stagger > * { transition-delay: calc(var(--i, 0) * 60ms); }
        .js .reveal-stagger > * { opacity: 0; transform: translateY(14px); transition: opacity .6s cubic-bezier(.16,1,.3,1), transform .7s cubic-bezier(.16,1,.3,1); transition-delay: calc(var(--i, 0) * 60ms); }
        .js .reveal-stagger.in > * { opacity: 1; transform: none; }

        /* ---------- Hero: animasi masuk ---------- */
        .hero-in { animation: lpUp .9s cubic-bezier(.16,1,.3,1) both; animation-delay: var(--d, 0ms); }
        @keyframes lpUp { from { opacity: 0; transform: translateY(22px); } to { opacity: 1; transform: none; } }
        .hero-word { display: inline-block; animation: lpWord .9s cubic-bezier(.16,1,.3,1) both; animation-delay: var(--d, 0ms); }
        @keyframes lpWord { from { opacity: 0; transform: translateY(.45em); filter: blur(6px); } to { opacity: 1; transform: none; filter: none; } }
        .hero-highlight { background: linear-gradient(transparent 62%, rgba(198,242,107,.75) 62%, rgba(198,242,107,.75) 92%, transparent 92%) no-repeat 0 0 / 0% 100%; animation: lpMark .9s cubic-bezier(.65,0,.35,1) 1.1s forwards; }
        @keyframes lpMark { to { background-size: 100% 100%; } }
        .dot-ping { position: relative; }
        .dot-ping::after { content: ''; position: absolute; inset: 0; border-radius: 9999px; background: inherit; animation: lpPing 1.8s cubic-bezier(0,0,.2,1) infinite; }
        @keyframes lpPing { 75%, 100% { transform: scale(2.6); opacity: 0; } }
        .btn-shine { position: relative; overflow: hidden; }
        .btn-shine::after { content: ''; position: absolute; top: 0; bottom: 0; left: -60%; width: 40%; background: linear-gradient(100deg, transparent, rgba(198,242,107,.35), transparent); transform: skewX(-20deg); animation: lpShine 4.5s ease-in-out 2s infinite; }
        @keyframes lpShine { 0%, 70% { left: -60%; } 100% { left: 130%; } }

        /* ---------- Mockup ponsel ---------- */
        .phone-in { animation: lpPhoneIn 1.2s cubic-bezier(.16,1,.3,1) .25s both; }
        @keyframes lpPhoneIn { from { opacity: 0; transform: translateY(60px) rotate(6deg) scale(.94); } to { opacity: 1; transform: none; } }
        .phone-float { animation: lpFloat 6s ease-in-out 1.5s infinite; }
        @keyframes lpFloat { 0%, 100% { transform: translateY(0) rotate(0); } 50% { transform: translateY(-12px) rotate(-.6deg); } }
        .blob-drift { animation: lpBlob 9s ease-in-out infinite alternate; }
        @keyframes lpBlob { from { transform: translate(0, 0) scale(1); } to { transform: translate(-40px, 30px) scale(1.15); } }
        .float-chip { animation: lpChip 5s ease-in-out infinite; animation-delay: var(--d, 0s); }
        @keyframes lpChip { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }

        .mk-pop { animation: lpPop .7s cubic-bezier(.34,1.56,.64,1) both; animation-delay: var(--d, 0ms); }
        @keyframes lpPop { from { opacity: 0; transform: translateY(16px) scale(.96); } to { opacity: 1; transform: none; } }
        .mk-blink { animation: lpBlink 1s steps(1) infinite; }
        @keyframes lpBlink { 50% { opacity: .15; } }
        .mk-fence { animation: lpDash 1.6s linear infinite; }
        @keyframes lpDash { to { stroke-dashoffset: -26; } }
        .mk-radar, .mk-halo { transform-box: fill-box; transform-origin: center; }
        .mk-radar { animation: lpRadar 3s cubic-bezier(.2,.6,.3,1) infinite; }
        .mk-radar.delay { animation-delay: 1.5s; }
        @keyframes lpRadar { from { transform: scale(.15); opacity: .9; } to { transform: scale(1); opacity: 0; } }
        .mk-halo { animation: lpHalo 2s ease-out infinite; }
        @keyframes lpHalo { from { transform: scale(1); opacity: .55; } to { transform: scale(3.2); opacity: 0; } }
        .mk-ring { animation: lpRing 1.6s cubic-bezier(.65,0,.35,1) 1.4s both; }
        @keyframes lpRing { from { stroke-dashoffset: 138; } to { stroke-dashoffset: 11; } }
        .mk-bar { animation: lpBar 1.6s cubic-bezier(.65,0,.35,1) 1.5s both; }
        @keyframes lpBar { from { width: 0; } to { width: 90%; } }
        .mk-line { transform-origin: left; animation: lpLine .8s cubic-bezier(.65,0,.35,1) 1.2s both; }
        @keyframes lpLine { from { transform: scaleX(0); } to { transform: scaleX(1); } }
        .mk-nudge { animation: lpNudge 2.2s ease-in-out 2s infinite; }
        @keyframes lpNudge { 0%, 60%, 100% { transform: translateX(0); } 30% { transform: translateX(5px); } }
        .mk-sos { animation: lpSos 2s ease-out infinite; }
        @keyframes lpSos { 0% { box-shadow: 0 0 0 0 rgba(200,38,26,.55); } 70%, 100% { box-shadow: 0 0 0 14px rgba(200,38,26,0); } }
        .mk-bell { transform-origin: 50% 10%; animation: lpBell 4s ease-in-out 2.5s infinite; }
        @keyframes lpBell { 0%, 8%, 100% { transform: rotate(0); } 2% { transform: rotate(14deg); } 4% { transform: rotate(-12deg); } 6% { transform: rotate(6deg); } }

        /* ---------- Kartu ---------- */
        .card-lift, .js .reveal.card-lift, .js .reveal-stagger > .card-lift { transition: opacity .6s cubic-bezier(.16,1,.3,1), transform .6s cubic-bezier(.16,1,.3,1), box-shadow .35s, border-color .2s; }
        .card-lift:hover, .js .reveal.in.card-lift:hover, .js .reveal-stagger.in > .card-lift:hover { transform: translateY(-4px); box-shadow: 0 18px 40px -18px rgba(11,43,37,.35); transition-delay: 0s !important; }
        .card-lift .card-icon { transition: transform .45s cubic-bezier(.34,1.56,.64,1); }
        .card-lift:hover .card-icon { transform: rotate(-8deg) scale(1.1); }

        @media (prefers-reduced-motion: reduce) {
            .ring-pulse { animation: none; }
            .js .reveal, .js .reveal-stagger > * { opacity: 1; transform: none; transition: none; }
            .hero-in, .hero-word, .phone-in, .phone-float, .blob-drift, .float-chip, .mk-pop, .mk-blink, .mk-fence,
            .mk-radar, .mk-halo, .mk-ring, .mk-bar, .mk-line, .mk-nudge, .mk-sos, .mk-bell, .dot-ping::after, .btn-shine::after { animation: none !important; }
            .mk-radar, .mk-halo { opacity: 0; }
            .hero-highlight { animation: none; background-size: 100% 100%; }
            .card-lift, .card-lift:hover, .card-lift:hover .card-icon { transform: none; }
        }
    </style>
    <script>document.documentElement.classList.add('js');</script>
</head>
<body class="bg-[#F1F4EC] text-[#0B2B25] antialiased">

{{-- ================= NAV ================= --}}
<header class="sticky top-0 z-40 bg-[#F1F4EC]/85 backdrop-blur-md border-b border-[#0B2B25]/10">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
        <a href="{{ route('landing') }}" class="flex items-center gap-2.5 min-w-0">
            <img src="{{ $systemLogo ?? asset('images/logo.png') }}" alt="" class="w-9 h-9 rounded-xl object-contain bg-white border border-[#0B2B25]/10">
            <span class="font-space text-lg font-bold truncate">{{ $systemName ?? 'VokaTrack' }}</span>
        </a>
        <nav class="hidden md:flex items-center gap-7 text-sm font-semibold text-[#0B2B25]/70">
            <a href="#fitur" class="hover:text-[#0B2B25]">Fitur</a>
            <a href="#cara-kerja" class="hover:text-[#0B2B25]">Cara Kerja</a>
            @if($showPricing)
                <a href="#harga" class="hover:text-[#0B2B25]">Harga</a>
            @endif
            <a href="#faq" class="hover:text-[#0B2B25]">FAQ</a>
        </nav>
        <div class="flex items-center gap-2">
            <a href="{{ route('login') }}" class="px-3.5 py-2 rounded-xl text-sm font-bold text-[#0B2B25] hover:bg-[#0B2B25]/5">Masuk</a>
            <a href="#demo" class="hidden sm:inline-flex px-4 py-2 rounded-xl text-sm font-extrabold bg-[#0B2B25] text-[#C6F26B] hover:bg-[#123d34]">Ajukan Demo</a>
        </div>
    </div>
</header>

<main>
    {{-- ================= HERO ================= --}}
    <section class="max-w-6xl mx-auto px-4 sm:px-6 pt-12 sm:pt-20 pb-16 grid lg:grid-cols-2 gap-12 items-center">
        <div class="space-y-6">
            <span class="hero-in inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white border border-[#0B2B25]/10 text-xs font-bold text-[#2E6B4F]">
                <span class="dot-ping w-1.5 h-1.5 rounded-full bg-[#7BB33A]"></span> Untuk SMK, guru pembimbing, dan DU/DI
            </span>
            <h1 class="font-space text-4xl sm:text-5xl lg:text-[3.4rem] font-bold leading-[1.05]">
                @foreach(explode(' ', 'PKL siswa terpantau rapi, dari hari pertama sampai sertifikat.') as $w => $word)
                    <span class="hero-word {{ $word === 'rapi,' ? 'hero-highlight' : '' }}" style="--d: {{ 120 + $w * 55 }}ms">{{ $word }}</span>
                @endforeach
            </h1>
            <p class="hero-in text-base sm:text-lg text-[#0B2B25]/70 leading-relaxed max-w-xl" style="--d: 650ms">
                Siswa presensi dengan selfie dan lokasi, menulis jurnal harian, lalu pembimbing industri dan guru memverifikasinya. Sekolah melihat semuanya dari satu dasbor, tanpa rekap manual.
            </p>
            <div class="hero-in flex flex-col sm:flex-row gap-3" style="--d: 780ms">
                <a href="#demo" class="btn-shine inline-flex justify-center items-center px-6 py-3.5 rounded-2xl text-sm font-extrabold bg-[#0B2B25] text-[#C6F26B] hover:bg-[#123d34] shadow-lg shadow-[#0B2B25]/20">
                    Coba gratis {{ \App\Services\SubscriptionService::TRIAL_DAYS }} hari
                </a>
                <a href="{{ $showPricing ? '#harga' : '#fitur' }}" class="inline-flex justify-center items-center px-6 py-3.5 rounded-2xl text-sm font-bold bg-white border border-[#0B2B25]/15 hover:border-[#0B2B25]/30">
                    {{ $showPricing ? 'Lihat paket harga' : 'Lihat fitur' }}
                </a>
            </div>
            <p class="hero-in text-xs text-[#0B2B25]/55" style="--d: 900ms">Tanpa kartu kredit. Akun admin sekolah dibuat oleh tim kami setelah permohonan disetujui.</p>
        </div>

        {{-- Mockup ponsel siswa --}}
        <div class="relative flex justify-center lg:justify-end" aria-hidden="true">
            <div class="blob-drift absolute -top-6 right-6 sm:right-16 w-64 h-64 rounded-full bg-[#C6F26B]/30 blur-3xl"></div>
            <div class="phone-in relative">
                <div class="phone-float">
                    @include('public.partials.student-phone-mockup')
                </div>
                {{-- Chip mengambang di sekitar ponsel --}}
                <div class="hidden sm:block absolute -left-16 top-44 hero-in" style="--d: 1500ms">
                    <div class="float-chip flex items-center gap-2 rounded-2xl bg-white/95 border border-[#0B2B25]/10 shadow-xl shadow-[#0B2B25]/10 px-3 py-2">
                        <span class="w-7 h-7 rounded-full bg-[#C6F26B] text-[#0B2B25] flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <span class="text-[11px] leading-tight"><span class="block font-extrabold">Presensi tercatat</span><span class="text-[#0B2B25]/55">07.12 · 38 m dari lokasi</span></span>
                    </div>
                </div>
                <div class="hidden sm:block absolute -right-8 bottom-40 hero-in" style="--d: 1800ms">
                    <div class="float-chip flex items-center gap-2 rounded-2xl bg-[#0B2B25] text-white shadow-xl shadow-[#0B2B25]/25 px-3 py-2" style="--d: 1.2s">
                        <span class="w-7 h-7 rounded-full bg-white/10 text-[#C6F26B] flex items-center justify-center">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        </span>
                        <span class="text-[11px] leading-tight"><span class="block font-extrabold">Jurnal disetujui</span><span class="text-[#A9C2B9]">oleh pembimbing DU/DI</span></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= PERAN ================= --}}
    <section class="border-y border-[#0B2B25]/10 bg-white/60">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
            <p class="text-center text-xs font-bold uppercase tracking-widest text-[#0B2B25]/50 mb-5">Satu platform, setiap pihak punya tampilan sendiri</p>
            <ul class="reveal-stagger grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-3 text-center">
                @foreach([
                    'Siswa PKL' => 'Presensi & jurnal dari ponsel',
                    'Guru Pembimbing' => 'Verifikasi & supervisi',
                    'Pembimbing DU/DI' => 'Setujui jurnal & menilai',
                    'Kepala Program Keahlian' => 'Monitoring & kelola jurusan',
                    'Admin Sekolah' => 'Plotting & master data',
                    'Kepala Sekolah' => 'Laporan eksekutif',
                    'Orang Tua' => 'Pantau lewat tautan aman',
                ] as $role => $desc)
                    <li class="card-lift rounded-2xl bg-white border border-[#0B2B25]/10 px-2.5 sm:px-3 py-4 flex flex-col justify-center {{ $loop->last ? 'col-span-2 sm:col-span-1' : '' }}" style="--i: {{ $loop->index }}">
                        <div class="text-sm font-extrabold leading-snug">{{ $role }}</div>
                        <div class="text-[11px] text-[#0B2B25]/55 mt-1 leading-tight">{{ $desc }}</div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ================= FITUR ================= --}}
    <section id="fitur" class="max-w-6xl mx-auto px-4 sm:px-6 py-20 scroll-mt-16">
        <div class="max-w-2xl reveal">
            <h2 class="font-space text-3xl sm:text-4xl font-bold">Semua kebutuhan PKL, tanpa spreadsheet.</h2>
            <p class="mt-3 text-[#0B2B25]/65">Dirancang dari alur PKL SMK yang sebenarnya: siswa di lapangan, pembimbing di industri, guru di sekolah.</p>
        </div>
        @php
            $features = [
                ['Presensi selfie + geofence', 'Check-in hanya bisa dari radius lokasi DU/DI. Foto dan jarak GPS tersimpan sebagai bukti.', 'M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z'],
                ['Jurnal harian dua tingkat', 'Siswa menulis kegiatan dengan foto, pembimbing DU/DI memverifikasi, lalu guru menyetujui.', 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
                ['WhatsApp Gateway & SOS', 'Pengajuan izin dengan lampiran, plus tombol darurat SOS terintegrasi WhatsApp Gateway otomatis ke nomor HP guru pembimbing.', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
                ['Penilaian & e-sertifikat', 'Bobot nilai DU/DI dan guru dihitung otomatis. Sertifikat PDF ber-QR dapat diverifikasi publik.', 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'],
                ['Peta sebaran DU/DI', 'Lihat posisi seluruh mitra industri dan status kehadiran siswa di peta interaktif.', 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7'],
                ['Portal orang tua', 'Orang tua memantau kehadiran dan jurnal anak lewat tautan aman, tanpa perlu membuat akun.', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
            ];
        @endphp
        <div class="mt-12 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($features as $i => [$title, $desc, $icon])
                <article class="reveal rounded-3xl bg-white border border-[#0B2B25]/10 p-6 hover:border-[#0B2B25]/25 card-lift" style="transition-delay: {{ ($i % 3) * 70 }}ms">
                    <div class="card-icon w-11 h-11 rounded-2xl bg-[#0B2B25] text-[#C6F26B] flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/></svg>
                    </div>
                    <h3 class="mt-4 text-base font-extrabold">{{ $title }}</h3>
                    <p class="mt-1.5 text-sm text-[#0B2B25]/65 leading-relaxed">{{ $desc }}</p>
                </article>
            @endforeach
        </div>
    </section>

    {{-- ================= CARA KERJA ================= --}}
    <section id="cara-kerja" class="forest text-white scroll-mt-16">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-20">
            <div class="max-w-2xl reveal">
                <h2 class="font-space text-3xl sm:text-4xl font-bold">Mulai dalam tiga langkah.</h2>
                <p class="mt-3 text-[#A9C2B9]">Tidak perlu instalasi server. Data tiap sekolah terisolasi dan dicadangkan rutin.</p>
            </div>
            <ol class="mt-12 grid md:grid-cols-3 gap-4">
                @foreach([
                    ['Ajukan demo', 'Isi formulir singkat di bawah. Tim kami memverifikasi NPSN dan menghubungi Anda.'],
                    ['Trial ' . \App\Services\SubscriptionService::TRIAL_DAYS . ' hari', 'Akun admin sekolah langsung aktif. Impor siswa, guru, dan mitra DU/DI, lalu jalankan PKL sungguhan.'],
                    ['Pilih paket', 'Lanjutkan dengan paket sesuai jumlah siswa. Tagihan dan perpanjangan tercatat rapi di portal sekolah.'],
                ] as $i => [$title, $desc])
                    <li class="reveal rounded-3xl bg-white/5 border border-white/10 p-6" style="transition-delay: {{ $i * 80 }}ms">
                        <div class="font-space text-4xl font-bold text-[#C6F26B]">0{{ $i + 1 }}</div>
                        <h3 class="mt-3 text-lg font-extrabold">{{ $title }}</h3>
                        <p class="mt-1.5 text-sm text-[#A9C2B9] leading-relaxed">{{ $desc }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ================= HARGA (disembunyikan via LandingController::SHOW_PRICING) ================= --}}
    @if($showPricing)
    <section id="harga" class="max-w-6xl mx-auto px-4 sm:px-6 py-20 scroll-mt-16">
        <div class="max-w-2xl reveal">
            <h2 class="font-space text-3xl sm:text-4xl font-bold">Harga sesuai jumlah siswa.</h2>
            <p class="mt-3 text-[#0B2B25]/65">Semua paket mencakup presensi, jurnal, penilaian, portal orang tua, dan dukungan teknis.</p>
        </div>

        @if($plans->isEmpty())
            <p class="mt-10 text-sm text-[#0B2B25]/60">Paket sedang diperbarui. Hubungi kami lewat formulir demo untuk penawaran.</p>
        @else
            @php
                $planGrid = match (true) {
                    $plans->count() >= 4 => 'lg:grid-cols-4',
                    $plans->count() === 3 => 'lg:grid-cols-3',
                    default => 'lg:grid-cols-2',
                };
            @endphp
            <div class="mt-12 grid sm:grid-cols-2 {{ $planGrid }} gap-4 items-stretch">
                @foreach($plans as $plan)
                    @php
                        $popular = (bool) $plan->is_popular;
                        $isFree = (float) $plan->price <= 0;
                        $features = $plan->features ?? [];
                    @endphp
                    <article class="reveal relative flex flex-col rounded-3xl p-6 border {{ $popular ? 'bg-[#0B2B25] text-white border-[#0B2B25] shadow-xl shadow-[#0B2B25]/20' : 'bg-white border-[#0B2B25]/10' }}">
                        @if($popular)
                            <span class="absolute -top-3 left-6 px-3 py-1 rounded-full bg-[#C6F26B] text-[#0B2B25] text-[11px] font-extrabold">Paling dipilih</span>
                        @endif
                        <h3 class="text-base font-extrabold">{{ $plan->name }}</h3>
                        @if($plan->description)
                            <p class="mt-1 text-xs {{ $popular ? 'text-[#A9C2B9]' : 'text-[#0B2B25]/55' }}">{{ $plan->description }}</p>
                        @endif
                        <div class="mt-5">
                            <div class="font-space text-2xl xl:text-3xl font-bold whitespace-nowrap">{{ $isFree ? 'Gratis' : 'Rp ' . number_format($plan->price, 0, ',', '.') }}</div>
                            <div class="text-xs mt-0.5 {{ $popular ? 'text-[#A9C2B9]' : 'text-[#0B2B25]/55' }}">{{ $isFree ? 'selama ' . \App\Services\SubscriptionService::TRIAL_DAYS . ' hari' : ltrim($plan->period_label, '/ ') }}</div>
                        </div>
                        <ul class="mt-5 space-y-2.5 text-sm flex-1">
                            @foreach(array_filter([
                                'Hingga ' . number_format($plan->max_students) . ' siswa',
                                'Storage media ' . ($plan->max_storage_mb >= 1024 ? round($plan->max_storage_mb / 1024, 1) . ' GB' : $plan->max_storage_mb . ' MB'),
                                'Presensi geofence & jurnal dua tingkat',
                                'Penilaian & portal orang tua',
                                !empty($features['certificate']) ? 'E-sertifikat PDF ber-QR' : null,
                            ]) as $line)
                                <li class="flex gap-2">
                                    <svg class="w-4 h-4 mt-0.5 shrink-0 {{ $popular ? 'text-[#C6F26B]' : 'text-[#2E6B4F]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    <span>{{ $line }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <a href="#demo" data-plan="{{ $plan->name }}" class="plan-cta mt-6 inline-flex justify-center px-4 py-3 rounded-2xl text-sm font-extrabold {{ $popular ? 'bg-[#C6F26B] text-[#0B2B25] hover:brightness-95' : 'bg-[#0B2B25] text-[#C6F26B] hover:bg-[#123d34]' }}">
                            {{ $isFree ? 'Mulai trial' : 'Ajukan paket ini' }}
                        </a>
                    </article>
                @endforeach
            </div>
            <p class="mt-6 text-xs text-[#0B2B25]/55">Harga belum termasuk pajak bila berlaku. Sekolah dengan kebutuhan khusus dapat meminta penawaran tersendiri.</p>
        @endif
    </section>
    @endif

    {{-- ================= FAQ ================= --}}
    <section id="faq" class="bg-white/60 border-y border-[#0B2B25]/10 scroll-mt-16">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-20">
            <h2 class="font-space text-3xl sm:text-4xl font-bold reveal">Pertanyaan yang sering diajukan</h2>
            <div class="mt-8 space-y-3">
                @foreach([
                    ['Apakah siswa perlu memasang aplikasi?', 'Tidak. VokaTrack berjalan di browser ponsel. Siswa cukup membuka alamat sekolah dan masuk dengan akunnya.'],
                    ['Bagaimana jika siswa mencoba presensi dari rumah?', 'Check-in ditolak bila posisi GPS berada di luar radius lokasi DU/DI yang ditetapkan sekolah. Foto selfie dan jarak tersimpan sebagai bukti.'],
                    ['Apakah data sekolah kami terpisah dari sekolah lain?', 'Ya. Setiap sekolah adalah tenant terisolasi, berkas foto disimpan privat, dan seluruh aktivitas administratif tercatat di log audit.'],
                    ['Apa yang terjadi setelah masa trial berakhir?', 'Tim kami mengirim tagihan paket pilihan Anda. Setelah dibayar, paket aktif otomatis dan data selama trial tetap utuh.'],
                    ['Bagaimana bila langganan terlambat dibayar?', 'Ada masa tenggang 7 hari setelah masa sewa berakhir. Sekolah tetap bisa dipakai sambil menyelesaikan pembayaran.'],
                ] as [$q, $a])
                    <details class="group rounded-2xl bg-white border border-[#0B2B25]/10 p-5">
                        <summary class="flex items-center justify-between gap-4 cursor-pointer list-none font-bold">
                            {{ $q }}
                            <svg class="w-5 h-5 shrink-0 transition-transform group-open:rotate-45" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </summary>
                        <p class="mt-3 text-sm text-[#0B2B25]/65 leading-relaxed">{{ $a }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= FORMULIR DEMO ================= --}}
    <section id="demo" class="max-w-6xl mx-auto px-4 sm:px-6 py-20 scroll-mt-16">
        <div class="grid lg:grid-cols-5 gap-10 items-start">
            <div class="lg:col-span-2 space-y-4 reveal">
                <h2 class="font-space text-3xl sm:text-4xl font-bold">Ajukan demo untuk sekolah Anda.</h2>
                <p class="text-[#0B2B25]/65">Isi data sekolah dan kontak penanggung jawab PKL. Kami akan menghubungi Anda untuk aktivasi trial {{ \App\Services\SubscriptionService::TRIAL_DAYS }} hari.</p>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-center gap-2"><svg class="w-4 h-4 text-[#2E6B4F] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> <span>Gratis, tanpa kewajiban berlangganan</span></li>
                    <li class="flex items-center gap-2"><svg class="w-4 h-4 text-[#2E6B4F] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> <span>Pendampingan impor data awal</span></li>
                    <li class="flex items-center gap-2"><svg class="w-4 h-4 text-[#2E6B4F] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> <span>Satu NPSN untuk satu permohonan</span></li>
                </ul>
            </div>

            <div class="lg:col-span-3 rounded-3xl bg-white border border-[#0B2B25]/10 p-6 sm:p-8 shadow-sm">
                @if(session('success'))
                    <div class="rounded-2xl bg-[#C6F26B]/40 border border-[#7BB33A]/40 p-5 space-y-4" role="status">
                        <div>
                            <div class="font-extrabold">Permohonan terkirim</div>
                            <p class="text-sm mt-1">{{ session('success') }}</p>
                        </div>
                        @if(session('demo_whatsapp_url'))
                            <a id="demo-whatsapp-link" href="{{ session('demo_whatsapp_url') }}" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl text-sm font-extrabold bg-[#25D366] text-white hover:brightness-95">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 004.74 1.21h.01c5.46 0 9.91-4.45 9.91-9.91A9.85 9.85 0 0012.04 2zm5.8 14.13c-.25.69-1.44 1.32-1.98 1.37-.51.05-.98.24-3.3-.69-2.79-1.1-4.56-3.95-4.7-4.13-.14-.18-1.12-1.49-1.12-2.85 0-1.35.71-2.02.96-2.29.25-.28.55-.34.73-.34l.53.01c.17.01.4-.06.62.48.25.6.83 2.07.9 2.22.08.15.13.32.03.51-.1.2-.15.32-.29.49-.15.18-.31.39-.44.52-.15.15-.3.31-.13.6.17.3.76 1.25 1.63 2.03 1.12 1 2.07 1.31 2.36 1.46.3.15.47.12.64-.07.18-.2.74-.86.94-1.16.2-.3.39-.25.66-.15.27.1 1.72.81 2.02.96.3.15.49.22.56.35.08.12.08.72-.17 1.41z"/></svg>
                                Kirim lewat WhatsApp
                            </a>
                            <p class="text-xs text-[#0B2B25]/60">WhatsApp akan terbuka otomatis. Bila tidak, tekan tombol di atas.</p>
                        @endif
                    </div>
                @else
                    @php $field = 'w-full px-4 py-3 rounded-xl bg-[#F1F4EC]/70 border border-[#0B2B25]/15 text-sm font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#7BB33A]/40 focus:border-[#2E6B4F]'; @endphp
                    @if($errors->any())
                        <div class="mb-5 rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800" role="alert">
                            <div class="font-bold">Periksa kembali isian Anda:</div>
                            <ul class="list-disc list-inside mt-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form action="{{ route('public.request-demo') }}" method="POST" class="grid sm:grid-cols-2 gap-4">
                        @csrf
                        <div class="hidden" aria-hidden="true">
                            <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="school_name" class="block text-xs font-bold mb-1.5">Nama sekolah</label>
                            <input id="school_name" type="text" name="school_name" value="{{ old('school_name') }}" required maxlength="150" class="{{ $field }}" placeholder="SMK Negeri 1 ...">
                        </div>
                        <div>
                            <label for="npsn" class="block text-xs font-bold mb-1.5">NPSN</label>
                            <input id="npsn" type="text" name="npsn" value="{{ old('npsn') }}" required inputmode="numeric" pattern="[0-9]{8}" maxlength="8" class="{{ $field }} font-mono" placeholder="8 digit">
                        </div>
                        <div>
                            <label for="estimated_students" class="block text-xs font-bold mb-1.5">Perkiraan siswa PKL</label>
                            <input id="estimated_students" type="number" name="estimated_students" value="{{ old('estimated_students') }}" min="1" class="{{ $field }}" placeholder="mis. 240">
                        </div>
                        <div>
                            <label for="contact_name" class="block text-xs font-bold mb-1.5">Nama penanggung jawab</label>
                            <input id="contact_name" type="text" name="contact_name" value="{{ old('contact_name') }}" required maxlength="100" class="{{ $field }}">
                        </div>
                        <div>
                            <label for="phone" class="block text-xs font-bold mb-1.5">Nomor WhatsApp</label>
                            <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required maxlength="20" class="{{ $field }}" placeholder="08xxxxxxxxxx">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="email" class="block text-xs font-bold mb-1.5">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="100" class="{{ $field }}" placeholder="humas@sekolah.sch.id">
                            <p class="text-[11px] text-[#0B2B25]/50 mt-1">Email ini akan menjadi login admin sekolah saat permohonan disetujui.</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="notes" class="block text-xs font-bold mb-1.5">Catatan (opsional)</label>
                            <textarea id="notes" name="notes" rows="3" maxlength="500" class="{{ $field }}" placeholder="Jadwal PKL, jumlah DU/DI, atau paket yang diminati">{{ old('notes') }}</textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <button type="submit" class="w-full px-6 py-3.5 rounded-2xl text-sm font-extrabold bg-[#0B2B25] text-[#C6F26B] hover:bg-[#123d34]">Kirim permohonan demo</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </section>
</main>

<footer class="forest text-[#A9C2B9]">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm">
        <div class="flex items-center gap-2.5">
            <img src="{{ $systemLogo ?? asset('images/logo.png') }}" alt="" class="w-8 h-8 rounded-lg object-contain bg-white">
            <span class="font-space font-bold text-white">{{ $systemName ?? 'VokaTrack' }}</span>
            <span class="hidden sm:inline">· {{ $systemTagline ?? 'Platform PKL SMK' }}</span>
        </div>
        <div class="flex items-center gap-5">
            @if($showPricing)
                <a href="#harga" class="hover:text-white">Harga</a>
            @endif
            <a href="#demo" class="hover:text-white">Ajukan Demo</a>
            <a href="{{ route('login') }}" class="hover:text-white">Masuk</a>
        </div>
        <div class="text-xs">© {{ date('Y') }} {{ $systemName ?? 'VokaTrack' }}</div>
    </div>
</footer>

<script>
    (function () {
        // Animasi muncul saat di-scroll (konten tetap terlihat bila JS mati / reduced motion).
        const items = document.querySelectorAll('.reveal, .reveal-stagger');
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduce || !('IntersectionObserver' in window)) {
            items.forEach((el) => el.classList.add('in'));
        } else {
            const io = new IntersectionObserver((entries) => {
                entries.forEach((e) => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
            }, { rootMargin: '0px 0px -8% 0px' });
            items.forEach((el) => io.observe(el));
        }

        // Tombol paket mengisi catatan formulir demo.
        document.querySelectorAll('.plan-cta').forEach((btn) => {
            btn.addEventListener('click', () => {
                const notes = document.getElementById('notes');
                if (notes && !notes.value) notes.value = 'Tertarik dengan ' + btn.dataset.plan + '.';
            });
        });

        @if($errors->any() || session('success'))
            document.getElementById('demo')?.scrollIntoView();
        @endif

        @if(session('demo_whatsapp_url'))
            // Teruskan otomatis ke WhatsApp; tombol tetap tersedia bila pengalihan diblokir browser.
            setTimeout(() => { window.location.href = @json(session('demo_whatsapp_url')); }, 1200);
        @endif
    })();
</script>
</body>
</html>
