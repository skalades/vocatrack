@extends('layouts.app')

@section('page_title', 'Dashboard Eksekutif Kepala Sekolah')
@section('page_subtitle', 'Analitik agregat ketercapaian dan kedisiplinan program PKL sekolah')

@section('content')
<div class="space-y-6">

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Siswa Aktif PKL</span>
                <span class="text-2xl font-black text-slate-800">{{ $totalActivePkl }}</span>
                <span class="text-[11px] text-indigo-600 font-semibold block mt-1">Sedang di industri</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl">
                👨‍🎓
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Mitra Industri DU/DI</span>
                <span class="text-2xl font-black text-slate-800">{{ $totalCompanies }}</span>
                <span class="text-[11px] text-slate-400 font-medium block mt-1">Perusahaan rekanan</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                🏢
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Tingkat Kehadiran</span>
                <span class="text-2xl font-black text-emerald-700">{{ $attendanceRate }}%</span>
                <span class="text-[11px] text-emerald-600 font-medium block mt-1">Kedisiplinan agregat</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                📈
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Jurnal Terverifikasi</span>
                <span class="text-2xl font-black text-indigo-700">{{ $journalApprovalRate }}%</span>
                <span class="text-[11px] text-indigo-600 font-medium block mt-1">Kelengkapan portofolio</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                📖
            </div>
        </div>
    </div>

    <!-- Jurusan Distribution & Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-3">
            <h3 class="text-sm font-bold text-slate-800">Distribusi Kompetensi Keahlian</h3>
            <p class="text-xs text-slate-500">Jumlah rombel per jurusan yang melaksanakan program PKL</p>

            <div class="divide-y divide-slate-100">
                @foreach($majors as $m)
                    <div class="py-3 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold text-slate-900 block">{{ $m->name }}</span>
                            <span class="text-[11px] text-indigo-600 font-mono font-semibold">Kode: {{ $m->code }}</span>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
                            {{ $m->classes_count }} Rombel
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-4">
            <h3 class="text-sm font-bold text-slate-800">Laporan & Rekapitulasi</h3>
            <p class="text-xs text-slate-500">Akses cepat ringkasan presensi dan evaluasi akhir siswa</p>

            <div class="space-y-3">
                <a href="{{ route('kepapsekolah.rekap') }}" class="p-3.5 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 flex items-center justify-between transition-colors">
                    <div class="flex items-center space-x-3">
                        <span class="text-xl">📊</span>
                        <div>
                            <span class="text-xs font-bold text-slate-800 block">Rekapitulasi Presensi Kelas</span>
                            <span class="text-[11px] text-slate-400 block">Matriks kehadiran bulanan (H, T, I, S, DL, LK, A)</span>
                        </div>
                    </div>
                    <span class="text-indigo-600 font-bold text-xs">&rarr;</span>
                </a>
            </div>
        </div>
    </div>

</div>
@endsection
