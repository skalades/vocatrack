@extends('layouts.app')

@section('page_title', 'Dashboard Admin Sekolah')
@section('page_subtitle', 'Monitoring operasional dan presensi PKL di ' . (auth()->user()->school->name ?? 'Sekolah'))

@section('content')
<div class="space-y-6">

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Siswa Terdaftar</span>
                <span class="text-2xl font-black text-slate-800">{{ $totalStudents }}</span>
                <span class="text-[11px] text-indigo-600 font-semibold block mt-1">{{ $activePkl }} Sedang Aktif PKL</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl">
                👨‍🎓
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Guru Pembimbing</span>
                <span class="text-2xl font-black text-slate-800">{{ $totalTeachers }}</span>
                <span class="text-[11px] text-slate-400 font-medium block mt-1">Pembimbing Sekolah</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                👩‍🏫
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Mitra DU/DI</span>
                <span class="text-2xl font-black text-slate-800">{{ $totalCompanies }}</span>
                <span class="text-[11px] text-slate-400 font-medium block mt-1">Perusahaan Rekanan</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                🏢
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Presensi Hari Ini</span>
                <span class="text-2xl font-black text-slate-800">{{ $attendanceStats['hadir'] + $attendanceStats['terlambat'] }}</span>
                <span class="text-[11px] text-emerald-600 font-semibold block mt-1">Siswa telah masuk</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                📋
            </div>
        </div>
    </div>

    <!-- Attendance Today Status Matrix -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
        <h3 class="text-sm font-bold text-slate-800 mb-1">Ringkasan Presensi Hari Ini</h3>
        <p class="text-xs text-slate-500 mb-4">{{ \Carbon\Carbon::today()->isoFormat('dddd, D MMMM Y') }}</p>

        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-center">
            <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-100">
                <span class="text-2xl font-black text-emerald-700 block">{{ $attendanceStats['hadir'] }}</span>
                <span class="text-xs font-bold text-emerald-800 uppercase block mt-1">Hadir Tepat Waktu</span>
            </div>
            <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-100">
                <span class="text-2xl font-black text-amber-700 block">{{ $attendanceStats['terlambat'] }}</span>
                <span class="text-xs font-bold text-amber-800 uppercase block mt-1">Terlambat</span>
            </div>
            <div class="p-3.5 rounded-xl bg-blue-50 border border-blue-100">
                <span class="text-2xl font-black text-blue-700 block">{{ $attendanceStats['izin'] }}</span>
                <span class="text-xs font-bold text-blue-800 uppercase block mt-1">Izin</span>
            </div>
            <div class="p-3.5 rounded-xl bg-purple-50 border border-purple-100">
                <span class="text-2xl font-black text-purple-700 block">{{ $attendanceStats['sakit'] }}</span>
                <span class="text-xs font-bold text-purple-800 uppercase block mt-1">Sakit</span>
            </div>
            <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-100">
                <span class="text-2xl font-black text-rose-700 block">{{ $attendanceStats['alfa'] }}</span>
                <span class="text-xs font-bold text-rose-800 uppercase block mt-1">Alfa</span>
            </div>
        </div>
    </div>

    <!-- Quick Links Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('adminsekolah.students') }}" class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:border-indigo-300 transition-colors flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
                👥
            </div>
            <div>
                <span class="text-xs font-bold text-slate-800 block">Kelola Siswa & Impor</span>
                <span class="text-[11px] text-slate-400 block">Tambah dan impor data siswa via Excel/CSV</span>
            </div>
        </a>

        <a href="{{ route('adminsekolah.companies') }}" class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:border-indigo-300 transition-colors flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                🏢
            </div>
            <div>
                <span class="text-xs font-bold text-slate-800 block">Mitra DU/DI & GPS</span>
                <span class="text-[11px] text-slate-400 block">Atur geofencing koordinat dan radius kantor</span>
            </div>
        </a>

        <a href="{{ route('adminsekolah.plotting') }}" class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:border-indigo-300 transition-colors flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                📌
            </div>
            <div>
                <span class="text-xs font-bold text-slate-800 block">Plotting Penempatan</span>
                <span class="text-[11px] text-slate-400 block">Petakan siswa ke perusahaan dan guru pembimbing</span>
            </div>
        </a>
    </div>

</div>
@endsection
