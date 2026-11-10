@extends('layouts.app')

@section('page_title', 'Dashboard Guru Pembimbing')
@section('page_subtitle', 'Monitoring real-time absensi dan bimbingan PKL siswa binaan')

@section('content')
<div class="space-y-6">

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Siswa Binaan</span>
                <span class="text-2xl font-black text-slate-800">{{ $totalStudents }}</span>
                <span class="text-[11px] text-slate-400 font-medium block mt-1">Dalam bimbingan Anda</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl">
                👨‍🎓
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Presensi Hari Ini</span>
                <span class="text-2xl font-black text-emerald-700">{{ $todayAttendances->count() }}</span>
                <span class="text-[11px] text-slate-400 font-medium block mt-1">Siswa hadir / rekam</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                ✓
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Permohonan Izin / Sakit</span>
                <span class="text-2xl font-black {{ $pendingRequests > 0 ? 'text-amber-600' : 'text-slate-800' }}">{{ $pendingRequests }}</span>
                <span class="text-[11px] text-slate-400 font-medium block mt-1">Menunggu approval</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                📝
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Jurnal Tingkat 2</span>
                <span class="text-2xl font-black {{ $pendingJournals > 0 ? 'text-blue-600' : 'text-slate-800' }}">{{ $pendingJournals }}</span>
                <span class="text-[11px] text-slate-400 font-medium block mt-1">Menunggu review guru</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                📖
            </div>
        </div>
    </div>

    <!-- Mentored Students List -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <h3 class="text-sm font-bold text-slate-800 mb-1">Daftar Siswa Bimbingan</h3>
        <p class="text-xs text-slate-500 mb-4">Siswa yang dimonitor dan dinilai selama kegiatan PKL</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($studentPkls as $sp)
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                    <div>
                        <span class="font-bold text-xs text-slate-900 block">{{ $sp->student->user->name }}</span>
                        <span class="text-[11px] text-slate-500 block">{{ $sp->student->class->name }} &bull; NISN: {{ $sp->student->nisn }}</span>
                        <span class="text-[11px] text-indigo-700 font-semibold block mt-1">DU/DI: {{ $sp->company->name }}</span>
                    </div>
                    <a href="{{ route('guru.reports.download', $sp->id) }}" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 font-bold text-xs shadow-2xs flex items-center space-x-1">
                        <span>📄 PDF</span>
                    </a>
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
