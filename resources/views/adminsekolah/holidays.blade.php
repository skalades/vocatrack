@extends('layouts.app')

@section('page_title', 'Kalender Hari Libur')
@section('page_subtitle', 'Kelola hari libur nasional dan libur khusus sekolah untuk filter evaluasi presensi')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Enterprise Hero Banner -->
    <x-page-header 
        title="Kalender Hari Libur Sekolah" 
        subtitle="Hari libur yang terdaftar akan mengecualikan siswa dari penalti ketidakhadiran (alfa otomatis) oleh cron job evaluasi harian."
        badge="SISTEM PRESENSI"
    >
        <form action="{{ route('adminsekolah.holidays.sync-national') }}" method="POST" class="inline" onsubmit="return confirm('Sinkronisasikan daftar resmi hari libur nasional untuk tahun {{ $selectedYear }}?')">
            @csrf
            <input type="hidden" name="year" value="{{ $selectedYear }}">
            <x-button type="submit" variant="secondary" size="sm">
                <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span>Sinkronisasi Libur Nasional</span>
            </x-button>
        </form>

        <x-button type="button" variant="primary" size="sm" onclick="document.getElementById('modal-add-holiday').classList.remove('hidden')">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span>+ Tambah Libur Sekolah</span>
        </x-button>
    </x-page-header>

    <!-- Top KPI Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-stat-card 
            title="Total Libur ({{ $selectedYear }})" 
            :value="$stats['total']" 
            subtitle="Hari bebas presensi terdaftar"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Libur Nasional" 
            :value="$stats['national']" 
            subtitle="Standar kalender resmi pemerintah"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Libur Khusus Sekolah" 
            :value="$stats['custom']" 
            subtitle="Libur semester, jeda tengah, atau kegiatan lokal"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('adminsekolah.holidays') }}" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <div class="flex items-center space-x-2">
                <label for="year" class="text-xs font-bold text-slate-700">Tahun:</label>
                <select name="year" id="year" onchange="this.form.submit()" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-300 bg-white text-slate-800 focus:ring-2 focus:ring-indigo-500">
                    @for($y = Carbon\Carbon::now()->year - 1; $y <= Carbon\Carbon::now()->year + 2; $y++)
                        <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div class="flex items-center space-x-2">
                <label for="month" class="text-xs font-bold text-slate-700">Bulan:</label>
                <select name="month" id="month" onchange="this.form.submit()" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-300 bg-white text-slate-800 focus:ring-2 focus:ring-indigo-500">
                    <option value="">Semua Bulan</option>
                    @foreach([
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ] as $num => $monthName)
                        <option value="{{ $num }}" {{ $selectedMonth == $num ? 'selected' : '' }}>{{ $monthName }}</option>
                    @endforeach
                </select>
            </div>

            @if($selectedMonth)
                <a href="{{ route('adminsekolah.holidays', ['year' => $selectedYear]) }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 underline">
                    Reset Bulan
                </a>
            @endif
        </form>

        <span class="text-xs font-medium text-slate-500">
            Menampilkan {{ $holidays->count() }} hari libur
        </span>
    </div>

    <!-- Holidays Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        @if($holidays->isEmpty())
            <div class="p-12 text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <h3 class="text-base font-bold text-slate-800 mb-1">Belum Ada Hari Libur Terdaftar</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto mb-5">
                    Klik tombol Sinkronisasi Libur Nasional untuk mengisi kalender libur tahun {{ $selectedYear }} secara instan atau tambah hari libur sekolah secara manual.
                </p>
                <div class="flex items-center justify-center gap-3">
                    <form action="{{ route('adminsekolah.holidays.sync-national') }}" method="POST">
                        @csrf
                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                        <x-button type="submit" variant="primary" size="sm">
                            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Sinkronisasi Otomatis Libur {{ $selectedYear }}</span>
                        </x-button>
                    </form>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-4 w-12 text-center">No</th>
                            <th class="py-3.5 px-4">Tanggal Libur</th>
                            <th class="py-3.5 px-4">Nama Hari Libur / Keterangan</th>
                            <th class="py-3.5 px-4">Tipe</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                        @foreach($holidays as $index => $holiday)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3.5 px-4 text-center text-slate-400 font-semibold">
                                    {{ $index + 1 }}
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900">
                                        {{ \Carbon\Carbon::parse($holiday->holiday_date)->translatedFormat('l, d F Y') }}
                                    </div>
                                    <span class="text-[11px] font-mono text-slate-400">
                                        {{ \Carbon\Carbon::parse($holiday->holiday_date)->format('Y-m-d') }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-800">{{ $holiday->title }}</div>
                                    @if($holiday->description)
                                        <div class="text-[11px] text-slate-500">{{ $holiday->description }}</div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($holiday->is_national)
                                        <x-badge color="emerald">Libur Nasional</x-badge>
                                    @else
                                        <x-badge color="indigo">Khusus Sekolah</x-badge>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <form action="{{ route('adminsekolah.holidays.destroy', $holiday) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus hari libur ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded-lg transition-colors" title="Hapus Hari Libur">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>

<!-- Modal Tambah Hari Libur -->
<div id="modal-add-holiday" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs hidden p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-100 animate-in fade-in zoom-in duration-200">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-800">Tambah Hari Libur Sekolah</h3>
            <button type="button" onclick="document.getElementById('modal-add-holiday').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('adminsekolah.holidays.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label for="title" class="block text-xs font-bold text-slate-700 mb-1">Nama / Keterangan Libur <span class="text-rose-500">*</span></label>
                <input type="text" name="title" id="title" required placeholder="Contoh: Libur Jeda Semester Ganjil" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label for="holiday_date" class="block text-xs font-bold text-slate-700 mb-1">Tanggal Libur <span class="text-rose-500">*</span></label>
                <input type="date" name="holiday_date" id="holiday_date" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label for="description" class="block text-xs font-bold text-slate-700 mb-1">Deskripsi Tambahan (Opsional)</label>
                <textarea name="description" id="description" rows="2" placeholder="Catatan internal perihal hari libur ini..." class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
            </div>

            <div class="flex items-center space-x-2 pt-1">
                <input type="checkbox" name="is_national" id="is_national" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <label for="is_national" class="text-xs font-medium text-slate-700">Tandai sebagai Hari Libur Nasional</label>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <x-button type="button" variant="secondary" size="sm" onclick="document.getElementById('modal-add-holiday').classList.add('hidden')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="sm">
                    Simpan Hari Libur
                </x-button>
            </div>
        </form>
    </div>
</div>
@endsection
