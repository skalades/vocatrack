@extends('layouts.app')

@section('page_title', 'Validasi Absensi Harian Siswa Magang')
@section('page_subtitle', 'Verifikasi bukti foto selfie dan status geofencing radius kantor')

@section('content')
<div class="space-y-6">

    <x-page-header 
        title="Validasi Presensi Harian Siswa Magang" 
        subtitle="Pantau foto selfie saat check-in, validitas radius geofencing kantor, dan jam kehadiran harian."
    />

    <!-- Filters Card -->
    <x-card>
        <form method="GET" class="flex flex-col sm:flex-row sm:items-end gap-3">
            <div class="flex-1 max-w-xs">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilih Tanggal Presensi</label>
                <input type="date" name="date" value="{{ request('date', \Carbon\Carbon::today()->toDateString()) }}" 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-800">
            </div>
            <div class="flex items-center gap-2">
                <x-button type="submit" variant="primary" size="md">
                    Filter Tanggal
                </x-button>
                <a href="{{ route('dudi.attendances') }}" class="px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 font-bold text-xs transition-colors">
                    Hari Ini
                </a>
            </div>
        </form>
    </x-card>

    <!-- Table Card -->
    <x-card noPadding>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6">Nama Siswa</th>
                        <th class="py-3.5 px-4">Tanggal</th>
                        <th class="py-3.5 px-4">Selfie Check-In</th>
                        <th class="py-3.5 px-4">Jam Masuk / Pulang</th>
                        <th class="py-3.5 px-4">Status Geofencing</th>
                        <th class="py-3.5 px-4">Status Kehadiran</th>
                        <th class="py-3.5 px-6">Catatan Capaian</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($attendances as $a)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3.5 px-6">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-700 font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ substr($a->studentPkl->student->user->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <span class="font-bold text-slate-900 block truncate">{{ $a->studentPkl->student->user->name }}</span>
                                        <span class="text-[10px] text-slate-400 font-medium">{{ $a->studentPkl->student->class->name }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-800">
                                {{ \Carbon\Carbon::parse($a->date)->format('d/m/Y') }}
                            </td>
                            <td class="py-3.5 px-4">
                                @if($a->checkin_photo_path)
                                    <a href="{{ asset('storage/' . $a->checkin_photo_path) }}" target="_blank" class="block group">
                                        <img src="{{ asset('storage/' . $a->checkin_photo_path) }}" class="w-11 h-11 rounded-xl object-cover border border-slate-200 shadow-2xs group-hover:scale-105 transition-transform" alt="Selfie Siswa">
                                    </a>
                                @else
                                    <span class="text-[10px] text-slate-400 italic">Tanpa Foto</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-700">
                                <div><strong class="text-slate-900">Masuk:</strong> {{ $a->checkin_time ? substr($a->checkin_time, 0, 5) . ' WIB' : '-' }}</div>
                                <div><strong class="text-slate-900">Pulang:</strong> {{ $a->checkout_time ? substr($a->checkout_time, 0, 5) . ' WIB' : '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($a->is_within_radius === true)
                                    <x-badge variant="success" size="xs">
                                        Dalam Radius
                                    </x-badge>
                                @elseif($a->is_within_radius === false)
                                    <x-badge variant="danger" size="xs">
                                        Luar Radius
                                    </x-badge>
                                    @if($a->reason)
                                        <span class="block text-[10px] text-slate-500 italic mt-0.5 max-w-[140px] truncate" title="{{ $a->reason }}">
                                            "{{ $a->reason }}"
                                        </span>
                                    @endif
                                @else
                                    <span class="text-[10px] text-slate-400 font-mono">-</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <x-badge :variant="$a->status" size="xs">
                                    {{ strtoupper(str_replace('_', ' ', $a->status)) }}
                                </x-badge>
                            </td>
                            <td class="py-3.5 px-6 text-[11px] text-slate-600 max-w-xs truncate font-medium">
                                {{ $a->checkout_notes ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12">
                                <x-empty-state 
                                    title="Tidak Ada Catatan Presensi" 
                                    description="Tidak ditemukan rekaman presensi siswa magang pada tanggal yang dipilih."
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

</div>
@endsection
