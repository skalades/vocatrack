@extends('layouts.app')

@section('page_title', 'Master Data Siswa')
@section('page_subtitle', 'Kelola basis data siswa dan impor massal dari file CSV atau Excel')

@section('content')
<div class="space-y-6">

    <x-page-header 
        title="Daftar Siswa Sekolah" 
        subtitle="Manajemen identitas siswa, NISN, penempatan rombel kelas, dan status PKL."
    >
        <x-button type="button" variant="secondary" size="sm" onclick="document.getElementById('modal-import-student').classList.remove('hidden')">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Impor CSV / Excel
        </x-button>
        <x-button type="button" variant="primary" size="sm" onclick="document.getElementById('modal-add-student').classList.remove('hidden')">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Tambah Siswa Baru
        </x-button>
    </x-page-header>

    @if(session('failedRows'))
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200/80 text-amber-900 text-xs shadow-2xs space-y-2">
            <div class="flex items-center gap-2 font-bold">
                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>Baris Impor yang Gagal / Dilewati:</span>
            </div>
            <ul class="list-disc list-inside space-y-1 font-mono text-[11px] text-amber-800 bg-white/60 p-2.5 rounded-xl border border-amber-100">
                @foreach(session('failedRows') as $failed)
                    <li>{{ $failed }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Students Table Card -->
    <x-card noPadding>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6">Nama Siswa</th>
                        <th class="py-3.5 px-4">NISN / NIS</th>
                        <th class="py-3.5 px-4">Kelas & Jurusan</th>
                        <th class="py-3.5 px-4">Email Login</th>
                        <th class="py-3.5 px-6">Status PKL</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($students as $st)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3.5 px-6">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-700 font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ substr($st->user->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <span class="font-bold text-slate-900 block truncate">{{ $st->user->name }}</span>
                                        <span class="text-[10px] text-slate-400 font-medium">{{ $st->user->profile->gender === 'L' ? 'Laki-Laki' : 'Perempuan' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-slate-800 font-mono block">{{ $st->nisn }}</span>
                                <span class="text-[10px] text-slate-400 font-mono block">NIS: {{ $st->nis }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-slate-900 block">{{ $st->class->name }}</span>
                                <span class="text-[11px] text-slate-500 font-medium block">{{ $st->class->major->code }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-600">
                                {{ $st->user->email }}
                            </td>
                            <td class="py-3.5 px-6">
                                @if($st->activePkl)
                                    <x-badge variant="success" size="xs">
                                        {{ $st->activePkl->company->name }}
                                    </x-badge>
                                @else
                                    <x-badge variant="neutral" size="xs">
                                        Belum Diplot
                                    </x-badge>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12">
                                <x-empty-state 
                                    title="Belum Ada Siswa Terdaftar" 
                                    description="Tambahkan data siswa secara manual atau impor sekaligus via file Excel/CSV."
                                    actionLabel="+ Tambah Siswa Sekarang"
                                    actionClick="document.getElementById('modal-add-student').classList.remove('hidden')"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($students->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $students->links() }}
            </div>
        @endif
    </x-card>

</div>

<!-- Modal Add Student -->
<div id="modal-add-student" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in fade-in duration-150">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Tambah Siswa Baru</h3>
                <p class="text-xs text-slate-500 font-medium">Buat akun siswa dan masukkan data pokok</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-add-student').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('adminsekolah.students.store') }}" method="POST" class="space-y-3.5">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Rombel / Kelas</label>
                <select name="class_id" required 
                        class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->major->name }})</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">NISN (10 Digit)</label>
                    <input type="text" name="nisn" required placeholder="0061234567" 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-mono text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">NIS</label>
                    <input type="text" name="nis" required placeholder="202401" 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-mono text-slate-900">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Lengkap Siswa</label>
                <input type="text" name="name" required placeholder="Nama Siswa Lengkap" 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Jenis Kelamin</label>
                    <select name="gender" 
                            class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                        <option value="L">Laki-Laki (L)</option>
                        <option value="P">Perempuan (P)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Nomor Telepon / WhatsApp</label>
                    <input type="text" name="phone" placeholder="08123456789" 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Email (Username Login)</label>
                <input type="email" name="email" required placeholder="siswa@smk.sch.id" 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                <span class="text-[11px] text-slate-400 block mt-1 font-medium">Kata sandi default awal: <code class="text-slate-700 font-mono font-bold">password</code></span>
            </div>
            <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-add-student').classList.add('hidden')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md">
                    Simpan Siswa
                </x-button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Import Students -->
<div id="modal-import-student" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in fade-in duration-150">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Impor Siswa Massal</h3>
                <p class="text-xs text-slate-500 font-medium">Unggah file format .csv atau .xlsx</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-import-student').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('adminsekolah.students.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Target Kelas Siswa</label>
                <select name="class_id" required 
                        class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->major->name }})</option>
                    @endforeach
                </select>
            </div>

            <div class="p-4 rounded-2xl bg-indigo-50/60 border border-indigo-100 text-slate-700 text-xs space-y-1.5">
                <p class="font-bold text-indigo-950 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Format Urutan Kolom Spreadsheet:
                </p>
                <p class="font-mono text-[11px] text-indigo-900 bg-white/80 p-2 rounded-lg border border-indigo-100">
                    Kolom 1: NIS | Kolom 2: NISN | Kolom 3: Nama | Kolom 4: Email | Kolom 5: Gender (L/P)
                </p>
                <p class="text-[11px] text-slate-500 leading-relaxed">
                    Sistem otomatis mendeteksi baris header dan memproses baris valid secara terpisah jika terdapat baris duplikat.
                </p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilih File Spreadsheet (.csv, .xlsx)</label>
                <input type="file" name="file" required accept=".csv,.xlsx,.txt" 
                       class="w-full text-xs text-slate-600 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:bg-indigo-50 file:text-indigo-700 file:font-bold hover:file:bg-indigo-100 file:transition-colors cursor-pointer border border-slate-200 rounded-2xl p-2 bg-slate-50/50">
            </div>

            <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-import-student').classList.add('hidden')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md">
                    Mulai Impor Siswa
                </x-button>
            </div>
        </form>
    </div>
</div>
@endsection
