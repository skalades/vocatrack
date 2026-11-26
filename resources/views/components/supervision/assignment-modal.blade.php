@props([
    'actionUrl',
    'periods',
    'selectedPeriodId',
    'teachers',
    'matrix',
    'modalId' => 'modal-create-assignment',
    'title' => 'Buat Penugasan Monitoring (Multi-DU/DI)',
    'subtitle' => 'Tugaskan satu guru atau Kajur untuk melakukan Monitoring 1 atau Monitoring 2 ke satu atau beberapa perusahaan sekaligus.',
])

<div id="{{ $modalId }}" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto hidden">
    <div class="bg-white rounded-3xl max-w-4xl w-full shadow-2xl border border-slate-100 overflow-hidden my-8">
        <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
            <div>
                <h3 class="text-base sm:text-lg font-black text-slate-900">{{ $title }}</h3>
                <p class="text-xs text-slate-500 mt-0.5">{{ $subtitle }}</p>
            </div>
            <button type="button" onclick="document.getElementById('{{ $modalId }}').classList.add('hidden')" class="p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ $actionUrl }}" method="POST" class="p-5 sm:p-6 space-y-5">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Periode PKL <span class="text-rose-500">*</span></label>
                    <select name="pkl_period_id" required class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($periods as $period)
                            <option value="{{ $period->id }}" {{ (int) $selectedPeriodId === (int) $period->id ? 'selected' : '' }}>
                                {{ $period->name }} {{ $period->is_active ? '(Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Tahap Monitoring <span class="text-rose-500">*</span></label>
                    <select name="stage" id="{{ $modalId }}-stage" required onchange="refreshAssignmentMatrixAvailability('{{ $modalId }}')" class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="monitoring_1">Monitoring 1 (Bulan Ke-1)</option>
                        <option value="monitoring_2">Monitoring 2 (Pertengahan)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Petugas Monitoring (Guru / Kajur) <span class="text-rose-500">*</span></label>
                    <select name="teacher_id" required class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">-- Pilih Guru / Kajur --</option>
                        @foreach($teachers as $t)
                            @php
                                $headed = $t->headedMajors->pluck('code')->implode(', ');
                            @endphp
                            <option value="{{ $t->id }}">
                                {{ $t->user->name ?? 'Guru' }}{{ $headed ? " [Kajur {$headed}]" : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">No. Surat Tugas (Opsional)</label>
                    <input type="text" name="assignment_number" placeholder="Contoh: ST-MON/024/2026" class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Target Tanggal (Opsional)</label>
                    <input type="date" name="scheduled_date" class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Catatan Tugas (Opsional)</label>
                    <input type="text" name="notes" placeholder="Contoh: Rute kawasan industri selatan" class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            <!-- Multi-DU/DI & Siswa Selection -->
            <div class="space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Pilih Perusahaan (DU/DI) & Siswa Target <span class="text-rose-500">*</span></label>
                        <p class="text-[11px] text-slate-500">Centang satu atau beberapa DU/DI. Siswa yang sudah selesai pada tahap ini otomatis dikunci.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="text" oninput="filterAssignmentCompanies('{{ $modalId }}', this.value)" placeholder="Cari nama DU/DI..." class="text-xs px-3 py-1.5 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                        <span id="{{ $modalId }}-summary" class="text-[11px] font-extrabold px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100 whitespace-nowrap">0 siswa dipilih</span>
                    </div>
                </div>

                <div class="max-h-72 overflow-y-auto border border-slate-200 rounded-2xl divide-y divide-slate-100 bg-slate-50/40" id="{{ $modalId }}-list">
                    @forelse($matrix as $row)
                        @php $company = $row['company']; @endphp
                        <div class="p-3.5 bg-white hover:bg-slate-50/60 transition-colors assignment-company-row" data-company-name="{{ strtolower($company->name ?? '') }}">
                            <div class="flex items-start justify-between gap-3">
                                <label class="flex items-start gap-2.5 cursor-pointer flex-1">
                                    <input type="checkbox"
                                           class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 assignment-company-checkbox"
                                           data-modal="{{ $modalId }}"
                                           data-company="{{ $company->id }}"
                                           onchange="toggleCompanyStudents('{{ $modalId }}', {{ $company->id }}, this.checked)">
                                    <div class="min-w-0">
                                        <div class="text-xs font-black text-slate-900">{{ $company->name }}</div>
                                        <div class="text-[11px] text-slate-500 truncate">{{ $company->address ?? '-' }}</div>
                                    </div>
                                </label>
                                <div class="flex items-center gap-2 shrink-0 text-[10px] font-bold">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700">{{ $row['students_count'] }} siswa</span>
                                    <button type="button" onclick="document.getElementById('{{ $modalId }}-students-{{ $company->id }}').classList.toggle('hidden')" class="text-indigo-600 hover:underline">
                                        Detail Siswa
                                    </button>
                                </div>
                            </div>

                            <div id="{{ $modalId }}-students-{{ $company->id }}" class="mt-2.5 pl-6 space-y-1.5 hidden">
                                @foreach($row['students'] as $stRow)
                                    @php
                                        $spkl = $stRow['student_pkl'];
                                        $m1 = $stRow['monitoring_1'];
                                        $m2 = $stRow['monitoring_2'];
                                    @endphp
                                    <label class="flex items-center justify-between gap-2 p-2 rounded-xl border border-slate-100 bg-slate-50/70 text-[11px] assignment-student-item"
                                           data-company="{{ $company->id }}"
                                           data-m1-status="{{ $m1['status'] }}"
                                           data-m2-status="{{ $m2['status'] }}">
                                        <span class="flex items-center gap-2 min-w-0">
                                            <input type="checkbox"
                                                   name="student_pkl_ids[]"
                                                   value="{{ $spkl->id }}"
                                                   data-company="{{ $company->id }}"
                                                   data-m1-status="{{ $m1['status'] }}"
                                                   data-m2-status="{{ $m2['status'] }}"
                                                   onchange="updateAssignmentSummary('{{ $modalId }}')"
                                                   class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 assignment-student-checkbox">
                                            <span class="font-bold text-slate-800 truncate">{{ $spkl->student->user->name ?? '-' }}</span>
                                            <span class="text-slate-400">({{ $spkl->student->class->name ?? '-' }})</span>
                                            <span class="text-slate-500">&bull; Pembimbing: <strong class="text-slate-700">{{ $spkl->teacher->user->name ?? '-' }}</strong></span>
                                        </span>
                                        <span class="shrink-0 text-[10px] font-bold student-stage-status-label"></span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-xs text-slate-500">
                            Belum ada siswa PKL aktif pada periode yang dipilih.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="document.getElementById('{{ $modalId }}').classList.add('hidden')" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-sm shadow-indigo-200 transition-all">
                    Simpan Penugasan Monitoring
                </button>
            </div>
        </form>
    </div>
</div>

@once
<script>
    function refreshAssignmentMatrixAvailability(modalId) {
        const stageSelect = document.getElementById(modalId + '-stage');
        const stage = stageSelect ? stageSelect.value : 'monitoring_1';
        const attr = stage === 'monitoring_2' ? 'data-m2-status' : 'data-m1-status';
        const container = document.getElementById(modalId + '-list');
        if (!container) return;

        container.querySelectorAll('.assignment-student-item').forEach(row => {
            const status = row.getAttribute(attr);
            const checkbox = row.querySelector('.assignment-student-checkbox');
            const labelEl = row.querySelector('.student-stage-status-label');

            if (status === 'completed') {
                checkbox.checked = false;
                checkbox.disabled = true;
                row.classList.add('opacity-55');
                labelEl.textContent = 'Sudah Selesai';
                labelEl.className = 'shrink-0 text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 student-stage-status-label';
            } else if (status === 'assigned') {
                checkbox.disabled = false;
                row.classList.remove('opacity-55');
                labelEl.textContent = 'Sudah Ada Tugas (Akan Dialihkan)';
                labelEl.className = 'shrink-0 text-[10px] font-bold px-2 py-0.5 rounded bg-amber-50 text-amber-700 student-stage-status-label';
            } else {
                checkbox.disabled = false;
                row.classList.remove('opacity-55');
                labelEl.textContent = 'Belum Ditugaskan';
                labelEl.className = 'shrink-0 text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-600 student-stage-status-label';
            }
        });

        container.querySelectorAll('.assignment-company-checkbox').forEach(companyCb => {
            const companyId = companyCb.getAttribute('data-company');
            const enabledStudents = container.querySelectorAll(`.assignment-student-checkbox[data-company="${companyId}"]:not(:disabled)`);
            companyCb.disabled = enabledStudents.length === 0;
            if (enabledStudents.length === 0) companyCb.checked = false;
        });

        updateAssignmentSummary(modalId);
    }

    function toggleCompanyStudents(modalId, companyId, checked) {
        const container = document.getElementById(modalId + '-list');
        if (!container) return;
        container.querySelectorAll(`.assignment-student-checkbox[data-company="${companyId}"]:not(:disabled)`).forEach(cb => {
            cb.checked = checked;
        });
        updateAssignmentSummary(modalId);
    }

    function updateAssignmentSummary(modalId) {
        const container = document.getElementById(modalId + '-list');
        const summary = document.getElementById(modalId + '-summary');
        if (!container || !summary) return;
        const checkedBoxes = container.querySelectorAll('.assignment-student-checkbox:checked');
        const companies = new Set();
        checkedBoxes.forEach(cb => companies.add(cb.getAttribute('data-company')));
        summary.textContent = `${companies.size} DU/DI · ${checkedBoxes.length} siswa dipilih`;
    }

    function filterAssignmentCompanies(modalId, keyword) {
        const container = document.getElementById(modalId + '-list');
        if (!container) return;
        const q = (keyword || '').toLowerCase().trim();
        container.querySelectorAll('.assignment-company-row').forEach(row => {
            const name = row.getAttribute('data-company-name') || '';
            row.classList.toggle('hidden', q !== '' && !name.includes(q));
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[id$="-stage"]').forEach(el => {
            refreshAssignmentMatrixAvailability(el.id.replace('-stage', ''));
        });
    });
</script>
@endonce
