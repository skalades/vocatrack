@extends('layouts.app')

@section('page_title', 'Rekapitulasi Presensi Kelas (Matriks Bulanan)')
@section('page_subtitle', 'Penyajian matriks absensi siswa per tanggal dengan kode status dan ringkasan total')

@section('content')
<div class="space-y-6">

    <x-page-header 
        title="Rekapitulasi Presensi Matriks Bulanan" 
        subtitle="Matriks kehadiran siswa lengkap per tanggal dengan indikator kode status dan rekap agregat."
    >
        <a href="{{ route('kepsek.rekap.export', ['class_id' => $selectedClassId, 'month' => $month]) }}" 
           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs shadow-emerald-600/20 transition-all cursor-pointer">
            <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Unduh Excel / CSV</span>
        </a>
    </x-page-header>

    <!-- Filters Card -->
    <x-card>
        <form method="GET" class="flex flex-col sm:flex-row sm:items-end gap-3.5">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilih Rombel Kelas</label>
                <select name="class_id" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-bold text-slate-900">
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ $selectedClassId == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->major->name }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Periode Bulan</label>
                <input type="month" name="month" value="{{ $month }}" class="w-full sm:w-auto text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-bold text-slate-900 font-mono">
            </div>
            <div>
                <x-button type="submit" variant="primary" size="md" class="w-full sm:w-auto">
                    Tampilkan Rekap
                </x-button>
            </div>
        </form>
    </x-card>

    <!-- Matrix Legend -->
    <div class="flex flex-wrap items-center gap-2 text-[11px] font-bold px-1">
        <span class="text-slate-400 uppercase text-[10px] mr-1">Legenda Status:</span>
        <x-badge variant="success" size="xs">H: Hadir</x-badge>
        <x-badge variant="warning" size="xs">T: Terlambat</x-badge>
        <x-badge variant="info" size="xs">I: Izin</x-badge>
        <x-badge variant="purple" size="xs">S: Sakit</x-badge>
        <x-badge variant="teal" size="xs">DL: Dinas Luar</x-badge>
        <x-badge variant="indigo" size="xs">LK: WFH</x-badge>
        <x-badge variant="danger" size="xs">A: Alfa</x-badge>
    </div>

    <!-- Matrix Table Card -->
    <x-card noPadding>
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-center text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-600 font-extrabold border-b border-slate-200 text-[10px] tracking-wider uppercase">
                    <tr>
                        <th class="py-3 px-4 text-left sticky left-0 bg-slate-50 z-10 min-w-[180px] border-r border-slate-200 shadow-xs">Nama Siswa</th>
                        @for($d = 1; $d <= $daysInMonth; $d++)
                            <th class="py-2.5 px-1 min-w-[28px] border-r border-slate-200/60 font-mono {{ \Carbon\Carbon::parse($month . '-' . $d)->isWeekend() ? 'bg-slate-200/50 text-rose-600 font-black' : '' }}">
                                {{ $d }}
                            </th>
                        @endfor
                        <th class="py-2.5 px-2 bg-emerald-50 text-emerald-900 border-r border-slate-200 font-mono">H</th>
                        <th class="py-2.5 px-2 bg-amber-50 text-amber-900 border-r border-slate-200 font-mono">T</th>
                        <th class="py-2.5 px-2 bg-sky-50 text-sky-900 border-r border-slate-200 font-mono">I</th>
                        <th class="py-2.5 px-2 bg-purple-50 text-purple-900 border-r border-slate-200 font-mono">S</th>
                        <th class="py-2.5 px-2 bg-teal-50 text-teal-900 border-r border-slate-200 font-mono">DL</th>
                        <th class="py-2.5 px-2 bg-indigo-50 text-indigo-900 border-r border-slate-200 font-mono">LK</th>
                        <th class="py-2.5 px-2 bg-rose-50 text-rose-900 font-mono">A</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-[11px] font-medium text-slate-700">
                    @forelse($studentPkls as $sp)
                        @php
                            $attMap = $sp->attendances->keyBy(fn($a) => \Carbon\Carbon::parse($a->date)->day);
                            $h = 0; $t = 0; $i = 0; $s = 0; $dl = 0; $lk = 0; $a = 0;
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors group">
                            <td class="py-2.5 px-4 text-left sticky left-0 bg-white group-hover:bg-slate-50 z-10 border-r border-slate-200 shadow-xs transition-colors">
                                <span class="font-bold text-slate-900 block truncate max-w-[170px]">{{ $sp->student->user->name }}</span>
                                <span class="text-[9px] font-mono text-slate-400 block">{{ $sp->student->nisn }}</span>
                            </td>
                            @for($d = 1; $d <= $daysInMonth; $d++)
                                @php
                                    $isWeekend = \Carbon\Carbon::parse($month . '-' . $d)->isWeekend();
                                    $record = $attMap[$d] ?? null;
                                    $code = '-';
                                    if ($record) {
                                        $code = match ($record->status) {
                                            'hadir' => 'H',
                                            'terlambat' => 'T',
                                            'izin' => 'I',
                                            'sakit' => 'S',
                                            'dinas_luar' => 'DL',
                                            'luar_kantor' => 'LK',
                                            'alfa' => 'A',
                                            default => '-'
                                        };
                                        if ($code === 'H') $h++;
                                        elseif ($code === 'T') $t++;
                                        elseif ($code === 'I') $i++;
                                        elseif ($code === 'S') $s++;
                                        elseif ($code === 'DL') $dl++;
                                        elseif ($code === 'LK') $lk++;
                                        elseif ($code === 'A') $a++;
                                    }
                                    $cellColor = match($code) {
                                        'H' => 'text-emerald-700 font-black bg-emerald-50/50',
                                        'T' => 'text-amber-700 font-black bg-amber-50/50',
                                        'I' => 'text-sky-700 font-black bg-sky-50/50',
                                        'S' => 'text-purple-700 font-black bg-purple-50/50',
                                        'DL' => 'text-teal-700 font-black bg-teal-50/50',
                                        'LK' => 'text-indigo-700 font-black bg-indigo-50/50',
                                        'A' => 'text-rose-700 font-black bg-rose-50/50',
                                        default => ($isWeekend ? 'bg-slate-100/60 text-slate-300 font-normal' : 'text-slate-300 font-normal')
                                    };
                                @endphp
                                <td class="py-2 px-0.5 border-r border-slate-100 font-mono text-[10px] {{ $cellColor }}">
                                    {{ $code }}
                                </td>
                            @endfor
                            <td class="py-2.5 px-2 font-bold font-mono text-emerald-800 bg-emerald-50/40 border-r border-slate-200">{{ $h }}</td>
                            <td class="py-2.5 px-2 font-bold font-mono text-amber-800 bg-amber-50/40 border-r border-slate-200">{{ $t }}</td>
                            <td class="py-2.5 px-2 font-bold font-mono text-sky-800 bg-sky-50/40 border-r border-slate-200">{{ $i }}</td>
                            <td class="py-2.5 px-2 font-bold font-mono text-purple-800 bg-purple-50/40 border-r border-slate-200">{{ $s }}</td>
                            <td class="py-2.5 px-2 font-bold font-mono text-teal-800 bg-teal-50/40 border-r border-slate-200">{{ $dl }}</td>
                            <td class="py-2.5 px-2 font-bold font-mono text-indigo-800 bg-indigo-50/40 border-r border-slate-200">{{ $lk }}</td>
                            <td class="py-2.5 px-2 font-bold font-mono text-rose-800 bg-rose-50/40">{{ $a }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $daysInMonth + 8 }}" class="py-12">
                                <x-empty-state 
                                    title="Tidak Ada Siswa Terdaftar" 
                                    description="Tidak ada data siswa PKL terdaftar pada rombel kelas yang dipilih."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

</div>
@endsection
