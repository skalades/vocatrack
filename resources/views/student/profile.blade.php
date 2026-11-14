@extends('layouts.student-mobile')

@section('title', 'Profil & Penempatan PKL — VokaTrack Siswa')

@section('content')
<div class="space-y-4">

    <!-- 1. Digital Student Identity Card -->
    <div class="relative overflow-hidden bg-white rounded-3xl border border-slate-200/80 shadow-xs">
        <!-- Header Banner with Official Motif -->
        <div class="relative h-20 bg-gradient-to-r from-indigo-600 via-indigo-700 to-indigo-800 p-4 flex items-start justify-between text-white overflow-hidden">
            <div class="absolute -right-6 -bottom-8 w-28 h-28 rounded-full bg-white/10 blur-xs pointer-events-none"></div>
            <div class="relative z-10 flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-[10px] font-extrabold uppercase tracking-widest text-indigo-100">Kartu Tanda Siswa PKL</span>
            </div>
            <span class="relative z-10 text-[10px] font-bold px-2 py-0.5 rounded-full bg-white/15 backdrop-blur-md text-white border border-white/20">
                {{ auth()->user()->school->name ?? 'SMK Indonesia' }}
            </span>
        </div>

        <!-- Student Profile Content -->
        <div class="relative px-5 pb-5 pt-0 text-center">
            <!-- Avatar with Outer Ring & Status Indicator -->
            <div class="relative -mt-10 mx-auto w-20 h-20 rounded-3xl bg-white p-1.5 shadow-md flex items-center justify-center">
                <div class="w-full h-full rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-700 to-indigo-500 text-white font-black text-2xl flex items-center justify-center shadow-inner select-none transition-transform hover:scale-105 duration-200">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <span class="absolute bottom-1 right-1 flex h-4 w-4">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-4 w-4 bg-emerald-500 border-2 border-white shadow-xs" title="Siswa Aktif"></span>
                </span>
            </div>

            <!-- Student Full Name & Affiliation -->
            <div class="mt-2.5">
                <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight leading-snug">
                    {{ auth()->user()->name }}
                </h2>
                <p class="text-xs text-slate-500 font-medium mt-0.5">
                    {{ auth()->user()->email }}
                </p>
            </div>

            <!-- Class & Major Badges (Modular & Scalable) -->
            <div class="flex items-center justify-center flex-wrap gap-1.5 mt-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200/80 shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-indigo-600 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                    <span>Kelas {{ $student->class->name ?? '-' }}</span>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200/80 shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-slate-500 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span class="truncate max-w-[220px]">{{ $student->class->major->name ?? '-' }}</span>
                </span>
            </div>

            <!-- Identification Number Chips (NISN & NIS) -->
            <div class="grid grid-cols-2 gap-2 mt-4 pt-4 border-t border-slate-100">
                <div class="bg-slate-50/80 border border-slate-100 rounded-2xl py-2 px-3 text-center">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">NISN</span>
                    <span class="text-xs font-extrabold text-slate-800 font-mono tracking-wide mt-0.5 block">
                        {{ $student->nisn ?? '-' }}
                    </span>
                </div>
                <div class="bg-slate-50/80 border border-slate-100 rounded-2xl py-2 px-3 text-center">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">NIS</span>
                    <span class="text-xs font-extrabold text-slate-800 font-mono tracking-wide mt-0.5 block">
                        {{ $student->nis ?? '-' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. PKL Placement Information Card -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs space-y-4">
        <!-- Section Title & Status Badge -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider leading-none">Informasi Penempatan PKL</h3>
                    <p class="text-[11px] text-slate-400 font-medium mt-0.5">Mitra Industri & Pembimbing Resmi</p>
                </div>
            </div>
            <x-badge variant="success" size="xs">
                Aktif PKL
            </x-badge>
        </div>

        <!-- Mitra DU/DI Spotlight -->
        <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200/70 space-y-1.5">
            <div class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                <span class="text-[10px] font-extrabold text-indigo-700 uppercase tracking-wider">Perusahaan Mitra (DU/DI)</span>
            </div>
            <h4 class="font-black text-slate-900 text-sm sm:text-base leading-snug">
                {{ $spkl->company->name }}
            </h4>
            <div class="flex items-start gap-1.5 pt-1 text-slate-500">
                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 mt-0.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <p class="text-[11px] font-medium leading-relaxed">
                    {{ $spkl->company->address }}
                </p>
            </div>
        </div>

        <!-- Mentors & Supervisors Section (Scalable & Non-Truncating) -->
        <div class="space-y-2.5">
            <!-- Mentor DU/DI (Industri) -->
            <div class="p-3.5 rounded-2xl bg-white border border-slate-200/80 shadow-2xs flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 border border-amber-200/80 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="text-[10px] font-extrabold text-amber-800 uppercase tracking-wider block">
                        Mentor DU/DI (Industri)
                    </span>
                    <span class="font-extrabold text-slate-900 text-xs sm:text-sm block leading-snug mt-0.5 break-words">
                        {{ $spkl->supervisor->user->name ?? 'Belum Ditentukan' }}
                    </span>
                    <p class="text-[11px] text-slate-500 font-medium leading-tight mt-0.5">
                        {{ $spkl->supervisor->position ?? 'Pembimbing Industri' }}
                    </p>
                </div>
            </div>

            <!-- Guru Pembimbing (Sekolah) -->
            <div class="p-3.5 rounded-2xl bg-white border border-slate-200/80 shadow-2xs flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-200/80 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="text-[10px] font-extrabold text-indigo-700 uppercase tracking-wider block">
                        Guru Pembimbing (Sekolah)
                    </span>
                    <span class="font-extrabold text-slate-900 text-xs sm:text-sm block leading-snug mt-0.5 break-words">
                        {{ $spkl->teacher->user->name ?? 'Belum Ditentukan' }}
                    </span>
                    <p class="text-[11px] text-slate-500 font-mono font-medium leading-tight mt-0.5">
                        NIP: {{ $spkl->teacher->nip ?? '-' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Periode & Program PKL Footer -->
        <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Periode Pelaksanaan</span>
                <div class="flex items-center gap-1.5 text-xs font-bold text-slate-700 mt-0.5">
                    <svg class="w-3.5 h-3.5 text-slate-400 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>
                        {{ \Carbon\Carbon::parse($spkl->pklPeriod->start_date)->isoFormat('D MMMM Y') }} &ndash; {{ \Carbon\Carbon::parse($spkl->pklPeriod->end_date)->isoFormat('D MMMM Y') }}
                    </span>
                </div>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200/80 shadow-2xs self-start sm:self-auto">
                {{ $spkl->pklPeriod->name }}
            </span>
        </div>
    </div>

    <!-- 3. Final Assessment Card (Consistent Light Academic Theme) -->
    @if($assessment)
        @php
            $finalScore = (float) $assessment->final_score;
            $predicateLabel = match (true) {
                $finalScore >= 90 => 'A (Sangat Memuaskan)',
                $finalScore >= 80 => 'B (Memuaskan)',
                $finalScore >= 70 => 'C (Cukup)',
                default => 'D (Perlu Bimbingan)',
            };
            $predicateColor = match (true) {
                $finalScore >= 90 => 'bg-emerald-50 text-emerald-700 border-emerald-200/80',
                $finalScore >= 80 => 'bg-indigo-50 text-indigo-700 border-indigo-200/80',
                $finalScore >= 70 => 'bg-amber-50 text-amber-700 border-amber-200/80',
                default => 'bg-rose-50 text-rose-700 border-rose-200/80',
            };
        @endphp

        <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 border border-amber-200/80 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider leading-none">Lembar Nilai Akhir PKL</h3>
                        <p class="text-[11px] text-slate-400 font-medium mt-0.5">Transkrip Evaluasi Terpadu</p>
                    </div>
                </div>
                <x-badge variant="success" size="xs">
                    Nilai Final
                </x-badge>
            </div>

            <!-- Grand Score Spotlight Hero -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-50/70 via-slate-50 to-indigo-50/30 border border-indigo-100/80 p-4 sm:p-5 flex items-center justify-between shadow-2xs">
                <div class="space-y-1">
                    <span class="text-[10px] font-black text-indigo-700 uppercase tracking-widest block">
                        Skor Akhir Terpadu
                    </span>
                    <p class="text-xs text-slate-500 font-medium">
                        Bobot resmi: 60% DU/DI + 40% Sekolah
                    </p>
                    <div class="pt-1">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border shadow-2xs {{ $predicateColor }}">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                            <span>Predikat:</span>
                            <span>{{ $predicateLabel }}</span>
                        </span>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-4xl sm:text-5xl font-black text-indigo-900 tracking-tight font-sans">
                        {{ number_format($finalScore, 1) }}
                    </div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mt-0.5">
                        Skala 100
                    </span>
                </div>
            </div>

            <!-- Detailed Breakdown (DU/DI & Sekolah) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                <!-- DU/DI (Industri) -->
                <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200/70 space-y-2.5">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                        <span class="text-[10px] font-extrabold text-amber-800 uppercase tracking-wider">DU/DI (Industri)</span>
                        <span class="text-[10px] font-extrabold text-amber-700 bg-amber-100/80 px-2 py-0.5 rounded-md border border-amber-200">Bobot 60%</span>
                    </div>
                    <div class="space-y-2 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 font-medium text-[11px]">Kedisiplinan & Etos</span>
                            <span class="font-black text-slate-900 px-2 py-0.5 bg-white rounded-lg border border-slate-200/80 font-mono shadow-2xs">
                                {{ number_format($assessment->score_dudi_discipline, 0) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 font-medium text-[11px]">Kompetensi Teknis</span>
                            <span class="font-black text-slate-900 px-2 py-0.5 bg-white rounded-lg border border-slate-200/80 font-mono shadow-2xs">
                                {{ number_format($assessment->score_dudi_technical, 0) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 font-medium text-[11px]">Inisiatif & Tanggung Jawab</span>
                            <span class="font-black text-slate-900 px-2 py-0.5 bg-white rounded-lg border border-slate-200/80 font-mono shadow-2xs">
                                {{ number_format($assessment->score_dudi_initiative, 0) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Sekolah (Guru Pembimbing) -->
                <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200/70 space-y-2.5 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                            <span class="text-[10px] font-extrabold text-indigo-800 uppercase tracking-wider">Sekolah (Guru)</span>
                            <span class="text-[10px] font-extrabold text-indigo-700 bg-indigo-100/80 px-2 py-0.5 rounded-md border border-indigo-200">Bobot 40%</span>
                        </div>
                        <div class="space-y-2 text-xs mt-2.5">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-600 font-medium text-[11px]">Laporan & Jurnal PKL</span>
                                <span class="font-black text-slate-900 px-2 py-0.5 bg-white rounded-lg border border-slate-200/80 font-mono shadow-2xs">
                                    {{ number_format($assessment->score_teacher_report, 0) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between text-[10px] text-slate-500 font-medium">
                        <span>Bimbingan Akademik</span>
                        <span class="text-emerald-600 font-bold flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Selesai
                        </span>
                    </div>
                </div>
            </div>

            <!-- Supervisor Notes & Feedback (Speech Bubble Cards) -->
            @if($assessment->notes_dudi || $assessment->notes_teacher)
                <div class="space-y-2.5 pt-1">
                    @if($assessment->notes_dudi)
                        <div class="p-3.5 rounded-2xl bg-amber-50/70 border border-amber-200/80 space-y-1">
                            <div class="flex items-center gap-1.5 text-amber-800">
                                <svg class="w-3.5 h-3.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                <span class="text-[10px] font-black uppercase tracking-wider">Catatan Mentor Industri (DU/DI):</span>
                            </div>
                            <p class="text-xs text-slate-700 leading-relaxed font-medium italic pl-5">
                                &ldquo;{{ $assessment->notes_dudi }}&rdquo;
                            </p>
                        </div>
                    @endif

                    @if($assessment->notes_teacher)
                        <div class="p-3.5 rounded-2xl bg-indigo-50/70 border border-indigo-200/80 space-y-1">
                            <div class="flex items-center gap-1.5 text-indigo-800">
                                <svg class="w-3.5 h-3.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                <span class="text-[10px] font-black uppercase tracking-wider">Catatan Guru Pembimbing:</span>
                            </div>
                            <p class="text-xs text-slate-700 leading-relaxed font-medium italic pl-5">
                                &ldquo;{{ $assessment->notes_teacher }}&rdquo;
                            </p>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @else
        <!-- Assessment Pending Notice -->
        <div class="p-4 rounded-3xl bg-white border border-slate-200/80 shadow-xs flex items-center space-x-3 text-slate-600">
            <div class="w-10 h-10 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-center shrink-0 text-slate-400">
                <svg class="w-5 h-5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <span class="text-xs font-bold text-slate-900 block leading-tight">Lembar Nilai Belum Terbit</span>
                <span class="text-[11px] text-slate-400 font-medium leading-relaxed block mt-0.5">Penilaian akhir akan muncul setelah dievaluasi oleh mentor DU/DI dan guru pembimbing.</span>
            </div>
        </div>
    @endif

    <!-- E-Sertifikat & Transkrip PKL (Sprint 4) -->
    @if(isset($certificate) && $certificate)
        <div class="relative overflow-hidden bg-gradient-to-br from-indigo-900 via-slate-900 to-slate-900 rounded-3xl p-5 border border-indigo-500/40 shadow-lg text-white space-y-4">
            <div class="absolute -right-8 -bottom-10 w-36 h-36 rounded-full bg-indigo-500/10 blur-xl pointer-events-none"></div>

            <div class="flex items-center justify-between pb-3 border-b border-white/10">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-2xl bg-amber-500/20 border border-amber-500/40 text-amber-300 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <h3 class="text-xs font-black uppercase tracking-wider text-white">E-Sertifikat PKL Resmi</h3>
                        </div>
                        <p class="text-[10px] text-slate-300 font-medium">Nomor: {{ $certificate->certificate_number }}</p>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    {{ $certificate->final_predicate }}
                </span>
            </div>

            <div class="bg-white/5 rounded-2xl p-3.5 border border-white/10 text-xs text-slate-300 space-y-1">
                <div class="flex justify-between">
                    <span class="text-slate-400">Skor Akhir Evaluasi:</span>
                    <strong class="text-white font-mono text-sm">{{ number_format($certificate->final_score, 1) }} / 100</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Tanggal Penerbitan:</span>
                    <strong class="text-slate-200">{{ $certificate->issued_date ? $certificate->issued_date->translatedFormat('d M Y') : '-' }}</strong>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-2 pt-1">
                <a href="{{ route('student.certificate.download') }}" class="flex-1 py-3 px-4 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs text-center transition-colors shadow-md shadow-indigo-600/30 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Unduh E-Sertifikat (PDF)</span>
                </a>
                <a href="{{ $certificate->verification_url }}" target="_blank" class="py-3 px-4 rounded-2xl bg-white/10 hover:bg-white/15 text-white font-bold text-xs text-center transition-colors border border-white/15 flex items-center justify-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    <span>Validasi QR</span>
                </a>
            </div>
        </div>
    @endif

    <!-- 4. Portal Monitoring Orang Tua (1 Siswa 1 Link Tanpa Login) -->
    <div class="p-5 rounded-3xl bg-white border border-slate-200/80 shadow-xs space-y-4">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center space-x-2.5">
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 border border-indigo-200/80 text-indigo-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-900">Portal Monitoring Orang Tua</h3>
                    <p class="text-[11px] text-slate-500 font-medium mt-0.5">Link khusus untuk orang tua tanpa perlu login</p>
                </div>
            </div>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span>Aktif</span>
            </span>
        </div>

        <p class="text-xs text-slate-600 leading-relaxed">
            Berikan tautan ini kepada orang tua/wali Anda. Mereka dapat langsung memantau kehadiran harian dan jurnal PKL Anda secara aman tanpa perlu membuat akun atau memasukkan kata sandi.
        </p>

        @php
            $monitorUrl = $student->parent_monitoring_url ?? route('parent.monitor', ['token' => $student->parent_token ?? '']);
            $waMessage = rawurlencode("Halo Ayah/Bunda, ini link khusus untuk memantau kehadiran & jurnal kegiatan PKL saya di VokaTrack tanpa perlu login:\n" . $monitorUrl);
        @endphp

        <div class="space-y-2">
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Tautan Pribadi Orang Tua</label>
            <div class="flex items-center gap-2">
                <input type="text" id="parent-portal-link" readonly value="{{ $monitorUrl }}" class="flex-1 bg-slate-50 border border-slate-200 rounded-2xl px-3.5 py-2.5 text-xs text-slate-700 font-mono select-all focus:outline-hidden focus:border-indigo-400">
                <button type="button" onclick="copyParentPortalLink()" id="btn-copy-link" class="shrink-0 px-3.5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-bold text-xs rounded-2xl transition-all shadow-xs flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    <span id="copy-text">Salin</span>
                </button>
            </div>
        </div>

        <div class="flex items-center gap-2 pt-1">
            <a href="https://wa.me/?text={{ $waMessage }}" target="_blank" class="flex-1 py-3 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] text-white font-bold text-xs transition-all flex items-center justify-center gap-2 shadow-xs cursor-pointer">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                <span>Bagikan ke WhatsApp</span>
            </a>

            <form action="{{ route('student.parent-token.regenerate') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membuat ulang tautan monitoring? Tautan lama yang sudah dibagikan sebelumnya tidak akan dapat digunakan lagi.');">
                @csrf
                <button type="submit" title="Buat Ulang Link Baru (Reset)" class="py-3 px-3.5 rounded-2xl bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 font-bold text-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4 text-slate-600 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span class="hidden sm:inline">Reset</span>
                </button>
            </form>
        </div>
    </div>

    <!-- 5. Keamanan Akun & Ubah Kata Sandi Mandiri -->
    <div class="p-5 rounded-3xl bg-white border border-slate-200/80 shadow-xs space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2.5">
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 border border-indigo-200/80 text-indigo-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-900">Keamanan Akun Siswa</h3>
                    <p class="text-[11px] text-slate-500 font-medium mt-0.5">Ubah kata sandi secara mandiri</p>
                </div>
            </div>
            <button type="button" onclick="openChangePasswordModal()" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-bold text-xs rounded-xl transition-all shadow-xs flex items-center gap-1.5 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                <span>Ubah Sandi</span>
            </button>
        </div>
        <p class="text-xs text-slate-600 leading-relaxed">
            Jaga kerahasiaan kata sandi Anda untuk memastikan data absensi dan catatan jurnal PKL Anda tetap aman dan terlindungi.
        </p>
    </div>

    <!-- 6. Account & Session Management -->
    <div class="p-4 rounded-3xl bg-white border border-slate-200/80 shadow-xs space-y-3">
        <form action="{{ route('logout') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin keluar dari akun Siswa?');">
            @csrf
            <button type="submit" class="w-full py-3.5 px-4 rounded-2xl bg-rose-50 hover:bg-rose-100 active:bg-rose-200 border border-rose-200/80 text-rose-700 font-bold text-xs transition-colors flex items-center justify-center space-x-2 cursor-pointer shadow-2xs">
                <svg class="w-4 h-4 text-rose-600 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span>Keluar dari Akun Siswa</span>
            </button>
        </form>
        <p class="text-[10px] text-slate-400 text-center font-medium">
            VokaTrack &bull; Sistem Monitoring PKL Vokasi
        </p>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function copyParentPortalLink() {
        const input = document.getElementById('parent-portal-link');
        const copyText = document.getElementById('copy-text');
        if (!input) return;

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(input.value).then(() => {
                if (copyText) {
                    copyText.innerText = 'Tersalin!';
                    setTimeout(() => { copyText.innerText = 'Salin'; }, 2000);
                }
            }).catch(() => {
                fallbackCopy(input, copyText);
            });
        } else {
            fallbackCopy(input, copyText);
        }
    }

    function fallbackCopy(input, copyText) {
        input.select();
        input.setSelectionRange(0, 99999);
        document.execCommand('copy');
        if (copyText) {
            copyText.innerText = 'Tersalin!';
            setTimeout(() => { copyText.innerText = 'Salin'; }, 2000);
        }
    }
</script>
@endpush
