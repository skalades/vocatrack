@extends('layouts.app')

@section('title', 'Dashboard Pembimbing Industri DU/DI — VokaTrack')
@section('hide_header', true)

@section('content')
<div class="w-full space-y-6">

    <h1 class="sr-only">Dashboard Pembimbing Industri DU/DI</h1>

    @php
        $internCount = $studentPkls->count();
        $presentCount = $todayAttendances->count();
        $presencePct = $internCount > 0 ? min(100, (int) round($presentCount / $internCount * 100)) : 0;
        $pendingTotal = $pendingRequests + $pendingJournals;
        $ringCirc = round(2 * M_PI * 52, 2);
    @endphp

    {{-- ===================== HERO (tema forest, senada portal siswa & guru) ===================== --}}
    <section data-reveal data-spotlight class="vt-hero relative overflow-hidden bg-[#0B2B25] rounded-[28px] text-white shadow-xl shadow-[#0B2B25]/20">
        <div class="absolute -right-24 -top-24 w-96 h-96 rounded-full border border-white/[0.07] pointer-events-none"></div>
        <div class="absolute -right-6 -top-6 w-60 h-60 rounded-full border border-white/[0.07] pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-24 w-72 h-72 rounded-full bg-[#C6F26B]/10 blur-3xl pointer-events-none"></div>

        <div class="relative grid grid-cols-1 xl:grid-cols-12 gap-6 p-6 sm:p-7">
            <div class="xl:col-span-8 space-y-5 min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-[#C6F26B] text-[#0B2B25] uppercase tracking-wider">Pembimbing Industri</span>
                    <span class="text-xs font-semibold text-[#A9C2B9] truncate">{{ $company->name }}</span>
                </div>

                <div class="space-y-1.5">
                    <h2 class="font-display text-2xl sm:text-[28px] font-bold tracking-tight leading-tight">Halo, {{ auth()->user()->name }}</h2>
                    <p class="text-sm text-[#A9C2B9] font-medium max-w-2xl leading-relaxed">
                        Anda mendampingi <strong class="text-white">{{ $internCount }} siswa magang</strong> di {{ $company->name }}.
                        @if($pendingTotal > 0)
                            Ada <strong class="text-[#C6F26B]">{{ $pendingTotal }} hal</strong> yang menunggu persetujuan Anda.
                        @else
                            Semua pengajuan &amp; jurnal sudah ditinjau — kerja bagus.
                        @endif
                    </p>
                </div>

                <div class="rounded-2xl bg-white/[0.06] border border-white/10 p-4 space-y-2.5">
                    <div class="flex flex-wrap items-baseline justify-between gap-2 text-xs">
                        <span class="font-bold text-white">Presensi hari ini</span>
                        <span class="text-[#A9C2B9] font-medium" id="banner-live-date">{{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}</span>
                    </div>
                    <div class="h-2 rounded-full bg-white/10 overflow-hidden">
                        <div data-grow-x class="h-full rounded-full bg-[#C6F26B]" style="width: {{ $presencePct }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-[#A9C2B9] font-semibold">
                        <span><span data-counter class="font-space text-white">{{ $presentCount }}</span> dari {{ $internCount }} siswa sudah presensi</span>
                        <span class="font-space text-[#C6F26B]" id="banner-live-clock">{{ \Carbon\Carbon::now()->format('H:i:s') }} WIB</span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <a href="{{ route('dudi.attendances') }}" data-ripple class="group inline-flex items-center gap-2 pl-4 pr-1.5 py-1.5 rounded-full bg-[#C6F26B] hover:bg-[#b5e855] text-[#0B2B25] text-xs font-extrabold shadow-md shadow-black/20 transition-all hover:-translate-y-0.5">
                        <span>Validasi Presensi</span>
                        <span class="w-7 h-7 rounded-full bg-[#0B2B25] text-[#C6F26B] flex items-center justify-center group-hover:translate-x-0.5 transition-transform">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </span>
                    </a>
                    <a href="{{ route('dudi.journals') }}" data-ripple class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-white/[0.08] hover:bg-white/[0.14] border border-white/15 text-white text-xs font-bold transition-colors">
                        <span>Review Jurnal</span>
                        @if($pendingJournals > 0)
                            <span class="min-w-5 h-5 px-1.5 rounded-full bg-[#C6F26B] text-[#0B2B25] text-[10px] font-extrabold flex items-center justify-center">{{ $pendingJournals }}</span>
                        @endif
                    </a>
                    <a href="{{ route('dudi.requests') }}" data-ripple class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-white/[0.08] hover:bg-white/[0.14] border border-white/15 text-white text-xs font-bold transition-colors">
                        <span>Dinas Luar &amp; WFH</span>
                        @if($pendingRequests > 0)
                            <span class="min-w-5 h-5 px-1.5 rounded-full bg-[#C6F26B] text-[#0B2B25] text-[10px] font-extrabold flex items-center justify-center">{{ $pendingRequests }}</span>
                        @endif
                    </a>
                </div>
            </div>

            {{-- Ring kehadiran --}}
            <div class="xl:col-span-4 flex xl:justify-end items-center">
                <div class="w-full xl:w-auto rounded-3xl bg-white/[0.06] border border-white/10 p-5 flex items-center gap-5">
                    <div class="relative w-28 h-28 shrink-0">
                        <svg class="w-28 h-28 -rotate-90" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="52" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="10"/>
                            <circle data-ring cx="60" cy="60" r="52" fill="none" stroke="#C6F26B" stroke-width="10" stroke-linecap="round"
                                    stroke-dasharray="{{ $ringCirc }}"
                                    stroke-dashoffset="{{ round($ringCirc * (1 - $presencePct / 100), 2) }}"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="font-space text-2xl font-bold leading-none"><span data-counter>{{ $presencePct }}</span>%</span>
                            <span class="text-[10px] text-[#A9C2B9] font-semibold mt-1">hadir</span>
                        </div>
                    </div>
                    <div class="space-y-2 text-xs min-w-0">
                        <div>
                            <p class="text-[#A9C2B9] font-semibold">Siswa magang</p>
                            <p class="font-space text-lg font-bold text-white leading-tight">{{ $internCount }}</p>
                        </div>
                        <div>
                            <p class="text-[#A9C2B9] font-semibold">Menunggu Anda</p>
                            <p class="font-space text-lg font-bold leading-tight {{ $pendingTotal > 0 ? 'text-[#C6F26B]' : 'text-white' }}">{{ $pendingTotal }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

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
                    Jadwal Kerja: <strong>{{ $company->work_days_label }}</strong>
                    @if($company->custom_work_hours_summary)
                        &bull; Jam Kerja: <strong>{{ $company->custom_work_hours_summary }}</strong>
                    @endif
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

    <!-- Pengumuman Terbaru -->
    <x-recent-announcements-card
        :announcements="$recentAnnouncements ?? collect()"
        :view-all-url="route('dudi.announcements')"
        subtitle="Informasi resmi dari sekolah untuk mitra DU/DI"
        empty-message="Belum ada pengumuman aktif dari sekolah."
    />

</div>

<x-announcement-detail-modal />

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
