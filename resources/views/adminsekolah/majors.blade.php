@extends('layouts.app')

@section('page_title', 'Master Jurusan & Rombel Kelas')
@section('page_subtitle', 'Manajemen kompetensi keahlian dan rombongan belajar peserta PKL')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Enterprise Hero Banner -->
    <x-page-header 
        title="Jurusan Keahlian & Rombongan Belajar" 
        subtitle="Struktur program keahlian vokasi dan pembagian rombel kelas siswa peserta Praktik Kerja Lapangan."
        badge="MANAJEMEN SEKOLAH"
    >
        <x-button type="button" variant="secondary" size="sm" onclick="document.getElementById('modal-add-major').classList.remove('hidden')">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span>+ Tambah Jurusan</span>
        </x-button>
        <x-button type="button" variant="primary" size="sm" onclick="document.getElementById('modal-add-class').classList.remove('hidden')">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span>+ Tambah Rombel Kelas</span>
        </x-button>
    </x-page-header>

    <!-- Top KPI Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card 
            title="Program Keahlian (Jurusan)" 
            :value="$majors->count()" 
            subtitle="Konsentrasi keahlian aktif"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Rombongan Belajar (Kelas)" 
            :value="$classes->count()" 
            subtitle="Rombel terdaftar"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Tingkat XII (Kelas Akhir)" 
            :value="$classes->where('grade_level', 'XII')->count()" 
            subtitle="Peserta utama program PKL"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Tingkat XI & X" 
            :value="$classes->whereIn('grade_level', ['X', 'XI'])->count()" 
            subtitle="Kelas persiapan kejuruan"
            color="amber"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Dual Master Grid: Jurusan & Kelas -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Jurusan (Majors) Card -->
        <x-card 
            title="Daftar Jurusan Keahlian Vokasi" 
            :subtitle="$majors->count() . ' Program Keahlian Terdaftar'"
        >
            <x-slot:headerActions>
                <x-button type="button" variant="primary" size="xs" onclick="document.getElementById('modal-add-major').classList.remove('hidden')">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    + Jurusan
                </x-button>
            </x-slot:headerActions>

            @if($majors->isEmpty())
                <x-empty-state 
                    title="Belum Ada Jurusan Keahlian" 
                    description="Tambahkan konsentrasi keahlian seperti RPL, TKJ, Akuntansi, atau DKV terlebih dahulu."
                    actionLabel="+ Tambah Jurusan Sekarang"
                    actionClick="document.getElementById('modal-add-major').classList.remove('hidden')"
                />
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($majors as $m)
                        <div class="py-4 first:pt-0 last:pb-0 flex items-center justify-between gap-3 group">
                            <div class="flex items-center space-x-3.5 min-w-0">
                                <div class="w-11 h-11 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-700 font-black text-xs flex items-center justify-center shrink-0 shadow-xs shadow-indigo-600/10 group-hover:scale-105 transition-transform">
                                    {{ $m->code }}
                                </div>
                                <div class="min-w-0">
                                    <span class="font-bold text-slate-900 block truncate group-hover:text-indigo-600 transition-colors text-sm">
                                        {{ $m->name }}
                                    </span>
                                    <span class="text-[11px] font-mono text-slate-400 font-medium">
                                        Kode: {{ $m->code }} &bull; {{ $m->classes_count }} Rombel Kelas
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <x-badge variant="indigo" size="sm">
                                    {{ $m->classes_count }} Rombel
                                </x-badge>
                                <button type="button" 
                                        onclick="openEditMajorModal({{ json_encode([
                                            'id' => $m->id,
                                            'code' => $m->code,
                                            'name' => $m->name,
                                            'update_url' => route('adminsekolah.majors.update', $m),
                                        ]) }})"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors cursor-pointer"
                                        title="Ubah Nama/Kode Jurusan">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                @if($m->classes_count === 0)
                                    <form action="{{ route('adminsekolah.majors.destroy', $m) }}" method="POST" class="inline" onsubmit="return confirm('Hapus jurusan {{ $m->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer" title="Hapus Jurusan">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

        <!-- Kelas (Classes) Card -->
        <x-card 
            title="Daftar Rombongan Belajar (Rombel)" 
            :subtitle="$classes->count() . ' Rombel Kelas Aktif Terdata'"
        >
            <x-slot:headerActions>
                <x-button type="button" variant="primary" size="xs" onclick="document.getElementById('modal-add-class').classList.remove('hidden')">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    + Kelas
                </x-button>
            </x-slot:headerActions>

            @if($classes->isEmpty())
                <x-empty-state 
                    title="Belum Ada Rombel Kelas" 
                    description="Tambahkan rombel kelas agar peserta didik dapat dikelompokkan dengan rapi."
                    actionLabel="+ Tambah Kelas Sekarang"
                    actionClick="document.getElementById('modal-add-class').classList.remove('hidden')"
                />
            @else
                <div class="divide-y divide-slate-100 max-h-[500px] overflow-y-auto pr-1 custom-scrollbar">
                    @foreach($classes as $c)
                        <div class="py-3.5 first:pt-0 last:pb-0 flex items-center justify-between gap-3 group">
                            <div class="flex items-center space-x-3.5 min-w-0">
                                <div class="w-10 h-10 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-700 font-black text-xs flex items-center justify-center shrink-0 shadow-xs shadow-emerald-600/10 group-hover:scale-105 transition-transform">
                                    {{ $c->grade_level }}
                                </div>
                                <div class="min-w-0">
                                    <span class="font-bold text-slate-900 block truncate group-hover:text-emerald-700 transition-colors text-sm">
                                        {{ $c->name }}
                                    </span>
                                    <span class="text-[11px] text-slate-500 block truncate font-medium mt-0.5">
                                        {{ $c->major->name }} &bull; TA {{ $c->academic_year }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <x-badge variant="success" size="sm">
                                    Kelas {{ $c->grade_level }}
                                </x-badge>
                                <button type="button" 
                                        onclick="openEditClassModal({{ json_encode([
                                            'id' => $c->id,
                                            'major_id' => $c->major_id,
                                            'name' => $c->name,
                                            'grade_level' => $c->grade_level,
                                            'academic_year' => $c->academic_year,
                                            'update_url' => route('adminsekolah.classes.update', $c),
                                        ]) }})"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors cursor-pointer"
                                        title="Ubah Rombel Kelas">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <form action="{{ route('adminsekolah.classes.destroy', $c) }}" method="POST" class="inline" onsubmit="return confirm('Hapus rombel kelas {{ $c->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer" title="Hapus Kelas">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

    </div>

</div>

<!-- Modal Add Major -->
<div id="modal-add-major" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-2xl border border-slate-100 animate-in fade-in duration-150">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Tambah Jurusan Baru</h3>
                <p class="text-xs text-slate-500 font-medium">Program keahlian kurikulum SMK</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-add-major').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('adminsekolah.majors.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Singkatan / Kode Jurusan</label>
                <input type="text" name="code" required placeholder="Contoh: RPL, TKJ, AKL, DKV" 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-bold uppercase text-slate-900">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Lengkap Program Keahlian</label>
                <input type="text" name="name" required placeholder="Contoh: Rekayasa Perangkat Lunak" 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
            </div>
            <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-add-major').classList.add('hidden')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md">
                    Simpan Jurusan
                </x-button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Add Class -->
<div id="modal-add-class" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-2xl border border-slate-100 animate-in fade-in duration-150">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Tambah Rombel Kelas Baru</h3>
                <p class="text-xs text-slate-500 font-medium">Kelompok belajar siswa per angkatan</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-add-class').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('adminsekolah.classes.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilih Jurusan Keahlian</label>
                <select name="major_id" required 
                        class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                    @foreach($majors as $m)
                        <option value="{{ $m->id }}">{{ $m->code }} - {{ $m->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Tingkat Kelas</label>
                    <select name="grade_level" 
                            class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                        <option value="XII">XII (Dua Belas)</option>
                        <option value="XI">XI (Sebelas)</option>
                        <option value="X">X (Sepuluh)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Tahun Ajaran</label>
                    <input type="text" name="academic_year" required value="2026/2027" placeholder="2026/2027" 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-mono text-slate-900">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Rombel / Kelas</label>
                <input type="text" name="name" required placeholder="Contoh: XII RPL 1" 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
            </div>
            <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-add-class').classList.add('hidden')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md">
                    Simpan Kelas
                </x-button>
            </div>
        </form>
    </div>
</div>
<!-- Modal Edit Major -->
<div id="modal-edit-major" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-2xl border border-slate-100 animate-in fade-in duration-150">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Edit Jurusan</h3>
                <p class="text-xs text-slate-500 font-medium">Perbarui kode atau nama jurusan</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-edit-major').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="edit-major-form" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Kode Singkatan Jurusan</label>
                <input type="text" id="edit-major-code" name="code" required 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-mono uppercase text-slate-900">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Lengkap Program Keahlian</label>
                <input type="text" id="edit-major-name" name="name" required 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
            </div>
            <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-edit-major').classList.add('hidden')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md">
                    Simpan Perubahan
                </x-button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Class -->
<div id="modal-edit-class" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-2xl border border-slate-100 animate-in fade-in duration-150">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Edit Rombel Kelas</h3>
                <p class="text-xs text-slate-500 font-medium">Perbarui informasi rombongan belajar</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-edit-class').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="edit-class-form" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Jurusan Keahlian</label>
                <select id="edit-class-major-id" name="major_id" required 
                        class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                    @foreach($majors as $m)
                        <option value="{{ $m->id }}">{{ $m->code }} - {{ $m->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Tingkat Kelas</label>
                    <select id="edit-class-grade-level" name="grade_level" 
                            class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                        <option value="XII">XII (Dua Belas)</option>
                        <option value="XI">XI (Sebelas)</option>
                        <option value="X">X (Sepuluh)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Tahun Ajaran</label>
                    <input type="text" id="edit-class-academic-year" name="academic_year" required 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-mono text-slate-900">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Rombel / Kelas</label>
                <input type="text" id="edit-class-name" name="name" required 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
            </div>
            <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-edit-class').classList.add('hidden')">
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
    function openEditMajorModal(data) {
        document.getElementById('edit-major-form').action = data.update_url;
        document.getElementById('edit-major-code').value = data.code;
        document.getElementById('edit-major-name').value = data.name;
        document.getElementById('modal-edit-major').classList.remove('hidden');
    }

    function openEditClassModal(data) {
        document.getElementById('edit-class-form').action = data.update_url;
        document.getElementById('edit-class-major-id').value = data.major_id;
        document.getElementById('edit-class-grade-level').value = data.grade_level;
        document.getElementById('edit-class-academic-year').value = data.academic_year;
        document.getElementById('edit-class-name').value = data.name;
        document.getElementById('modal-edit-class').classList.remove('hidden');
    }
</script>
@endpush
@endsection
