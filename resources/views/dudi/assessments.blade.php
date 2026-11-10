@extends('layouts.app')

@section('page_title', 'Penilaian Kompetensi Industri (DU/DI)')
@section('page_subtitle', 'Evaluasi etos kerja, disiplin, dan penguasaan teknis siswa selama praktik kerja lapangan')

@section('content')
<div class="space-y-6">

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <h3 class="text-sm font-bold text-slate-800 mb-1">Daftar Evaluasi Siswa Magang</h3>
        <p class="text-xs text-slate-500 mb-4">Bobot nilai industri berkontribusi sebesar 60% terhadap nilai akhir PKL siswa</p>

        <div class="space-y-4">
            @foreach($studentPkls as $sp)
                @php $a = $sp->assessment; @endphp
                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200/60 pb-3">
                        <div>
                            <span class="font-bold text-sm text-slate-900 block">{{ $sp->student->user->name }}</span>
                            <span class="text-xs text-slate-500">{{ $sp->student->class->name }} &bull; NISN: {{ $sp->student->nisn }} &bull; Jurusan: {{ $sp->student->class->major->name }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-slate-400 font-bold uppercase block">Nilai Akhir Terpadu</span>
                            <span class="text-xl font-black text-indigo-700">
                                {{ $a ? number_format($a->final_score, 1) : '--' }}
                            </span>
                        </div>
                    </div>

                    <form action="{{ route('dudi.assessments.store') }}" method="POST" class="space-y-3.5">
                        @csrf
                        <input type="hidden" name="student_pkl_id" value="{{ $sp->id }}">

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">1. Kedisiplinan & Etos Kerja (0–100)</label>
                                <input type="number" step="0.1" min="0" max="100" name="score_dudi_discipline" value="{{ $a ? $a->score_dudi_discipline : '' }}" required placeholder="Contoh: 90" class="w-full text-xs p-2.5 rounded-xl bg-white border border-slate-200 font-bold">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">2. Keterampilan Teknis (0–100)</label>
                                <input type="number" step="0.1" min="0" max="100" name="score_dudi_technical" value="{{ $a ? $a->score_dudi_technical : '' }}" required placeholder="Contoh: 88" class="w-full text-xs p-2.5 rounded-xl bg-white border border-slate-200 font-bold">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">3. Inisiatif & Sikap Kerja (0–100)</label>
                                <input type="number" step="0.1" min="0" max="100" name="score_dudi_initiative" value="{{ $a ? $a->score_dudi_initiative : '' }}" required placeholder="Contoh: 85" class="w-full text-xs p-2.5 rounded-xl bg-white border border-slate-200 font-bold">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Khusus Mentor DU/DI</label>
                            <textarea name="notes_dudi" rows="2" placeholder="Catatan kelebihan, perkembangan keterampilan, atau saran perbaikan..." class="w-full text-xs p-2.5 rounded-xl bg-white border border-slate-200">{{ $a ? $a->notes_dudi : '' }}</textarea>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-200 transition-all active:scale-[0.98]">
                                Simpan Nilai Industri
                            </button>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
