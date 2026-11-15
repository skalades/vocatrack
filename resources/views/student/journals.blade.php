@extends('layouts.student-mobile')

@section('title', 'Jurnal PKL — VokaTrack Siswa')

@php
    $studentName = auth()->user()->name ?? 'Raka Pratama';
    $todayStr = \Carbon\Carbon::today()->toDateString();
@endphp

@section('content')
<div class="relative w-full flex flex-col font-sans select-none animate-fade-in pb-20">

    @if(!$spkl)
        <!-- Siswa Belum Di-Plotting -->
        <div class="p-5 pt-8 text-center bg-white rounded-[26px] m-5 border border-amber-200 shadow-sm">
            <div class="w-16 h-16 rounded-full bg-[#FFF1D6] text-[#7A4B00] flex items-center justify-center mx-auto text-2xl font-bold">
                ⏳
            </div>
            <h2 class="font-space font-bold text-lg text-[#10211D] mt-3">Belum Memiliki Penempatan PKL</h2>
            <p class="text-xs text-[#5A6B65] mt-1 max-w-[280px] mx-auto leading-relaxed">
                Anda belum memiliki penugasan PKL aktif. Logbook jurnal harian akan terbuka setelah penempatan mitra DU/DI Anda selesai dilakukan oleh sekolah.
            </p>
            <a href="{{ route('student.home') }}" class="inline-block mt-4 px-5 py-2.5 rounded-full bg-[#0B2B25] text-white text-xs font-bold">
                Kembali ke Beranda
            </a>
        </div>
    @else
        <!-- Top Dark Green Curved Header Banner -->
        <div class="absolute left-0 top-0 right-0 h-[176px] bg-[#0B2B25] rounded-b-[32px] overflow-hidden pointer-events-none">
            <svg width="390" height="176" viewBox="0 0 390 176" aria-hidden="true" class="absolute left-0 top-0 w-full h-full object-cover opacity-60">
                <circle cx="360" cy="20" r="56" fill="none" stroke="#12382F" stroke-width="2"/>
                <circle cx="360" cy="20" r="96" fill="none" stroke="#12382F" stroke-width="2"/>
                <circle cx="360" cy="20" r="136" fill="none" stroke="#12382F" stroke-width="2"/>
            </svg>
        </div>

    <!-- Main Content Container with relative positioning -->
    <div class="relative z-10 flex flex-col px-5 pt-5 box-border">

        <!-- Title & Week Badge Row -->
        <div class="flex items-center justify-between h-11">
            <h1 class="font-space font-bold text-[26px] leading-tight text-white">Jurnal PKL</h1>
            <div class="flex items-center gap-2">
                <span class="bg-[#C6F26B]/16 text-[#C6F26B] rounded-full px-3 py-1.5 text-[12.5px] font-bold">
                    Minggu ke-{{ $currentWeek ?? 6 }}
                </span>
                <a href="{{ route('student.profile') }}" title="Profil Siswa" aria-label="Profil Siswa"
                   class="flex items-center justify-center w-8 h-8 rounded-full bg-white/10 text-white hover:bg-white/20 hover:text-[#C6F26B] transition-colors shrink-0">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M20 21a8 8 0 1 0-16 0"/>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Weekly Horizontal Calendar Strip (5 Days) -->
        <div class="grid grid-cols-5 gap-2 mt-3">
            @foreach($weeklyDays as $wDay)
                <div class="h-[62px] rounded-[18px] flex flex-col items-center justify-center gap-0.5 transition-all {{ $wDay['is_today'] ? 'bg-[#C6F26B] text-[#0B2B25] shadow-xs' : 'bg-white/[0.09] text-[#A9C2B9]' }}"
                     aria-label="{{ $wDay['short'] }} {{ $wDay['date_num'] }}{{ $wDay['is_today'] ? ', Hari ini' : '' }}">
                    <span class="text-[11px] font-bold">{{ $wDay['short'] }}</span>
                    <span class="font-space text-xl font-bold leading-none">{{ $wDay['date_num'] }}</span>
                </div>
            @endforeach
        </div>

        <!-- Today's Journal Form Card -->
        <div class="mt-5 bg-white rounded-[26px] p-4 flex flex-col gap-3 shadow-[0_10px_30px_rgba(11,43,37,0.1)] border border-slate-100">
            
            <form action="{{ route('student.journal.store') }}" method="POST" enctype="multipart/form-data" id="journal-main-form" class="flex flex-col gap-3">
                @csrf
                <input type="hidden" name="date" value="{{ $todayStr }}">
                <input type="hidden" name="action_type" id="journal-action-type" value="submit">

                <!-- Kegiatan Hari Ini & Counter -->
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-baseline justify-between">
                        <label for="kegiatan-input" class="text-sm font-bold text-[#10211D]">Kegiatan hari ini</label>
                        <span id="char-counter" class="text-xs font-semibold text-[#5A6B65]">0 / 300</span>
                    </div>
                    <textarea id="kegiatan-input" name="activity_description" rows="3" required minlength="50"
                              placeholder="Contoh: Memasang kabel UTP dan mengonfigurasi switch di ruang server."
                              class="w-full min-h-[82px] border border-[#CBD5CA] rounded-2xl p-3 text-sm text-[#10211D] bg-[#F7F9F5] focus:bg-white focus:outline-none focus:border-[#0F7A66] transition-colors resize-none leading-relaxed">{{ old('activity_description', $todayJournal->activity_description ?? '') }}</textarea>
                    <span id="min-char-hint" class="text-[11px] text-slate-400">Minimal 50 karakter uraian kegiatan teknis.</span>
                </div>

                <!-- Kompetensi yang dipelajari (Selectable Chips) -->
                <div class="flex flex-col gap-1.5">
                    <span class="text-sm font-bold text-[#10211D]">Kompetensi yang dipelajari</span>
                    <div class="flex gap-2 overflow-x-auto pb-1 -mx-4 px-4 custom-scrollbar">
                        <button type="button" onclick="toggleCompetencyChip(this, 'Instalasi jaringan')" 
                                class="competency-chip shrink-0 min-h-[40px] px-3.5 rounded-full font-bold text-xs border border-[#CBD5CA] bg-white text-[#10211D] hover:border-[#0B2B25] active:scale-95 transition-all cursor-pointer">
                            Instalasi jaringan
                        </button>
                        <button type="button" onclick="toggleCompetencyChip(this, 'Konfigurasi switch')" 
                                class="competency-chip shrink-0 min-h-[40px] px-3.5 rounded-full font-bold text-xs border border-[#CBD5CA] bg-white text-[#10211D] hover:border-[#0B2B25] active:scale-95 transition-all cursor-pointer">
                            Konfigurasi switch
                        </button>
                        <button type="button" onclick="toggleCompetencyChip(this, 'Dokumentasi')" 
                                class="competency-chip shrink-0 min-h-[40px] px-3.5 rounded-full font-bold text-xs border border-[#CBD5CA] bg-white text-[#10211D] hover:border-[#0B2B25] active:scale-95 transition-all cursor-pointer">
                            Dokumentasi
                        </button>
                        <button type="button" onclick="toggleCompetencyChip(this, 'Troubleshooting')" 
                                class="competency-chip shrink-0 min-h-[40px] px-3.5 rounded-full font-bold text-xs border border-[#CBD5CA] bg-white text-[#10211D] hover:border-[#0B2B25] active:scale-95 transition-all cursor-pointer">
                            Troubleshooting
                        </button>
                        <button type="button" onclick="toggleCompetencyChip(this, 'Maintenance server')" 
                                class="competency-chip shrink-0 min-h-[40px] px-3.5 rounded-full font-bold text-xs border border-[#CBD5CA] bg-white text-[#10211D] hover:border-[#0B2B25] active:scale-95 transition-all cursor-pointer">
                            Maintenance server
                        </button>
                    </div>
                </div>

                <!-- Photo Attachment Row -->
                <div class="flex items-center gap-2.5 pt-1">
                    <div id="photo-preview-box" class="relative w-[60px] h-[60px] rounded-[16px] bg-[#12382F] overflow-hidden shrink-0 flex items-center justify-center border border-slate-200">
                        @if($todayJournal && $todayJournal->photos->isNotEmpty())
                            <img src="{{ Storage::url($todayJournal->photos->first()->photo_path) }}" alt="Bukti" class="w-full h-full object-cover">
                        @else
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#C6F26B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                <polyline points="21 15 16 10 5 21"/>
                            </svg>
                        @endif
                    </div>

                    <!-- Add Photo Trigger -->
                    <label for="journal-photos-input" 
                           class="w-[60px] h-[60px] rounded-[16px] border-2 border-dashed border-[#7C8D85] bg-[#F7F9F5] hover:bg-[#EEF2EC] text-[#0B5E4E] flex items-center justify-center shrink-0 cursor-pointer active:scale-95 transition-all">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                    </label>
                    <input type="file" id="journal-photos-input" name="photos[]" accept="image/*" class="hidden" onchange="handleJournalPhotoSelected(this)">

                    <span class="text-xs text-[#5A6B65] leading-relaxed flex-1">
                        Foto dokumentasi kegiatan. Ambil dari kamera atau unggah bukti pekerjaan.
                    </span>
                </div>

                <!-- Simpan Jurnal CTA Button -->
                <button type="submit" 
                        class="mt-2 flex items-center justify-center gap-2.5 h-14 rounded-[28px] bg-[#0B2B25] hover:bg-[#12382F] text-white font-bold text-[15.5px] shadow-[0_8px_20px_rgba(11,43,37,0.2)] active:scale-95 transition-all cursor-pointer">
                    <span class="w-[26px] h-[26px] rounded-full bg-[#C6F26B] text-[#0B2B25] flex items-center justify-center shrink-0">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </span>
                    <span>Simpan Jurnal</span>
                </button>
            </form>

        </div>

        <!-- Riwayat Jurnal Section -->
        <div class="flex items-center justify-between mt-5 h-6">
            <h2 class="font-bold text-base text-[#10211D]">Riwayat jurnal</h2>
            <span class="text-xs font-bold text-[#0B5E4E]">Ketuk untuk melihat detail</span>
        </div>

        <!-- History List Cards -->
        <div class="flex flex-col gap-2 mt-2">
            @forelse($journals as $journal)
                @php
                    $jDate = \Carbon\Carbon::parse($journal->date);
                    $status = $journal->status;
                    $isApproved = ($status === 'approved');
                    $isRevision = ($status === 'revision');
                    $isReview = in_array($status, ['submitted', 'review_guru']);

                    $journalJson = [
                        'id' => $journal->id,
                        'date_formatted' => $jDate->isoFormat('dddd, D MMMM Y'),
                        'description' => $journal->activity_description,
                        'status' => $journal->status,
                        'status_label' => match($journal->status) {
                            'approved' => 'Disetujui',
                            'revision' => 'Perlu Revisi',
                            'draft' => 'Draft',
                            default => 'Menunggu Review',
                        },
                        'status_class' => match($journal->status) {
                            'approved' => 'bg-[#DDF3EC] text-[#0B5E4E]',
                            'revision' => 'bg-[#FFF1D6] text-[#7A4B00]',
                            'draft' => 'bg-slate-100 text-slate-700',
                            default => 'bg-sky-50 text-sky-700',
                        },
                        'reviewer_name' => $journal->approvals->first()?->approver?->name ?? 'Pembimbing',
                        'reviewer_notes' => $journal->approvals->first()?->notes,
                        'photos' => $journal->photos->map(fn($p) => asset('storage/' . $p->photo_path))->toArray(),
                        'is_editable' => in_array($journal->status, ['draft', 'revision']),
                    ];
                @endphp
                <div onclick="openJournalDetail(this)" 
                     data-journal="{{ json_encode($journalJson) }}"
                     class="bg-white rounded-[18px] p-2.5 px-3 flex items-center gap-3 shadow-xs border border-slate-100 hover:border-[#0F7A66] hover:shadow-md transition-all cursor-pointer group active:scale-[0.99]"
                     title="Ketuk untuk melihat detail jurnal">
                    
                    <!-- Date Box -->
                    <div class="w-11 h-12 rounded-xl bg-[#EBF0E7] flex flex-col items-center justify-center shrink-0 group-hover:bg-[#DDF3EC] transition-colors">
                        <span class="font-space font-bold text-lg leading-tight text-[#10211D]">{{ $jDate->format('d') }}</span>
                        <span class="text-[10px] font-bold text-[#5A6B65] uppercase leading-none">{{ $jDate->format('M') }}</span>
                    </div>

                    <!-- Journal Activity Summary -->
                    <div class="flex-1 min-w-0">
                        <span class="font-bold text-sm text-[#10211D] block truncate leading-tight group-hover:text-[#0F7A66] transition-colors">
                            {{ Str::limit($journal->activity_description, 40) }}
                        </span>
                        <span class="text-xs text-[#5A6B65] block truncate mt-0.5">
                            @if($isApproved)
                                Disetujui oleh Pembimbing
                            @elseif($isRevision)
                                <span class="text-amber-700 font-semibold">Perlu Revisi</span>
                            @else
                                Menunggu Review
                            @endif
                        </span>
                    </div>

                    <!-- Status Pill & Chevron -->
                    <div class="flex items-center gap-1.5 shrink-0">
                        @if($isApproved)
                            <span class="bg-[#DDF3EC] text-[#0B5E4E] rounded-full px-2.5 py-1 text-xs font-bold">
                                Disetujui
                            </span>
                        @elseif($isRevision)
                            <span class="bg-[#FFF1D6] text-[#7A4B00] rounded-full px-2.5 py-1 text-xs font-bold">
                                Revisi
                            </span>
                        @else
                            <span class="bg-slate-100 text-slate-700 rounded-full px-2.5 py-1 text-xs font-bold">
                                Review
                            </span>
                        @endif

                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#A9B5AF" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="group-hover:translate-x-0.5 group-hover:stroke-[#0B2B25] transition-all">
                            <path d="m9 18 6-6-6-6"/>
                        </svg>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-[20px] p-6 text-center text-slate-400 text-xs border border-slate-100">
                    Belum ada riwayat jurnal. Isi kegiatan praktik Anda di formulir atas.
                </div>
            @endforelse

            <!-- Pagination links if available -->
            @if(method_exists($journals, 'hasPages') && $journals->hasPages())
                <div class="mt-2">
                    {{ $journals->links() }}
                </div>
            @endif
        </div>

    </div>
    @endif
</div>

<!-- Modal Detail Jurnal Siswa -->
<div id="modal-journal-detail" onclick="if(event.target === this) closeJournalDetail()" 
     class="hidden fixed inset-0 z-50 bg-slate-950/75 backdrop-blur-md flex items-center justify-center p-3.5 sm:p-4 animate-in fade-in duration-200">
    <div class="bg-white w-full max-w-sm sm:max-w-md rounded-3xl shadow-2xl max-h-[86vh] flex flex-col border border-slate-100 overflow-hidden my-auto transform animate-in zoom-in-95 duration-200">
        
        <!-- Modal Sticky Header -->
        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between gap-2 shrink-0 bg-white">
            <div class="flex items-center gap-2 min-w-0">
                <span id="jd-status-badge" class="text-[11px] font-bold px-2.5 py-0.5 rounded-full shrink-0"></span>
                <span id="jd-date" class="text-xs font-bold text-[#10211D] truncate"></span>
            </div>
            <button type="button" onclick="closeJournalDetail()" title="Tutup" 
                    class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 active:bg-slate-300 text-slate-500 hover:text-slate-800 flex items-center justify-center shrink-0 transition-colors cursor-pointer">
                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="overflow-y-auto px-5 py-4 space-y-4 custom-scrollbar flex-1">
            
            <!-- Reviewer Feedback Callout (if any) -->
            <div id="jd-reviewer-card" class="hidden p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 space-y-1">
                <div class="flex items-center gap-1.5 text-xs font-bold text-amber-800">
                    <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                    <span id="jd-reviewer-title">Catatan Pembimbing</span>
                </div>
                <p id="jd-reviewer-notes" class="text-xs text-amber-900 leading-relaxed font-medium whitespace-pre-line"></p>
            </div>

            <!-- Kegiatan Box -->
            <div class="space-y-1.5">
                <span class="text-[11px] font-bold text-[#5A6B65] uppercase tracking-wider block">Uraian Kegiatan Praktik</span>
                <div class="bg-[#F7F9F5] rounded-2xl p-4 border border-[#CBD5CA]/60">
                    <p id="jd-description" class="text-xs text-[#10211D] leading-relaxed whitespace-pre-line font-medium"></p>
                </div>
            </div>

            <!-- Dokumentasi Foto -->
            <div id="jd-photos-section" class="space-y-1.5 hidden">
                <span class="text-[11px] font-bold text-[#5A6B65] uppercase tracking-wider block">Foto Dokumentasi</span>
                <div id="jd-photos-grid" class="grid grid-cols-2 gap-2"></div>
            </div>

        </div>

        <!-- Modal Footer -->
        <div class="p-3.5 sm:p-4 bg-white border-t border-slate-100 shrink-0 flex items-center gap-2">
            <button type="button" id="jd-edit-btn" class="hidden flex-1 min-h-[44px] py-2.5 px-4 rounded-2xl bg-[#0B2B25] hover:bg-[#12382F] active:scale-[0.98] text-white text-xs font-bold shadow-xs flex items-center justify-center gap-1.5 transition-all cursor-pointer">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                </svg>
                <span>Edit Jurnal Ini</span>
            </button>
            <button type="button" onclick="closeJournalDetail()" class="flex-1 min-h-[44px] py-2.5 px-4 rounded-2xl bg-slate-100 hover:bg-slate-200 active:scale-[0.98] text-[#10211D] text-xs font-bold flex items-center justify-center transition-all cursor-pointer">
                Tutup
            </button>
        </div>

    </div>
</div>

@push('scripts')
<script>
    // Live Character Counter & Validation Guide
    const textarea = document.getElementById('kegiatan-input');
    const counter = document.getElementById('char-counter');
    const hint = document.getElementById('min-char-hint');

    function updateCounter() {
        const len = textarea.value.length;
        if (counter) counter.textContent = `${len} / 300`;
        if (hint) {
            if (len >= 50) {
                hint.textContent = '✓ Karakter telah memenuhi standar uraian kegiatan.';
                hint.className = 'text-[11px] text-[#0B5E4E] font-semibold';
            } else {
                hint.textContent = `Minimal 50 karakter (${50 - len} karakter lagi).`;
                hint.className = 'text-[11px] text-slate-400';
            }
        }
    }

    if (textarea) {
        textarea.addEventListener('input', updateCounter);
        updateCounter();
    }

    // Competency Chip Click handler
    function toggleCompetencyChip(btn, label) {
        const isSelected = btn.classList.contains('bg-[#0B2B25]');
        if (isSelected) {
            btn.className = 'competency-chip shrink-0 min-h-[40px] px-3.5 rounded-full font-bold text-xs border border-[#CBD5CA] bg-white text-[#10211D] hover:border-[#0B2B25] active:scale-95 transition-all cursor-pointer';
        } else {
            btn.className = 'competency-chip shrink-0 min-h-[40px] px-3.5 rounded-full font-bold text-xs border border-[#0B2B25] bg-[#0B2B25] text-white active:scale-95 transition-all cursor-pointer shadow-xs';
            
            // Append competency label to textarea if not already present
            if (textarea && !textarea.value.includes(label)) {
                if (textarea.value.trim().length > 0) {
                    textarea.value += ' · ' + label;
                } else {
                    textarea.value = label + ': ';
                }
                updateCounter();
            }
        }
    }

    // Photo preview handler
    function handleJournalPhotoSelected(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const box = document.getElementById('photo-preview-box');
                if (box) {
                    box.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Modal Detail Jurnal Handler
    function openJournalDetail(el) {
        try {
            const data = JSON.parse(el.getAttribute('data-journal'));

            document.getElementById('jd-date').textContent = data.date_formatted;
            
            const badge = document.getElementById('jd-status-badge');
            badge.className = `text-[11px] font-bold px-2.5 py-0.5 rounded-full shrink-0 ${data.status_class}`;
            badge.textContent = data.status_label;

            document.getElementById('jd-description').textContent = data.description;

            // Reviewer Feedback card
            const reviewerCard = document.getElementById('jd-reviewer-card');
            const reviewerNotes = document.getElementById('jd-reviewer-notes');
            const reviewerTitle = document.getElementById('jd-reviewer-title');
            if (data.reviewer_notes && data.reviewer_notes.trim().length > 0) {
                reviewerTitle.textContent = `Catatan dari ${data.reviewer_name}`;
                reviewerNotes.textContent = data.reviewer_notes;
                reviewerCard.classList.remove('hidden');
            } else {
                reviewerCard.classList.add('hidden');
            }

            // Documentation Photos
            const photosSection = document.getElementById('jd-photos-section');
            const photosGrid = document.getElementById('jd-photos-grid');
            photosGrid.innerHTML = '';
            if (data.photos && data.photos.length > 0) {
                data.photos.forEach((src) => {
                    const div = document.createElement('div');
                    div.className = 'relative aspect-4/3 rounded-xl overflow-hidden bg-slate-100 border border-slate-200/80 cursor-zoom-in group';
                    div.onclick = () => window.openImageLightbox && window.openImageLightbox(src);
                    div.innerHTML = `<img src="${src}" alt="Dokumentasi Jurnal" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">`;
                    photosGrid.appendChild(div);
                });
                photosSection.classList.remove('hidden');
            } else {
                photosSection.classList.add('hidden');
            }

            // Edit button (for draft or revision status)
            const editBtn = document.getElementById('jd-edit-btn');
            if (data.is_editable) {
                editBtn.classList.remove('hidden');
                editBtn.onclick = function() {
                    closeJournalDetail();
                    const textareaEl = document.getElementById('kegiatan-input');
                    if (textareaEl) {
                        textareaEl.value = data.description;
                        updateCounter();
                        textareaEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        textareaEl.focus();
                    }
                };
            } else {
                editBtn.classList.add('hidden');
            }

            document.getElementById('modal-journal-detail').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        } catch (e) {
            console.error('Failed to open journal detail:', e);
        }
    }

    function closeJournalDetail() {
        const modal = document.getElementById('modal-journal-detail');
        if (modal) modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
</script>
@endpush
@endsection
