@props([
    'visits',
    'pdfRouteName',
    'currentTeacherId' => null,
    'emptyTitle' => 'Belum Ada Catatan Kunjungan',
    'emptyDescription' => 'Belum ada laporan kunjungan monitoring yang tercatat.',
])

<div class="divide-y divide-slate-100">
    @forelse ($visits as $visit)
        @php
            $coveredStudents = $visit->studentPkls;
            $visitorName = $visit->teacher->user->name ?? '-';
            $isVisitorSelf = $currentTeacherId && (int) $visit->teacher_id === (int) $currentTeacherId;
            $hasDifferentAdvisor = $coveredStudents->contains(fn ($spkl) => (int) $spkl->teacher_id !== (int) $visit->teacher_id);
        @endphp
        <div class="p-5 sm:p-6 hover:bg-slate-50/60 transition-colors flex flex-col lg:flex-row lg:items-start justify-between gap-5">
            <div class="space-y-3 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border {{ $visit->stage_badge_class }}">
                        {{ $visit->stage_label }}
                    </span>
                    <span class="text-xs font-bold text-slate-400">&bull;</span>
                    <span class="text-xs font-bold text-slate-700 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ $visit->visit_date ? $visit->visit_date->translatedFormat('d F Y') : '-' }}
                    </span>
                    <span class="text-xs font-bold text-slate-400">&bull;</span>
                    <span class="text-xs font-medium text-slate-500">{{ $visit->pklPeriod->name ?? 'Periode PKL' }}</span>

                    @if($visit->assignment?->assignment_number)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            ST: {{ $visit->assignment->assignment_number }}
                        </span>
                    @endif

                    @if($currentTeacherId)
                        @if($isVisitorSelf && $hasDifferentAdvisor)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-200">
                                Petugas Penugasan
                            </span>
                        @elseif(!$isVisitorSelf)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                Dimonitoring oleh: {{ $visitorName }}
                            </span>
                        @endif
                    @endif
                </div>

                <div>
                    <h3 class="text-sm sm:text-base font-black text-slate-900">{{ $visit->company->name ?? 'Perusahaan Mitra' }}</h3>
                    <div class="text-xs text-slate-500 mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1">
                        <span>{{ $visit->company->address ?? '-' }}</span>
                        <span>&bull; Petugas Lapangan: <strong class="text-slate-700">{{ $visitorName }}</strong></span>
                    </div>
                </div>

                @if($coveredStudents->isNotEmpty())
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-[11px] font-bold text-slate-400 mr-1">Siswa ({{ $coveredStudents->count() }}):</span>
                        @foreach($coveredStudents as $spkl)
                            @php
                                $advisorName = $spkl->teacher->user->name ?? '-';
                                $diffAdvisor = (int) $spkl->teacher_id !== (int) $visit->teacher_id;
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-semibold {{ $diffAdvisor ? 'bg-indigo-50 text-indigo-800 border border-indigo-100' : 'bg-slate-100 text-slate-700' }}"
                                  title="Pembimbing Asli: {{ $advisorName }}">
                                {{ $spkl->student->user->name ?? '-' }}
                                @if($diffAdvisor)
                                    <span class="text-[10px] text-indigo-500 font-normal">(Pemb: {{ $advisorName }})</span>
                                @endif
                            </span>
                        @endforeach
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
                    <div class="bg-slate-50 border border-slate-100 rounded-2xl p-3.5 text-xs text-slate-700">
                        <div class="font-bold text-indigo-700 mb-1 flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Kemajuan & Kedisiplinan Siswa:
                        </div>
                        <p class="line-clamp-3 leading-relaxed">{{ $visit->student_progress_summary }}</p>
                    </div>

                    <div class="bg-slate-50 border border-slate-100 rounded-2xl p-3.5 text-xs text-slate-700">
                        <div class="font-bold text-emerald-700 mb-1 flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                            </svg>
                            Masukan dari Pembimbing DU/DI:
                        </div>
                        <p class="line-clamp-3 leading-relaxed">{{ $visit->company_feedback }}</p>
                    </div>
                </div>

                @if($visit->follow_up_agreement)
                    <div class="text-xs bg-amber-50/70 border border-amber-100 text-amber-900 rounded-xl p-3">
                        <span class="font-bold">Tindak Lanjut:</span> {{ $visit->follow_up_agreement }}
                    </div>
                @endif

                @if($visit->photo_with_students_path || $visit->photo_with_supervisor_path)
                    <div class="flex items-center gap-3 pt-1">
                        <span class="text-xs font-bold text-slate-400">Dokumentasi Foto:</span>
                        @if($visit->photo_with_students_path)
                            <a href="{{ Storage::url($visit->photo_with_students_path) }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:underline">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Foto Siswa
                            </a>
                        @endif
                        @if($visit->photo_with_supervisor_path)
                            <a href="{{ Storage::url($visit->photo_with_supervisor_path) }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:underline">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Foto Pembimbing DU/DI
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            <div class="shrink-0 flex items-center lg:self-center">
                <a href="{{ route($pdfRouteName, $visit->id) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200/80 rounded-2xl text-xs font-bold transition-all shadow-2xs">
                    <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Unduh Berita Acara (PDF)</span>
                </a>
            </div>
        </div>
    @empty
        <div class="p-12 text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-slate-100 rounded-3xl flex items-center justify-center text-slate-400">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h4 class="text-sm sm:text-base font-bold text-slate-800">{{ $emptyTitle }}</h4>
            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">{{ $emptyDescription }}</p>
        </div>
    @endforelse
</div>

@if($visits->hasPages())
    <div class="p-4 border-t border-slate-100">
        {{ $visits->links() }}
    </div>
@endif
