@extends('layouts.app')

@section('page_title', 'Monitoring Presensi Jurusan')
@section('page_subtitle', 'Pantau kehadiran harian peserta PKL jurusan ' . $major->name)

@section('content')
<div class="w-full space-y-6">

    <x-page-header 
        title="Monitoring Presensi Jurusan" 
        subtitle="Rekapitulasi kehadiran harian siswa jurusan {{ $major->name }} di lokasi mitra industri."
        badge="MONITORING KAJUR"
    />

    <!-- Summary Metrics -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Total Presensi</span>
            <span class="text-xl font-black text-slate-900 mt-1 block">{{ $stats['total'] }}</span>
        </div>
        <div class="p-4 rounded-2xl bg-emerald-50/60 border border-emerald-200/60 shadow-2xs">
            <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider block">Hadir Tepat Waktu</span>
            <span class="text-xl font-black text-emerald-900 mt-1 block">{{ $stats['hadir'] }}</span>
        </div>
        <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200/60 shadow-2xs">
            <span class="text-[11px] font-bold text-amber-700 uppercase tracking-wider block">Terlambat</span>
            <span class="text-xl font-black text-amber-900 mt-1 block">{{ $stats['terlambat'] }}</span>
        </div>
        <div class="p-4 rounded-2xl bg-blue-50/60 border border-blue-200/60 shadow-2xs">
            <span class="text-[11px] font-bold text-blue-700 uppercase tracking-wider block">Izin / Sakit / Dinas</span>
            <span class="text-xl font-black text-blue-900 mt-1 block">{{ $stats['izin'] }}</span>
        </div>
        <div class="p-4 rounded-2xl bg-rose-50/60 border border-rose-200/60 shadow-2xs">
            <span class="text-[11px] font-bold text-rose-700 uppercase tracking-wider block">Mangkir (Alfa)</span>
            <span class="text-xl font-black text-rose-900 mt-1 block">{{ $stats['alfa'] }}</span>
        </div>
    </div>

    <!-- Filter Form -->
    <x-card>
        <form method="GET" action="{{ route('kajur.attendance') }}" class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Tanggal Presensi</label>
                <input type="date" name="date" value="{{ $selectedDate }}" 
                       class="w-full text-xs sm:text-sm px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-mono text-slate-900">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Rombel Kelas</label>
                <select name="class_id" class="w-full text-xs sm:text-sm px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                    <option value="">-- Semua Kelas --</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Status Kehadiran</label>
                <select name="status" class="w-full text-xs sm:text-sm px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                    <option value="">-- Semua Status --</option>
                    <option value="hadir" {{ request('status') === 'hadir' ? 'selected' : '' }}>Hadir</option>
                    <option value="terlambat" {{ request('status') === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                    <option value="izin" {{ request('status') === 'izin' ? 'selected' : '' }}>Izin</option>
                    <option value="sakit" {{ request('status') === 'sakit' ? 'selected' : '' }}>Sakit</option>
                    <option value="alfa" {{ request('status') === 'alfa' ? 'selected' : '' }}>Alfa</option>
                </select>
            </div>

            <div class="flex items-end">
                <x-button type="submit" variant="primary" size="md" class="w-full justify-center">
                    Terapkan Filter
                </x-button>
            </div>
        </form>
    </x-card>

    <!-- Table -->
    <x-card :title="'Daftar Presensi (' . \Carbon\Carbon::parse($selectedDate)->isoFormat('D MMMM Y') . ')'">
        @if($attendances->isEmpty())
            <x-empty-state 
                title="Tidak Ada Data Presensi" 
                description="Belum ada catatan presensi pada tanggal dan kriteria filter yang dipilih."
            />
        @else
            <div class="overflow-x-auto -mx-6">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                            <th class="py-3 px-6">Siswa</th>
                            <th class="py-3 px-4">Kelas</th>
                            <th class="py-3 px-4">Mitra DU/DI</th>
                            <th class="py-3 px-4">Masuk / Pulang</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-6">Guru Pembimbing</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach($attendances as $a)
                            @php
                                $spkl = $a->student->studentPkl;
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3 px-6">
                                    <div class="font-bold text-slate-900">{{ $a->student->name }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">NISN: {{ $a->student->nisn }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md font-semibold text-slate-700 bg-slate-100 text-[11px]">
                                        {{ $a->student->schoolClass?->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-medium text-slate-900">{{ $spkl?->company?->name ?? '-' }}</span>
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-700">
                                    <div>In: {{ $a->check_in_at ? \Carbon\Carbon::parse($a->check_in_at)->format('H:i') : '-' }}</div>
                                    <div class="text-slate-400">Out: {{ $a->check_out_at ? \Carbon\Carbon::parse($a->check_out_at)->format('H:i') : '-' }}</div>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <x-badge :variant="match($a->status) {
                                        'hadir' => 'emerald',
                                        'terlambat' => 'amber',
                                        'izin', 'sakit', 'dinas_luar', 'luar_kantor' => 'blue',
                                        default => 'rose'
                                    }" size="xs">
                                        {{ strtoupper(str_replace('_', ' ', $a->status)) }}
                                    </x-badge>
                                </td>
                                <td class="py-3 px-6 text-slate-600">
                                    {{ $spkl?->teacher?->user->name ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pt-4 border-t border-slate-100">
                {{ $attendances->links() }}
            </div>
        @endif
    </x-card>

</div>
@endsection
