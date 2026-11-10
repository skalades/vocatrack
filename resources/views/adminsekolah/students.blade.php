@extends('layouts.app')

@section('page_title', 'Master Data Siswa')
@section('page_subtitle', 'Kelola data siswa dan impor massal dari file CSV atau Excel')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h3 class="text-base font-bold text-slate-800">Daftar Siswa Sekolah</h3>
            <p class="text-xs text-slate-500">Siswa yang terdaftar dalam basis data sekolah</p>
        </div>
        <div class="flex items-center space-x-2">
            <button onclick="document.getElementById('modal-import-student').classList.remove('hidden')" class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs flex items-center space-x-1.5 shadow-2xs">
                <span>📥 Impor CSV / Excel</span>
            </button>
            <button onclick="document.getElementById('modal-add-student').classList.remove('hidden')" class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs flex items-center space-x-1.5 shadow-md shadow-indigo-200">
                <span>+ Tambah Siswa</span>
            </button>
        </div>
    </div>

    @if(session('failedRows'))
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs space-y-2">
            <h4 class="font-bold">Daftar Baris Impor yang Gagal / Dilewati:</h4>
            <ul class="list-disc list-inside space-y-1 font-mono text-[11px]">
                @foreach(session('failedRows') as $failed)
                    <li>{{ $failed }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Students Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200/80 font-bold uppercase text-[10px]">
                    <tr>
                        <th class="py-3 px-5">Nama Siswa</th>
                        <th class="py-3 px-4">NISN / NIS</th>
                        <th class="py-3 px-4">Kelas & Jurusan</th>
                        <th class="py-3 px-4">Email Login</th>
                        <th class="py-3 px-4">Status PKL</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($students as $st)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-5">
                                <span class="font-bold text-slate-900 block">{{ $st->user->name }}</span>
                                <span class="text-[10px] text-slate-400 block">{{ $st->user->profile->gender === 'L' ? 'Laki-Laki' : 'Perempuan' }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-mono">
                                <span class="block font-bold text-slate-800">{{ $st->nisn }}</span>
                                <span class="text-[10px] text-slate-400 block">NIS: {{ $st->nis }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-slate-800 block">{{ $st->class->name }}</span>
                                <span class="text-[11px] text-slate-500 block">{{ $st->class->major->code }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[11px]">{{ $st->user->email }}</td>
                            <td class="py-3.5 px-4">
                                @if($st->activePkl)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        {{ $st->activePkl->company->name }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500">
                                        Belum Diplot
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-slate-400">Belum ada siswa terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $students->links() }}
        </div>
    </div>

</div>

<!-- Modal Add Student -->
<div id="modal-add-student" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-3xl p-5 shadow-2xl">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-slate-900">Tambah Siswa Baru</h3>
            <button type="button" onclick="document.getElementById('modal-add-student').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
        </div>
        <form action="{{ route('adminsekolah.students.store') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Rombel / Kelas</label>
                <select name="class_id" required class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->major->name }})</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">NISN (10 Digit)</label>
                    <input type="text" name="nisn" required placeholder="0061234567" class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200 font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">NIS</label>
                    <input type="text" name="nis" required placeholder="202401" class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200 font-mono">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap Siswa</label>
                <input type="text" name="name" required placeholder="Nama Siswa" class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Jenis Kelamin</label>
                    <select name="gender" class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                        <option value="L">Laki-Laki (L)</option>
                        <option value="P">Perempuan (P)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Telepon</label>
                    <input type="text" name="phone" placeholder="08..." class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Email Siswa (Untuk Login)</label>
                <input type="email" name="email" required placeholder="siswa@sekolah.sch.id" class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                <span class="text-[10px] text-slate-400 block mt-0.5">Password awal otomatis diset: <code>password</code></span>
            </div>
            <div class="flex space-x-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-add-student').classList.add('hidden')" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600">Batal</button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Import Students -->
<div id="modal-import-student" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-3xl p-5 shadow-2xl">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-slate-900">Impor Data Siswa Massal</h3>
            <button type="button" onclick="document.getElementById('modal-import-student').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
        </div>
        <form action="{{ route('adminsekolah.students.import') }}" method="POST" enctype="multipart/form-data" class="space-y-3.5">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Target Kelas Siswa</label>
                <select name="class_id" required class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->major->name }})</option>
                    @endforeach
                </select>
            </div>

            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 text-slate-600 text-[11px] space-y-1">
                <p class="font-bold text-slate-800">Format Kolom File (CSV / Excel):</p>
                <p class="font-mono text-[10px]">Kolom 1: NIS | Kolom 2: NISN | Kolom 3: Nama | Kolom 4: Email | Kolom 5: Gender (L/P)</p>
                <p class="text-[10px] text-slate-400 mt-1">Duplikasi NISN atau email otomatis dilaporkan tanpa membatalkan baris yang valid.</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Pilih File (.csv, .xlsx)</label>
                <input type="file" name="file" required accept=".csv,.xlsx,.txt" class="w-full text-xs text-slate-600 file:mr-2 file:py-2 file:px-3 file:rounded-xl file:border-0 file:bg-slate-100 file:text-slate-700 file:font-semibold">
            </div>

            <div class="flex space-x-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-import-student').classList.add('hidden')" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600">Batal</button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold">Mulai Impor</button>
            </div>
        </form>
    </div>
</div>
@endsection
