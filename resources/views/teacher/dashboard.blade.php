@extends('layouts.app')

@section('title', 'Dashboard Guru Pembimbing — VokaTrack')
@section('hide_header', true)

@php
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
    ];
    $urgencyMeta = [
        'critical' => ['label' => 'SOS',    'pill' => 'bg-[#B42318] text-white'],
        'high'     => ['label' => 'Tinggi', 'pill' => 'bg-rose-100 text-rose-700'],
        'medium'   => ['label' => 'Sedang', 'pill' => 'bg-amber-100 text-amber-700'],
        'low'      => ['label' => 'Rendah', 'pill' => 'bg-slate-100 text-slate-600'],
    ];
    $initials = fn ($name) => collect(explode(' ', trim($name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $fmtTime = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format('H.i') : null;
    $verifTotal = $pendingRequests + $pendingJournals;
    $attendancePct = $expectedToday > 0 ? (int) round($todayPresentCount / $expectedToday * 100) : 0;
    $trendMax = max(1, $totalStudents);
    $chevron = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>';
@endphp

@section('content')
<div class="w-full space-y-5">
    <h1 class="sr-only">Dashboard Guru Pembimbing</h1>

    {{-- ===================== HERO ===================== --}}
    <section data-reveal data-spotlight class="relative overflow-hidden bg-[#0B2B25] rounded-[28px] text-white shadow-xl shadow-[#0B2B25]/20">
        <div class="absolute -right-24 -top-24 w-96 h-96 rounded-full border border-white/[0.07] pointer-events-none"></div>
        <div class="absolute -right-6 -top-6 w-60 h-60 rounded-full border border-white/[0.07] pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-24 w-72 h-72 rounded-full bg-[#C6F26B]/10 blur-3xl pointer-events-none"></div>

        <div class="relative grid grid-cols-1 lg:grid-cols-12 gap-6 p-6 sm:p-7">
            <div class="lg:col-span-8 space-y-5 min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-[#C6F26B] text-[#0B2B25] uppercase tracking-wider">Guru Pembimbing</span>
                    <span class="text-xs font-semibold text-[#A9C2B9] truncate">{{ auth()->user()->school->name ?? 'SMK' }}</span>
                    @if($teacher->nip)
                        <span class="text-[11px] text-white/40 font-mono">NIP {{ $teacher->nip }}</span>
                    @endif
                </div>

                <div class="space-y-1.5">
                    <h2 class="font-display text-2xl sm:text-[28px] font-bold tracking-tight leading-tight">Halo, {{ auth()->user()->name }}</h2>
                    <p class="text-sm text-[#A9C2B9] font-medium max-w-2xl leading-relaxed">
                        Anda membimbing <strong class="text-white">{{ $totalStudents }} siswa</strong> di <strong class="text-white">{{ $totalCompanies }} DU/DI</strong>.
                        @if($verifTotal > 0 || $openIncidentsCount > 0)
                            Ada <strong class="text-[#C6F26B]">{{ $verifTotal + $openIncidentsCount }} hal</strong> yang menunggu tindakan Anda hari ini.
                        @else
                            Semua verifikasi sudah tuntas — kerja bagus.
                        @endif
                    </p>
                </div>

                @if($periodProgress)
                    <div class="rounded-2xl bg-white/[0.06] border border-white/10 p-4 space-y-2.5">
                        <div class="flex flex-wrap items-baseline justify-between gap-2 text-xs">
                            <span class="font-bold text-white">{{ $periodProgress['name'] }}</span>
                            <span class="text-[#A9C2B9] font-medium">
                                {{ $periodProgress['start']->isoFormat('D MMM') }} – {{ $periodProgress['end']->isoFormat('D MMM Y') }}
                            </span>
                        </div>
                        <div class="h-2 rounded-full bg-white/10 overflow-hidden">
                            <div data-grow-x class="h-full rounded-full bg-[#C6F26B]" style="width: {{ $periodProgress['percent'] }}%"></div>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-[#A9C2B9] font-semibold">
                            <span>Hari ke-<span data-counter class="font-space text-white">{{ $periodProgress['day'] }}</span> dari {{ $periodProgress['total'] }}</span>
                            <span>Sisa <span data-counter class="font-space text-[#C6F26B]">{{ $periodProgress['remaining'] }}</span> hari</span>
                        </div>
                    </div>
                @endif

                <div class="flex flex-wrap items-center gap-2.5">
                    <a href="{{ route('guru.monitoring', ['date' => now()->toDateString()]) }}" data-ripple class="group inline-flex items-center gap-2 pl-4 pr-1.5 py-1.5 rounded-full bg-[#C6F26B] hover:bg-[#b5e855] text-[#0B2B25] text-xs font-extrabold shadow-md shadow-black/20 transition-all hover:-translate-y-0.5">
                        <span>Monitoring Absensi</span>
                        <span class="w-7 h-7 rounded-full bg-[#0B2B25] text-[#C6F26B] flex items-center justify-center group-hover:translate-x-0.5 transition-transform">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </span>
                    </a>
                    <a href="{{ route('guru.journals') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 hover:bg-white/20 border border-white/15 text-xs font-bold transition-colors">
                        Review Jurnal
                        @if($pendingJournals > 0)<span class="px-1.5 rounded-full bg-[#C6F26B] text-[#0B2B25] font-space text-[11px]">{{ $pendingJournals }}</span>@endif
                    </a>
                    <a href="{{ route('guru.announcements') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 hover:bg-white/20 border border-white/15 text-xs font-bold transition-colors">
                        Buat Pengumuman
                    </a>
                </div>
            </div>

            {{-- Ringkasan hari ini --}}
            <div class="lg:col-span-4 flex flex-col justify-between gap-4 rounded-3xl bg-white/[0.06] border border-white/10 p-5">
                <div class="flex items-center justify-between text-[11px] font-semibold text-[#A9C2B9]">
                    <span id="banner-live-date">{{ now()->isoFormat('dddd, D MMMM Y') }}</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-[#C6F26B] vt-live-dot"></span><span class="font-space font-bold text-[#C6F26B]" id="banner-live-clock">{{ now()->format('H:i:s') }} WIB</span></span>
                </div>
                <div class="flex items-center gap-5">
                    <div class="relative w-24 h-24 shrink-0">
                        <svg class="w-24 h-24 -rotate-90" viewBox="0 0 36 36">
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="rgba(255,255,255,.1)" stroke-width="3.2"/>
                            <circle data-ring cx="18" cy="18" r="15.5" fill="none" stroke="#C6F26B" stroke-width="3.2" stroke-linecap="round"
                                    stroke-dasharray="{{ round(97.4 * $attendancePct / 100, 1) }} 97.4"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span data-counter class="font-space text-xl font-bold leading-none">{{ $attendancePct }}%</span>
                            <span class="text-[9px] text-[#A9C2B9] font-bold uppercase mt-0.5">Hadir</span>
                        </div>
                    </div>
                    <div class="min-w-0 space-y-1">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-[#A9C2B9]">Presensi hari ini</p>
                        <p class="font-space text-4xl font-bold leading-none"><span data-counter>{{ $todayPresentCount }}</span><span class="text-white/40 text-2xl">/{{ $expectedToday }}</span></p>
                        <p class="text-[11px] text-[#A9C2B9] font-medium">
                            @if($isSchoolHoliday) Hari libur sekolah
                            @elseif($expectedToday === 0) Tidak ada jadwal kerja hari ini
                            @else {{ $todaySummary['belum'] }} belum · {{ $checkedOutCount }} sudah pulang
                            @endif
                        </p>
                    </div>
                </div>
                <div class="grid grid-cols-4 gap-1.5 text-center">
                    @foreach([['Tepat', $todaySummary['hadir'], 'text-[#C6F26B]'], ['Telat', $todaySummary['terlambat'], 'text-amber-300'], ['Izin/Skt', $todaySummary['izin_sakit'], 'text-sky-300'], ['Alfa', $todaySummary['alfa'], 'text-rose-300']] as [$lbl, $val, $cls])
                        <div class="rounded-xl bg-white/[0.06] py-2">
                            <span data-counter class="font-space text-base font-bold {{ $cls }} block leading-none">{{ $val }}</span>
                            <span class="text-[9px] text-[#A9C2B9] font-bold uppercase">{{ $lbl }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== ALERTS ===================== --}}
    @if($criticalIncidentsCount > 0 || $flaggedTodayCount > 0 || $todaySummary['belum_telat'] > 0)
        <section data-reveal-stagger class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @if($criticalIncidentsCount > 0)
                <a href="{{ route('guru.incidents', ['urgency' => 'critical']) }}" class="relative overflow-hidden flex items-center gap-3.5 p-4 rounded-2xl bg-[#B42318] text-white shadow-lg shadow-[#B42318]/25 hover:-translate-y-0.5 transition-transform">
                    <span class="relative w-11 h-11 rounded-full bg-white text-[#B42318] font-space font-extrabold text-xs flex items-center justify-center shrink-0">
                        <span class="absolute inset-0 rounded-full bg-white/60 animate-sos-pulse"></span><span class="relative">SOS</span>
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-extrabold">{{ $criticalIncidentsCount }} laporan darurat terbuka</p>
                        <p class="text-[11px] text-white/80 font-medium">Segera tindak lanjuti dan catat kronologinya.</p>
                    </div>
                </a>
            @endif
            @if($flaggedTodayCount > 0)
                <a href="{{ route('guru.monitoring', ['date' => now()->toDateString()]) }}" class="flex items-center gap-3.5 p-4 rounded-2xl bg-[#2A0A08] text-white hover:-translate-y-0.5 transition-transform">
                    <span class="w-11 h-11 rounded-full bg-rose-500/20 text-rose-300 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-6.2-7-11a7 7 0 1114 0c0 4.8-7 11-7 11z"/><path stroke-linecap="round" d="M4 4l16 16"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-extrabold">{{ $flaggedTodayCount }} presensi lokasi janggal</p>
                        <p class="text-[11px] text-rose-200/80 font-medium">Fake GPS atau di luar radius DU/DI hari ini.</p>
                    </div>
                </a>
            @endif
            @if($todaySummary['belum_telat'] > 0)
                <a href="#presensi-hari-ini" class="flex items-center gap-3.5 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 hover:-translate-y-0.5 transition-transform">
                    <span class="w-11 h-11 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-extrabold">{{ $todaySummary['belum_telat'] }} siswa belum presensi</p>
                        <p class="text-[11px] text-amber-800/80 font-medium">Sudah melewati batas jam masuk DU/DI.</p>
                    </div>
                </a>
            @endif
        </section>
    @endif

    {{-- ===================== KPI ===================== --}}
    <section data-reveal-stagger class="grid grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-4">
        {{-- Verifikasi --}}
        <a href="{{ $pendingJournals >= $pendingRequests ? route('guru.journals') : route('guru.requests') }}" data-tilt class="group relative bg-white rounded-3xl p-5 border border-slate-200/70 hover:border-[#0B2B25]/30 hover:shadow-lg transition-all">
            <div class="flex items-start justify-between">
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Menunggu Verifikasi</p>
                <span class="w-9 h-9 rounded-xl {{ $verifTotal ? 'bg-[#C6F26B] text-[#0B2B25]' : 'bg-slate-100 text-slate-400' }} flex items-center justify-center">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7 4h10a2 2 0 012 2v14l-7-3-7 3V6a2 2 0 012-2z"/></svg>
                </span>
            </div>
            <p data-counter class="font-space text-4xl font-bold text-slate-900 mt-2 leading-none">{{ $verifTotal }}</p>
            <div class="flex flex-wrap gap-1.5 mt-3 text-[11px] font-bold">
                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ $pendingJournals }} jurnal</span>
                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ $pendingRequests }} izin/sakit</span>
            </div>
        </a>

        {{-- Kendala --}}
        <a href="{{ route('guru.incidents') }}" data-tilt class="group relative bg-white rounded-3xl p-5 border {{ $criticalIncidentsCount ? 'border-rose-300 ring-2 ring-rose-100' : 'border-slate-200/70' }} hover:shadow-lg transition-all">
            <div class="flex items-start justify-between">
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Kendala & SOS</p>
                <span class="w-9 h-9 rounded-xl {{ $openIncidentsCount ? 'bg-rose-50 text-rose-600' : 'bg-slate-100 text-slate-400' }} flex items-center justify-center">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 3h.01M10.3 3.9L2.4 18a2 2 0 001.7 3h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
                </span>
            </div>
            <p class="font-space text-4xl font-bold {{ $openIncidentsCount ? 'text-rose-600' : 'text-slate-900' }} mt-2 leading-none" data-counter>{{ $openIncidentsCount }}</p>
            <p class="text-[11px] font-semibold text-slate-500 mt-3">
                {{ $openIncidentsCount ? 'laporan terbuka' . ($criticalIncidentsCount ? ' · ' . $criticalIncidentsCount . ' darurat' : '') : 'Tidak ada laporan terbuka' }}
            </p>
        </a>

        {{-- Kehadiran periode --}}
        <a href="{{ route('guru.monitoring') }}" data-tilt class="group relative bg-white rounded-3xl p-5 border border-slate-200/70 hover:border-[#0B2B25]/30 hover:shadow-lg transition-all">
            <div class="flex items-start justify-between">
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Rata-rata Kehadiran</p>
                <span class="w-9 h-9 rounded-xl bg-slate-100 text-[#0B2B25] flex items-center justify-center">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 17l6-6 4 4 8-8M14 7h7v7"/></svg>
                </span>
            </div>
            <p data-counter class="font-space text-4xl font-bold text-slate-900 mt-2 leading-none">{{ $avgAttendanceRate !== null ? $avgAttendanceRate . '%' : '—' }}</p>
            <p class="text-[11px] font-semibold mt-3 {{ $studentsNeedAttention ? 'text-orange-600' : 'text-slate-500' }}">
                {{ $studentsNeedAttention ? $studentsNeedAttention . ' siswa perlu perhatian' : 'Selama periode PKL berjalan' }}
            </p>
        </a>

        {{-- Penilaian --}}
        <a href="{{ route('guru.assessments') }}" data-tilt class="group relative bg-white rounded-3xl p-5 border border-slate-200/70 hover:border-[#0B2B25]/30 hover:shadow-lg transition-all">
            <div class="flex items-start justify-between">
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nilai Guru Terisi</p>
                <span class="w-9 h-9 rounded-xl bg-slate-100 text-[#0B2B25] flex items-center justify-center">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.5 3.5l2.4 4.9 5.4.8-3.9 3.8.9 5.4-4.8-2.6-4.8 2.6.9-5.4-3.9-3.8 5.4-.8z"/></svg>
                </span>
            </div>
            <p class="font-space text-4xl font-bold text-slate-900 mt-2 leading-none"><span data-counter>{{ $teacherScoredCount }}</span><span class="text-slate-300 text-2xl">/{{ $totalStudents }}</span></p>
            <p class="text-[11px] font-semibold text-slate-500 mt-3">
                {{ $avgFinalScore !== null ? 'Rata-rata nilai akhir ' . $avgFinalScore : 'Belum ada nilai akhir' }}
            </p>
        </a>
    </section>

    {{-- ===================== MAIN GRID ===================== --}}
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 items-start">

        {{-- ---------- LEFT ---------- --}}
        <div data-reveal-stagger class="xl:col-span-8 space-y-5 min-w-0">

            {{-- Presensi hari ini --}}
            <section id="presensi-hari-ini" class="bg-white rounded-3xl border border-slate-200/70 overflow-hidden">
                <div class="px-5 pt-5 pb-4 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Presensi Hari Ini</h3>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">{{ now()->isoFormat('dddd, D MMMM Y') }} · diurutkan dari yang perlu perhatian</p>
                    </div>
                    <a href="{{ route('guru.monitoring', ['date' => now()->toDateString()]) }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#12382F] hover:underline">Detail & foto {!! $chevron !!}</a>
                </div>

                <div class="px-5 pb-4 flex flex-wrap gap-1.5">
                    @foreach([['hadir', 'Hadir', $todaySummary['hadir']], ['terlambat', 'Terlambat', $todaySummary['terlambat']], ['dinas_luar', 'Dinas/WFH', $todaySummary['tugas']], ['izin', 'Izin/Sakit', $todaySummary['izin_sakit']], ['alfa', 'Alfa', $todaySummary['alfa']], ['belum', 'Belum', $todaySummary['belum']], ['libur', 'Libur', $todaySummary['libur']]] as [$k, $lbl, $val])
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-[11px] font-bold {{ $val ? $stateMeta[$k]['pill'] : 'bg-white text-slate-400 border-slate-200' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $val ? $stateMeta[$k]['dot'] : 'bg-slate-300' }}"></span>{{ $lbl }} <span class="font-space">{{ $val }}</span>
                        </span>
                    @endforeach
                </div>

                @if($todayRows->isEmpty())
                    <div class="p-8 border-t border-slate-100">
                        <x-empty-state title="Belum Ada Siswa Binaan" description="Hubungi Admin Sekolah untuk plotting siswa ke bimbingan Anda." />
                    </div>
                @else
                    <div class="overflow-x-auto border-t border-slate-100">
                        <table class="w-full text-xs min-w-[680px]">
                            <thead>
                                <tr class="text-[10px] uppercase tracking-wider text-slate-400 font-bold text-left bg-slate-50/60">
                                    <th class="px-5 py-2.5">Siswa</th>
                                    <th class="px-3 py-2.5">Status</th>
                                    <th class="px-3 py-2.5">Masuk</th>
                                    <th class="px-3 py-2.5">Pulang</th>
                                    <th class="px-3 py-2.5">Lokasi</th>
                                    <th class="px-5 py-2.5 text-right">Selfie</th>
                                </tr>
                            </thead>
                            <tbody data-reveal-stagger class="divide-y divide-slate-100">
                                @foreach($todayRows as $row)
                                    @php
                                        $sp = $row['spkl']; $att = $row['attendance']; $meta = $stateMeta[$row['state']] ?? $stateMeta['belum'];
                                        $jamMasuk = $row['hours']['checkin_late'] ?? null;
                                    @endphp
                                    <tr class="{{ $row['flagged'] ? 'bg-rose-50/50' : 'hover:bg-slate-50/60' }} transition-colors">
                                        <td class="px-5 py-3">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <span class="w-9 h-9 rounded-full bg-[#C6F26B] text-[#0B2B25] font-extrabold text-[11px] flex items-center justify-center shrink-0">{{ $initials($sp->student->user->name) }}</span>
                                                <div class="min-w-0">
                                                    <p class="font-bold text-slate-900 truncate">{{ $sp->student->user->name }} <span class="text-slate-400 font-medium">· {{ $sp->student->class->name ?? '-' }}</span></p>
                                                    <p class="text-[11px] text-slate-500 truncate">{{ $sp->company->name ?? 'Belum ada DU/DI' }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3">
                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full border text-[10px] font-bold whitespace-nowrap {{ $meta['pill'] }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $meta['dot'] }}"></span>{{ $meta['label'] }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            @if($att && $att->checkin_time)
                                                <span class="font-space font-bold text-slate-900">{{ $fmtTime($att->checkin_time) }}</span>
                                            @else
                                                <span class="text-slate-300">—</span>
                                                @if($jamMasuk && in_array($row['state'], ['belum', 'belum_telat']))
                                                    <span class="block text-[10px] text-slate-400">batas {{ substr($jamMasuk, 0, 5) }}</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            @if($att && $att->checkout_time)
                                                <span class="font-space font-bold text-slate-900">{{ $fmtTime($att->checkout_time) }}</span>
                                            @else
                                                <span class="text-slate-300">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3">
                                            @if($att && $att->is_mock_detected)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-[#B42318] text-white text-[10px] font-bold">Fake GPS</span>
                                            @elseif($att && $att->is_within_radius === false)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 text-[10px] font-bold">Luar radius</span>
                                            @elseif($att && $att->is_within_radius === true)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold">Dalam area</span>
                                            @elseif($att && $att->checkin_time)
                                                <span class="text-[10px] text-slate-400 font-semibold">GPS nonaktif</span>
                                            @else
                                                <span class="text-slate-300">—</span>
                                            @endif
                                            @if($att && $att->accuracy_meters)
                                                <span class="block text-[10px] text-slate-400 mt-0.5">±{{ $att->accuracy_meters }} m</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-right">
                                            @if($att && $att->checkin_photo_path)
                                                <button type="button" onclick="openImageLightbox('{{ $att->checkin_photo_url }}', 'Selfie masuk: {{ addslashes($sp->student->user->name) }}')" class="inline-block cursor-zoom-in" title="Lihat selfie">
                                                    <img src="{{ $att->checkin_photo_url }}" alt="Selfie {{ $sp->student->user->name }}" class="w-9 h-9 rounded-xl object-cover border border-slate-200 hover:scale-110 transition-transform">
                                                </button>
                                            @else
                                                <span class="text-slate-300">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            {{-- Tren 7 hari --}}
            <section class="bg-white rounded-3xl border border-slate-200/70 p-5">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Tren Kehadiran 7 Hari</h3>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">Jumlah siswa per status presensi harian</p>
                    </div>
                    <div class="flex flex-wrap gap-3 text-[11px] font-semibold text-slate-500">
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-[#12382F]"></span>Hadir</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-amber-400"></span>Terlambat</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-sky-400"></span>Izin/Sakit</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-rose-500"></span>Alfa</span>
                    </div>
                </div>
                <div class="grid grid-cols-7 gap-2 sm:gap-4 items-end h-44">
                    @foreach($attendanceTrend as $day)
                        @php $sum = $day['hadir'] + $day['terlambat'] + $day['izin'] + $day['alfa']; $isToday = $day['date']->isToday(); @endphp
                        <div class="h-full flex flex-col items-center justify-end gap-2 group" title="{{ $day['date']->isoFormat('dddd, D MMM') }}: {{ $day['hadir'] }} hadir, {{ $day['terlambat'] }} terlambat, {{ $day['izin'] }} izin/sakit, {{ $day['alfa'] }} alfa">
                            <span class="font-space text-[11px] font-bold {{ $sum ? 'text-slate-700' : 'text-slate-300' }}">{{ $sum ?: '–' }}</span>
                            <div data-grow-y class="w-full max-w-[44px] flex-1 flex flex-col-reverse rounded-xl overflow-hidden bg-slate-50 {{ $isToday ? 'ring-2 ring-[#C6F26B] ring-offset-2' : '' }}">
                                @foreach([['hadir', 'bg-[#12382F]'], ['terlambat', 'bg-amber-400'], ['izin', 'bg-sky-400'], ['alfa', 'bg-rose-500']] as [$k, $cls])
                                    @if($day[$k] > 0)
                                        <div class="{{ $cls }} w-full" style="height: {{ round($day[$k] / $trendMax * 100, 1) }}%"></div>
                                    @endif
                                @endforeach
                            </div>
                            <span class="text-[10px] font-bold {{ $isToday ? 'text-[#0B2B25]' : 'text-slate-400' }} uppercase">{{ $isToday ? 'Hari ini' : $day['date']->isoFormat('ddd D') }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Siswa bimbingan --}}
            <section class="bg-white rounded-3xl border border-slate-200/70 p-5">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Perkembangan Siswa Bimbingan</h3>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">Kehadiran, jurnal, dan penilaian selama periode PKL</p>
                    </div>
                    <a href="{{ route('guru.reports') }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#12382F] hover:underline">Laporan PDF {!! $chevron !!}</a>
                </div>

                @if($studentInsights->isEmpty())
                    <x-empty-state title="Belum Ada Siswa Binaan" description="Hubungi Admin Sekolah untuk plotting siswa ke bimbingan Anda." />
                @else
                    <div data-reveal-stagger class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        @foreach($studentInsights as $s)
                            @php
                                $sp = $s['spkl'];
                                $rate = $s['attendance_rate'];
                                $rateColor = $rate === null ? 'bg-slate-200' : ($rate >= 90 ? 'bg-[#12382F]' : ($rate >= 75 ? 'bg-amber-400' : 'bg-rose-500'));
                                $jPct = $s['journal_total'] ? round($s['journal_approved'] / $s['journal_total'] * 100) : 0;
                                $todayMeta = $stateMeta[$s['today']['state'] ?? 'belum'] ?? $stateMeta['belum'];
                            @endphp
                            <article class="rounded-[22px] border {{ count($s['warnings']) ? 'border-orange-200 bg-orange-50/30' : 'border-slate-200/80 bg-white' }} {{ $s['open_incidents'] ? 'vt-attention' : '' }} p-4 space-y-4 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-[#0B2B25]/[0.07]">
                                {{-- header --}}
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="w-11 h-11 rounded-full bg-[#0B2B25] text-[#C6F26B] font-extrabold text-xs flex items-center justify-center shrink-0">{{ $initials($sp->student->user->name) }}</span>
                                        <div class="min-w-0">
                                            <p class="font-extrabold text-slate-900 text-sm truncate">{{ $sp->student->user->name }}</p>
                                            <p class="text-[11px] text-slate-500 truncate">{{ $sp->student->class->name ?? '-' }} · NISN <span class="font-mono">{{ $sp->student->nisn }}</span></p>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border text-[10px] font-bold whitespace-nowrap {{ $todayMeta['pill'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $todayMeta['dot'] }}"></span>{{ $todayMeta['label'] }}
                                    </span>
                                </div>

                                {{-- DU/DI --}}
                                <div class="flex items-center gap-2.5 p-3 rounded-2xl bg-slate-50 text-xs">
                                    <svg class="w-4 h-4 text-[#12382F] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1m4 0h1m-6 4h1m4 0h1m-6 4h1m4 0h1"/></svg>
                                    <div class="min-w-0">
                                        <p class="font-bold text-slate-800 truncate">{{ $sp->company->name ?? 'Belum ditempatkan' }}</p>
                                        <p class="text-[10px] text-slate-500 truncate">Pembimbing DU/DI: {{ $sp->supervisor->user->name ?? 'Belum ditentukan' }}</p>
                                    </div>
                                </div>

                                {{-- Kehadiran --}}
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between text-[11px] font-bold">
                                        <span class="text-slate-500 uppercase tracking-wider">Kehadiran</span>
                                        <span class="font-space text-slate-900 text-sm">{{ $rate !== null ? $rate . '%' : '—' }}</span>
                                    </div>
                                    <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden"><div data-grow-x class="h-full rounded-full {{ $rateColor }}" style="width: {{ $rate ?? 0 }}%"></div></div>
                                    <div class="flex flex-wrap gap-x-3 gap-y-0.5 text-[10px] font-semibold text-slate-500">
                                        <span><span class="font-space text-slate-800">{{ $s['present'] }}</span> hadir</span>
                                        <span><span class="font-space text-amber-600">{{ $s['late'] }}</span> telat</span>
                                        <span><span class="font-space text-sky-600">{{ $s['leave'] }}</span> izin/sakit</span>
                                        <span><span class="font-space text-rose-600">{{ $s['alfa'] }}</span> alfa</span>
                                        <span class="ml-auto">Terakhir masuk: {{ $s['last_checkin'] ? $s['last_checkin']->isoFormat('D MMM') : '—' }}</span>
                                    </div>
                                </div>

                                {{-- Jurnal --}}
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between text-[11px] font-bold">
                                        <span class="text-slate-500 uppercase tracking-wider">Jurnal disetujui</span>
                                        <span class="font-space text-slate-900 text-sm">{{ $s['journal_approved'] }}<span class="text-slate-300">/{{ $s['journal_total'] }}</span></span>
                                    </div>
                                    <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden"><div data-grow-x class="h-full rounded-full bg-[#C6F26B]" style="width: {{ $jPct }}%"></div></div>
                                    <div class="flex flex-wrap gap-1.5 text-[10px] font-bold">
                                        @if($s['journal_review_guru'])<a href="{{ route('guru.journals') }}" class="px-2 py-0.5 rounded-full bg-[#0B2B25] text-[#C6F26B]">{{ $s['journal_review_guru'] }} menunggu Anda</a>@endif
                                        @if($s['journal_at_dudi'])<span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ $s['journal_at_dudi'] }} di DU/DI</span>@endif
                                        @if($s['journal_revision'])<span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700">{{ $s['journal_revision'] }} revisi</span>@endif
                                        <span class="px-2 py-0.5 rounded-full {{ ($s['days_since_journal'] ?? 99) > 3 ? 'bg-orange-50 text-orange-700' : 'bg-slate-50 text-slate-500' }}">
                                            {{ $s['days_since_journal'] === null ? 'Belum pernah isi' : ($s['days_since_journal'] === 0 ? 'Isi hari ini' : 'Terakhir ' . $s['days_since_journal'] . ' hari lalu') }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Penilaian --}}
                                <div class="grid grid-cols-3 gap-2 text-center">
                                    <div class="rounded-xl py-2 {{ $s['dudi_scored'] ? 'bg-emerald-50' : 'bg-slate-50' }}">
                                        <span class="text-[9px] font-bold uppercase text-slate-500 block">Nilai DU/DI</span>
                                        <span class="text-[11px] font-extrabold {{ $s['dudi_scored'] ? 'text-emerald-700' : 'text-slate-400' }}">{{ $s['dudi_scored'] ? 'Terisi' : 'Belum' }}</span>
                                    </div>
                                    <div class="rounded-xl py-2 {{ $s['teacher_scored'] ? 'bg-emerald-50' : 'bg-slate-50' }}">
                                        <span class="text-[9px] font-bold uppercase text-slate-500 block">Nilai Guru</span>
                                        <span class="text-[11px] font-extrabold {{ $s['teacher_scored'] ? 'text-emerald-700' : 'text-slate-400' }}">{{ $s['teacher_scored'] ? 'Terisi' : 'Belum' }}</span>
                                    </div>
                                    <div class="rounded-xl py-2 {{ $s['final_score'] ? 'bg-[#0B2B25] text-white' : 'bg-slate-50' }}">
                                        <span class="text-[9px] font-bold uppercase {{ $s['final_score'] ? 'text-[#A9C2B9]' : 'text-slate-500' }} block">Nilai Akhir</span>
                                        <span class="font-space text-sm font-bold {{ $s['final_score'] ? 'text-[#C6F26B]' : 'text-slate-400' }}">{{ $s['final_score'] ? number_format($s['final_score'], 1) : '—' }}</span>
                                    </div>
                                </div>

                                @if(count($s['warnings']))
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-orange-600" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 3h.01M10.3 3.9L2.4 18a2 2 0 001.7 3h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
                                        @foreach($s['warnings'] as $w)
                                            <span class="px-2 py-0.5 rounded-full bg-orange-100 text-orange-800 text-[10px] font-bold">{{ $w }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- aksi --}}
                                <div class="flex items-center gap-1.5 pt-3 border-t border-slate-100">
                                    <a href="{{ route('guru.monitoring') }}" class="px-3 py-1.5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] transition-colors">Absensi</a>
                                    <a href="{{ route('guru.journals', ['student_pkl_id' => $sp->id]) }}" class="px-3 py-1.5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] transition-colors">Jurnal</a>
                                    <a href="{{ route('guru.assessments') }}" class="px-3 py-1.5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] transition-colors">Nilai</a>
                                    <a href="{{ route('guru.reports.download', $sp->id) }}" class="ml-auto inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-[#0B2B25] hover:bg-[#12382F] text-[#C6F26B] font-bold text-[11px] transition-colors" title="Unduh buku laporan PDF">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M5 20h14"/></svg>PDF
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        {{-- ---------- RIGHT ---------- --}}
        <aside data-reveal-stagger class="xl:col-span-4 space-y-5 min-w-0">

            {{-- Antrean tindakan --}}
            <section class="bg-[#0B2B25] text-white rounded-3xl p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-extrabold tracking-tight">Antrean Tindakan</h3>
                        <p class="text-[11px] text-[#A9C2B9] font-medium mt-0.5">Diurutkan dari yang paling lama menunggu</p>
                    </div>
                    <span data-counter class="font-space text-2xl font-bold text-[#C6F26B]">{{ $verifTotal }}</span>
                </div>

                @if($verifTotal === 0)
                    <div class="rounded-2xl bg-white/[0.06] p-5 text-center">
                        <span class="w-10 h-10 rounded-full bg-[#C6F26B] text-[#0B2B25] flex items-center justify-center mx-auto mb-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <p class="text-xs font-bold">Semua sudah diverifikasi</p>
                        <p class="text-[11px] text-[#A9C2B9] mt-0.5">Tidak ada izin/sakit atau jurnal yang menunggu.</p>
                    </div>
                @else
                    @if($pendingJournalsList->isNotEmpty())
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-[11px] font-bold">
                                <span class="uppercase tracking-wider text-[#A9C2B9]">Jurnal tingkat 2 · {{ $pendingJournals }}</span>
                                <a href="{{ route('guru.journals') }}" class="text-[#C6F26B] hover:underline">Review semua</a>
                            </div>
                            @foreach($pendingJournalsList as $pj)
                                <a href="{{ route('guru.journals', ['student_pkl_id' => $pj->student_pkl_id]) }}" class="flex items-center gap-3 p-3 rounded-2xl bg-white/[0.06] hover:bg-white/[0.12] transition-colors">
                                    <span class="w-10 shrink-0 text-center leading-none">
                                        <span class="font-space text-base font-bold block">{{ $pj->date->format('d') }}</span>
                                        <span class="text-[9px] uppercase text-[#A9C2B9] font-bold">{{ $pj->date->isoFormat('MMM') }}</span>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="text-xs font-bold block truncate">{{ $pj->studentPkl->student->user->name }}</span>
                                        <span class="text-[10px] text-[#A9C2B9] block truncate">{{ Str::limit($pj->activity_description ?? '', 48) }}</span>
                                    </span>
                                    <span class="text-[10px] font-bold text-white/50 shrink-0">{{ $pj->date->diffForHumans(null, true) }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if($pendingRequestsList->isNotEmpty())
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-[11px] font-bold">
                                <span class="uppercase tracking-wider text-[#A9C2B9]">Izin & sakit · {{ $pendingRequests }}</span>
                                <a href="{{ route('guru.requests') }}" class="text-[#C6F26B] hover:underline">Proses</a>
                            </div>
                            @foreach($pendingRequestsList as $pr)
                                <a href="{{ route('guru.requests') }}" class="flex items-center gap-3 p-3 rounded-2xl bg-white/[0.06] hover:bg-white/[0.12] transition-colors">
                                    <span class="px-2 py-1 rounded-lg text-[10px] font-extrabold uppercase shrink-0 {{ $pr->type === 'sakit' ? 'bg-purple-400/20 text-purple-200' : 'bg-sky-400/20 text-sky-200' }}">{{ $pr->type }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="text-xs font-bold block truncate">{{ $pr->studentPkl->student->user->name }}</span>
                                        <span class="text-[10px] text-[#A9C2B9] block truncate">
                                            {{ $pr->start_date->isoFormat('D MMM') }}{{ $pr->end_date && !$pr->end_date->eq($pr->start_date) ? ' – ' . $pr->end_date->isoFormat('D MMM') : '' }}
                                            · {{ Str::limit($pr->reason ?? '', 30) }}
                                        </span>
                                    </span>
                                    @if($pr->attachment_path)
                                        <svg class="w-3.5 h-3.5 text-white/50 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-label="Ada lampiran"><path stroke-linecap="round" stroke-linejoin="round" d="M15.2 7l-6.6 6.6a2 2 0 102.8 2.8l6.6-6.6a4 4 0 10-5.7-5.7l-6.6 6.6a6 6 0 108.5 8.5L21 12.5"/></svg>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif
                @endif
            </section>

            {{-- Kendala & SOS --}}
            <section class="bg-white rounded-3xl border {{ $criticalIncidentsCount ? 'border-rose-300' : 'border-slate-200/70' }} p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Kendala & SOS Siswa</h3>
                        <p class="text-[11px] text-slate-500 font-medium mt-0.5">{{ $openIncidentsCount }} laporan belum selesai</p>
                    </div>
                    <a href="{{ route('guru.incidents') }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#12382F] hover:underline">Semua {!! $chevron !!}</a>
                </div>
                @forelse($openIncidents as $inc)
                    @php $u = $urgencyMeta[$inc->urgency] ?? $urgencyMeta['low']; @endphp
                    <a href="{{ route('guru.incidents') }}" class="block p-3 rounded-2xl {{ $inc->urgency === 'critical' ? 'bg-rose-50 border border-rose-200' : 'bg-slate-50 hover:bg-slate-100' }} transition-colors">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-1.5 py-0.5 rounded-md text-[9px] font-extrabold uppercase {{ $u['pill'] }}">{{ $u['label'] }}</span>
                            <span class="text-[10px] font-bold text-slate-500">{{ $inc->status === 'in_progress' ? 'Sedang ditindak' : 'Baru masuk' }}</span>
                            <span class="ml-auto text-[10px] text-slate-400">{{ $inc->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-xs font-bold text-slate-900 truncate">{{ $inc->subject }}</p>
                        <p class="text-[11px] text-slate-500 truncate">{{ $inc->studentPkl->student->user->name }} · {{ $inc->studentPkl->company->name ?? '-' }}</p>
                    </a>
                @empty
                    <div class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50">
                        <span class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <p class="text-[11px] text-slate-500 font-medium">Tidak ada kendala terbuka dari siswa bimbingan Anda.</p>
                    </div>
                @endforelse
            </section>

            {{-- Peringatan lokasi --}}
            @if($flaggedAttendances->isNotEmpty())
                <section class="bg-white rounded-3xl border border-slate-200/70 p-5 space-y-3">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Peringatan Lokasi</h3>
                        <p class="text-[11px] text-slate-500 font-medium mt-0.5">Fake GPS / luar radius dalam 7 hari terakhir</p>
                    </div>
                    @foreach($flaggedAttendances as $fa)
                        <a href="{{ route('guru.monitoring', ['date' => $fa->date->toDateString()]) }}" class="flex items-center gap-3 p-2.5 rounded-2xl hover:bg-slate-50 transition-colors">
                            <span class="w-9 h-9 rounded-full {{ $fa->is_mock_detected ? 'bg-[#B42318] text-white' : 'bg-rose-100 text-rose-700' }} flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-6.2-7-11a7 7 0 1114 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="text-xs font-bold text-slate-900 block truncate">{{ $fa->studentPkl->student->user->name }}</span>
                                <span class="text-[10px] text-slate-500 block truncate">{{ $fa->is_mock_detected ? 'Fake GPS terdeteksi' : 'Di luar radius' }} · {{ $fa->studentPkl->company->name ?? '-' }}</span>
                            </span>
                            <span class="text-[10px] font-bold text-slate-400 shrink-0">{{ $fa->date->isoFormat('D MMM') }}</span>
                        </a>
                    @endforeach
                </section>
            @endif

            {{-- Jurnal tertahan di DU/DI --}}
            @if($stuckAtDudi->isNotEmpty())
                <section class="bg-amber-50 rounded-3xl border border-amber-200 p-5 space-y-3">
                    <div>
                        <h3 class="text-base font-extrabold text-amber-950 tracking-tight">Jurnal Tertahan di DU/DI</h3>
                        <p class="text-[11px] text-amber-800/80 font-medium mt-0.5">Belum diverifikasi pembimbing industri lebih dari 2 hari. Ingatkan mereka saat kunjungan atau lewat telepon.</p>
                    </div>
                    @foreach($stuckAtDudi as $st)
                        <div class="flex items-center gap-3 p-3 rounded-2xl bg-white border border-amber-200/70">
                            <span class="font-space text-lg font-bold text-amber-700 w-8 text-center shrink-0">{{ $st['count'] }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="text-xs font-bold text-slate-900 block truncate">{{ $st['company']->name ?? '-' }}</span>
                                <span class="text-[10px] text-slate-500 block truncate">{{ $st['supervisor'] ?? 'Pembimbing belum ditentukan' }}{{ $st['company']?->phone ? ' · ' . $st['company']->phone : '' }}</span>
                            </span>
                            <span class="text-[10px] font-bold text-amber-700 shrink-0">sejak {{ \Carbon\Carbon::parse($st['oldest'])->isoFormat('D MMM') }}</span>
                        </div>
                    @endforeach
                </section>
            @endif

            {{-- Supervisi --}}
            <section class="bg-white rounded-3xl border border-slate-200/70 p-5 space-y-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Kunjungan Supervisi</h3>
                        <p class="text-[11px] text-slate-500 font-medium mt-0.5">Tahapan kunjungan per DU/DI</p>
                    </div>
                    <span class="font-space text-sm font-bold text-slate-900 whitespace-nowrap">{{ $supervisionDone }}<span class="text-slate-300">/{{ $supervisionTarget }}</span></span>
                </div>
                @forelse($supervisionProgress as $sv)
                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-bold text-slate-900 truncate">{{ $sv['company']->name }}</p>
                            <span class="text-[10px] font-semibold text-slate-400 shrink-0">{{ $sv['students'] }} siswa</span>
                        </div>
                        <div class="grid grid-cols-4 gap-1">
                            @foreach($supervisionStages as $key => $label)
                                @php $done = $sv['done']->contains($key); @endphp
                                <div class="space-y-1" title="{{ $label }}{{ $done ? ' — selesai' : ' — belum' }}">
                                    <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden"><div data-grow-x class="h-full rounded-full {{ $done ? 'bg-[#12382F]' : '' }}"></div></div>
                                    <span class="block text-[9px] font-bold {{ $done ? 'text-[#12382F]' : 'text-slate-400' }} truncate">{{ $label }}</span>
                                </div>
                            @endforeach
                        </div>
                        <p class="text-[10px] text-slate-500">
                            @if($sv['next_stage'])
                                Berikutnya: <span class="font-bold text-slate-700">{{ $sv['next_stage'] }}</span>
                            @else
                                <span class="font-bold text-emerald-700">Semua tahap selesai</span>
                            @endif
                            @if($sv['last_visit']) · terakhir {{ \Carbon\Carbon::parse($sv['last_visit'])->isoFormat('D MMM') }} @endif
                        </p>
                    </div>
                @empty
                    <p class="text-[11px] text-slate-500">Belum ada DU/DI binaan.</p>
                @endforelse
                <a href="{{ route('guru.supervisions') }}" data-ripple class="flex items-center justify-center gap-1.5 w-full py-2.5 rounded-full bg-[#C6F26B] hover:bg-[#b5e855] text-[#0B2B25] text-xs font-extrabold transition-colors">
                    Catat Kunjungan & Berita Acara
                </a>
            </section>

            {{-- Pengumuman --}}
            <section class="bg-white rounded-3xl border border-slate-200/70 p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Pengumuman</h3>
                    <a href="{{ route('guru.announcements') }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#12382F] hover:underline">Kelola {!! $chevron !!}</a>
                </div>
                @forelse($recentAnnouncements as $ann)
                    <button type="button" onclick='openDashboardAnnouncementModal(@json($ann))' class="w-full text-left flex items-start gap-3 p-2.5 -mx-1 rounded-2xl hover:bg-slate-50 transition-colors">
                        @if($ann->image_path)
                            <img src="{{ $ann->image_url }}" alt="" class="w-11 h-11 rounded-xl object-cover border border-slate-200 shrink-0">
                        @else
                            <span class="w-11 h-11 rounded-xl bg-slate-100 text-[#12382F] flex items-center justify-center shrink-0">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.9v13.3a1.8 1.8 0 01-3.4.6l-2.2-6.1M18 13a3 3 0 100-6M5.4 13.7A4 4 0 017 6h1.8c4.1 0 7.6-1.2 9.2-3v14c-1.5-1.8-5.1-3-9.2-3H7a4 4 0 01-1.6-.3z"/></svg>
                            </span>
                        @endif
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-1.5">
                                <span class="text-[9px] font-extrabold uppercase text-[#12382F] tracking-wider">{{ $ann->category_label }}</span>
                                @if($ann->is_pinned)<span class="text-[9px] font-bold text-amber-700">· Disematkan</span>@endif
                            </span>
                            <span class="text-xs font-bold text-slate-900 block line-clamp-1">{{ $ann->title }}</span>
                            <span class="text-[10px] text-slate-400 block">{{ $ann->author->name ?? 'Sekolah' }} · {{ ($ann->published_at ?? $ann->created_at)->diffForHumans() }}</span>
                        </span>
                    </button>
                @empty
                    <p class="text-[11px] text-slate-500">Belum ada pengumuman aktif. <a href="{{ route('guru.announcements') }}" class="font-bold text-[#12382F] hover:underline">Tulis pengumuman</a></p>
                @endforelse
            </section>
        </aside>
    </div>
</div>

<!-- Modal Detail Pengumuman Dashboard -->
<div id="modal-dashboard-announcement" onclick="if(event.target === this) closeDashboardAnnouncementModal()" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-2xl max-h-[90vh] overflow-y-auto border border-slate-100 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <span id="modal-dash-category" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200">
                PENGUMUMAN
            </span>
            <button type="button" onclick="closeDashboardAnnouncementModal()" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div id="modal-dash-img-container" class="hidden rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 cursor-zoom-in max-h-60" onclick="zoomModalImage()">
            <img id="modal-dash-img" src="" alt="Lampiran" class="w-full h-full object-cover">
        </div>

        <div class="space-y-2">
            <h3 id="modal-dash-title" class="text-base font-extrabold text-slate-900 tracking-tight leading-snug"></h3>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <span id="modal-dash-author" class="font-bold text-slate-700"></span>
                <span>&bull;</span>
                <span id="modal-dash-time"></span>
            </div>
        </div>

        <div id="modal-dash-content" class="text-xs text-slate-700 leading-relaxed font-medium bg-slate-50/70 p-4 rounded-2xl border border-slate-100 whitespace-pre-wrap"></div>

        <div class="pt-2 flex justify-end">
            <x-button type="button" variant="outline" size="sm" onclick="closeDashboardAnnouncementModal()">Tutup</x-button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let currentModalImgUrl = null;

    function openDashboardAnnouncementModal(ann) {
        document.getElementById('modal-dash-title').textContent = ann.title;
        document.getElementById('modal-dash-content').textContent = ann.content ? ann.content.replace(/<[^>]*>?/gm, '') : '';
        document.getElementById('modal-dash-category').textContent = (ann.category || 'UMUM').toUpperCase();
        document.getElementById('modal-dash-author').textContent = ann.author ? ann.author.name : 'Sekolah';
        document.getElementById('modal-dash-time').textContent = ann.published_at || ann.created_at || '';

        const imgContainer = document.getElementById('modal-dash-img-container');
        const imgElem = document.getElementById('modal-dash-img');

        if (ann.image_path) {
            currentModalImgUrl = ann.image_url || ('/storage/' + ann.image_path);
            imgElem.src = currentModalImgUrl;
            imgContainer.classList.remove('hidden');
        } else {
            currentModalImgUrl = null;
            imgContainer.classList.add('hidden');
            imgElem.src = '';
        }

        document.getElementById('modal-dashboard-announcement').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeDashboardAnnouncementModal() {
        document.getElementById('modal-dashboard-announcement').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function zoomModalImage() {
        if (currentModalImgUrl && typeof openImageLightbox === 'function') {
            openImageLightbox(currentModalImgUrl, document.getElementById('modal-dash-title').textContent);
        }
    }

    // Real-time Live Clock Updater
    function updateDashboardClock() {
        const clockElem = document.getElementById('banner-live-clock');
        const dateElem = document.getElementById('banner-live-date');
        if (!clockElem) return;

        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        clockElem.textContent = `${hours}:${minutes}:${seconds} WIB`;

        if (dateElem) {
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const dayName = days[now.getDay()];
            const dateNum = now.getDate();
            const monthName = months[now.getMonth()];
            const year = now.getFullYear();
            dateElem.textContent = `${dayName}, ${dateNum} ${monthName} ${year}`;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateDashboardClock();
        setInterval(updateDashboardClock, 1000);
    });
</script>
@endpush
@endsection
