@extends('layouts.app')

@section('page_title', 'Master Data Siswa')
@section('page_subtitle', 'Kelola basis data siswa dan impor massal dari file CSV atau Excel')

@section('content')
<div class="w-full space-y-6">

    <!-- Enterprise Hero Banner -->
    <x-page-header 
        title="Daftar Siswa Sekolah" 
        subtitle="Manajemen identitas siswa, nomor induk (NISN/NIS), penempatan rombel kelas, dan status keaktifan penugasan PKL."
        badge="MANAJEMEN SEKOLAH"
    >
        <x-button type="button" variant="secondary" size="sm" onclick="document.getElementById('modal-import-student').classList.remove('hidden')">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            <span>Impor Spreadsheet</span>
        </x-button>
        <x-button type="button" variant="primary" size="sm" onclick="document.getElementById('modal-add-student').classList.remove('hidden')">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span>+ Tambah Siswa Baru</span>
        </x-button>
    </x-page-header>

    <!-- Top KPI Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card 
            title="Total Siswa Terdata" 
            :value="$totalStudents" 
            subtitle="Akun peserta didik aktif di sekolah"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Sudah Terplot ke DU/DI" 
            :value="$totalPlotted" 
            subtitle="Memiliki penugasan PKL aktif"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Belum Memiliki DU/DI" 
            :value="$totalUnplotted" 
            subtitle="Menunggu alokasi plotting PKL"
            :color="$totalUnplotted > 0 ? 'amber' : 'emerald'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Rombongan Belajar (Rombel)" 
            :value="$classes->count() . ' Kelas'" 
            subtitle="Total rombel terdaftar"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Alert for Failed Import Rows -->
    @if(session('failedRows'))
        <div class="p-5 rounded-2xl bg-amber-50 border border-amber-200/80 text-amber-900 text-xs shadow-2xs space-y-2">
            <div class="flex items-center gap-2 font-bold text-sm">
                <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>Catatan Baris Impor Spreadsheet yang Dilewati:</span>
            </div>
            <ul class="list-disc list-inside space-y-1 font-mono text-[11px] text-amber-800 bg-white/70 p-3 rounded-xl border border-amber-100 max-h-40 overflow-y-auto">
                @foreach(session('failedRows') as $failed)
                    <li>{{ $failed }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Students Table Card -->
    <x-card noPadding>
        <!-- Table Search & Filter Toolbar -->
        <div class="p-4 sm:p-5 bg-white border-b border-slate-100 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="relative flex-1 max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" id="student-search-input" onkeyup="filterStudentsTable()" placeholder="Cari nama siswa, NISN, atau kelas..." 
                       class="w-full text-xs font-semibold pl-9.5 pr-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all">
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-500 font-semibold hidden md:inline">
                    Menampilkan {{ $students->count() }} dari {{ $students->total() }} siswa
                </span>
            </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs min-w-[940px]" id="students-table">
                <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6 min-w-[210px]">Identitas Siswa</th>
                        <th class="py-3.5 px-4 min-w-[120px]">NISN / NIS</th>
                        <th class="py-3.5 px-4 min-w-[140px]">Kelas & Jurusan</th>
                        <th class="py-3.5 px-4 min-w-[160px]">Akun Login</th>
                        <th class="py-3.5 px-6 min-w-[140px]">Status Plotting PKL</th>
                        <th class="py-3.5 px-4 text-right table-sticky-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium" id="students-tbody">
                    @forelse($students as $st)
                        <tr class="hover:bg-slate-50/70 transition-colors student-row group">
                            <td class="py-3.5 px-6 min-w-[210px]">
                                <div class="flex items-center space-x-3.5">
                                    <div class="w-9 h-9 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-700 font-black text-xs flex items-center justify-center shrink-0 shadow-xs shadow-indigo-600/10">
                                        {{ substr($st->user->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <span class="font-bold text-slate-900 block truncate text-sm student-name" title="{{ $st->user->name }}">{{ $st->user->name }}</span>
                                        <span class="text-[11px] text-slate-400 font-medium">
                                            {{ $st->user->profile->gender === 'L' ? 'Laki-Laki' : 'Perempuan' }} 
                                            @if($st->user->profile->phone) &bull; {{ $st->user->profile->phone }} @endif
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 min-w-[120px]">
                                <span class="font-bold text-slate-800 font-mono block student-nisn">{{ $st->nisn }}</span>
                                <span class="text-[10px] text-slate-400 font-mono block">NIS: {{ $st->nis }}</span>
                            </td>
                            <td class="py-3.5 px-4 min-w-[140px]">
                                <span class="font-bold text-slate-900 block student-class">{{ $st->class->name }}</span>
                                <span class="text-[11px] text-slate-500 font-medium block truncate" title="{{ $st->class->major->name }}">{{ $st->class->major->name }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-600 min-w-[160px]">
                                <span class="block truncate" title="{{ $st->user->email }}">{{ $st->user->email }}</span>
                            </td>
                            <td class="py-3.5 px-6 min-w-[140px]">
                                @if($st->activePkl)
                                    <div class="flex flex-col items-start gap-1">
                                        <x-badge variant="success" size="xs">
                                            {{ $st->activePkl->company->name }}
                                        </x-badge>
                                        <span class="text-[10px] text-slate-400 font-medium">
                                            Penempatan Aktif
                                        </span>
                                    </div>
                                @else
                                    <x-badge variant="neutral" size="xs">
                                        Belum Diplot
                                    </x-badge>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-1.5 table-sticky-right">
                                @if($st->parent_monitoring_url)
                                    <a href="{{ $st->parent_monitoring_url }}" target="_blank" class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-[11px] font-bold border border-indigo-200 transition-colors inline-flex items-center gap-1 cursor-pointer" title="Lihat Portal Monitoring Orang Tua (Tanpa Login)">
                                        <svg class="w-3 h-3 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <span>Link Ortu</span>
                                    </a>
                                @endif
                                <button type="button" 
                                        onclick="openEditStudentModal({{ json_encode([
                                            'id' => $st->id,
                                            'name' => $st->user->name,
                                            'email' => $st->user->email,
                                            'nis' => $st->nis,
                                            'nisn' => $st->nisn,
                                            'class_id' => $st->class_id,
                                            'gender' => $st->user->profile->gender ?? 'L',
                                            'phone' => $st->user->profile->phone ?? '',
                                            'update_url' => route('adminsekolah.students.update', $st),
                                        ]) }})"
                                        class="px-2.5 py-1 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-700 text-[11px] font-bold border border-slate-200 transition-colors cursor-pointer inline-flex items-center gap-1"
                                        title="Ubah data identitas siswa">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    <span>Edit</span>
                                </button>
                                <form action="{{ route('impersonate.start', $st->user) }}" method="POST" class="inline" onsubmit="return confirm('Masuk sebagai {{ $st->user->name }} (Mode Impersonasi)?')">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 text-[11px] font-bold border border-amber-200 transition-colors cursor-pointer" title="Masuk sebagai siswa ini untuk inspeksi sistem">
                                        Login As
                                    </button>
                                </form>
                                @if(!$st->activePkl)
                                    <form action="{{ route('adminsekolah.students.destroy', $st) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data siswa {{ $st->user->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-[11px] font-bold border border-rose-200 transition-colors cursor-pointer" title="Hapus akun dan profil siswa">
                                            Hapus
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12">
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
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Target Rombel Kelas</label>
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

<!-- Modal Edit Student -->
<div id="modal-edit-student" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in fade-in duration-150">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Edit Data Siswa</h3>
                <p class="text-xs text-slate-500 mt-0.5">Perbarui biodata siswa dan rombongan belajar</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-edit-student').classList.add('hidden')" class="p-1 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="edit-student-form" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Lengkap Siswa</label>
                <input type="text" id="edit-student-name" name="name" required 
                       class="w-full text-xs font-semibold px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-slate-800">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">NIS (Sekolah)</label>
                    <input type="text" id="edit-student-nis" name="nis" required 
                           class="w-full text-xs font-mono font-semibold px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-slate-800">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">NISN (Nasional)</label>
                    <input type="text" id="edit-student-nisn" name="nisn" required 
                           class="w-full text-xs font-mono font-semibold px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-slate-800">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Rombongan Belajar (Kelas)</label>
                <select id="edit-student-class-id" name="class_id" required 
                        class="w-full text-xs font-semibold px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-slate-800">
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->major->name }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Email (Akun Login)</label>
                <input type="email" id="edit-student-email" name="email" required 
                       class="w-full text-xs font-mono font-semibold px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-slate-800">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Jenis Kelamin</label>
                    <select id="edit-student-gender" name="gender" required 
                            class="w-full text-xs font-semibold px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-slate-800">
                        <option value="L">Laki-Laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Nomor WhatsApp / HP</label>
                    <input type="text" id="edit-student-phone" name="phone" placeholder="08..." 
                           class="w-full text-xs font-mono font-semibold px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-slate-800">
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-edit-student').classList.add('hidden')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md">
                    Simpan Perubahan
                </x-button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function filterStudentsTable() {
        const query = document.getElementById('student-search-input').value.toLowerCase();
        const rows = document.querySelectorAll('.student-row');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    }

    function openEditStudentModal(data) {
        document.getElementById('edit-student-form').action = data.update_url;
        document.getElementById('edit-student-name').value = data.name;
        document.getElementById('edit-student-email').value = data.email;
        document.getElementById('edit-student-nis').value = data.nis;
        document.getElementById('edit-student-nisn').value = data.nisn;
        document.getElementById('edit-student-class-id').value = data.class_id;
        document.getElementById('edit-student-gender').value = data.gender;
        document.getElementById('edit-student-phone').value = data.phone || '';
        document.getElementById('modal-edit-student').classList.remove('hidden');
    }
</script>
@endpush
@endsection
