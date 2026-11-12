@extends('layouts.app')

@section('page_title', 'Master Data Guru Pembimbing')
@section('page_subtitle', 'Kelola akun guru pembimbing PKL sekolah dan pantau alokasi siswa binaan')

@section('content')
<div class="w-full space-y-6">

    <!-- Enterprise Hero Banner -->
    <x-page-header 
        title="Daftar Guru Pembimbing PKL" 
        subtitle="Guru yang bertugas membimbing di lapangan, memantau absensi real-time, memverifikasi jurnal tingkat dua, dan mengevaluasi nilai sekolah."
        badge="MANAJEMEN SEKOLAH"
    >
        <x-button type="button" variant="primary" size="sm" onclick="document.getElementById('modal-add-teacher').classList.remove('hidden')">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span>+ Tambah Guru Pembimbing</span>
        </x-button>
    </x-page-header>

    <!-- Top KPI Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card 
            title="Total Guru Pembimbing" 
            :value="$totalTeachers" 
            subtitle="Tenaga pendidik pembimbing di sekolah"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Total Alokasi Siswa" 
            :value="$totalPlotted . ' Siswa'" 
            subtitle="Siswa dalam bimbingan aktif"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Rata-rata Rasio Bimbingan" 
            :value="$avgPlotted . ' Siswa/Guru'" 
            subtitle="Beban rata-rata bimbingan"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Pembimbing Aktif Lapangan" 
            :value="$activeTeachersCount" 
            subtitle="Memiliki siswa binaan aktif"
            :color="$activeTeachersCount > 0 ? 'emerald' : 'amber'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Table Card -->
    <x-card noPadding>
        <!-- Table Search Toolbar -->
        <div class="p-4 sm:p-5 bg-white border-b border-slate-100 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <div class="relative flex-1 max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" id="teacher-search-input" onkeyup="filterTeachersTable()" placeholder="Cari nama guru, NIP, atau email..." 
                       class="w-full text-xs font-semibold pl-9.5 pr-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all">
            </div>

            <div class="flex items-center gap-2">
                <form method="GET" action="{{ route('adminsekolah.teachers') }}" class="flex items-center gap-2">
                    <select name="major" onchange="this.form.submit()" 
                            class="text-xs font-bold px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <option value="all" {{ ($selectedMajor ?? 'all') === 'all' ? 'selected' : '' }}>Semua Jurusan</option>
                        <option value="lintas" {{ ($selectedMajor ?? '') === 'lintas' ? 'selected' : '' }}>Lintas Jurusan</option>
                        @foreach($majors as $m)
                            <option value="{{ $m->id }}" {{ ($selectedMajor ?? '') == $m->id ? 'selected' : '' }}>
                                {{ $m->code }} - {{ $m->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
                <span class="text-xs text-slate-500 font-semibold hidden md:inline ml-2">
                    {{ $teachers->total() }} Guru Terdaftar
                </span>
            </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs min-w-[960px]" id="teachers-table">
                <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6 min-w-[210px]">Identitas Guru Pembimbing</th>
                        <th class="py-3.5 px-4 min-w-[140px]">Nomor Induk Pegawai (NIP)</th>
                        <th class="py-3.5 px-4 min-w-[140px]">Jurusan yang Diampu</th>
                        <th class="py-3.5 px-4 min-w-[160px]">Email Login Sistem</th>
                        <th class="py-3.5 px-6 text-center min-w-[120px]">Beban Bimbingan</th>
                        <th class="py-3.5 px-4 text-right table-sticky-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($teachers as $t)
                        <tr class="hover:bg-slate-50/70 transition-colors teacher-row group">
                            <td class="py-3.5 px-6 min-w-[210px]">
                                <div class="flex items-center space-x-3.5">
                                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-700 font-black text-xs flex items-center justify-center shrink-0 shadow-xs shadow-emerald-600/10">
                                        {{ substr($t->user->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <span class="font-bold text-slate-900 block truncate text-sm teacher-name" title="{{ $t->user->name }}">{{ $t->user->name }}</span>
                                        <span class="text-[11px] text-slate-400 font-medium">
                                            {{ $t->user->profile->phone ?? 'Kontak belum diisi' }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 min-w-[140px]">
                                <span class="font-mono font-bold text-slate-800 text-xs teacher-nip whitespace-nowrap">{{ $t->nip ?? '-' }}</span>
                            </td>
                            <td class="py-3.5 px-4 min-w-[140px]">
                                @if($t->majors->isNotEmpty())
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($t->majors as $mj)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200/60" title="{{ $mj->name }}">
                                                {{ $mj->code }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        Lintas Jurusan
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-600 min-w-[160px]">
                                <span class="block truncate" title="{{ $t->user->email }}">{{ $t->user->email }}</span>
                            </td>
                            <td class="py-3.5 px-6 text-center min-w-[120px] whitespace-nowrap">
                                <x-badge variant="indigo" size="sm">
                                    {{ $t->studentPkls->count() }} Siswa Binaan
                                </x-badge>
                            </td>
                            <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-1.5 table-sticky-right">
                                <button type="button" 
                                        onclick="openEditTeacherModal({{ json_encode([
                                            'id' => $t->id,
                                            'name' => $t->user->name,
                                            'email' => $t->user->email,
                                            'nip' => $t->nip ?? '',
                                            'phone' => $t->user->profile->phone ?? '',
                                            'major_ids' => $t->majors->pluck('id')->toArray(),
                                            'update_url' => route('adminsekolah.teachers.update', $t),
                                        ]) }})"
                                        class="px-2.5 py-1 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-700 text-[11px] font-bold border border-slate-200 transition-colors cursor-pointer inline-flex items-center gap-1"
                                        title="Ubah data guru pembimbing">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    <span>Edit</span>
                                </button>
                                <form action="{{ route('impersonate.start', $t->user) }}" method="POST" class="inline" onsubmit="return confirm('Masuk sebagai {{ $t->user->name }} (Mode Impersonasi)?')">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 text-[11px] font-bold border border-amber-200 transition-colors cursor-pointer" title="Masuk sebagai akun ini untuk inspeksi sistem">
                                        Login As
                                    </button>
                                </form>
                                @if($t->studentPkls->whereIn('status', ['assigned', 'active'])->count() === 0)
                                    <form action="{{ route('adminsekolah.teachers.destroy', $t) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun guru {{ $t->user->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-[11px] font-bold border border-rose-200 transition-colors cursor-pointer" title="Hapus akun guru pembimbing">
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
                                    title="Belum Ada Guru Pembimbing" 
                                    description="Tambahkan akun guru pembimbing untuk memandu dan mengevaluasi siswa peserta PKL."
                                    actionLabel="+ Tambah Guru Sekarang"
                                    actionClick="document.getElementById('modal-add-teacher').classList.remove('hidden')"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($teachers->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $teachers->links() }}
            </div>
        @endif
    </x-card>

</div>

<!-- Modal Add Teacher -->
<div id="modal-add-teacher" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in fade-in duration-150">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Tambah Guru Pembimbing</h3>
                <p class="text-xs text-slate-500 font-medium">Buat akun login dan data pokok guru pembimbing</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-add-teacher').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('adminsekolah.teachers.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Lengkap & Gelar</label>
                <input type="text" name="name" required placeholder="Contoh: Siti Nurhaliza, M.Pd." 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nomor Induk Pegawai (NIP)</label>
                <input type="text" name="nip" placeholder="198205142008012003" 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-mono text-slate-900">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nomor Telepon / WhatsApp</label>
                <input type="text" name="phone" placeholder="081234567890" 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Email (Username Login)</label>
                <input type="email" name="email" required placeholder="guru@smk.sch.id" 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                <span class="text-[11px] text-slate-400 block mt-1 font-medium">Kata sandi default awal: <code class="text-slate-700 font-mono font-bold">password</code></span>
            </div>
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold text-slate-700">Jurusan yang Diampu</label>
                    <div class="space-x-2 text-[10px]">
                        <button type="button" onclick="toggleAllTeacherMajors('add-teacher-major-check', true)" class="text-indigo-600 hover:underline font-bold cursor-pointer">Pilih Semua</button>
                        <span class="text-slate-300">|</span>
                        <button type="button" onclick="toggleAllTeacherMajors('add-teacher-major-check', false)" class="text-slate-500 hover:underline cursor-pointer">Kosongkan</button>
                    </div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 p-3 bg-slate-50 border border-slate-200 rounded-2xl max-h-48 overflow-y-auto">
                    @foreach($majors as $m)
                        <label class="flex items-center space-x-2 text-xs font-semibold text-slate-700 cursor-pointer p-1.5 rounded-lg hover:bg-white transition">
                            <input type="checkbox" name="major_ids[]" value="{{ $m->id }}" class="add-teacher-major-check rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="truncate" title="{{ $m->name }}">{{ $m->code }}</span>
                        </label>
                    @endforeach
                </div>
                <span class="text-[11px] text-slate-400 block mt-1 font-medium">Jika kosong, guru dapat membimbing siswa di semua jurusan (Lintas Jurusan).</span>
            </div>
            <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-add-teacher').classList.add('hidden')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md">
                    Simpan Guru
                </x-button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Teacher -->
<div id="modal-edit-teacher" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in fade-in duration-150">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Edit Guru Pembimbing</h3>
                <p class="text-xs text-slate-500 mt-0.5">Perbarui profil tenaga pendidik pembimbing PKL</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-edit-teacher').classList.add('hidden')" class="p-1 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="edit-teacher-form" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Lengkap & Gelar</label>
                <input type="text" id="edit-teacher-name" name="name" required 
                       class="w-full text-xs font-semibold px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-slate-800">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nomor Induk Pegawai (NIP)</label>
                <input type="text" id="edit-teacher-nip" name="nip" placeholder="Opsional / NIP" 
                       class="w-full text-xs font-mono font-semibold px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-slate-800">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nomor Kontak WhatsApp / HP</label>
                <input type="text" id="edit-teacher-phone" name="phone" placeholder="08..." 
                       class="w-full text-xs font-mono font-semibold px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-slate-800">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Email (Username Login)</label>
                <input type="email" id="edit-teacher-email" name="email" required 
                       class="w-full text-xs font-mono font-semibold px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-slate-800">
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold text-slate-700">Jurusan yang Diampu</label>
                    <div class="space-x-2 text-[10px]">
                        <button type="button" onclick="toggleAllTeacherMajors('edit-teacher-major-check', true)" class="text-indigo-600 hover:underline font-bold cursor-pointer">Pilih Semua</button>
                        <span class="text-slate-300">|</span>
                        <button type="button" onclick="toggleAllTeacherMajors('edit-teacher-major-check', false)" class="text-slate-500 hover:underline cursor-pointer">Kosongkan</button>
                    </div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 p-3 bg-slate-50 border border-slate-200 rounded-2xl max-h-48 overflow-y-auto">
                    @foreach($majors as $m)
                        <label class="flex items-center space-x-2 text-xs font-semibold text-slate-700 cursor-pointer p-1.5 rounded-lg hover:bg-white transition">
                            <input type="checkbox" name="major_ids[]" value="{{ $m->id }}" id="edit-t-major-{{ $m->id }}" class="edit-teacher-major-check rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="truncate" title="{{ $m->name }}">{{ $m->code }}</span>
                        </label>
                    @endforeach
                </div>
                <span class="text-[11px] text-slate-400 block mt-1 font-medium">Jika kosong, guru dapat membimbing siswa di semua jurusan (Lintas Jurusan).</span>
            </div>

            <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-edit-teacher').classList.add('hidden')">
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
    function toggleAllTeacherMajors(className, checked) {
        document.querySelectorAll('.' + className).forEach(el => {
            el.checked = checked;
        });
    }

    function filterTeachersTable() {
        const query = document.getElementById('teacher-search-input').value.toLowerCase();
        const rows = document.querySelectorAll('.teacher-row');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    }

    function openEditTeacherModal(data) {
        document.getElementById('edit-teacher-form').action = data.update_url;
        document.getElementById('edit-teacher-name').value = data.name;
        document.getElementById('edit-teacher-nip').value = data.nip || '';
        document.getElementById('edit-teacher-phone').value = data.phone || '';
        document.getElementById('edit-teacher-email').value = data.email;

        // Reset and set majors
        document.querySelectorAll('.edit-teacher-major-check').forEach(el => el.checked = false);
        if (Array.isArray(data.major_ids)) {
            data.major_ids.forEach(id => {
                const el = document.getElementById('edit-t-major-' + id);
                if (el) el.checked = true;
            });
        }

        document.getElementById('modal-edit-teacher').classList.remove('hidden');
    }
</script>
@endpush
@endsection
