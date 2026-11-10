@extends('layouts.app')

@section('page_title', 'Ekspor Laporan PDF Siswa PKL')
@section('page_subtitle', 'Unduh dokumen laporan individu lengkap dengan kop resmi sekolah dan lembar pengesahan')

@section('content')
<div class="space-y-6">

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <h3 class="text-sm font-bold text-slate-800 mb-1">Daftar Dokumen Laporan Siswa</h3>
        <p class="text-xs text-slate-500 mb-4">Laporan memuat kop dinamis, identitas siswa, rekapitulasi kehadiran matriks, jurnal yang disetujui, dan lembar nilai</p>

        <div class="divide-y divide-slate-100">
            @foreach($studentPkls as $sp)
                <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50/50 transition-colors">
                    <div>
                        <span class="font-bold text-xs text-slate-900 block">{{ $sp->student->user->name }}</span>
                        <span class="text-[11px] text-slate-500 block">
                            {{ $sp->student->class->name }} &bull; NISN: {{ $sp->student->nisn }} &bull; DU/DI: {{ $sp->company->name }}
                        </span>
                        <div class="flex items-center space-x-2 mt-1">
                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                {{ $sp->attendances->count() }} Hari Presensi
                            </span>
                            <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-full border border-indigo-200">
                                {{ $sp->journals->where('status', 'approved')->count() }} Jurnal Disetujui
                            </span>
                            @if($sp->assessment)
                                <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                                    Nilai: {{ number_format($sp->assessment->final_score, 1) }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <a href="{{ route('guru.reports.download', $sp->id) }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-200 flex items-center justify-center space-x-1.5 transition-transform active:scale-[0.98]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Unduh Dokumen PDF</span>
                    </a>
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
