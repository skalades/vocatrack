<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        @if($status === 'active' && isset($student))
            Monitoring PKL: {{ $student->user->name }} - VokaTrack
        @else
            Portal Monitoring Orang Tua - VokaTrack
        @endif
    </title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col justify-between antialiased selection:bg-indigo-500 selection:text-white">

    <!-- Top Brand Header Bar -->
    <header class="border-b border-slate-800/80 bg-slate-950/80 backdrop-blur-md sticky top-0 z-30">
        <div class="max-w-xl mx-auto px-4 py-3.5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center font-black text-sm shadow-md shadow-indigo-600/30">
                    V
                </div>
                <div>
                    <span class="text-sm font-black tracking-tight text-white">VokaTrack</span>
                    <span class="text-[10px] font-bold text-slate-400 block -mt-1 tracking-wider uppercase">Portal Monitoring Orang Tua</span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @if($status === 'active')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Akses Terhubung</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/30">
                        <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                        <span>Tautan Tidak Aktif</span>
                    </span>
                @endif
            </div>
        </div>
    </header>

    <!-- Main Container (Mobile-First 1-column layout) -->
    <main class="flex-1 max-w-xl w-full mx-auto px-4 py-6 sm:py-8 space-y-4">

        {{-- 1. ERROR / INACTIVE / NOT FOUND STATE --}}
        @if($status === 'not_found' || $status === 'disabled')
            <div class="bg-gradient-to-b from-slate-800/90 to-slate-900 border border-slate-700/80 rounded-3xl p-6 sm:p-8 text-center shadow-2xl relative overflow-hidden">
                <div class="w-16 h-16 mx-auto rounded-3xl bg-rose-500/10 border border-rose-500/30 text-rose-400 flex items-center justify-center mb-4 shadow-lg shadow-rose-500/10">
                    <svg class="w-8 h-8 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>

                <h1 class="text-lg font-black text-white tracking-tight">
                    {{ $status === 'disabled' ? 'Portal Dinonaktifkan' : 'Tautan Tidak Ditemukan' }}
                </h1>

                <p class="text-xs sm:text-sm text-slate-400 mt-2 leading-relaxed">
                    {{ $message ?? 'Tautan monitoring ini tidak valid atau telah diperbarui oleh siswa demi keamanan.' }}
                </p>

                <div class="mt-6 p-4 rounded-2xl bg-slate-950/60 border border-slate-800 text-left text-xs text-slate-400 space-y-2">
                    <div class="flex items-start gap-2 text-indigo-300 font-bold">
                        <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Petunjuk untuk Orang Tua / Wali:</span>
                    </div>
                    <p class="leading-relaxed text-slate-300 pl-6">
                        Silakan hubungi putra/putri Anda dan minta mereka membuka menu <strong>Profil &raquo; Bagikan Link Orang Tua</strong> di aplikasi VokaTrack mereka untuk mengirimkan link terbaru via WhatsApp.
                    </p>
                </div>
            </div>

        {{-- 2. ACTIVE MONITORING STATE --}}
        @else

            <!-- Student Identity & School Banner Card -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-800 to-slate-900 border border-slate-700/80 p-5 shadow-xl">
                <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-indigo-500/10 blur-2xl pointer-events-none"></div>

                <div class="flex items-center gap-3.5">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white font-black text-xl flex items-center justify-center shadow-md shadow-indigo-600/30 shrink-0">
                        {{ strtoupper(substr($student->user->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="text-[10px] font-extrabold uppercase tracking-widest text-indigo-400 block">
                            {{ $student->user->school->name ?? 'SMK Indonesia' }}
                        </span>
                        <h1 class="text-base sm:text-lg font-black text-white truncate">
                            {{ $student->user->name }}
                        </h1>
                        <p class="text-xs text-slate-400 font-medium">
                            Kelas {{ $student->class->name ?? '-' }} &bull; {{ $student->class->major->name ?? '-' }}
                        </p>
                    </div>
                </div>

                <!-- PKL Placement Info Bar -->
                @if($has_pkl && $spkl)
                    <div class="mt-4 pt-3.5 border-t border-slate-700/60 grid grid-cols-2 gap-2 text-xs">
                        <div class="bg-slate-950/40 rounded-2xl p-2.5 border border-slate-800">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Tempat PKL</span>
                            <span class="font-bold text-white truncate block mt-0.5">{{ $spkl->company->name ?? '-' }}</span>
                        </div>
                        <div class="bg-slate-950/40 rounded-2xl p-2.5 border border-slate-800">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Status PKL</span>
                            <span class="inline-flex items-center gap-1 font-bold text-emerald-400 mt-0.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                {{ ucfirst($spkl->status) }}
                            </span>
                        </div>
                    </div>
                @else
                    <div class="mt-4 pt-3 border-t border-slate-700/60 text-xs text-amber-400 flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Siswa belum memiliki penugasan periode PKL yang aktif saat ini.</span>
                    </div>
                @endif
            </div>

            @if($has_pkl && $spkl)
                <!-- LIVE STATUS HARI INI (HERO CARD) -->
                <div class="rounded-3xl bg-slate-800/90 border border-slate-700/80 p-5 shadow-xl space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-xs font-bold text-slate-300">Status Kehadiran Hari Ini</span>
                        </div>
                        <span class="text-[11px] font-semibold text-slate-400">
                            {{ \Carbon\Carbon::today()->translatedFormat('l, d F Y') }}
                        </span>
                    </div>

                    @if($today_attendance)
                        @php
                            $statusStyle = match($today_attendance->status) {
                                'hadir' => [
                                    'bg' => 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400',
                                    'badge' => 'Hadir Tepat Waktu',
                                    'desc' => 'Siswa telah berada di lokasi PKL.',
                                ],
                                'terlambat' => [
                                    'bg' => 'bg-amber-500/10 border-amber-500/30 text-amber-400',
                                    'badge' => 'Hadir (Terlambat)',
                                    'desc' => 'Siswa hadir melewati toleransi jam masuk.',
                                ],
                                'izin' => [
                                    'bg' => 'bg-indigo-500/10 border-indigo-500/30 text-indigo-400',
                                    'badge' => 'Izin Resmi Tercatat',
                                    'desc' => 'Permohonan izin telah disetujui.',
                                ],
                                'sakit' => [
                                    'bg' => 'bg-amber-500/10 border-amber-500/30 text-amber-400',
                                    'badge' => 'Sakit Tercatat',
                                    'desc' => 'Keterangan sakit telah dilaporkan.',
                                ],
                                'dinas_luar', 'luar_kantor' => [
                                    'bg' => 'bg-sky-500/10 border-sky-500/30 text-sky-400',
                                    'badge' => 'Tugas Luar Kantor',
                                    'desc' => 'Siswa menjalankan tugas luar bersama instansi.',
                                ],
                                default => [
                                    'bg' => 'bg-rose-500/10 border-rose-500/30 text-rose-400',
                                    'badge' => 'Tidak Hadir / Alfa',
                                    'desc' => 'Belum ada keterangan resmi kehadiran hari ini.',
                                ],
                            };
                        @endphp

                        <div class="p-4 rounded-2xl border {{ $statusStyle['bg'] }} flex items-start justify-between gap-3">
                            <div class="space-y-1">
                                <span class="text-sm sm:text-base font-black block">
                                    {{ $statusStyle['badge'] }}
                                </span>
                                <p class="text-xs text-slate-300">
                                    {{ $statusStyle['desc'] }}
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-[10px] font-bold text-slate-400 block uppercase">Jam Masuk</span>
                                <span class="font-mono text-sm font-bold text-white">
                                    {{ $today_attendance->checkin_time ? substr($today_attendance->checkin_time, 0, 5) . ' WIB' : '-' }}
                                </span>
                            </div>
                        </div>

                        <!-- Check-out Status Row -->
                        <div class="flex items-center justify-between text-xs px-2 pt-1 text-slate-400">
                            <span>Jam Kepulangan:</span>
                            @if($today_attendance->checkout_time)
                                <span class="font-mono font-bold text-emerald-400">
                                    {{ substr($today_attendance->checkout_time, 0, 5) }} WIB (Sudah Presensi Pulang)
                                </span>
                            @else
                                <span class="text-slate-400 font-medium italic">
                                    Belum jam pulang kerja
                                </span>
                            @endif
                        </div>

                    @else
                        <!-- State: Belum Presensi -->
                        <div class="p-4 rounded-2xl border border-slate-700 bg-slate-950/40 text-center space-y-2">
                            <div class="w-10 h-10 mx-auto rounded-full bg-slate-800 text-slate-400 flex items-center justify-center">
                                <svg class="w-5 h-5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <span class="text-sm font-bold text-slate-200 block">Belum Melakukan Presensi</span>
                            <p class="text-xs text-slate-400 max-w-sm mx-auto">
                                Putra/putri Anda belum tercatat melakukan presensi check-in untuk hari ini.
                            </p>
                        </div>
                    @endif
                </div>

                <!-- REKAPITULASI KEHADIRAN (STATISTICS GRID) -->
                <div class="rounded-3xl bg-slate-800/90 border border-slate-700/80 p-5 shadow-xl space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-300">Rekapitulasi Kehadiran PKL</span>
                        <span class="text-[11px] font-bold text-indigo-400">
                            Tingkat Kehadiran: {{ $attendance_stats['rate'] }}%
                        </span>
                    </div>

                    <div class="grid grid-cols-4 gap-2">
                        <div class="p-2.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-center">
                            <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider block">Hadir</span>
                            <span class="font-mono text-base font-black text-white mt-0.5 block">{{ $attendance_stats['hadir'] }}</span>
                        </div>
                        <div class="p-2.5 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-center">
                            <span class="text-[10px] font-bold text-amber-400 uppercase tracking-wider block">Telat</span>
                            <span class="font-mono text-base font-black text-white mt-0.5 block">{{ $attendance_stats['terlambat'] }}</span>
                        </div>
                        <div class="p-2.5 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-center">
                            <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-wider block">Izin/Sakit</span>
                            <span class="font-mono text-base font-black text-white mt-0.5 block">{{ $attendance_stats['izin'] + $attendance_stats['sakit'] }}</span>
                        </div>
                        <div class="p-2.5 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-center">
                            <span class="text-[10px] font-bold text-rose-400 uppercase tracking-wider block">Alfa</span>
                            <span class="font-mono text-base font-black text-white mt-0.5 block">{{ $attendance_stats['alfa'] }}</span>
                        </div>
                    </div>
                </div>

                <!-- JURNAL AKTIVITAS PKL TERKINI -->
                <div class="rounded-3xl bg-slate-800/90 border border-slate-700/80 p-5 shadow-xl space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-300">Jurnal Aktivitas Terbaru</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-700 text-slate-300">
                            {{ $recent_journals->count() }} Kegiatan
                        </span>
                    </div>

                    @if($recent_journals->isNotEmpty())
                        <div class="space-y-2.5">
                            @foreach($recent_journals as $journal)
                                <div class="p-3.5 rounded-2xl bg-slate-950/40 border border-slate-700/70 space-y-2">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-bold text-slate-300">
                                            {{ \Carbon\Carbon::parse($journal->date)->translatedFormat('d M Y') }}
                                        </span>
                                        @if($journal->status === 'approved')
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">
                                                <svg class="w-3 h-3 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                <span>Disetujui</span>
                                            </span>
                                        @else
                                            <span class="text-[10px] font-bold text-slate-400 bg-slate-800 px-2 py-0.5 rounded-full">
                                                {{ ucfirst($journal->status) }}
                                            </span>
                                        @endif
                                    </div>

                                    <p class="text-xs text-slate-300 leading-relaxed line-clamp-3">
                                        {{ $journal->activity_description }}
                                    </p>

                                    <!-- Foto Dokumentasi Jika Ada -->
                                    @if($journal->photos->isNotEmpty())
                                        <div class="flex items-center gap-2 pt-1 overflow-x-auto">
                                            @foreach($journal->photos as $photo)
                                                <a href="{{ asset('storage/' . $photo->photo_path) }}" target="_blank" class="shrink-0 w-12 h-12 rounded-xl overflow-hidden border border-slate-700 bg-slate-800 hover:opacity-80 transition-opacity">
                                                    <img src="{{ asset('storage/' . $photo->photo_path) }}" alt="Foto Kegiatan" class="w-full h-full object-cover">
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-4 rounded-2xl border border-slate-700/60 bg-slate-950/30 text-center text-xs text-slate-400">
                            Belum ada catatan jurnal harian yang dimasukkan.
                        </div>
                    @endif
                </div>

                <!-- KONTAK & PEMBIMBING -->
                <div class="rounded-3xl bg-slate-800/90 border border-slate-700/80 p-5 shadow-xl space-y-3">
                    <span class="text-xs font-bold text-slate-300 block">Informasi Pembimbing PKL</span>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                        <div class="p-3 rounded-2xl bg-slate-950/40 border border-slate-800 space-y-1">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Guru Pembimbing Sekolah</span>
                            <span class="font-bold text-white block">{{ $spkl->teacher->user->name ?? 'Belum Ditugaskan' }}</span>
                        </div>

                        <div class="p-3 rounded-2xl bg-slate-950/40 border border-slate-800 space-y-1">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Mentor Industri (DUDI)</span>
                            <span class="font-bold text-white block">{{ $spkl->supervisor->user->name ?? 'Pembimbing Lapangan' }}</span>
                            <span class="text-[11px] text-slate-400 truncate block">{{ $spkl->company->name ?? '-' }}</span>
                        </div>
                    </div>
                </div>

            @endif

        @endif

        <!-- Privacy & Read-Only Notice Footer Card -->
        <div class="p-4 rounded-3xl bg-slate-950/50 border border-slate-800/80 text-center space-y-1.5">
            <div class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-slate-400">
                <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <span>Akses Khusus Orang Tua (Hanya Baca / Read-Only)</span>
            </div>
            <p class="text-[10px] text-slate-500 leading-relaxed">
                Halaman ini diformulasikan untuk memudahkan orang tua memantau kedisiplinan dan keselamatan siswa selama PKL tanpa perlu mengingat akun atau kata sandi.
            </p>
            <div class="text-[10px] font-bold text-slate-400 pt-1">
                VokaTrack &bull; Sistem Monitoring PKL Vokasi Terintegrasi
            </div>
        </div>

    </main>

</body>
</html>
