@extends('layouts.app')

@section('page_title', 'Rekapitulasi Nilai PKL Kelas')
@section('page_subtitle', 'Rekap nilai industri dan sekolah untuk bahan rapor siswa rombel Anda')

@section('content')
<div class="w-full space-y-6">

    <x-page-header 
        title="Rekapitulasi Nilai PKL Rombel" 
        subtitle="Pantau nilai akhir PKL (DU/DI 60% + Guru Pembimbing 40%) dan status sertifikat siswa rombel Anda."
        badge="NILAI & RAPOR WALI KELAS"
    >
        <a href="{{ route('walikelas.grades', array_merge(request()->query(), ['export' => 'csv'])) }}" 
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-colors shadow-2xs">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Ekspor Rekap CSV</span>
        </a>
    </x-page-header>

    @if($classes->count() > 1)
        <x-card>
            <form method="GET" action="{{ route('walikelas.grades') }}" class="flex flex-col sm:flex-row gap-3 items-end">
                <div class="flex-1 w-full">
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Rombel Kelas</label>
                    <select name="class_id" class="w-full text-xs sm:text-sm px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                        <option value="">-- Semua Rombel Ampuan --</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-button type="submit" variant="primary" size="md">Filter</x-button>
            </form>
        </x-card>
    @endif

    <x-card :title="'Daftar Rekap Nilai Siswa (' . $records->total() . ' Peserta Terdata)'">
        @if($records->isEmpty())
            <x-empty-state title="Belum Ada Rekap Nilai" description="Siswa rombel Anda belum memiliki data penugasan PKL atau penilaian." />
        @else
            <div class="overflow-x-auto -mx-6">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                            <th class="py-3 px-6">Identitas Siswa</th>
                            <th class="py-3 px-4">Kelas</th>
                            <th class="py-3 px-4">Mitra DU/DI</th>
                            <th class="py-3 px-4 text-center">Nilai DU/DI (60%)</th>
                            <th class="py-3 px-4 text-center">Nilai Guru (40%)</th>
                            <th class="py-3 px-4 text-center">Nilai Akhir</th>
                            <th class="py-3 px-6 text-center">Status & Sertifikat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach($records as $r)
                            @php
                                $ass = $r->assessment;
                                $scoreDudi = $ass ? round(($ass->score_dudi_discipline + $ass->score_dudi_technical + $ass->score_dudi_initiative) / 3, 1) : null;
                                $scoreGuru = $ass ? $ass->score_teacher_report : null;
                                $finalScore = $ass ? $ass->final_score : null;
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3 px-6">
                                    <div class="font-bold text-slate-900">{{ $r->student?->user?->name ?? 'Siswa' }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">NISN: {{ $r->student?->nisn ?? '-' }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md font-semibold text-slate-700 bg-slate-100 text-[11px]">
                                        {{ $r->student?->schoolClass?->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-900">{{ $r->company?->name ?? '-' }}</td>
                                <td class="py-3 px-4 text-center font-mono font-bold text-slate-800">{{ $scoreDudi ?? '-' }}</td>
                                <td class="py-3 px-4 text-center font-mono font-bold text-slate-800">{{ $scoreGuru ?? '-' }}</td>
                                <td class="py-3 px-4 text-center font-mono font-extrabold text-sm {{ $finalScore >= 75 ? 'text-emerald-700' : 'text-slate-900' }}">
                                    {{ $finalScore ?? '-' }}
                                </td>
                                <td class="py-3 px-6 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <x-badge :variant="$ass ? 'emerald' : 'amber'" size="xs">
                                            {{ $ass ? 'Selesai Dinilai' : 'Belum Lengkap' }}
                                        </x-badge>
                                        @if($r->certificate)
                                            <x-badge variant="indigo" size="xs">Sertifikat Terbit</x-badge>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pt-4 border-t border-slate-100">
                {{ $records->links() }}
            </div>
        @endif
    </x-card>

</div>
@endsection
