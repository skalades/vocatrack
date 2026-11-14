@extends('layouts.app')

@section('page_title', 'DevOps, Antrean & Kesehatan Sistem')
@section('page_subtitle', 'Pemantauan antrean tugas asinkron, log error aplikasi, pembersihan cache, dan status server')

@section('content')
<div class="w-full space-y-6 sm:space-y-8 animate-in fade-in duration-300">

    <!-- Enterprise Hero Header -->
    <x-page-header 
        title="DevOps & Diagnostik Server Platform" 
        subtitle="Pantau antrean pekerjaan latar belakang (queue), inspeksi log error aplikasi langsung dari browser, dan jalankan pembersihan cache performa."
        badge="DEVOPS & ENGINE STATUS"
    >
        <form action="{{ route('superadmin.system.clear-cache') }}" method="POST" class="inline-block" onsubmit="return confirm('Bersihkan seluruh cache konfigurasi, view, dan aplikasi?')">
            @csrf
            <input type="hidden" name="type" value="all">
            <x-button type="submit" variant="primary" size="md" class="shadow-lg shadow-indigo-600/25">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span>Bersihkan Seluruh Cache</span>
            </x-button>
        </form>
    </x-page-header>

    <!-- Top KPI Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <x-stat-card 
            title="Pekerjaan Gagal (Failed Jobs)" 
            :value="$failedJobsCount" 
            subtitle="Job antrean yang mengalami kegagalan"
            :color="$failedJobsCount > 0 ? 'rose' : 'emerald'"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 stroke-[1.8] {{ $failedJobsCount > 0 ? 'text-rose-600' : 'text-emerald-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Antrean Tertunda (Queue Backlog)" 
            :value="$pendingJobsCount" 
            subtitle="Job yang menunggu dieksekusi"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-indigo-600 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Penyimpanan Media Platform" 
            :value="$storageStats['total_mb'] . ' MB'" 
            :subtitle="'Setara dengan ~' . $storageStats['total_gb'] . ' GB media cloud'"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-blue-600 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Engine Versi" 
            :value="'Laravel ' . $systemHealth['laravel_version']" 
            :subtitle="'PHP ' . $systemHealth['php_version'] . ' • ' . strtoupper($systemHealth['app_env'])"
            color="teal"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-teal-600 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Quick Cache & Maintenance Actions Bar -->
    <x-card>
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Alat Pemeliharaan Cache Cepat</h3>
                <p class="text-xs text-slate-500">Jalankan instruksi pembersihan memori sementara tanpa perlu mengakses command line server.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <form action="{{ route('superadmin.system.clear-cache') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="type" value="config">
                    <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors">
                        Clear Config
                    </button>
                </form>

                <form action="{{ route('superadmin.system.clear-cache') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="type" value="route">
                    <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors">
                        Clear Routes
                    </button>
                </form>

                <form action="{{ route('superadmin.system.clear-cache') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="type" value="view">
                    <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors">
                        Clear Views
                    </button>
                </form>

                <form action="{{ route('superadmin.system.clear-cache') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="type" value="cache">
                    <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors">
                        Clear App Cache
                    </button>
                </form>
            </div>
        </div>
    </x-card>

    <!-- Failed Jobs Section -->
    <x-card noPadding>
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">Daftar Pekerjaan Gagal (Failed Jobs)</h3>
                <p class="text-xs text-slate-500 font-medium">Pekerjaan antrean asinkron (misal: pengiriman email, generate dokumen) yang mengalami pengecualian error.</p>
            </div>

            @if($failedJobsCount > 0)
                <form action="{{ route('superadmin.system.flush-jobs') }}" method="POST" onsubmit="return confirm('Kosongkan dan hapus seluruh antrean failed jobs?')">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 rounded-xl bg-rose-50 text-rose-700 hover:bg-rose-100 font-bold text-xs transition-colors border border-rose-200">
                        Kosongkan Semua Job Gagal
                    </button>
                </form>
            @endif
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs min-w-[850px]">
                <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3 px-6">ID & Koneksi</th>
                        <th class="py-3 px-4">Antrean (Queue)</th>
                        <th class="py-3 px-4">Pesan Eksepsi</th>
                        <th class="py-3 px-4">Waktu Gagal</th>
                        <th class="py-3 px-6 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($failedJobs as $job)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-6 font-mono font-bold text-slate-900">
                                #{{ $job->id }}
                                <span class="text-[10px] text-slate-400 block font-normal">{{ $job->connection }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded font-mono font-bold text-[10px] bg-indigo-50 text-indigo-700">
                                    {{ $job->queue }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-rose-700 font-mono text-[11px] max-w-md truncate">
                                {{ Str::limit($job->exception, 90) }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                {{ $job->failed_at }}
                            </td>
                            <td class="py-3.5 px-6 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <form action="{{ route('superadmin.system.retry-job', $job->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-bold text-[11px] transition-colors">
                                            Retry
                                        </button>
                                    </form>

                                    <form action="{{ route('superadmin.system.delete-job', $job->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus job gagal ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 font-bold text-[11px] transition-colors">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center space-y-1">
                                    <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span class="text-xs font-bold text-slate-700">Semua Antrean Bersih & Normal</span>
                                    <span class="text-[11px] text-slate-400">Tidak ada pekerjaan antrean yang gagal.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <!-- Live Application Error Log Viewer -->
    <x-card noPadding>
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">Live Application Error Log (storage/logs/laravel.log)</h3>
                <p class="text-xs text-slate-500 font-medium">Melihat rekaman error terkini aplikasi secara langsung dari antarmuka web.</p>
            </div>

            <form method="GET" action="{{ route('superadmin.system-health') }}" class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-500">Filter Level:</span>
                <select name="log_level" onchange="this.form.submit()" class="text-xs font-bold px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
                    <option value="all" {{ request('log_level') === 'all' || !request('log_level') ? 'selected' : '' }}>Semua Level</option>
                    <option value="ERROR" {{ request('log_level') === 'ERROR' ? 'selected' : '' }}>Hanya ERROR</option>
                    <option value="WARNING" {{ request('log_level') === 'WARNING' ? 'selected' : '' }}>Hanya WARNING</option>
                    <option value="CRITICAL" {{ request('log_level') === 'CRITICAL' ? 'selected' : '' }}>Hanya CRITICAL</option>
                </select>
            </form>
        </div>

        <div class="p-4 bg-slate-950 text-slate-300 font-mono text-xs max-h-[480px] overflow-y-auto custom-scrollbar divide-y divide-slate-800/60">
            @forelse($logLines as $entry)
                @php
                    $badgeColor = match($entry['level']) {
                        'ERROR', 'CRITICAL' => 'text-rose-400 bg-rose-950/60 border-rose-800/80',
                        'WARNING' => 'text-amber-400 bg-amber-950/60 border-amber-800/80',
                        default => 'text-emerald-400 bg-emerald-950/60 border-emerald-800/80',
                    };
                @endphp
                <div class="py-2.5 space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] text-slate-500">{{ $entry['date'] }}</span>
                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold border {{ $badgeColor }}">
                            {{ $entry['level'] }}
                        </span>
                    </div>
                    <p class="text-slate-200 text-[11px] leading-relaxed break-all select-all">
                        {{ $entry['message'] }}
                    </p>
                </div>
            @empty
                <div class="py-8 text-center text-slate-500">
                    <p>Tidak ditemukan rekaman log pada berkas laravel.log atau log kosong.</p>
                </div>
            @endforelse
        </div>
    </x-card>

</div>
@endsection
