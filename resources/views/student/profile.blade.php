@extends('layouts.student-mobile')

@section('title', 'Profil Siswa — VokaTrack')

@section('content')
<div class="space-y-4">

    <!-- Profile Header Card -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm text-center">
        <div class="w-16 h-16 rounded-full bg-indigo-100 text-indigo-700 font-extrabold text-xl flex items-center justify-center mx-auto mb-3 shadow-inner">
            {{ substr(auth()->user()->name, 0, 1) }}
        </div>
        <h2 class="text-base font-extrabold text-slate-900">{{ auth()->user()->name }}</h2>
        <p class="text-xs text-slate-500 mt-0.5">
            NISN: {{ $student->nisn }} &bull; NIS: {{ $student->nis }}
        </p>
        <span class="inline-block mt-2 px-3 py-1 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
            {{ $student->class->name ?? '-' }} ({{ $student->class->major->name ?? '-' }})
        </span>
    </div>

    <!-- PKL Assignment Info Card -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm space-y-3">
        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-2">
            Informasi Penempatan PKL
        </h3>

        <div class="space-y-2.5 text-xs">
            <div>
                <span class="text-[10px] text-slate-400 font-bold block uppercase">Perusahaan Mitra (DU/DI)</span>
                <span class="font-bold text-slate-800">{{ $spkl->company->name }}</span>
                <p class="text-[11px] text-slate-500 mt-0.5">{{ $spkl->company->address }}</p>
            </div>

            <div class="grid grid-cols-2 gap-2 pt-1 border-t border-slate-100">
                <div>
                    <span class="text-[10px] text-slate-400 font-bold block uppercase">Pembimbing Industri</span>
                    <span class="font-bold text-slate-800">{{ $spkl->supervisor->user->name ?? 'Belum Diplot' }}</span>
                    <p class="text-[10px] text-slate-500">{{ $spkl->supervisor->position ?? '-' }}</p>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 font-bold block uppercase">Guru Pembimbing</span>
                    <span class="font-bold text-slate-800">{{ $spkl->teacher->user->name ?? 'Belum Diplot' }}</span>
                    <p class="text-[10px] text-slate-500">NIP: {{ $spkl->teacher->nip ?? '-' }}</p>
                </div>
            </div>

            <div class="pt-1 border-t border-slate-100">
                <span class="text-[10px] text-slate-400 font-bold block uppercase">Periode Pelaksanaan</span>
                <span class="font-semibold text-slate-700">
                    {{ \Carbon\Carbon::parse($spkl->pklPeriod->start_date)->isoFormat('D MMMM Y') }} s.d. {{ \Carbon\Carbon::parse($spkl->pklPeriod->end_date)->isoFormat('D MMMM Y') }}
                </span>
            </div>
        </div>
    </div>

    <!-- Assessment Card (if graded) -->
    @if($assessment)
        <div class="bg-gradient-to-br from-indigo-900 to-indigo-800 rounded-3xl p-5 text-white shadow-lg space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-200">Lembar Penilaian Akhir</h3>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white">Final</span>
            </div>

            <div class="flex items-center justify-between py-2 border-y border-indigo-700/60">
                <div>
                    <span class="text-[10px] text-indigo-200 block uppercase">Nilai Akhir Terpadu</span>
                    <span class="text-xs text-indigo-100 font-medium">Bobot 60% DU/DI : 40% Sekolah</span>
                </div>
                <div class="text-3xl font-black text-amber-300">
                    {{ number_format($assessment->final_score, 1) }}
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 text-xs pt-1">
                <div class="bg-indigo-950/40 p-2.5 rounded-xl border border-indigo-700/40">
                    <span class="text-[10px] text-indigo-300 font-bold block uppercase">DU/DI (Industri)</span>
                    <p class="text-[11px] text-indigo-100 mt-0.5">Disiplin: {{ $assessment->score_dudi_discipline }}</p>
                    <p class="text-[11px] text-indigo-100">Teknis: {{ $assessment->score_dudi_technical }}</p>
                    <p class="text-[11px] text-indigo-100">Inisiatif: {{ $assessment->score_dudi_initiative }}</p>
                </div>
                <div class="bg-indigo-950/40 p-2.5 rounded-xl border border-indigo-700/40">
                    <span class="text-[10px] text-indigo-300 font-bold block uppercase">Sekolah (Guru)</span>
                    <p class="text-[11px] text-indigo-100 mt-0.5">Laporan & Jurnal: {{ $assessment->score_teacher_report }}</p>
                </div>
            </div>

            @if($assessment->notes_dudi || $assessment->notes_teacher)
                <div class="text-[11px] text-indigo-200 bg-white/10 p-2.5 rounded-xl border border-white/10 space-y-1">
                    @if($assessment->notes_dudi)
                        <p><strong class="text-white">Catatan DU/DI:</strong> "{{ $assessment->notes_dudi }}"</p>
                    @endif
                    @if($assessment->notes_teacher)
                        <p><strong class="text-white">Catatan Guru:</strong> "{{ $assessment->notes_teacher }}"</p>
                    @endif
                </div>
            @endif
        </div>
    @endif

    <!-- Logout Button -->
    <div class="pt-2">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="w-full py-3 rounded-2xl bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 font-bold text-xs transition-colors flex items-center justify-center space-x-2">
                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                <span>Keluar dari Akun Siswa</span>
            </button>
        </form>
    </div>

</div>
@endsection
