@extends('layouts.app')

@section('page_title', 'Penilaian Akademik PKL (Guru Pembimbing)')
@section('page_subtitle', 'Pengisian nilai laporan akhir, bimbingan, dan evaluasi capaian kompetensi')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

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
            <span>Ekspor PDF Laporan</span>
        </x-button>
    </x-page-header>

    @php
        $totalEvaluated = $studentPkls->filter(fn($sp) => $sp->assessment && $sp->assessment->score_teacher_report !== null)->count();
        $pendingEval = $studentPkls->filter(fn($sp) => !$sp->assessment || $sp->assessment->score_teacher_report === null)->count();
        $completeBoth = $studentPkls->filter(fn($sp) => $sp->assessment && $sp->assessment->final_score !== null)->count();
    @endphp

    <!-- Top KPI Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card 
            title="Total Siswa Binaan" 
            :value="$studentPkls->count() . ' Siswa'" 
            subtitle="Alokasi bimbingan aktif"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Sudah Dinilai Guru" 
            :value="$totalEvaluated" 
            subtitle="Nilai laporan & seminar tersimpan"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Belum Dinilai Guru" 
            :value="$pendingEval" 
            subtitle="Menunggu pengisian nilai"
            :color="$pendingEval > 0 ? 'amber' : 'indigo'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Nilai Lengkap Terpadu" 
            :value="$completeBoth" 
            subtitle="Siap diterbitkan ke sertifikat"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Student Assessment List Card -->
    <x-card 
        title="Evaluasi Siswa Bimbingan" 
        :subtitle="$studentPkls->count() . ' Siswa Siap Dievaluasi'"
    >
        @if($studentPkls->isEmpty())
            <x-empty-state 
                title="Belum Ada Siswa Binaan" 
                description="Anda belum memiliki siswa yang diplot untuk dinilai."
            />
        @else
            <div class="space-y-4">
                @foreach($studentPkls as $sp)
                    @php $a = $sp->assessment; @endphp
                    <div class="p-5 sm:p-6 rounded-3xl bg-slate-50/80 border border-slate-200/80 space-y-4 hover:border-slate-300 transition-colors">
                        
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200/70 pb-3">
                            <div class="flex items-center space-x-3.5">
                                <div class="w-10 h-10 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-700 font-black text-xs flex items-center justify-center shrink-0 shadow-xs shadow-indigo-600/10">
                                    {{ substr($sp->student->user->name, 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <span class="font-extrabold text-sm text-slate-900 block truncate">{{ $sp->student->user->name }}</span>
                                    <span class="text-xs text-slate-500 font-medium block truncate">
                                        {{ $sp->student->class->name }} &bull; NISN: {{ $sp->student->nisn }} &bull; DU/DI: {{ $sp->company->name }}
                                    </span>
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Nilai Akhir Terpadu</span>
                                <span class="text-2xl font-black text-indigo-700 font-mono">
                                    {{ $a && $a->final_score ? number_format($a->final_score, 1) : '--' }}
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            
                            <!-- Left: DU/DI Industry Scores -->
                            <div class="p-4.5 bg-white rounded-2xl border border-slate-200 text-xs space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-xs uppercase tracking-wider text-indigo-900 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        Skor Pembimbing DU/DI (Industri)
                                    </span>
                                </div>

                                @if($a && ($a->score_dudi_discipline > 0 || $a->score_dudi_technical > 0))
                                    <div class="grid grid-cols-3 gap-2 text-center pt-1">
                                        <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                                            <span class="text-base font-extrabold block text-slate-900 font-mono">{{ $a->score_dudi_discipline }}</span>
                                            <span class="text-[9px] text-slate-400 font-bold block uppercase mt-0.5">Disiplin</span>
                                        </div>
                                        <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                                            <span class="text-base font-extrabold block text-slate-900 font-mono">{{ $a->score_dudi_technical }}</span>
                                            <span class="text-[9px] text-slate-400 font-bold block uppercase mt-0.5">Teknis</span>
                                        </div>
                                        <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                                            <span class="text-base font-extrabold block text-slate-900 font-mono">{{ $a->score_dudi_initiative }}</span>
                                            <span class="text-[9px] text-slate-400 font-bold block uppercase mt-0.5">Inisiatif</span>
                                        </div>
                                    </div>
                                    @if($a->notes_dudi)
                                        <p class="text-xs text-slate-600 italic bg-slate-50 p-3 rounded-xl border border-slate-100 leading-relaxed font-medium">
                                            "{{ $a->notes_dudi }}"
                                        </p>
                                    @endif
                                @else
                                    <div class="py-8 text-center text-slate-400 text-xs italic font-medium bg-slate-50/50 rounded-xl">
                                        Belum dinilai oleh pihak DU/DI industri.
                                    </div>
                                @endif
                            </div>

                            <!-- Right: Teacher Grading Form -->
                            <div class="p-4.5 bg-white rounded-2xl border border-slate-200 space-y-3">
                                <span class="font-bold text-xs uppercase tracking-wider text-emerald-900 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Input Nilai Sekolah (Guru Pembimbing)
                                </span>

                                <form action="{{ route('guru.assessments.store') }}" method="POST" class="space-y-3">
                                    @csrf
                                    <input type="hidden" name="student_pkl_id" value="{{ $sp->id }}">

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Skor Laporan & Seminar Bimbingan (0–100)</label>
                                        <input type="number" step="0.1" min="0" max="100" name="score_teacher_report" value="{{ $a ? $a->score_teacher_report : '' }}" required placeholder="Contoh: 88.5" 
                                               class="w-full text-xs sm:text-sm p-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-mono font-bold text-slate-900">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Evaluasi Guru</label>
                                        <textarea name="notes_teacher" rows="2" placeholder="Catatan kemajuan kompetensi atau evaluasi laporan..." 
                                                  class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900 leading-relaxed">{{ $a ? $a->notes_teacher : '' }}</textarea>
                                    </div>

                                    <x-button type="submit" variant="primary" size="md" class="w-full">
                                        Simpan & Hitung Nilai Akhir
                                    </x-button>
                                </form>
                            </div>

                        </div>

                    </div>
                @endforeach
            </div>
        @endif
    </x-card>

</div>
@endsection
