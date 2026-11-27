@extends('layouts.app')

@section('page_title', 'Monitoring Absensi & Jurnal Harian')
@section('page_subtitle', 'Pantau siswa yang belum absen masuk, belum absen pulang, atau belum mengisi jurnal serta lakukan eskalasi ke Guru Pembimbing')

@section('content')
<div class="w-full space-y-6">

    <x-page-header
        title="Monitoring Absensi & Jurnal Harian PKL"
        subtitle="Audit kepatuhan harian siswa per jurusan atau perusahaan, lihat detail pembimbing, dan kirim pengingat eskalasi ke Guru Pembimbing."
        badge="ADMINISTRASI SEKOLAH"
    >
        <x-button type="button" variant="secondary" size="sm" onclick="window.location.href='{{ route('adminsekolah.map', ['date' => $date]) }}'">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
            <span>Peta Sebaran DU/DI</span>
        </x-button>
    </x-page-header>

    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2.5">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-4 text-xs space-y-1">
            <div class="font-bold">Eskalasi tidak dapat diproses:</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($isSchoolHoliday)
        <div class="p-3.5 rounded-2xl bg-sky-50 border border-sky-200 text-sky-800 text-xs font-semibold flex items-center gap-2">
            <svg class="w-4 h-4 text-sky-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>Tanggal {{ \Carbon\Carbon::parse($date)->isoFormat('dddd, D MMMM Y') }} tercatat sebagai Hari Libur Sekolah (kecuali perusahaan 7 hari kerja / On-Project).</span>
        </div>
    @endif

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card
            title="Wajib Hadir ({{ \Carbon\Carbon::parse($date)->format('d/m') }})"
            :value="$stats['expected']"
            :subtitle="'Dari ' . $stats['total'] . ' siswa aktif pada tanggal ini'"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card
            title="Belum Absen Masuk"
            :value="$stats['missing_checkin']"
            subtitle="Belum check-in / Alfa"
            :color="$stats['missing_checkin'] > 0 ? 'rose' : 'emerald'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card
            title="Belum Absen Pulang"
            :value="$stats['missing_checkout']"
            subtitle="Sudah masuk, belum check-out"
            :color="$stats['missing_checkout'] > 0 ? 'amber' : 'emerald'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card
            title="Belum Isi Jurnal"
            :value="$stats['missing_journal']"
            subtitle="Belum menyusun jurnal harian"
            :color="$stats['missing_journal'] > 0 ? 'amber' : 'emerald'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Filter Bar + Quick Compliance Tabs -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-4 shadow-xs space-y-4">
        <form method="GET" action="{{ route('adminsekolah.attendance-monitoring') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
            <input type="hidden" name="compliance" value="{{ $compliance }}">

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Tanggal Pantau</label>
                <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()"
                       class="w-full text-xs font-semibold px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Jurusan</label>
                <select name="major_id" onchange="this.form.submit()" class="w-full text-xs font-semibold px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="all">Semua Jurusan</option>
                    @foreach($majors as $m)
                        <option value="{{ $m->id }}" {{ (string) request('major_id') === (string) $m->id ? 'selected' : '' }}>
                            {{ $m->code ? $m->code . ' — ' : '' }}{{ $m->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Perusahaan (DU/DI)</label>
                <select name="company_id" onchange="this.form.submit()" class="w-full text-xs font-semibold px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="all">Semua Perusahaan</option>
                    @foreach($companies as $c)
                        <option value="{{ $c->id }}" {{ (string) request('company_id') === (string) $c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Guru Pembimbing</label>
                <select name="teacher_id" onchange="this.form.submit()" class="w-full text-xs font-semibold px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="all">Semua Pembimbing</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}" {{ (string) request('teacher_id') === (string) $t->id ? 'selected' : '' }}>
                            {{ $t->user->name ?? '-' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Cari Siswa / DU/DI</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama, NISN, DU/DI..."
                       class="w-full text-xs font-medium px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div class="flex items-center gap-2">
                <x-button type="submit" variant="primary" size="sm" class="flex-1 justify-center">
                    Filter
                </x-button>
                @if(request()->hasAny(['major_id', 'company_id', 'teacher_id', 'search', 'compliance']) || $date !== now()->toDateString())
                    <a href="{{ route('adminsekolah.attendance-monitoring') }}" class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50">
                        Reset
                    </a>
                @endif
            </div>
        </form>

        @php
            $baseFilterParams = request()->except(['compliance', 'page']);
            $tabs = [
                'all' => ['label' => 'Semua Siswa', 'count' => $stats['total'], 'color' => 'indigo'],
                'needs_attention' => ['label' => 'Perlu Perhatian', 'count' => $stats['needs_attention'], 'color' => 'rose'],
                'missing_checkin' => ['label' => 'Belum Absen Masuk', 'count' => $stats['missing_checkin'], 'color' => 'rose'],
                'missing_checkout' => ['label' => 'Belum Absen Pulang', 'count' => $stats['missing_checkout'], 'color' => 'amber'],
                'missing_journal' => ['label' => 'Belum Isi Jurnal', 'count' => $stats['missing_journal'], 'color' => 'amber'],
                'complete' => ['label' => 'Sudah Lengkap', 'count' => $stats['complete'], 'color' => 'emerald'],
                'exempt' => ['label' => 'Izin / Libur', 'count' => $stats['exempt'], 'color' => 'slate'],
            ];
        @endphp

        <div class="flex flex-wrap items-center gap-2 pt-3 border-t border-slate-100">
            @foreach($tabs as $key => $tab)
                @php
                    $isActiveTab = $compliance === $key;
                    $activeTabClass = match($tab['color']) {
                        'rose' => 'bg-rose-600 text-white border-rose-600',
                        'amber' => 'bg-amber-500 text-white border-amber-500',
                        'emerald' => 'bg-emerald-600 text-white border-emerald-600',
                        'slate' => 'bg-slate-700 text-white border-slate-700',
                        default => 'bg-indigo-600 text-white border-indigo-600',
                    };
                @endphp
                <a href="{{ route('adminsekolah.attendance-monitoring', array_merge($baseFilterParams, ['compliance' => $key])) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-colors {{ $isActiveTab ? $activeTabClass : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100' }}">
                    <span>{{ $tab['label'] }}</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ $isActiveTab ? 'bg-white/20 text-white' : 'bg-white text-slate-700 border border-slate-200' }}">
                        {{ $tab['count'] }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Monitoring Table -->
    <x-card title="Daftar Kepatuhan Absensi & Jurnal Siswa ({{ \Carbon\Carbon::parse($date)->isoFormat('dddd, D MMMM Y') }})" noPadding>
        @if($rows->isEmpty())
            <div class="p-10 text-center space-y-2">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="text-sm font-bold text-slate-700">Tidak ada data siswa pada filter ini.</p>
                <p class="text-xs text-slate-500">Coba ubah filter jurusan, perusahaan, tanggal, atau tab status kepatuhan.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200/80 bg-slate-50/70 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-4">Siswa &amp; Jurusan</th>
                            <th class="py-3.5 px-4">Perusahaan &amp; Pembimbing</th>
                            <th class="py-3.5 px-4">Absen Masuk</th>
                            <th class="py-3.5 px-4">Absen Pulang</th>
                            <th class="py-3.5 px-4">Jurnal Harian</th>
                            <th class="py-3.5 px-4">Eskalasi</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach($rows as $row)
                            <tr class="hover:bg-slate-50/80 transition-colors {{ $row['needs_attention'] ? 'bg-rose-50/20' : '' }}">
                                <td class="py-3.5 px-4">
                                    <button type="button" onclick='openDetailEscalationModal(@json($row), false)' class="text-left group cursor-pointer">
                                        <span class="font-bold text-slate-900 group-hover:text-indigo-700 block">{{ $row['student_name'] }}</span>
                                        <span class="text-[11px] text-slate-500 block mt-0.5">
                                            {{ $row['class_name'] }} &bull; <strong class="text-slate-700">{{ $row['major_name'] }}</strong>
                                        </span>
                                        <span class="text-[10px] text-slate-400 font-mono">NISN: {{ $row['student_nisn'] }}</span>
                                    </button>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-semibold text-slate-800 block">{{ $row['company_name'] }}</span>
                                    <span class="text-[11px] text-slate-500 block mt-0.5">
                                        Guru: <strong class="text-slate-700">{{ $row['teacher_name'] }}</strong>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($row['is_exempt'])
                                        <x-badge variant="neutral" size="xs">{{ $row['checkin_status_label'] }}</x-badge>
                                    @elseif($row['missing_checkin'])
                                        <x-badge variant="danger" size="xs">{{ $row['checkin_status_label'] }}</x-badge>
                                    @elseif($row['attendance_status'] === 'terlambat')
                                        <x-badge variant="warning" size="xs">{{ $row['checkin_status_label'] }}</x-badge>
                                    @else
                                        <x-badge variant="success" size="xs">{{ $row['checkin_status_label'] }}</x-badge>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($row['is_exempt'])
                                        <span class="text-[11px] text-slate-400">-</span>
                                    @elseif($row['missing_checkout'])
                                        <x-badge variant="warning" size="xs">{{ $row['checkout_status_label'] }}</x-badge>
                                    @elseif($row['checkout_time'])
                                        <x-badge variant="success" size="xs">{{ $row['checkout_status_label'] }}</x-badge>
                                    @else
                                        <span class="text-[11px] text-slate-400">{{ $row['checkout_status_label'] }}</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($row['is_exempt'])
                                        <span class="text-[11px] text-slate-400">-</span>
                                    @elseif($row['missing_journal'])
                                        <x-badge variant="warning" size="xs">{{ $row['journal_status_label'] }}</x-badge>
                                    @else
                                        <x-badge variant="success" size="xs">{{ $row['journal_status_label'] }}</x-badge>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($row['latest_escalation'])
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200"
                                              title="Dieskalasi pukul {{ $row['latest_escalation']['time'] }} oleh {{ $row['latest_escalation']['by'] }}">
                                            Dieskalasi {{ $row['latest_escalation']['time'] }} ({{ $row['escalation_count'] }}x)
                                        </span>
                                    @else
                                        <span class="text-[11px] text-slate-400">Belum ada</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-1.5">
                                    <button type="button"
                                            onclick='openDetailEscalationModal(@json($row), false)'
                                            class="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors cursor-pointer">
                                        Detail
                                    </button>
                                    @if(!$row['is_exempt'] && $row['needs_attention'])
                                        <button type="button"
                                                onclick='openDetailEscalationModal(@json($row), true)'
                                                class="px-2.5 py-1.5 rounded-lg text-xs font-bold text-amber-800 bg-amber-100 hover:bg-amber-200 border border-amber-200 transition-colors cursor-pointer">
                                            Eskalasi
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100">
                {{ $rows->links() }}
            </div>
        @endif
    </x-card>

</div>

<!-- Modal Detail Siswa + Eskalasi ke Guru Pembimbing -->
<div id="modal-detail-escalation" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white w-full max-w-2xl rounded-3xl p-6 shadow-2xl border border-slate-100 my-8 space-y-5">
        <div class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100">
            <div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200 uppercase tracking-wider">Detail &amp; Eskalasi Harian</span>
                <h3 id="detail-student-name" class="text-lg font-extrabold text-slate-900 mt-1">-</h3>
                <p id="detail-date-subtitle" class="text-xs text-slate-500 font-medium">-</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-detail-escalation').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- 4 Kartu Informasi Relasi -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70 space-y-1">
                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Profil Siswa</span>
                <p id="detail-student-nisn" class="font-mono text-slate-700 font-semibold">-</p>
                <p class="text-slate-600">Kontak Siswa: <strong id="detail-student-phone" class="font-mono text-slate-900">-</strong></p>
            </div>

            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70 space-y-1">
                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Jurusan &amp; Rombel</span>
                <p id="detail-major-name" class="font-bold text-slate-900">-</p>
                <p class="text-slate-600">Kelas: <strong id="detail-class-name" class="text-slate-900">-</strong></p>
            </div>

            <div class="p-3.5 rounded-2xl bg-indigo-50/50 border border-indigo-100 space-y-1">
                <span class="text-[10px] font-extrabold text-indigo-500 uppercase tracking-wider block">Mitra Perusahaan (DU/DI)</span>
                <p id="detail-company-name" class="font-bold text-slate-900">-</p>
                <p id="detail-company-address" class="text-[11px] text-slate-600 line-clamp-2">-</p>
                <p class="text-[11px] text-slate-600">PIC: <strong id="detail-company-pic">-</strong> (<span id="detail-company-phone" class="font-mono">-</span>)</p>
                <p class="text-[11px] text-indigo-700 font-semibold"><span id="detail-company-schedule">-</span></p>
            </div>

            <div class="p-3.5 rounded-2xl bg-amber-50/60 border border-amber-200/70 space-y-1">
                <span class="text-[10px] font-extrabold text-amber-700 uppercase tracking-wider block">Guru Pembimbing</span>
                <p id="detail-teacher-name" class="font-bold text-slate-900">-</p>
                <p class="text-[11px] text-slate-600">NIP: <span id="detail-teacher-nip" class="font-mono">-</span></p>
                <p class="text-[11px] text-slate-600">WA / HP: <strong id="detail-teacher-phone" class="font-mono text-slate-900">-</strong></p>
            </div>
        </div>

        <!-- Ringkasan Kepatuhan Tanggal Ini -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200 space-y-2 text-xs">
            <span class="text-[11px] font-extrabold text-slate-700 uppercase tracking-wider block">Status Kehadiran &amp; Jurnal</span>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/60">
                    <span class="text-[10px] text-slate-400 block">Absen Masuk</span>
                    <strong id="detail-checkin-status" class="text-slate-900">-</strong>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/60">
                    <span class="text-[10px] text-slate-400 block">Absen Pulang</span>
                    <strong id="detail-checkout-status" class="text-slate-900">-</strong>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/60">
                    <span class="text-[10px] text-slate-400 block">Jurnal Harian</span>
                    <strong id="detail-journal-status" class="text-slate-900">-</strong>
                </div>
            </div>
            <p id="detail-journal-summary" class="hidden text-[11px] text-slate-600 bg-slate-50 p-2.5 rounded-xl border border-slate-200/60 italic"></p>
        </div>

        <!-- Riwayat Eskalasi Hari Ini -->
        <div id="detail-escalation-history-box" class="hidden p-3.5 rounded-2xl bg-indigo-50/60 border border-indigo-200/70 space-y-2 text-xs">
            <span class="text-[11px] font-extrabold text-indigo-800 block">Riwayat Eskalasi Hari Ini</span>
            <ul id="detail-escalation-history-list" class="space-y-1.5 text-[11px] text-indigo-900"></ul>
        </div>

        <!-- Form Eskalasi ke Guru Pembimbing -->
        <form id="form-escalate-teacher" method="POST" action="{{ route('adminsekolah.attendance-monitoring.escalate') }}" class="p-4 rounded-2xl bg-amber-50/50 border border-amber-200 space-y-3">
            @csrf
            <input type="hidden" name="student_pkl_id" id="escalate-spkl-id">
            <input type="hidden" name="date" id="escalate-date">

            <div class="flex items-center justify-between gap-2">
                <div>
                    <h4 class="text-xs font-extrabold text-slate-900">Eskalasi ke Guru Pembimbing</h4>
                    <p class="text-[11px] text-slate-600">Kirim pengingat ke guru pembimbing agar segera mengingatkan siswa terkait.</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-slate-200 cursor-pointer text-xs font-semibold text-slate-800">
                    <input type="checkbox" name="missing_types[]" value="checkin" id="escalate-type-checkin" class="rounded text-amber-600 border-slate-300 focus:ring-amber-500/20">
                    <span>Belum Absen Masuk</span>
                </label>
                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-slate-200 cursor-pointer text-xs font-semibold text-slate-800">
                    <input type="checkbox" name="missing_types[]" value="checkout" id="escalate-type-checkout" class="rounded text-amber-600 border-slate-300 focus:ring-amber-500/20">
                    <span>Belum Absen Pulang</span>
                </label>
                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-slate-200 cursor-pointer text-xs font-semibold text-slate-800">
                    <input type="checkbox" name="missing_types[]" value="journal" id="escalate-type-journal" class="rounded text-amber-600 border-slate-300 focus:ring-amber-500/20">
                    <span>Belum Isi Jurnal</span>
                </label>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Catatan Admin untuk Guru Pembimbing (Opsional)</label>
                <textarea name="note" id="escalate-note" rows="2" placeholder="Contoh: Mohon segera konfirmasi ke siswa & pembimbing DU/DI hari ini..."
                          class="w-full text-xs p-2.5 rounded-xl bg-white border border-slate-200 focus:border-amber-500 focus:ring-amber-500/20 text-slate-800"></textarea>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                <a id="detail-teacher-wa-btn" href="#" target="_blank" rel="noopener noreferrer"
                   class="hidden inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition-colors">
                    <span>Buka WhatsApp Guru (Manual)</span>
                </a>
                <div class="flex items-center gap-2 ml-auto">
                    <x-button type="button" variant="secondary" size="sm" onclick="document.getElementById('modal-detail-escalation').classList.add('hidden')">
                        Tutup
                    </x-button>
                    <x-button type="submit" variant="primary" size="sm">
                        Kirim Eskalasi ke Guru
                    </x-button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    window.openDetailEscalationModal = function (row, focusEscalation) {
        document.getElementById('detail-student-name').textContent = row.student_name || '-';
        document.getElementById('detail-date-subtitle').textContent = 'Tanggal Pemantauan: ' + (row.date || '-');
        document.getElementById('detail-student-nisn').textContent = 'NIS: ' + (row.student_nis || '-') + ' | NISN: ' + (row.student_nisn || '-');
        document.getElementById('detail-student-phone').textContent = row.student_phone || '-';

        document.getElementById('detail-major-name').textContent = row.major_name || '-';
        document.getElementById('detail-class-name').textContent = row.class_name || '-';

        document.getElementById('detail-company-name').textContent = row.company_name || '-';
        document.getElementById('detail-company-address').textContent = row.company_address || '-';
        document.getElementById('detail-company-pic').textContent = row.company_pic || '-';
        document.getElementById('detail-company-phone').textContent = row.company_phone || '-';
        document.getElementById('detail-company-schedule').textContent = (row.company_work_days || '-') + ' • Jam ' + (row.company_work_hours || '-');

        document.getElementById('detail-teacher-name').textContent = row.teacher_name || 'Belum Ditugaskan';
        document.getElementById('detail-teacher-nip').textContent = row.teacher_nip || '-';
        document.getElementById('detail-teacher-phone').textContent = row.teacher_phone || '-';

        document.getElementById('detail-checkin-status').textContent = row.checkin_status_label || '-';
        document.getElementById('detail-checkout-status').textContent = row.checkout_status_label || '-';
        document.getElementById('detail-journal-status').textContent = row.journal_status_label || '-';

        const journalSummaryEl = document.getElementById('detail-journal-summary');
        if (row.journal_summary) {
            journalSummaryEl.textContent = 'Ringkasan Jurnal: "' + row.journal_summary + '"';
            journalSummaryEl.classList.remove('hidden');
        } else {
            journalSummaryEl.classList.add('hidden');
        }

        const historyBox = document.getElementById('detail-escalation-history-box');
        const historyList = document.getElementById('detail-escalation-history-list');
        historyList.innerHTML = '';
        if (Array.isArray(row.escalations) && row.escalations.length > 0) {
            row.escalations.forEach(item => {
                const li = document.createElement('li');
                const waText = item.sent_whatsapp ? ' + WA' : '';
                const noteText = item.note ? ' — "' + item.note + '"' : '';
                li.textContent = '[' + item.time + '] Oleh ' + item.by + ' (Notif' + waText + '): ' + (item.missing_labels || []).join(', ') + noteText;
                historyList.appendChild(li);
            });
            historyBox.classList.remove('hidden');
        } else {
            historyBox.classList.add('hidden');
        }

        document.getElementById('escalate-spkl-id').value = row.spkl_id;
        document.getElementById('escalate-date').value = row.date;

        const missing = Array.isArray(row.missing_types) ? row.missing_types : [];
        document.getElementById('escalate-type-checkin').checked = missing.includes('checkin');
        document.getElementById('escalate-type-checkout').checked = missing.includes('checkout');
        document.getElementById('escalate-type-journal').checked = missing.includes('journal');
        document.getElementById('escalate-note').value = '';

        const waBtn = document.getElementById('detail-teacher-wa-btn');
        if (row.teacher_wa_url) {
            waBtn.href = row.teacher_wa_url;
            waBtn.classList.remove('hidden');
        } else {
            waBtn.classList.add('hidden');
        }

        document.getElementById('modal-detail-escalation').classList.remove('hidden');
        if (focusEscalation) {
            document.getElementById('escalate-note').focus();
        }
    };
</script>
@endpush
@endsection
