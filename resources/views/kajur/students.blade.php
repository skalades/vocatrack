@extends('layouts.app')

@section('page_title', 'Siswa & Plotting PKL Jurusan')
@section('page_subtitle', 'Distribusi penempatan industri & penetapan guru pembimbing jurusan ' . $major->name)

@section('content')
<div class="w-full space-y-6">

    <x-page-header 
        title="Siswa & Plotting PKL Jurusan" 
        subtitle="Kelola penempatan industri dan penetapan guru pembimbing untuk peserta didik jurusan {{ $major->name }}."
        badge="MANAJEMEN KAJUR"
    />

    <!-- Filter Bar -->
    <x-card>
        <form method="GET" action="{{ route('kajur.students') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="relative sm:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama siswa, NISN, atau NIS..." 
                       class="w-full pl-9 pr-4 py-2.5 text-xs sm:text-sm rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <div>
                <select name="class_id" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                    <option value="">-- Semua Rombel Kelas --</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <select name="status" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                    <option value="">-- Semua Status --</option>
                    <option value="plotted" {{ request('status') === 'plotted' ? 'selected' : '' }}>Sudah Terplotting</option>
                    <option value="unplotted" {{ request('status') === 'unplotted' ? 'selected' : '' }}>Belum Terplotting</option>
                </select>
                <x-button type="submit" variant="primary" size="md">
                    Filter
                </x-button>
            </div>
        </form>
    </x-card>

    <!-- Students Table -->
    <x-card :title="'Daftar Peserta Didik (' . $students->total() . ' Siswa)'">
        @if($students->isEmpty())
            <x-empty-state 
                title="Data Siswa Tidak Ditemukan" 
                description="Tidak ada peserta didik yang sesuai dengan filter pencarian Anda."
            />
        @else
            <div class="overflow-x-auto -mx-6">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                            <th class="py-3 px-6">Identitas Siswa</th>
                            <th class="py-3 px-4">Kelas</th>
                            <th class="py-3 px-4">Mitra DU/DI</th>
                            <th class="py-3 px-4">Guru Pembimbing</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach($students as $s)
                            @php
                                $spkl = $s->studentPkl;
                                $isPlotted = $spkl && $spkl->status === 'active';
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3 px-6">
                                    <div class="font-bold text-slate-900">{{ $s->user?->name ?? $s->name ?? 'Siswa' }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">NISN: {{ $s->nisn }} &bull; NIS: {{ $s->nis ?? '-' }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md font-semibold text-slate-700 bg-slate-100 text-[11px]">
                                        {{ $s->schoolClass?->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    @if($isPlotted && $spkl->company)
                                        <div class="font-semibold text-slate-900">{{ $spkl->company->name }}</div>
                                        <div class="text-[11px] text-slate-400 line-clamp-1">{{ $spkl->company->address }}</div>
                                    @else
                                        <span class="text-slate-400 italic text-xs">Belum ditentukan</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    @if($isPlotted && $spkl->teacher)
                                        <div class="font-semibold text-slate-900 flex items-center gap-1.5">
                                            <span>{{ $spkl->teacher->user->name }}</span>
                                            @if($spkl->teacher->id === $myTeacherId)
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">Anda</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-slate-400 italic text-xs">Belum ditunjuk</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <x-badge :variant="$isPlotted ? 'emerald' : 'amber'" size="xs">
                                        {{ $isPlotted ? 'Aktif PKL' : 'Belum Plotting' }}
                                    </x-badge>
                                </td>
                                <td class="py-3 px-6 text-right">
                                    <button type="button" 
                                            onclick="openAssignPlottingModal({{ json_encode([
                                                'student_id' => $s->id,
                                                'student_name' => $s->user?->name ?? $s->name ?? 'Siswa',
                                                'class_name' => $s->schoolClass?->name ?? '-',
                                                'company_id' => $spkl?->company_id,
                                                'teacher_id' => $spkl?->teacher_id,
                                                'action_url' => route('kajur.students.assign', $s),
                                            ]) }})"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-xl transition-colors cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        <span>Plotting</span>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pt-4 border-t border-slate-100">
                {{ $students->links() }}
            </div>
        @endif
    </x-card>

</div>

<!-- Modal Assign Plotting PKL -->
<div id="modal-assign-plotting" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-2xl border border-slate-100 animate-in fade-in duration-150">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Atur Penugasan PKL</h3>
                <p id="assign-student-meta" class="text-xs text-slate-500 font-medium"></p>
            </div>
            <button type="button" onclick="document.getElementById('modal-assign-plotting').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="assign-plotting-form" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilih Mitra DU/DI</label>
                <select id="assign-company-id" name="company_id" required 
                        class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                    <option value="">-- Pilih Perusahaan Mitra --</option>
                    @foreach($companies as $cmp)
                        <option value="{{ $cmp->id }}">{{ $cmp->name }} ({{ $cmp->contact_person }})</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-400 mt-1">Hanya menampilkan mitra industri yang terhubung ke jurusan {{ $major->name }}.</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilih Guru Pembimbing</label>
                <select id="assign-teacher-id" name="teacher_id" required 
                        class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                    <option value="">-- Pilih Guru Pembimbing --</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}">
                            {{ $t->user->name }} 
                            @if($t->id === $myTeacherId) (Anda / Kepala Jurusan) @endif
                            [Beban: {{ $t->active_mentees_count }} Siswa]
                        </option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-400 mt-1">Anda dapat menugaskan diri sendiri atau rekan guru pembimbing lainnya.</p>
            </div>

            <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-assign-plotting').classList.add('hidden')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md">
                    Simpan Penugasan
                </x-button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openAssignPlottingModal(data) {
        document.getElementById('assign-plotting-form').action = data.action_url;
        document.getElementById('assign-student-meta').innerText = data.student_name + ' (' + data.class_name + ')';
        document.getElementById('assign-company-id').value = data.company_id || '';
        document.getElementById('assign-teacher-id').value = data.teacher_id || '';
        document.getElementById('modal-assign-plotting').classList.remove('hidden');
    }
</script>
@endpush
@endsection
