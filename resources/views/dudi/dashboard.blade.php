@extends('layouts.app')

@section('page_title', 'Dashboard Pembimbing Industri DU/DI')
@section('page_subtitle', 'Monitoring siswa magang di ' . $company->name)

@section('content')
<div class="space-y-6">

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Siswa Magang</span>
                <span class="text-2xl font-black text-slate-800">{{ $studentPkls->count() }}</span>
                <span class="text-[11px] text-slate-400 font-medium block mt-1">Ditempatkan di perusahaan</span>
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
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Dinas Luar & WFH</span>
                <span class="text-2xl font-black {{ $pendingRequests > 0 ? 'text-cyan-700' : 'text-slate-800' }}">{{ $pendingRequests }}</span>
                <span class="text-[11px] text-slate-400 font-medium block mt-1">Menunggu persetujuan DU/DI</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-xl">
                🚗
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Jurnal Tingkat 1</span>
                <span class="text-2xl font-black {{ $pendingJournals > 0 ? 'text-amber-600' : 'text-slate-800' }}">{{ $pendingJournals }}</span>
                <span class="text-[11px] text-slate-400 font-medium block mt-1">Menunggu review lapangan</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                📝
            </div>
        </div>
    </div>

    <!-- Company Geofencing Info Card -->
    <div class="p-4 rounded-2xl bg-indigo-50/70 border border-indigo-100 flex items-center justify-between">
        <div>
            <span class="text-xs font-bold text-indigo-950 block">Aturan Presensi Geofencing DU/DI</span>
            <p class="text-[11px] text-indigo-700 mt-0.5">
                Status GPS: <strong>{{ $company->gps_enabled ? 'Aktif (Radius ' . $company->radius_meters . ' Meter)' : 'Bebas Radius' }}</strong> &bull;
                Hari Kerja: <strong>{{ $company->work_days === '6_days' ? '6 Hari (Sen-Sab)' : '5 Hari (Sen-Jum)' }}</strong>
            </p>
        </div>
        @if($company->gps_enabled)
            <span class="px-3 py-1 rounded-full text-xs font-mono font-bold bg-white text-indigo-700 border border-indigo-200">
                {{ $company->latitude }}, {{ $company->longitude }}
            </span>
        @endif
    </div>

    <!-- Interns List -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <h3 class="text-sm font-bold text-slate-800 mb-1">Daftar Siswa Magang Aktif</h3>
        <p class="text-xs text-slate-500 mb-4">Siswa SMK yang menjalankan tugas praktik industri di perusahaan Anda</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($studentPkls as $sp)
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                    <div>
                        <span class="font-bold text-xs text-slate-900 block">{{ $sp->student->user->name }}</span>
                        <span class="text-[11px] text-slate-500 block">{{ $sp->student->class->name }} &bull; NISN: {{ $sp->student->nisn }}</span>
                        <span class="text-[10px] text-emerald-700 font-bold block mt-1">Status: AKTIF</span>
                    </div>
                    <div class="text-right">
                        <a href="{{ route('dudi.attendances') }}" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-100 block mb-1">
                            Lihat Presensi
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
