<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <title>
        @if($status === 'active' && isset($student))
            Monitoring PKL: {{ $student->user->name }} — VokaTrack
        @else
            Portal Monitoring Orang Tua — VokaTrack
        @endif
    </title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
        .font-space { font-family: 'Space Grotesk', system-ui, sans-serif; }
    </style>
</head>
<body class="bg-[#F1F4EC] text-[#10211D] min-h-screen flex flex-col justify-between antialiased selection:bg-[#C6F26B] selection:text-[#0B2B25]">

    <!-- Top Brand Header Bar -->
    <header class="bg-[#0B2B25] text-white border-b border-white/10 sticky top-0 z-30 shadow-xs">
        <div class="max-w-xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-[#C6F26B] text-[#0B2B25] flex items-center justify-center font-space font-extrabold text-sm shadow-xs shrink-0">
                    V
                </div>
                <div>
                    <span class="text-sm font-space font-bold tracking-tight text-white block">VokaTrack</span>
                    <span class="text-[10px] font-bold text-[#A9C2B9] block -mt-1 tracking-wider uppercase">Portal Monitoring Orang Tua</span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @if($status === 'active')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-[#C6F26B]/20 text-[#C6F26B] border border-[#C6F26B]/35">
                        <span class="w-2 h-2 rounded-full bg-[#C6F26B] animate-pulse"></span>
                        <span>Akses Terhubung</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-rose-500/20 text-rose-200 border border-rose-500/30">
                        <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                        <span>Tautan Tidak Aktif</span>
                    </span>
                @endif
            </div>
        </div>
    </header>

    <!-- Main Container (Mobile-First 1-column layout) -->
    <main class="flex-1 max-w-xl w-full mx-auto px-4 py-5 space-y-4">

        {{-- 1. ERROR / INACTIVE / NOT FOUND STATE --}}
        @if($status === 'not_found' || $status === 'disabled')
            <div class="bg-white rounded-[26px] border border-[#E5ECE9] p-6 sm:p-8 text-center shadow-xs">
                <div class="w-16 h-16 mx-auto rounded-full bg-[#FEE4E2] text-[#B42318] flex items-center justify-center mb-4 shadow-xs">
                    <svg class="w-8 h-8 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>

                <h1 class="text-lg font-space font-bold text-[#10211D] tracking-tight">
                    {{ $status === 'disabled' ? 'Portal Dinonaktifkan' : 'Tautan Tidak Ditemukan' }}
                </h1>

                <p class="text-xs sm:text-sm text-[#5A6B65] mt-2 leading-relaxed max-w-md mx-auto">
                    {{ $message ?? 'Tautan monitoring ini tidak valid atau telah diperbarui oleh siswa demi keamanan.' }}
                </p>

                <div class="mt-6 p-4 rounded-2xl bg-[#F8FAF9] border border-[#E5ECE9] text-left text-xs text-[#5A6B65] space-y-2">
                    <div class="flex items-start gap-2 text-[#0B2B25] font-bold">
                        <svg class="w-4 h-4 shrink-0 mt-0.5 text-[#0F7A66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Petunjuk untuk Orang Tua / Wali:</span>
                    </div>
                    <p class="leading-relaxed text-[#5A6B65] pl-6">
                        Silakan hubungi putra/putri Anda dan minta mereka membuka menu <strong>Profil &raquo; Bagikan Link Orang Tua</strong> di aplikasi VokaTrack mereka untuk mengirimkan link terbaru via WhatsApp.
                    </p>
                </div>
            </div>

        {{-- 2. ACTIVE MONITORING STATE --}}
        @else

            <!-- Student Identity & School Banner Card -->
            <div class="relative overflow-hidden rounded-[26px] bg-[#0B2B25] text-white p-5 shadow-sm border border-white/10">
                <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-[#C6F26B]/10 blur-2xl pointer-events-none"></div>

                <div class="flex items-center gap-3.5">
                    <div class="w-14 h-14 rounded-2xl bg-[#C6F26B] text-[#0B2B25] font-space font-extrabold text-2xl flex items-center justify-center shadow-xs shrink-0">
                        {{ strtoupper(substr($student->user->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="text-[10px] font-extrabold uppercase tracking-widest text-[#A9C2B9] block">
                            {{ $student->user->school->name ?? 'SMK Indonesia' }}
                        </span>
                        <h1 class="text-base sm:text-lg font-space font-bold text-white truncate">
                            {{ $student->user->name }}
                        </h1>
                        <p class="text-xs text-[#D1E3DC] font-medium truncate mt-0.5">
                            Kelas {{ $student->class->name ?? '-' }} &bull; {{ $student->class->major->name ?? '-' }}
                        </p>
                    </div>
                </div>

                <!-- PKL Placement Info Bar -->
                @if($has_pkl && $spkl)
                    <div class="mt-4 pt-3.5 border-t border-white/15 grid grid-cols-2 gap-2 text-xs">
                        <div class="bg-white/[0.08] rounded-2xl p-2.5 border border-white/10">
                            <span class="text-[10px] font-bold text-[#A9C2B9] uppercase tracking-wider block">Tempat PKL</span>
                            <span class="font-bold text-white truncate block mt-0.5">{{ $spkl->company->name ?? '-' }}</span>
                        </div>
                        <div class="bg-white/[0.08] rounded-2xl p-2.5 border border-white/10">
                            <span class="text-[10px] font-bold text-[#A9C2B9] uppercase tracking-wider block">Status PKL</span>
                            <span class="inline-flex items-center gap-1.5 font-bold text-[#C6F26B] mt-0.5">
                                <span class="w-2 h-2 rounded-full bg-[#C6F26B]"></span>
                                {{ ucfirst($spkl->status) }}
                            </span>
                        </div>
                    </div>
                @else
                    <div class="mt-4 pt-3 border-t border-white/15 text-xs text-amber-200 flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Siswa belum memiliki penugasan periode PKL yang aktif saat ini.</span>
                    </div>
                @endif
            </div>

            @if($has_pkl && $spkl)
                <!-- LIVE STATUS HARI INI (HERO CARD) -->
                <div class="rounded-[26px] bg-white border border-[#E5ECE9] p-5 shadow-xs space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#0F7A66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-xs font-bold text-[#10211D]">Status Kehadiran Hari Ini</span>
                        </div>
                        <span class="text-[11px] font-semibold text-[#5A6B65]">
                            {{ \Carbon\Carbon::today()->translatedFormat('l, d F Y') }}
                        </span>
                    </div>

                    @if($today_attendance)
                        @php
                            $statusStyle = match($today_attendance->status) {
                                'hadir' => [
                                    'bg' => 'bg-[#DDF3EC] border-[#A3DFC8] text-[#0B5E4E]',
                                    'badge' => 'Hadir Tepat Waktu',
                                    'desc' => 'Putra/putri Anda telah berada di lokasi PKL.',
                                ],
                                'terlambat' => [
                                    'bg' => 'bg-[#FFF1D6] border-[#FFE0A3] text-[#7A4B00]',
                                    'badge' => 'Hadir (Terlambat)',
                                    'desc' => 'Siswa hadir melewati batas toleransi jam masuk.',
                                ],
                                'izin' => [
                                    'bg' => 'bg-[#E8F0FE] border-[#C2D7FA] text-[#1967D2]',
                                    'badge' => 'Izin Resmi Tercatat',
                                    'desc' => 'Permohonan izin telah disetujui sekolah & DU/DI.',
                                ],
                                'sakit' => [
                                    'bg' => 'bg-[#FFF1D6] border-[#FFE0A3] text-[#7A4B00]',
                                    'badge' => 'Sakit Tercatat',
                                    'desc' => 'Keterangan sakit telah dilaporkan.',
                                ],
                                'dinas_luar', 'luar_kantor' => [
                                    'bg' => 'bg-[#E0F2FE] border-[#BAE6FD] text-[#0369A1]',
                                    'badge' => 'Tugas Luar Kantor',
                                    'desc' => 'Siswa menjalankan tugas luar bersama instansi.',
                                ],
                                default => [
                                    'bg' => 'bg-[#FEE4E2] border-[#FECDCA] text-[#B42318]',
                                    'badge' => 'Tidak Hadir / Alfa',
                                    'desc' => 'Belum ada keterangan resmi kehadiran hari ini.',
                                ],
                            };
                        @endphp

                        <div class="p-4 rounded-2xl border {{ $statusStyle['bg'] }} flex items-start justify-between gap-3">
                            <div class="space-y-1">
                                <span class="text-sm font-space font-bold block">
                                    {{ $statusStyle['badge'] }}
                                </span>
                                <p class="text-xs opacity-90">
                                    {{ $statusStyle['desc'] }}
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-[10px] font-bold uppercase tracking-wider block opacity-75">Jam Masuk</span>
                                <span class="font-space text-sm font-bold block mt-0.5">
                                    {{ $today_attendance->checkin_time ? substr($today_attendance->checkin_time, 0, 5) . ' WIB' : '-' }}
                                </span>
                            </div>
                        </div>

                        <!-- Check-out Status Row -->
                        <div class="flex items-center justify-between text-xs px-2 pt-1 text-[#5A6B65]">
                            <span>Jam Kepulangan:</span>
                            @if($today_attendance->checkout_time)
                                <span class="font-space font-bold text-[#0B5E4E]">
                                    {{ substr($today_attendance->checkout_time, 0, 5) }} WIB (Sudah Presensi Pulang)
                                </span>
                            @else
                                <span class="text-[#5A6B65] font-medium italic">
                                    Belum jam pulang kerja
                                </span>
                            @endif
                        </div>

                    @else
                        <!-- State: Belum Presensi -->
                        <div class="p-5 rounded-2xl border border-[#E5ECE9] bg-[#F8FAF9] text-center space-y-2">
                            <div class="w-12 h-12 mx-auto rounded-full bg-[#FFF1D6] text-[#7A4B00] flex items-center justify-center shadow-xs">
                                <svg class="w-6 h-6 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <span class="text-sm font-space font-bold text-[#10211D] block">Belum Melakukan Presensi</span>
                            <p class="text-xs text-[#5A6B65] max-w-sm mx-auto leading-relaxed">
                                Putra/putri Anda belum tercatat melakukan presensi check-in untuk hari ini.
                            </p>
                        </div>
                    @endif
                </div>

                <!-- REKAPITULASI KEHADIRAN (STATISTICS GRID) -->
                <div class="rounded-[26px] bg-white border border-[#E5ECE9] p-5 shadow-xs space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-[#10211D]">Rekapitulasi Kehadiran PKL</span>
                        <span class="text-[11px] font-bold text-[#0B5E4E] bg-[#DDF3EC] px-2.5 py-0.5 rounded-full border border-[#A3DFC8]">
                            Tingkat Kehadiran: {{ $attendance_stats['rate'] }}%
                        </span>
                    </div>

                    <div class="grid grid-cols-4 gap-2">
                        <div class="p-2.5 rounded-2xl bg-[#DDF3EC]/80 border border-[#A3DFC8] text-center">
                            <span class="text-[10px] font-extrabold text-[#0B5E4E] uppercase tracking-wider block">Hadir</span>
                            <span class="font-space text-base font-black text-[#0B5E4E] mt-0.5 block">{{ $attendance_stats['hadir'] }}</span>
                        </div>
                        <div class="p-2.5 rounded-2xl bg-[#FFF1D6]/80 border border-[#FFE0A3] text-center">
                            <span class="text-[10px] font-extrabold text-[#7A4B00] uppercase tracking-wider block">Telat</span>
                            <span class="font-space text-base font-black text-[#7A4B00] mt-0.5 block">{{ $attendance_stats['terlambat'] }}</span>
                        </div>
                        <div class="p-2.5 rounded-2xl bg-[#E8F0FE]/80 border border-[#C2D7FA] text-center">
                            <span class="text-[10px] font-extrabold text-[#1967D2] uppercase tracking-wider block">Izin/Sakit</span>
                            <span class="font-space text-base font-black text-[#1967D2] mt-0.5 block">{{ $attendance_stats['izin'] + $attendance_stats['sakit'] }}</span>
                        </div>
                        <div class="p-2.5 rounded-2xl bg-[#FEE4E2]/80 border border-[#FECDCA] text-center">
                            <span class="text-[10px] font-extrabold text-[#B42318] uppercase tracking-wider block">Alfa</span>
                            <span class="font-space text-base font-black text-[#B42318] mt-0.5 block">{{ $attendance_stats['alfa'] }}</span>
                        </div>
                    </div>
                </div>

                <!-- JURNAL AKTIVITAS PKL TERKINI -->
                <div class="rounded-[26px] bg-white border border-[#E5ECE9] p-5 shadow-xs space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-[#10211D]">Jurnal Aktivitas Terbaru</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-[#F1F4EC] text-[#5A6B65]">
                            {{ $recent_journals->count() }} Kegiatan
                        </span>
                    </div>

                    @if($recent_journals->isNotEmpty())
                        <div class="space-y-2.5">
                            @foreach($recent_journals as $journal)
                                <div class="p-3.5 rounded-2xl bg-[#F8FAF9] border border-[#E5ECE9] space-y-2">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-bold text-[#10211D]">
                                            {{ \Carbon\Carbon::parse($journal->date)->translatedFormat('d M Y') }}
                                        </span>
                                        @if($journal->status === 'approved')
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-[#0B5E4E] bg-[#DDF3EC] px-2 py-0.5 rounded-full border border-[#A3DFC8]">
                                                <svg class="w-3 h-3 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                <span>Disetujui</span>
                                            </span>
                                        @else
                                            <span class="text-[10px] font-bold text-[#5A6B65] bg-slate-200/80 px-2 py-0.5 rounded-full">
                                                {{ ucfirst($journal->status) }}
                                            </span>
                                        @endif
                                    </div>

                                    <p class="text-xs text-[#5A6B65] leading-relaxed line-clamp-3">
                                        {{ $journal->activity_description }}
                                    </p>

                                    <!-- Foto Dokumentasi Jika Ada -->
                                    @if($journal->photos->isNotEmpty())
                                        <div class="flex items-center gap-2 pt-1 overflow-x-auto">
                                            @foreach($journal->photos as $photo)
                                                <a href="{{ $photo->parentUrl($student->parent_token) }}" target="_blank" class="shrink-0 w-12 h-12 rounded-xl overflow-hidden border border-[#E5ECE9] bg-white hover:opacity-80 transition-opacity">
                                                    <img src="{{ $photo->parentUrl($student->parent_token) }}" alt="Foto Kegiatan" class="w-full h-full object-cover">
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-4 rounded-2xl border border-[#E5ECE9] bg-[#F8FAF9] text-center text-xs text-[#5A6B65]">
                            Belum ada catatan jurnal harian yang dimasukkan.
                        </div>
                    @endif
                </div>

                <!-- KONTAK & PEMBIMBING -->
                <div class="rounded-[26px] bg-white border border-[#E5ECE9] p-5 shadow-xs space-y-3">
                    <span class="text-xs font-bold text-[#10211D] block">Informasi Pembimbing PKL</span>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                        <div class="p-3 rounded-2xl bg-[#F8FAF9] border border-[#E5ECE9] space-y-1">
                            <span class="text-[10px] font-bold text-[#5A6B65] uppercase tracking-wider block">Guru Pembimbing Sekolah</span>
                            <span class="font-bold text-[#10211D] block">{{ $spkl->teacher->user->name ?? 'Belum Ditugaskan' }}</span>
                        </div>

                        <div class="p-3 rounded-2xl bg-[#F8FAF9] border border-[#E5ECE9] space-y-1">
                            <span class="text-[10px] font-bold text-[#5A6B65] uppercase tracking-wider block">Mentor Industri (DUDI)</span>
                            <span class="font-bold text-[#10211D] block">{{ $spkl->supervisor->user->name ?? 'Pembimbing Lapangan' }}</span>
                            <span class="text-[11px] text-[#5A6B65] truncate block">{{ $spkl->company->name ?? '-' }}</span>
                        </div>
                    </div>
                </div>

            @endif

        @endif

        <!-- Privacy & Read-Only Notice Footer Card -->
        <div class="p-4 rounded-[26px] bg-white border border-[#E5ECE9] text-center space-y-1.5 shadow-xs">
            <div class="inline-flex items-center gap-1.5 text-[11px] font-bold text-[#10211D]">
                <svg class="w-3.5 h-3.5 text-[#0F7A66]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <span>Akses Khusus Orang Tua (Hanya Baca / Read-Only)</span>
            </div>
            <p class="text-[10px] text-[#5A6B65] leading-relaxed">
                Halaman ini diformulasikan untuk memudahkan orang tua memantau kedisiplinan dan keselamatan siswa selama PKL tanpa perlu mengingat akun atau kata sandi.
            </p>
            <div class="text-[10px] font-bold text-[#0B2B25] pt-1">
                VokaTrack &bull; Sistem Monitoring PKL Vokasi Terintegrasi
            </div>
        </div>

    </main>

    <footer class="max-w-xl w-full mx-auto px-4 pb-6 pt-1">
        <x-creator-branding variant="light" class="justify-center text-[#5A6B65]" />
    </footer>

</body>
</html>