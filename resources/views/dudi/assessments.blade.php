@extends('layouts.app')

@section('page_title', 'Penilaian Kompetensi Industri (DU/DI)')
@section('page_subtitle', 'Evaluasi etos kerja, kedisiplinan, dan penguasaan teknis siswa selama praktik kerja lapangan')

@section('content')
<div class="space-y-6">

    <x-page-header 
        title="Penilaian Praktik Industri (DU/DI)" 
        subtitle="Evaluasi etos kerja dan kompetensi teknis siswa magang. Nilai industri berkontribusi sebesar 60% terhadap sertifikat akhir PKL."
    />

    <x-card 
        title="Daftar Siswa Magang" 
        :subtitle="$studentPkls->count() . ' Siswa Magang Terdaftar'"
    >
        @if($studentPkls->isEmpty())
            <x-empty-state 
                title="Belum Ada Siswa Magang" 
                description="Belum ada siswa magang yang terdaftar di perusahaan Anda untuk dinilai."
            />
        @else
            <div class="space-y-4">
                @foreach($studentPkls as $sp)
                    @php $a = $sp->assessment; @endphp
                    <div class="p-5 sm:p-6 rounded-2xl bg-slate-50/80 border border-slate-200/80 space-y-4 hover:border-slate-300 transition-colors">
                        
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200/70 pb-3">
                            <div>
                                <span class="font-extrabold text-sm text-slate-900 block">{{ $sp->student->user->name }}</span>
                                <span class="text-xs text-slate-500 font-medium">{{ $sp->student->class->name }} &bull; NISN: {{ $sp->student->nisn }} &bull; Jurusan: {{ $sp->student->class->major->name }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Nilai Akhir Terpadu</span>
                                <span class="text-2xl font-black text-indigo-700 font-mono">
                                    {{ $a && $a->final_score ? number_format($a->final_score, 1) : '--' }}
                                </span>
                            </div>
                        </div>

                        <form action="{{ route('dudi.assessments.store') }}" method="POST" class="space-y-4">
                            @csrf
                            <input type="hidden" name="student_pkl_id" value="{{ $sp->id }}">

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                                <div class="p-3.5 bg-white rounded-2xl border border-slate-200">
                                    <label class="block text-xs font-bold text-slate-800 mb-1">1. Disiplin & Etos Kerja (0–100)</label>
                                    <input type="number" step="0.1" min="0" max="100" name="score_dudi_discipline" value="{{ $a ? $a->score_dudi_discipline : '' }}" required placeholder="Contoh: 90" 
                                           class="w-full text-sm p-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-mono font-bold text-slate-900">
                                    <span class="text-[10px] text-slate-400 font-medium block mt-1">Kehadiran & kepatuhan SOP</span>
                                </div>
                                <div class="p-3.5 bg-white rounded-2xl border border-slate-200">
                                    <label class="block text-xs font-bold text-slate-800 mb-1">2. Keterampilan Teknis (0–100)</label>
                                    <input type="number" step="0.1" min="0" max="100" name="score_dudi_technical" value="{{ $a ? $a->score_dudi_technical : '' }}" required placeholder="Contoh: 88" 
                                           class="w-full text-sm p-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-mono font-bold text-slate-900">
                                    <span class="text-[10px] text-slate-400 font-medium block mt-1">Ketepatan & kualitas hasil kerja</span>
                                </div>
                                <div class="p-3.5 bg-white rounded-2xl border border-slate-200">
                                    <label class="block text-xs font-bold text-slate-800 mb-1">3. Inisiatif & Sikap (0–100)</label>
                                    <input type="number" step="0.1" min="0" max="100" name="score_dudi_initiative" value="{{ $a ? $a->score_dudi_initiative : '' }}" required placeholder="Contoh: 85" 
                                           class="w-full text-sm p-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-mono font-bold text-slate-900">
                                    <span class="text-[10px] text-slate-400 font-medium block mt-1">Kerjasama tim & proaktivitas</span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Catatan Khusus Mentor Lapangan</label>
                                <textarea name="notes_dudi" rows="2" placeholder="Catatan kelebihan, pencapaian proyek industri, atau saran pengembangan keterampilan siswa..." 
                                          class="w-full text-xs p-3 rounded-xl bg-white border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 font-medium text-slate-900 leading-relaxed">{{ $a ? $a->notes_dudi : '' }}</textarea>
                            </div>

                            <div class="flex items-center justify-end pt-1">
                                <x-button type="submit" variant="primary" size="md">
                                    Simpan Nilai Industri
                                </x-button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>

</div>
@endsection
