@extends('layouts.app')

@section('title', 'Capaian Pembelajaran (CP) — Jurusan ' . $major->code)

@section('content')
<div class="space-y-6">

    <!-- Top Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-medium mb-1">
                <a href="{{ route('kajur.dashboard') }}" class="hover:text-emerald-700 transition-colors">Portal Kajur</a>
                <span>&bull;</span>
                <span class="text-emerald-700 font-bold">Capaian Pembelajaran</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <span>Capaian Pembelajaran (CP) & Elemen Kompetensi</span>
                <span class="px-2.5 py-0.5 rounded-lg text-xs font-extrabold bg-emerald-50 text-emerald-800 border border-emerald-200/80 uppercase font-mono">
                    {{ $major->code }}
                </span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Standarisasi elemen kompetensi dan tujuan pembelajaran (TP) Kurikulum Merdeka untuk jurusan {{ $major->name }}.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <x-button type="button" variant="primary" size="md" onclick="document.getElementById('modal-add-cp').classList.remove('hidden')">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                + Tambah Capaian Pembelajaran
            </x-button>
        </div>
    </div>

    <!-- Stat Cards Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-stat-card 
            title="Total Capaian Pembelajaran" 
            :value="$learningObjectives->count()" 
            subtitle="Tujuan pembelajaran terdaftar"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Status Aktif Digunakan" 
            :value="$learningObjectives->where('is_active', true)->count()" 
            subtitle="Tampil di pilihan jurnal siswa"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Total Jurnal Terkait" 
            :value="$learningObjectives->sum('journals_count')" 
            subtitle="Eviden aktivitas siswa terhubung"
            color="amber"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Main Table Card -->
    <x-card 
        title="Daftar Capaian Pembelajaran Jurusan {{ $major->name }}" 
        subtitle="Elemen kompetensi yang dijadikan indikator target jurnal harian dan transkrip nilai PKL"
    >
        @if($learningObjectives->isEmpty())
            <x-empty-state 
                title="Belum Ada Capaian Pembelajaran" 
                description="Rumuskan butir-butir Capaian Pembelajaran (CP) dan Tujuan Pembelajaran (TP) untuk jurusan ini agar siswa dapat menghubungkannya dengan jurnal harian."
                actionLabel="+ Tambah Capaian Pembelajaran Sekarang"
                actionClick="document.getElementById('modal-add-cp').classList.remove('hidden')"
            />
        @else
            <div class="overflow-x-auto -mx-6 -my-4">
                <table class="w-full text-left text-xs text-slate-700">
                    <thead class="bg-slate-50/80 text-slate-500 font-extrabold uppercase tracking-wider text-[10px] border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-6 w-16 text-center">Kode</th>
                            <th class="py-3 px-6 min-w-[200px]">Capaian Pembelajaran (CP)</th>
                            <th class="py-3 px-6">Elemen Kurikulum</th>
                            <th class="py-3 px-4 text-center">Target Jam</th>
                            <th class="py-3 px-4 text-center">Jurnal Siswa</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($learningObjectives as $lo)
                            <tr class="hover:bg-slate-50/60 transition-colors group">
                                <td class="py-4 px-6 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md font-mono font-black text-xs bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        {{ $lo->code }}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="font-bold text-slate-900 group-hover:text-emerald-700 transition-colors text-sm">
                                        {{ $lo->title }}
                                    </div>
                                    @if($lo->description)
                                        <p class="text-xs text-slate-500 font-medium mt-0.5 line-clamp-2 leading-relaxed">
                                            {{ $lo->description }}
                                        </p>
                                    @endif
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                        {{ $lo->element_name ?? 'Keterampilan Teknis Kejuruan' }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-center font-mono font-bold text-slate-700">
                                    {{ $lo->target_hours > 0 ? $lo->target_hours . ' JP' : '-' }}
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200 font-mono">
                                        {{ $lo->journals_count }} aktivitas
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    @if($lo->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end space-x-1">
                                        <button type="button" 
                                                class="p-1.5 text-slate-400 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors cursor-pointer"
                                                title="Edit Capaian Pembelajaran"
                                                data-cp="{{ json_encode([
                                                    'id' => $lo->id,
                                                    'code' => $lo->code,
                                                    'title' => $lo->title,
                                                    'element_name' => $lo->element_name,
                                                    'description' => $lo->description,
                                                    'target_hours' => $lo->target_hours,
                                                    'sort_order' => $lo->sort_order,
                                                    'is_active' => (bool) $lo->is_active,
                                                    'update_url' => route('kajur.competencies.update', $lo),
                                                ]) }}"
                                                onclick="openEditCpModal(JSON.parse(this.dataset.cp))">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </button>

                                        <form action="{{ route('kajur.competencies.destroy', $lo) }}" method="POST" onsubmit="return confirm('Hapus Capaian Pembelajaran ini? Relasi pada jurnal siswa juga akan terhapus.');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer"
                                                    title="Hapus Capaian Pembelajaran">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

</div>

<!-- Modal Add CP -->
<div id="modal-add-cp" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-2xl border border-slate-100 animate-in fade-in duration-150">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Tambah Capaian Pembelajaran</h3>
                <p class="text-xs text-slate-500 font-medium">Kurikulum Merdeka Jurusan {{ $major->code }}</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-add-cp').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('kajur.competencies.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Kode CP / TP <span class="text-rose-500">*</span></label>
                    <input type="text" name="code" required placeholder="Contoh: CP-01" 
                           class="w-full text-xs font-mono font-bold uppercase px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 text-slate-900">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Elemen Kurikulum Merdeka</label>
                    <select name="element_name" class="w-full text-xs px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                        <option value="Keterampilan Teknis Kejuruan">Keterampilan Teknis Kejuruan (Hard Skills)</option>
                        <option value="Internalisasi & Penerapan Soft Skills">Internalisasi & Penerapan Soft Skills</option>
                        <option value="K3LH & SOP Industri">K3LH & SOP Industri</option>
                        <option value="Alur Bisnis & Kewirausahaan">Alur Bisnis & Kewirausahaan</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Judul Capaian Pembelajaran <span class="text-rose-500">*</span></label>
                <input type="text" name="title" required placeholder="Contoh: Pemetaan Geologi & Pengukuran Strike/Dip" 
                       class="w-full text-xs sm:text-sm font-semibold px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 text-slate-900">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Deskripsi / Indikator Ketercapaian</label>
                <textarea name="description" rows="3" placeholder="Uraian rinci target kemampuan yang diharapkan dapat dikuasai dan diterapkan peserta didik selama PKL..."
                          class="w-full text-xs font-medium p-3 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 text-slate-900 leading-relaxed"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Target Jam / JP</label>
                    <input type="number" name="target_hours" min="0" value="0" placeholder="Contoh: 40" 
                           class="w-full text-xs font-mono px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Urutan Tampil</label>
                    <input type="number" name="sort_order" min="0" value="1" placeholder="1" 
                           class="w-full text-xs font-mono px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 text-slate-900">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="add-is-active" name="is_active" value="1" checked 
                       class="w-4 h-4 text-emerald-600 border-slate-300 rounded focus:ring-emerald-500">
                <label for="add-is-active" class="text-xs font-medium text-slate-700">Aktifkan segera untuk pilihan jurnal siswa</label>
            </div>

            <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-add-cp').classList.add('hidden')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md">
                    Simpan Capaian Pembelajaran
                </x-button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit CP -->
<div id="modal-edit-cp" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-2xl border border-slate-100 animate-in fade-in duration-150">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Edit Capaian Pembelajaran</h3>
                <p class="text-xs text-slate-500 font-medium">Perbarui indikator dan elemen kompetensi</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-edit-cp').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="form-edit-cp" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Kode CP / TP <span class="text-rose-500">*</span></label>
                    <input type="text" id="edit-cp-code" name="code" required 
                           class="w-full text-xs font-mono font-bold uppercase px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 text-slate-900">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Elemen Kurikulum Merdeka</label>
                    <select id="edit-cp-element" name="element_name" class="w-full text-xs px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                        <option value="Keterampilan Teknis Kejuruan">Keterampilan Teknis Kejuruan (Hard Skills)</option>
                        <option value="Internalisasi & Penerapan Soft Skills">Internalisasi & Penerapan Soft Skills</option>
                        <option value="K3LH & SOP Industri">K3LH & SOP Industri</option>
                        <option value="Alur Bisnis & Kewirausahaan">Alur Bisnis & Kewirausahaan</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Judul Capaian Pembelajaran <span class="text-rose-500">*</span></label>
                <input type="text" id="edit-cp-title" name="title" required 
                       class="w-full text-xs sm:text-sm font-semibold px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 text-slate-900">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Deskripsi / Indikator Ketercapaian</label>
                <textarea id="edit-cp-description" name="description" rows="3" 
                          class="w-full text-xs font-medium p-3 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 text-slate-900 leading-relaxed"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Target Jam / JP</label>
                    <input type="number" id="edit-cp-hours" name="target_hours" min="0" 
                           class="w-full text-xs font-mono px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Urutan Tampil</label>
                    <input type="number" id="edit-cp-order" name="sort_order" min="0" 
                           class="w-full text-xs font-mono px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 text-slate-900">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="edit-cp-active" name="is_active" value="1" 
                       class="w-4 h-4 text-emerald-600 border-slate-300 rounded focus:ring-emerald-500">
                <label for="edit-cp-active" class="text-xs font-medium text-slate-700">Status Aktif (Ditampilkan ke siswa)</label>
            </div>

            <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-edit-cp').classList.add('hidden')">
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
    function openEditCpModal(data) {
        document.getElementById('form-edit-cp').action = data.update_url;
        document.getElementById('edit-cp-code').value = data.code || '';
        document.getElementById('edit-cp-title').value = data.title || '';
        document.getElementById('edit-cp-element').value = data.element_name || 'Keterampilan Teknis Kejuruan';
        document.getElementById('edit-cp-description').value = data.description || '';
        document.getElementById('edit-cp-hours').value = data.target_hours || 0;
        document.getElementById('edit-cp-order').value = data.sort_order || 0;
        document.getElementById('edit-cp-active').checked = !!data.is_active;
        document.getElementById('modal-edit-cp').classList.remove('hidden');
    }
</script>
@endpush
@endsection
