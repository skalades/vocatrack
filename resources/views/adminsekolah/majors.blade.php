@extends('layouts.app')

@section('page_title', 'Master Jurusan & Kelas')
@section('page_subtitle', 'Manajemen kompetensi keahlian dan rombongan belajar PKL')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Jurusan (Majors) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-bold text-slate-800">Daftar Jurusan Keahlian</h3>
                <p class="text-xs text-slate-500">Program keahlian vokasi</p>
            </div>
            <button onclick="document.getElementById('modal-add-major').classList.remove('hidden')" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs">
                + Tambah Jurusan
            </button>
        </div>

        <div class="divide-y divide-slate-100 text-xs">
            @foreach($majors as $m)
                <div class="py-3 flex items-center justify-between">
                    <div>
                        <span class="font-bold text-slate-900 block">{{ $m->name }}</span>
                        <span class="text-[11px] font-mono text-indigo-600 font-semibold block">Kode: {{ $m->code }}</span>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                        {{ $m->classes->count() }} Kelas
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Kelas (Classes) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-bold text-slate-800">Daftar Rombel / Kelas</h3>
                <p class="text-xs text-slate-500">Tingkat dan tahun ajaran</p>
            </div>
            <button onclick="document.getElementById('modal-add-class').classList.remove('hidden')" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs">
                + Tambah Kelas
            </button>
        </div>

        <div class="divide-y divide-slate-100 text-xs">
            @foreach($classes as $c)
                <div class="py-3 flex items-center justify-between">
                    <div>
                        <span class="font-bold text-slate-900 block">{{ $c->name }}</span>
                        <span class="text-[11px] text-slate-500 block">{{ $c->major->name }} &bull; TA: {{ $c->academic_year }}</span>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                        Tingkat {{ $c->grade_level }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

</div>

<!-- Modal Add Major -->
<div id="modal-add-major" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-sm rounded-3xl p-5 shadow-2xl">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-slate-900">Tambah Jurusan Baru</h3>
            <button type="button" onclick="document.getElementById('modal-add-major').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
        </div>
        <form action="{{ route('adminsekolah.majors.store') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Kode Singkat (Contoh: RPL, TKJ)</label>
                <input type="text" name="code" required placeholder="RPL" class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200 uppercase font-mono">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap Jurusan</label>
                <input type="text" name="name" required placeholder="Rekayasa Perangkat Lunak" class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
            </div>
            <div class="flex space-x-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-add-major').classList.add('hidden')" class="flex-1 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600">Batal</button>
                <button type="submit" class="flex-1 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Add Class -->
<div id="modal-add-class" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-sm rounded-3xl p-5 shadow-2xl">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-slate-900">Tambah Kelas Baru</h3>
            <button type="button" onclick="document.getElementById('modal-add-class').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
        </div>
        <form action="{{ route('adminsekolah.classes.store') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Jurusan</label>
                <select name="major_id" required class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                    @foreach($majors as $m)
                        <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->code }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Kelas (Contoh: XII RPL 1)</label>
                <input type="text" name="name" required placeholder="XII RPL 1" class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tingkat</label>
                    <select name="grade_level" class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                        <option value="XII" selected>XII</option>
                        <option value="XI">XI</option>
                        <option value="X">X</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tahun Ajaran</label>
                    <input type="text" name="academic_year" value="2026/2027" required class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                </div>
            </div>
            <div class="flex space-x-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-add-class').classList.add('hidden')" class="flex-1 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600">Batal</button>
                <button type="submit" class="flex-1 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
