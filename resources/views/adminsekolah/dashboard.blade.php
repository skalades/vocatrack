@extends('layouts.app')

@section('page_title', 'Dashboard Admin Sekolah')
@section('page_subtitle', 'Monitoring operasional dan presensi PKL di ' . (auth()->user()->school->name ?? 'Sekolah'))

@section('content')
<div class="space-y-6">

    <x-page-header 
        title="Ringkasan Operasional PKL" 
        subtitle="Selamat datang di panel kontrol PKL. Pantau presensi harian, plotting penugasan, dan kemitraan industri."
    >
        <x-button type="button" variant="secondary" size="sm" onclick="window.location.href='{{ route('adminsekolah.settings') }}'">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span>Konfigurasi Jam Kerja</span>
        </x-button>
        <x-button type="button" variant="primary" size="sm" onclick="window.location.href='{{ route('adminsekolah.students') }}'">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span>Kelola Siswa</span>
        </x-button>
    </x-page-header>

    <!-- Primary Stat Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <x-stat-card 
            title="Siswa Terdaftar" 
            :value="$totalStudents" 
            :subtitle="$activePkl . ' Sedang Aktif PKL'"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Guru Pembimbing" 
            :value="$totalTeachers" 
            subtitle="Pembimbing Sekolah Aktif"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Mitra Industri DU/DI" 
            :value="$totalCompanies" 
            subtitle="Perusahaan Rekanan Aktif"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Presensi Masuk Hari Ini" 
            :value="$attendanceStats['hadir'] + $attendanceStats['terlambat']" 
            subtitle="Total Siswa Telah Masuk"
            color="amber"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

    </div>

    <!-- Attendance Today Status Matrix -->
    <x-card 
        title="Ringkasan Presensi Siswa Hari Ini" 
        :subtitle="\Carbon\Carbon::today()->isoFormat('dddd, D MMMM Y')"
    >
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
            
            <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-100 flex flex-col items-center justify-center text-center transition-all hover:bg-emerald-50">
                <span class="text-xs font-bold text-emerald-800 tracking-wider uppercase mb-1">Tepat Waktu</span>
                <span class="text-3xl font-black text-emerald-700 tracking-tight">{{ $attendanceStats['hadir'] }}</span>
                <span class="text-[10px] text-emerald-600 font-semibold mt-1">Siswa Hadir</span>
            </div>

            <div class="p-4 rounded-2xl bg-amber-50/70 border border-amber-100 flex flex-col items-center justify-center text-center transition-all hover:bg-amber-50">
                <span class="text-xs font-bold text-amber-800 tracking-wider uppercase mb-1">Terlambat</span>
                <span class="text-3xl font-black text-amber-700 tracking-tight">{{ $attendanceStats['terlambat'] }}</span>
                <span class="text-[10px] text-amber-600 font-semibold mt-1">Lewat Jam Masuk</span>
            </div>

            <div class="p-4 rounded-2xl bg-sky-50/70 border border-sky-100 flex flex-col items-center justify-center text-center transition-all hover:bg-sky-50">
                <span class="text-xs font-bold text-sky-800 tracking-wider uppercase mb-1">Izin</span>
                <span class="text-3xl font-black text-sky-700 tracking-tight">{{ $attendanceStats['izin'] }}</span>
                <span class="text-[10px] text-sky-600 font-semibold mt-1">Permohonan Izin</span>
            </div>

            <div class="p-4 rounded-2xl bg-purple-50/70 border border-purple-100 flex flex-col items-center justify-center text-center transition-all hover:bg-purple-50">
                <span class="text-xs font-bold text-purple-800 tracking-wider uppercase mb-1">Sakit</span>
                <span class="text-3xl font-black text-purple-700 tracking-tight">{{ $attendanceStats['sakit'] }}</span>
                <span class="text-[10px] text-purple-600 font-semibold mt-1">Surat Dokter</span>
            </div>

            <div class="col-span-2 sm:col-span-1 lg:col-span-1 p-4 rounded-2xl bg-rose-50/70 border border-rose-100 flex flex-col items-center justify-center text-center transition-all hover:bg-rose-50">
                <span class="text-xs font-bold text-rose-800 tracking-wider uppercase mb-1">Alfa</span>
                <span class="text-3xl font-black text-rose-700 tracking-tight">{{ $attendanceStats['alfa'] }}</span>
                <span class="text-[10px] text-rose-600 font-semibold mt-1">Tanpa Keterangan</span>
            </div>

        </div>
    </x-card>

    <!-- Papan Pengumuman Aktif -->
    <x-card 
        title="Pengumuman & Siaran Terkini" 
        subtitle="Siaran informasi aktif yang sedang ditayangkan untuk civitas sekolah"
    >
        <x-slot:headerActions>
            <a href="{{ route('adminsekolah.announcements') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-1">
                <span>Kelola Semua Pengumuman</span>
                <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </x-slot:headerActions>

        @if(isset($recentAnnouncements) && $recentAnnouncements->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($recentAnnouncements as $ann)
                    <div class="rounded-2xl border border-slate-200/80 p-3.5 bg-slate-50/50 hover:bg-white hover:shadow-xs transition-all flex flex-col justify-between">
                        <div>
                            @if($ann->image_path)
                                <div class="w-full h-24 rounded-xl overflow-hidden mb-2.5 bg-slate-100 border border-slate-200">
                                    <img src="{{ $ann->image_url }}" alt="{{ $ann->title }}" class="w-full h-full object-cover">
                                </div>
                            @endif
                            <div class="flex items-center justify-between gap-1 mb-1.5">
                                <x-badge :variant="$ann->category_badge_variant" size="xs">
                                    {{ $ann->category_label }}
                                </x-badge>
                                @if($ann->is_pinned)
                                    <span class="text-[9px] font-extrabold text-amber-700">📌 Pinned</span>
                                @endif
                            </div>
                            <h4 class="text-xs font-extrabold text-slate-900 line-clamp-2 leading-snug">{{ $ann->title }}</h4>
                            <p class="text-[11px] text-slate-500 line-clamp-2 mt-1 leading-relaxed">{{ Str::limit(strip_tags($ann->content), 70) }}</p>
                        </div>
                        <div class="pt-2 mt-2 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400">
                            <span class="truncate font-semibold">{{ $ann->target_role_label }}</span>
                            <span>{{ $ann->published_at ? $ann->published_at->diffForHumans() : $ann->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="py-6 text-center text-slate-400 text-xs">
                Belum ada pengumuman yang diterbitkan. <a href="{{ route('adminsekolah.announcements') }}" class="font-bold text-indigo-600 hover:underline">Buat pengumuman pertama</a>.
            </div>
        @endif
    </x-card>

    <!-- Quick Navigation Shortcuts -->
    <div>
        <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400 mb-3 px-1">Navigasi Operasional Cepat</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            
            <a href="{{ route('adminsekolah.students') }}" class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:border-indigo-300 hover:shadow-md transition-all group flex items-start space-x-3.5">
                <div class="w-11 h-11 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <span class="text-sm font-bold text-slate-800 group-hover:text-indigo-600 transition-colors block">Kelola Siswa & Impor</span>
                    <span class="text-xs text-slate-500 block mt-0.5 leading-relaxed">Tambah siswa baru atau impor massal via file Excel/CSV sesuai template resmi.</span>
                </div>
            </a>

            <a href="{{ route('adminsekolah.companies') }}" class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:border-blue-300 hover:shadow-md transition-all group flex items-start space-x-3.5">
                <div class="w-11 h-11 rounded-xl bg-blue-50 border border-blue-100 text-blue-600 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <span class="text-sm font-bold text-slate-800 group-hover:text-blue-600 transition-colors block">Mitra DU/DI & Geofencing</span>
                    <span class="text-xs text-slate-500 block mt-0.5 leading-relaxed">Kelola data mitra industri rekanan, atur koordinat GPS kantor dan radius toleransi presensi.</span>
                </div>
            </a>

            <a href="{{ route('adminsekolah.plotting') }}" class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:border-emerald-300 hover:shadow-md transition-all group flex items-start space-x-3.5">
                <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <span class="text-sm font-bold text-slate-800 group-hover:text-emerald-600 transition-colors block">Plotting Penugasan PKL</span>
                    <span class="text-xs text-slate-500 block mt-0.5 leading-relaxed">Petakan siswa ke perusahaan rekanan dan tugaskan guru pembimbing sekolah terkait.</span>
                </div>
            </a>

        </div>
    </div>

</div>
@endsection
