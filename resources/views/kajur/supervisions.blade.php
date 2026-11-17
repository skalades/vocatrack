@extends('layouts.app')

@section('page_title', 'Supervisi Guru Pembimbing')
@section('page_subtitle', 'Rekapitulasi kunjungan monitoring industri oleh guru pembimbing jurusan ' . $major->name)

@section('content')
<div class="w-full space-y-6">

    <x-page-header 
        title="Supervisi & Kunjungan Industri" 
        subtitle="Logbook kunjungan pembimbingan dan penyerahan/penjemputan siswa PKL jurusan {{ $major->name }}."
        badge="SUPERVISI KAJUR"
    />

    <x-card :title="'Daftar Kunjungan Supervisi Guru (' . $supervisions->total() . ' Kunjungan Terdata)'">
        @if($supervisions->isEmpty())
            <x-empty-state 
                title="Belum Ada Catatan Kunjungan" 
                description="Guru pembimbing jurusan belum mencatat logbook kunjungan ke mitra industri."
            />
        @else
            <div class="overflow-x-auto -mx-6">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                            <th class="py-3 px-6">Guru Pembimbing</th>
                            <th class="py-3 px-4">Mitra DU/DI</th>
                            <th class="py-3 px-4">Tanggal Kunjungan</th>
                            <th class="py-3 px-6">Catatan / Hasil Evaluasi Industri</th>
                            <th class="py-3 px-6 text-right">Berita Acara</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach($supervisions as $sv)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3 px-6">
                                    <div class="font-bold text-slate-900">{{ $sv->teacher->user->name }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">NIP: {{ $sv->teacher->nip ?? '-' }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-semibold text-slate-900">{{ $sv->company->name }}</span>
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-700 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($sv->visit_date)->isoFormat('D MMMM Y') }}
                                </td>
                                <td class="py-3 px-6">
                                    <p class="text-slate-700 line-clamp-2 leading-relaxed">
                                        {{ $sv->notes ?? '-' }}
                                    </p>
                                </td>
                                <td class="py-3 px-6 text-right whitespace-nowrap">
                                    <a href="{{ route('guru.supervisions.pdf', $sv) }}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span>Unduh PDF</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pt-4 border-t border-slate-100">
                {{ $supervisions->links() }}
            </div>
        @endif
    </x-card>

</div>
@endsection
