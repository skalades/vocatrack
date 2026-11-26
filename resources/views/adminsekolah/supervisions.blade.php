@extends('layouts.app')

@section('page_title', 'Penugasan & Monitoring Supervisi')
@section('page_subtitle', 'Atur petugas Monitoring 1 & Monitoring 2 (termasuk Kajur & lintas DU/DI) serta rekap Berita Acara')

@section('content')
<div class="w-full space-y-6">

    <x-page-header
        title="Penugasan & Monitoring Supervisi PKL"
        subtitle="Kelola penugasan 2x monitoring lapangan (satu petugas dapat ditugaskan ke beberapa DU/DI sekaligus) tanpa mengubah guru pembimbing asli siswa."
        badge="ADMINISTRASI SEKOLAH"
    >
        <x-button type="button" variant="primary" size="sm" onclick="document.getElementById('modal-admin-assignment').classList.remove('hidden')">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span>Buat Penugasan Monitoring</span>
        </x-button>
    </x-page-header>

    @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-4 text-xs space-y-1">
            <div class="font-bold">Gagal menyimpan penugasan:</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card title="Surat Tugas Monitoring" :value="$stats['total_assignments']" subtitle="Penugasan khusus dibuat" color="indigo">
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>
            </x-slot:icon>
        </x-stat-card>
        <x-stat-card title="Target Menunggu" :value="$stats['pending_targets']" subtitle="Siswa belum dimonitoring petugas" :color="$stats['pending_targets'] > 0 ? 'amber' : 'emerald'">
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>
        <x-stat-card title="Target Selesai" :value="$stats['completed_targets']" subtitle="Laporan tugas telah masuk" color="emerald">
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>
        <x-stat-card title="Total Berita Acara" :value="$stats['total_visits']" subtitle="Seluruh kunjungan tercatat" color="blue">
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-4 shadow-xs">
        <form method="GET" action="{{ route('adminsekolah.supervisions') }}" class="flex flex-wrap items-center gap-3">
            <select name="pkl_period_id" onchange="this.form.submit()" class="text-xs font-semibold rounded-xl border-slate-200 bg-slate-50 focus:border-indigo-500 focus:ring-indigo-500">
                @foreach($periods as $p)
                    <option value="{{ $p->id }}" {{ (int) $selectedPeriodId === (int) $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>

            <select name="stage" onchange="this.form.submit()" class="text-xs font-semibold rounded-xl border-slate-200 bg-slate-50 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Semua Tahapan</option>
                <option value="monitoring_1" {{ request('stage') === 'monitoring_1' ? 'selected' : '' }}>Monitoring 1</option>
                <option value="monitoring_2" {{ request('stage') === 'monitoring_2' ? 'selected' : '' }}>Monitoring 2</option>
                <option value="insidental" {{ request('stage') === 'insidental' ? 'selected' : '' }}>Insidental</option>
            </select>

            <select name="teacher_id" onchange="this.form.submit()" class="text-xs font-semibold rounded-xl border-slate-200 bg-slate-50 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Semua Petugas / Guru</option>
                @foreach($teachers as $t)
                    <option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>{{ $t->user->name ?? '-' }}</option>
                @endforeach
            </select>

            <select name="company_id" onchange="this.form.submit()" class="text-xs font-semibold rounded-xl border-slate-200 bg-slate-50 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Semua DU/DI</option>
                @foreach($companies as $c)
                    <option value="{{ $c->id }}" {{ request('company_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>

            @if(request()->hasAny(['stage', 'teacher_id', 'company_id']))
                <a href="{{ route('adminsekolah.supervisions', ['pkl_period_id' => $selectedPeriodId]) }}" class="text-xs font-bold text-slate-500 hover:text-rose-600">Reset Filter</a>
            @endif
        </form>
    </div>

    <!-- Daftar Penugasan Monitoring -->
    <x-card title="Daftar Penugasan Monitoring Lapangan" noPadding>
        <x-supervision.assignments-list :assignments="$assignments" destroyRouteName="adminsekolah.supervisions.assignments.destroy" />
    </x-card>

    <!-- Tracker 2x Monitoring per DU/DI -->
    <x-card title="Tracker Ketercapaian 2x Monitoring per DU/DI" noPadding>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-5">Mitra DU/DI</th>
                        <th class="py-3 px-4 text-center">Siswa Aktif</th>
                        <th class="py-3 px-4">Monitoring 1</th>
                        <th class="py-3 px-4">Monitoring 2</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($matrix as $row)
                        <tr class="hover:bg-slate-50/60">
                            <td class="py-3 px-5">
                                <div class="font-bold text-slate-900">{{ $row['company']->name ?? '-' }}</div>
                                <div class="text-[11px] text-slate-400">{{ $row['company']->address ?? '-' }}</div>
                            </td>
                            <td class="py-3 px-4 text-center font-bold text-slate-700">{{ $row['students_count'] }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700">{{ $row['m1_done_count'] }} selesai</span>
                                @if($row['m1_assigned_count'] > 0)
                                    <span class="ml-1 px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-700">{{ $row['m1_assigned_count'] }} ditugaskan</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700">{{ $row['m2_done_count'] }} selesai</span>
                                @if($row['m2_assigned_count'] > 0)
                                    <span class="ml-1 px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-700">{{ $row['m2_assigned_count'] }} ditugaskan</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-500">Belum ada penempatan siswa PKL aktif pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <!-- Riwayat Kunjungan & Berita Acara -->
    <x-card title="Riwayat Kunjungan & Berita Acara Sekolah" noPadding>
        <x-supervision.visits-list :visits="$visits" pdfRouteName="adminsekolah.supervisions.pdf" />
    </x-card>

</div>

<x-supervision.assignment-modal
    modalId="modal-admin-assignment"
    :actionUrl="route('adminsekolah.supervisions.assignments.store')"
    :periods="$periods"
    :selectedPeriodId="$selectedPeriodId"
    :teachers="$teachers"
    :matrix="$matrix"
/>
@endsection
