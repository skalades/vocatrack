@php
    $teacherName = auth()->user()->name ?? 'Guru Pembimbing';
    $nameParts = explode(' ', trim($teacherName));
    $teacherFirstName = $nameParts[0];
    $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
    $verifTotal = $pendingRequests + $pendingJournals;
    $attendancePct = $attendancePct ?? ($expectedToday > 0 ? (int) round($todayPresentCount / $expectedToday * 100) : 0);
    $stateMeta = [
        'hadir'       => ['label' => 'Hadir',            'pill' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'dot' => 'bg-emerald-500'],
        'terlambat'   => ['label' => 'Terlambat',        'pill' => 'bg-amber-50 text-amber-700 border-amber-200',       'dot' => 'bg-amber-500'],
        'dinas_luar'  => ['label' => 'Dinas Luar',       'pill' => 'bg-teal-50 text-teal-700 border-teal-200',          'dot' => 'bg-teal-500'],
        'luar_kantor' => ['label' => 'Luar Kantor',      'pill' => 'bg-teal-50 text-teal-700 border-teal-200',          'dot' => 'bg-teal-500'],
        'izin'        => ['label' => 'Izin',             'pill' => 'bg-sky-50 text-sky-700 border-sky-200',             'dot' => 'bg-sky-500'],
        'sakit'       => ['label' => 'Sakit',            'pill' => 'bg-purple-50 text-purple-700 border-purple-200',    'dot' => 'bg-purple-500'],
        'pengajuan'   => ['label' => 'Ajukan Izin',      'pill' => 'bg-sky-50 text-sky-700 border-sky-200',             'dot' => 'bg-sky-400'],
        'alfa'        => ['label' => 'Alfa',             'pill' => 'bg-rose-50 text-rose-700 border-rose-200',          'dot' => 'bg-rose-500'],
        'belum'       => ['label' => 'Belum Presensi',   'pill' => 'bg-slate-100 text-slate-600 border-slate-200',      'dot' => 'bg-slate-400'],
        'belum_telat' => ['label' => 'Belum · Lewat Jam','pill' => 'bg-orange-50 text-orange-700 border-orange-200',    'dot' => 'bg-orange-500'],
        'libur'       => ['label' => 'Libur Sekolah',    'pill' => 'bg-slate-50 text-slate-500 border-slate-200',       'dot' => 'bg-slate-300'],
        'libur_dudi'  => ['label' => 'Libur DU/DI',      'pill' => 'bg-slate-50 text-slate-500 border-slate-200',       'dot' => 'bg-slate-300'],
        'belum_mulai' => ['label' => 'Belum Mulai PKL',  'pill' => 'bg-slate-100 text-slate-500 border-slate-200',      'dot' => 'bg-slate-400'],
    ];
@endphp

<div class="space-y-3.5 pb-10 select-none animate-fade-in">

    <!-- 1. QUICK ACTIONS (4 Grid dalam 1 Baris) -->
        <section class="grid grid-cols-4 gap-2">
            <a href="{{ route('guru.monitoring') }}" class="flex flex-col items-center justify-center gap-1.5 p-2.5 rounded-2xl bg-white border border-slate-200/80 shadow-2xs hover:border-[#0B2B25] active:scale-95 transition-all">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-800 flex items-center justify-center">
                    <svg class="w-5 h-5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <span class="text-[10px] font-extrabold text-slate-800 text-center leading-tight">Presensi</span>
            </a>

            <a href="{{ route('guru.journals') }}" class="flex flex-col items-center justify-center gap-1.5 p-2.5 rounded-2xl bg-white border border-slate-200/80 shadow-2xs hover:border-[#0B2B25] active:scale-95 transition-all relative">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-800 flex items-center justify-center relative">
                    <svg class="w-5 h-5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    @if($verifTotal > 0)
                        <span class="absolute -top-1 -right-1 min-w-[16px] h-4 px-1 rounded-full bg-rose-500 text-white text-[9px] font-black flex items-center justify-center">
                            {{ $verifTotal > 99 ? '99+' : $verifTotal }}
                        </span>
                    @endif
                </div>
                <span class="text-[10px] font-extrabold text-slate-800 text-center leading-tight">Verifikasi</span>
            </a>

            <a href="{{ route('guru.supervisions') }}" class="flex flex-col items-center justify-center gap-1.5 p-2.5 rounded-2xl bg-white border border-slate-200/80 shadow-2xs hover:border-[#0B2B25] active:scale-95 transition-all">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-800 flex items-center justify-center">
                    <svg class="w-5 h-5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <span class="text-[10px] font-extrabold text-slate-800 text-center leading-tight">Supervisi</span>
            </a>

            <a href="{{ route('guru.assessments') }}" class="flex flex-col items-center justify-center gap-1.5 p-2.5 rounded-2xl bg-white border border-slate-200/80 shadow-2xs hover:border-[#0B2B25] active:scale-95 transition-all">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-800 flex items-center justify-center">
                    <svg class="w-5 h-5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                <span class="text-[10px] font-extrabold text-slate-800 text-center leading-tight">Penilaian</span>
            </a>
        </section>

        <!-- 3. DARURAT SOS ALERT (HANYA JIKA ADA) -->
        @if($criticalIncidentsCount > 0)
            <section class="p-3.5 rounded-2xl bg-gradient-to-r from-[#B42318] to-rose-700 text-white shadow-md flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h4 class="text-xs font-extrabold leading-none">Laporan Darurat SOS</h4>
                        <p class="text-[10px] text-white/80 mt-1 truncate">{{ $criticalIncidentsCount }} siswa butuh tindak lanjut segera</p>
                    </div>
                </div>
                <a href="{{ route('guru.incidents', ['urgency' => 'critical']) }}" class="px-3 py-1.5 rounded-xl bg-white text-[#B42318] font-extrabold text-[11px] shrink-0 hover:bg-slate-100 transition-colors shadow-xs">
                    Tangani
                </a>
            </section>
        @endif

        <!-- 4. PERMOHONAN RESET SANDI (HANYA JIKA ADA) -->
        @if(isset($pendingResetRequests) && $pendingResetRequests->isNotEmpty())
            <section class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-950 flex items-center justify-between gap-2.5">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-7 h-7 rounded-lg bg-amber-200 text-amber-900 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-extrabold text-amber-950 leading-tight">{{ $pendingResetRequests->count() }} Permohonan Reset Sandi</p>
                        <p class="text-[10px] text-amber-800 truncate">Siswa bimbingan meminta reset</p>
                    </div>
                </div>
                <a href="{{ route('guru.monitoring') }}" class="px-2.5 py-1 rounded-lg bg-amber-200 text-amber-950 font-bold text-[10px] shrink-0 hover:bg-amber-300 transition-colors">
                    Tinjau
                </a>
            </section>
        @endif

        <!-- 5. REKAP KEHADIRAN HARI INI (4 KOTAK RINGKAS) -->
        <section class="bg-white rounded-2xl border border-slate-200/80 p-3.5 space-y-2.5 shadow-2xs">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-extrabold text-slate-900">Rekap Kehadiran Hari Ini</h3>
                    <p class="text-[10px] text-slate-400">{{ now()->isoFormat('dddd, D MMMM Y') }}</p>
                </div>
                <a href="{{ route('guru.monitoring') }}" class="text-[11px] font-extrabold text-[#0B2B25] hover:text-[#12382F] flex items-center gap-1">
                    <span>Detail</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>

            <div class="grid grid-cols-4 gap-1.5 text-center">
                <div class="bg-emerald-50 border border-emerald-100 rounded-xl py-2">
                    <span class="font-space text-base font-extrabold text-emerald-700 block leading-tight">{{ $todaySummary['hadir'] }}</span>
                    <span class="text-[9px] font-bold text-emerald-600 uppercase">Hadir</span>
                </div>
                <div class="bg-amber-50 border border-amber-100 rounded-xl py-2">
                    <span class="font-space text-base font-extrabold text-amber-700 block leading-tight">{{ $todaySummary['terlambat'] }}</span>
                    <span class="text-[9px] font-bold text-amber-600 uppercase">Telat</span>
                </div>
                <div class="bg-sky-50 border border-sky-100 rounded-xl py-2">
                    <span class="font-space text-base font-extrabold text-sky-700 block leading-tight">{{ $todaySummary['izin_sakit'] }}</span>
                    <span class="text-[9px] font-bold text-sky-600 uppercase">Izin</span>
                </div>
                <div class="bg-rose-50 border border-rose-100 rounded-xl py-2">
                    <span class="font-space text-base font-extrabold text-rose-700 block leading-tight">{{ $todaySummary['alfa'] }}</span>
                    <span class="text-[9px] font-bold text-rose-600 uppercase">Alfa</span>
                </div>
            </div>
        </section>

        <!-- 6. PERHATIAN HARI INI (MAKSIMAL 4 SISWA, BUKAN SELURUH TABEL) -->
        @php
            $priorityRows = $todayRows->take(4);
        @endphp
        <section class="bg-white rounded-2xl border border-slate-200/80 p-3.5 space-y-2.5 shadow-2xs">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-extrabold text-slate-900">Perhatian Hari Ini</h3>
                <a href="{{ route('guru.monitoring') }}" class="text-[11px] font-bold text-slate-500 hover:text-slate-800">
                    Semua ({{ $totalStudents }}) &rarr;
                </a>
            </div>

            <div class="space-y-2">
                @forelse($priorityRows as $row)
                    @php
                        $sp = $row['spkl'];
                        $student = $sp->student;
                        $st = $row['state'];
                        $meta = $stateMeta[$st] ?? $stateMeta['belum'];
                        $studentInitial = collect(explode(' ', trim($student->user->name ?? 'S')))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
                    @endphp
                    <div class="flex items-center justify-between gap-2.5 p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                        <div class="flex items-center space-x-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-white border border-slate-200 text-slate-800 font-extrabold text-[11px] flex items-center justify-center shrink-0">
                                {{ $studentInitial }}
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-bold text-slate-900 truncate text-xs">{{ $student->user->name ?? 'Siswa' }}</h4>
                                <p class="text-[10px] text-slate-400 font-mono truncate">{{ $sp->company->name ?? 'Mitra DU/DI' }}</p>
                            </div>
                        </div>
                        
                        <div class="shrink-0 text-right">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold border {{ $meta['pill'] }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $meta['dot'] }}"></span>
                                {{ $meta['label'] }}
                            </span>
                            @if($row['flagged'])
                                <span class="block text-[8px] font-black text-rose-600 uppercase tracking-tight mt-0.5">Luar Radius</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 text-center py-4">Belum ada data presensi hari ini.</p>
                @endforelse
            </div>

            <a href="{{ route('guru.monitoring') }}" class="w-full py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold text-xs flex items-center justify-center gap-1.5 transition-colors">
                <span>Buka Monitoring Lengkap ({{ $totalStudents }} Siswa)</span>
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </section>

        <!-- 7. PWA GURU & NOTIFIKASI WEB PUSH -->
        <x-teacher.pwa-card />

</div>
