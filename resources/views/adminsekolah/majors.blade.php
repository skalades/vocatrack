@extends('layouts.app')

@section('page_title', 'Master Jurusan & Rombel Kelas')
@section('page_subtitle', 'Manajemen kompetensi keahlian dan rombongan belajar peserta PKL')

@section('content')
<div class="w-full space-y-6">

    <!-- Enterprise Hero Banner -->
    <x-page-header 
        title="Jurusan Keahlian & Rombongan Belajar" 
        subtitle="Struktur program keahlian vokasi dan pembagian rombel kelas siswa peserta Praktik Kerja Lapangan."
        badge="MANAJEMEN SEKOLAH"
    >
        <x-button type="button" variant="outline" size="sm" onclick="document.getElementById('modal-promote-classes').classList.remove('hidden')" class="border-indigo-200 text-indigo-700 hover:bg-indigo-50">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            <span>Kenaikan Kelas & Kelulusan</span>
        </x-button>
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
            title="Tingkat XII & XIII (Peserta PKL)" 
            :value="$classes->whereIn('grade_level', ['XII', 'XIII'])->count()" 
            subtitle="Peserta utama program PKL"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Program 4 Tahun (Geologi dll)" 
            :value="$majors->where('program_duration_years', 4)->count()" 
            subtitle="Program durasi magang s.d. 8-10 bln"
            color="amber"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
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
                                    <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                                        <span class="text-[11px] font-mono text-slate-400 font-medium">
                                            Kode: {{ $m->code }} &bull; {{ $m->classes_count }} Rombel Kelas
                                        </span>
                                        <span class="text-slate-300">&bull;</span>
                                        @if($m->isFourYearProgram())
                                            <span class="inline-flex items-center font-bold text-purple-700 bg-purple-50 px-2 py-0.5 rounded-md border border-purple-200/80 text-[11px]">
                                                4 Tahun (Magang {{ $m->default_pkl_duration_months }} Bln)
                                            </span>
                                        @else
                                            <span class="inline-flex items-center font-medium text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200 text-[11px]">
                                                3 Tahun (6 Bln)
                                            </span>
                                        @endif
                                        <span class="text-slate-300">&bull;</span>
                                        @if($m->headTeacher)
                                            <span class="inline-flex items-center gap-1 font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200/60 text-[11px]">
                                                <svg class="w-3 h-3 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                Kajur: {{ $m->headTeacher->user->name }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center text-slate-400 italic text-[11px]">
                                                Kajur: Belum Ditunjuk
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button type="button" 
                                        onclick="openMajorCompetenciesModal({{ json_encode([
                                            'id' => $m->id,
                                            'code' => $m->code,
                                            'name' => $m->name,
                                            'store_url' => route('adminsekolah.majors.competencies.store', $m),
                                            'competencies' => $m->learningObjectives->map(fn($lo) => [
                                                'id' => $lo->id,
                                                'code' => $lo->code,
                                                'title' => $lo->title,
                                                'element_name' => $lo->element_name,
                                                'target_hours' => $lo->target_hours,
                                                'is_active' => $lo->is_active,
                                                'delete_url' => route('adminsekolah.competencies.destroy', $lo),
                                            ]),
                                        ]) }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200/80 hover:bg-emerald-100 transition-colors cursor-pointer"
                                        title="Kelola Capaian Pembelajaran (CP) Jurusan {{ $m->code }}">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span>{{ $m->learning_objectives_count }} CP</span>
                                </button>
                                <x-badge variant="indigo" size="sm">
                                    {{ $m->classes_count }} Rombel
                                </x-badge>
                                <button type="button" 
                                        onclick="openEditMajorModal({{ json_encode([
                                            'id' => $m->id,
                                            'code' => $m->code,
                                            'name' => $m->name,
                                            'program_duration_years' => $m->program_duration_years ?? 3,
                                            'default_pkl_duration_months' => $m->default_pkl_duration_months ?? 6,
                                            'head_teacher_id' => $m->head_teacher_id,
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
                                    <div class="mt-1">
                                        @if($c->homeroomTeacher)
                                            <span class="inline-flex items-center gap-1 font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200/60 text-[11px]">
                                                <svg class="w-3 h-3 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                Wali Kelas: {{ $c->homeroomTeacher->user->name }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center text-slate-400 italic text-[11px]">
                                                Wali Kelas: Belum Ditunjuk
                                            </span>
                                        @endif
                                    </div>
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
                                            'homeroom_teacher_id' => $c->homeroom_teacher_id,
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
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Lama Program</label>
                    <select name="program_duration_years" 
                            class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                        <option value="3">3 Tahun (Reguler)</option>
                        <option value="4">4 Tahun (Geologi / Khusus)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Batas Magang</label>
                    <div class="relative">
                        <input type="number" name="default_pkl_duration_months" value="6" min="1" max="24"
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-mono text-slate-900">
                        <span class="absolute right-3.5 top-2.5 text-xs text-slate-400 font-medium">Bulan</span>
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Kepala Program Keahlian (Kajur)</label>
                <select name="head_teacher_id" 
                        class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                    <option value="">-- Belum Ditunjuk / Pilih Nanti --</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}">{{ $t->user->name }} (NIP: {{ $t->nip ?? '-' }})</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-400 mt-1">Guru yang ditunjuk otomatis memiliki akses ke panel Kepala Jurusan.</p>
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
                        <option value="XIII">XIII (Tiga Belas - Khusus 4 Tahun)</option>
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
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Wali Kelas (Opsional)</label>
                <select name="homeroom_teacher_id" 
                        class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                    <option value="">-- Belum Ditunjuk / Pilih Nanti --</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}">{{ $t->user->name }} (NIP: {{ $t->nip ?? '-' }})</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-400 mt-1">Guru yang ditunjuk otomatis memiliki akses ke panel Wali Kelas.</p>
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
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Lama Program</label>
                    <select id="edit-major-duration" name="program_duration_years" 
                            class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                        <option value="3">3 Tahun (Reguler)</option>
                        <option value="4">4 Tahun (Geologi / Khusus)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Batas Magang</label>
                    <div class="relative">
                        <input type="number" id="edit-major-pkl-duration" name="default_pkl_duration_months" min="1" max="24"
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-mono text-slate-900">
                        <span class="absolute right-3.5 top-2.5 text-xs text-slate-400 font-medium">Bulan</span>
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Kepala Program Keahlian (Kajur)</label>
                <select id="edit-major-head-teacher-id" name="head_teacher_id" 
                        class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                    <option value="">-- Belum Ditunjuk / Kosongkan --</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}">{{ $t->user->name }} (NIP: {{ $t->nip ?? '-' }})</option>
                    @endforeach
                </select>
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
                        <option value="XIII">XIII (Tiga Belas - Khusus 4 Tahun)</option>
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
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Wali Kelas</label>
                <select id="edit-class-homeroom-teacher-id" name="homeroom_teacher_id" 
                        class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                    <option value="">-- Belum Ditunjuk / Kosongkan --</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}">{{ $t->user->name }} (NIP: {{ $t->nip ?? '-' }})</option>
                    @endforeach
                </select>
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

<!-- Modal Kelola Capaian Pembelajaran Jurusan (Admin Sekolah) -->
<div id="modal-major-competencies" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-2xl w-full p-6 space-y-5 shadow-2xl border border-slate-200 max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 shrink-0">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 font-black text-sm flex items-center justify-center shrink-0">
                    <span id="cp-modal-badge">CP</span>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 tracking-tight" id="cp-modal-title">Capaian Pembelajaran Jurusan</h3>
                    <p class="text-xs text-slate-500 font-medium">Standarisasi tujuan pembelajaran & elemen kompetensi PKL</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modal-major-competencies').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="overflow-y-auto space-y-4 flex-1 pr-1">
            <!-- List of Existing CPs -->
            <div>
                <span class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Daftar Capaian Pembelajaran Terdaftar:</span>
                <div id="cp-modal-list" class="space-y-2">
                    <!-- Populated dynamically via JS -->
                </div>
                <div id="cp-modal-empty" class="hidden p-4 rounded-2xl bg-slate-50 text-center text-xs text-slate-400 border border-slate-100">
                    Belum ada Capaian Pembelajaran terdaftar untuk jurusan ini.
                </div>
            </div>

            <!-- Form Tambah CP Baru -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3">
                <span class="block text-xs font-extrabold text-slate-900 uppercase tracking-wider">+ Tambah Capaian Pembelajaran Baru</span>
                <form id="cp-store-form" method="POST" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Kode CP *</label>
                            <input type="text" name="code" required placeholder="Contoh: CP-01" class="w-full text-xs px-3 py-2 rounded-xl bg-white border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-mono text-slate-900">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Judul Capaian Pembelajaran *</label>
                            <input type="text" name="title" required placeholder="Contoh: Pemetaan Geologi Lapangan" class="w-full text-xs px-3 py-2 rounded-xl bg-white border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Elemen Kurikulum Merdeka</label>
                            <input type="text" name="element_name" placeholder="Contoh: Geologi Lapangan & Navigasi" class="w-full text-xs px-3 py-2 rounded-xl bg-white border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Target Durasi Jam (JP)</label>
                            <input type="number" name="target_hours" value="0" min="0" class="w-full text-xs px-3 py-2 rounded-xl bg-white border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-mono text-slate-900">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Deskripsi & Indikator Kinerja</label>
                        <textarea name="description" rows="2" placeholder="Uraikan indikator keberhasilan capaian kompetensi ini..." class="w-full text-xs px-3 py-2 rounded-xl bg-white border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 text-slate-900"></textarea>
                    </div>
                    <div class="flex justify-end pt-1">
                        <x-button type="submit" variant="primary" size="sm">
                            + Simpan Capaian Pembelajaran
                        </x-button>
                    </div>
                </form>
            </div>
        </div>

        <div class="flex justify-end pt-3 border-t border-slate-100 shrink-0">
            <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-major-competencies').classList.add('hidden')">
                Tutup
            </x-button>
        </div>
    </div>
</div>

<!-- Modal Kenaikan Kelas & Kelulusan Otomatis (Rollover) -->
<div id="modal-promote-classes" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-2xl rounded-3xl p-6 shadow-2xl border border-slate-100 max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100 shrink-0">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Kenaikan Kelas & Kelulusan Otomatis</h3>
                <p class="text-xs text-slate-500 font-medium">Proses pergantian tahun ajaran otomatis mengikuti pola penamaan rombel</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-promote-classes').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('adminsekolah.classes.promote') }}" method="POST" class="space-y-4 flex-1 overflow-y-auto pr-1">
            @csrf
            <div class="grid grid-cols-2 gap-3 p-3.5 rounded-2xl bg-indigo-50/50 border border-indigo-100">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tahun Ajaran Asal</label>
                    <input type="text" id="promo-current-year" name="current_academic_year" required value="2026/2027"
                           class="w-full text-xs font-mono font-bold px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tahun Ajaran Tujuan</label>
                    <input type="text" id="promo-target-year" name="target_academic_year" required value="2027/2028"
                           class="w-full text-xs font-mono font-bold px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-900">
                </div>
            </div>

            <div class="flex justify-between items-center">
                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Simulasi / Preview Hasil:</span>
                <button type="button" onclick="loadPromotionPreview()" class="px-3 py-1.5 rounded-xl bg-indigo-50 text-indigo-700 font-bold text-xs hover:bg-indigo-100 border border-indigo-200 transition-colors flex items-center gap-1 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Muat Simulasi
                </button>
            </div>

            <div id="promo-preview-container" class="space-y-2 min-h-[140px] max-h-60 overflow-y-auto pr-1">
                <div class="p-6 text-center text-xs text-slate-400 bg-slate-50 rounded-2xl border border-slate-100">
                    Klik tombol "Muat Simulasi" di atas untuk meninjau pola pergeseran rombel dan kelulusan siswa.
                </div>
            </div>

            <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs space-y-1">
                <span class="font-bold block">Pola Kenaikan Otomatis:</span>
                <p class="text-[11px] text-amber-800 leading-relaxed">
                    &bull; <strong>Jurusan 4 Tahun (Geologi):</strong> Siswa Kelas XII otomatis naik ke <strong>Kelas XIII</strong> dan masa magang tetap aktif sampai Agustus.<br>
                    &bull; <strong>Jurusan 3 Tahun (Reguler):</strong> Siswa Kelas XII otomatis dinyatakan <strong>Lulus (Alumni)</strong> dan akun beralih ke mode arsip.<br>
                    &bull; <strong>Tingkat X & XI:</strong> Otomatis naik tingkat mengikuti pola nama kelas (misal: <em>X GP 1 &rarr; XI GP 1</em>).
                </p>
            </div>

            <label class="flex items-center gap-2.5 p-3 rounded-2xl bg-slate-50 border border-slate-200 cursor-pointer text-xs text-slate-700">
                <input type="checkbox" name="preserve_homeroom_teacher" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <span>Pertahankan penugasan <strong>Wali Kelas</strong> saat ini pada rombel tahun ajaran baru</span>
            </label>

            <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100 shrink-0">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-promote-classes').classList.add('hidden')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md" onclick="return confirm('Apakah Anda yakin ingin memproses kenaikan kelas dan kelulusan ini secara massal?')">
                    Eksekusi Kenaikan Kelas & Kelulusan
                </x-button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function loadPromotionPreview() {
        const curYear = document.getElementById('promo-current-year').value;
        const tgtYear = document.getElementById('promo-target-year').value;
        const container = document.getElementById('promo-preview-container');

        container.innerHTML = '<div class="p-4 text-center text-xs text-indigo-600 font-bold">Sedang memuat simulasi...</div>';

        fetch(`{{ route('adminsekolah.classes.promotion-preview') }}?current_academic_year=${encodeURIComponent(curYear)}&target_academic_year=${encodeURIComponent(tgtYear)}`)
            .then(res => res.json())
            .then(data => {
                if (!data.preview || data.preview.length === 0) {
                    container.innerHTML = '<div class="p-4 text-center text-xs text-slate-400 bg-slate-50 rounded-2xl border border-slate-100">Tidak ada rombel kelas yang terdaftar pada tahun ajaran asal ' + curYear + '</div>';
                    return;
                }

                container.innerHTML = '';
                data.preview.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'p-3 rounded-xl border flex items-center justify-between text-xs gap-3 ' + 
                        (item.target_grade === 'XIII' 
                            ? 'bg-purple-50/70 border-purple-200' 
                            : (item.action_type === 'graduate' ? 'bg-slate-50 border-slate-200' : 'bg-emerald-50/60 border-emerald-200'));

                    const badgeClass = item.target_grade === 'XIII'
                        ? 'bg-purple-600 text-white'
                        : (item.action_type === 'graduate' ? 'bg-slate-600 text-white' : 'bg-emerald-600 text-white');

                    div.innerHTML = `
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-900">${item.class_name}</span>
                                <span class="text-[10px] text-slate-500 font-mono">(${item.major_code})</span>
                                <span class="text-slate-400">&rarr;</span>
                                <span class="font-extrabold ${item.target_grade === 'XIII' ? 'text-purple-700' : (item.action_type === 'graduate' ? 'text-slate-700' : 'text-emerald-700')}">
                                    ${item.target_class_name}
                                </span>
                            </div>
                            <span class="text-[10px] text-slate-400 block mt-0.5">${item.student_count} siswa aktif</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold ${badgeClass} shrink-0">
                            ${item.action_label}
                        </span>
                    `;
                    container.appendChild(div);
                });
            })
            .catch(err => {
                container.innerHTML = '<div class="p-4 text-center text-xs text-rose-600 bg-rose-50 rounded-2xl border border-rose-200">Gagal memuat simulasi: ' + err.message + '</div>';
            });
    }

    function openEditMajorModal(data) {
        document.getElementById('edit-major-form').action = data.update_url;
        document.getElementById('edit-major-code').value = data.code;
        document.getElementById('edit-major-name').value = data.name;
        document.getElementById('edit-major-duration').value = data.program_duration_years || 3;
        document.getElementById('edit-major-pkl-duration').value = data.default_pkl_duration_months || 6;
        document.getElementById('edit-major-head-teacher-id').value = data.head_teacher_id || '';
        document.getElementById('modal-edit-major').classList.remove('hidden');
    }
    function openMajorCompetenciesModal(data) {
        document.getElementById('cp-modal-badge').textContent = data.code;
        document.getElementById('cp-modal-title').textContent = 'Capaian Pembelajaran — ' + data.name + ' (' + data.code + ')';
        document.getElementById('cp-store-form').action = data.store_url;

        const list = document.getElementById('cp-modal-list');
        const empty = document.getElementById('cp-modal-empty');
        list.innerHTML = '';

        if (!data.competencies || data.competencies.length === 0) {
            empty.classList.remove('hidden');
        } else {
            empty.classList.add('hidden');
            data.competencies.forEach(cp => {
                const div = document.createElement('div');
                div.className = 'p-3 rounded-2xl bg-white border border-slate-200 flex items-center justify-between gap-3 hover:border-emerald-300 transition-colors';
                div.innerHTML = `
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200/80 font-mono font-bold text-xs shrink-0">${cp.code}</span>
                        <div class="min-w-0">
                            <span class="font-bold text-slate-900 text-xs block truncate">${cp.title}</span>
                            <span class="text-[11px] text-slate-400 block truncate">${cp.element_name || 'Kompetensi Kejuruan'} &bull; ${cp.target_hours} JP</span>
                        </div>
                    </div>
                    <form action="${cp.delete_url}" method="POST" onsubmit="return confirm('Hapus Capaian Pembelajaran ${cp.code}?')" class="shrink-0">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 transition-colors" title="Hapus CP">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                `;
                list.appendChild(div);
            });
        }

        document.getElementById('modal-major-competencies').classList.remove('hidden');
    }

    function openEditClassModal(data) {
        document.getElementById('edit-class-form').action = data.update_url;
        document.getElementById('edit-class-major-id').value = data.major_id;
        document.getElementById('edit-class-homeroom-teacher-id').value = data.homeroom_teacher_id || '';
        document.getElementById('edit-class-grade-level').value = data.grade_level;
        document.getElementById('edit-class-academic-year').value = data.academic_year;
        document.getElementById('edit-class-name').value = data.name;
        document.getElementById('modal-edit-class').classList.remove('hidden');
    }
</script>
@endpush
@endsection
