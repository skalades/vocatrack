@extends('layouts.app')

@section('page_title', 'Master Mitra DU/DI & Geofencing GPS')
@section('page_subtitle', 'Pengaturan lokasi kantor perusahaan rekanan dan aturan geofencing presensi')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Enterprise Hero Banner -->
    <x-page-header 
        title="Dunia Usaha & Dunia Industri (DU/DI)" 
        subtitle="Daftar perusahaan tempat siswa melaksanakan praktik kerja lapangan, koordinat presensi GPS, dan mentor industri."
        badge="MANAJEMEN SEKOLAH"
    >
        <x-button type="button" variant="primary" size="sm" onclick="openAddCompanyModal()">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span>+ Tambah Mitra DU/DI</span>
        </x-button>
    </x-page-header>

    @php
        $totalGpsActive = $companies->where('gps_enabled', true)->count();
        $totalActiveStudents = $companies->sum(fn($c) => $c->studentPkls->count());
        $crossMajorCount = $companies->filter(fn($c) => $c->majors->isEmpty())->count();
    @endphp

    <!-- Top KPI Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card 
            title="Total Mitra Perusahaan" 
            :value="$companies->total()" 
            subtitle="Perusahaan rekanan DU/DI"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Geofencing GPS Aktif" 
            :value="$totalGpsActive . ' DU/DI'" 
            subtitle="Validasi radius presensi aktif"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Siswa Ditempatkan" 
            :value="$totalActiveStudents . ' Siswa'" 
            subtitle="Siswa magang aktif di DU/DI"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Mitra Lintas Jurusan" 
            :value="$crossMajorCount . ' Industri'" 
            subtitle="Terbuka untuk semua jurusan"
            color="amber"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Filters & Search Toolbar -->
    <x-card noPadding class="p-4 bg-white border border-slate-200/80">
        <form method="GET" action="{{ route('adminsekolah.companies') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <!-- Search -->
            <div class="sm:col-span-6 relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama perusahaan, PIC, atau alamat..." 
                       class="w-full text-xs font-semibold pl-9.5 pr-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
            </div>

            <!-- Major Filter -->
            <div class="sm:col-span-4">
                <select name="major" onchange="this.form.submit()" 
                        class="w-full text-xs font-semibold px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                    <option value="all" {{ ($selectedMajor ?? 'all') === 'all' ? 'selected' : '' }}>Semua Jurusan / Filter Kejuruan</option>
                    <option value="lintas" {{ ($selectedMajor ?? '') === 'lintas' ? 'selected' : '' }}>-- Umum / Lintas Jurusan Saja --</option>
                    @foreach($majors as $m)
                        <option value="{{ $m->id }}" {{ ($selectedMajor ?? '') == $m->id ? 'selected' : '' }}>
                            {{ $m->code }} - {{ $m->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Buttons -->
            <div class="sm:col-span-2 flex items-center space-x-2">
                <button type="submit" class="w-full text-xs font-bold py-2.5 px-3 rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 transition-colors">
                    Filter
                </button>
                @if(request()->filled('search') || (request()->filled('major') && request('major') !== 'all'))
                    <a href="{{ route('adminsekolah.companies') }}" class="p-2.5 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors shrink-0" title="Reset filter">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
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
                        <th class="py-3.5 px-6">Nama Perusahaan & Alamat</th>
                        <th class="py-3.5 px-4">Jurusan Terkait</th>
                        <th class="py-3.5 px-4">Kontak PIC</th>
                        <th class="py-3.5 px-4">Geofencing GPS</th>
                        <th class="py-3.5 px-4">Hari Kerja</th>
                        <th class="py-3.5 px-4">Mentor Lapangan</th>
                        <th class="py-3.5 px-4 text-center">Siswa Aktif</th>
                        <th class="py-3.5 px-6 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($companies as $cp)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3.5 px-6">
                                <div class="flex items-start space-x-3">
                                    <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100 text-blue-700 font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-4 h-4 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    </div>
                                    <div class="min-w-0 max-w-xs">
                                        <span class="font-bold text-slate-900 block truncate">{{ $cp->name }}</span>
                                        <span class="text-[11px] text-slate-500 block truncate leading-snug font-normal mt-0.5">{{ $cp->address }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex flex-wrap gap-1 max-w-[180px]">
                                    @forelse($cp->majors as $m)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200/80" title="{{ $m->name }}">
                                            {{ $m->code }}
                                        </span>
                                    @empty
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                            Semua Jurusan
                                        </span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-slate-800 block">{{ $cp->contact_person }}</span>
                                <span class="text-[11px] text-slate-400 font-mono block mt-0.5">{{ $cp->phone }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($cp->gps_enabled)
                                    <x-badge variant="success" size="xs">
                                        Radius {{ $cp->radius_meters }}m
                                    </x-badge>
                                    <span class="block text-[10px] font-mono text-slate-400 mt-1">
                                        {{ number_format($cp->latitude, 4) }}, {{ number_format($cp->longitude, 4) }}
                                    </span>
                                @else
                                    <x-badge variant="neutral" size="xs">
                                        Radius Bebas
                                    </x-badge>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-semibold text-slate-800">
                                    {{ $cp->work_days === '6_days' ? '6 Hari (Sen-Sab)' : '5 Hari (Sen-Jum)' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                @forelse($cp->supervisors as $sup)
                                    <div class="mb-1 last:mb-0">
                                        <span class="block font-bold text-slate-900 text-xs">{{ $sup->user->name }}</span>
                                        <span class="block text-[10px] text-slate-400 font-mono">{{ $sup->user->email }}</span>
                                    </div>
                                @empty
                                    <span class="text-xs text-slate-400 italic">Belum ada mentor</span>
                                @endforelse
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <x-badge variant="indigo" size="sm">
                                    {{ $cp->studentPkls->count() }} Siswa
                                </x-badge>
                            </td>
                            <td class="py-3.5 px-6 text-center whitespace-nowrap space-x-1.5">
                                @php
                                    $modalData = [
                                        'id' => $cp->id,
                                        'name' => $cp->name,
                                        'address' => $cp->address,
                                        'contact_person' => $cp->contact_person,
                                        'phone' => $cp->phone,
                                        'work_days' => $cp->work_days,
                                        'gps_enabled' => (bool) $cp->gps_enabled,
                                        'latitude' => $cp->latitude,
                                        'longitude' => $cp->longitude,
                                        'radius_meters' => $cp->radius_meters,
                                        'major_ids' => $cp->majors->pluck('id')->toArray(),
                                        'update_url' => route('adminsekolah.companies.update', $cp),
                                        'store_supervisor_url' => route('adminsekolah.companies.supervisors.store', $cp),
                                        'students' => $cp->studentPkls->map(fn($sp) => [
                                            'id' => $sp->id,
                                            'name' => $sp->student->user->name ?? '-',
                                            'nisn' => $sp->student->nisn ?? '-',
                                            'nis' => $sp->student->nis ?? '-',
                                            'class' => $sp->student->class->name ?? '-',
                                            'major' => $sp->student->class->major->code ?? '-',
                                            'teacher_name' => $sp->teacher->user->name ?? 'Belum Ditugaskan',
                                            'supervisor_name' => $sp->supervisor->user->name ?? 'Belum Ditentukan',
                                            'status' => strtoupper($sp->status),
                                            'unplot_url' => route('adminsekolah.plotting.destroy', $sp),
                                        ])->values()->toArray(),
                                        'supervisors' => $cp->supervisors->map(fn($sup) => [
                                            'id' => $sup->id,
                                            'name' => $sup->user->name ?? '-',
                                            'email' => $sup->user->email ?? '-',
                                            'phone' => $sup->user->profile->phone ?? '-',
                                            'position' => $sup->position ?? 'Mentor Lapangan DU/DI',
                                            'has_students' => $cp->studentPkls->where('supervisor_id', $sup->id)->isNotEmpty(),
                                            'destroy_url' => route('adminsekolah.companies.supervisors.destroy', [$cp, $sup]),
                                        ])->values()->toArray(),
                                        'teachers' => $cp->studentPkls->pluck('teacher')->filter()->unique('id')->map(fn($tc) => [
                                            'id' => $tc->id,
                                            'name' => $tc->user->name ?? '-',
                                            'email' => $tc->user->email ?? '-',
                                            'nip' => $tc->nip ?? '-',
                                            'phone' => $tc->user->profile->phone ?? '-',
                                            'student_count' => $cp->studentPkls->where('teacher_id', $tc->id)->count(),
                                        ])->values()->toArray(),
                                    ];
                                @endphp
                                <button type="button" 
                                        onclick="openEditCompanyModal({{ json_encode($modalData) }})"
                                        class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 transition-colors cursor-pointer"
                                        title="Kelola & edit data kemitraan DU/DI">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Kelola & Edit
                                </button>
                                @if($cp->studentPkls->count() === 0)
                                    <form action="{{ route('adminsekolah.companies.destroy', $cp) }}" method="POST" class="inline" onsubmit="return confirm('Hapus data mitra industri {{ $cp->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-colors cursor-pointer" title="Hapus mitra industri">
                                            Hapus
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12">
                                <x-empty-state 
                                    title="Belum Ada Mitra DU/DI" 
                                    description="Tambahkan perusahaan rekanan untuk penempatan PKL dan geofencing radius absensi."
                                    actionLabel="+ Tambah Mitra DU/DI Sekarang"
                                    actionClick="openAddCompanyModal()"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($companies->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $companies->links() }}
            </div>
        @endif
    </x-card>

</div>

<!-- Modal Add Company -->
<div id="modal-add-company" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-xl rounded-3xl p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in fade-in duration-150">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Tambah Mitra DU/DI Baru</h3>
                <p class="text-xs text-slate-500 font-medium">Lengkapi profil industri, jurusan kejuruan terkait, dan akun mentor</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-add-company').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('adminsekolah.companies.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Perusahaan / Industri</label>
                <input type="text" name="name" required placeholder="PT Inovasi Teknologi Nusantara" 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-semibold text-slate-900">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Kantor / Pabrik Tempat PKL</label>
                <textarea name="address" rows="2" required placeholder="Jl. Raya Kawasan Industri No. 45..." 
                          class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900 leading-relaxed"></textarea>
            </div>

            <!-- Jurusan Terkait / Program Keahlian -->
            <div class="p-4 rounded-2xl bg-indigo-50/50 border border-indigo-100/80 space-y-2.5">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-indigo-950 block">Jurusan / Kompetensi Keahlian Terkait</span>
                        <span class="text-[11px] text-slate-500 font-medium">Pilih program keahlian yang bermitra dengan DU/DI ini</span>
                    </div>
                    <div class="flex items-center space-x-2 text-[11px]">
                        <button type="button" onclick="toggleAllMajors('add-major-check', true)" class="font-bold text-indigo-600 hover:text-indigo-800">Pilih Semua</button>
                        <span class="text-slate-300">|</span>
                        <button type="button" onclick="toggleAllMajors('add-major-check', false)" class="font-bold text-slate-500 hover:text-slate-700">Kosongkan</button>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-48 overflow-y-auto p-1">
                    @foreach($majors as $m)
                        <label class="flex items-start space-x-2 p-2 rounded-xl bg-white border border-slate-200 hover:border-indigo-300 hover:bg-white/90 transition cursor-pointer select-none">
                            <input type="checkbox" name="major_ids[]" value="{{ $m->id }}" 
                                   class="add-major-check mt-0.5 w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500/20 cursor-pointer">
                            <div class="min-w-0">
                                <span class="block text-xs font-extrabold text-slate-800 leading-tight">{{ $m->code }}</span>
                                <span class="block text-[10px] text-slate-500 leading-tight truncate" title="{{ $m->name }}">{{ $m->name }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
                <p class="text-[10px] text-slate-500 font-medium">Jika tidak ada yang dicentang, DU/DI akan otomatis berstatus <strong>Semua Jurusan / Lintas Program Keahlian</strong>.</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Penanggung Jawab (PIC)</label>
                    <input type="text" name="contact_person" required placeholder="Nama PIC Kantor" 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Telepon Kantor / PIC</label>
                    <input type="text" name="phone" required placeholder="021-12345678" 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Hari Kerja Mingguan</label>
                <select name="work_days" 
                        class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-semibold text-slate-900">
                    <option value="5_days">5 Hari Kerja (Senin s.d. Jumat)</option>
                    <option value="6_days">6 Hari Kerja (Senin s.d. Sabtu)</option>
                </select>
            </div>

            <!-- GPS Geofencing Section -->
            <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200/80 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-900 block">Validasi Presensi Geofencing GPS</span>
                        <span class="text-[11px] text-slate-500 font-medium">Kunci radius presensi hanya di lokasi kantor</span>
                    </div>
                    <label class="flex items-center space-x-2 cursor-pointer select-none">
                        <input type="checkbox" name="gps_enabled" value="1" id="check-gps" onchange="toggleGpsFields()" 
                               class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500/20 cursor-pointer">
                        <span class="text-xs text-indigo-700 font-bold">Aktifkan</span>
                    </label>
                </div>

                <div id="gps-fields" class="hidden grid grid-cols-3 gap-2.5 pt-2 border-t border-slate-200/60">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 mb-1">Latitude</label>
                        <input type="text" name="latitude" id="lat-in" placeholder="-6.2088" 
                               class="w-full text-xs p-2 rounded-xl bg-white border border-slate-200 font-mono text-slate-800">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 mb-1">Longitude</label>
                        <input type="text" name="longitude" id="lng-in" placeholder="106.8456" 
                               class="w-full text-xs p-2 rounded-xl bg-white border border-slate-200 font-mono text-slate-800">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 mb-1">Radius (Meter)</label>
                        <input type="number" name="radius_meters" value="100" min="10" max="5000" 
                               class="w-full text-xs p-2 rounded-xl bg-white border border-slate-200 font-bold text-slate-800">
                    </div>
                </div>
            </div>

            <!-- Initial Supervisor Account -->
            <div class="p-4 rounded-2xl bg-indigo-50/70 border border-indigo-100 space-y-2.5">
                <div class="flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </span>
                    <span class="text-xs font-bold text-indigo-950">Akun Pembimbing DU/DI (Mentor Lapangan)</span>
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[10px] font-bold text-indigo-900 mb-1">Nama Mentor</label>
                        <input type="text" name="supervisor_name" placeholder="Budi Santoso, S.Kom." 
                               class="w-full text-xs p-2 rounded-xl bg-white border border-indigo-200 focus:ring-2 focus:ring-indigo-500/20 font-medium text-slate-900">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-indigo-900 mb-1">Email Login Mentor</label>
                        <input type="email" name="supervisor_email" placeholder="budi@perusahaan.com" 
                               class="w-full text-xs p-2 rounded-xl bg-white border border-indigo-200 focus:ring-2 focus:ring-indigo-500/20 font-medium text-slate-900">
                    </div>
                </div>
                <p class="text-[10px] text-indigo-700 font-medium">Kata sandi default login mentor: <code class="bg-white/80 px-1.5 py-0.5 rounded font-mono font-bold text-indigo-900">password</code></p>
            </div>

            <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-add-company').classList.add('hidden')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md">
                    Simpan Mitra DU/DI
                </x-button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit & Management Company (Multi-Tab: Profil, Siswa PKL, Guru, Mentor) -->
<div id="modal-edit-company" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-3xl rounded-3xl p-6 shadow-2xl border border-slate-100 max-h-[92vh] overflow-y-auto animate-in fade-in duration-150 space-y-5">
        
        <!-- Modal Header -->
        <div class="flex items-start justify-between pb-3 border-b border-slate-100">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200">
                        MITRA DU/DI
                    </span>
                    <h3 id="modal-company-title" class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight">GARUDA TV</h3>
                </div>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Kelola profil industri, pantau siswa PKL aktif, guru pembimbing, dan mentor lapangan</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-edit-company').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Tab Navigation Bar -->
        <div class="flex items-center bg-slate-100/80 p-1.5 rounded-2xl gap-1 text-xs font-bold overflow-x-auto">
            <button type="button" id="btn-tab-profile" onclick="switchCompanyTab('tab-profile')"
                    class="company-tab-btn px-3.5 py-2 rounded-xl bg-indigo-600 text-white shadow-xs transition-all flex items-center space-x-1.5 shrink-0 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span>Profil & Lokasi</span>
            </button>

            <button type="button" id="btn-tab-students" onclick="switchCompanyTab('tab-students')"
                    class="company-tab-btn px-3.5 py-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-white/80 transition-all flex items-center space-x-1.5 shrink-0 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span>Siswa PKL</span>
                <span id="modal-badge-student-count" class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] font-extrabold bg-indigo-100 text-indigo-700">0</span>
            </button>

            <button type="button" id="btn-tab-teachers" onclick="switchCompanyTab('tab-teachers')"
                    class="company-tab-btn px-3.5 py-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-white/80 transition-all flex items-center space-x-1.5 shrink-0 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <span>Guru Pembimbing</span>
                <span id="modal-badge-teacher-count" class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-700">0</span>
            </button>

            <button type="button" id="btn-tab-mentors" onclick="switchCompanyTab('tab-mentors')"
                    class="company-tab-btn px-3.5 py-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-white/80 transition-all flex items-center space-x-1.5 shrink-0 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Mentor DU/DI</span>
                <span id="modal-badge-mentor-count" class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-700">0</span>
            </button>
        </div>

        <!-- ================= TAB 1: PROFIL & GEOFENCING FORM ================= -->
        <div id="tab-profile" class="company-tab-pane space-y-4">
            <form id="form-edit-company" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Perusahaan / Industri</label>
                    <input type="text" name="name" id="edit-name" required 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-semibold text-slate-900">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Kantor / Pabrik Tempat PKL</label>
                    <textarea name="address" id="edit-address" rows="2" required 
                              class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900 leading-relaxed"></textarea>
                </div>

                <!-- Jurusan Terkait / Program Keahlian -->
                <div class="p-4 rounded-2xl bg-indigo-50/50 border border-indigo-100/80 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-indigo-950 block">Jurusan / Kompetensi Keahlian Terkait</span>
                            <span class="text-[11px] text-slate-500 font-medium">Tentukan program keahlian yang relevan untuk DU/DI ini</span>
                        </div>
                        <div class="flex items-center space-x-2 text-[11px]">
                            <button type="button" onclick="toggleAllMajors('edit-major-check', true)" class="font-bold text-indigo-600 hover:text-indigo-800">Pilih Semua</button>
                            <span class="text-slate-300">|</span>
                            <button type="button" onclick="toggleAllMajors('edit-major-check', false)" class="font-bold text-slate-500 hover:text-slate-700">Kosongkan</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-48 overflow-y-auto p-1">
                        @foreach($majors as $m)
                            <label class="flex items-start space-x-2 p-2 rounded-xl bg-white border border-slate-200 hover:border-indigo-300 hover:bg-white/90 transition cursor-pointer select-none">
                                <input type="checkbox" name="major_ids[]" value="{{ $m->id }}" id="edit-major-{{ $m->id }}"
                                       class="edit-major-check mt-0.5 w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500/20 cursor-pointer">
                                <div class="min-w-0">
                                    <span class="block text-xs font-extrabold text-slate-800 leading-tight">{{ $m->code }}</span>
                                    <span class="block text-[10px] text-slate-500 leading-tight truncate" title="{{ $m->name }}">{{ $m->name }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-[10px] text-slate-500 font-medium">Jika tidak ada yang dicentang, DU/DI akan otomatis berstatus <strong>Semua Jurusan / Lintas Program Keahlian</strong>.</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Penanggung Jawab (PIC)</label>
                        <input type="text" name="contact_person" id="edit-contact-person" required 
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Telepon Kantor / PIC</label>
                        <input type="text" name="phone" id="edit-phone" required 
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Hari Kerja Mingguan</label>
                    <select name="work_days" id="edit-work-days" 
                            class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-semibold text-slate-900">
                        <option value="5_days">5 Hari Kerja (Senin s.d. Jumat)</option>
                        <option value="6_days">6 Hari Kerja (Senin s.d. Sabtu)</option>
                    </select>
                </div>

                <!-- GPS Geofencing Section -->
                <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200/80 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-slate-900 block">Validasi Presensi Geofencing GPS</span>
                            <span class="text-[11px] text-slate-500 font-medium">Kunci radius presensi hanya di lokasi kantor</span>
                        </div>
                        <label class="flex items-center space-x-2 cursor-pointer select-none">
                            <input type="checkbox" name="gps_enabled" value="1" id="edit-check-gps" onchange="toggleEditGpsFields()" 
                                   class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500/20 cursor-pointer">
                            <span class="text-xs text-indigo-700 font-bold">Aktifkan</span>
                        </label>
                    </div>

                    <div id="edit-gps-fields" class="hidden grid grid-cols-3 gap-2.5 pt-2 border-t border-slate-200/60">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 mb-1">Latitude</label>
                            <input type="text" name="latitude" id="edit-lat-in" placeholder="-6.2088" 
                                   class="w-full text-xs p-2 rounded-xl bg-white border border-slate-200 font-mono text-slate-800">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 mb-1">Longitude</label>
                            <input type="text" name="longitude" id="edit-lng-in" placeholder="106.8456" 
                                   class="w-full text-xs p-2 rounded-xl bg-white border border-slate-200 font-mono text-slate-800">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 mb-1">Radius (Meter)</label>
                            <input type="number" name="radius_meters" id="edit-radius-in" value="100" min="10" max="5000" 
                                   class="w-full text-xs p-2 rounded-xl bg-white border border-slate-200 font-bold text-slate-800">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
                    <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-edit-company').classList.add('hidden')">
                        Batal
                    </x-button>
                    <x-button type="submit" variant="primary" size="md">
                        Simpan Perubahan
                    </x-button>
                </div>
            </form>
        </div>

        <!-- ================= TAB 2: SISWA PKL DI PERUSAHAAN INI ================= -->
        <div id="tab-students" class="company-tab-pane hidden space-y-4">
            <div class="flex items-center justify-between p-3 rounded-2xl bg-indigo-50/60 border border-indigo-100 text-xs">
                <div class="flex items-center space-x-2">
                    <span class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </span>
                    <span class="text-indigo-950 font-medium">Daftar siswa magang aktif yang ditempatkan di mitra industri ini.</span>
                </div>
                <a href="{{ route('adminsekolah.plotting') }}" class="px-3 py-1.5 rounded-xl bg-indigo-600 text-white font-bold hover:bg-indigo-700 transition-colors shadow-xs">
                    + Plotting Siswa Baru
                </a>
            </div>

            <!-- Student List Table -->
            <div class="overflow-x-auto border border-slate-200/80 rounded-2xl">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-extrabold uppercase text-[10px] tracking-wider border-b border-slate-200/80">
                        <tr>
                            <th class="py-3 px-4">Nama Siswa</th>
                            <th class="py-3 px-3">Kelas & Jurusan</th>
                            <th class="py-3 px-3">Guru Pembimbing</th>
                            <th class="py-3 px-3">Mentor DU/DI</th>
                            <th class="py-3 px-3 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="modal-student-tbody" class="divide-y divide-slate-100 text-slate-700 font-medium">
                        <!-- Populated by JavaScript -->
                    </tbody>
                </table>
            </div>

            <div id="modal-students-empty" class="hidden p-8 text-center rounded-2xl bg-slate-50 border border-slate-200/80">
                <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <span class="text-xs font-bold text-slate-800 block">Belum Ada Siswa Ditempatkan</span>
                <p class="text-[11px] text-slate-500 mt-0.5">Belum ada siswa yang diplot ke perusahaan ini untuk periode aktif.</p>
                <div class="mt-3">
                    <a href="{{ route('adminsekolah.plotting') }}" class="inline-flex items-center px-3 py-1.5 rounded-xl bg-indigo-600 text-white font-bold text-xs hover:bg-indigo-700 transition shadow-xs">
                        + Plotting Siswa Sekarang
                    </a>
                </div>
            </div>
        </div>

        <!-- ================= TAB 3: GURU PEMBIMBING SEKOLAH ================= -->
        <div id="tab-teachers" class="company-tab-pane hidden space-y-4">
            <div class="p-3 rounded-2xl bg-blue-50/60 border border-blue-100 text-xs text-blue-950 flex items-center space-x-2">
                <span class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </span>
                <span>Guru pembimbing sekolah yang bertugas mendampingi, memantau absensi/jurnal, dan melakukan supervisi siswa di perusahaan ini.</span>
            </div>

            <div id="modal-teacher-container" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Populated by JavaScript -->
            </div>

            <div id="modal-teachers-empty" class="hidden p-8 text-center rounded-2xl bg-slate-50 border border-slate-200/80">
                <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
                <span class="text-xs font-bold text-slate-800 block">Belum Ada Guru Ditugaskan</span>
                <p class="text-[11px] text-slate-500 mt-0.5">Guru pembimbing otomatis terhubung ke DU/DI ini saat siswa diplot pada menu Plotting.</p>
            </div>
        </div>

        <!-- ================= TAB 4: MENTOR LAPANGAN DU/DI ================= -->
        <div id="tab-mentors" class="company-tab-pane hidden space-y-4">
            <div class="p-3 rounded-2xl bg-emerald-50/60 border border-emerald-100 text-xs text-emerald-950 flex items-center space-x-2">
                <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </span>
                <span>Akun pembimbing industri / mentor lapangan untuk verifikasi presensi, approval jurnal kegiatan, dan evaluasi nilai akhir.</span>
            </div>

            <!-- Existing Mentors List -->
            <div>
                <h4 class="text-xs font-bold text-slate-900 mb-2">Mentor Terdaftar</h4>
                <div id="modal-mentor-container" class="space-y-2">
                    <!-- Populated by JavaScript -->
                </div>
                <div id="modal-mentors-empty" class="hidden p-6 text-center rounded-2xl bg-slate-50 border border-slate-200/80">
                    <span class="text-xs font-bold text-slate-600 block">Belum Ada Mentor Terdaftar</span>
                    <p class="text-[11px] text-slate-400 mt-0.5">Tambahkan akun mentor industri melalui formulir di bawah ini.</p>
                </div>
            </div>

            <!-- Form Add New Supervisor for this Company -->
            <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200/80 space-y-3">
                <div class="flex items-center space-x-2 pb-2 border-b border-slate-200/60">
                    <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </span>
                    <span class="text-xs font-bold text-slate-900">+ Tambah Akun Mentor DU/DI Baru</span>
                </div>

                <form id="form-add-supervisor-to-company" method="POST" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 mb-1">Nama Lengkap Mentor</label>
                            <input type="text" name="name" required placeholder="Nama Pembimbing Lapangan"
                                   class="w-full text-xs p-2.5 rounded-xl bg-white border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 font-semibold text-slate-900">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 mb-1">Email Login Mentor</label>
                            <input type="email" name="email" required placeholder="mentor@perusahaan.com"
                                   class="w-full text-xs p-2.5 rounded-xl bg-white border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 font-semibold text-slate-900">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 mb-1">Jabatan / Posisi (Opsional)</label>
                            <input type="text" name="position" placeholder="Contoh: Senior Video Editor / HRD"
                                   class="w-full text-xs p-2.5 rounded-xl bg-white border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 font-medium text-slate-900">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 mb-1">Nomor Telepon / WhatsApp</label>
                            <input type="text" name="phone" placeholder="08123456789"
                                   class="w-full text-xs p-2.5 rounded-xl bg-white border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 font-medium text-slate-900">
                        </div>
                    </div>

                    <p class="text-[10px] text-slate-500">Kata sandi default login mentor adalah <code class="font-bold text-emerald-700 bg-emerald-50 px-1 py-0.5 rounded">password</code>.</p>

                    <div class="flex justify-end pt-1">
                        <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-bold text-xs hover:bg-emerald-700 transition shadow-xs cursor-pointer">
                            Simpan & Buat Akun Mentor
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<!-- Hidden Action Forms for Modal Buttons -->
<form id="form-unplot-from-modal" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

<form id="form-destroy-supervisor-from-modal" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

<script>
    function openAddCompanyModal() {
        document.getElementById('modal-add-company').classList.remove('hidden');
    }

    function toggleGpsFields() {
        const checked = document.getElementById('check-gps').checked;
        const fields = document.getElementById('gps-fields');
        if (checked) {
            fields.classList.remove('hidden');
        } else {
            fields.classList.add('hidden');
        }
    }

    function toggleEditGpsFields() {
        const checked = document.getElementById('edit-check-gps').checked;
        const fields = document.getElementById('edit-gps-fields');
        if (checked) {
            fields.classList.remove('hidden');
        } else {
            fields.classList.add('hidden');
        }
    }

    function toggleAllMajors(className, checked) {
        document.querySelectorAll('.' + className).forEach(el => {
            el.checked = checked;
        });
    }

    function switchCompanyTab(tabId) {
        // Sembunyikan semua tab pane
        document.querySelectorAll('.company-tab-pane').forEach(el => el.classList.add('hidden'));

        // Reset style tombol tab
        document.querySelectorAll('.company-tab-btn').forEach(btn => {
            btn.className = "company-tab-btn px-3.5 py-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-white/80 transition-all flex items-center space-x-1.5 shrink-0 cursor-pointer";
        });

        // Tampilkan pane aktif
        const activePane = document.getElementById(tabId);
        if (activePane) activePane.classList.remove('hidden');

        // Beri style aktif pada tombol
        const activeBtn = document.getElementById('btn-' + tabId);
        if (activeBtn) {
            activeBtn.className = "company-tab-btn px-3.5 py-2 rounded-xl bg-indigo-600 text-white shadow-xs transition-all flex items-center space-x-1.5 shrink-0 cursor-pointer";
        }
    }

    function openEditCompanyModal(data) {
        // Reset ke tab profil secara default
        switchCompanyTab('tab-profile');

        // Judul modal
        document.getElementById('modal-company-title').textContent = data.name || 'Mitra DU/DI';

        // 1. Populasi Form Edit Profil
        const form = document.getElementById('form-edit-company');
        form.action = data.update_url;

        document.getElementById('edit-name').value = data.name || '';
        document.getElementById('edit-address').value = data.address || '';
        document.getElementById('edit-contact-person').value = data.contact_person || '';
        document.getElementById('edit-phone').value = data.phone || '';
        document.getElementById('edit-work-days').value = data.work_days || '5_days';

        const gpsCheck = document.getElementById('edit-check-gps');
        gpsCheck.checked = !!data.gps_enabled;
        document.getElementById('edit-lat-in').value = data.latitude !== null ? data.latitude : '';
        document.getElementById('edit-lng-in').value = data.longitude !== null ? data.longitude : '';
        document.getElementById('edit-radius-in').value = data.radius_meters || 100;
        toggleEditGpsFields();

        document.querySelectorAll('.edit-major-check').forEach(el => {
            el.checked = false;
        });
        if (Array.isArray(data.major_ids)) {
            data.major_ids.forEach(id => {
                const el = document.getElementById('edit-major-' + id);
                if (el) el.checked = true;
            });
        }

        // 2. Populasi Tab Siswa PKL
        const students = Array.isArray(data.students) ? data.students : [];
        document.getElementById('modal-badge-student-count').textContent = students.length;

        const studentTbody = document.getElementById('modal-student-tbody');
        const studentsEmpty = document.getElementById('modal-students-empty');
        studentTbody.innerHTML = '';

        if (students.length > 0) {
            studentsEmpty.classList.add('hidden');
            studentTbody.closest('div').classList.remove('hidden');

            students.forEach(st => {
                const tr = document.createElement('tr');
                tr.className = "hover:bg-slate-50/70 transition-colors";
                tr.innerHTML = `
                    <td class="py-3 px-4">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-700 font-bold text-xs flex items-center justify-center shrink-0">
                                ${st.name.charAt(0)}
                            </div>
                            <div class="min-w-0">
                                <span class="font-bold text-slate-900 block truncate">${st.name}</span>
                                <span class="text-[10px] text-slate-400 font-mono block">NISN: ${st.nisn}</span>
                            </div>
                        </div>
                    </td>
                    <td class="py-3 px-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200/60">
                            ${st.class}
                        </span>
                    </td>
                    <td class="py-3 px-3">
                        <span class="font-semibold text-slate-800 block text-xs">${st.teacher_name}</span>
                    </td>
                    <td class="py-3 px-3">
                        <span class="font-medium text-slate-700 block text-xs">${st.supervisor_name}</span>
                    </td>
                    <td class="py-3 px-3 text-center">
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            ${st.status}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-right whitespace-nowrap">
                        <button type="button" onclick="submitUnplot('${st.unplot_url}', '${st.name}')"
                                class="px-2 py-1 rounded-lg bg-rose-50 text-rose-700 text-[10px] font-bold border border-rose-200 hover:bg-rose-100 transition cursor-pointer"
                                title="Batalkan penugasan siswa dari mitra ini">
                            Unplot
                        </button>
                    </td>
                `;
                studentTbody.appendChild(tr);
            });
        } else {
            studentTbody.closest('div').classList.add('hidden');
            studentsEmpty.classList.remove('hidden');
        }

        // 3. Populasi Tab Guru Pembimbing
        const teachers = Array.isArray(data.teachers) ? data.teachers : [];
        document.getElementById('modal-badge-teacher-count').textContent = teachers.length;

        const teacherContainer = document.getElementById('modal-teacher-container');
        const teachersEmpty = document.getElementById('modal-teachers-empty');
        teacherContainer.innerHTML = '';

        if (teachers.length > 0) {
            teachersEmpty.classList.add('hidden');
            teacherContainer.classList.remove('hidden');

            teachers.forEach(tc => {
                const card = document.createElement('div');
                card.className = "p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-start space-x-3";
                card.innerHTML = `
                    <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs shrink-0">
                        ${tc.name.charAt(0)}
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="font-bold text-slate-900 block text-xs truncate">${tc.name}</span>
                        <span class="text-[10px] text-slate-400 font-mono block">NIP: ${tc.nip || '-'}</span>
                        <div class="flex items-center space-x-2 mt-1 text-[11px] text-slate-500">
                            <span>${tc.email}</span>
                            ${tc.phone ? `<span>&bull; ${tc.phone}</span>` : ''}
                        </div>
                        <div class="mt-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-blue-50 text-blue-700 border border-blue-200/80">
                                Membimbing ${tc.student_count} Siswa di DU/DI ini
                            </span>
                        </div>
                    </div>
                `;
                teacherContainer.appendChild(card);
            });
        } else {
            teacherContainer.classList.add('hidden');
            teachersEmpty.classList.remove('hidden');
        }

        // 4. Populasi Tab Mentor DU/DI
        const supervisors = Array.isArray(data.supervisors) ? data.supervisors : [];
        document.getElementById('modal-badge-mentor-count').textContent = supervisors.length;

        const mentorContainer = document.getElementById('modal-mentor-container');
        const mentorsEmpty = document.getElementById('modal-mentors-empty');
        mentorContainer.innerHTML = '';

        if (supervisors.length > 0) {
            mentorsEmpty.classList.add('hidden');
            mentorContainer.classList.remove('hidden');

            supervisors.forEach(sup => {
                const card = document.createElement('div');
                card.className = "p-3 rounded-2xl bg-white border border-slate-200 flex items-center justify-between";
                card.innerHTML = `
                    <div class="flex items-center space-x-3 min-w-0">
                        <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-800 font-bold text-xs flex items-center justify-center shrink-0">
                            ${sup.name.charAt(0)}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center space-x-1.5">
                                <span class="font-bold text-slate-900 text-xs block truncate">${sup.name}</span>
                                <span class="text-[9px] font-semibold px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 border border-slate-200">
                                    ${sup.position}
                                </span>
                            </div>
                            <span class="text-[11px] text-slate-400 font-mono block">${sup.email} ${sup.phone ? '&bull; ' + sup.phone : ''}</span>
                        </div>
                    </div>

                    ${!sup.has_students ? `
                        <button type="button" onclick="submitDestroySupervisor('${sup.destroy_url}', '${sup.name}')"
                                class="px-2 py-1 rounded-lg text-rose-600 hover:bg-rose-50 border border-rose-200 text-[10px] font-bold transition cursor-pointer">
                            Hapus
                        </button>
                    ` : `
                        <span class="text-[10px] text-slate-400 font-medium px-2 py-1 bg-slate-50 rounded-lg">
                            Mentor Aktif
                        </span>
                    `}
                `;
                mentorContainer.appendChild(card);
            });
        } else {
            mentorContainer.classList.add('hidden');
            mentorsEmpty.classList.remove('hidden');
        }

        // Set action URL form tambah mentor baru
        document.getElementById('form-add-supervisor-to-company').action = data.store_supervisor_url;

        // Buka Modal
        document.getElementById('modal-edit-company').classList.remove('hidden');
    }

    function submitUnplot(url, studentName) {
        if (confirm(`Batalkan penugasan PKL siswa "${studentName}" dari mitra industri ini?`)) {
            const form = document.getElementById('form-unplot-from-modal');
            form.action = url;
            form.submit();
        }
    }

    function submitDestroySupervisor(url, mentorName) {
        if (confirm(`Hapus akun mentor lapangan "${mentorName}"?`)) {
            const form = document.getElementById('form-destroy-supervisor-from-modal');
            form.action = url;
            form.submit();
        }
    }
</script>
@endsection
