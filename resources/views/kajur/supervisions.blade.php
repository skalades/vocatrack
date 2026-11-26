@extends('layouts.app')

@section('page_title', 'Supervisi & Penugasan Monitoring Jurusan')
@section('page_subtitle', 'Tugaskan petugas Monitoring 1 & 2 serta pantau Berita Acara kunjungan siswa jurusan ' . $major->name)

@section('content')
<div class="w-full space-y-6">

    <x-page-header
        title="Supervisi & Kunjungan Industri"
        subtitle="Kelola penugasan 2x monitoring untuk siswa jurusan {{ $major->name }} dan pantau hasil kunjungan lintas pembimbing."
        badge="SUPERVISI KAJUR"
    >
        <x-button type="button" variant="primary" size="sm" onclick="document.getElementById('modal-kajur-assignment').classList.remove('hidden')">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span>Tugaskan Monitoring Jurusan</span>
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

    <!-- Filter Bar -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-4 shadow-xs">
        <form method="GET" action="{{ route('kajur.supervisions') }}" class="flex flex-wrap items-center gap-3">
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

            <select name="company_id" onchange="this.form.submit()" class="text-xs font-semibold rounded-xl border-slate-200 bg-slate-50 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Semua DU/DI</option>
                @foreach($companies as $c)
                    <option value="{{ $c->id }}" {{ request('company_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>

            @if(request()->hasAny(['stage', 'company_id', 'teacher_id']))
                <a href="{{ route('kajur.supervisions', ['pkl_period_id' => $selectedPeriodId]) }}" class="text-xs font-bold text-slate-500 hover:text-rose-600">Reset Filter</a>
            @endif
        </form>
    </div>

    <!-- Penugasan Monitoring Jurusan -->
    <x-card title="Penugasan Monitoring Jurusan {{ $major->code }}" noPadding>
        <x-supervision.assignments-list :assignments="$assignments" destroyRouteName="kajur.supervisions.assignments.destroy" />
    </x-card>

    <!-- Riwayat Kunjungan & Berita Acara -->
    <x-card :title="'Daftar Kunjungan Supervisi (' . $supervisions->total() . ' Kunjungan Terdata)'" noPadding>
        <x-supervision.visits-list
            :visits="$supervisions"
            pdfRouteName="kajur.supervisions.pdf"
            emptyDescription="Belum ada logbook kunjungan monitoring yang mencakup siswa jurusan Anda."
        />
    </x-card>

</div>

<x-supervision.assignment-modal
    modalId="modal-kajur-assignment"
    :actionUrl="route('kajur.supervisions.assignments.store')"
    :periods="$periods"
    :selectedPeriodId="$selectedPeriodId"
    :teachers="$teachers"
    :matrix="$matrix"
    title="Tugaskan Monitoring Siswa Jurusan {{ $major->code }}"
    subtitle="Pilih guru atau Anda sendiri (Kajur) untuk melakukan Monitoring 1 / Monitoring 2 pada satu atau beberapa DU/DI."
/>
@endsection
