@extends('layouts.app')

@section('page_title', 'Dashboard Eksekutif Kepala Sekolah')
@section('page_subtitle', 'Analitik agregat ketercapaian dan kedisiplinan program PKL sekolah')

@section('content')
<div class="space-y-6">

    <x-page-header 
        title="Dashboard Eksekutif PKL" 
        subtitle="Analisis agregat partisipasi siswa, mitra industri DU/DI, rasio kehadiran, dan ketercapaian jurnal portofolio."
    />

    <!-- Executive Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <x-stat-card 
            title="Siswa Aktif PKL" 
            :value="$totalActivePkl" 
            subtitle="Sedang aktif di industri"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Mitra Industri DU/DI" 
            :value="$totalCompanies" 
            subtitle="Perusahaan rekanan aktif"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Tingkat Kehadiran" 
            :value="$attendanceRate . '%'" 
            subtitle="Kedisiplinan presensi agregat"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Jurnal Terverifikasi" 
            :value="$journalApprovalRate . '%'" 
            subtitle="Kelengkapan portofolio siswa"
            color="purple"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

    </div>

    <!-- Jurusan Distribution & Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <x-card 
            title="Distribusi Kompetensi Keahlian" 
            subtitle="Jumlah rombongan belajar per jurusan yang mengikuti program PKL"
        >
            <div class="divide-y divide-slate-100">
                @foreach($majors as $m)
                    <div class="py-3.5 first:pt-0 last:pb-0 flex items-center justify-between text-xs">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-700 font-extrabold text-xs flex items-center justify-center shrink-0">
                                {{ $m->code }}
                            </div>
                            <div>
                                <span class="font-bold text-slate-900 block">{{ $m->name }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">Kode ID: #{{ $m->id }}</span>
                            </div>
                        </div>
                        <x-badge variant="neutral" size="sm">
                            {{ $m->classes_count }} Rombel Kelas
                        </x-badge>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card 
            title="Laporan & Rekapitulasi Eksekutif" 
            subtitle="Akses cepat dokumen agregat presensi dan evaluasi akhir siswa"
        >
            <div class="space-y-3">
                <a href="{{ route('kepsek.rekap') }}" class="p-4 rounded-2xl bg-slate-50/80 hover:bg-slate-50 border border-slate-200/80 hover:border-indigo-300 transition-all flex items-center justify-between group shadow-2xs">
                    <div class="flex items-center space-x-3.5">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-700 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                            <svg class="w-5 h-5 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-slate-900 group-hover:text-indigo-600 transition-colors block">Rekapitulasi Presensi Matriks</span>
                            <span class="text-[11px] text-slate-500 font-medium block mt-0.5">Matriks kehadiran bulanan (Hadir, Terlambat, Izin, Sakit, Alfa)</span>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-slate-400 group-hover:text-indigo-600 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </x-card>

    </div>

</div>
@endsection
