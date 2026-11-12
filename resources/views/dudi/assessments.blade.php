@extends('layouts.app')

@section('page_title', 'Penilaian Kompetensi Industri (DU/DI)')
@section('page_subtitle', 'Evaluasi etos kerja, kedisiplinan, dan penguasaan teknis siswa selama praktik kerja lapangan')

@section('content')
<div class="w-full space-y-6">

    <!-- Enterprise Hero Banner -->
    <x-page-header 
        title="Penilaian Praktik Industri (DU/DI)" 
        subtitle="Evaluasi etos kerja, kedisiplinan kerja, dan kompetensi teknis siswa magang. Nilai industri berkontribusi sebesar {{ $weightDudi ?? 60 }}% terhadap sertifikat akhir kelulusan PKL."
        badge="PEMBIMBING INDUSTRI DU/DI"
    >
        <x-button type="button" variant="secondary" size="sm" onclick="window.location.href='{{ route('dudi.attendances') }}'">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            <span>Validasi Presensi</span>
        </x-button>
        <x-button type="button" variant="primary" size="sm" onclick="window.location.href='{{ route('dudi.journals') }}'">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Approval Jurnal</span>
        </x-button>
    </x-page-header>

    @php
        $wDudi = $weightDudi ?? 60;
        $wGuru = $weightGuru ?? 40;

        $evaluatedCount = $studentPkls->filter(fn($sp) => $sp->assessment && ($sp->assessment->score_dudi_discipline > 0 || $sp->assessment->score_dudi_technical > 0))->count();
        $pendingCount = $studentPkls->count() - $evaluatedCount;
        $completedBoth = $studentPkls->filter(fn($sp) => $sp->assessment && $sp->assessment->final_score !== null && $sp->assessment->final_score > 0)->count();
    @endphp

    <!-- Top KPI Metrics -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <x-stat-card 
            title="Total Siswa Magang" 
            :value="$studentPkls->count() . ' Siswa'" 
            subtitle="Peserta magang di industri Anda"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Sudah Dinilai Industri" 
            :value="$evaluatedCount . ' Siswa'" 
            subtitle="Skor kompetensi tersimpan"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Belum Dinilai Industri" 
            :value="$pendingCount . ' Siswa'" 
            subtitle="Perlu input evaluasi mentor"
            :color="$pendingCount > 0 ? 'amber' : 'indigo'"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Bobot Kontribusi Nilai" 
            :value="$wDudi . '% Skor'" 
            subtitle="Penentu predikat kelulusan"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Formula Transparency & Rubric Guide Bar -->
    <div class="bg-gradient-to-r from-indigo-900/5 via-indigo-600/5 to-slate-900/5 rounded-2xl border border-indigo-100 p-4 sm:p-4.5 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
        <div class="flex items-start sm:items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-xs shadow-indigo-600/30">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-extrabold text-slate-900">Perhitungan Skor Industri:</span>
                    <span class="px-2 py-0.5 rounded-md font-mono font-bold bg-white text-indigo-700 border border-indigo-200 shadow-2xs">
                        Rata-rata: (Disiplin + Teknis + Inisiatif) / 3 &bull; Bobot Akhir: {{ $wDudi }}%
                    </span>
                </div>
                <p class="text-slate-500 mt-0.5 text-[11px]">
                    Evaluasi komprehensif mencakup kedisiplinan SOP kerja, keahlian teknis vokasi, dan proaktivitas dalam tim industri.
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
                <input type="text" id="dudi-assessment-search" placeholder="Cari nama siswa magang, NISN, atau kelas..." 
                       oninput="filterDudiAssessmentList()"
                       class="w-full text-xs font-semibold pl-9 pr-8 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-slate-800 placeholder-slate-400 transition-all">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <button type="button" id="clear-dudi-search-btn" onclick="clearDudiSearch()" class="hidden absolute right-2.5 top-2.5 p-0.5 text-slate-400 hover:text-slate-600 rounded">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- View Switcher -->
            <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl border border-slate-200 shrink-0 self-start sm:self-auto">
                <button type="button" id="btn-dudi-view-cards" onclick="switchDudiAssessmentView('cards')" 
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-white text-indigo-700 shadow-2xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span>Mode Kartu</span>
                </button>
                <button type="button" id="btn-dudi-view-table" onclick="switchDudiAssessmentView('table')" 
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-slate-600 hover:text-slate-900">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>Mode Tabel</span>
                </button>
            </div>
        </div>

        <!-- Filter Status Pills -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-0.5 pt-1 text-xs">
            <span class="text-slate-400 font-semibold text-[11px] mr-1 hidden sm:inline">Filter:</span>
            <button type="button" onclick="filterDudiByStatus('all')" class="dudi-status-filter-btn px-3 py-1 rounded-lg text-xs font-bold transition-all bg-indigo-600 text-white" data-filter="all">
                Semua ({{ $studentPkls->count() }})
            </button>
            <button type="button" onclick="filterDudiByStatus('pending')" class="dudi-status-filter-btn px-3 py-1 rounded-lg text-xs font-semibold transition-all bg-slate-100 text-slate-700 hover:bg-slate-200" data-filter="pending">
                Belum Dinilai ({{ $pendingCount }})
            </button>
            <button type="button" onclick="filterDudiByStatus('evaluated')" class="dudi-status-filter-btn px-3 py-1 rounded-lg text-xs font-semibold transition-all bg-slate-100 text-slate-700 hover:bg-slate-200" data-filter="evaluated">
                Sudah Dinilai ({{ $evaluatedCount }})
            </button>
        </div>
    </div>

    <!-- Empty Search State -->
    <div id="empty-dudi-filter-state" class="hidden py-12 text-center bg-white rounded-2xl border border-slate-200/80 p-6 space-y-3">
        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <h4 class="text-sm font-bold text-slate-800">Tidak Ada Siswa Sesuai Filter</h4>
        <p class="text-xs text-slate-500 max-w-sm mx-auto">Coba ubah kata kunci pencarian atau ganti status filter di atas.</p>
        <button type="button" onclick="resetDudiAssessmentFilters()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors">
            Reset Filter
        </button>
    </div>

    @if($studentPkls->isEmpty())
        <x-empty-state 
            title="Belum Ada Siswa Magang" 
            description="Belum ada siswa magang yang terdaftar di perusahaan Anda untuk dinilai."
        />
    @else

        <!-- ========================================================================= -->
        <!-- VIEW 1: MODERN CARDS VIEW FOR DU/DI                                       -->
        <!-- ========================================================================= -->
        <div id="dudi-cards-container" class="space-y-5">
            @foreach($studentPkls as $sp)
                @php
                    $a = $sp->assessment;
                    $hasEvaluated = $a && ($a->score_dudi_discipline > 0 || $a->score_dudi_technical > 0);
                    $avgDudi = $hasEvaluated ? round(($a->score_dudi_discipline + $a->score_dudi_technical + $a->score_dudi_initiative) / 3, 1) : 0;
                    $contribDudi = $hasEvaluated ? round($avgDudi * ($wDudi / 100), 2) : 0;
                    $isComplete = $a && $a->final_score !== null && $a->final_score > 0;
                    $statusTag = $hasEvaluated ? 'evaluated' : 'pending';
                @endphp

                <div class="dudi-assessment-card bg-white rounded-2xl border border-slate-200/90 shadow-xs hover:border-slate-300 hover:shadow-sm transition-all duration-200 overflow-hidden"
                     id="dudi-student-card-{{ $sp->id }}"
                     data-name="{{ strtolower($sp->student->user->name) }}"
                     data-nisn="{{ $sp->student->nisn }}"
                     data-class="{{ strtolower($sp->student->class->name ?? '') }}"
                     data-status="{{ $statusTag }}">

                    <!-- Card Header: Student Profile & Scores -->
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
                                    @if($hasEvaluated)
                                        <x-badge variant="success" size="xs">Nilai Industri Tersimpan</x-badge>
                                    @else
                                        <x-badge variant="warning" size="xs">Perlu Penilaian</x-badge>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2.5 text-xs text-slate-500 mt-1.5 flex-wrap">
                                    <span class="font-mono text-[11px] text-slate-500">NISN: {{ $sp->student->nisn }}</span>
                                    <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                    <span class="text-slate-600 font-medium">Jurusan: {{ $sp->student->class->major->name ?? '-' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Rata-rata Skor Industri & Nilai Akhir -->
                        <div class="flex items-center md:items-end justify-between md:justify-end gap-6 shrink-0 pt-3 md:pt-0 border-t md:border-t-0 border-slate-100">
                            <div class="text-left md:text-right">
                                <span class="text-[10px] text-slate-400 font-extrabold uppercase tracking-wider block">Rata-Rata Industri ({{ $wDudi }}%)</span>
                                <div class="flex items-baseline md:justify-end gap-1.5 mt-0.5">
                                    <span id="dudi-avg-display-{{ $sp->id }}" class="text-2xl font-black font-mono tracking-tight {{ $hasEvaluated ? 'text-indigo-700' : 'text-slate-300' }}">
                                        {{ $hasEvaluated ? number_format($avgDudi, 1) : '--' }}
                                    </span>
                                    <span class="text-xs font-bold text-slate-400">/ 100</span>
                                </div>
                                <span id="dudi-contrib-display-{{ $sp->id }}" class="text-[10px] text-slate-400 font-medium block mt-0.5">
                                    {{ $hasEvaluated ? 'Kontribusi: ' . $contribDudi . ' poin' : 'Kontribusi dihitung otomatis' }}
                                </span>
                            </div>

                            <div class="text-left md:text-right pl-4 border-l border-slate-200/80">
                                <span class="text-[10px] text-slate-400 font-extrabold uppercase tracking-wider block">Nilai Akhir Terpadu</span>
                                <span class="text-2xl font-black font-mono tracking-tight {{ $isComplete ? 'text-emerald-600' : 'text-slate-300' }}">
                                    {{ $isComplete ? number_format($a->final_score, 1) : '--' }}
                                </span>
                                <span class="text-[10px] text-slate-400 font-medium block mt-0.5">
                                    {{ $isComplete ? 'Terpadu 100%' : 'Menunggu Guru' }}
                                </span>
                            </div>
                        </div>

                    </div>

                    <!-- Evaluation Form -->
                    <form action="{{ route('dudi.assessments.store') }}" method="POST" class="p-5 sm:p-6 space-y-5 bg-slate-50/30">
                        @csrf
                        <input type="hidden" name="student_pkl_id" value="{{ $sp->id }}">

                        <!-- 3 Criteria Scoring Cards -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            
                            <!-- Criterion 1: Discipline -->
                            <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-2xs space-y-2 focus-within:border-indigo-400 focus-within:ring-2 focus-within:ring-indigo-500/10 transition-all">
                                <div class="flex items-center justify-between">
                                    <label for="dudi-disc-{{ $sp->id }}" class="text-xs font-bold text-slate-800">
                                        1. Disiplin & Etos Kerja <span class="text-rose-500">*</span>
                                    </label>
                                    <span class="text-[10px] font-bold text-indigo-600 uppercase">0–100</span>
                                </div>
                                <div class="relative">
                                    <input type="number" 
                                           id="dudi-disc-{{ $sp->id }}"
                                           step="0.1" 
                                           min="0" 
                                           max="100" 
                                           name="score_dudi_discipline" 
                                           value="{{ $a ? $a->score_dudi_discipline : '' }}" 
                                           required 
                                           placeholder="Contoh: 90" 
                                           data-student-id="{{ $sp->id }}"
                                           data-weight-dudi="{{ $wDudi }}"
                                           oninput="recalcDudiStudentAverage('{{ $sp->id }}', {{ $wDudi }})"
                                           class="w-full text-base font-extrabold font-mono px-3.5 py-2.5 rounded-xl bg-slate-50/70 border border-slate-200 focus:bg-white focus:outline-none focus:border-indigo-600 text-slate-900 transition-all">
                                </div>
                                <span class="text-[11px] text-slate-400 font-medium block">
                                    Presensi, kepatuhan jam kerja & SOP K3
                                </span>
                            </div>

                            <!-- Criterion 2: Technical -->
                            <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-2xs space-y-2 focus-within:border-indigo-400 focus-within:ring-2 focus-within:ring-indigo-500/10 transition-all">
                                <div class="flex items-center justify-between">
                                    <label for="dudi-tech-{{ $sp->id }}" class="text-xs font-bold text-slate-800">
                                        2. Keterampilan Teknis <span class="text-rose-500">*</span>
                                    </label>
                                    <span class="text-[10px] font-bold text-indigo-600 uppercase">0–100</span>
                                </div>
                                <div class="relative">
                                    <input type="number" 
                                           id="dudi-tech-{{ $sp->id }}"
                                           step="0.1" 
                                           min="0" 
                                           max="100" 
                                           name="score_dudi_technical" 
                                           value="{{ $a ? $a->score_dudi_technical : '' }}" 
                                           required 
                                           placeholder="Contoh: 88" 
                                           data-student-id="{{ $sp->id }}"
                                           data-weight-dudi="{{ $wDudi }}"
                                           oninput="recalcDudiStudentAverage('{{ $sp->id }}', {{ $wDudi }})"
                                           class="w-full text-base font-extrabold font-mono px-3.5 py-2.5 rounded-xl bg-slate-50/70 border border-slate-200 focus:bg-white focus:outline-none focus:border-indigo-600 text-slate-900 transition-all">
                                </div>
                                <span class="text-[11px] text-slate-400 font-medium block">
                                    Penguasaan alat kerja, akurasi & mutu
                                </span>
                            </div>

                            <!-- Criterion 3: Initiative -->
                            <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-2xs space-y-2 focus-within:border-indigo-400 focus-within:ring-2 focus-within:ring-indigo-500/10 transition-all">
                                <div class="flex items-center justify-between">
                                    <label for="dudi-init-{{ $sp->id }}" class="text-xs font-bold text-slate-800">
                                        3. Inisiatif & Kerjasama <span class="text-rose-500">*</span>
                                    </label>
                                    <span class="text-[10px] font-bold text-indigo-600 uppercase">0–100</span>
                                </div>
                                <div class="relative">
                                    <input type="number" 
                                           id="dudi-init-{{ $sp->id }}"
                                           step="0.1" 
                                           min="0" 
                                           max="100" 
                                           name="score_dudi_initiative" 
                                           value="{{ $a ? $a->score_dudi_initiative : '' }}" 
                                           required 
                                           placeholder="Contoh: 85" 
                                           data-student-id="{{ $sp->id }}"
                                           data-weight-dudi="{{ $wDudi }}"
                                           oninput="recalcDudiStudentAverage('{{ $sp->id }}', {{ $wDudi }})"
                                           class="w-full text-base font-extrabold font-mono px-3.5 py-2.5 rounded-xl bg-slate-50/70 border border-slate-200 focus:bg-white focus:outline-none focus:border-indigo-600 text-slate-900 transition-all">
                                </div>
                                <span class="text-[11px] text-slate-400 font-medium block">
                                    Proaktivitas, komunikasi tim & etika
                                </span>
                            </div>

                        </div>

                        <!-- Mentor Notes Field -->
                        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-2xs space-y-2">
                            <label class="block text-xs font-bold text-slate-800">Catatan Khusus & Rekomendasi Mentor Lapangan</label>
                            <textarea name="notes_dudi" rows="2" placeholder="Catatan keunggulan, pencapaian proyek industri, atau evaluasi pengembangan kompetensi siswa..." 
                                      class="w-full text-xs font-medium p-3 rounded-xl bg-slate-50/70 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-slate-900 leading-relaxed transition-all">{{ $a ? $a->notes_dudi : '' }}</textarea>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-between pt-1">
                            <div class="text-[11px] text-slate-500">
                                @if($hasEvaluated)
                                    <span class="text-emerald-700 font-bold inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Nilai tersimpan & siap diteruskan ke sekolah
                                    </span>
                                @else
                                    <span class="text-amber-600 font-medium">Perlu disimpan oleh mentor lapangan</span>
                                @endif
                            </div>

                            <x-button type="submit" variant="primary" size="md">
                                <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ $hasEvaluated ? 'Perbarui Nilai Industri' : 'Simpan Nilai Industri' }}</span>
                            </x-button>
                        </div>
                    </form>

                </div>
            @endforeach
        </div>

        <!-- ========================================================================= -->
        <!-- VIEW 2: COMPACT TABLE VIEW FOR DU/DI                                      -->
        <!-- ========================================================================= -->
        <div id="dudi-table-container" class="hidden bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse min-w-[920px]">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-4 sm:px-5 min-w-[200px]">Siswa Magang</th>
                            <th class="py-3.5 px-3 text-center min-w-[80px]">Disiplin</th>
                            <th class="py-3.5 px-3 text-center min-w-[80px]">Teknis</th>
                            <th class="py-3.5 px-3 text-center min-w-[80px]">Inisiatif</th>
                            <th class="py-3.5 px-3 text-center min-w-[120px]">Rata-rata ({{ $wDudi }}%)</th>
                            <th class="py-3.5 px-3 text-center min-w-[100px]">Nilai Terpadu</th>
                            <th class="py-3.5 px-4 text-center min-w-[110px]">Status</th>
                            <th class="py-3.5 px-4 text-right table-sticky-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                        @foreach($studentPkls as $sp)
                            @php
                                $a = $sp->assessment;
                                $hasEvaluated = $a && ($a->score_dudi_discipline > 0 || $a->score_dudi_technical > 0);
                                $avgDudi = $hasEvaluated ? round(($a->score_dudi_discipline + $a->score_dudi_technical + $a->score_dudi_initiative) / 3, 1) : 0;
                                $isComplete = $a && $a->final_score !== null && $a->final_score > 0;
                                $statusTag = $hasEvaluated ? 'evaluated' : 'pending';
                            @endphp
                            <tr class="dudi-table-row hover:bg-slate-50/70 transition-colors group"
                                data-name="{{ strtolower($sp->student->user->name) }}"
                                data-nisn="{{ $sp->student->nisn }}"
                                data-class="{{ strtolower($sp->student->class->name ?? '') }}"
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

                                <td class="py-3.5 px-3 text-center font-mono font-bold">
                                    {{ $hasEvaluated ? $a->score_dudi_discipline : '--' }}
                                </td>
                                <td class="py-3.5 px-3 text-center font-mono font-bold">
                                    {{ $hasEvaluated ? $a->score_dudi_technical : '--' }}
                                </td>
                                <td class="py-3.5 px-3 text-center font-mono font-bold">
                                    {{ $hasEvaluated ? $a->score_dudi_initiative : '--' }}
                                </td>

                                <td class="py-3.5 px-3 text-center">
                                    @if($hasEvaluated)
                                        <span class="font-extrabold font-mono text-indigo-700 text-sm block">{{ $avgDudi }}</span>
                                    @else
                                        <span class="text-[11px] text-amber-600 font-semibold italic">Belum dinilai</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-3 text-center">
                                    @if($isComplete)
                                        <span class="text-sm font-black font-mono text-emerald-700">{{ number_format($a->final_score, 1) }}</span>
                                    @else
                                        <span class="text-slate-300 font-mono font-bold text-sm">--</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    @if($hasEvaluated)
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                            Tersimpan
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            Perlu Dinilai
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-right whitespace-nowrap table-sticky-right">
                                    <button type="button" 
                                            onclick="openDudiCardForStudent('{{ $sp->id }}')" 
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

<!-- Client-side Interactive Filter & Realtime Average Calculation Engine -->
<script>
    let currentDudiFilterStatus = 'all';

    function switchDudiAssessmentView(mode) {
        const cardsContainer = document.getElementById('dudi-cards-container');
        const tableContainer = document.getElementById('dudi-table-container');
        const btnCards = document.getElementById('btn-dudi-view-cards');
        const btnTable = document.getElementById('btn-dudi-view-table');

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

    function openDudiCardForStudent(studentId) {
        switchDudiAssessmentView('cards');
        const card = document.getElementById('dudi-student-card-' + studentId);
        if (card) {
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            card.classList.add('ring-2', 'ring-indigo-500', 'ring-offset-2');
            setTimeout(() => {
                card.classList.remove('ring-2', 'ring-indigo-500', 'ring-offset-2');
                const input = document.getElementById('dudi-disc-' + studentId);
                input?.focus();
            }, 600);
        }
    }

    function filterDudiByStatus(status) {
        currentDudiFilterStatus = status;

        document.querySelectorAll('.dudi-status-filter-btn').forEach(btn => {
            if (btn.getAttribute('data-filter') === status) {
                btn.className = 'dudi-status-filter-btn px-3 py-1 rounded-lg text-xs font-bold transition-all bg-indigo-600 text-white';
            } else {
                btn.className = 'dudi-status-filter-btn px-3 py-1 rounded-lg text-xs font-semibold transition-all bg-slate-100 text-slate-700 hover:bg-slate-200';
            }
        });

        filterDudiAssessmentList();
    }

    function clearDudiSearch() {
        const searchInput = document.getElementById('dudi-assessment-search');
        if (searchInput) {
            searchInput.value = '';
            filterDudiAssessmentList();
        }
    }

    function resetDudiAssessmentFilters() {
        const searchInput = document.getElementById('dudi-assessment-search');
        if (searchInput) searchInput.value = '';
        filterDudiByStatus('all');
    }

    function filterDudiAssessmentList() {
        const query = (document.getElementById('dudi-assessment-search')?.value || '').toLowerCase().trim();
        const clearBtn = document.getElementById('clear-dudi-search-btn');
        if (clearBtn) {
            clearBtn.classList.toggle('hidden', query === '');
        }

        const cards = document.querySelectorAll('.dudi-assessment-card');
        const tableRows = document.querySelectorAll('.dudi-table-row');
        let visibleCount = 0;

        cards.forEach(card => {
            const name = card.getAttribute('data-name') || '';
            const nisn = card.getAttribute('data-nisn') || '';
            const cls = card.getAttribute('data-class') || '';
            const status = card.getAttribute('data-status') || '';

            const matchesQuery = query === '' || name.includes(query) || nisn.includes(query) || cls.includes(query);
            const matchesStatus = currentDudiFilterStatus === 'all' || status === currentDudiFilterStatus;

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
            const cls = row.getAttribute('data-class') || '';
            const status = row.getAttribute('data-status') || '';

            const matchesQuery = query === '' || name.includes(query) || nisn.includes(query) || cls.includes(query);
            const matchesStatus = currentDudiFilterStatus === 'all' || status === currentDudiFilterStatus;

            if (matchesQuery && matchesStatus) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });

        const emptyState = document.getElementById('empty-dudi-filter-state');
        if (emptyState) {
            emptyState.classList.toggle('hidden', visibleCount > 0);
        }
    }

    function recalcDudiStudentAverage(studentId, weightDudi) {
        const disc = parseFloat(document.getElementById('dudi-disc-' + studentId)?.value) || 0;
        const tech = parseFloat(document.getElementById('dudi-tech-' + studentId)?.value) || 0;
        const init = parseFloat(document.getElementById('dudi-init-' + studentId)?.value) || 0;

        const avg = ((disc + tech + init) / 3).toFixed(1);
        const contrib = (avg * (weightDudi / 100)).toFixed(2);

        const avgDisplay = document.getElementById('dudi-avg-display-' + studentId);
        const contribDisplay = document.getElementById('dudi-contrib-display-' + studentId);

        if (avgDisplay) {
            avgDisplay.textContent = avg;
            avgDisplay.classList.remove('text-slate-300');
            avgDisplay.classList.add('text-indigo-700');
        }
        if (contribDisplay) {
            contribDisplay.textContent = `Kontribusi: ${contrib} poin (${weightDudi}%)`;
        }
    }
</script>
@endsection
