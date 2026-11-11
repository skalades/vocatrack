@extends('layouts.app')

@section('page_title', 'Penilaian Kompetensi Industri (DU/DI)')
@section('page_subtitle', 'Evaluasi etos kerja, kedisiplinan, dan penguasaan teknis siswa selama praktik kerja lapangan')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Enterprise Hero Banner -->
    <x-page-header 
        title="Penilaian Praktik Industri (DU/DI)" 
        subtitle="Evaluasi etos kerja, kedisiplinan kerja, dan kompetensi teknis siswa magang. Nilai industri berkontribusi sebesar 60% terhadap sertifikat akhir kelulusan PKL."
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
        $evaluatedCount = $studentPkls->filter(fn($sp) => $sp->assessment && ($sp->assessment->score_dudi_discipline > 0 || $sp->assessment->score_dudi_technical > 0))->count();
        $pendingCount = $studentPkls->count() - $evaluatedCount;
    @endphp

    <!-- Top KPI Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card 
            title="Total Siswa Magang" 
            :value="$studentPkls->count() . ' Siswa'" 
            subtitle="Peserta magang di industri Anda"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Sudah Dinilai Industri" 
            :value="$evaluatedCount . ' Siswa'" 
            subtitle="Skor kompetensi tersimpan"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Belum Dinilai" 
            :value="$pendingCount . ' Siswa'" 
            subtitle="Perlu input evaluasi mentor"
            :color="$pendingCount > 0 ? 'amber' : 'indigo'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Bobot Kontribusi Nilai" 
            value="60% Skor" 
            subtitle="Penentu predikat sertifikat"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Assessments List Card -->
    <x-card 
        title="Daftar Siswa Magang & Lembar Evaluasi" 
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
                    <div class="p-5 sm:p-6 rounded-3xl bg-slate-50/80 border border-slate-200/80 space-y-4 hover:border-slate-300 transition-colors">
                        
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200/70 pb-3">
                            <div class="flex items-center space-x-3.5">
                                <div class="w-10 h-10 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-700 font-black text-xs flex items-center justify-center shrink-0 shadow-xs shadow-indigo-600/10">
                                    {{ substr($sp->student->user->name, 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <span class="font-extrabold text-sm text-slate-900 block truncate">{{ $sp->student->user->name }}</span>
                                    <span class="text-xs text-slate-500 font-medium block truncate mt-0.5">
                                        {{ $sp->student->class->name }} &bull; NISN: {{ $sp->student->nisn }} &bull; Jurusan: {{ $sp->student->class->major->name ?? '-' }}
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

                        <form action="{{ route('dudi.assessments.store') }}" method="POST" class="space-y-4">
                            @csrf
                            <input type="hidden" name="student_pkl_id" value="{{ $sp->id }}">

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                                <div class="p-4 bg-white rounded-2xl border border-slate-200">
                                    <label class="block text-xs font-bold text-slate-800 mb-1">1. Disiplin & Etos Kerja (0–100)</label>
                                    <input type="number" step="0.1" min="0" max="100" name="score_dudi_discipline" value="{{ $a ? $a->score_dudi_discipline : '' }}" required placeholder="Contoh: 90" 
                                           class="w-full text-sm p-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-mono font-bold text-slate-900">
                                    <span class="text-[10px] text-slate-400 font-medium block mt-1">Kehadiran & kepatuhan SOP kantor</span>
                                </div>
                                <div class="p-4 bg-white rounded-2xl border border-slate-200">
                                    <label class="block text-xs font-bold text-slate-800 mb-1">2. Keterampilan Teknis (0–100)</label>
                                    <input type="number" step="0.1" min="0" max="100" name="score_dudi_technical" value="{{ $a ? $a->score_dudi_technical : '' }}" required placeholder="Contoh: 88" 
                                           class="w-full text-sm p-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-mono font-bold text-slate-900">
                                    <span class="text-[10px] text-slate-400 font-medium block mt-1">Kualitas & ketepatan hasil kerja</span>
                                </div>
                                <div class="p-4 bg-white rounded-2xl border border-slate-200">
                                    <label class="block text-xs font-bold text-slate-800 mb-1">3. Inisiatif & Sikap (0–100)</label>
                                    <input type="number" step="0.1" min="0" max="100" name="score_dudi_initiative" value="{{ $a ? $a->score_dudi_initiative : '' }}" required placeholder="Contoh: 85" 
                                           class="w-full text-sm p-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-mono font-bold text-slate-900">
                                    <span class="text-[10px] text-slate-400 font-medium block mt-1">Kerjasama tim & proaktivitas kerja</span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Catatan Khusus Mentor Lapangan</label>
                                <textarea name="notes_dudi" rows="2" placeholder="Catatan kelebihan, pencapaian proyek industri, atau saran pengembangan keterampilan siswa..." 
                                          class="w-full text-xs p-3 rounded-xl bg-white border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 font-medium text-slate-900 leading-relaxed">{{ $a ? $a->notes_dudi : '' }}</textarea>
                            </div>

                            <div class="flex items-center justify-end pt-1">
                                <x-button type="submit" variant="primary" size="md">
                                    <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    <span>Simpan Nilai Industri</span>
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
