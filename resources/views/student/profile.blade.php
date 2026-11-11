@extends('layouts.student-mobile')

@section('title', 'Profil & Penempatan PKL — VokaTrack Siswa')

@section('content')
<div class="space-y-4">

    <!-- Digital Student Card Header -->
    <div class="relative overflow-hidden bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs text-center">
        <div class="absolute top-0 inset-x-0 h-16 bg-gradient-to-r from-indigo-600 via-indigo-700 to-indigo-800"></div>
        <div class="relative pt-4">
            <div class="w-18 h-18 rounded-3xl bg-white p-1 shadow-lg mx-auto mb-3">
                <div class="w-full h-full rounded-2xl bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white font-black text-xl flex items-center justify-center shadow-inner">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
            </div>
            
            <h2 class="text-base font-extrabold text-slate-900 tracking-tight">{{ auth()->user()->name }}</h2>
            <div class="flex items-center justify-center gap-2 mt-1 text-xs text-slate-500 font-mono font-medium">
                <span>NISN: {{ $student->nisn }}</span>
                <span>&bull;</span>
                <span>NIS: {{ $student->nis }}</span>
            </div>

            <div class="mt-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200/80">
                    {{ $student->class->name ?? '-' }} ({{ $student->class->major->name ?? '-' }})
                </span>
            </div>
        </div>
    </div>

    <!-- PKL Placement Details Card -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs space-y-3.5">
        <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
            <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Informasi Penempatan PKL</span>
            <x-badge variant="success" size="xs">
                Aktif PKL
            </x-badge>
        </div>

        <div class="space-y-3 text-xs">
            <div>
                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Perusahaan Mitra (DU/DI)</span>
                <span class="font-extrabold text-slate-900 text-sm block mt-0.5">{{ $spkl->company->name }}</span>
                <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed font-medium">{{ $spkl->company->address }}</p>
            </div>

            <div class="grid grid-cols-2 gap-2.5 pt-2 border-t border-slate-100">
                <div class="p-2.5 rounded-2xl bg-slate-50/80 border border-slate-100">
                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Mentor DU/DI</span>
                    <span class="font-bold text-slate-900 block truncate mt-0.5">{{ $spkl->supervisor->user->name ?? 'Belum Ditentukan' }}</span>
                    <p class="text-[10px] text-slate-400 truncate">{{ $spkl->supervisor->position ?? 'Pembimbing Industri' }}</p>
                </div>
                <div class="p-2.5 rounded-2xl bg-slate-50/80 border border-slate-100">
                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Guru Pembimbing</span>
                    <span class="font-bold text-slate-900 block truncate mt-0.5">{{ $spkl->teacher->user->name ?? 'Belum Ditentukan' }}</span>
                    <p class="text-[10px] text-slate-400 font-mono truncate">NIP: {{ $spkl->teacher->nip ?? '-' }}</p>
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                <div>
                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Periode Pelaksanaan</span>
                    <span class="font-semibold text-slate-700 text-xs block mt-0.5">
                        {{ \Carbon\Carbon::parse($spkl->pklPeriod->start_date)->isoFormat('D MMMM Y') }} &ndash; {{ \Carbon\Carbon::parse($spkl->pklPeriod->end_date)->isoFormat('D MMMM Y') }}
                    </span>
                </div>
                <x-badge variant="indigo" size="xs">
                    {{ $spkl->pklPeriod->name }}
                </x-badge>
            </div>
        </div>
    </div>

    <!-- Final Assessment Card (if graded) -->
    @if($assessment)
        <div class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-5 text-white shadow-xl space-y-3.5 border border-indigo-900/40">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-amber-400/20 text-amber-300 flex items-center justify-center text-xs">
                        ★
                    </span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-200">Lembar Nilai Akhir PKL</h3>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    Nilai Final
                </span>
            </div>

            <div class="flex items-center justify-between py-2 border-y border-white/10">
                <div>
                    <span class="text-[10px] text-indigo-300 uppercase tracking-wider block font-bold">Skor Akhir Terpadu</span>
                    <span class="text-xs text-slate-300 font-medium">Bobot 60% DU/DI : 40% Sekolah</span>
                </div>
                <div class="text-3xl font-black text-amber-300 font-mono">
                    {{ number_format($assessment->final_score, 1) }}
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 text-xs pt-1">
                <div class="bg-white/5 p-3 rounded-2xl border border-white/10">
                    <span class="text-[10px] text-indigo-300 font-bold block uppercase tracking-wider mb-1">DU/DI (Industri)</span>
                    <p class="text-[11px] text-slate-200">Kedisiplinan: <strong>{{ $assessment->score_dudi_discipline }}</strong></p>
                    <p class="text-[11px] text-slate-200">Kompetensi Teknis: <strong>{{ $assessment->score_dudi_technical }}</strong></p>
                    <p class="text-[11px] text-slate-200">Inisiatif: <strong>{{ $assessment->score_dudi_initiative }}</strong></p>
                </div>
                <div class="bg-white/5 p-3 rounded-2xl border border-white/10">
                    <span class="text-[10px] text-indigo-300 font-bold block uppercase tracking-wider mb-1">Sekolah (Guru)</span>
                    <p class="text-[11px] text-slate-200">Laporan & Jurnal: <strong>{{ $assessment->score_teacher_report }}</strong></p>
                </div>
            </div>

            @if($assessment->notes_dudi || $assessment->notes_teacher)
                <div class="text-[11px] text-slate-300 bg-white/5 p-3 rounded-2xl border border-white/10 space-y-1 leading-relaxed">
                    @if($assessment->notes_dudi)
                        <p><strong class="text-amber-200">Catatan DU/DI:</strong> "{{ $assessment->notes_dudi }}"</p>
                    @endif
                    @if($assessment->notes_teacher)
                        <p><strong class="text-indigo-200">Catatan Guru:</strong> "{{ $assessment->notes_teacher }}"</p>
                    @endif
                </div>
            @endif
        </div>
    @endif

    <!-- Logout Button -->
    <div class="pt-2">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="w-full py-3.5 px-4 rounded-2xl bg-rose-50 hover:bg-rose-100 active:bg-rose-200 border border-rose-200 text-rose-700 font-bold text-xs transition-colors flex items-center justify-center space-x-2 cursor-pointer">
                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                <span>Keluar dari Akun Siswa</span>
            </button>
        </form>
    </div>

</div>
@endsection
