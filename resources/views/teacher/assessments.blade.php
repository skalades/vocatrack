@extends('layouts.app')

@section('page_title', 'Penilaian Akademik PKL (Guru Pembimbing)')
@section('page_subtitle', 'Pengisian nilai laporan akhir, bimbingan, dan evaluasi capaian kompetensi')

@section('content')
<div class="space-y-6">

    <x-page-header 
        title="Penilaian Akademik PKL" 
        subtitle="Evaluasi laporan PKL dan penguasaan kompetensi vokasi. Nilai akhir dihitung otomatis berdasarkan formula bobot terpadu (60% DU/DI : 40% Sekolah)."
    />

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
                    <div class="p-5 rounded-2xl bg-slate-50/80 border border-slate-200/80 space-y-4 hover:border-slate-300 transition-colors">
                        
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200/70 pb-3">
                            <div>
                                <span class="font-extrabold text-sm text-slate-900 block">{{ $sp->student->user->name }}</span>
                                <span class="text-xs text-slate-500 font-medium">{{ $sp->student->class->name }} &bull; NISN: {{ $sp->student->nisn }} &bull; DU/DI: {{ $sp->company->name }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="text-right">
                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Nilai Akhir Terpadu</span>
                                    <span class="text-2xl font-black text-indigo-700 font-mono">
                                        {{ $a && $a->final_score ? number_format($a->final_score, 1) : '--' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            
                            <!-- Left: DU/DI Industry Scores -->
                            <div class="p-4 bg-white rounded-2xl border border-slate-200 text-xs space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-xs uppercase tracking-wider text-indigo-900 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        Skor Pembimbing DU/DI (60%)
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
                                        <p class="text-xs text-slate-600 italic bg-slate-50 p-2.5 rounded-xl border border-slate-100 leading-relaxed font-medium">
                                            "{{ $a->notes_dudi }}"
                                        </p>
                                    @endif
                                @else
                                    <div class="py-6 text-center text-slate-400 text-xs italic font-medium bg-slate-50/50 rounded-xl">
                                        Belum dinilai oleh pihak DU/DI.
                                    </div>
                                @endif
                            </div>

                            <!-- Right: Teacher Grading Form -->
                            <div class="p-4 bg-white rounded-2xl border border-slate-200 space-y-3">
                                <span class="font-bold text-xs uppercase tracking-wider text-emerald-900 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Input Nilai Sekolah (Guru 40%)
                                </span>

                                <form action="{{ route('guru.assessments.store') }}" method="POST" class="space-y-3">
                                    @csrf
                                    <input type="hidden" name="student_pkl_id" value="{{ $sp->id }}">

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Skor Laporan & Bimbingan (0–100)</label>
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
