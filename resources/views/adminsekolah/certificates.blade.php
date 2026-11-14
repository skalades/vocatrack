@extends('layouts.app')

@section('page_title', 'E-Sertifikat & Transkrip PKL')
@section('page_subtitle', 'Penerbitan dokumen sertifikat kelulusan resmi berstandar industri dengan validasi QR Code publik')

@section('content')
<div class="w-full space-y-6">

    <!-- Enterprise Hero Banner -->
    <x-page-header 
        title="E-Sertifikat & Transkrip Kelulusan PKL" 
        subtitle="Penerbitan dokumen sertifikat kelulusan resmi berstandar industri dengan validasi QR Code publik anti-pemalsuan."
        badge="LUARAN RESMI"
    >
        @if ($stats['ready_to_issue'] > 0)
            <form action="{{ route('adminsekolah.certificates.batch') }}" method="POST" onsubmit="return confirm('Terbitkan E-Sertifikat untuk seluruh {{ $stats['ready_to_issue'] }} siswa yang sudah dinilai?')">
                @csrf
                <x-button type="submit" variant="primary" size="sm">
                    <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span>Terbitkan Massal ({{ $stats['ready_to_issue'] }} Siswa)</span>
                </x-button>
            </form>
        @endif
    </x-page-header>

    @if(auth()->user()->school && !auth()->user()->school->hasFeature('certificate'))
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-amber-900 shadow-xs">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <h4 class="text-xs sm:text-sm font-bold">Fitur E-Sertifikat Terbatas</h4>
                    <p class="text-xs text-amber-700">Paket sewa sekolah Anda saat ini belum mencakup penerbitan E-Sertifikat & Transkrip. Hubungi Super Administrator untuk upgrade paket lisensi.</p>
                </div>
            </div>
            <a href="{{ route('adminsekolah.billing') }}" class="px-3.5 py-1.5 rounded-xl bg-amber-600 text-white font-bold text-xs hover:bg-amber-700 transition-colors shrink-0 text-center">
                Info Langganan
            </a>
        </div>
    @endif

    <!-- KPI STATS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <x-stat-card 
            title="Total Sertifikat Terbit" 
            :value="$stats['total_issued']" 
            subtitle="Dokumen sah bertanda tangan"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Siap Diterbitkan" 
            :value="$stats['ready_to_issue']" 
            subtitle="Siswa telah dinilai DU/DI & Guru"
            :color="$stats['ready_to_issue'] > 0 ? 'amber' : 'indigo'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Predikat Sangat Baik" 
            :value="$stats['sangat_baik']" 
            subtitle="Siswa berpredikat unggul (A)"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- ANTRIAN PENERBITAN BARU -->
    @if ($unissuedStudents->isNotEmpty())
        <div class="bg-amber-50/70 border border-amber-200/90 rounded-3xl p-5 sm:p-6 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-black text-amber-950 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                        Siswa Siap Diterbitkan Sertifikatnya ({{ $unissuedStudents->count() }})
                    </h3>
                    <p class="text-xs text-amber-800/90 mt-0.5">Penilaian dari DU/DI dan Guru telah lengkap. Anda dapat menerbitkan sertifikat secara individual atau massal.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach ($unissuedStudents->take(6) as $spkl)
                    <div class="bg-white p-4 rounded-2xl border border-amber-200/80 shadow-2xs flex flex-col justify-between gap-3">
                        <div>
                            <div class="font-bold text-slate-900 text-xs sm:text-sm">{{ $spkl->student->user->name ?? 'Siswa' }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">{{ $spkl->company->name ?? '-' }}</div>
                            <div class="text-xs font-bold text-emerald-600 mt-1">Nilai: {{ number_format($spkl->assessment->final_score ?? 80, 1) }}</div>
                        </div>
                        <form action="{{ route('adminsekolah.certificates.generate') }}" method="POST">
                            @csrf
                            <input type="hidden" name="student_pkl_id" value="{{ $spkl->id }}">
                            <button type="submit" class="w-full py-1.5 px-3 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-xl transition-colors shadow-2xs">
                                Terbitkan Sertifikat
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- TABEL SERTIFIKAT RESMI -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="font-black text-slate-900 text-sm sm:text-base flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Daftar E-Sertifikat Diterbitkan</span>
            </div>

            <form method="GET" action="{{ route('adminsekolah.certificates') }}" class="flex items-center gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama siswa atau no sertifikat..." class="text-xs font-semibold rounded-xl border-slate-200 bg-white shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 w-64">
                <button type="submit" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">Cari</button>
                @if(request('search'))
                    <a href="{{ route('adminsekolah.certificates') }}" class="text-xs font-bold text-slate-500 hover:text-rose-600">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs min-w-[880px]">
                <thead class="bg-slate-50 text-slate-400 uppercase font-black tracking-wider text-[10px] border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3.5 min-w-[150px]">Nomor & Tanggal</th>
                        <th class="px-5 py-3.5 min-w-[180px]">Nama Siswa</th>
                        <th class="px-5 py-3.5 min-w-[180px]">Mitra Industri (DU/DI)</th>
                        <th class="px-5 py-3.5 text-center min-w-[110px]">Nilai & Predikat</th>
                        <th class="px-5 py-3.5 text-center min-w-[120px]">Verifikasi QR</th>
                        <th class="px-5 py-3.5 text-right table-sticky-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse ($certificates as $cert)
                        <tr class="hover:bg-slate-50/60 transition-colors group">
                            <td class="px-5 py-4 whitespace-nowrap min-w-[150px]">
                                <div class="font-mono font-bold text-slate-900">{{ $cert->certificate_number }}</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">{{ $cert->issued_date ? $cert->issued_date->translatedFormat('d M Y') : '-' }}</div>
                            </td>
                            <td class="px-5 py-4 min-w-[180px]">
                                <div class="font-bold text-slate-900 truncate" title="{{ $cert->studentPkl->student->user->name ?? '-' }}">{{ $cert->studentPkl->student->user->name ?? '-' }}</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">{{ $cert->studentPkl->student->class->name ?? '-' }}</div>
                            </td>
                            <td class="px-5 py-4 min-w-[180px]">
                                <div class="font-semibold text-slate-800 truncate" title="{{ $cert->studentPkl->company->name ?? '-' }}">{{ $cert->studentPkl->company->name ?? '-' }}</div>
                                <div class="text-[11px] text-slate-400 mt-0.5 truncate" title="{{ $cert->studentPkl->pklPeriod->name ?? '-' }}">{{ $cert->studentPkl->pklPeriod->name ?? '-' }}</div>
                            </td>
                            <td class="px-5 py-4 text-center whitespace-nowrap min-w-[110px]">
                                <div class="font-black text-slate-900 text-sm font-mono">{{ number_format($cert->final_score, 1) }}</div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold border mt-0.5 {{ $cert->predicate_badge_class }}">
                                    {{ $cert->final_predicate }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center whitespace-nowrap min-w-[120px]">
                                <a href="{{ $cert->verification_url }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-indigo-600 hover:text-indigo-800 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    Portal Publik
                                </a>
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap table-sticky-right">
                                <a href="{{ route('adminsekolah.certificates.download', $cert->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200/80 rounded-xl text-xs font-bold transition-all shadow-2xs">
                                    <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Unduh PDF
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                Belum ada E-Sertifikat yang diterbitkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($certificates->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $certificates->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
