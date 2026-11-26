@extends('layouts.app')

@section('page_title', 'Dashboard Wali Kelas')
@section('page_subtitle', 'Pemantauan peserta didik PKL rombongan belajar ' . ($selectedClass?->name ?? ''))

@section('content')
<div class="w-full space-y-6">

    <!-- Hero Banner -->
    <div class="relative overflow-hidden bg-gradient-to-r from-[#0B2B25] via-slate-900 to-slate-950 rounded-3xl p-6 sm:p-7 text-white shadow-xl shadow-emerald-950/20 border border-emerald-900/40">
        <div class="relative space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-white/10">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-[#C6F26B] text-[#0B2B25] uppercase tracking-wider">
                        WALI KELAS
                    </span>
                    <span class="text-xs text-white/30">&bull;</span>
                    <span class="text-xs font-bold text-emerald-300">
                        {{ $classes->count() > 1 && !$activeClassFilter ? $classes->pluck('name')->implode(', ') : ($selectedClass?->name ?? '-') }}
                    </span>
                    @if($selectedClass?->major)
                        <span class="text-xs text-white/30">&bull;</span>
                        <span class="text-xs font-semibold text-slate-300">{{ $selectedClass->major->code }} - {{ $selectedClass->major->name }}</span>
                    @endif
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    @if($classes->count() > 1)
                        <form method="GET" action="{{ route('walikelas.dashboard') }}">
                            <select name="class_id" onchange="this.form.submit()" class="px-3 py-1 rounded-xl bg-white/10 border border-white/15 text-xs font-bold text-white focus:outline-none">
                                <option value="" class="text-slate-900">Semua Rombel ({{ $classes->count() }})</option>
                                @foreach($classes as $cls)
                                    <option value="{{ $cls->id }}" class="text-slate-900" {{ $activeClassFilter == $cls->id ? 'selected' : '' }}>
                                        {{ $cls->name }} ({{ $cls->academic_year }})
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-xl bg-white/[0.06] border border-white/10 text-xs text-slate-200">
                        <svg class="w-3.5 h-3.5 text-[#C6F26B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>{{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
                <div class="space-y-1.5 min-w-0">
                    <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-white">
                        Selamat Datang, {{ auth()->user()->name }}
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-300 font-medium max-w-2xl leading-relaxed">
                        Pantau kehadiran harian, kepatuhan jurnal, pembinaan kendala, serta distribusi link portal orang tua untuk peserta didik rombel ampuannya.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <x-button type="button" variant="primary" size="sm" onclick="window.location.href='{{ route('walikelas.students') }}'">
                        <span>Daftar Siswa & Plotting</span>
                    </x-button>
                    <x-button type="button" variant="secondary" size="sm" onclick="window.location.href='{{ route('walikelas.parent-links') }}'">
                        <span>Portal Orang Tua</span>
                    </x-button>
                </div>
            </div>
        </div>
    </div>

    <!-- Dual / Multi-Role Alert -->
    @if($myMenteesCount > 0 || auth()->user()->isKajur())
        <div class="p-4 rounded-2xl bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-200/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-xs">
            <div class="flex items-center space-x-3.5">
                <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-indigo-950">Anda Juga Memiliki Tugas Bimbingan / Struktural Lain</h4>
                    <p class="text-xs text-indigo-700">
                        @if($myMenteesCount > 0)
                            Anda membimbing langsung <strong>{{ $myMenteesCount }} siswa PKL</strong> ({{ $myPendingJournalsCount }} jurnal menunggu verifikasi).
                        @endif
                        @if(auth()->user()->isKajur())
                            Anda juga menjabat sebagai Kepala Jurusan <strong>{{ auth()->user()->headedMajor()?->code }}</strong>.
                        @endif
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if(auth()->user()->isKajur())
                    <a href="{{ route('kajur.dashboard') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold text-emerald-700 bg-white hover:bg-emerald-50 border border-emerald-200 transition-colors shadow-2xs">
                        <span>Panel Kajur</span>
                    </a>
                @endif
                <a href="{{ route('guru.dashboard') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-indigo-700 bg-white hover:bg-indigo-50 border border-indigo-200 transition-colors shadow-2xs">
                    <span>Portal Bimbingan Saya</span>
                </a>
            </div>
        </div>
    @endif

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card 
            title="Total Siswa Rombel" 
            :value="$totalStudents" 
            :subtitle="$totalPlotted . ' Terplotting • ' . $totalUnplotted . ' Belum'"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Presensi Hari Ini" 
            :value="$hadirToday" 
            :subtitle="$izinToday . ' Izin/Sakit • ' . $alfaToday . ' Alfa'"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Jurnal Menunggu Guru" 
            :value="$pendingJournalsCount" 
            subtitle="Menunggu review guru pembimbing"
            :color="$pendingJournalsCount > 0 ? 'amber' : 'emerald'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Kendala Terbuka" 
            :value="$openIncidentsCount" 
            subtitle="Laporan kendala aktif siswa kelas"
            :color="$openIncidentsCount > 0 ? 'rose' : 'indigo'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Peringatan Dini & Belum Plotting -->
        <div class="lg:col-span-1 space-y-6">
            <x-card title="Perlu Perhatian (Alfa >= 2)" subtitle="Siswa kelas dengan akumulasi mangkir">
                @if($highAlfaStudents->isEmpty())
                    <x-empty-state title="Kedisiplinan Baik" description="Belum ada siswa dengan akumulasi alfa >= 2 hari." />
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($highAlfaStudents as $row)
                            <div class="py-3 first:pt-0 last:pb-0 flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-slate-900 truncate">{{ $row->studentPkl?->student?->user?->name ?? 'Siswa' }}</p>
                                    <p class="text-[11px] text-slate-500 truncate">{{ $row->studentPkl?->company?->name ?? '-' }}</p>
                                </div>
                                <x-badge variant="danger" size="xs">{{ $row->alfa_count }}x Alfa</x-badge>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>

            <x-card title="Siswa Belum Terplotting" subtitle="Eskalasikan ke Kajur atau Admin Sekolah">
                @if($unplottedStudents->isEmpty())
                    <x-empty-state title="Semua Sudah Terplotting" description="Seluruh siswa di rombel ini sudah mendapatkan tempat PKL." />
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($unplottedStudents as $st)
                            <div class="py-2.5 first:pt-0 last:pb-0 flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-slate-900 truncate">{{ $st->user?->name }}</p>
                                    <p class="text-[11px] text-slate-400 font-mono">NISN: {{ $st->nisn ?? '-' }}</p>
                                </div>
                                <x-badge variant="warning" size="xs">{{ $st->schoolClass?->name }}</x-badge>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>

        <!-- Kendala / Insiden Terbaru -->
        <div class="lg:col-span-2">
            <x-card title="Laporan Kendala & Pembinaan Kelas" subtitle="Pantau laporan kendala siswa rombel Anda">
                <x-slot:headerActions>
                    <a href="{{ route('walikelas.incidents') }}" class="text-xs font-bold text-emerald-700 hover:underline">Lihat Semua &rarr;</a>
                </x-slot:headerActions>
                @if($recentIncidents->isEmpty())
                    <x-empty-state title="Tidak Ada Laporan Kendala" description="Kegiatan PKL siswa pada rombel Anda berjalan aman dan lancar." />
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($recentIncidents as $inc)
                            <div class="py-3.5 first:pt-0 last:pb-0 flex items-start justify-between gap-3">
                                <div class="min-w-0 space-y-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-slate-900 text-xs sm:text-sm">{{ $inc->studentPkl?->student?->user?->name ?? 'Siswa' }}</span>
                                        <span class="text-[11px] text-slate-500 font-semibold">({{ $inc->studentPkl?->student?->schoolClass?->name ?? '-' }})</span>
                                        <x-badge :variant="$inc->status === 'resolved' ? 'emerald' : 'rose'" size="xs">{{ strtoupper($inc->status) }}</x-badge>
                                    </div>
                                    <p class="text-xs text-slate-700 font-medium line-clamp-1">{{ $inc->subject }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $inc->studentPkl?->company?->name ?? 'DU/DI' }} &bull; {{ $inc->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>
    </div>

</div>
@endsection
