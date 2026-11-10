@extends('layouts.app')

@section('page_title', 'Penilaian Akademik PKL (Guru Pembimbing)')
@section('page_subtitle', 'Pengisian nilai laporan akhir, pembimbingan, dan evaluasi capaian kompetensi')

@section('content')
<div class="space-y-6">

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <h3 class="text-sm font-bold text-slate-800 mb-1">Evaluasi Siswa Bimbingan</h3>
        <p class="text-xs text-slate-500 mb-4">Nilai akhir dihitung otomatis berdasarkan formula bobot terpadu (60% DU/DI : 40% Sekolah)</p>

        <div class="space-y-4">
            @foreach($studentPkls as $sp)
                @php $a = $sp->assessment; @endphp
                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200/60 pb-3">
                        <div>
                            <span class="font-bold text-sm text-slate-900 block">{{ $sp->student->user->name }}</span>
                            <span class="text-xs text-slate-500">{{ $sp->student->class->name }} &bull; NISN: {{ $sp->student->nisn }} &bull; DU/DI: {{ $sp->company->name }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-slate-400 font-bold uppercase block">Nilai Akhir Terpadu</span>
                            <span class="text-xl font-black text-indigo-700">
                                {{ $a ? number_format($a->final_score, 1) : '--' }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Left: DU/DI Scores -->
                        <div class="p-3.5 bg-white rounded-xl border border-slate-200 text-xs space-y-2">
                            <span class="font-bold text-slate-800 block text-[11px] uppercase tracking-wider text-indigo-700">Skor dari Pembimbing DU/DI</span>
                            @if($a && ($a->score_dudi_discipline > 0 || $a->score_dudi_technical > 0))
                                <div class="grid grid-cols-3 gap-2 text-center pt-1">
                                    <div class="bg-slate-50 p-2 rounded-lg">
                                        <span class="font-bold block text-slate-800">{{ $a->score_dudi_discipline }}</span>
                                        <span class="text-[9px] text-slate-400 block uppercase">Disiplin</span>
                                    </div>
                                    <div class="bg-slate-50 p-2 rounded-lg">
                                        <span class="font-bold block text-slate-800">{{ $a->score_dudi_technical }}</span>
                                        <span class="text-[9px] text-slate-400 block uppercase">Teknis</span>
                                    </div>
                                    <div class="bg-slate-50 p-2 rounded-lg">
                                        <span class="font-bold block text-slate-800">{{ $a->score_dudi_initiative }}</span>
                                        <span class="text-[9px] text-slate-400 block uppercase">Inisiatif</span>
                                    </div>
                                </div>
                                @if($a->notes_dudi)
                                    <p class="text-[11px] text-slate-600 italic mt-2">"{{ $a->notes_dudi }}"</p>
                                @endif
                            @else
                                <p class="text-[11px] text-slate-400 italic py-2">Belum dinilai oleh pihak DU/DI.</p>
                            @endif
                        </div>

                        <!-- Right: Teacher Grading Form -->
                        <div class="p-3.5 bg-white rounded-xl border border-slate-200 space-y-3">
                            <span class="font-bold text-slate-800 block text-[11px] uppercase tracking-wider text-emerald-700">Input Nilai Sekolah (Guru)</span>
                            <form action="{{ route('guru.assessments.store') }}" method="POST" class="space-y-3">
                                @csrf
                                <input type="hidden" name="student_pkl_id" value="{{ $sp->id }}">

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Skor Laporan & Bimbingan (0–100)</label>
                                    <input type="number" step="0.1" min="0" max="100" name="score_teacher_report" value="{{ $a ? $a->score_teacher_report : '' }}" required placeholder="Contoh: 88.5" class="w-full text-xs p-2 rounded-xl bg-slate-50 border border-slate-200 font-bold">
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Catatan Evaluasi Guru</label>
                                    <textarea name="notes_teacher" rows="2" placeholder="Catatan kemajuan kompetensi..." class="w-full text-xs p-2 rounded-xl bg-slate-50 border border-slate-200">{{ $a ? $a->notes_teacher : '' }}</textarea>
                                </div>

                                <button type="submit" class="w-full py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-200">
                                    Simpan & Hitung Nilai Akhir
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
