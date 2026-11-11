@extends('layouts.app')

@section('title', 'Dashboard Pembimbing Industri DU/DI — VokaTrack')
@section('hide_header', true)

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <h1 class="sr-only">Dashboard Pembimbing Industri DU/DI</h1>

    <!-- Hero Welcome Banner -->
    <div class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-7 text-white shadow-xl shadow-slate-950/20 border border-slate-800">
        <div class="absolute -right-10 -bottom-10 w-60 h-60 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>

        <div class="relative space-y-4">
            <!-- Top Banner Meta: Badge + Company + Live Date & Time -->
            <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-white/10">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-500/20 text-indigo-300 border border-indigo-400/30 uppercase tracking-wider">
                        PEMBIMBING INDUSTRI DU/DI
                    </span>
                    <span class="text-xs text-slate-500">&bull;</span>
                    <span class="text-xs font-semibold text-slate-300 truncate">{{ $company->name }}</span>
                </div>

                <!-- Live Date & Clock Widget -->
                <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 text-xs text-slate-200 shadow-inner">
                    <div class="flex items-center gap-1.5 font-semibold text-slate-200">
                        <svg class="w-3.5 h-3.5 text-indigo-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span id="banner-live-date">{{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}</span>
                    </div>
                    <span class="text-white/30">&bull;</span>
                    <div class="flex items-center gap-1.5 font-mono font-bold text-amber-300">
                        <svg class="w-3.5 h-3.5 text-amber-300 shrink-0 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span id="banner-live-clock">{{ \Carbon\Carbon::now()->format('H:i:s') }} WIB</span>
                    </div>
                </div>
            </div>

            <!-- Main Greeting & Action Buttons -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
                <div class="space-y-1.5 min-w-0">
                    <h2 class="text-xl sm:text-2xl font-black tracking-tight text-white">
                        Selamat Datang, {{ auth()->user()->name }}
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-300 font-medium max-w-2xl leading-relaxed">
                        Pantau kehadiran harian, validasi foto selfie check-in GPS, dan tinjau log kegiatan siswa magang di {{ $company->name }}.
                    </p>
                </div>

                <!-- Header Quick Actions -->
                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    <x-button type="button" variant="primary" size="sm" onclick="window.location.href='{{ route('dudi.attendances') }}'">
                        <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Validasi Presensi</span>
                    </x-button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <x-stat-card 
            title="Siswa Magang" 
            :value="$studentPkls->count()" 
            subtitle="Ditempatkan di perusahaan Anda"
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
            title="Dinas Luar & WFH" 
            :value="$pendingRequests" 
            subtitle="Menunggu persetujuan DU/DI"
            :color="$pendingRequests > 0 ? 'teal' : 'indigo'"
            :badge="$pendingRequests > 0 ? 'Perlu Diproses' : null"
            :badgeVariant="$pendingRequests > 0 ? 'teal' : 'neutral'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Review Jurnal Tingkat 1" 
            :value="$pendingJournals" 
            subtitle="Menunggu review mentor"
            :color="$pendingJournals > 0 ? 'amber' : 'indigo'"
            :badge="$pendingJournals > 0 ? 'Verifikasi Mentor' : null"
            :badgeVariant="$pendingJournals > 0 ? 'warning' : 'neutral'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

    </div>

    <!-- Company Geofencing Info Banner -->
    <div class="p-4 rounded-2xl bg-indigo-50/70 border border-indigo-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div>
                <span class="text-xs font-bold text-indigo-950 block">Aturan Geofencing Presensi GPS Perusahaan</span>
                <p class="text-xs text-indigo-700 mt-0.5 font-medium">
                    Status GPS: <strong>{{ $company->gps_enabled ? 'Aktif (Radius ' . $company->radius_meters . ' Meter)' : 'Bebas Radius' }}</strong> &bull;
                    Jadwal Kerja: <strong>{{ $company->work_days === '6_days' ? '6 Hari (Senin - Sabtu)' : '5 Hari (Senin - Jumat)' }}</strong>
                </p>
            </div>
        </div>
        @if($company->gps_enabled)
            <span class="px-3 py-1.5 rounded-xl text-xs font-mono font-bold bg-white text-indigo-700 border border-indigo-200/80 shrink-0 shadow-2xs">
                {{ number_format($company->latitude, 4) }}, {{ number_format($company->longitude, 4) }}
            </span>
        @endif
    </div>

    <!-- Interns List -->
    <x-card 
        title="Daftar Siswa Magang Aktif" 
        :subtitle="$studentPkls->count() . ' Siswa Menjalankan Praktik Industri di Perusahaan Anda'"
    >
        @if($studentPkls->isEmpty())
            <x-empty-state 
                title="Belum Ada Siswa Magang" 
                description="Belum ada siswa yang diplot ke perusahaan Anda saat ini."
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
                                <span class="text-[10px] text-emerald-700 font-bold block mt-0.5">STATUS: AKTIF MAGANG</span>
                            </div>
                        </div>
                        <div class="shrink-0">
                            <a href="{{ route('dudi.attendances') }}" class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 font-bold text-xs shadow-2xs block transition-colors">
                                Presensi
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>

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
