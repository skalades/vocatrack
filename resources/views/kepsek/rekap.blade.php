@extends('layouts.app')

@section('page_title', 'Rekapitulasi Presensi Kelas (Matriks Bulanan)')
@section('page_subtitle', 'Penyajian matriks absensi siswa per tanggal dengan kode status dan ringkasan total')

@section('content')
<div class="w-full space-y-6 sm:space-y-8 animate-in fade-in duration-300">

    <!-- Enterprise Hero Header -->
    <x-page-header 
        title="Rekapitulasi Presensi Matriks Bulanan" 
        subtitle="Matriks kehadiran siswa lengkap per tanggal dengan indikator kode status dan rekap agregat kelas."
        badge="KEPALA SEKOLAH"
    >
        <a href="{{ route('kepsek.rekap.export', ['class_id' => $selectedClassId, 'month' => $month]) }}" 
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs sm:text-sm shadow-lg shadow-emerald-600/25 transition-all cursor-pointer">
            <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Unduh Excel / CSV</span>
        </a>
    </x-page-header>

    <!-- Top KPI Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <x-stat-card 
            label="Total Rombel Kelas" 
            :value="$classes->count()" 
            description="Kelas peserta program PKL"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            label="Siswa Terdata di Kelas" 
            :value="$studentPkls->count()" 
            description="Siswa aktif pada rombel terpilih"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            label="Periode Bulan Terpilih" 
            :value="\Carbon\Carbon::parse($month . '-01')->translatedFormat('F Y')" 
            :description="$daysInMonth . ' hari kalender observasi'"
            color="amber"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            label="Format Ekspor Dokumen" 
            value="Excel / CSV" 
            description="Laporan rekap bulanan siap cetak"
            color="purple"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Filters Card -->
    <x-card>
        <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Filter Rekapitulasi Matriks Presensi</h3>
                <p class="text-xs text-slate-500 font-medium">Tentukan rombel kelas dan bulan periode yang ingin ditinjau</p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                Filter Terapan
            </span>
        </div>

        <form method="GET" class="flex flex-col sm:flex-row sm:items-end gap-3.5">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilih Rombel Kelas</label>
                <select name="class_id" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-bold text-slate-900 transition-colors">
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ $selectedClassId == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->major->name }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Periode Bulan</label>
                <input type="month" name="month" value="{{ $month }}" class="w-full sm:w-auto text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-bold text-slate-900 font-mono transition-colors">
            </div>
            <div class="flex items-center gap-2">
                <x-button type="submit" variant="primary" size="md" class="w-full sm:w-auto shadow-md shadow-indigo-600/20">
                    <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Tampilkan Rekap
                </x-button>
                <a href="{{ route('kepsek.rekap.export', ['class_id' => $selectedClassId, 'month' => $month]) }}" 
                   class="sm:hidden w-full inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-emerald-600 text-white font-bold text-xs shadow-md shadow-emerald-600/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Unduh File
                </a>
            </div>
        </form>
    </x-card>

    <!-- Matrix Legend -->
    <div class="p-3.5 bg-white rounded-2xl border border-slate-100 shadow-xs flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex items-center space-x-2">
            <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
            <span class="text-xs font-extrabold text-slate-800 uppercase tracking-wider text-[11px]">Legenda Status Presensi:</span>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                <span class="font-mono font-black">H</span> Hadir
            </span>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200/60">
                <span class="font-mono font-black">T</span> Terlambat
            </span>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-sky-50 text-sky-700 border border-sky-200/60">
                <span class="font-mono font-black">I</span> Izin
            </span>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-purple-50 text-purple-700 border border-purple-200/60">
                <span class="font-mono font-black">S</span> Sakit
            </span>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-teal-50 text-teal-700 border border-teal-200/60">
                <span class="font-mono font-black">DL</span> Dinas Luar
            </span>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200/60">
                <span class="font-mono font-black">LK</span> WFH / Luar Kantor
            </span>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200/60">
                <span class="font-mono font-black">A</span> Alfa
            </span>
        </div>
    </div>

    <!-- Matrix Table Card -->
    <x-card noPadding>
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-center text-xs border-collapse min-w-[1260px]">
                <thead class="bg-slate-50 text-slate-600 font-extrabold border-b border-slate-200 text-[10px] tracking-wider uppercase">
                    <tr>
                        <th class="py-3.5 px-4 text-left sticky left-0 bg-slate-50 z-20 min-w-[200px] border-r border-slate-200 shadow-sm">Nama Siswa & NISN</th>
                        @for($d = 1; $d <= $daysInMonth; $d++)
                            <th class="py-2.5 px-1 min-w-[28px] border-r border-slate-200/60 font-mono {{ \Carbon\Carbon::parse($month . '-' . $d)->isWeekend() ? 'bg-rose-50/80 text-rose-600 font-black' : '' }}">
                                {{ $d }}
                            </th>
                        @endfor
                        <th class="py-2.5 px-2 bg-emerald-50 text-emerald-900 border-r border-slate-200 font-mono font-bold">H</th>
                        <th class="py-2.5 px-2 bg-amber-50 text-amber-900 border-r border-slate-200 font-mono font-bold">T</th>
                        <th class="py-2.5 px-2 bg-sky-50 text-sky-900 border-r border-slate-200 font-mono font-bold">I</th>
                        <th class="py-2.5 px-2 bg-purple-50 text-purple-900 border-r border-slate-200 font-mono font-bold">S</th>
                        <th class="py-2.5 px-2 bg-teal-50 text-teal-900 border-r border-slate-200 font-mono font-bold">DL</th>
                        <th class="py-2.5 px-2 bg-indigo-50 text-indigo-900 border-r border-slate-200 font-mono font-bold">LK</th>
                        <th class="py-2.5 px-2 bg-rose-50 text-rose-900 font-mono font-bold">A</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-[11px] font-medium text-slate-700">
                    @forelse($studentPkls as $sp)
                        @php
                            $attMap = $sp->attendances->keyBy(fn($a) => \Carbon\Carbon::parse($a->date)->day);
                            $h = 0; $t = 0; $i = 0; $s = 0; $dl = 0; $lk = 0; $a = 0;
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors group">
                            <td class="py-2.5 px-4 text-left sticky left-0 bg-white group-hover:bg-slate-50 z-10 border-r border-slate-200 shadow-sm transition-colors">
                                <div class="flex items-center space-x-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-indigo-50 border border-indigo-100 text-indigo-700 font-bold text-[10px] flex items-center justify-center shrink-0">
                                        {{ substr($sp->student->user->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <span class="font-bold text-slate-900 block truncate max-w-[150px] sm:max-w-[180px]">{{ $sp->student->user->name }}</span>
                                        <span class="text-[9px] font-mono text-slate-400 block">{{ $sp->student->nisn }}</span>
                                    </div>
                                </div>
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
