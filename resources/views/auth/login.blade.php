<!DOCTYPE html>
<html lang="id" class="h-full bg-[#F1F4EC]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0B2B25">
    <title>Masuk — {{ $systemName ?? 'VokaTrack' }} PKL SMK Enterprise</title>
    <link rel="icon" type="image/png" href="{{ $systemLogo ?? asset('images/logo.png') }}">

    <!-- Google Fonts: Plus Jakarta Sans & Space Grotesk (senada portal guru & siswa) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
        .font-space { font-family: 'Space Grotesk', system-ui, sans-serif; letter-spacing: -0.02em; }

        /* Panel hutan: gradasi + grid satelit halus */
        .forest-panel {
            background-color: #0B2B25;
            background-image:
                radial-gradient(ellipse at 15% 0%, rgba(198, 242, 107, 0.14) 0, transparent 55%),
                radial-gradient(ellipse at 100% 100%, rgba(46, 107, 79, 0.55) 0, transparent 60%),
                linear-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.035) 1px, transparent 1px);
            background-size: auto, auto, 32px 32px, 32px 32px;
        }

        /* Radar geofence */
        .geo-ring { position: absolute; inset: 0; border-radius: 9999px; border: 1px solid rgba(198, 242, 107, 0.22); }
        .geo-pulse {
            position: absolute; inset: 0; border-radius: 9999px;
            border: 2px solid rgba(198, 242, 107, 0.55);
            animation: loginGeoPulse 3.2s cubic-bezier(0.2, 0.6, 0.3, 1) infinite;
        }
        .geo-pulse.delay { animation-delay: 1.6s; }
        @keyframes loginGeoPulse {
            0%   { transform: scale(0.25); opacity: 0.9; }
            100% { transform: scale(1);    opacity: 0; }
        }
        .geo-sweep {
            position: absolute; inset: 0; border-radius: 9999px;
            background: conic-gradient(from 0deg, rgba(198, 242, 107, 0.28), transparent 70deg, transparent 360deg);
            animation: loginGeoSweep 6s linear infinite;
        }
        @keyframes loginGeoSweep { to { transform: rotate(360deg); } }

        .login-input:focus {
            border-color: #12382F;
            box-shadow: 0 0 0 3px rgba(198, 242, 107, 0.45);
            background-color: #fff;
            outline: none;
        }

        @media (prefers-reduced-motion: reduce) {
            .geo-pulse, .geo-sweep, .animate-float, .animate-fade-in-up { animation: none !important; }
        }
    </style>
</head>
<body class="min-h-full antialiased text-[#10211D] bg-[#F1F4EC] selection:bg-[#C6F26B] selection:text-[#0B2B25]">

    <div class="min-h-screen lg:grid lg:grid-cols-[1.05fr_1fr]">

        <!-- ================= Panel Brand (Forest) ================= -->
        <aside class="forest-panel relative overflow-hidden text-white px-6 pt-10 pb-24 sm:px-10 lg:px-14 lg:py-12 flex flex-col">

            <!-- Brand -->
            <div class="flex items-center gap-3 relative z-10">
                <div class="w-12 h-12 rounded-2xl bg-white/10 ring-1 ring-white/15 backdrop-blur flex items-center justify-center p-1.5">
                    <img src="{{ $systemLogo ?? asset('images/logo.png') }}" alt="{{ $systemName ?? 'VokaTrack' }} Logo" class="w-full h-full object-contain">
                </div>
                <div class="min-w-0">
                    <p class="font-space text-xl font-bold leading-none">{{ $systemName ?? 'VokaTrack' }}</p>
                    <p class="text-[11px] text-[#A9C2B9] font-semibold mt-1 tracking-wide uppercase">Portal PKL SMK</p>
                </div>
            </div>

            <!-- Headline -->
            <div class="relative z-10 mt-8 lg:mt-auto max-w-md animate-fade-in-up">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#C6F26B]/15 ring-1 ring-[#C6F26B]/30 text-[#C6F26B] text-[11px] font-bold">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#C6F26B] opacity-70"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-[#C6F26B]"></span>
                    </span>
                    Presensi GPS Aktif
                </span>
                <h1 class="font-space text-3xl sm:text-4xl lg:text-[2.75rem] font-bold leading-[1.08] mt-4">
                    Pantau PKL,<br><span class="text-[#C6F26B]">tanpa ribet.</span>
                </h1>
                <p class="text-sm text-[#A9C2B9] mt-3 leading-relaxed hidden sm:block">
                    Presensi berbasis geofence, jurnal harian, dan supervisi guru pembimbing — semuanya dalam satu sistem terintegrasi.
                </p>
            </div>

            <!-- Radar Geofence (desktop) -->
            <div class="hidden lg:flex relative z-10 my-10 items-center justify-center">
                <div class="relative w-64 h-64 xl:w-72 xl:h-72">
                    <div class="geo-ring"></div>
                    <div class="geo-ring" style="inset: 16%"></div>
                    <div class="geo-ring" style="inset: 32%"></div>
                    <div class="geo-sweep"></div>
                    <div class="geo-pulse"></div>
                    <div class="geo-pulse delay"></div>
                    <!-- Titik siswa -->
                    <span class="absolute w-2.5 h-2.5 rounded-full bg-[#C6F26B] shadow-[0_0_12px_#C6F26B]" style="top: 28%; left: 62%"></span>
                    <span class="absolute w-2 h-2 rounded-full bg-[#C6F26B]/80" style="top: 60%; left: 30%"></span>
                    <span class="absolute w-2 h-2 rounded-full bg-[#C6F26B]/70" style="top: 70%; left: 66%"></span>
                    <!-- Pin pusat DU/DI -->
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="w-12 h-12 rounded-2xl bg-[#C6F26B] text-[#0B2B25] flex items-center justify-center shadow-lg shadow-black/30 animate-float">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-6.2-7-11.5A7 7 0 0112 2.5a7 7 0 017 7C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fitur -->
            <ul class="relative z-10 hidden lg:grid grid-cols-3 gap-3 mt-auto">
                @foreach ([
                    ['Geofence', 'Radius DU/DI', 'M12 21s-7-6.2-7-11.5A7 7 0 0112 2.5a7 7 0 017 7C19 14.8 12 21 12 21z'],
                    ['Jurnal', 'Harian siswa', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.6a1 1 0 01.7.3l5.4 5.4a1 1 0 01.3.7V19a2 2 0 01-2 2z'],
                    ['SOS', 'Pantau real-time', 'M12 9v3.75m0 3.75h.008M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z'],
                ] as [$title, $sub, $icon])
                    <li class="rounded-2xl bg-white/[0.06] ring-1 ring-white/10 p-3.5">
                        <svg class="w-5 h-5 text-[#C6F26B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                        <p class="text-sm font-bold mt-2">{{ $title }}</p>
                        <p class="text-[11px] text-[#A9C2B9] font-medium">{{ $sub }}</p>
                    </li>
                @endforeach
            </ul>
        </aside>

        <!-- ================= Panel Form ================= -->
        <main class="relative -mt-14 lg:mt-0 px-4 pb-10 sm:px-6 lg:px-12 lg:py-12 flex items-start lg:items-center justify-center">
            <div class="w-full max-w-md animate-fade-in-up">

                <div class="bg-white rounded-[28px] shadow-xl shadow-[#0B2B25]/10 ring-1 ring-[#D7DFD3] p-6 sm:p-8">
                    <div class="mb-6">
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-[#2E6B4F]">Masuk Akun</p>
                        <h2 class="font-space text-2xl font-bold text-[#10211D] mt-1">Selamat Datang</h2>
                        <p class="text-xs sm:text-sm text-[#647A70] mt-1 font-medium">Masukkan email/NISN dan kata sandi untuk melanjutkan.</p>
                    </div>

                    <!-- Flash Alerts -->
                    @if(session('success'))
                        <div class="mb-5 p-3.5 rounded-2xl bg-[#EEF6DF] border border-[#CDE4A3] text-[#12382F] text-xs flex items-center gap-2.5 font-semibold">
                            <svg class="w-4 h-4 text-[#2E6B4F] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @if(session('info'))
                        <div class="mb-5 p-3.5 rounded-2xl bg-[#F1F4EC] border border-[#D7DFD3] text-[#1F3530] text-xs flex items-center gap-2.5 font-semibold">
                            <svg class="w-4 h-4 text-[#2E6B4F] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>{{ session('info') }}</span>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-5 p-3.5 rounded-2xl bg-rose-50 border border-rose-200/80 text-rose-900 text-xs flex items-center gap-2.5 font-semibold">
                            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    <!-- Form -->
                    <form action="{{ route('login.post') }}" method="POST" class="space-y-4" onsubmit="handleLoginSubmit(this)">
                        @csrf

                        <div>
                            <label for="email" class="block text-xs font-bold text-[#34473F] mb-1.5">Alamat Email atau NISN</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#8B9F95]">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" />
                                    </svg>
                                </div>
                                <input type="text" id="email" name="email" value="{{ old('email') }}" required autofocus
                                       placeholder="nama@sekolah.sch.id / NISN"
                                       autocomplete="username email"
                                       autocapitalize="none"
                                       autocorrect="off"
                                       spellcheck="false"
                                       class="login-input w-full pl-10 pr-4 py-3 rounded-2xl text-sm bg-[#F1F4EC] border border-[#D7DFD3] transition-all font-semibold text-[#10211D] placeholder:text-[#8B9F95] placeholder:font-medium">
                            </div>
                        </div>

                        <div>
                            <label for="password" class="block text-xs font-bold text-[#34473F] mb-1.5">Kata Sandi</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#8B9F95]">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <input type="password" id="password" name="password" required
                                       placeholder="••••••••"
                                       autocomplete="current-password"
                                       class="login-input w-full pl-10 pr-11 py-3 rounded-2xl text-sm bg-[#F1F4EC] border border-[#D7DFD3] transition-all font-semibold text-[#10211D] placeholder:text-[#8B9F95]">
                                <button type="button" onclick="togglePasswordVisibility()" id="pwd-toggle-btn" aria-label="Lihat kata sandi" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-[#8B9F95] hover:text-[#12382F] cursor-pointer transition-colors">
                                    <svg id="pwd-eye-open" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg id="pwd-eye-slash" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-xs pt-0.5">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="remember" class="w-4 h-4 rounded border-[#B9C6BD] text-[#12382F] accent-[#12382F] focus:ring-[#C6F26B] cursor-pointer">
                                <span class="text-[#4A5E55] font-semibold">Ingat saya di perangkat ini</span>
                            </label>
                            <a href="{{ route('password.request') }}" class="font-bold text-[#2E6B4F] hover:text-[#0B2B25] hover:underline transition-colors">
                                Lupa password?
                            </a>
                        </div>

                        <button type="submit" id="login-submit" class="group w-full py-3.5 px-4 rounded-2xl bg-[#0B2B25] hover:bg-[#12382F] text-white font-bold text-sm tracking-wide shadow-lg shadow-[#0B2B25]/25 transition-all active:scale-[0.99] cursor-pointer flex items-center justify-center gap-2 disabled:opacity-80 disabled:cursor-wait">
                            <span id="login-submit-label">Masuk ke Sistem</span>
                            <span class="w-7 h-7 rounded-full bg-[#C6F26B] text-[#0B2B25] flex items-center justify-center transition-transform group-hover:translate-x-0.5">
                                <svg id="login-submit-icon" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
                                <svg id="login-submit-spinner" class="w-4 h-4 hidden animate-spin" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity="0.25"/><path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                            </span>
                        </button>
                    </form>
                </div>

                <!-- Info peran -->
                <div class="mt-5 flex flex-wrap items-center justify-center gap-2 text-[11px] font-semibold text-[#647A70]">
                    <span class="px-2.5 py-1 rounded-full bg-white ring-1 ring-[#D7DFD3]">Siswa</span>
                    <span class="px-2.5 py-1 rounded-full bg-white ring-1 ring-[#D7DFD3]">Guru Pembimbing</span>
                    <span class="px-2.5 py-1 rounded-full bg-white ring-1 ring-[#D7DFD3]">DU/DI</span>
                    <span class="px-2.5 py-1 rounded-full bg-white ring-1 ring-[#D7DFD3]">Admin Sekolah</span>
                </div>
                <p class="text-center text-[11px] text-[#8B9F95] mt-4 font-medium">
                    &copy; {{ date('Y') }} {{ $systemName ?? 'VokaTrack' }} · Sistem Presensi GPS & Jurnal PKL SMK
                </p>
            </div>
        </main>
    </div>

    <script>
        function togglePasswordVisibility() {
            const pwdInput = document.getElementById('password');
            const eyeOpen = document.getElementById('pwd-eye-open');
            const eyeSlash = document.getElementById('pwd-eye-slash');
            const toggleBtn = document.getElementById('pwd-toggle-btn');

            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                eyeOpen.classList.add('hidden');
                eyeSlash.classList.remove('hidden');
                toggleBtn.setAttribute('aria-label', 'Sembunyikan kata sandi');
            } else {
                pwdInput.type = 'password';
                eyeOpen.classList.remove('hidden');
                eyeSlash.classList.add('hidden');
                toggleBtn.setAttribute('aria-label', 'Lihat kata sandi');
            }
        }

        function handleLoginSubmit(form) {
            const btn = document.getElementById('login-submit');
            // Tunda disable agar data form tetap terkirim
            setTimeout(() => {
                btn.disabled = true;
                document.getElementById('login-submit-label').textContent = 'Memproses…';
                document.getElementById('login-submit-icon').classList.add('hidden');
                document.getElementById('login-submit-spinner').classList.remove('hidden');
            }, 0);
        }

        // Pulihkan tombol jika halaman dipulihkan dari cache (tombol Back)
        window.addEventListener('pageshow', (e) => {
            if (!e.persisted) return;
            document.getElementById('login-submit').disabled = false;
            document.getElementById('login-submit-label').textContent = 'Masuk ke Sistem';
            document.getElementById('login-submit-icon').classList.remove('hidden');
            document.getElementById('login-submit-spinner').classList.add('hidden');
        });
    </script>
</body>
</html>
