@extends('layouts.app')

@section('page_title', 'Dashboard Guru Pembimbing')
@section('page_subtitle', 'Monitoring real-time absensi dan bimbingan PKL siswa binaan')

@section('content')
<div class="space-y-6">

    <x-page-header 
        title="Dashboard Bimbingan PKL" 
        subtitle="Monitoring kehadiran real-time, evaluasi jurnal harian, dan penilaian kompetensi siswa binaan Anda."
    >
        <x-button type="button" variant="primary" size="sm" onclick="window.location.href='{{ route('guru.monitoring') }}'">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span>Monitoring Absensi</span>
        </x-button>
    </x-page-header>

    <!-- Stat Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <x-stat-card 
            title="Siswa Binaan" 
            :value="$totalStudents" 
            subtitle="Dalam bimbingan aktif Anda"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Presensi Masuk Hari Ini" 
            :value="$todayAttendances->count()" 
            subtitle="Siswa telah rekam kehadiran"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Permohonan Izin / Sakit" 
            :value="$pendingRequests" 
            subtitle="Menunggu verifikasi guru"
            :color="$pendingRequests > 0 ? 'amber' : 'indigo'"
            :badge="$pendingRequests > 0 ? 'Perlu Diproses' : null"
            :badgeVariant="$pendingRequests > 0 ? 'warning' : 'neutral'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Review Jurnal Tingkat 2" 
            :value="$pendingJournals" 
            subtitle="Lolos verifikasi industri"
            :color="$pendingJournals > 0 ? 'blue' : 'indigo'"
            :badge="$pendingJournals > 0 ? 'Review Guru' : null"
            :badgeVariant="$pendingJournals > 0 ? 'info' : 'neutral'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

    </div>

    <!-- Papan Pengumuman Bimbingan -->
    <x-card 
        title="Pengumuman & Instruksi Bimbingan" 
        subtitle="Siaran pengumuman aktif untuk bimbingan PKL"
    >
        <x-slot:headerActions>
            <a href="{{ route('guru.announcements') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-1">
                <span>Kelola Pengumuman Guru</span>
                <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </x-slot:headerActions>

        @if(isset($recentAnnouncements) && $recentAnnouncements->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                Belum ada pengumuman bimbingan aktif. <a href="{{ route('guru.announcements') }}" class="font-bold text-indigo-600 hover:underline">Tulis pengumuman baru</a>.
            </div>
        @endif
    </x-card>

    <!-- Mentored Students List -->
    <x-card 
        title="Daftar Siswa Bimbingan Aktif" 
        :subtitle="$studentPkls->count() . ' Siswa Dimonitor dan Dinilai Selama Masa PKL'"
    >
        @if($studentPkls->isEmpty())
            <x-empty-state 
                title="Belum Ada Siswa Binaan" 
                description="Hubungi Admin Sekolah untuk melakukan plotting siswa ke bimbingan Anda."
            />
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                @foreach($studentPkls as $sp)
                    <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/80 hover:bg-slate-50 hover:border-indigo-200 transition-all flex items-center justify-between gap-3 group">
                        <div class="flex items-center space-x-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-700 font-bold text-xs flex items-center justify-center shrink-0">
                                {{ substr($sp->student->user->name, 0, 1) }}
                            </div>
                            <div class="min-w-0">
                                <span class="font-bold text-slate-900 block truncate group-hover:text-indigo-600 transition-colors">{{ $sp->student->user->name }}</span>
                                <span class="text-[11px] text-slate-500 font-medium block truncate">{{ $sp->student->class->name }} &bull; NISN: {{ $sp->student->nisn }}</span>
                                <span class="text-[11px] font-semibold text-indigo-700 block truncate mt-0.5">DU/DI: {{ $sp->company->name }}</span>
                            </div>
                        </div>
                        <a href="{{ route('guru.reports.download', $sp->id) }}" class="px-3 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 font-bold text-xs shadow-2xs flex items-center space-x-1.5 shrink-0 transition-colors">
                            <svg class="w-3.5 h-3.5 text-rose-600 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Unduh PDF</span>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>

</div>
@endsection
