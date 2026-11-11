<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — VokaTrack PKL SMK Enterprise</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .bg-mesh {
            background-color: #f8fafc;
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(79, 70, 229, 0.08) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(241, 245, 249, 0.5) 0px, transparent 50%);
        }
    </style>
</head>
<body class="min-h-full flex items-center justify-center p-4 sm:p-6 bg-mesh selection:bg-indigo-600 selection:text-white">

    <div class="w-full max-w-lg my-8">
        
        <!-- Brand Identity -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center mb-3">
                <img src="{{ asset('images/logo.png') }}" alt="VokaTrack Logo" class="h-24 sm:h-28 w-auto object-contain drop-shadow-md transition-transform hover:scale-105 duration-200">
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">VokaTrack</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">Sistem Presensi GPS & Jurnal PKL SMK Terintegrasi</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/70 border border-slate-200/90 p-6 sm:p-8">
            <div class="mb-6">
                <h2 class="text-lg font-bold text-slate-900 tracking-tight">Selamat Datang</h2>
                <p class="text-xs text-slate-500 mt-0.5 font-medium">Masukkan email dan kata sandi akun Anda untuk melanjutkan.</p>
            </div>

            <!-- Flash Alerts -->
            @if(session('success'))
                <div class="mb-5 p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200/80 text-emerald-900 text-xs flex items-center gap-2.5 font-medium">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-5 p-3.5 rounded-2xl bg-rose-50 border border-rose-200/80 text-rose-900 text-xs flex items-center gap-2.5 font-medium">
                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <!-- Form -->
            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf
                
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" />
                            </svg>
                        </div>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                               placeholder="nama@sekolah.sch.id"
                               class="w-full pl-10 pr-4 py-2.5 rounded-xl text-xs sm:text-sm bg-slate-50/70 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium text-slate-900 placeholder:text-slate-400">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">Kata Sandi</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input type="password" id="password" name="password" required
                               placeholder="••••••••"
                               class="w-full pl-10 pr-10 py-2.5 rounded-xl text-xs sm:text-sm bg-slate-50/70 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium text-slate-900 placeholder:text-slate-400">
                        <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
                            <svg id="pwd-eye-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs pt-0.5">
                    <label class="flex items-center space-x-2 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500/20 cursor-pointer">
                        <span class="text-slate-600 font-medium">Ingat saya di perangkat ini</span>
                    </label>
                </div>

                <button type="submit" class="w-full py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold text-xs sm:text-sm tracking-wide shadow-md shadow-indigo-600/20 transition-all active:scale-[0.99] cursor-pointer">
                    Masuk ke Sistem
                </button>
            </form>
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const pwdInput = document.getElementById('password');
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
            } else {
                pwdInput.type = 'password';
            }
        }
    </script>
</body>
</html>
