@extends('layouts.app')

@section('page_title', 'Monitoring Absensi Siswa Binaan')
@section('page_subtitle', 'Pantau catatan jam masuk, jam pulang, dan status presensi harian siswa')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Enterprise Hero Banner -->
    <x-page-header 
        title="Monitoring Presensi Siswa Binaan" 
        subtitle="Log kehadiran real-time, waktu check-in/out harian, rekam jejak kedisiplinan, dan verifikasi kehadiran siswa binaan."
        badge="GURU PEMBIMBING"
    >
        <x-button type="button" variant="secondary" size="sm" onclick="window.location.href='{{ route('guru.requests') }}'">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>Verifikasi Izin & Sakit</span>
        </x-button>
        <x-button type="button" variant="primary" size="sm" onclick="window.location.href='{{ route('guru.journals') }}'">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Review Jurnal (Tingkat 2)</span>
        </x-button>
    </x-page-header>

    <!-- Top KPI Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card 
            title="Total Log Kehadiran" 
            :value="$stats['total'] ?? $attendances->total()" 
            subtitle="Rekaman presensi terdata"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Hadir Tepat Waktu" 
            :value="$stats['hadir'] ?? 0" 
            subtitle="Hadir tepat waktu terverifikasi"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Terlambat Masuk" 
            :value="$stats['late'] ?? 0" 
            subtitle="Perlu pembinaan disiplin"
            :color="($stats['late'] ?? 0) > 0 ? 'amber' : 'indigo'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Izin, Sakit & Dinas" 
            :value="$stats['leave'] ?? 0" 
            subtitle="Permohonan & penugasan luar"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Filter Card -->
    <x-card noPadding class="p-4 sm:p-5 bg-white border border-slate-200/80">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3.5 items-end">
            <div class="sm:col-span-5">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilih Tanggal Presensi</label>
                <input type="date" name="date" value="{{ request('date') }}" 
                       class="w-full text-xs font-semibold px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-slate-800">
            </div>
            <div class="sm:col-span-4">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Filter Status Kehadiran</label>
                <select name="status" 
                        class="w-full text-xs font-semibold px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-slate-800">
                    <option value="">-- Semua Status Kehadiran --</option>
                    <option value="hadir" {{ request('status') === 'hadir' ? 'selected' : '' }}>Hadir Tepat Waktu</option>
                    <option value="terlambat" {{ request('status') === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                    <option value="izin" {{ request('status') === 'izin' ? 'selected' : '' }}>Izin Resmi</option>
                    <option value="sakit" {{ request('status') === 'sakit' ? 'selected' : '' }}>Sakit</option>
                    <option value="dinas_luar" {{ request('status') === 'dinas_luar' ? 'selected' : '' }}>Dinas Luar</option>
                    <option value="luar_kantor" {{ request('status') === 'luar_kantor' ? 'selected' : '' }}>WFH / Luar Kantor</option>
                    <option value="alfa" {{ request('status') === 'alfa' ? 'selected' : '' }}>Alfa (Tanpa Keterangan)</option>
                </select>
            </div>
            <div class="sm:col-span-3 flex items-center gap-2">
                <button type="submit" class="flex-1 py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-xs shadow-indigo-600/20 transition-all cursor-pointer">
                    Terapkan Filter
                </button>
                @if(request()->filled('date') || request()->filled('status'))
                    <a href="{{ route('guru.monitoring') }}" class="px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 font-bold text-xs transition-colors" title="Reset filter">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Table Card -->
    <x-card noPadding>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-5">Identitas Siswa</th>
                        <th class="py-3.5 px-4">Tanggal</th>
                        <th class="py-3.5 px-3 text-center">Swafoto</th>
                        <th class="py-3.5 px-4">Jam Presensi</th>
                        <th class="py-3.5 px-4">Mitra DU/DI</th>
                        <th class="py-3.5 px-4">Lokasi & GPS</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-5">Catatan Capaian</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($attendances as $a)
                        @php
                            $detailPayload = [
                                'studentName' => $a->studentPkl->student->user->name ?? 'Siswa',
                                'studentNisn' => $a->studentPkl->student->nisn ?? '-',
                                'companyName' => $a->studentPkl->company->name ?? '-',
                                'date' => \Carbon\Carbon::parse($a->date)->format('d/m/Y'),
                                'checkinTime' => $a->checkin_time ? substr($a->checkin_time, 0, 5) : null,
                                'checkoutTime' => $a->checkout_time ? substr($a->checkout_time, 0, 5) : null,
                                'status' => $a->status,
                                'photoUrl' => $a->checkin_photo_path ? asset('storage/' . $a->checkin_photo_path) : null,
                                'isWithinRadius' => $a->is_within_radius,
                                'studentLat' => $a->checkin_lat ? (float) $a->checkin_lat : null,
                                'studentLng' => $a->checkin_lng ? (float) $a->checkin_lng : null,
                                'companyLat' => $a->studentPkl->company->latitude ? (float) $a->studentPkl->company->latitude : null,
                                'companyLng' => $a->studentPkl->company->longitude ? (float) $a->studentPkl->company->longitude : null,
                                'radiusMeters' => $a->studentPkl->company->radius_meters ?? null,
                                'notes' => $a->checkout_notes ?? '',
                            ];
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3.5 px-5">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-700 font-black text-xs flex items-center justify-center shrink-0 shadow-xs shadow-indigo-600/10">
                                        {{ substr($a->studentPkl->student->user->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <span class="font-bold text-slate-900 block truncate text-sm">{{ $a->studentPkl->student->user->name }}</span>
                                        <span class="text-[11px] text-slate-400 font-mono">NISN: {{ $a->studentPkl->student->nisn }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-800 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($a->date)->format('d/m/Y') }}
                            </td>
                            <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                @if($a->checkin_photo_path)
                                    <button type="button" 
                                            onclick="openImageLightbox('{{ asset('storage/' . $a->checkin_photo_path) }}')" 
                                            class="inline-block group cursor-pointer relative" 
                                            title="Klik untuk melihat swafoto ukuran besar">
                                        <img src="{{ asset('storage/' . $a->checkin_photo_path) }}" 
                                             class="w-10 h-10 rounded-xl object-cover border border-slate-200 shadow-2xs group-hover:scale-110 group-hover:border-indigo-400 transition-all" 
                                             alt="Swafoto {{ $a->studentPkl->student->user->name }}">
                                        <span class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-indigo-600 text-white flex items-center justify-center shadow-xs">
                                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                        </span>
                                    </button>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[10px] text-slate-400 font-medium italic bg-slate-100/80 px-2 py-1 rounded-lg">
                                        <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="4"/></svg>
                                        Tanpa Foto
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-700 whitespace-nowrap">
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">In:</span>
                                        @if($a->checkin_time)
                                            <span class="font-bold text-slate-900 bg-slate-100 px-1.5 py-0.5 rounded">{{ substr($a->checkin_time, 0, 5) }}</span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Out:</span>
                                        @if($a->checkout_time)
                                            <span class="font-bold text-slate-700 bg-slate-100 px-1.5 py-0.5 rounded">{{ substr($a->checkout_time, 0, 5) }}</span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-indigo-950 block truncate max-w-[180px]" title="{{ $a->studentPkl->company->name }}">{{ $a->studentPkl->company->name }}</span>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="space-y-1">
                                    <div>
                                        @if($a->is_within_radius === true)
                                            <x-badge variant="success" size="xs">Dalam Radius</x-badge>
                                        @elseif($a->is_within_radius === false)
                                            <x-badge variant="danger" size="xs">Luar Radius</x-badge>
                                        @else
                                            <span class="text-[10px] text-slate-400 font-mono">-</span>
                                        @endif
                                    </div>
                                    @if($a->checkin_lat && $a->checkin_lng)
                                        <div>
                                            <a href="https://www.google.com/maps?q={{ $a->checkin_lat }},{{ $a->checkin_lng }}" 
                                               target="_blank" 
                                               rel="noopener noreferrer" 
                                               class="inline-flex items-center gap-1 text-[10px] font-bold text-indigo-600 hover:text-indigo-800 hover:underline" 
                                               title="Buka titik koordinat presensi siswa di Google Maps">
                                                <svg class="w-3 h-3 text-rose-500 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 010 5z"/></svg>
                                                <span>Peta GPS</span>
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <x-badge :variant="$a->status" size="xs">
                                    {{ strtoupper(str_replace('_', ' ', $a->status)) }}
                                </x-badge>
                            </td>
                            <td class="py-3.5 px-5 text-[11px] text-slate-500 max-w-[200px] truncate font-medium" title="{{ $a->checkout_notes }}">
                                {{ $a->checkout_notes ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <button type="button" 
                                        onclick='showAttendanceDetail(@json($detailPayload))'
                                        class="py-1.5 px-2.5 rounded-xl border border-slate-200 bg-white hover:bg-indigo-50 hover:border-indigo-300 text-slate-700 hover:text-indigo-700 font-bold text-xs shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5"
                                        title="Buka rincian lengkap foto swafoto, jam, dan titik lokasi presensi">
                                    <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <span>Detail</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12">
                                <x-empty-state 
                                    title="Tidak Ada Data Kehadiran" 
                                    description="Tidak ditemukan rekaman presensi siswa binaan yang sesuai dengan filter yang dipilih."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($attendances->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $attendances->links() }}
            </div>
        @endif
    </x-card>

    <!-- Modal Reusable Detail Presensi -->
    <x-attendance-detail-modal />

</div>
@endsection
