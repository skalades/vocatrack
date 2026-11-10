<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — VokaTrack PKL SMK</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-full flex items-center justify-center p-4 bg-gradient-to-br from-slate-100 via-indigo-50/30 to-slate-100">
    <div class="w-full max-w-md">
        <!-- Brand Header -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-600 text-white font-black text-2xl shadow-xl shadow-indigo-200 mb-3">
                VT
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">VokaTrack</h1>
            <p class="text-xs text-slate-500 mt-1 font-medium">Sistem Presensi & Jurnal PKL SMK Multi-Sekolah</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/60 border border-slate-200/80 p-7">
            <h2 class="text-base font-bold text-slate-900 mb-1">Selamat Datang Kembali</h2>
            <p class="text-xs text-slate-500 mb-5">Masukkan kredensial akun Anda untuk mengakses sistem.</p>

            @if(session('success'))
                <div class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                           placeholder="nama@sekolah.sch.id"
                           class="w-full px-4 py-2.5 rounded-xl text-xs bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium text-slate-900">
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">Kata Sandi</label>
                    <input type="password" id="password" name="password" required
                           placeholder="••••••••"
                           class="w-full px-4 py-2.5 rounded-xl text-xs bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium text-slate-900">
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                        <span class="text-slate-600 font-medium">Ingat saya</span>
                    </label>
                </div>

                <button type="submit" class="w-full py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs tracking-wide shadow-lg shadow-indigo-200 transition-all active:scale-[0.98]">
                    Masuk ke Sistem
                </button>
            </form>

            <!-- Quick Demo Accounts Switcher -->
            <div class="mt-6 pt-5 border-t border-slate-100">
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2.5 text-center">Akun Demo (Klik untuk Isi Cepat)</p>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" onclick="fillDemo('siswa@smkn1tekno.sch.id', 'password')" class="text-left px-2.5 py-2 rounded-lg bg-indigo-50/70 hover:bg-indigo-100 border border-indigo-100 text-indigo-900 text-[11px] font-semibold transition-colors">
                        📱 Siswa (Mobile)
                    </button>
                    <button type="button" onclick="fillDemo('guru@smkn1tekno.sch.id', 'password')" class="text-left px-2.5 py-2 rounded-lg bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 text-[11px] font-semibold transition-colors">
                        👩‍🏫 Guru Pembimbing
                    </button>
                    <button type="button" onclick="fillDemo('supervisor@inovasidigital.com', 'password')" class="text-left px-2.5 py-2 rounded-lg bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 text-[11px] font-semibold transition-colors">
                        🏢 Pembimbing DU/DI
                    </button>
                    <button type="button" onclick="fillDemo('admin@smkn1tekno.sch.id', 'password')" class="text-left px-2.5 py-2 rounded-lg bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 text-[11px] font-semibold transition-colors">
                        ⚙️ Admin Sekolah
                    </button>
                    <button type="button" onclick="fillDemo('kepsek@smkn1tekno.sch.id', 'password')" class="text-left px-2.5 py-2 rounded-lg bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 text-[11px] font-semibold transition-colors">
                        🎓 Kepala Sekolah
                    </button>
                    <button type="button" onclick="fillDemo('superadmin@vokatrack.id', 'password')" class="text-left px-2.5 py-2 rounded-lg bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 text-[11px] font-semibold transition-colors">
                        🌐 Super Admin
                    </button>
                </div>
                <p class="text-[10px] text-center text-slate-400 mt-2 font-medium">Kata sandi seluruh akun demo: <code class="text-slate-600 font-bold">password</code></p>
            </div>
        </div>
    </div>

    <script>
        function fillDemo(email, pass) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = pass;
        }
    </script>
</body>
</html>
