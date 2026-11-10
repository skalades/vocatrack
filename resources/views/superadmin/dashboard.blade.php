@extends('layouts.app')

@section('page_title', 'Dashboard Platform Super Admin')
@section('page_subtitle', 'Monitoring agregat instalasi multi-sekolah VokaTrack')

@section('content')
<div class="space-y-6">

    <!-- Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Total Sekolah</span>
                <span class="text-2xl font-black text-slate-800">{{ $totalSchools }}</span>
                <span class="text-[11px] text-emerald-600 font-semibold block mt-1">{{ $activeSchools }} Sekolah Aktif</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl">
                🏫
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Total Siswa Terdaftar</span>
                <span class="text-2xl font-black text-slate-800">{{ $totalStudents }}</span>
                <span class="text-[11px] text-slate-400 font-medium block mt-1">Lintas seluruh sekolah</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                👨‍🎓
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Total Akun Pengguna</span>
                <span class="text-2xl font-black text-slate-800">{{ $totalUsers }}</span>
                <span class="text-[11px] text-slate-400 font-medium block mt-1">Admin, Guru, DU/DI, Siswa</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                👥
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Estimasi Storage</span>
                <span class="text-2xl font-black text-slate-800">4.2 MB</span>
                <span class="text-[11px] text-slate-400 font-medium block mt-1">Foto selfie & lampiran</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                💾
            </div>
        </div>
    </div>

    <!-- Quick Action & Recent Schools Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-800">Daftar Sekolah Terdaftar</h3>
                <p class="text-xs text-slate-500">Kelola tenant sekolah dan akun administrator</p>
            </div>
            <a href="{{ route('superadmin.schools') }}" class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-colors">
                Kelola Semua Sekolah &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200/80 font-bold uppercase text-[10px]">
                    <tr>
                        <th class="py-3 px-5">Nama Sekolah</th>
                        <th class="py-3 px-4">NPSN</th>
                        <th class="py-3 px-4">Kontak</th>
                        <th class="py-3 px-4 text-center">Kelas</th>
                        <th class="py-3 px-4 text-center">Mitra DU/DI</th>
                        <th class="py-3 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @foreach($recentSchools as $sch)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-5">
                                <span class="font-bold text-slate-900 block">{{ $sch->name }}</span>
                                <span class="text-[11px] text-slate-400 block truncate max-w-xs">{{ $sch->address }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold">{{ $sch->npsn }}</td>
                            <td class="py-3.5 px-4">
                                <span class="block">{{ $sch->email }}</span>
                                <span class="text-[11px] text-slate-400 block">{{ $sch->phone }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold">{{ $sch->classes_count }}</td>
                            <td class="py-3.5 px-4 text-center font-bold">{{ $sch->companies_count }}</td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold {{ $sch->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                    {{ $sch->is_active ? 'Aktif' : 'Non-Aktif' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
