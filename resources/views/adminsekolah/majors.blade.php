@extends('layouts.app')

@section('page_title', 'Master Jurusan & Kelas')
@section('page_subtitle', 'Manajemen kompetensi keahlian dan rombongan belajar PKL')

@section('content')
<div class="space-y-6">

    <x-page-header 
        title="Jurusan & Rombongan Belajar" 
        subtitle="Struktur program keahlian vokasi dan kelas siswa peserta program PKL."
    >
        <x-button type="button" variant="secondary" size="sm" onclick="document.getElementById('modal-add-major').classList.remove('hidden')">
            <svg class="w-4 h-4 mr-1 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Tambah Jurusan
        </x-button>
        <x-button type="button" variant="primary" size="sm" onclick="document.getElementById('modal-add-class').classList.remove('hidden')">
            <svg class="w-4 h-4 mr-1 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Tambah Kelas Baru
        </x-button>
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Jurusan (Majors) Card -->
        <x-card 
            title="Daftar Jurusan Keahlian" 
            :subtitle="$majors->count() . ' Program Keahlian Terdaftar'"
        >
            <x-slot:headerActions>
                <x-button type="button" variant="primary" size="xs" onclick="document.getElementById('modal-add-major').classList.remove('hidden')">
                    + Tambah
                </x-button>
            </x-slot:headerActions>

            @if($majors->isEmpty())
                <x-empty-state 
                    title="Belum Ada Jurusan" 
                    description="Tambahkan program keahlian seperti RPL, TKJ, atau Akuntansi terlebih dahulu."
                    actionLabel="+ Tambah Jurusan Sekarang"
                    actionClick="document.getElementById('modal-add-major').classList.remove('hidden')"
                />
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($majors as $m)
                        <div class="py-3.5 first:pt-0 last:pb-0 flex items-center justify-between gap-3 group">
                            <div class="flex items-center space-x-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-indigo-50/80 border border-indigo-100 text-indigo-700 font-extrabold text-xs flex items-center justify-center shrink-0">
                                    {{ $m->code }}
                                </div>
                                <div class="min-w-0">
                                    <span class="font-bold text-slate-900 block truncate group-hover:text-indigo-600 transition-colors">{{ $m->name }}</span>
                                    <span class="text-[11px] font-mono text-slate-400 font-medium">Kode ID: #{{ $m->id }}</span>
                                </div>
                            </div>
                            <x-badge variant="neutral" size="sm">
                                {{ $m->classes->count() }} Rombel Kelas
                            </x-badge>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

        <!-- Kelas (Classes) Card -->
        <x-card 
            title="Daftar Rombel / Kelas" 
            :subtitle="$classes->count() . ' Rombongan Belajar Aktif'"
        >
            <x-slot:headerActions>
                <x-button type="button" variant="primary" size="xs" onclick="document.getElementById('modal-add-class').classList.remove('hidden')">
                    + Tambah
                </x-button>
            </x-slot:headerActions>

            @if($classes->isEmpty())
                <x-empty-state 
                    title="Belum Ada Rombel Kelas" 
                    description="Tambahkan rombel kelas agar siswa dapat dikelompokkan dengan rapi."
                    actionLabel="+ Tambah Kelas Sekarang"
                    actionClick="document.getElementById('modal-add-class').classList.remove('hidden')"
                />
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($classes as $c)
                        <div class="py-3.5 first:pt-0 last:pb-0 flex items-center justify-between gap-3 group">
                            <div class="flex items-center space-x-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-emerald-50/80 border border-emerald-100 text-emerald-700 font-extrabold text-xs flex items-center justify-center shrink-0">
                                    {{ $c->grade_level }}
                                </div>
                                <div class="min-w-0">
                                    <span class="font-bold text-slate-900 block truncate group-hover:text-emerald-700 transition-colors">{{ $c->name }}</span>
                                    <span class="text-[11px] text-slate-500 block truncate font-medium">
                                        {{ $c->major->name }} &bull; TA {{ $c->academic_year }}
                                    </span>
                                </div>
                            </div>
                            <x-badge variant="success" size="sm">
                                Kelas {{ $c->grade_level }}
                            </x-badge>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

    </div>

</div>

<!-- Modal Add Major -->
<div id="modal-add-major" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in fade-in duration-150">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Tambah Jurusan Baru</h3>
                <p class="text-xs text-slate-500 font-medium">Buat program kompetensi keahlian</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-add-major').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('adminsekolah.majors.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Kode Singkat (Contoh: RPL, TKJ, AKL)</label>
                <input type="text" name="code" required placeholder="RPL" 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 uppercase font-mono font-bold text-slate-900">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Lengkap Jurusan Keahlian</label>
                <input type="text" name="name" required placeholder="Rekayasa Perangkat Lunak" 
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
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in fade-in duration-150">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Tambah Rombel Kelas</h3>
                <p class="text-xs text-slate-500 font-medium">Hubungkan rombel ke jurusan keahlian</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-add-class').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('adminsekolah.classes.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Jurusan Induk</label>
                <select name="major_id" required 
                        class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                    @foreach($majors as $m)
                        <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->code }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Rombel Kelas (Contoh: XII RPL 1)</label>
                <input type="text" name="name" required placeholder="XII RPL 1" 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Tingkat Jenjang</label>
                    <select name="grade_level" 
                            class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                        <option value="XII" selected>Tingkat XII</option>
                        <option value="XI">Tingkat XI</option>
                        <option value="X">Tingkat X</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Tahun Ajaran</label>
                    <input type="text" name="academic_year" value="2026/2027" required 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-mono text-slate-900">
                </div>
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
@endsection
