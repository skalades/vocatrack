@extends('layouts.app')

@section('page_title', 'Monitoring Absensi Siswa Binaan')
@section('page_subtitle', 'Pantau catatan jam masuk, jam pulang, dan status presensi harian siswa')

@section('content')
<div class="space-y-6">

    <x-page-header 
        title="Monitoring Presensi Siswa Binaan" 
        subtitle="Log kehadiran real-time, waktu check-in/out, dan status validasi presensi."
    />

    <!-- Filter Card -->
    <x-card>
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3.5 items-end">
            <div class="sm:col-span-5">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Filter Tanggal</label>
                <input type="date" name="date" value="{{ request('date') }}" 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-800">
            </div>
            <div class="sm:col-span-4">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Filter Status</label>
                <select name="status" 
                        class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-800">
                    <option value="">-- Semua Status --</option>
                    <option value="hadir" {{ request('status') === 'hadir' ? 'selected' : '' }}>Hadir Tepat Waktu</option>
                    <option value="terlambat" {{ request('status') === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                    <option value="izin" {{ request('status') === 'izin' ? 'selected' : '' }}>Izin</option>
                    <option value="sakit" {{ request('status') === 'sakit' ? 'selected' : '' }}>Sakit</option>
                    <option value="dinas_luar" {{ request('status') === 'dinas_luar' ? 'selected' : '' }}>Dinas Luar</option>
                    <option value="luar_kantor" {{ request('status') === 'luar_kantor' ? 'selected' : '' }}>WFH / Luar Kantor</option>
                    <option value="alfa" {{ request('status') === 'alfa' ? 'selected' : '' }}>Alfa</option>
                </select>
            </div>
            <div class="sm:col-span-3 flex items-center gap-2">
                <x-button type="submit" variant="primary" size="md" class="flex-1">
                    Filter
                </x-button>
                <a href="{{ route('guru.monitoring') }}" class="px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 font-bold text-xs transition-colors">
                    Reset
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
                        <th class="py-3.5 px-4">DU/DI</th>
                        <th class="py-3.5 px-4">Jam Masuk</th>
                        <th class="py-3.5 px-4">Jam Pulang</th>
                        <th class="py-3.5 px-4">Status</th>
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
                                        <span class="text-[10px] text-slate-400 font-mono">NISN: {{ $a->studentPkl->student->nisn }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-800">
                                {{ \Carbon\Carbon::parse($a->date)->format('d/m/Y') }}
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-indigo-900">
                                {{ $a->studentPkl->company->name }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-700">
                                {{ $a->checkin_time ? substr($a->checkin_time, 0, 5) . ' WIB' : '-' }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-700">
                                {{ $a->checkout_time ? substr($a->checkout_time, 0, 5) . ' WIB' : '-' }}
                            </td>
                            <td class="py-3.5 px-4">
                                <x-badge :variant="$a->status" size="xs">
                                    {{ strtoupper(str_replace('_', ' ', $a->status)) }}
                                </x-badge>
                            </td>
                            <td class="py-3.5 px-6 text-[11px] text-slate-500 max-w-xs truncate font-medium">
                                {{ $a->checkout_notes ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12">
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

</div>
@endsection
