@extends('layouts.app')

@section('page_title', 'Monitoring Absensi Siswa Binaan')
@section('page_subtitle', 'Pantau catatan jam masuk, pulang, dan status presensi harian siswa')

@section('content')
<div class="space-y-6">

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Filter Tanggal</label>
                <input type="date" name="date" value="{{ request('date') }}" class="w-full text-xs p-2 rounded-xl bg-slate-50 border border-slate-200">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Filter Status</label>
                <select name="status" class="w-full text-xs p-2 rounded-xl bg-slate-50 border border-slate-200">
                    <option value="">-- Semua Status --</option>
                    <option value="hadir" {{ request('status') === 'hadir' ? 'selected' : '' }}>Hadir</option>
                    <option value="terlambat" {{ request('status') === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                    <option value="izin" {{ request('status') === 'izin' ? 'selected' : '' }}>Izin</option>
                    <option value="sakit" {{ request('status') === 'sakit' ? 'selected' : '' }}>Sakit</option>
                    <option value="dinas_luar" {{ request('status') === 'dinas_luar' ? 'selected' : '' }}>Dinas Luar</option>
                    <option value="luar_kantor" {{ request('status') === 'luar_kantor' ? 'selected' : '' }}>WFH / Luar Kantor</option>
                    <option value="alfa" {{ request('status') === 'alfa' ? 'selected' : '' }}>Alfa</option>
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs">
                    Terapkan Filter
                </button>
                <a href="{{ route('guru.monitoring') }}" class="px-3 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-50">
                    Reset
                </a>
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
                        <th class="py-3 px-4">DU/DI</th>
                        <th class="py-3 px-4">Jam Masuk</th>
                        <th class="py-3 px-4">Jam Pulang</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($attendances as $a)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-5">
                                <span class="font-bold text-slate-900 block">{{ $a->studentPkl->student->user->name }}</span>
                                <span class="text-[10px] text-slate-400 block">NISN: {{ $a->studentPkl->student->nisn }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-semibold">
                                {{ \Carbon\Carbon::parse($a->date)->format('d/m/Y') }}
                            </td>
                            <td class="py-3.5 px-4 text-indigo-700 font-semibold">
                                {{ $a->studentPkl->company->name }}
                            </td>
                            <td class="py-3.5 px-4 font-mono">
                                {{ $a->checkin_time ? substr($a->checkin_time, 0, 5) . ' WIB' : '-' }}
                            </td>
                            <td class="py-3.5 px-4 font-mono">
                                {{ $a->checkout_time ? substr($a->checkout_time, 0, 5) . ' WIB' : '-' }}
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
                            <td colspan="7" class="py-6 text-center text-slate-400">Tidak ada data kehadiran yang sesuai filter.</td>
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
