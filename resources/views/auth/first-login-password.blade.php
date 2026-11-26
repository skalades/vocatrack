<!DOCTYPE html>
<html lang="id" class="h-full bg-[#F1F4EC]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0B2B25">
    <title>Buat Kata Sandi Baru — {{ $systemName ?? 'VokaTrack' }} PKL SMK Enterprise</title>
    <link rel="icon" type="image/png" href="{{ $systemLogo ?? asset('images/logo.png') }}">

    <!-- Google Fonts: Plus Jakarta Sans & Space Grotesk -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
        .font-space { font-family: 'Space Grotesk', system-ui, sans-serif; letter-spacing: -0.02em; }

        .forest-panel {
            background-color: #0B2B25;
            background-image:
                radial-gradient(ellipse at 15% 0%, rgba(198, 242, 107, 0.14) 0, transparent 55%),
                radial-gradient(ellipse at 100% 100%, rgba(46, 107, 79, 0.55) 0, transparent 60%),
                linear-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.035) 1px, transparent 1px);
            background-size: auto, auto, 32px 32px, 32px 32px;
        }

        .login-input:focus {
            border-color: #12382F;
            box-shadow: 0 0 0 3px rgba(198, 242, 107, 0.45);
            background-color: #fff;
            outline: none;
        }
    </style>
</head>
<body class="min-h-full antialiased text-[#10211D] bg-[#F1F4EC] selection:bg-[#C6F26B] selection:text-[#0B2B25]">
    @php
        $roleLabels = [
            'super-admin' => 'Super Admin',
            'admin-sekolah' => 'Admin Sekolah',
            'kepala-sekolah' => 'Kepala Sekolah',
            'guru-pembimbing' => 'Guru Pembimbing',
            'pembimbing-dudi' => 'Pembimbing DU/DI',
            'siswa' => 'Siswa PKL',
        ];
        $roleLabel = $roleLabels[$user->role] ?? ucfirst((string) $user->role);
    @endphp

    <div class="min-h-screen lg:grid lg:grid-cols-[1.05fr_1fr]">

        <!-- ================= Panel Brand (Forest) ================= -->
        <aside class="forest-panel relative overflow-hidden text-white px-6 pt-10 pb-20 sm:px-10 lg:px-14 lg:py-12 flex flex-col justify-between">

            <!-- Brand -->
            <div class="flex items-center gap-3 relative z-10">
                <div class="w-12 h-12 rounded-2xl bg-white/10 ring-1 ring-white/15 backdrop-blur flex items-center justify-center p-1.5">
                    <img src="{{ $systemLogo ?? asset('images/logo.png') }}" alt="{{ $systemName ?? 'VokaTrack' }} Logo" class="w-full h-full object-contain">
                </div>
                <div class="min-w-0">
                    <p class="font-space text-xl font-bold leading-none">{{ $systemName ?? 'VokaTrack' }}</p>
                    <p class="text-[11px] text-[#A9C2B9] font-semibold mt-1 tracking-wide uppercase">Aktivasi Keamanan Akun</p>
                </div>
            </div>

            <!-- Content Banner -->
            <div class="relative z-10 my-10 max-w-md">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#C6F26B]/15 ring-1 ring-[#C6F26B]/30 text-[#C6F26B] text-[11px] font-bold">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Login Pertama Terdeteksi
                </span>
                <h1 class="font-space text-3xl sm:text-4xl font-bold leading-[1.12] mt-4">
                    Lindungi akun Anda<br><span class="text-[#C6F26B]">sebelum mulai.</span>
                </h1>
                <p class="text-sm text-[#A9C2B9] mt-3 leading-relaxed">
                    Akun Anda masih menggunakan kata sandi default sistem (<code class="px-1.5 py-0.5 rounded bg-white/10 text-[#C6F26B] font-semibold">password</code>). Buat kata sandi baru yang hanya Anda ketahui untuk mengakses portal {{ $systemName ?? 'VokaTrack' }}.
                </p>

                <ul class="mt-6 space-y-2.5 text-xs text-[#D7E6DF] hidden sm:block">
                    <li class="flex items-center gap-2.5">
                        <span class="w-5 h-5 rounded-full bg-[#C6F26B]/20 text-[#C6F26B] flex items-center justify-center shrink-0">✓</span>
                        <span>Minimal 8 karakter dengan kombinasi huruf &amp; angka</span>
                    </li>
                    <li class="flex items-center gap-2.5">
                        <span class="w-5 h-5 rounded-full bg-[#C6F26B]/20 text-[#C6F26B] flex items-center justify-center shrink-0">✓</span>
                        <span>Tidak menggunakan kata sandi bawaan sistem</span>
                    </li>
                    <li class="flex items-center gap-2.5">
                        <span class="w-5 h-5 rounded-full bg-[#C6F26B]/20 text-[#C6F26B] flex items-center justify-center shrink-0">✓</span>
                        <span>Berlaku hanya sekali saat login pertama</span>
                    </li>
                </ul>
            </div>

            <div class="relative z-10 text-xs text-[#A9C2B9]/80 font-medium">
                Standar Keamanan Akun {{ $systemName ?? 'VokaTrack' }} PKL SMK
            </div>
        </aside>

        <!-- ================= Panel Form ================= -->
        <main class="relative -mt-10 lg:mt-0 px-4 pb-12 sm:px-6 lg:px-12 lg:py-12 flex items-start lg:items-center justify-center">
            <div class="w-full max-w-md">

                <div class="bg-white rounded-[28px] shadow-xl shadow-[#0B2B25]/10 ring-1 ring-[#D7DFD3] p-6 sm:p-8">
                    <div class="mb-5">
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-[#2E6B4F]">Wajib Ubah Kata Sandi</p>
                        <h2 class="font-space text-2xl font-bold text-[#10211D] mt-1">Buat Kata Sandi Baru</h2>
                        <p class="text-xs sm:text-sm text-[#647A70] mt-1 font-medium">Ganti kata sandi default sistem sebelum melanjutkan ke dasbor Anda.</p>
                    </div>

                    <!-- Identitas Akun Aktif -->
                    <div class="mb-5 p-3.5 rounded-2xl bg-[#F1F4EC] border border-[#D7DFD3] flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-[#10211D] truncate">{{ $user->name }}</p>
                            <p class="text-[11px] text-[#647A70] font-medium truncate">{{ $user->email }}</p>
                        </div>
                        <span class="shrink-0 px-2.5 py-1 rounded-full bg-[#0B2B25] text-[#C6F26B] text-[10px] font-bold uppercase tracking-wide">
                            {{ $roleLabel }}
                        </span>
                    </div>

                    <!-- Peringatan Sandi Default -->
                    <div class="mb-5 p-3.5 rounded-2xl bg-amber-50 border border-amber-200/80 text-amber-900 text-xs flex items-start gap-2.5 font-semibold leading-relaxed">
                        <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 9v3.75m0 3.75h.008M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <span>Kata sandi Anda saat ini masih menggunakan default sistem (<strong>password</strong>). Silakan atur kata sandi pribadi Anda sekarang.</span>
                    </div>

                    @if($errors->any())
                        <div class="mb-5 p-3.5 rounded-2xl bg-rose-50 border border-rose-200/80 text-rose-900 text-xs flex items-center gap-2.5 font-semibold">
                            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    <!-- Form Ubah Password Pertama Kali -->
                    <form action="{{ route('password.first-login.update') }}" method="POST" class="space-y-4" onsubmit="handleFirstLoginSubmit(this)">
                        @csrf

                        <div>
                            <label for="first_password" class="block text-xs font-bold text-[#34473F] mb-1.5">Kata Sandi Baru</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#8B9F95]">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <input type="password" id="first_password" name="password" required minlength="8" autofocus
                                       placeholder="Minimal 8 karakter"
                                       autocomplete="new-password"
                                       oninput="validatePasswordCriteria()"
                                       class="login-input w-full pl-10 pr-11 py-3 rounded-2xl text-sm bg-[#F1F4EC] border border-[#D7DFD3] transition-all font-semibold text-[#10211D] placeholder:text-[#8B9F95]">
                                <button type="button" onclick="toggleFieldVisibility('first_password', 'eye-open-1', 'eye-slash-1')" aria-label="Lihat kata sandi baru" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-[#8B9F95] hover:text-[#12382F] cursor-pointer transition-colors">
                                    <svg id="eye-open-1" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg id="eye-slash-1" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="first_password_confirmation" class="block text-xs font-bold text-[#34473F] mb-1.5">Ulangi Kata Sandi Baru</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#8B9F95]">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </div>
                                <input type="password" id="first_password_confirmation" name="password_confirmation" required minlength="8"
                                       placeholder="Ketik ulang kata sandi baru"
                                       autocomplete="new-password"
                                       oninput="validatePasswordCriteria()"
                                       class="login-input w-full pl-10 pr-11 py-3 rounded-2xl text-sm bg-[#F1F4EC] border border-[#D7DFD3] transition-all font-semibold text-[#10211D] placeholder:text-[#8B9F95]">
                                <button type="button" onclick="toggleFieldVisibility('first_password_confirmation', 'eye-open-2', 'eye-slash-2')" aria-label="Lihat konfirmasi kata sandi" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-[#8B9F95] hover:text-[#12382F] cursor-pointer transition-colors">
                                    <svg id="eye-open-2" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg id="eye-slash-2" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Indikator Kriteria -->
                        <div class="p-3 rounded-2xl bg-[#F8FAF5] border border-[#E2E8DC] space-y-1.5 text-[11px] font-semibold text-[#647A70]">
                            <p id="rule-length" class="flex items-center gap-2 transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                <span>Minimal 8 karakter</span>
                            </p>
                            <p id="rule-not-default" class="flex items-center gap-2 transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                <span>Bukan kata sandi default sistem (password)</span>
                            </p>
                            <p id="rule-match" class="flex items-center gap-2 transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                <span>Konfirmasi kata sandi cocok</span>
                            </p>
                        </div>

                        <button type="submit" id="first-login-submit" class="group w-full py-3.5 px-4 rounded-2xl bg-[#0B2B25] hover:bg-[#12382F] text-white font-bold text-sm tracking-wide shadow-lg shadow-[#0B2B25]/25 transition-all active:scale-[0.99] cursor-pointer flex items-center justify-center gap-2 disabled:opacity-80 disabled:cursor-wait">
                            <span id="first-login-submit-label">Simpan &amp; Lanjutkan ke Dasbor</span>
                            <span class="w-7 h-7 rounded-full bg-[#C6F26B] text-[#0B2B25] flex items-center justify-center transition-transform group-hover:translate-x-0.5">
                                <svg id="first-login-submit-icon" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
                                <svg id="first-login-submit-spinner" class="w-4 h-4 hidden animate-spin" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity="0.25"/><path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                            </span>
                        </button>
                    </form>

                    <!-- Opsi Keluar -->
                    <div class="mt-6 pt-5 border-t border-[#D7DFD3] text-center">
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-xs font-bold text-[#647A70] hover:text-[#10211D] hover:underline cursor-pointer transition-colors">
                                Keluar &amp; masuk dengan akun lain
                            </button>
                        </form>
                    </div>
                </div>

                <p class="text-center text-[11px] text-[#8B9F95] mt-5 font-medium">
                    &copy; {{ date('Y') }} {{ $systemName ?? 'VokaTrack' }} · Sistem Presensi GPS &amp; Jurnal PKL SMK
                </p>
                <x-creator-branding variant="light" class="justify-center mt-2.5 text-[#647A70]" />
            </div>
        </main>
    </div>

    <script>
        function toggleFieldVisibility(inputId, openId, slashId) {
            const input = document.getElementById(inputId);
            const eyeOpen = document.getElementById(openId);
            const eyeSlash = document.getElementById(slashId);
            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                eyeOpen?.classList.add('hidden');
                eyeSlash?.classList.remove('hidden');
            } else {
                input.type = 'password';
                eyeOpen?.classList.remove('hidden');
                eyeSlash?.classList.add('hidden');
            }
        }

        function setRuleState(el, passed) {
            if (!el) return;
            el.classList.toggle('text-[#2E6B4F]', passed);
            el.classList.toggle('text-[#647A70]', !passed);
        }

        function validatePasswordCriteria() {
            const pwd = document.getElementById('first_password')?.value || '';
            const confirm = document.getElementById('first_password_confirmation')?.value || '';

            setRuleState(document.getElementById('rule-length'), pwd.length >= 8);
            setRuleState(document.getElementById('rule-not-default'), pwd.length > 0 && pwd !== 'password');
            setRuleState(document.getElementById('rule-match'), pwd.length > 0 && pwd === confirm);
        }

        function handleFirstLoginSubmit() {
            const btn = document.getElementById('first-login-submit');
            setTimeout(() => {
                btn.disabled = true;
                document.getElementById('first-login-submit-label').textContent = 'Menyimpan…';
                document.getElementById('first-login-submit-icon').classList.add('hidden');
                document.getElementById('first-login-submit-spinner').classList.remove('hidden');
            }, 0);
        }

        window.addEventListener('pageshow', (e) => {
            if (!e.persisted) return;
            const btn = document.getElementById('first-login-submit');
            if (btn) btn.disabled = false;
            document.getElementById('first-login-submit-label').textContent = 'Simpan & Lanjutkan ke Dasbor';
            document.getElementById('first-login-submit-icon').classList.remove('hidden');
            document.getElementById('first-login-submit-spinner').classList.add('hidden');
        });
    </script>
</body>
</html>
