@extends('layouts.app')

@section('page_title', 'Approval Jurnal Siswa Magang (Tingkat 1 — DU/DI)')
@section('page_subtitle', 'Verifikasi kesesuaian aktivitas teknis di lapangan sebelum diteruskan ke guru pembimbing')

@section('content')
<div class="max-w-7xl mx-auto space-y-6 animate-in fade-in duration-300">

    <!-- Enterprise Hero Banner -->
    <x-page-header 
        title="Approval Jurnal Magang (Tingkat 1)" 
        subtitle="Mentor industri bertindak sebagai lini pertama verifikasi logbook harian, pekerjaan teknis, dan foto dokumentasi sebelum divalidasi oleh pihak sekolah."
        badge="PEMBIMBING INDUSTRI DU/DI"
    >
        <x-button type="button" variant="secondary" size="sm" onclick="window.location.href='{{ route('dudi.attendances') }}'">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            <span>Validasi Presensi</span>
        </x-button>
        <x-button type="button" variant="primary" size="sm" onclick="window.location.href='{{ route('dudi.assessments') }}'">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <span>Penilaian Praktik DU/DI</span>
        </x-button>
    </x-page-header>

    @php
        $pendingDudi = $journals->where('status', 'submitted')->count();
        $approvedLevel1 = $journals->whereIn('status', ['review_guru', 'approved'])->count();
        $revisionCount = $journals->where('status', 'revision')->count();
        $allPendingJournals = $studentPkls->flatMap->journals->where('status', 'submitted');
    @endphp

    <!-- Top KPI Metrics -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <x-stat-card 
            title="Total Jurnal Siswa" 
            :value="$journals->total()" 
            subtitle="Seluruh log aktivitas lapangan"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Menunggu Review DU/DI" 
            :value="$pendingDudi" 
            subtitle="Perlu verifikasi mentor industri"
            :color="$pendingDudi > 0 ? 'amber' : 'indigo'"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Lolos Verifikasi Tingkat 1" 
            :value="$approvedLevel1" 
            subtitle="Diteruskan ke pihak sekolah"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Siswa Magang Aktif" 
            :value="$studentPkls->count()" 
            subtitle="Total siswa di unit industri Anda"
            color="cyan"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Segmented View Mode Controller -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-1.5 bg-slate-200/60 rounded-2xl border border-slate-200">
        <div class="flex items-center gap-1 overflow-x-auto p-0.5">
            <button type="button" id="tab-btn-students" onclick="switchJournalView('students')" 
                    class="view-tab-btn inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all bg-cyan-600 text-white shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Kelompok per Siswa</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-white/20 text-white font-mono">
                    {{ $studentPkls->count() }}
                </span>
            </button>

            <button type="button" id="tab-btn-pending" onclick="switchJournalView('pending')" 
                    class="view-tab-btn inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all bg-white text-slate-700 hover:bg-slate-50 border border-transparent">
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Antrean Butuh Validasi</span>
                @if($pendingDudi > 0)
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-amber-500 text-white font-mono animate-pulse">
                        {{ $pendingDudi }}
                    </span>
                @endif
            </button>

            <button type="button" id="tab-btn-table" onclick="switchJournalView('table')" 
                    class="view-tab-btn inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all bg-white text-slate-700 hover:bg-slate-50 border border-transparent">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Tabel Lengkap</span>
            </button>
        </div>

        <div class="px-3 text-xs text-slate-500 font-medium hidden sm:block">
            Terorganisir untuk mentor industri
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 1: KELOMPOK PER SISWA MAGANG                                         -->
    <!-- ========================================================================= -->
    <div id="view-students" class="space-y-4">
        @forelse($studentPkls as $sp)
            @php
                $sJournals = $sp->journals ?? collect();
                $sPending = $sJournals->where('status', 'submitted')->count();
                $sApproved = $sJournals->whereIn('status', ['review_guru', 'approved'])->count();
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs hover:border-slate-300 transition-all overflow-hidden">
                <div onclick="toggleStudentJournals('{{ $sp->id }}')" 
                     class="p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 cursor-pointer hover:bg-slate-50/60 transition-colors">
                    
                    <div class="flex items-start sm:items-center space-x-3.5 min-w-0">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-cyan-600 to-cyan-800 text-white font-black text-sm flex items-center justify-center shrink-0 shadow-sm shadow-cyan-600/20">
                            {{ substr($sp->student->user->name, 0, 1) }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h4 class="text-sm font-extrabold text-slate-900 truncate">{{ $sp->student->user->name }}</h4>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $sp->student->class->name ?? 'Kelas' }}
                                </span>
                                @if($sPending > 0)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 animate-pulse">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        {{ $sPending }} Butuh Review
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 text-xs text-slate-500 mt-1 flex-wrap">
                                <span class="font-mono text-[11px] text-slate-400">NISN: {{ $sp->student->nisn }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0 pt-2 md:pt-0 border-t md:border-t-0 border-slate-100">
                        <div class="flex items-center gap-2 text-xs">
                            <span class="px-2.5 py-1 rounded-xl bg-slate-100 font-bold text-slate-700">
                                {{ $sJournals->count() }} Total Jurnal
                            </span>
                            <span class="px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-800 font-bold border border-emerald-200">
                                {{ $sApproved }} Terverifikasi
                            </span>
                        </div>

                        <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-bold border border-slate-200 transition-colors">
                            <span>Riwayat</span>
                            <svg id="student-chevron-{{ $sp->id }}" class="w-4 h-4 text-slate-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Accordion Body -->
                <div id="student-journals-{{ $sp->id }}" class="hidden border-t border-slate-100 bg-slate-50/50 p-4 sm:p-5 space-y-3">
                    @forelse($sJournals as $sj)
                        @php
                            $isPendingItem = $sj->status === 'submitted';
                        @endphp
                        <div class="bg-white p-4 rounded-xl border border-slate-200/70 shadow-2xs flex flex-col md:flex-row md:items-start justify-between gap-4 hover:border-slate-300 transition-colors">
                            <div class="space-y-2 flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="inline-flex items-center gap-1 text-xs font-bold text-slate-900 font-mono">
                                        <svg class="w-3.5 h-3.5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        {{ \Carbon\Carbon::parse($sj->date)->isoFormat('dddd, D MMMM Y') }}
                                    </span>
                                    <span class="text-[10px] text-slate-400">({{ \Carbon\Carbon::parse($sj->date)->diffForHumans() }})</span>
                                    <x-badge :variant="$sj->status" size="xs">
                                        {{ strtoupper(str_replace('_', ' ', $sj->status)) }}
                                    </x-badge>
                                </div>

                                <p class="text-xs text-slate-700 leading-relaxed font-normal">
                                    {{ $sj->activity_description }}
                                </p>

                                @if($sj->photos->isNotEmpty())
                                    <div class="flex items-center gap-2 pt-1">
                                        @foreach($sj->photos as $ph)
                                            <a href="{{ asset('storage/' . $ph->photo_path) }}" target="_blank" class="w-14 h-14 rounded-lg overflow-hidden border border-slate-200 shadow-2xs hover:scale-105 transition-transform block shrink-0">
                                                <img src="{{ asset('storage/' . $ph->photo_path) }}" class="w-full h-full object-cover" alt="Foto Kerja">
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="shrink-0 flex items-center gap-2 self-end md:self-center">
                                <button type="button" onclick="openJournalModal('{{ $sj->id }}')" 
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ $isPendingItem ? 'bg-cyan-600 hover:bg-cyan-700 text-white shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                                    @if($isPendingItem)
                                        <span>Validasi Lapangan</span>
                                    @else
                                        <span>Lihat Detail</span>
                                    @endif
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-xs text-slate-400">
                            Siswa ini belum memiliki riwayat jurnal yang dikirimkan.
                        </div>
                    @endforelse
                </div>
            </div>
        @empty
            <x-empty-state 
                title="Belum Ada Siswa Magang" 
                description="Belum ada siswa yang ditempatkan magang pada unit industri Anda."
            />
        @endforelse
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 2: ANTREAN BUTUH VALIDASI MENTOR (ACTION QUEUE)                      -->
    <!-- ========================================================================= -->
    <div id="view-pending" class="hidden space-y-4">
        @if($allPendingJournals->isEmpty())
            <x-card class="text-center py-12">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h4 class="text-sm font-extrabold text-slate-900 tracking-tight">Semua Jurnal Magang Telah Divalidasi</h4>
                <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                    Tidak ada jurnal siswa magang yang sedang menunggu persetujuan mentor industri saat ini.
                </p>
            </x-card>
        @else
            <div class="space-y-4">
                @foreach($allPendingJournals as $pj)
                    <x-card class="p-5 sm:p-6 border-l-4 border-l-cyan-500">
                        <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-5">
                            <div class="space-y-3 flex-1 min-w-0">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-2xl bg-cyan-100 text-cyan-800 font-extrabold text-xs flex items-center justify-center shrink-0">
                                        {{ substr($pj->studentPkl->student->user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-sm font-extrabold text-slate-900">{{ $pj->studentPkl->student->user->name }}</h4>
                                            <span class="text-xs text-slate-400 font-mono">{{ $pj->studentPkl->student->class->name ?? '' }}</span>
                                        </div>
                                        <p class="text-xs text-slate-500 font-medium">
                                            {{ \Carbon\Carbon::parse($pj->date)->isoFormat('dddd, D MMMM Y') }}
                                        </p>
                                    </div>
                                </div>

                                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70 text-xs sm:text-sm text-slate-800 leading-relaxed font-medium">
                                    {{ $pj->activity_description }}
                                </div>

                                @if($pj->photos->isNotEmpty())
                                    <div class="flex items-center gap-2 overflow-x-auto pt-1">
                                        @foreach($pj->photos as $ph)
                                            <a href="{{ asset('storage/' . $ph->photo_path) }}" target="_blank" class="w-16 h-16 rounded-xl overflow-hidden border border-slate-200 shadow-2xs hover:scale-105 transition-transform block shrink-0">
                                                <img src="{{ asset('storage/' . $ph->photo_path) }}" class="w-full h-full object-cover" alt="Foto Dokumentasi">
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <!-- Inline Decision Form -->
                            <div class="lg:w-80 shrink-0 bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-3">
                                <form action="{{ route('dudi.journals.process', $pj->id) }}" method="POST" class="space-y-3">
                                    @csrf
                                    <label class="block text-xs font-bold text-slate-900">Catatan Rekomendasi Mentor</label>
                                    <textarea name="notes" rows="2" placeholder="Catatan teknis pekerjaan atau arahan revisi..." 
                                              class="w-full text-xs p-2.5 rounded-xl bg-white border border-slate-200 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 font-medium text-slate-800"></textarea>
                                    
                                    <div class="flex items-center gap-2">
                                        <button type="submit" name="action" value="revision" onclick="return confirm('Minta siswa merevisi jurnal ini?')" 
                                                class="flex-1 py-2 rounded-xl bg-white hover:bg-rose-50 text-rose-600 font-bold text-xs border border-rose-200 transition-colors">
                                            Minta Revisi
                                        </button>
                                        <button type="submit" name="action" value="approve" 
                                                class="flex-1 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs shadow-xs transition-colors">
                                            Validasi & Teruskan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </x-card>
                @endforeach
            </div>
        @endif
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 3: TABEL LENGKAP (PRECISION TABLE)                                   -->
    <!-- ========================================================================= -->
    <div id="view-table" class="hidden space-y-4">
        <x-card>
            <form method="GET" action="{{ route('dudi.journals') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                <div class="sm:col-span-5 relative">
                    <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari aktivitas atau nama siswa..." 
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs sm:text-sm font-medium text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-cyan-500/20">
                </div>

                <div class="sm:col-span-4">
                    <select name="student_pkl_id" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs sm:text-sm font-medium text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-cyan-500/20">
                        <option value="">-- Seluruh Siswa Magang --</option>
                        @foreach($studentPkls as $sp)
                            <option value="{{ $sp->id }}" {{ request('student_pkl_id') == $sp->id ? 'selected' : '' }}>
                                {{ $sp->student->user->name }} ({{ $sp->student->class->name ?? '' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-3 flex items-center gap-2">
                    <input type="date" name="date" value="{{ request('date') }}" 
                           class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-mono text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-cyan-500/20">
                    <x-button type="submit" variant="primary" size="md">
                        Filter
                    </x-button>
                </div>
            </form>
        </x-card>

        <x-card noPadding>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse table-fixed min-w-[760px]">
                    <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                        <tr>
                            <th class="py-3.5 px-4 w-[240px]">Siswa Magang</th>
                            <th class="py-3.5 px-4 w-[160px]">Tanggal Jurnal</th>
                            <th class="py-3.5 px-4 min-w-[240px]">Ringkasan Aktivitas Lapangan</th>
                            <th class="py-3.5 px-4 w-[130px] text-center">Status Verifikasi</th>
                            <th class="py-3.5 px-4 w-[130px] text-center">Aksi Mentor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                        @forelse($journals as $j)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4">
                                    <div class="flex items-center space-x-2.5">
                                        <div class="w-8 h-8 rounded-lg bg-cyan-50 border border-cyan-100 text-cyan-800 font-extrabold text-xs flex items-center justify-center shrink-0">
                                            {{ substr($j->studentPkl->student->user->name, 0, 1) }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <span class="font-bold text-slate-900 block truncate text-xs">{{ $j->studentPkl->student->user->name }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono block truncate">{{ $j->studentPkl->student->class->name ?? '' }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="font-bold text-slate-900 block text-xs">{{ \Carbon\Carbon::parse($j->date)->isoFormat('dddd, D MMM Y') }}</span>
                                    <span class="text-[10px] text-slate-400 block">{{ \Carbon\Carbon::parse($j->date)->diffForHumans() }}</span>
                                </td>

                                <td class="py-3 px-4">
                                    <p class="text-xs text-slate-800 line-clamp-2 leading-relaxed">
                                        {{ $j->activity_description }}
                                    </p>
                                    @if($j->photos->isNotEmpty())
                                        <span class="inline-flex items-center gap-1 text-[10px] text-cyan-700 font-bold mt-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            {{ $j->photos->count() }} Foto
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <x-badge :variant="$j->status" size="xs">
                                        {{ strtoupper(str_replace('_', ' ', $j->status)) }}
                                    </x-badge>
                                </td>

                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <button type="button" onclick="openJournalModal('{{ $j->id }}')" 
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-bold transition-all {{ $j->status === 'submitted' ? 'bg-cyan-600 hover:bg-cyan-700 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                                        {{ $j->status === 'submitted' ? 'Validasi' : 'Detail' }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12">
                                    <x-empty-state 
                                        title="Tidak Ada Jurnal" 
                                        description="Tidak ada data jurnal magang yang ditemukan."
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($journals->hasPages())
                <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                    {{ $journals->links() }}
                </div>
            @endif
        </x-card>
    </div>

</div>

<!-- Universal Journal Detail Modal (DU/DI) -->
@foreach($journals as $j)
    <div id="modal-journal-{{ $j->id }}" onclick="if(event.target === this) closeJournalModal('{{ $j->id }}')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-2xl rounded-3xl p-6 sm:p-7 shadow-2xl border border-slate-100 max-h-[92vh] overflow-y-auto animate-in zoom-in-95 duration-150 text-left">
            <div class="flex items-start justify-between pb-4 mb-4 border-b border-slate-100">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-cyan-50 border border-cyan-100 text-cyan-800 font-extrabold text-xs flex items-center justify-center shrink-0">
                        {{ substr($j->studentPkl->student->user->name, 0, 1) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-extrabold text-slate-900 tracking-tight">{{ $j->studentPkl->student->user->name }}</h3>
                            <x-badge :variant="$j->status" size="xs">
                                {{ strtoupper(str_replace('_', ' ', $j->status)) }}
                            </x-badge>
                        </div>
                        <p class="text-xs text-slate-500 font-medium">
                            {{ $j->studentPkl->student->class->name ?? 'Kelas' }} &bull; {{ \Carbon\Carbon::parse($j->date)->isoFormat('dddd, D MMMM Y') }}
                        </p>
                    </div>
                </div>
                <button type="button" onclick="closeJournalModal('{{ $j->id }}')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Uraian Pekerjaan Teknis di DU/DI:</label>
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/70 text-xs sm:text-sm text-slate-800 leading-relaxed font-medium">
                        {{ $j->activity_description }}
                    </div>
                </div>

                @if($j->photos->isNotEmpty())
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Dokumentasi Kerja Lapangan ({{ $j->photos->count() }}):</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach($j->photos as $ph)
                                <a href="{{ asset('storage/' . $ph->photo_path) }}" target="_blank" class="group relative rounded-2xl overflow-hidden border border-slate-200 shadow-2xs block aspect-video">
                                    <img src="{{ asset('storage/' . $ph->photo_path) }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" alt="Dokumentasi">
                                    <div class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold">
                                        Perbesar
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($j->approvals->isNotEmpty())
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/70 space-y-2">
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider">Riwayat Verifikasi Bertingkat:</label>
                        <div class="space-y-2 text-xs">
                            @foreach($j->approvals as $ap)
                                <div class="flex items-start gap-2.5 p-2.5 rounded-xl bg-white border border-slate-200/60 shadow-2xs">
                                    <span class="w-2 h-2 rounded-full {{ $ap->status === 'approved' ? 'bg-emerald-500' : 'bg-rose-500' }} mt-1.5 shrink-0"></span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-bold text-slate-900">{{ $ap->approver->name }}</span>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md {{ $ap->status === 'approved' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                                {{ strtoupper($ap->status) }} ({{ strtoupper(str_replace('-', ' ', $ap->role_level)) }})
                                            </span>
                                        </div>
                                        @if($ap->notes)
                                            <p class="text-slate-600 mt-1 italic">"{{ $ap->notes }}"</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($j->status === 'submitted')
                    <form action="{{ route('dudi.journals.process', $j->id) }}" method="POST" class="pt-2 border-t border-slate-100 space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-900 mb-1.5">Catatan Evaluasi / Rekomendasi Mentor Industri:</label>
                            <textarea name="notes" rows="2" placeholder="Tuliskan catatan teknis pekerjaan atau alasan revisi..." 
                                      class="w-full text-xs sm:text-sm p-3 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-cyan-500/20 font-medium text-slate-800"></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-2.5 pt-1">
                            <button type="submit" name="action" value="revision" onclick="return confirm('Minta siswa merevisi jurnal ini?')" 
                                    class="px-4 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs border border-rose-200 transition-colors">
                                Minta Revisi
                            </button>
                            <button type="submit" name="action" value="approve" 
                                    class="px-5 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs shadow-md shadow-cyan-600/20 transition-all">
                                Validasi & Teruskan ke Sekolah
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endforeach

<script>
    function switchJournalView(viewName) {
        document.getElementById('view-students').classList.add('hidden');
        document.getElementById('view-pending').classList.add('hidden');
        document.getElementById('view-table').classList.add('hidden');

        document.querySelectorAll('.view-tab-btn').forEach(btn => {
            btn.classList.remove('bg-cyan-600', 'text-white', 'shadow-sm');
            btn.classList.add('bg-white', 'text-slate-700');
        });

        document.getElementById('view-' + viewName).classList.remove('hidden');

        const activeBtn = document.getElementById('tab-btn-' + viewName);
        if (activeBtn) {
            activeBtn.classList.add('bg-cyan-600', 'text-white', 'shadow-sm');
            activeBtn.classList.remove('bg-white', 'text-slate-700');
        }
    }

    function toggleStudentJournals(spId) {
        const content = document.getElementById('student-journals-' + spId);
        const chevron = document.getElementById('student-chevron-' + spId);
        if (!content) return;

        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
        } else {
            content.classList.add('hidden');
            if (chevron) chevron.classList.remove('rotate-180');
        }
    }

    function openJournalModal(id) {
        const modal = document.getElementById('modal-journal-' + id);
        if (modal) modal.classList.remove('hidden');
    }

    function closeJournalModal(id) {
        const modal = document.getElementById('modal-journal-' + id);
        if (modal) modal.classList.add('hidden');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('[id^="modal-journal-"]').forEach(m => m.classList.add('hidden'));
        }
    });
</script>
@endsection
