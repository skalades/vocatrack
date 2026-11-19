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
        @if(!$isPklStarted)
            <div class="mt-4 p-4 rounded-3xl bg-[#F4FAF6] border border-[#D5EADB] text-[#0B2B25] flex items-center gap-3">
                <span class="w-8 h-8 rounded-full bg-[#E0EBF5] text-[#1E5680] flex items-center justify-center shrink-0 font-bold">
                    ℹ
                </span>
                <div class="text-xs leading-relaxed text-[#36504A]">
                    <strong>Pelaksanaan PKL Belum Dimulai:</strong> Jadwal PKL di {{ $spkl->company->name ?? 'industri' }} belum resmi dimulai. Pengisian jurnal harian saat ini dianulir / belum diwajibkan.
                </div>
            </div>
        @endif

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

                <!-- Capaian Pembelajaran / Elemen Kompetensi Terkait -->
                <div class="flex flex-col gap-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-[#10211D]">Capaian Pembelajaran (CP) Terkait</span>
                        <span class="text-[11px] text-slate-400">Pilih satu atau lebih</span>
                    </div>
                    @if(isset($learningObjectives) && $learningObjectives->isNotEmpty())
                        <div class="flex gap-2 overflow-x-auto pb-1.5 -mx-4 px-4 custom-scrollbar">
                            @foreach($learningObjectives as $lo)
                                @php
                                    $isSelected = ($todayJournal && $todayJournal->learningObjectives->contains('id', $lo->id)) || in_array($lo->id, (array) old('learning_objective_ids', []));
                                @endphp
                                <label class="lo-chip shrink-0 min-h-[38px] px-3.5 py-1.5 rounded-full font-bold text-xs border transition-all cursor-pointer select-none flex items-center gap-1.5 {{ $isSelected ? 'bg-[#0B2B25] text-white border-[#0B2B25] shadow-xs' : 'border-[#CBD5CA] bg-white text-[#10211D] hover:border-[#0B2B25]' }}"
                                       title="{{ $lo->title }} ({{ $lo->element_name }})">
                                    <input type="checkbox" name="learning_objective_ids[]" value="{{ $lo->id }}" {{ $isSelected ? 'checked' : '' }} class="hidden" onchange="handleLoChipChange(this)">
                                    <span class="font-mono text-[11px] opacity-80">{{ $lo->code }}</span>
                                    <span class="max-w-[200px] truncate">{{ $lo->title }}</span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-slate-400 text-xs">
                            Belum ada Capaian Pembelajaran aktif untuk jurusan ini. Tuliskan uraian kegiatan langsung pada kolom di atas.
                        </div>
                    @endif
                </div>

                <!-- Photo Attachment Row -->
                <div class="flex items-center gap-2.5 pt-1">
                    <div id="photo-preview-box" class="relative w-[60px] h-[60px] rounded-[16px] bg-[#12382F] overflow-hidden shrink-0 flex items-center justify-center border border-slate-200">
                        @if($todayJournal && $todayJournal->photos->isNotEmpty())
                            <img src="{{ $todayJournal->photos->first()->photo_url }}" alt="Bukti" class="w-full h-full object-cover">
                        @else
                            <svg id="photo-preview-placeholder" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#C6F26B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                <polyline points="21 15 16 10 5 21"/>
                            </svg>
                        @endif
                    </div>

                    <!-- Add Photo Trigger -->
                    <label for="journal-photos-input" id="journal-photo-label"
                           class="w-[60px] h-[60px] rounded-[16px] border-2 border-dashed border-[#7C8D85] bg-[#F7F9F5] hover:bg-[#EEF2EC] text-[#0B5E4E] flex items-center justify-center shrink-0 cursor-pointer active:scale-95 transition-all"
                           title="Ambil foto atau pilih dari galeri">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                    </label>
                    <input type="file" id="journal-photos-input" name="photos[]" accept="image/*,.heic,.heif,.HEIC,.HEIF" class="hidden" onchange="handleJournalPhotoSelected(this)">
                    <input type="hidden" name="compressed_photos[]" id="journal-compressed-photo">

                    <div id="photo-helper-text" class="text-xs text-[#5A6B65] leading-relaxed flex-1">
                        Foto dokumentasi kegiatan. Ambil dari kamera atau unggah bukti pekerjaan.
                    </div>
                </div>

                <!-- Simpan Jurnal CTA Button -->
                <button type="submit" {{ !$isPklStarted ? 'disabled' : '' }}
                        class="mt-2 flex items-center justify-center gap-2.5 h-14 rounded-[28px] bg-[#0B2B25] hover:bg-[#12382F] text-white font-bold text-[15.5px] shadow-[0_8px_20px_rgba(11,43,37,0.2)] active:scale-95 transition-all {{ !$isPklStarted ? 'opacity-50 cursor-not-allowed pointer-events-none' : 'cursor-pointer' }}">
                    <span class="w-[26px] h-[26px] rounded-full {{ !$isPklStarted ? 'bg-slate-400 text-white' : 'bg-[#C6F26B] text-[#0B2B25]' }} flex items-center justify-center shrink-0">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </span>
                    <span>{{ !$isPklStarted ? 'PKL Belum Dimulai' : 'Simpan Jurnal' }}</span>
                </button>
            </form>

        </div>

        <!-- Jurnal yang ditulis offline & menunggu dikirim (diisi oleh resources/js/student-pwa.js) -->
        <div id="offline-journal-queue" class="hidden mt-5" aria-live="polite"></div>

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
                        'photos' => $journal->photos->map(fn($p) => $p->photo_url)->toArray(),
                        'learning_objectives' => $journal->learningObjectives->map(fn($lo) => [
                            'id' => $lo->id,
                            'code' => $lo->code,
                            'title' => $lo->title,
                            'element_name' => $lo->element_name,
                        ])->toArray(),
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
                        <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                            @if($journal->learningObjectives->isNotEmpty())
                                @foreach($journal->learningObjectives->take(2) as $cp)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200/60 font-mono">
                                        {{ $cp->code }}
                                    </span>
                                @endforeach
                                @if($journal->learningObjectives->count() > 2)
                                    <span class="text-[10px] text-slate-400 font-bold">+{{ $journal->learningObjectives->count() - 2 }}</span>
                                @endif
                            @endif
                            <span class="text-xs text-[#5A6B65] truncate">
                                @if($isApproved)
                                    Disetujui
                                @elseif($isRevision)
                                    <span class="text-amber-700 font-semibold">Perlu Revisi</span>
                                @else
                                    Menunggu Review
                                @endif
                            </span>
                        </div>
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
     class="hidden fixed inset-0 z-[85] bg-slate-900/60 backdrop-blur-xs flex flex-col justify-start sm:justify-center items-center p-3 pt-14 sm:pt-16 transition-all">
    <div class="bg-white w-full max-w-[400px] rounded-3xl shadow-2xl max-h-[85vh] flex flex-col border border-slate-200/80 overflow-hidden my-auto transform animate-in zoom-in-95 duration-200">
        
        <!-- Modal Sticky Header -->
        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between shrink-0 bg-white">
            <div class="flex items-center space-x-2.5 min-w-0 flex-1 pr-2">
                <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-[#EBF6F0] text-[#0F7A66] flex items-center justify-center font-bold text-xs shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-xs sm:text-sm font-extrabold text-[#10211D] truncate leading-tight font-space">Detail Jurnal PKL</h3>
                    <div class="flex items-center gap-1.5 mt-0.5 truncate">
                        <span id="jd-date" class="text-[10px] sm:text-[11px] text-slate-400 font-medium truncate"></span>
                        <span id="jd-status-badge" class="text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0"></span>
                    </div>
                </div>
            </div>
            <button type="button" onclick="closeJournalDetail()" aria-label="Tutup" 
                    class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="overflow-y-auto p-4 sm:p-5 space-y-3.5 custom-scrollbar flex-1">
            
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

            <!-- Capaian Pembelajaran Terkait -->
            <div id="jd-lo-section" class="space-y-1.5 hidden">
                <span class="text-[11px] font-bold text-[#5A6B65] uppercase tracking-wider block">Capaian Pembelajaran (CP) Terkait</span>
                <div id="jd-lo-list" class="flex flex-wrap gap-1.5"></div>
            </div>

            <!-- Dokumentasi Foto -->
            <div id="jd-photos-section" class="space-y-1.5 hidden">
                <span class="text-[11px] font-bold text-[#5A6B65] uppercase tracking-wider block">Foto Dokumentasi</span>
                <div id="jd-photos-grid" class="grid grid-cols-2 gap-2"></div>
            </div>

        </div>

        <!-- Modal Footer -->
        <div class="p-4 pt-3 bg-white border-t border-slate-100 shrink-0 flex items-center gap-2.5">
            <button type="button" id="jd-edit-btn" class="hidden flex-1 h-11 rounded-2xl bg-[#0B2B25] hover:bg-[#12382F] active:scale-[0.98] text-white text-xs sm:text-sm font-bold shadow-xs flex items-center justify-center gap-1.5 transition-all cursor-pointer">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                </svg>
                <span>Edit Jurnal Ini</span>
            </button>
            <button type="button" onclick="closeJournalDetail()" class="flex-1 h-11 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs sm:text-sm font-bold active:scale-[0.98] transition-all cursor-pointer">
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
                hint.textContent = 'Karakter telah memenuhi standar uraian kegiatan.';
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

    // Learning Objective (CP) Chip Selection Handler
    function handleLoChipChange(input) {
        const label = input.closest('label');
        if (!label) return;
        if (input.checked) {
            label.classList.remove('border-[#CBD5CA]', 'bg-white', 'text-[#10211D]');
            label.classList.add('bg-[#0B2B25]', 'text-white', 'border-[#0B2B25]', 'shadow-xs');
        } else {
            label.classList.remove('bg-[#0B2B25]', 'text-white', 'border-[#0B2B25]', 'shadow-xs');
            label.classList.add('border-[#CBD5CA]', 'bg-white', 'text-[#10211D]');
        }
    }

    function formatBytes(bytes, decimals = 1) {
        if (!+bytes) return '0 B';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
    }

    async function compressJournalImage(file) {
        let drawable = null;
        let originalWidth = 0;
        let originalHeight = 0;
        let tempUrl = null;

        // 1. Coba decode via createImageBitmap (Akselerasi hardware, mendukung HEIC di Safari iOS 15+)
        if (typeof window.createImageBitmap === 'function') {
            try {
                const bitmap = await window.createImageBitmap(file);
                drawable = bitmap;
                originalWidth = bitmap.width;
                originalHeight = bitmap.height;
            } catch (bitmapErr) {
                console.warn('createImageBitmap failed, trying Blob URL image:', bitmapErr);
            }
        }

        // 2. Coba via Image element dengan URL.createObjectURL (Safari mendukung render HEIC via Blob URL)
        if (!drawable) {
            try {
                tempUrl = URL.createObjectURL(file);
                drawable = await new Promise((resolve, reject) => {
                    const img = new Image();
                    img.onload = () => resolve(img);
                    img.onerror = (e) => reject(new Error('Gagal memuat gambar via Blob URL'));
                    img.src = tempUrl;
                });
                originalWidth = drawable.width;
                originalHeight = drawable.height;
            } catch (blobErr) {
                if (tempUrl) {
                    URL.revokeObjectURL(tempUrl);
                    tempUrl = null;
                }
                console.warn('Blob URL image load failed, trying FileReader:', blobErr);
            }
        }

        // 3. Fallback via FileReader data URL
        if (!drawable) {
            drawable = await new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = new Image();
                    img.onload = () => resolve(img);
                    img.onerror = () => reject(new Error('Format berkas tidak dapat didekode oleh peramban'));
                    img.src = e.target.result;
                };
                reader.onerror = () => reject(new Error('Gagal membaca berkas'));
                reader.readAsDataURL(file);
            });
            originalWidth = drawable.width;
            originalHeight = drawable.height;
        }

        const MAX_SIDE = 1200; // 1200px max side is crisp and clear for documentation
        const scale = Math.min(1, MAX_SIDE / Math.max(originalWidth, originalHeight));

        const canvas = document.createElement('canvas');
        canvas.width = Math.round(originalWidth * scale);
        canvas.height = Math.round(originalHeight * scale);

        const ctx = canvas.getContext('2d');
        ctx.imageSmoothingEnabled = true;
        ctx.imageSmoothingQuality = 'high';
        ctx.drawImage(drawable, 0, 0, canvas.width, canvas.height);

        // Bersihkan resource memori
        if (tempUrl) {
            URL.revokeObjectURL(tempUrl);
        }
        if (drawable && typeof drawable.close === 'function') {
            drawable.close(); // Bersihkan ImageBitmap
        }

        // Iterative compression targeting < 350KB
        const TARGET_MAX_BYTES = 350 * 1024;
        let quality = 0.75;
        let dataUrl = canvas.toDataURL('image/jpeg', quality);

        while (dataUrl.length * 0.75 > TARGET_MAX_BYTES && quality > 0.4) {
            quality -= 0.1;
            dataUrl = canvas.toDataURL('image/jpeg', quality);
        }

        return {
            dataUrl: dataUrl,
            size: Math.round(dataUrl.length * 0.75),
            originalSize: file.size,
            width: canvas.width,
            height: canvas.height
        };
    }

    // Photo preview and client-side compression handler
    async function handleJournalPhotoSelected(input) {
        if (!input.files || !input.files[0]) return;
        const file = input.files[0];

        const previewBox = document.getElementById('photo-preview-box');
        const helperText = document.getElementById('photo-helper-text');
        const hiddenBase64 = document.getElementById('journal-compressed-photo');
        const submitBtn = document.querySelector('#journal-main-form button[type="submit"]');

        // Loading state
        if (submitBtn) submitBtn.disabled = true;
        if (previewBox) {
            previewBox.innerHTML = `
                <div class="flex flex-col items-center justify-center p-1 text-center">
                    <svg class="animate-spin w-5 h-5 text-[#C6F26B] mb-1" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span class="text-[9px] font-bold text-[#C6F26B] leading-none">Memproses...</span>
                </div>
            `;
        }
        if (helperText) {
            helperText.innerHTML = `<span class="text-xs text-amber-600 font-semibold animate-pulse">Sedang mengoptimalkan resolusi foto...</span>`;
        }

        try {
            const result = await compressJournalImage(file);

            // 1. Tampilkan gambar terkompresi & tombol hapus
            if (previewBox) {
                previewBox.innerHTML = `
                    <img src="${result.dataUrl}" class="w-full h-full object-cover">
                    <button type="button" onclick="clearSelectedJournalPhoto()" 
                            class="absolute top-1 right-1 w-5 h-5 rounded-full bg-slate-900/80 text-white flex items-center justify-center hover:bg-rose-600 transition-colors cursor-pointer shadow-xs" 
                            title="Hapus foto">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                `;
            }

            // 2. Set hidden base64 input (compressed_photos[])
            if (hiddenBase64) {
                hiddenBase64.value = result.dataUrl;
            }

            // 3. Kosongkan input berkas mentah agar payload jaringan ringan & terhindar dari error MIME berkas mentah
            input.value = '';

            // 4. Update helper text with size savings badge
            if (helperText) {
                helperText.innerHTML = `
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs font-bold text-[#0B5E4E] flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-[#0B5E4E] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            Foto siap diunggah
                        </span>
                        <span class="text-[11px] text-[#5A6B65]">
                            Ukuran teroptimasi: <strong class="text-slate-800">${formatBytes(result.size)}</strong> 
                            <span class="text-slate-400 line-through">(${formatBytes(result.originalSize)})</span>
                        </span>
                    </div>
                `;
            }
        } catch (err) {
            console.warn('Image compression fallback to raw file upload:', err);

            // Biarkan berkas asli tetap di input.files (name="photos[]")
            if (hiddenBase64) hiddenBase64.value = '';

            // Tampilkan icon preview dokumen/foto yang bersih (bukan tag <img> rusak)
            const ext = (file.name.split('.').pop() || 'IMG').toUpperCase().slice(0, 4);
            if (previewBox) {
                previewBox.innerHTML = `
                    <div class="flex flex-col items-center justify-center p-1 text-center w-full h-full bg-[#0B2B25] text-white select-none">
                        <svg class="w-6 h-6 text-[#C6F26B] mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21 15 16 10 5 21"/>
                        </svg>
                        <span class="text-[9px] font-extrabold text-[#C6F26B] leading-none tracking-wider">${ext}</span>
                    </div>
                    <button type="button" onclick="clearSelectedJournalPhoto()" 
                            class="absolute top-1 right-1 w-5 h-5 rounded-full bg-slate-900/80 text-white flex items-center justify-center hover:bg-rose-600 transition-colors cursor-pointer shadow-xs" 
                            title="Hapus foto">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                `;
            }

            if (helperText) {
                helperText.innerHTML = `
                    <div class="flex flex-col gap-0.5">
                        <span class="text-xs font-bold text-[#0B5E4E] flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-[#0B5E4E] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            Berkas siap diunggah
                        </span>
                        <span class="text-[11px] text-[#5A6B65] truncate max-w-[200px]" title="${file.name}">
                            ${file.name} (${formatBytes(file.size)})
                        </span>
                    </div>
                `;
            }
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    }
            if (submitBtn) submitBtn.disabled = false;
        }
    }

    function clearSelectedJournalPhoto() {
        const input = document.getElementById('journal-photos-input');
        const hiddenBase64 = document.getElementById('journal-compressed-photo');
        const previewBox = document.getElementById('photo-preview-box');
        const helperText = document.getElementById('photo-helper-text');

        if (input) input.value = '';
        if (hiddenBase64) hiddenBase64.value = '';

        if (previewBox) {
            previewBox.innerHTML = `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#C6F26B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <polyline points="21 15 16 10 5 21"/>
                </svg>
            `;
        }

        if (helperText) {
            helperText.innerHTML = `Foto dokumentasi kegiatan. Ambil dari kamera atau unggah bukti pekerjaan.`;
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

            // Capaian Pembelajaran (CP) Tagged
            const loSection = document.getElementById('jd-lo-section');
            const loList = document.getElementById('jd-lo-list');
            if (loList) {
                loList.innerHTML = '';
                if (data.learning_objectives && data.learning_objectives.length > 0) {
                    data.learning_objectives.forEach(lo => {
                        const span = document.createElement('span');
                        span.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200/80';
                        span.innerHTML = `<strong class="font-mono text-[11px]">${lo.code}</strong> <span>${lo.title}</span>`;
                        loList.appendChild(span);
                    });
                    loSection.classList.remove('hidden');
                } else {
                    loSection.classList.add('hidden');
                }
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
