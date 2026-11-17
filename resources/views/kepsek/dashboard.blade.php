@extends('layouts.app')

@section('title', 'Dashboard Eksekutif PKL — VokaTrack')
@section('hide_header', true)

@section('content')
<div class="w-full space-y-6">

    <h1 class="sr-only">Dashboard Eksekutif PKL</h1>

    <!-- Hero Welcome Banner -->
    <div data-reveal data-spotlight class="vt-hero relative overflow-hidden bg-[#0B2B25] rounded-[28px] p-6 sm:p-7 text-white shadow-xl shadow-[#0B2B25]/20">
        <div class="absolute -right-24 -top-24 w-96 h-96 rounded-full border border-white/[0.07] pointer-events-none"></div>
        <div class="absolute -right-6 -top-6 w-60 h-60 rounded-full border border-white/[0.07] pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-24 w-72 h-72 rounded-full bg-[#C6F26B]/10 blur-3xl pointer-events-none"></div>

        <div class="relative space-y-4">
            <!-- Top Banner Meta: Badge + School + Live Date & Time -->
            <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-white/10">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-[#C6F26B] text-[#0B2B25] uppercase tracking-wider">
                        KEPALA SEKOLAH
                    </span>
                    <span class="text-xs text-white/30">&bull;</span>
                    <span class="text-xs font-semibold text-[#A9C2B9] truncate">{{ auth()->user()->school->name ?? 'SMK Terpadu' }}</span>
                </div>

                <!-- Live Date & Clock Widget -->
                <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-2xl bg-white/[0.06] border border-white/10 text-xs text-white/80">
                    <div class="flex items-center gap-1.5 font-semibold text-slate-200">
                        <svg class="w-3.5 h-3.5 text-[#C6F26B] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span id="banner-live-date">{{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}</span>
                    </div>
                    <span class="text-white/30">&bull;</span>
                    <div class="flex items-center gap-1.5 font-mono font-bold text-[#C6F26B]">
                        <svg class="w-3.5 h-3.5 text-[#C6F26B] shrink-0 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span id="banner-live-clock">{{ \Carbon\Carbon::now()->format('H:i:s') }} WIB</span>
                    </div>
                </div>
            </div>

            <!-- Main Greeting & Action Buttons -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
                <div class="space-y-1.5 min-w-0">
                    <h2 class="font-display text-2xl sm:text-[28px] font-bold tracking-tight leading-tight text-white">
                        Halo, {{ auth()->user()->name }}
                    </h2>
                    <p class="text-xs sm:text-sm text-[#A9C2B9] font-medium max-w-2xl leading-relaxed">
                        Analisis agregat ketercapaian dan kedisiplinan siswa, kemitraan DU/DI, serta evaluasi portofolio jurnal kegiatan PKL sekolah.
                    </p>
                </div>

                <!-- Header Quick Actions -->
                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    <x-button type="button" variant="primary" size="sm" onclick="window.location.href='{{ route('kepsek.rekap') }}'">
                        <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <span>Rekapitulasi Kehadiran</span>
                    </x-button>
                </div>
            </div>
        </div>
    </div>

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

    <!-- Tren Kehadiran & Siswa Berisiko (P2 Nilai Tambah) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Tren Kehadiran 7 Hari -->
        <div class="lg:col-span-2">
            <x-card 
                title="Tren Kehadiran 7 Hari Terakhir" 
                subtitle="Dinamika presensi siswa aktif di industri sepekan terakhir"
            >
                <div class="space-y-3 pt-1">
                    @foreach($weeklyTrend as $day)
                        @php
                            $total = max(1, $day['total']);
                            $pctHadir = round(($day['hadir'] / $total) * 100);
                            $pctIzin = round(($day['izin'] / $total) * 100);
                            $pctAlfa = round(($day['alfa'] / $total) * 100);
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-xs font-semibold mb-1">
                                <span class="text-slate-700 w-24 shrink-0 font-medium">{{ $day['date'] }}</span>
                                <div class="flex items-center gap-3 text-[11px] font-mono">
                                    <span class="text-emerald-700 font-bold">{{ $day['hadir'] }} Hadir</span>
                                    @if($day['izin'] > 0)
                                        <span class="text-amber-700">{{ $day['izin'] }} Izin/Sakit</span>
                                    @endif
                                    @if($day['alfa'] > 0)
                                        <span class="text-rose-700 font-bold">{{ $day['alfa'] }} Alfa</span>
                                    @endif
                                    <span class="text-slate-400">({{ $day['total'] }} rekaman)</span>
                                </div>
                            </div>
                            <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden flex">
                                <div style="width: {{ $pctHadir }}%" class="bg-emerald-500" title="{{ $day['hadir'] }} Hadir"></div>
                                <div style="width: {{ $pctIzin }}%" class="bg-amber-400" title="{{ $day['izin'] }} Izin/Sakit"></div>
                                <div style="width: {{ $pctAlfa }}%" class="bg-rose-500" title="{{ $day['alfa'] }} Alfa"></div>
                            </div>
                        </div>
                    @endforeach

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-4 text-[11px] text-slate-500">
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Hadir/Terlambat</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span> Izin/Sakit/Dinas</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Alfa/Tanpa Keterangan</span>
                    </div>
                </div>
            </x-card>
        </div>

        <!-- Siswa Butuh Perhatian (Alfa / Resiko) -->
        <div>
            <x-card 
                title="Siswa Butuh Perhatian" 
                subtitle="Deteksi dini kedisiplinan siswa dengan catatan alfa"
            >
                @if(isset($studentsAtRisk) && $studentsAtRisk->isNotEmpty())
                    <div class="divide-y divide-slate-100">
                        @foreach($studentsAtRisk as $spkl)
                            <div class="py-2.5 first:pt-0 last:pb-0 flex items-center justify-between text-xs">
                                <div>
                                    <span class="font-bold text-slate-900 block">{{ $spkl->student->user?->name ?? 'Siswa' }}</span>
                                    <span class="text-[11px] text-slate-500 truncate block max-w-[170px]">{{ $spkl->company->name ?? 'DU/DI' }}</span>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        {{ $spkl->alfa_count }} Alfa
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6 text-slate-400">
                        <svg class="w-8 h-8 mx-auto mb-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-xs font-semibold text-slate-700">Disiplin Presensi Terjaga</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Tidak ada siswa aktif dengan catatan alfa beruntun.</p>
                    </div>
                @endif

                <div class="mt-4 pt-3 border-t border-slate-100">
                    <div class="flex items-center justify-between text-xs text-slate-600">
                        <span class="font-medium">Kunjungan Supervisi Guru:</span>
                        <span class="font-bold text-slate-900">{{ $supervisionThisMonth }} bulan ini ({{ $supervisionCount }} total)</span>
                    </div>
                </div>
            </x-card>
        </div>

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

@push('scripts')
<script>
    function updateDashboardClock() {
        const clockElem = document.getElementById('banner-live-clock');
        const dateElem = document.getElementById('banner-live-date');
        if (!clockElem) return;

        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        clockElem.textContent = `${hours}:${minutes}:${seconds} WIB`;

        if (dateElem) {
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const dayName = days[now.getDay()];
            const dateNum = now.getDate();
            const monthName = months[now.getMonth()];
            const year = now.getFullYear();
            dateElem.textContent = `${dayName}, ${dateNum} ${monthName} ${year}`;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateDashboardClock();
        setInterval(updateDashboardClock, 1000);
    });
</script>
@endpush
@endsection
