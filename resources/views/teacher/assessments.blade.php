@extends('layouts.app')

@section('page_title', 'Penilaian Akademik PKL (Guru Pembimbing)')
@section('page_subtitle', 'Pengisian nilai laporan akhir, bimbingan, dan evaluasi capaian kompetensi')

@section('content')
<div class="w-full space-y-6">

    <!-- Enterprise Hero Banner -->
    <x-page-header 
        title="Penilaian Akademik Siswa PKL" 
        subtitle="Evaluasi laporan PKL dan penguasaan kompetensi vokasi. Nilai akhir dihitung otomatis berdasarkan formula terpadu (Bobot Industri DU/DI : Sekolah)."
        badge="GURU PEMBIMBING"
    >
        <x-button type="button" variant="secondary" size="sm" onclick="window.location.href='{{ route('guru.journals') }}'">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Review Jurnal</span>
        </x-button>
        <x-button type="button" variant="primary" size="sm" onclick="window.location.href='{{ route('guru.reports') }}'">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Rekap & Ekspor Laporan</span>
        </x-button>
    </x-page-header>

    @php
        $wDudi = $weightDudi ?? 60;
        $wGuru = $weightGuru ?? 40;

        $totalEvaluated = $studentPkls->filter(fn($sp) => $sp->assessment && $sp->assessment->score_teacher_report !== null && $sp->assessment->score_teacher_report > 0)->count();
        $pendingTeacher = $studentPkls->filter(fn($sp) => !$sp->assessment || $sp->assessment->score_teacher_report === null || $sp->assessment->score_teacher_report == 0)->count();
        $pendingDudi = $studentPkls->filter(fn($sp) => !$sp->assessment || ($sp->assessment->score_dudi_discipline == 0 && $sp->assessment->score_dudi_technical == 0))->count();
        $completeBoth = $studentPkls->filter(fn($sp) => $sp->assessment && $sp->assessment->final_score !== null && $sp->assessment->final_score > 0)->count();
    @endphp

    <!-- Top KPI Metrics -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <x-stat-card 
            title="Total Siswa Binaan" 
            :value="$studentPkls->count() . ' Siswa'" 
            subtitle="Alokasi bimbingan aktif"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Sudah Dinilai Guru" 
            :value="$totalEvaluated" 
            subtitle="Nilai laporan & bimbingan"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Perlu Dinilai Guru" 
            :value="$pendingTeacher" 
            subtitle="Menunggu input nilai sekolah"
            :color="$pendingTeacher > 0 ? 'amber' : 'indigo'"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Nilai Lengkap Terpadu" 
            :value="$completeBoth" 
            subtitle="Siap terbit ke sertifikat"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Formula Transparency & Rubric Guide Bar -->
    <div class="bg-gradient-to-r from-indigo-900/5 via-indigo-600/5 to-slate-900/5 rounded-2xl border border-indigo-100 p-4 sm:p-4.5 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
        <div class="flex items-start sm:items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-xs shadow-indigo-600/30">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-extrabold text-slate-900">Formula Nilai Akhir Terpadu:</span>
                    <span class="px-2 py-0.5 rounded-md font-mono font-bold bg-white text-indigo-700 border border-indigo-200 shadow-2xs">
                        ({{ $wDudi }}% Skor Industri) + ({{ $wGuru }}% Skor Sekolah)
                    </span>
                </div>
                <p class="text-slate-500 mt-0.5 text-[11px]">
                    Skor Industri dihitung dari rata-rata (Disiplin + Teknis + Inisiatif). Skor Sekolah dari evaluasi laporan akhir & seminar bimbingan.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 shrink-0 flex-wrap">
            <span class="px-2 py-1 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 font-bold text-[10px]">A: ≥ 90 (Sangat Baik)</span>
            <span class="px-2 py-1 rounded-lg bg-blue-50 text-blue-800 border border-blue-200 font-bold text-[10px]">B: 80–89.9 (Baik)</span>
            <span class="px-2 py-1 rounded-lg bg-amber-50 text-amber-800 border border-amber-200 font-bold text-[10px]">C: 70–79.9 (Cukup)</span>
            <span class="px-2 py-1 rounded-lg bg-rose-50 text-rose-800 border border-rose-200 font-bold text-[10px]">D: &lt; 70</span>
        </div>
    </div>

    <!-- Interactive Search, Filter & View Controls -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-3.5 sm:p-4 shadow-xs space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            
            <!-- Search Bar -->
            <div class="relative flex-1 max-w-md">
                <input type="text" id="assessment-search" placeholder="Cari nama siswa, NISN, atau mitra DU/DI..." 
                       oninput="filterAssessmentList()"
                       class="w-full text-xs font-semibold pl-9 pr-8 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-slate-800 placeholder-slate-400 transition-all">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <button type="button" id="clear-search-btn" onclick="clearAssessmentSearch()" class="hidden absolute right-2.5 top-2.5 p-0.5 text-slate-400 hover:text-slate-600 rounded">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- View Switcher -->
            <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl border border-slate-200 shrink-0 self-start sm:self-auto">
                <button type="button" id="btn-view-cards" onclick="switchAssessmentView('cards')" 
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-white text-indigo-700 shadow-2xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span>Mode Kartu</span>
                </button>
                <button type="button" id="btn-view-table" onclick="switchAssessmentView('table')" 
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-600 hover:text-slate-900">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>Mode Tabel</span>
                </button>
            </div>
        </div>

        <!-- Filter Status Pills -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-0.5 pt-1 text-xs">
            <span class="text-slate-400 font-semibold text-[11px] mr-1 hidden sm:inline">Filter:</span>
            <button type="button" onclick="filterByStatus('all')" class="status-filter-btn px-3 py-1 rounded-lg text-xs font-bold transition-all bg-indigo-600 text-white" data-filter="all">
                Semua ({{ $studentPkls->count() }})
            </button>
            <button type="button" onclick="filterByStatus('pending_teacher')" class="status-filter-btn px-3 py-1 rounded-lg text-xs font-semibold transition-all bg-slate-100 text-slate-700 hover:bg-slate-200" data-filter="pending_teacher">
                Perlu Nilai Guru ({{ $pendingTeacher }})
            </button>
            <button type="button" onclick="filterByStatus('pending_dudi')" class="status-filter-btn px-3 py-1 rounded-lg text-xs font-semibold transition-all bg-slate-100 text-slate-700 hover:bg-slate-200" data-filter="pending_dudi">
                Menunggu DU/DI ({{ $pendingDudi }})
            </button>
            <button type="button" onclick="filterByStatus('complete')" class="status-filter-btn px-3 py-1 rounded-lg text-xs font-semibold transition-all bg-slate-100 text-slate-700 hover:bg-slate-200" data-filter="complete">
                Lengkap ({{ $completeBoth }})
            </button>
        </div>
    </div>

    <!-- Empty Search State -->
    <div id="empty-filter-state" class="hidden py-12 text-center bg-white rounded-2xl border border-slate-200/80 p-6 space-y-3">
        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <h4 class="text-sm font-bold text-slate-800">Tidak Ada Siswa Sesuai Filter</h4>
        <p class="text-xs text-slate-500 max-w-sm mx-auto">Coba ubah kata kunci pencarian atau ganti status filter penilaian di atas.</p>
        <button type="button" onclick="resetAssessmentFilters()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors">
            Reset Filter
        </button>
    </div>

    @if($studentPkls->isEmpty())
        <x-empty-state 
            title="Belum Ada Siswa Binaan" 
            description="Anda belum memiliki siswa yang diplot untuk dinilai."
        />
    @else

        <!-- ========================================================================= -->
        <!-- VIEW 1: MODERN CARDS VIEW (KOMPREHENSIF & SEIMBANG)                       -->
        <!-- ========================================================================= -->
        <div id="assessment-cards-container" class="space-y-5">
            @foreach($studentPkls as $sp)
                @php
                    $a = $sp->assessment;
                    $hasDudiScore = $a && ($a->score_dudi_discipline > 0 || $a->score_dudi_technical > 0 || $a->score_dudi_initiative > 0);
                    $hasTeacherScore = $a && $a->score_teacher_report !== null && $a->score_teacher_report > 0;
                    $isComplete = $a && $a->final_score !== null && $a->final_score > 0;

                    $avgDudi = $hasDudiScore ? round(($a->score_dudi_discipline + $a->score_dudi_technical + $a->score_dudi_initiative) / 3, 1) : 0;
                    $scoreTeacher = $hasTeacherScore ? (float)$a->score_teacher_report : 0;

                    $contribDudi = $hasDudiScore ? round($avgDudi * ($wDudi / 100), 2) : 0;
                    $contribGuru = $hasTeacherScore ? round($scoreTeacher * ($wGuru / 100), 2) : 0;

                    // Predicate
                    $finalScoreVal = $isComplete ? (float)$a->final_score : null;
                    $predicate = null;
                    $predicateClass = '';
                    if ($finalScoreVal !== null) {
                        if ($finalScoreVal >= 90) {
                            $predicate = 'A (Sangat Baik)';
                            $predicateClass = 'bg-emerald-50 text-emerald-800 border-emerald-200';
                        } elseif ($finalScoreVal >= 80) {
                            $predicate = 'B (Baik)';
                            $predicateClass = 'bg-blue-50 text-blue-800 border-blue-200';
                        } elseif ($finalScoreVal >= 70) {
                            $predicate = 'C (Cukup)';
                            $predicateClass = 'bg-amber-50 text-amber-800 border-amber-200';
                        } else {
                            $predicate = 'D (Kurang)';
                            $predicateClass = 'bg-rose-50 text-rose-800 border-rose-200';
                        }
                    }

                    // Status Flag for Filter
                    $statusTag = 'pending_teacher';
                    if ($isComplete) {
                        $statusTag = 'complete';
                    } elseif (!$hasDudiScore) {
                        $statusTag = 'pending_dudi';
                    }
                @endphp

                <div class="assessment-card bg-white rounded-2xl border border-slate-200/90 shadow-xs hover:border-slate-300 hover:shadow-sm transition-all duration-200 overflow-hidden"
                     id="student-card-{{ $sp->id }}"
                     data-name="{{ strtolower($sp->student->user->name) }}"
                     data-nisn="{{ $sp->student->nisn }}"
                     data-company="{{ strtolower($sp->company->name ?? '') }}"
                     data-status="{{ $statusTag }}">

                    <!-- Card Header: Student Profile & Composite Score -->
                    <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gradient-to-r from-slate-50/70 via-white to-slate-50/40">
                        
                        <!-- Left: Student Details -->
                        <div class="flex items-start sm:items-center space-x-3.5 min-w-0">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-indigo-800 text-white font-black text-base flex items-center justify-center shrink-0 shadow-sm shadow-indigo-600/25">
                                {{ strtoupper(substr($sp->student->user->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-base font-extrabold text-slate-900 truncate tracking-tight">{{ $sp->student->user->name }}</h3>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200/70">
                                        {{ $sp->student->class->name ?? 'Kelas' }}
                                    </span>
                                    @if($isComplete)
                                        <x-badge variant="success" size="xs">Nilai Terpadu Lengkap</x-badge>
                                    @elseif(!$hasDudiScore && $hasTeacherScore)
                                        <x-badge variant="info" size="xs">Menunggu DU/DI</x-badge>
                                    @elseif(!$hasTeacherScore)
                                        <x-badge variant="warning" size="xs">Perlu Nilai Guru</x-badge>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2.5 text-xs text-slate-500 mt-1.5 flex-wrap">
                                    <span class="font-mono text-[11px] text-slate-500">NISN: {{ $sp->student->nisn }}</span>
                                    <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                    <span class="inline-flex items-center gap-1.5 font-semibold text-slate-700 truncate">
                                        <svg class="w-3.5 h-3.5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        {{ $sp->company->name ?? 'Mitra DU/DI' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Integrated Composite Score Badge -->
                        <div class="flex items-center md:items-end justify-between md:justify-end gap-4 shrink-0 pt-3 md:pt-0 border-t md:border-t-0 border-slate-100">
                            <div class="text-left md:text-right">
                                <span class="text-[10px] text-slate-400 font-extrabold uppercase tracking-wider block">Nilai Akhir Terpadu</span>
                                <div class="flex items-baseline md:justify-end gap-2 mt-0.5">
                                    <span class="text-3xl font-black font-mono tracking-tight {{ $finalScoreVal ? 'text-indigo-700' : 'text-slate-300' }}">
                                        {{ $finalScoreVal ? number_format($finalScoreVal, 1) : '--' }}
                                    </span>
                                    @if($predicate)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $predicateClass }}">
                                            {{ $predicate }}
                                        </span>
                                    @endif
                                </div>
                                <span class="text-[10px] text-slate-400 font-medium block mt-0.5">
                                    @if($isComplete)
                                        DU/DI: {{ $contribDudi }} + Guru: {{ $contribGuru }}
                                    @else
                                        Kalkulasi otomatis saat data lengkap
                                    @endif
                                </span>
                            </div>
                        </div>

                    </div>

                    <!-- Card Body: Two Symmetrical Balanced Columns -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-slate-100 bg-slate-50/30">
                        
                        <!-- Left Column: Skor Pembimbing DU/DI (Industri) -->
                        <div class="p-5 sm:p-6 space-y-4 flex flex-col justify-between">
                            <div class="space-y-4">
                                <!-- Subheader -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-indigo-50 border border-indigo-100 text-indigo-700 flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Skor Pembimbing Industri (DU/DI)</h4>
                                            <span class="text-[10px] text-slate-500 font-medium">Praktik Lapangan, Sikap & Etos Kerja</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200/80">
                                            Bobot {{ $wDudi }}%
                                        </span>
                                        @if($hasDudiScore)
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80 font-mono">
                                                Rata-rata: {{ $avgDudi }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                @if($hasDudiScore)
                                    <!-- 3 Modern Score Metrics -->
                                    <div class="grid grid-cols-3 gap-2.5 pt-1">
                                        <div class="bg-white p-3 rounded-xl border border-slate-200/80 shadow-2xs text-center space-y-1">
                                            <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Disiplin</span>
                                            <span class="text-xl font-black text-slate-900 font-mono block">{{ $a->score_dudi_discipline }}</span>
                                            <span class="text-[9px] text-slate-400 font-medium block">Etos & SOP K3</span>
                                        </div>
                                        <div class="bg-white p-3 rounded-xl border border-slate-200/80 shadow-2xs text-center space-y-1">
                                            <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Teknis</span>
                                            <span class="text-xl font-black text-slate-900 font-mono block">{{ $a->score_dudi_technical }}</span>
                                            <span class="text-[9px] text-slate-400 font-medium block">Keahlian Kerja</span>
                                        </div>
                                        <div class="bg-white p-3 rounded-xl border border-slate-200/80 shadow-2xs text-center space-y-1">
                                            <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Inisiatif</span>
                                            <span class="text-xl font-black text-slate-900 font-mono block">{{ $a->score_dudi_initiative }}</span>
                                            <span class="text-[9px] text-slate-400 font-medium block">Kerjasama Tim</span>
                                        </div>
                                    </div>

                                    <!-- Mentor Quote & Notes -->
                                    @if($a->notes_dudi)
                                        <div class="bg-white p-3.5 rounded-xl border border-slate-200/80 shadow-2xs space-y-1.5">
                                            <div class="flex items-center gap-1.5 text-[11px] font-bold text-slate-600">
                                                <svg class="w-3.5 h-3.5 text-indigo-500 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/></svg>
                                                <span>Catatan Mentor Lapangan:</span>
                                            </div>
                                            <p class="text-xs text-slate-600 italic font-medium leading-relaxed pl-5">
                                                "{{ $a->notes_dudi }}"
                                            </p>
                                        </div>
                                    @endif
                                @else
                                    <!-- Awaiting DU/DI State -->
                                    <div class="py-7 px-4 bg-white rounded-xl border border-dashed border-amber-200 text-center space-y-2">
                                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto border border-amber-100">
                                            <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                        <div>
                                            <h5 class="text-xs font-bold text-amber-900">Menunggu Penilaian Mentor Industri</h5>
                                            <p class="text-[11px] text-slate-500 mt-1 max-w-xs mx-auto leading-normal">
                                                Pihak DU/DI belum menginput nilai kompetensi lapangan. Anda tetap dapat menyimpan nilai sekolah di sisi kanan sekarang.
                                            </p>
                                        </div>
                                        <div class="pt-1">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                Status: Pending di Mentor DU/DI
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Footer DU/DI Contribution Note -->
                            <div class="pt-3 border-t border-slate-100 text-[11px] text-slate-500 flex items-center justify-between">
                                <span>Kontribusi Skor Industri:</span>
                                <span class="font-mono font-bold text-slate-800">
                                    {{ $hasDudiScore ? $contribDudi . ' poin (' . $wDudi . '%)' : 'Menunggu input (Bobot ' . $wDudi . '%)' }}
                                </span>
                            </div>
                        </div>

                        <!-- Right Column: Input Nilai Sekolah (Guru Pembimbing) -->
                        <div class="p-5 sm:p-6 space-y-4 flex flex-col justify-between bg-white lg:bg-transparent">
                            <form action="{{ route('guru.assessments.store') }}" method="POST" class="space-y-4">
                                @csrf
                                <input type="hidden" name="student_pkl_id" value="{{ $sp->id }}">

                                <!-- Subheader -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-emerald-50 border border-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Input Nilai Sekolah (Guru Pembimbing)</h4>
                                            <span class="text-[10px] text-slate-500 font-medium">Laporan Akhir, Bimbingan & Seminar</span>
                                        </div>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                                        Bobot {{ $wGuru }}%
                                    </span>
                                </div>

                                <!-- Input: Score -->
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label for="score-input-{{ $sp->id }}" class="text-xs font-bold text-slate-700">
                                            Skor Laporan & Seminar Bimbingan (0–100) <span class="text-rose-500">*</span>
                                        </label>
                                        <span id="preview-calc-{{ $sp->id }}" class="text-[11px] font-mono font-bold text-indigo-600 transition-opacity">
                                            @if($hasTeacherScore)
                                                Kontribusi: {{ $contribGuru }} poin
                                            @endif
                                        </span>
                                    </div>
                                    <div class="relative">
                                        <input type="number" 
                                               id="score-input-{{ $sp->id }}"
                                               name="score_teacher_report" 
                                               step="0.1" 
                                               min="0" 
                                               max="100" 
                                               value="{{ $hasTeacherScore ? $a->score_teacher_report : '' }}" 
                                               required 
                                               placeholder="Contoh: 88.5" 
                                               data-avg-dudi="{{ $avgDudi }}"
                                               data-has-dudi="{{ $hasDudiScore ? '1' : '0' }}"
                                               data-weight-dudi="{{ $wDudi }}"
                                               data-weight-guru="{{ $wGuru }}"
                                               data-student-id="{{ $sp->id }}"
                                               oninput="updateLiveScorePreview(this)"
                                               class="w-full text-sm font-mono font-extrabold px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 text-slate-900 shadow-2xs transition-all">
                                        <div class="absolute right-3 top-2.5 text-xs font-bold text-slate-400 pointer-events-none">
                                            / 100
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between mt-1 text-[11px] text-slate-400">
                                        <span>Capaian kompetensi sistematika penulisan & ujian</span>
                                        <span id="live-predict-badge-{{ $sp->id }}" class="hidden font-semibold text-emerald-600"></span>
                                    </div>
                                </div>

                                <!-- Input: Notes -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Catatan Evaluasi Guru Pembimbing</label>
                                    <textarea name="notes_teacher" rows="2" placeholder="Catatan kemajuan kompetensi siswa atau evaluasi sistematika laporan PKL..." 
                                              class="w-full text-xs font-medium p-3 rounded-xl bg-white border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 text-slate-900 leading-relaxed shadow-2xs transition-all">{{ $a ? $a->notes_teacher : '' }}</textarea>
                                </div>

                                <!-- Submit Actions -->
                                <div class="pt-2 flex items-center justify-between gap-3">
                                    <div class="text-[11px] text-slate-400">
                                        @if($hasTeacherScore)
                                            <span class="text-emerald-700 font-bold inline-flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Tersimpan
                                            </span>
                                        @else
                                            <span class="text-amber-600 font-medium">Belum disimpan</span>
                                        @endif
                                    </div>

                                    <x-button type="submit" variant="primary" size="md" class="shrink-0">
                                        <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                                        <span>{{ $hasTeacherScore ? 'Perbarui Nilai' : 'Simpan & Hitung Nilai' }}</span>
                                    </x-button>
                                </div>
                            </form>
                        </div>

                    </div>

                </div>
            @endforeach
        </div>

        <!-- ========================================================================= -->
        <!-- VIEW 2: COMPACT TABLE VIEW (SOLUSI SCALABLE UNTUK BANYAK SISWA)           -->
        <!-- ========================================================================= -->
        <div id="assessment-table-container" class="hidden bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse min-w-[940px]">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-4 sm:px-5 min-w-[200px]">Siswa & Kelas</th>
                            <th class="py-3.5 px-4 min-w-[160px]">Mitra DU/DI</th>
                            <th class="py-3.5 px-3 text-center min-w-[110px]">Skor Industri ({{ $wDudi }}%)</th>
                            <th class="py-3.5 px-3 text-center min-w-[110px]">Skor Sekolah ({{ $wGuru }}%)</th>
                            <th class="py-3.5 px-3 text-center min-w-[110px]">Nilai Akhir Terpadu</th>
                            <th class="py-3.5 px-4 text-center min-w-[110px]">Predikat / Status</th>
                            <th class="py-3.5 px-4 text-right table-sticky-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                        @foreach($studentPkls as $sp)
                            @php
                                $a = $sp->assessment;
                                $hasDudiScore = $a && ($a->score_dudi_discipline > 0 || $a->score_dudi_technical > 0 || $a->score_dudi_initiative > 0);
                                $hasTeacherScore = $a && $a->score_teacher_report !== null && $a->score_teacher_report > 0;
                                $isComplete = $a && $a->final_score !== null && $a->final_score > 0;
                                $avgDudi = $hasDudiScore ? round(($a->score_dudi_discipline + $a->score_dudi_technical + $a->score_dudi_initiative) / 3, 1) : 0;
                                $statusTag = $isComplete ? 'complete' : (!$hasDudiScore ? 'pending_dudi' : 'pending_teacher');
                            @endphp
                            <tr class="assessment-table-row hover:bg-slate-50/70 transition-colors group"
                                data-name="{{ strtolower($sp->student->user->name) }}"
                                data-nisn="{{ $sp->student->nisn }}"
                                data-company="{{ strtolower($sp->company->name ?? '') }}"
                                data-status="{{ $statusTag }}">
                                
                                <td class="py-3.5 px-4 sm:px-5">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-700 font-extrabold text-xs flex items-center justify-center shrink-0">
                                            {{ strtoupper(substr($sp->student->user->name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <span class="font-extrabold text-slate-900 block truncate">{{ $sp->student->user->name }}</span>
                                            <span class="text-[11px] text-slate-400 font-mono block">
                                                {{ $sp->student->class->name ?? '-' }} &bull; NISN: {{ $sp->student->nisn }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="font-semibold text-slate-800 block truncate max-w-xs">{{ $sp->company->name ?? '-' }}</span>
                                </td>

                                <td class="py-3.5 px-3 text-center">
                                    @if($hasDudiScore)
                                        <span class="font-extrabold font-mono text-slate-900 text-sm block">{{ $avgDudi }}</span>
                                        <span class="text-[10px] text-slate-400 block font-mono">D:{{ $a->score_dudi_discipline }} T:{{ $a->score_dudi_technical }} I:{{ $a->score_dudi_initiative }}</span>
                                    @else
                                        <span class="text-[11px] text-amber-600 font-semibold italic">Belum dinilai</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-3 text-center">
                                    @if($hasTeacherScore)
                                        <span class="font-extrabold font-mono text-emerald-700 text-sm block">{{ number_format($a->score_teacher_report, 1) }}</span>
                                        <span class="text-[10px] text-emerald-600/80 block">Tersimpan</span>
                                    @else
                                        <span class="text-[11px] text-amber-600 font-semibold italic">Belum dinilai</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-3 text-center">
                                    @if($isComplete)
                                        <span class="text-base font-black font-mono text-indigo-700">{{ number_format($a->final_score, 1) }}</span>
                                    @else
                                        <span class="text-slate-300 font-mono font-bold text-sm">--</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    @if($isComplete)
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                            @if($a->final_score >= 90) Predikat A
                                            @elseif($a->final_score >= 80) Predikat B
                                            @elseif($a->final_score >= 70) Predikat C
                                            @else Predikat D @endif
                                        </span>
                                    @elseif(!$hasDudiScore)
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            Menunggu DU/DI
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-800 border border-indigo-200">
                                            Perlu Nilai Guru
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-right whitespace-nowrap table-sticky-right">
                                    <button type="button" 
                                            onclick="openCardForStudent('{{ $sp->id }}')" 
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                                        <span>Beri Nilai</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    @endif

</div>

<!-- Client-side Interactive Filter & Realtime Score Calculation Engine -->
<script>
    let currentFilterStatus = 'all';

    function switchAssessmentView(mode) {
        const cardsContainer = document.getElementById('assessment-cards-container');
        const tableContainer = document.getElementById('assessment-table-container');
        const btnCards = document.getElementById('btn-view-cards');
        const btnTable = document.getElementById('btn-view-table');

        if (mode === 'table') {
            cardsContainer?.classList.add('hidden');
            tableContainer?.classList.remove('hidden');

            btnCards?.classList.remove('bg-white', 'text-indigo-700', 'shadow-2xs');
            btnCards?.classList.add('text-slate-600');

            btnTable?.classList.add('bg-white', 'text-indigo-700', 'shadow-2xs');
            btnTable?.classList.remove('text-slate-600');
        } else {
            tableContainer?.classList.add('hidden');
            cardsContainer?.classList.remove('hidden');

            btnTable?.classList.remove('bg-white', 'text-indigo-700', 'shadow-2xs');
            btnTable?.classList.add('text-slate-600');

            btnCards?.classList.add('bg-white', 'text-indigo-700', 'shadow-2xs');
            btnCards?.classList.remove('text-slate-600');
        }
    }

    function openCardForStudent(studentId) {
        switchAssessmentView('cards');
        const card = document.getElementById('student-card-' + studentId);
        if (card) {
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            card.classList.add('ring-2', 'ring-indigo-500', 'ring-offset-2');
            setTimeout(() => {
                card.classList.remove('ring-2', 'ring-indigo-500', 'ring-offset-2');
                const input = document.getElementById('score-input-' + studentId);
                input?.focus();
            }, 600);
        }
    }

    function filterByStatus(status) {
        currentFilterStatus = status;

        document.querySelectorAll('.status-filter-btn').forEach(btn => {
            if (btn.getAttribute('data-filter') === status) {
                btn.className = 'status-filter-btn px-3 py-1 rounded-lg text-xs font-bold transition-all bg-indigo-600 text-white';
            } else {
                btn.className = 'status-filter-btn px-3 py-1 rounded-lg text-xs font-semibold transition-all bg-slate-100 text-slate-700 hover:bg-slate-200';
            }
        });

        filterAssessmentList();
    }

    function clearAssessmentSearch() {
        const searchInput = document.getElementById('assessment-search');
        if (searchInput) {
            searchInput.value = '';
            filterAssessmentList();
        }
    }

    function resetAssessmentFilters() {
        const searchInput = document.getElementById('assessment-search');
        if (searchInput) searchInput.value = '';
        filterByStatus('all');
    }

    function filterAssessmentList() {
        const query = (document.getElementById('assessment-search')?.value || '').toLowerCase().trim();
        const clearBtn = document.getElementById('clear-search-btn');
        if (clearBtn) {
            clearBtn.classList.toggle('hidden', query === '');
        }

        const cards = document.querySelectorAll('.assessment-card');
        const tableRows = document.querySelectorAll('.assessment-table-row');
        let visibleCount = 0;

        cards.forEach(card => {
            const name = card.getAttribute('data-name') || '';
            const nisn = card.getAttribute('data-nisn') || '';
            const company = card.getAttribute('data-company') || '';
            const status = card.getAttribute('data-status') || '';

            const matchesQuery = query === '' || name.includes(query) || nisn.includes(query) || company.includes(query);
            const matchesStatus = currentFilterStatus === 'all' || status === currentFilterStatus;

            if (matchesQuery && matchesStatus) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        tableRows.forEach(row => {
            const name = row.getAttribute('data-name') || '';
            const nisn = row.getAttribute('data-nisn') || '';
            const company = row.getAttribute('data-company') || '';
            const status = row.getAttribute('data-status') || '';

            const matchesQuery = query === '' || name.includes(query) || nisn.includes(query) || company.includes(query);
            const matchesStatus = currentFilterStatus === 'all' || status === currentFilterStatus;

            if (matchesQuery && matchesStatus) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });

        const emptyState = document.getElementById('empty-filter-state');
        if (emptyState) {
            emptyState.classList.toggle('hidden', visibleCount > 0);
        }
    }

    function updateLiveScorePreview(input) {
        const studentId = input.getAttribute('data-student-id');
        const avgDudi = parseFloat(input.getAttribute('data-avg-dudi')) || 0;
        const hasDudi = input.getAttribute('data-has-dudi') === '1';
        const weightDudi = parseFloat(input.getAttribute('data-weight-dudi')) || 60;
        const weightGuru = parseFloat(input.getAttribute('data-weight-guru')) || 40;

        const val = parseFloat(input.value);
        const previewCalc = document.getElementById('preview-calc-' + studentId);
        const predictBadge = document.getElementById('live-predict-badge-' + studentId);

        if (!isNaN(val) && val >= 0 && val <= 100) {
            const contribGuru = (val * (weightGuru / 100)).toFixed(2);
            if (previewCalc) {
                previewCalc.textContent = `Kontribusi: ${contribGuru} poin (${weightGuru}%)`;
            }

            if (hasDudi) {
                const finalCalc = ((avgDudi * (weightDudi / 100)) + parseFloat(contribGuru)).toFixed(1);
                let pred = 'D';
                if (finalCalc >= 90) pred = 'A (Sangat Baik)';
                else if (finalCalc >= 80) pred = 'B (Baik)';
                else if (finalCalc >= 70) pred = 'C (Cukup)';

                if (predictBadge) {
                    predictBadge.classList.remove('hidden');
                    predictBadge.textContent = `Preview Nilai Akhir: ${finalCalc} • ${pred}`;
                }
            } else {
                if (predictBadge) {
                    predictBadge.classList.remove('hidden');
                    predictBadge.textContent = `Nilai akhir dihitung saat DU/DI mengisi skor`;
                }
            }
        } else {
            if (predictBadge) {
                predictBadge.classList.add('hidden');
            }
        }
    }
</script>
@endsection
