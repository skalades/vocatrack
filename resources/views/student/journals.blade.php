@extends('layouts.student-mobile')

@section('title', 'Jurnal PKL Harian — VokaTrack Siswa')

@section('content')
<div class="space-y-4">

    <!-- Segmented Tab Switcher (iOS/Modern Mobile App Style) -->
    <div class="grid grid-cols-2 p-1 bg-slate-200/70 rounded-2xl text-xs font-bold transition-all">
        <button type="button" onclick="switchJournalTab('history')" id="tab-btn-history"
                class="py-2 px-3 rounded-xl transition-all flex items-center justify-center gap-1.5 cursor-pointer bg-white text-slate-900 shadow-xs">
            <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Riwayat Jurnal</span>
            <span id="tab-count-history" class="text-[10px] px-1.5 py-0.2 rounded-full font-bold ml-0.5 bg-indigo-50 text-indigo-700">
                {{ $statusCounts['all'] ?? 0 }}
            </span>
        </button>
        <button type="button" onclick="switchJournalTab('form')" id="tab-btn-form"
                class="py-2 px-3 rounded-xl transition-all flex items-center justify-center gap-1.5 cursor-pointer text-slate-500 hover:text-slate-800 font-semibold">
            <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            <span>{{ $todayJournal ? 'Jurnal Hari Ini' : 'Tulis Jurnal' }}</span>
            @if(!$todayJournal)
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
            @endif
        </button>
    </div>

    <!-- ========================================== -->
    <!-- TAB 1: RIWAYAT JURNAL (Clean Feed View)    -->
    <!-- ========================================== -->
    <div id="tab-content-history" class="space-y-3.5">

        <!-- Friendly Reminder if Today is not submitted -->
        @if(!$todayJournal)
            <div class="p-3.5 rounded-2xl bg-gradient-to-r from-indigo-50 to-indigo-100/60 border border-indigo-100 flex items-center justify-between gap-3 shadow-2xs">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <div class="min-w-0">
                        <span class="text-xs font-bold text-slate-900 block truncate">Jurnal Hari Ini Belum Diisi</span>
                        <span class="text-[11px] text-slate-500 font-medium block truncate">Catat aktivitas PKL Anda hari ini</span>
                    </div>
                </div>
                <button type="button" onclick="switchJournalTab('form')" class="shrink-0 px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white text-xs font-bold shadow-xs transition-all cursor-pointer">
                    Tulis
                </button>
            </div>
        @endif

        <!-- Filter & Search Controls (Minimalist & Clean) -->
        <div class="space-y-2 pt-0.5">
            <!-- Search Keyword Form -->
            <form action="{{ route('student.journals') }}" method="GET" class="relative">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <input type="hidden" name="tab" value="history">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari isi catatan jurnal..." 
                           class="w-full text-xs py-2.5 pl-9 pr-8 rounded-2xl bg-white border border-slate-200/80 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium placeholder-slate-400 shadow-2xs transition-all">
                    @if(request('search'))
                        <a href="{{ route('student.journals', array_merge(request()->except(['search', 'page']), ['tab' => 'history'])) }}" 
                           class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600" title="Hapus pencarian">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                </div>
            </form>

            <!-- Horizontal Clean Status Chips -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs no-scrollbar">
                @php
                    $currentStatus = request('status', 'all');
                    $filters = [
                        ['key' => 'all', 'label' => 'Semua', 'count' => $statusCounts['all'] ?? 0],
                        ['key' => 'approved', 'label' => 'Disetujui', 'count' => $statusCounts['approved'] ?? 0],
                        ['key' => 'review', 'label' => 'Review', 'count' => $statusCounts['review'] ?? 0],
                        ['key' => 'draft', 'label' => 'Draft', 'count' => $statusCounts['draft'] ?? 0],
                        ['key' => 'revision', 'label' => 'Revisi', 'count' => $statusCounts['revision'] ?? 0],
                    ];
                @endphp
                @foreach($filters as $f)
                    @php $isActive = $currentStatus === $f['key']; @endphp
                    <a href="{{ route('student.journals', array_merge(request()->except(['page']), ['status' => $f['key'], 'tab' => 'history'])) }}"
                       class="shrink-0 px-3 py-1.5 rounded-xl text-[11px] font-bold transition-all {{ $isActive ? 'bg-indigo-600 text-white shadow-xs shadow-indigo-600/30' : 'bg-white hover:bg-slate-100 text-slate-600 border border-slate-200/80 shadow-2xs' }}">
                        {{ $f['label'] }} ({{ $f['count'] }})
                    </a>
                @endforeach
            </div>

            <!-- Active Filter Reset Pill -->
            @if(request()->filled('search') || (request()->filled('status') && request('status') !== 'all'))
                <div class="flex items-center justify-between text-[11px] bg-slate-100/90 px-3 py-1.5 rounded-xl border border-slate-200/70">
                    <span class="text-slate-700 font-medium truncate">
                        Filter aktif: 
                        @if(request('search')) <strong class="text-slate-900">"{{ request('search') }}"</strong> @endif
                        @if(request('status') && request('status') !== 'all') <span class="capitalize font-bold text-indigo-700">({{ request('status') }})</span> @endif
                    </span>
                    <a href="{{ route('student.journals', ['tab' => 'history']) }}" class="text-indigo-600 hover:text-indigo-800 font-bold shrink-0 ml-2">
                        ✕ Reset
                    </a>
                </div>
            @endif
        </div>

        <!-- Journal List Feed -->
        @if($journals->isEmpty())
            <x-empty-state 
                title="Tidak Ada Jurnal Ditemukan" 
                description="Belum ada logbook jurnal yang cocok dengan filter atau kata kunci pencarian Anda."
                class="py-8 bg-white rounded-3xl border border-slate-200/80 shadow-xs"
            />
        @else
            <div class="space-y-3">
                @foreach($journals as $j)
                    <div class="bg-white rounded-3xl p-4.5 border border-slate-200/80 shadow-xs space-y-3 hover:border-slate-300 transition-colors">
                        
                        <!-- Card Header: Localized Indonesian Date & Badge -->
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-600 shrink-0"></span>
                                <span class="text-xs font-bold text-slate-900 truncate">
                                    {{ \Carbon\Carbon::parse($j->date)->locale('id')->isoFormat('dddd, D MMMM Y') }}
                                </span>
                            </div>
                            <x-badge :variant="$j->status" size="xs">
                                {{ strtoupper($j->status) }}
                            </x-badge>
                        </div>

                        <!-- Activity Description with Compact Preview / Expand Toggle -->
                        @php
                            $isLong = strlen($j->activity_description) > 160;
                        @endphp
                        <div>
                            <div id="journal-desc-preview-{{ $j->id }}" class="text-xs text-slate-700 leading-relaxed font-medium {{ $isLong ? 'line-clamp-3' : '' }}">
                                {{ $j->activity_description }}
                            </div>
                            @if($isLong)
                                <button type="button" onclick="toggleJournalDesc({{ $j->id }})" id="journal-desc-btn-{{ $j->id }}" 
                                        class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 mt-1 inline-flex items-center gap-1 cursor-pointer">
                                    <span>Selengkapnya</span>
                                    <svg class="w-3.5 h-3.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                            @endif
                        </div>

                        <!-- Photos Preview Row (Click to Full Lightbox) -->
                        @if($j->photos->isNotEmpty())
                            <div class="flex items-center gap-2 overflow-x-auto pt-0.5 pb-1 no-scrollbar">
                                @foreach($j->photos as $ph)
                                    <button type="button" onclick="openImageLightbox('{{ asset('storage/' . $ph->photo_path) }}')" title="Klik untuk memperbesar foto dokumentasi"
                                            class="group relative w-16 h-16 rounded-2xl overflow-hidden border border-slate-200/80 shrink-0 shadow-2xs hover:scale-102 transition-transform cursor-pointer bg-slate-100">
                                        <img src="{{ asset('storage/' . $ph->photo_path) }}" class="w-full h-full object-cover" alt="Foto Dokumentasi Kegiatan">
                                        <div class="absolute inset-0 bg-slate-950/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        <!-- Collapsible Evaluasi & Catatan Pembimbing (Prevents clutter!) -->
                        @if($j->approvals->isNotEmpty())
                            <div class="pt-2 border-t border-slate-100">
                                <button type="button" onclick="toggleFeedback({{ $j->id }})" 
                                        class="w-full flex items-center justify-between p-2.5 rounded-xl bg-slate-50 hover:bg-slate-100/80 border border-slate-200/60 transition-colors text-left cursor-pointer">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <div class="w-5 h-5 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                        </div>
                                        <span class="text-[11px] font-bold text-slate-700 truncate">
                                            Catatan Pembimbing ({{ $j->approvals->count() }})
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1 text-[11px] font-semibold text-slate-400">
                                        <span id="feedback-toggle-text-{{ $j->id }}">Buka</span>
                                        <svg id="feedback-chevron-{{ $j->id }}" class="w-3.5 h-3.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </button>

                                <div id="feedback-container-{{ $j->id }}" class="hidden space-y-2 mt-2 pt-1">
                                    @foreach($j->approvals as $appr)
                                        <div class="p-3 rounded-2xl bg-slate-50/90 border border-slate-200/70 space-y-1.5">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="min-w-0">
                                                    <span class="text-xs font-bold text-slate-800 block truncate">{{ $appr->approver->name }}</span>
                                                    <span class="text-[10px] text-slate-400 capitalize block">{{ str_replace('_', ' ', $appr->role_level) }}</span>
                                                </div>
                                                <x-badge :variant="$appr->status === 'approved' ? 'success' : ($appr->status === 'rejected' ? 'danger' : 'warning')" size="xs">
                                                    {{ strtoupper($appr->status) }}
                                                </x-badge>
                                            </div>
                                            @if($appr->notes)
                                                <p class="text-[11px] text-slate-600 bg-white p-2.5 rounded-xl border border-slate-200/60 italic font-medium leading-relaxed">
                                                    "{{ $appr->notes }}"
                                                </p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Re-submit Action for Draft / Revision -->
                        @if(in_array($j->status, ['draft', 'revision']))
                            <form action="{{ route('student.journal.submit', $j->id) }}" method="POST" class="pt-1 border-t border-slate-100">
                                @csrf
                                <button type="submit" class="w-full py-2.5 px-3 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs border border-indigo-200 transition-colors cursor-pointer flex items-center justify-center gap-1.5 active:scale-[0.99]">
                                    <svg class="w-3.5 h-3.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                    <span>Ajukan Review Ulang</span>
                                </button>
                            </form>
                        @endif

                    </div>
                @endforeach
            </div>

            <!-- Responsive Modular Pagination -->
            <x-pagination :paginator="$journals" />
        @endif

    </div>

    <!-- ========================================== -->
    <!-- TAB 2: TULIS JURNAL / JURNAL HARI INI     -->
    <!-- ========================================== -->
    <div id="tab-content-form" class="space-y-4 hidden">

        @if($todayJournal && in_array($todayJournal->status, ['submitted', 'review_guru', 'approved']))
            <!-- Locked / Under Review View -->
            <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs space-y-4">
                
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-xs font-bold text-slate-900">Jurnal Hari Ini</h3>
                        <span class="text-[11px] text-slate-400 font-medium">{{ \Carbon\Carbon::parse($todayJournal->date)->locale('id')->isoFormat('dddd, D MMMM Y') }}</span>
                    </div>
                    <x-badge :variant="$todayJournal->status" size="xs">
                        {{ strtoupper($todayJournal->status) }}
                    </x-badge>
                </div>

                @if($todayJournal->status === 'approved')
                    <div class="p-3.5 rounded-2xl bg-emerald-50/80 border border-emerald-100 text-emerald-950 flex items-center gap-2.5">
                        <span class="w-7 h-7 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black shrink-0">✓</span>
                        <div>
                            <span class="text-xs font-bold block">Jurnal Disetujui</span>
                            <span class="text-[11px] text-emerald-800 font-medium block">Laporan telah diverifikasi oleh mentor DU/DI dan guru pembimbing.</span>
                        </div>
                    </div>
                @else
                    <div class="p-3.5 rounded-2xl bg-indigo-50/70 border border-indigo-100 text-indigo-950 flex items-center gap-2.5">
                        <span class="w-7 h-7 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-black shrink-0">⏳</span>
                        <div>
                            <span class="text-xs font-bold block">Dalam Tahap Peninjauan</span>
                            <span class="text-[11px] text-indigo-800 font-medium block">Jurnal hari ini telah diajukan dan sedang ditinjau pembimbing.</span>
                        </div>
                    </div>
                @endif

                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70 space-y-1.5">
                    <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Uraian Pekerjaan</span>
                    <p class="text-xs text-slate-800 font-medium leading-relaxed">
                        {{ $todayJournal->activity_description }}
                    </p>
                </div>

                @if($todayJournal->photos->isNotEmpty())
                    <div class="space-y-1.5">
                        <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Foto Kegiatan</span>
                        <div class="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar">
                            @foreach($todayJournal->photos as $ph)
                                <button type="button" onclick="openImageLightbox('{{ asset('storage/' . $ph->photo_path) }}')"
                                        class="w-16 h-16 rounded-2xl overflow-hidden border border-slate-200 shrink-0 shadow-2xs hover:scale-102 transition-transform cursor-pointer bg-slate-100">
                                    <img src="{{ asset('storage/' . $ph->photo_path) }}" class="w-full h-full object-cover" alt="Foto Hari Ini">
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($todayJournal->approvals->isNotEmpty())
                    <div class="space-y-2 pt-2 border-t border-slate-100">
                        <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Evaluasi Pembimbing</span>
                        @foreach($todayJournal->approvals as $appr)
                            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/70 space-y-1.5 text-xs">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-bold text-slate-800">{{ $appr->approver->name }}</span>
                                    <x-badge :variant="$appr->status === 'approved' ? 'success' : 'warning'" size="xs">
                                        {{ strtoupper($appr->status) }}
                                    </x-badge>
                                </div>
                                @if($appr->notes)
                                    <p class="text-[11px] text-slate-600 bg-white p-2.5 rounded-xl border border-slate-200/60 italic font-medium leading-relaxed">
                                        "{{ $appr->notes }}"
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                <button type="button" onclick="switchJournalTab('history')" class="w-full py-2.5 px-4 rounded-2xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold text-xs flex items-center justify-center gap-1.5 transition-colors cursor-pointer">
                    <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Buka Riwayat Semua Jurnal</span>
                </button>

            </div>
        @else
            <!-- Journal Input Form Card -->
            <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-xs font-bold text-slate-900">
                            {{ $todayJournal ? 'Sunting Jurnal Hari Ini' : 'Tulis Jurnal Baru' }}
                        </h3>
                        <span class="text-[11px] text-slate-400 font-medium">{{ \Carbon\Carbon::today()->locale('id')->isoFormat('D MMMM Y') }}</span>
                    </div>
                    @if($todayJournal)
                        <x-badge :variant="$todayJournal->status" size="xs">
                            {{ strtoupper($todayJournal->status) }}
                        </x-badge>
                    @else
                        <x-badge variant="warning" size="xs">
                            Hari Ini Belum Diisi
                        </x-badge>
                    @endif
                </div>

                <form action="{{ route('student.journal.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    
                    <!-- Date Input -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Tanggal Pelaksanaan Kegiatan</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <input type="date" name="date" value="{{ old('date', $todayJournal ? $todayJournal->date->toDateString() : \Carbon\Carbon::today()->toDateString()) }}" required
                                   class="w-full text-xs py-2.5 pl-10 pr-3 rounded-2xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-semibold text-slate-800 transition-all">
                        </div>
                    </div>

                    <!-- Description Input -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Uraian Pekerjaan & Kompetensi</label>
                        <textarea name="activity_description" id="journal-desc" rows="4" required minlength="50" oninput="updateCharCount()"
                                  placeholder="Uraikan apa saja yang dikerjakan, alat/teknologi yang digunakan, serta solusi atas kendala hari ini..."
                                  class="w-full text-xs p-3.5 rounded-2xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium leading-relaxed text-slate-900 transition-all">{{ old('activity_description', $todayJournal->activity_description ?? '') }}</textarea>
                        
                        <div class="flex items-center justify-between mt-1 text-[11px]">
                            <span class="text-slate-400 font-medium">Minimal 50 karakter</span>
                            <span id="char-counter" class="font-bold text-slate-400 font-mono transition-colors">0 / 50</span>
                        </div>
                    </div>

                    <!-- Photo Upload (Compact Modern Trigger) -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-bold text-slate-700">Foto Bukti Kegiatan</label>
                            <span id="photo-count-badge" class="text-[11px] font-medium text-slate-400">Maks. 3 Foto (Opsional)</span>
                        </div>

                        <!-- Sleek Upload Trigger Button -->
                        <label for="journal-photos-input" class="w-full py-3 px-4 rounded-2xl border border-dashed border-indigo-200 bg-indigo-50/30 hover:bg-indigo-50/60 active:scale-[0.99] text-indigo-700 font-bold text-xs flex items-center justify-center gap-2 cursor-pointer transition-all">
                            <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>Pilih / Ambil Foto</span>
                        </label>
                        <input type="file" name="photos[]" id="journal-photos-input" multiple accept="image/*" class="hidden" onchange="previewJournalPhotos(event)">

                        <!-- Photos Preview Grid -->
                        <div id="photos-preview-container" class="hidden grid grid-cols-3 gap-2 mt-2.5"></div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="grid grid-cols-2 gap-2 pt-2">
                        <button type="submit" name="action_type" value="draft" 
                                class="py-3 px-4 rounded-2xl border border-slate-200 bg-white hover:bg-slate-50 active:bg-slate-100 text-slate-700 font-bold text-xs flex items-center justify-center gap-1.5 shadow-2xs cursor-pointer active:scale-[0.98] transition-all">
                            <svg class="w-4 h-4 text-slate-400 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                            <span>Simpan Draft</span>
                        </button>
                        <button type="submit" name="action_type" value="submit" 
                                class="py-3 px-4 rounded-2xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold text-xs shadow-md shadow-indigo-600/25 flex items-center justify-center gap-1.5 cursor-pointer active:scale-[0.98] transition-all">
                            <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            <span>Ajukan Review</span>
                        </button>
                    </div>

                </form>
            </div>
        @endif

    </div>

</div>

@push('scripts')
<script>
    function switchJournalTab(tab) {
        const historySection = document.getElementById('tab-content-history');
        const formSection = document.getElementById('tab-content-form');
        const btnHistory = document.getElementById('tab-btn-history');
        const btnForm = document.getElementById('tab-btn-form');
        const countBadge = document.getElementById('tab-count-history');

        if (!historySection || !formSection || !btnHistory || !btnForm) return;

        if (tab === 'form') {
            historySection.classList.add('hidden');
            formSection.classList.remove('hidden');
            formSection.classList.remove('animate-fade-in');
            void formSection.offsetWidth;
            formSection.classList.add('animate-fade-in');

            btnForm.className = 'py-2 px-3 rounded-xl bg-white text-slate-900 shadow-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer';
            btnHistory.className = 'py-2 px-3 rounded-xl text-slate-500 hover:text-slate-800 font-semibold transition-all flex items-center justify-center gap-1.5 cursor-pointer';
            
            if (countBadge) countBadge.className = 'text-[10px] px-1.5 py-0.2 rounded-full font-bold ml-0.5 bg-slate-100 text-slate-500';
        } else {
            formSection.classList.add('hidden');
            historySection.classList.remove('hidden');
            historySection.classList.remove('animate-fade-in');
            void historySection.offsetWidth;
            historySection.classList.add('animate-fade-in');

            btnHistory.className = 'py-2 px-3 rounded-xl bg-white text-slate-900 shadow-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer';
            btnForm.className = 'py-2 px-3 rounded-xl text-slate-500 hover:text-slate-800 font-semibold transition-all flex items-center justify-center gap-1.5 cursor-pointer';
            
            if (countBadge) countBadge.className = 'text-[10px] px-1.5 py-0.2 rounded-full font-bold ml-0.5 bg-indigo-50 text-indigo-700';
        }

        // Update URL parameter without reload
        const url = new URL(window.location);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    }

    function toggleJournalDesc(id) {
        const preview = document.getElementById('journal-desc-preview-' + id);
        const btn = document.getElementById('journal-desc-btn-' + id);
        if (!preview || !btn) return;

        if (preview.classList.contains('line-clamp-3')) {
            preview.classList.remove('line-clamp-3');
            btn.innerHTML = '<span>Sembunyikan</span><svg class="w-3.5 h-3.5 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
        } else {
            preview.classList.add('line-clamp-3');
            btn.innerHTML = '<span>Selengkapnya</span><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
        }
    }

    function toggleFeedback(id) {
        const container = document.getElementById('feedback-container-' + id);
        const chevron = document.getElementById('feedback-chevron-' + id);
        const text = document.getElementById('feedback-toggle-text-' + id);
        if (!container) return;

        if (container.classList.contains('hidden')) {
            container.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
            if (text) text.innerText = 'Tutup';
        } else {
            container.classList.add('hidden');
            if (chevron) chevron.classList.remove('rotate-180');
            if (text) text.innerText = 'Buka';
        }
    }

    function updateCharCount() {
        const textarea = document.getElementById('journal-desc');
        if (!textarea) return;
        const counter = document.getElementById('char-counter');
        if (!counter) return;

        const len = textarea.value.length;
        if (len >= 50) {
            counter.className = 'font-bold text-emerald-600 font-mono transition-colors';
            counter.innerText = '✓ ' + len + ' karakter (Lengkap)';
        } else {
            counter.className = 'font-bold text-slate-400 font-mono transition-colors';
            counter.innerText = len + ' / 50 min.';
        }
    }

    function clearSelectedPhotos() {
        const input = document.getElementById('journal-photos-input');
        const container = document.getElementById('photos-preview-container');
        const badge = document.getElementById('photo-count-badge');
        if (input) input.value = '';
        if (container) {
            container.innerHTML = '';
            container.classList.add('hidden');
        }
        if (badge) {
            badge.innerText = 'Maks. 3 Foto (Opsional)';
            badge.className = 'text-[11px] font-medium text-slate-400';
        }
    }

    function previewJournalPhotos(event) {
        const files = Array.from(event.target.files);
        const container = document.getElementById('photos-preview-container');
        const badge = document.getElementById('photo-count-badge');
        
        if (files.length > 3) {
            alert('Maksimal hanya dapat memilih 3 foto dokumentasi kegiatan.');
            clearSelectedPhotos();
            return;
        }

        if (files.length === 0) {
            clearSelectedPhotos();
            return;
        }

        container.innerHTML = '';
        container.classList.remove('hidden');
        badge.innerText = files.length + ' Foto Terpilih';
        badge.className = 'text-[11px] font-bold text-indigo-600';

        files.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'relative aspect-square rounded-2xl overflow-hidden border border-slate-200 shadow-2xs group bg-slate-100 animate-scale-in';
                div.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-full object-cover">
                    <span class="absolute bottom-1 right-1 text-[9px] font-bold px-1.5 py-0.5 rounded-md bg-slate-900/70 text-white backdrop-blur-xs">
                        #${index + 1}
                    </span>
                `;
                container.appendChild(div);
            };
            reader.readAsDataURL(file);
        });

        // Add clear photos button if files exist
        const clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.onclick = clearSelectedPhotos;
        clearBtn.className = 'col-span-3 text-[11px] font-semibold text-rose-600 hover:text-rose-700 text-center py-1 mt-0.5 cursor-pointer';
        clearBtn.innerText = '✕ Hapus Pilihan Foto';
        container.appendChild(clearBtn);
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateCharCount();

        // Check URL parameters for tab
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');

        if (tabParam === 'form' || tabParam === 'write') {
            switchJournalTab('form');
        } else if (tabParam === 'history') {
            switchJournalTab('history');
        } else if (urlParams.has('search') || urlParams.has('status')) {
            switchJournalTab('history');
        } else {
            // Default to 'form' if today has not been written yet, else 'history'
            @if(!$todayJournal)
                switchJournalTab('form');
            @else
                switchJournalTab('history');
            @endif
        }
    });
</script>
@endpush
@endsection
