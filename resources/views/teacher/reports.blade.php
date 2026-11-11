@extends('layouts.app')

@section('page_title', 'Ekspor Laporan PDF Siswa PKL')
@section('page_subtitle', 'Unduh dokumen laporan individu lengkap dengan kop dinas resmi sekolah dan lembar pengesahan')

@section('content')
<div class="space-y-6">

    <x-page-header 
        title="Dokumen Laporan PDF Siswa PKL" 
        subtitle="Unduh berkas laporan individu yang merangkum kop dinas, biodata, rekap kehadiran matriks, log jurnal terverifikasi, dan lembar nilai resmi."
    />

    <x-card noPadding>
        @if($studentPkls->isEmpty())
            <div class="py-12">
                <x-empty-state 
                    title="Belum Ada Dokumen Siswa" 
                    description="Anda belum memiliki siswa bimbingan aktif untuk diekspor laporannya."
                />
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($studentPkls as $sp)
                    <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/60 transition-colors">
                        <div class="space-y-1.5 min-w-0">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-700 font-bold text-xs flex items-center justify-center shrink-0">
                                    {{ substr($sp->student->user->name, 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <span class="font-extrabold text-sm text-slate-900 block truncate">{{ $sp->student->user->name }}</span>
                                    <span class="text-xs text-slate-500 font-medium block truncate">
                                        {{ $sp->student->class->name }} &bull; NISN: {{ $sp->student->nisn }} &bull; DU/DI: {{ $sp->company->name }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 pt-1 pl-13">
                                <x-badge variant="success" size="xs">
                                    {{ $sp->attendances_count }} Hari Presensi
                                </x-badge>
                                <x-badge variant="info" size="xs">
                                    {{ $sp->approved_journals_count }} Jurnal Disetujui
                                </x-badge>
                                @if($sp->assessment)
                                    <x-badge variant="warning" size="xs">
                                        Nilai: {{ number_format($sp->assessment->final_score, 1) }}
                                    </x-badge>
                                @endif
                            </div>
                        </div>

                        <a href="{{ route('guru.reports.download', $sp->id) }}" 
                           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold text-xs shadow-xs shadow-indigo-600/20 transition-all shrink-0 cursor-pointer">
                            <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Unduh Dokumen PDF</span>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>

</div>
@endsection
