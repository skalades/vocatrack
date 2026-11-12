@extends('layouts.app')

@section('page_title', 'Plotting Penugasan PKL')
@section('page_subtitle', 'Pemetaan penempatan siswa ke DU/DI, guru pembimbing, dan mentor industri')

@section('content')
<div class="w-full space-y-6">

    <!-- Enterprise Hero Banner -->
    <x-page-header 
        title="Plotting Penugasan Siswa PKL" 
        subtitle="Alokasi penempatan peserta PKL ke mitra DU/DI rekanan, guru pembimbing sekolah, dan mentor lapangan."
        badge="MANAJEMEN SEKOLAH"
    >
        <x-button type="button" variant="secondary" size="sm" onclick="openManagePeriodsModal()">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>Kelola Periode PKL</span>
        </x-button>
        <x-button type="button" variant="primary" size="sm" onclick="openPlottingModal()">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span>+ Plotting Siswa Baru</span>
        </x-button>
    </x-page-header>

    <!-- KPI Summary Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card 
            title="Total Siswa Terdaftar" 
            :value="$totalStudents" 
            subtitle="Siswa peserta dalam sistem"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Sudah Ditempatkan" 
            :value="$totalPlotted" 
            :subtitle="($totalStudents > 0 ? round(($totalPlotted / $totalStudents) * 100) : 0) . '% teralokasi ke DU/DI'"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Belum Ditempatkan" 
            :value="$totalUnplotted" 
            subtitle="Menunggu alokasi ke mitra"
            :color="$totalUnplotted > 0 ? 'amber' : 'indigo'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Mitra Industri DU/DI" 
            :value="$totalCompanies" 
            subtitle="Perusahaan rekanan aktif"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Filters & Search Toolbar -->
    <x-card noPadding class="p-4 bg-white border border-slate-200/80">
        <form method="GET" action="{{ route('adminsekolah.plotting') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <!-- Search -->
            <div class="sm:col-span-5 relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama siswa, NISN, atau DU/DI..." 
                       class="w-full text-xs font-semibold pl-9.5 pr-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
            </div>

            <!-- Major Filter -->
            <div class="sm:col-span-3">
                <select name="major_id" onchange="this.form.submit()" 
                        class="w-full text-xs font-semibold px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                    <option value="all" {{ request('major_id', 'all') === 'all' ? 'selected' : '' }}>Semua Jurusan</option>
                    @foreach($majors as $m)
                        <option value="{{ $m->id }}" {{ request('major_id') == $m->id ? 'selected' : '' }}>
                            {{ $m->code }} - {{ $m->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Teacher Filter -->
            <div class="sm:col-span-2">
                <select name="teacher_id" onchange="this.form.submit()" 
                        class="w-full text-xs font-semibold px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                    <option value="all" {{ request('teacher_id', 'all') === 'all' ? 'selected' : '' }}>Semua Guru</option>
                    @foreach($teachers as $tc)
                        <option value="{{ $tc->id }}" {{ request('teacher_id') == $tc->id ? 'selected' : '' }}>
                            {{ $tc->user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Buttons -->
            <div class="sm:col-span-2 flex items-center space-x-2">
                <button type="submit" class="w-full text-xs font-bold py-2.5 px-3 rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 transition-colors">
                    Filter
                </button>
                @if(request()->filled('search') || (request()->filled('major_id') && request('major_id') !== 'all') || (request()->filled('teacher_id') && request('teacher_id') !== 'all'))
                    <a href="{{ route('adminsekolah.plotting') }}" class="p-2.5 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors shrink-0" title="Reset filter">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Table Card -->
    <x-card noPadding>
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs min-w-[1020px]">
                <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6 min-w-[210px]">Nama Siswa & Rombel</th>
                        <th class="py-3.5 px-4 min-w-[200px]">Perusahaan Mitra (DU/DI)</th>
                        <th class="py-3.5 px-4 min-w-[160px]">Guru Pembimbing</th>
                        <th class="py-3.5 px-4 min-w-[160px]">Mentor DU/DI</th>
                        <th class="py-3.5 px-4 min-w-[180px]">Periode PKL</th>
                        <th class="py-3.5 px-6 text-center min-w-[100px]">Status</th>
                        <th class="py-3.5 px-4 text-right table-sticky-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($studentPkls as $sp)
                        <tr class="hover:bg-slate-50/70 transition-colors group">
                            <td class="py-3.5 px-6 min-w-[210px]">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-700 font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ substr($sp->student->user->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center space-x-1.5">
                                            <span class="font-bold text-slate-900 block truncate" title="{{ $sp->student->user->name }}">{{ $sp->student->user->name }}</span>
                                            @if($sp->student->class && $sp->student->class->major)
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-slate-100 text-slate-700 border border-slate-200/80">
                                                    {{ $sp->student->class->major->code }}
                                                </span>
                                            @endif
                                        </div>
                                        <span class="text-[11px] text-slate-500 font-medium whitespace-nowrap">{{ $sp->student->class->name ?? '-' }} &bull; NISN: {{ $sp->student->nisn }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 min-w-[200px] max-w-[240px]">
                                <span class="font-bold text-indigo-900 block truncate" title="{{ $sp->company->name }}">{{ $sp->company->name }}</span>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @forelse($sp->company->majors as $m)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200/60" title="{{ $m->name }}">{{ $m->code }}</span>
                                    @empty
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-medium bg-slate-100 text-slate-500">Semua Jurusan</span>
                                    @endforelse
                                </div>
                                <span class="text-[11px] text-slate-400 block truncate font-normal mt-0.5" title="{{ $sp->company->address }}">{{ $sp->company->address }}</span>
                            </td>
                            <td class="py-3.5 px-4 min-w-[160px]">
                                <span class="font-semibold text-slate-900 block truncate" title="{{ $sp->teacher->user->name }}">{{ $sp->teacher->user->name }}</span>
                                <span class="text-[10px] text-slate-400 font-mono block whitespace-nowrap">NIP: {{ $sp->teacher->nip ?? '-' }}</span>
                            </td>
                            <td class="py-3.5 px-4 min-w-[160px]">
                                @if($sp->supervisor)
                                    <span class="font-semibold text-slate-900 block truncate" title="{{ $sp->supervisor->user->name }}">{{ $sp->supervisor->user->name }}</span>
                                    <span class="text-[10px] text-slate-400 font-mono block truncate" title="{{ $sp->supervisor->user->email }}">{{ $sp->supervisor->user->email }}</span>
                                @else
                                    <span class="text-slate-400 text-xs italic">Belum Ditentukan</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 min-w-[180px] max-w-[220px]">
                                <span class="text-xs font-semibold text-slate-700 block truncate" title="{{ $sp->pklPeriod->name }}">{{ $sp->pklPeriod->name }}</span>
                                <span class="text-[10px] text-slate-400 font-mono whitespace-nowrap block mt-0.5">{{ \Carbon\Carbon::parse($sp->pklPeriod->start_date)->format('d M') }} - {{ \Carbon\Carbon::parse($sp->pklPeriod->end_date)->format('d M Y') }}</span>
                            </td>
                            <td class="py-3.5 px-6 text-center min-w-[100px] whitespace-nowrap">
                                <x-badge variant="success" size="xs">
                                    {{ strtoupper($sp->status) }}
                                </x-badge>
                            </td>
                            <td class="py-3.5 px-4 text-right whitespace-nowrap table-sticky-right">
                                <form action="{{ route('adminsekolah.plotting.destroy', $sp) }}" method="POST" class="inline" onsubmit="return confirm('Batalkan penugasan PKL siswa {{ $sp->student->user->name }} di {{ $sp->company->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-[11px] font-bold border border-rose-200 transition-colors cursor-pointer" title="Batalkan penugasan siswa (unplot)">
                                        Unplot
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12">
                                <x-empty-state 
                                    title="Belum Ada Plotting Penugasan" 
                                    description="Hubungkan siswa dengan mitra industri DU/DI dan guru pembimbing sekolah."
                                    actionLabel="+ Plotting Siswa Sekarang"
                                    actionClick="openPlottingModal()"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($studentPkls->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $studentPkls->links() }}
            </div>
        @endif
    </x-card>

</div>

<!-- Modal Add Plotting (Scalable & Multi-Jurusan Ready) -->
<div id="modal-add-plotting" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-2xl rounded-3xl p-6 shadow-2xl border border-slate-100 max-h-[92vh] overflow-y-auto animate-in fade-in duration-150">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Plotting Penempatan Siswa PKL</h3>
                <p class="text-xs text-slate-500 font-medium">Petakan siswa ke perusahaan rekanan, mentor lapangan, dan guru pembimbing</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-add-plotting').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('adminsekolah.plotting.store') }}" method="POST" class="space-y-4">
            @csrf

            <!-- Periode PKL -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold text-slate-700">Periode Pelaksanaan PKL</label>
                    <button type="button" onclick="openAddPeriodModal()" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 flex items-center transition-colors">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        + Tambah / Kelola Periode
                    </button>
                </div>
                <select name="pkl_period_id" id="sel-period" required 
                        class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-semibold text-slate-900">
                    @foreach($periods as $pr)
                        <option value="{{ $pr->id }}" {{ $pr->is_active ? '' : 'disabled' }}>
                            {{ $pr->name }} ({{ \Carbon\Carbon::parse($pr->start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($pr->end_date)->format('d M Y') }}) {{ $pr->is_active ? '' : '[Nonaktif]' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Scalable Student Selection Container -->
            <div class="p-4 rounded-2xl bg-indigo-50/40 border border-indigo-100 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-indigo-950 block">Pilih Siswa PKL</span>
                        <span class="text-[11px] text-slate-500 font-medium">
                            <span id="badge-selected-count" class="font-bold text-indigo-700">0</span> siswa dipilih untuk penempatan ini
                        </span>
                    </div>

                    <!-- Toggle: Hanya yang belum diplot vs Semua -->
                    <div class="flex items-center bg-white p-1 rounded-xl border border-indigo-200/80 text-[10px] font-bold">
                        <button type="button" id="btn-toggle-unplotted" onclick="setPlotFilter('unplotted')" 
                                class="px-2.5 py-1 rounded-lg bg-indigo-600 text-white transition-all shadow-xs">
                            Hanya Belum Diplot
                        </button>
                        <button type="button" id="btn-toggle-all" onclick="setPlotFilter('all')" 
                                class="px-2.5 py-1 rounded-lg text-slate-600 hover:text-slate-900 transition-all">
                            Tampilkan Semua
                        </button>
                    </div>
                </div>

                <!-- Hierarchical Filtering: Jurusan & Kelas -->
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 pt-1">
                    <div class="sm:col-span-6">
                        <label class="block text-[10px] font-bold text-slate-600 mb-1">Filter Jurusan</label>
                        <select id="modal-filter-major" onchange="onMajorFilterChange()" 
                                class="w-full text-xs p-2 rounded-xl bg-white border border-slate-200 font-semibold text-slate-800">
                            <option value="all">-- Semua Jurusan --</option>
                            @foreach($majors as $m)
                                <option value="{{ $m->id }}">{{ $m->code }} - {{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-6">
                        <label class="block text-[10px] font-bold text-slate-600 mb-1">Filter Kelas / Rombel</label>
                        <select id="modal-filter-class" onchange="filterStudentList()" 
                                class="w-full text-xs p-2 rounded-xl bg-white border border-slate-200 font-semibold text-slate-800">
                            <option value="all">-- Semua Kelas --</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" data-major="{{ $c->major_id }}">{{ $c->name }} ({{ $c->major->code ?? '-' }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Live Student Search Bar -->
                <div class="relative">
                    <input type="text" id="modal-search-student" oninput="filterStudentList()" placeholder="Ketik nama siswa atau NISN..." 
                           class="w-full text-xs pl-8 pr-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    <svg class="w-3.5 h-3.5 absolute left-2.5 top-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                <!-- Batch Actions -->
                <div class="flex items-center justify-between text-[11px] px-1">
                    <div class="space-x-2">
                        <button type="button" onclick="selectAllVisibleStudents(true)" class="font-bold text-indigo-600 hover:text-indigo-800">Pilih Semua Tampil</button>
                        <span class="text-slate-300">|</span>
                        <button type="button" onclick="selectAllVisibleStudents(false)" class="font-bold text-slate-500 hover:text-slate-700">Batalkan Semua</button>
                    </div>
                    <span id="visible-student-count" class="text-slate-400 text-[10px]">Menampilkan 0 siswa</span>
                </div>

                <!-- Student List Scrollbox -->
                <div id="student-list-container" class="space-y-1.5 max-h-52 overflow-y-auto pr-1">
                    @foreach($students as $st)
                        <label id="student-item-{{ $st->id }}" 
                               data-id="{{ $st->id }}"
                               data-major="{{ $st->class->major_id ?? '' }}"
                               data-class="{{ $st->class_id }}"
                               data-plotted="{{ $st->is_plotted ? '1' : '0' }}"
                               data-search="{{ strtolower($st->user->name . ' ' . $st->nisn) }}"
                               class="student-row flex items-center justify-between p-2.5 rounded-xl bg-white border border-slate-200 transition-all select-none {{ $st->is_plotted ? 'opacity-60 bg-slate-50 cursor-not-allowed' : 'hover:border-indigo-300 cursor-pointer' }}">
                            
                            <div class="flex items-center space-x-2.5 min-w-0">
                                <input type="checkbox" name="student_ids[]" value="{{ $st->id }}" 
                                       onchange="updateSelectedCount()"
                                       {{ $st->is_plotted ? 'disabled' : '' }}
                                       class="student-checkbox w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500/20 {{ $st->is_plotted ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                                
                                <div class="min-w-0">
                                    <div class="flex items-center space-x-1.5">
                                        <span class="text-xs font-bold text-slate-900 block truncate">{{ $st->user->name }}</span>
                                        <span class="text-[9px] font-extrabold px-1.5 py-0.2 rounded bg-indigo-50 text-indigo-700 border border-indigo-200/60">
                                            {{ $st->class->name ?? '-' }}
                                        </span>
                                    </div>
                                    <span class="text-[10px] text-slate-400 font-mono block">NISN: {{ $st->nisn }}</span>
                                </div>
                            </div>

                            <div class="shrink-0 text-right">
                                @if($st->is_plotted)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200" title="Sudah memiliki penugasan PKL">
                                        Sudah di {{ Str::limit($st->plotted_company, 15) }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Belum Ada DU/DI
                                    </span>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>

                <!-- Empty State within Modal -->
                <div id="modal-empty-state" class="hidden p-6 rounded-2xl bg-white border border-slate-200 text-center">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center mx-auto mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <span class="text-xs font-bold text-slate-800 block">Tidak Ada Siswa yang Perlu Diplot</span>
                    <p class="text-[11px] text-slate-500 mt-0.5">Seluruh siswa pada filter ini sudah memiliki penugasan DU/DI.</p>
                </div>
            </div>

            <!-- Perusahaan Mitra (DU/DI) -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilih Perusahaan Mitra (DU/DI)</label>
                <select name="company_id" id="sel-company" onchange="updateSupervisors()" required 
                        class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-bold text-slate-900">
                    <option value="">-- Pilih Perusahaan DU/DI --</option>
                    @foreach($companies as $cp)
                        <option value="{{ $cp->id }}">
                            {{ $cp->name }} {{ $cp->majors->isNotEmpty() ? '[' . $cp->majors->pluck('code')->join(', ') . ']' : '[Semua Jurusan]' }}
                        </option>
                    @endforeach
                </select>
                <p class="text-[10px] text-slate-400 mt-1">Kode dalam kurung siku <code class="font-bold text-indigo-700">[GP, ...]</code> menunjukkan kompetensi keahlian/jurusan yang bermitra dengan industri tersebut.</p>
            </div>

            <!-- Mentor Lapangan DU/DI -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Mentor DU/DI (Opsional)</label>
                <select name="supervisor_id" id="sel-supervisor" 
                        class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                    <option value="">-- Pilih Mentor Lapangan --</option>
                    @foreach($companies as $cp)
                        @foreach($cp->supervisors as $sup)
                            <option value="{{ $sup->id }}" data-company="{{ $cp->id }}">
                                {{ $sup->user->name }} ({{ $cp->name }})
                            </option>
                        @endforeach
                    @endforeach
                </select>
            </div>

            <!-- Guru Pembimbing Sekolah -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Guru Pembimbing Sekolah</label>
                <select name="teacher_id" required 
                        class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                    @foreach($teachers as $tc)
                        <option value="{{ $tc->id }}">{{ $tc->user->name }} (NIP: {{ $tc->nip ?? '-' }})</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-add-plotting').classList.add('hidden')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md" id="btn-submit-plotting">
                    Simpan Plotting Penugasan
                </x-button>
            </div>
        </form>
    </div>
</div>

<script>
    let currentPlotFilter = 'unplotted'; // 'unplotted' or 'all'

    function openPlottingModal() {
        document.getElementById('modal-add-plotting').classList.remove('hidden');
        filterStudentList();
    }

    function setPlotFilter(type) {
        currentPlotFilter = type;
        const btnUnplotted = document.getElementById('btn-toggle-unplotted');
        const btnAll = document.getElementById('btn-toggle-all');

        if (type === 'unplotted') {
            btnUnplotted.className = "px-2.5 py-1 rounded-lg bg-indigo-600 text-white transition-all shadow-xs";
            btnAll.className = "px-2.5 py-1 rounded-lg text-slate-600 hover:text-slate-900 transition-all";
        } else {
            btnAll.className = "px-2.5 py-1 rounded-lg bg-indigo-600 text-white transition-all shadow-xs";
            btnUnplotted.className = "px-2.5 py-1 rounded-lg text-slate-600 hover:text-slate-900 transition-all";
        }

        filterStudentList();
    }

    function onMajorFilterChange() {
        const majorVal = document.getElementById('modal-filter-major').value;
        const classSelect = document.getElementById('modal-filter-class');
        const classOptions = classSelect.querySelectorAll('option');

        classOptions.forEach(opt => {
            if (opt.value === 'all') return;
            const optMajor = opt.getAttribute('data-major');
            if (majorVal === 'all' || optMajor === majorVal) {
                opt.style.display = '';
            } else {
                opt.style.display = 'none';
            }
        });

        // Reset class to all if current selected is hidden
        const currentSelectedOpt = classSelect.querySelector(`option[value="${classSelect.value}"]`);
        if (currentSelectedOpt && currentSelectedOpt.style.display === 'none') {
            classSelect.value = 'all';
        }

        filterStudentList();
    }

    function filterStudentList() {
        const majorVal = document.getElementById('modal-filter-major').value;
        const classVal = document.getElementById('modal-filter-class').value;
        const searchVal = document.getElementById('modal-search-student').value.toLowerCase().trim();

        const rows = document.querySelectorAll('.student-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const rowMajor = row.getAttribute('data-major');
            const rowClass = row.getAttribute('data-class');
            const rowPlotted = row.getAttribute('data-plotted') === '1';
            const rowSearch = row.getAttribute('data-search');

            // 1. Plotted filter
            let matchPlotted = true;
            if (currentPlotFilter === 'unplotted' && rowPlotted) {
                matchPlotted = false;
            }

            // 2. Major filter
            let matchMajor = (majorVal === 'all' || rowMajor === majorVal);

            // 3. Class filter
            let matchClass = (classVal === 'all' || rowClass === classVal);

            // 4. Search filter
            let matchSearch = (!searchVal || rowSearch.includes(searchVal));

            if (matchPlotted && matchMajor && matchClass && matchSearch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        document.getElementById('visible-student-count').textContent = `Menampilkan ${visibleCount} siswa`;

        const emptyState = document.getElementById('modal-empty-state');
        const listContainer = document.getElementById('student-list-container');
        if (visibleCount === 0) {
            emptyState.classList.remove('hidden');
            listContainer.classList.add('hidden');
        } else {
            emptyState.classList.add('hidden');
            listContainer.classList.remove('hidden');
        }

        updateSelectedCount();
    }

    function selectAllVisibleStudents(select) {
        const rows = document.querySelectorAll('.student-row');
        rows.forEach(row => {
            if (row.style.display !== 'none') {
                const cb = row.querySelector('.student-checkbox');
                if (cb && !cb.disabled) {
                    cb.checked = select;
                }
            }
        });
        updateSelectedCount();
    }

    function updateSelectedCount() {
        const checked = document.querySelectorAll('.student-checkbox:checked').length;
        document.getElementById('badge-selected-count').textContent = checked;
    }

    function updateSupervisors() {
        const selComp = document.getElementById('sel-company');
        const selSup = document.getElementById('sel-supervisor');
        if (!selComp || !selSup) return;
        const companyId = selComp.value;
        const supOptions = selSup.querySelectorAll('option');
        let hasSelectedValid = false;
        supOptions.forEach(opt => {
            if (!opt.value) return;
            const cId = opt.getAttribute('data-company');
            if (cId === companyId) {
                opt.style.display = '';
                if (opt.selected) hasSelectedValid = true;
            } else {
                opt.style.display = 'none';
            }
        });
        if (!hasSelectedValid && selSup.value) {
            selSup.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateSupervisors();
        filterStudentList();
    });

    function openManagePeriodsModal() {
        document.getElementById('modal-manage-periods').classList.remove('hidden');
    }

    function openAddPeriodModal() {
        const form = document.getElementById('form-period');
        form.action = "{{ route('adminsekolah.periods.store') }}";
        document.getElementById('period-form-method').value = "POST";
        document.getElementById('period-form-title').textContent = "Tambah Periode PKL Baru";
        document.getElementById('period-name').value = "";
        document.getElementById('period-start').value = "";
        document.getElementById('period-end').value = "";
        document.getElementById('period-active').checked = true;

        document.getElementById('modal-period-form').classList.remove('hidden');
    }

    function openEditPeriodModal(data) {
        const form = document.getElementById('form-period');
        form.action = `/adminsekolah/periods/${data.id}`;
        document.getElementById('period-form-method').value = "PUT";
        document.getElementById('period-form-title').textContent = "Edit Periode PKL";
        document.getElementById('period-name').value = data.name || "";
        document.getElementById('period-start').value = data.start_date ? data.start_date.substring(0, 10) : "";
        document.getElementById('period-end').value = data.end_date ? data.end_date.substring(0, 10) : "";
        document.getElementById('period-active').checked = !!data.is_active;

        document.getElementById('modal-period-form').classList.remove('hidden');
    }
</script>

<!-- Modal Manage Periods -->
<div id="modal-manage-periods" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-2xl rounded-3xl p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in fade-in duration-150">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Kelola Periode Pelaksanaan PKL</h3>
                <p class="text-xs text-slate-500 font-medium">Atur nama angkatan, rentang waktu mulai & berakhir, serta status keaktifan periode PKL</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-manage-periods').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold text-slate-700">Daftar Periode PKL Terdaftar</span>
            <x-button type="button" variant="primary" size="xs" onclick="openAddPeriodModal()">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                + Tambah Periode Baru
            </x-button>
        </div>

        <div class="border border-slate-200 rounded-2xl overflow-hidden divide-y divide-slate-100">
            @forelse($periods as $pr)
                <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50/70 transition-colors">
                    <div class="min-w-0">
                        <div class="flex items-center space-x-2">
                            <span class="text-xs font-bold text-slate-900 block truncate">{{ $pr->name }}</span>
                            @if($pr->is_active)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Aktif
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-500 border border-slate-200">
                                    Nonaktif
                                </span>
                            @endif
                        </div>
                        <div class="flex items-center space-x-3 text-[11px] text-slate-500 mt-1">
                            <span class="inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                {{ \Carbon\Carbon::parse($pr->start_date)->format('d M Y') }} s.d. {{ \Carbon\Carbon::parse($pr->end_date)->format('d M Y') }}
                            </span>
                            <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                            <span class="inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                {{ $pr->student_pkls_count ?? $pr->studentPkls->count() }} Siswa Diplot
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 shrink-0">
                        <form action="{{ route('adminsekolah.periods.toggle-active', $pr) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-2.5 py-1.5 rounded-xl text-xs font-bold transition-colors {{ $pr->is_active ? 'bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200' }}">
                                {{ $pr->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>

                        <button type="button" 
                                onclick="openEditPeriodModal({{ json_encode([
                                    'id' => $pr->id,
                                    'name' => $pr->name,
                                    'start_date' => $pr->start_date,
                                    'end_date' => $pr->end_date,
                                    'is_active' => (bool)$pr->is_active,
                                ]) }})"
                                class="px-2.5 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 transition-colors">
                            Edit
                        </button>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-slate-400 text-xs">
                    Belum ada periode PKL yang dibuat.
                </div>
            @endforelse
        </div>

        <div class="flex justify-end pt-4 mt-3 border-t border-slate-100">
            <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-manage-periods').classList.add('hidden')">
                Tutup
            </x-button>
        </div>
    </div>
</div>

<!-- Modal Form Period (Add / Edit) -->
<div id="modal-period-form" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-60 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-2xl border border-slate-100 animate-in fade-in duration-150">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <h3 id="period-form-title" class="text-base font-extrabold text-slate-900 tracking-tight">Tambah Periode PKL Baru</h3>
            <button type="button" onclick="document.getElementById('modal-period-form').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="form-period" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" id="period-form-method" value="POST">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Periode / Angkatan PKL</label>
                <input type="text" name="name" id="period-name" required placeholder="Contoh: PKL Gelombang 1 TP 2026/2027" 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-semibold text-slate-900">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Tanggal Mulai</label>
                    <input type="date" name="start_date" id="period-start" required 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Tanggal Berakhir</label>
                    <input type="date" name="end_date" id="period-end" required 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                </div>
            </div>

            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-800 block">Jadikan Periode Aktif</span>
                    <span class="text-[10px] text-slate-500">Periode aktif akan menjadi default saat plotting siswa</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" id="period-active" value="1" class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500/20 cursor-pointer">
                </label>
            </div>

            <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-period-form').classList.add('hidden')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md">
                    Simpan Periode
                </x-button>
            </div>
        </form>
    </div>
</div>

@endsection
