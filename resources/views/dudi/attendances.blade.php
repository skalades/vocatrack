@extends('layouts.app')

@section('page_title', 'Validasi Absensi Harian Siswa Magang')
@section('page_subtitle', 'Verifikasi bukti foto selfie dan status geofencing radius kantor')

@section('content')
<div class="space-y-6">

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4">
        <form method="GET" class="flex items-center space-x-3">
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Tanggal</label>
                <input type="date" name="date" value="{{ request('date', \Carbon\Carbon::today()->toDateString()) }}" class="text-xs p-2 rounded-xl bg-slate-50 border border-slate-200">
            </div>
            <div class="pt-5">
                <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs">
                    Filter Tanggal
                </button>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200/80 font-bold uppercase text-[10px]">
                    <tr>
                        <th class="py-3 px-5">Nama Siswa</th>
                        <th class="py-3 px-4">Tanggal</th>
                        <th class="py-3 px-4">Foto Selfie</th>
                        <th class="py-3 px-4">Jam Masuk & Pulang</th>
                        <th class="py-3 px-4">Geofencing Radius</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Catatan Check-Out</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($attendances as $a)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-5">
                                <span class="font-bold text-slate-900 block">{{ $a->studentPkl->student->user->name }}</span>
                                <span class="text-[10px] text-slate-400 block">{{ $a->studentPkl->student->class->name }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-semibold">
                                {{ \Carbon\Carbon::parse($a->date)->format('d/m/Y') }}
                            </td>
                            <td class="py-3.5 px-4">
                                @if($a->checkin_photo_path)
                                    <a href="{{ asset('storage/' . $a->checkin_photo_path) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $a->checkin_photo_path) }}" class="w-10 h-10 rounded-xl object-cover border border-slate-200 hover:scale-105 transition-transform" alt="Selfie">
                                    </a>
                                @else
                                    <span class="text-[10px] text-slate-400 italic">Tanpa Foto</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[11px]">
                                <div><strong class="text-slate-800">In:</strong> {{ $a->checkin_time ? substr($a->checkin_time, 0, 5) : '-' }}</div>
                                <div><strong class="text-slate-800">Out:</strong> {{ $a->checkout_time ? substr($a->checkout_time, 0, 5) : '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($a->is_within_radius === true)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        ✓ Dalam Radius
                                    </span>
                                @elseif($a->is_within_radius === false)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        ⚠ Luar Radius
                                    </span>
                                @else
                                    <span class="text-[10px] text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                @php
                                    $badge = match($a->status) {
                                        'hadir' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'terlambat' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'izin' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'sakit' => 'bg-purple-50 text-purple-700 border-purple-200',
                                        'dinas_luar' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                                        'luar_kantor' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                        default => 'bg-rose-50 text-rose-700 border-rose-200'
                                    };
                                @endphp
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $badge }}">
                                    {{ strtoupper(str_replace('_', ' ', $a->status)) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-[11px] text-slate-500 max-w-xs truncate">
                                {{ $a->checkout_notes ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-slate-400">Tidak ada catatan kehadiran pada tanggal ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $attendances->links() }}
        </div>
    </div>

</div>
@endsection
