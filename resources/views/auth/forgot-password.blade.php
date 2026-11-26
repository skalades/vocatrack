<!DOCTYPE html>
<html lang="id" class="h-full bg-[#F1F4EC]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0B2B25">
    <title>Lupa Kata Sandi — {{ $systemName ?? 'VokaTrack' }} PKL SMK Enterprise</title>
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
                    <p class="text-[11px] text-[#A9C2B9] font-semibold mt-1 tracking-wide uppercase">Bantuan Masuk Akun</p>
                </div>
            </div>

            <!-- Content Banner -->
            <div class="relative z-10 my-10 max-w-md">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#C6F26B]/15 ring-1 ring-[#C6F26B]/30 text-[#C6F26B] text-[11px] font-bold">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Pemulihan Sandi Siswa
                </span>
                <h1 class="font-space text-3xl sm:text-4xl font-bold leading-[1.15] mt-4">
                    Lupa kata sandi akun PKL Anda?
                </h1>
                <p class="text-sm text-[#A9C2B9] mt-3 leading-relaxed">
                    Demi keamanan dan kemudahan di tempat PKL, kata sandi siswa dapat direset secara langsung oleh <strong>Guru Pembimbing</strong> Anda. Cukup masukkan email terdaftar, notifikasi akan otomatis dikirim ke guru pembimbing.
                </p>
            </div>

            <div class="relative z-10 text-xs text-[#A9C2B9]/80 font-medium">
                Pusat Bantuan & Layanan PKL SMK Terintegrasi
            </div>
        </aside>

        <!-- ================= Panel Form ================= -->
        <main class="relative -mt-10 lg:mt-0 px-4 pb-12 sm:px-6 lg:px-12 lg:py-12 flex items-start lg:items-center justify-center">
            <div class="w-full max-w-md">

                <div class="bg-white rounded-[28px] shadow-xl shadow-[#0B2B25]/10 ring-1 ring-[#D7DFD3] p-6 sm:p-8">
                    <div class="mb-6">
                        <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-[#2E6B4F] hover:text-[#0B2B25] transition-colors mb-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                            Kembali ke Halaman Masuk
                        </a>
                        <h2 class="font-space text-2xl font-bold text-[#10211D]">Permohonan Reset Sandi</h2>
                        <p class="text-xs sm:text-sm text-[#647A70] mt-1 font-medium">Masukkan email siswa Anda untuk meminta reset sandi ke guru pembimbing.</p>
                    </div>

                    <!-- Flash Message -->
                    @if(session('status'))
                        <div class="mb-5 p-4 rounded-2xl bg-[#EEF6DF] border border-[#CDE4A3] text-[#12382F] text-xs flex items-start gap-2.5 font-semibold leading-relaxed">
                            <svg class="w-5 h-5 text-[#2E6B4F] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-5 p-3.5 rounded-2xl bg-rose-50 border border-rose-200/80 text-rose-900 text-xs flex items-center gap-2.5 font-semibold">
                            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    <!-- Form -->
                    <form action="{{ route('password.email') }}" method="POST" class="space-y-4">
                        @csrf

                        <div>
                            <label for="email" class="block text-xs font-bold text-[#34473F] mb-1.5">Alamat Email Akun Siswa</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#8B9F95]">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" />
                                    </svg>
                                </div>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                                       placeholder="siswa@sekolah.sch.id"
                                       autocomplete="email"
                                       class="login-input w-full pl-10 pr-4 py-3 rounded-2xl text-sm bg-[#F1F4EC] border border-[#D7DFD3] transition-all font-semibold text-[#10211D] placeholder:text-[#8B9F95]">
                            </div>
                            <p class="text-[11px] text-[#647A70] mt-1.5">
                                Masukkan email yang Anda gunakan saat login di VokaTrack.
                            </p>
                        </div>

                        <button type="submit" class="group w-full py-3.5 px-4 rounded-2xl bg-[#0B2B25] hover:bg-[#12382F] text-white font-bold text-sm tracking-wide shadow-lg shadow-[#0B2B25]/25 transition-all active:scale-[0.99] cursor-pointer flex items-center justify-center gap-2">
                            <span>Kirim Permintaan ke Guru Pembimbing</span>
                            <span class="w-7 h-7 rounded-full bg-[#C6F26B] text-[#0B2B25] flex items-center justify-center transition-transform group-hover:translate-x-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
                            </span>
                        </button>
                    </form>

                    <div class="mt-6 pt-5 border-t border-[#D7DFD3] text-center text-xs text-[#647A70]">
                        <p>Sudah ingat kata sandi Anda? <a href="{{ route('login') }}" class="font-bold text-[#2E6B4F] hover:underline">Masuk sekarang</a></p>
                    </div>
                </div>

                <p class="text-center text-[11px] text-[#8B9F95] mt-5 font-medium">
                    &copy; {{ date('Y') }} {{ $systemName ?? 'VokaTrack' }} · Sistem Presensi GPS & Jurnal PKL SMK
                </p>
                <x-creator-branding variant="light" class="justify-center mt-2.5 text-[#647A70]" />
            </div>
        </main>
    </div>

</body>
</html>
