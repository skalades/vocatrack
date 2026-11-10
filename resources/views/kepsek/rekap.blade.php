@extends('layouts.app')

@section('page_title', 'Rekapitulasi Presensi Kelas (Matriks Bulanan)')
@section('page_subtitle', 'Penyajian matriks absensi siswa per tanggal dengan kode status dan ringkasan total')

@section('content')
<div class="space-y-6">

    <!-- Filters & Export -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Pilih Kelas</label>
                <select name="class_id" class="text-xs p-2 rounded-xl bg-slate-50 border border-slate-200 font-semibold">
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ $selectedClassId == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->major->name }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Bulan</label>
                <input type="month" name="month" value="{{ $month }}" class="text-xs p-2 rounded-xl bg-slate-50 border border-slate-200 font-semibold">
            </div>
            <div class="pt-4">
                <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs">
                    Tampilkan
                </button>
            </div>
        </form>

        <div class="pt-2 md:pt-0">
            <a href="{{ route('kepapsekolah.rekap.export', ['class_id' => $selectedClassId, 'month' => $month]) }}" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-200 flex items-center space-x-1.5 transition-transform active:scale-[0.98]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Unduh File Excel / CSV</span>
            </a>
        </div>
    </div>

    <!-- Matrix Legend -->
    <div class="flex flex-wrap items-center gap-2 text-[11px] font-bold px-1">
        <span class="text-slate-400 uppercase text-[10px] mr-1">Keterangan:</span>
        <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">H: Hadir</span>
        <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200">T: Terlambat</span>
        <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200">I: Izin</span>
        <span class="px-2 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200">S: Sakit</span>
        <span class="px-2 py-0.5 rounded bg-cyan-50 text-cyan-700 border border-cyan-200">DL: Dinas Luar</span>
        <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">LK: WFH</span>
        <span class="px-2 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200">A: Alfa</span>
    </div>

    <!-- Matrix Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-center text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 text-[10px]">
                    <tr>
                        <th class="py-3 px-3 text-left sticky left-0 bg-slate-50 z-10 min-w-[160px] border-r border-slate-200">Nama Siswa</th>
                        @for($d = 1; $d <= $daysInMonth; $d++)
                            <th class="py-2.5 px-1 min-w-[28px] border-r border-slate-200/60 {{ \Carbon\Carbon::parse($month . '-' . $d)->isWeekend() ? 'bg-slate-200/50 text-rose-600' : '' }}">
                                {{ $d }}
                            </th>
                        @endfor
                        <th class="py-2.5 px-2 bg-emerald-50 text-emerald-800 border-r border-slate-200">H</th>
                        <th class="py-2.5 px-2 bg-amber-50 text-amber-800 border-r border-slate-200">T</th>
                        <th class="py-2.5 px-2 bg-blue-50 text-blue-800 border-r border-slate-200">I</th>
                        <th class="py-2.5 px-2 bg-purple-50 text-purple-800 border-r border-slate-200">S</th>
                        <th class="py-2.5 px-2 bg-cyan-50 text-cyan-800 border-r border-slate-200">DL</th>
                        <th class="py-2.5 px-2 bg-indigo-50 text-indigo-800 border-r border-slate-200">LK</th>
                        <th class="py-2.5 px-2 bg-rose-50 text-rose-800">A</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-[11px] font-medium text-slate-700">
                    @forelse($studentPkls as $sp)
                        @php
                            $attMap = $sp->attendances->keyBy(fn($a) => \Carbon\Carbon::parse($a->date)->day);
                            $h = 0; $t = 0; $i = 0; $s = 0; $dl = 0; $lk = 0; $a = 0;
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-2.5 px-3 text-left sticky left-0 bg-white z-10 border-r border-slate-200">
                                <span class="font-bold text-slate-900 block truncate max-w-[150px]">{{ $sp->student->user->name }}</span>
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
                                        'H' => 'text-emerald-700 font-bold bg-emerald-50/40',
                                        'T' => 'text-amber-700 font-bold bg-amber-50/40',
                                        'I' => 'text-blue-700 font-bold bg-blue-50/40',
                                        'S' => 'text-purple-700 font-bold bg-purple-50/40',
                                        'DL' => 'text-cyan-700 font-bold bg-cyan-50/40',
                                        'LK' => 'text-indigo-700 font-bold bg-indigo-50/40',
                                        'A' => 'text-rose-700 font-bold bg-rose-50/40',
                                        default => ($isWeekend ? 'bg-slate-100/50 text-slate-300' : 'text-slate-300')
                                    };
                                @endphp
                                <td class="py-2.5 px-0.5 border-r border-slate-100 {{ $cellColor }}">
                                    {{ $code }}
                                </td>
                            @endfor
                            <td class="py-2.5 px-2 font-bold text-emerald-800 bg-emerald-50/30 border-r border-slate-200">{{ $h }}</td>
                            <td class="py-2.5 px-2 font-bold text-amber-800 bg-amber-50/30 border-r border-slate-200">{{ $t }}</td>
                            <td class="py-2.5 px-2 font-bold text-blue-800 bg-blue-50/30 border-r border-slate-200">{{ $i }}</td>
                            <td class="py-2.5 px-2 font-bold text-purple-800 bg-purple-50/30 border-r border-slate-200">{{ $s }}</td>
                            <td class="py-2.5 px-2 font-bold text-cyan-800 bg-cyan-50/30 border-r border-slate-200">{{ $dl }}</td>
                            <td class="py-2.5 px-2 font-bold text-indigo-800 bg-indigo-50/30 border-r border-slate-200">{{ $lk }}</td>
                            <td class="py-2.5 px-2 font-bold text-rose-800 bg-rose-50/30">{{ $a }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $daysInMonth + 8 }}" class="py-8 text-center text-slate-400">
                                Tidak ada siswa terdaftar pada kelas ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
