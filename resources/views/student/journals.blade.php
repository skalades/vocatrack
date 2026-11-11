@extends('layouts.student-mobile')

@section('title', 'Jurnal PKL Harian — VokaTrack Siswa')

@section('content')
<div class="space-y-4">

    <!-- Header Title -->
    <div>
        <h2 class="text-lg font-black text-slate-900 tracking-tight">Jurnal Harian PKL</h2>
        <p class="text-xs text-slate-500 font-medium">Dokumentasikan rincian pekerjaan, kendala teknis, dan foto bukti kegiatan industri.</p>
    </div>

    <!-- Journal Input Card -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-xs font-bold text-slate-900">
                    {{ $todayJournal ? 'Sunting Jurnal Hari Ini' : 'Tulis Jurnal Baru' }}
                </h3>
                <span class="text-[10px] text-slate-400 font-medium">{{ \Carbon\Carbon::today()->isoFormat('D MMMM Y') }}</span>
            </div>
            @if($todayJournal)
                <x-badge :variant="$todayJournal->status" size="xs">
                    {{ strtoupper($todayJournal->status) }}
                </x-badge>
            @endif
        </div>

        @if($todayJournal && in_array($todayJournal->status, ['submitted', 'review_guru', 'approved']))
            <div class="p-4 rounded-2xl bg-indigo-50/70 border border-indigo-100 text-indigo-950 space-y-2.5">
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span class="text-xs font-bold">Jurnal Terkunci (Tahap Verifikasi)</span>
                </div>
                <p class="text-[11px] text-indigo-800 font-medium leading-relaxed">
                    Jurnal hari ini telah berstatus <strong>{{ strtoupper($todayJournal->status) }}</strong> dan sedang dalam peninjauan mentor DU/DI dan guru pembimbing.
                </p>
                <div class="bg-white/90 p-3 rounded-xl border border-indigo-100 text-xs text-slate-800 leading-relaxed font-medium">
                    {{ $todayJournal->activity_description }}
                </div>
            </div>
        @else
            <form action="{{ route('student.journal.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Tanggal Pelaksanaan Kegiatan</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <input type="date" name="date" value="{{ old('date', $todayJournal ? $todayJournal->date->toDateString() : \Carbon\Carbon::today()->toDateString()) }}" required
                               class="w-full text-xs py-3 pl-10 pr-3 rounded-2xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-semibold text-slate-800 transition-all">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-xs font-bold text-slate-700">Uraian Pekerjaan & Kompetensi</label>
                        <span id="char-counter" class="text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200 transition-all font-mono">
                            0 / min 50 karakter
                        </span>
                    </div>
                    <textarea name="activity_description" id="journal-desc" rows="4" required minlength="50" oninput="updateCharCount()"
                              placeholder="Uraikan secara spesifik apa saja yang dikerjakan, alat/teknologi yang digunakan, serta solusi atas kendala yang dihadapi (minimal 50 karakter)..."
                              class="w-full text-xs p-3.5 rounded-2xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium leading-relaxed text-slate-900 transition-all">{{ old('activity_description', $todayJournal->activity_description ?? '') }}</textarea>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-xs font-bold text-slate-700">Foto Dokumentasi Kerja</label>
                        <span id="photo-count-badge" class="text-[10px] font-semibold text-slate-400">Maks. 3 Foto (Opsional)</span>
                    </div>

                    <!-- Custom Dropzone -->
                    <label for="journal-photos-input" class="group flex flex-col items-center justify-center p-4 rounded-2xl border-2 border-dashed border-indigo-200 bg-indigo-50/20 hover:bg-indigo-50/40 hover:border-indigo-400 transition-all cursor-pointer text-center">
                        <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 group-hover:scale-105 flex items-center justify-center mb-1.5 transition-transform shadow-2xs">
                            <svg class="w-5 h-5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <span class="text-xs font-bold text-indigo-950 group-hover:text-indigo-600">Pilih / Ambil Foto Kegiatan</span>
                        <span class="text-[10px] text-slate-400 mt-0.5 font-medium">Format JPG/PNG, ukuran foto kegiatan industri</span>
                    </label>
                    <input type="file" name="photos[]" id="journal-photos-input" multiple accept="image/*" class="hidden" onchange="previewJournalPhotos(event)">

                    <!-- Photos Preview Grid -->
                    <div id="photos-preview-container" class="hidden grid grid-cols-3 gap-2 mt-3"></div>
                </div>

                <div class="grid grid-cols-2 gap-2.5 pt-1">
                    <button type="submit" name="action_type" value="draft" 
                            class="py-3 px-4 rounded-2xl border border-slate-200 bg-white hover:bg-slate-50 active:bg-slate-100 text-slate-700 font-bold text-xs flex items-center justify-center gap-1.5 transition-all shadow-2xs cursor-pointer active:scale-[0.98]">
                        <svg class="w-4 h-4 text-slate-500 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                        <span>Simpan Draft</span>
                    </button>
                    <button type="submit" name="action_type" value="submit" 
                            class="py-3 px-4 rounded-2xl bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/25 flex items-center justify-center gap-1.5 transition-all active:scale-[0.98] cursor-pointer">
                        <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        <span>Ajukan Review</span>
                    </button>
                </div>
            </form>
        @endif
    </div>

    <!-- Previous Journals List -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xs font-extrabold text-slate-900 tracking-tight">Daftar Jurnal Terkirim</h3>
                <p class="text-[11px] text-slate-500 font-medium">Rekapitulasi logbook kegiatan PKL Anda</p>
            </div>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-100">
                Total: {{ $statusCounts['all'] ?? $journals->total() }}
            </span>
        </div>

        <!-- Filter & Search Controls -->
        <div class="space-y-2.5 pt-1">
            <!-- Search Keyword Form -->
            <form action="{{ route('student.journals') }}" method="GET" class="flex gap-2">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari isi catatan jurnal..." 
                           class="w-full text-xs py-2.5 pl-8 pr-3 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium placeholder-slate-400 transition-all">
                </div>
                <button type="submit" class="px-3.5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-bold text-xs flex items-center gap-1 shadow-xs shadow-indigo-600/20 transition-all cursor-pointer">
                    <span>Cari</span>
                </button>
            </form>

            <!-- Horizontal Scrollable Status Pills -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
                @php
                    $currentStatus = request('status', 'all');
                @endphp
                
                <a href="{{ route('student.journals', array_merge(request()->except(['page']), ['status' => 'all'])) }}"
                   class="shrink-0 px-3 py-1.5 rounded-xl text-[11px] font-bold transition-all {{ $currentStatus === 'all' ? 'bg-indigo-600 text-white shadow-xs shadow-indigo-600/30' : 'bg-slate-100 hover:bg-slate-200 text-slate-600 border border-slate-200/80' }}">
                    Semua ({{ $statusCounts['all'] ?? 0 }})
                </a>

                <a href="{{ route('student.journals', array_merge(request()->except(['page']), ['status' => 'approved'])) }}"
                   class="shrink-0 px-3 py-1.5 rounded-xl text-[11px] font-bold transition-all {{ $currentStatus === 'approved' ? 'bg-emerald-600 text-white shadow-xs shadow-emerald-600/30' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200/80' }}">
                    Disetujui ({{ $statusCounts['approved'] ?? 0 }})
                </a>

                <a href="{{ route('student.journals', array_merge(request()->except(['page']), ['status' => 'review'])) }}"
                   class="shrink-0 px-3 py-1.5 rounded-xl text-[11px] font-bold transition-all {{ $currentStatus === 'review' ? 'bg-amber-600 text-white shadow-xs shadow-amber-600/30' : 'bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200/80' }}">
                    Review ({{ $statusCounts['review'] ?? 0 }})
                </a>

                <a href="{{ route('student.journals', array_merge(request()->except(['page']), ['status' => 'draft'])) }}"
                   class="shrink-0 px-3 py-1.5 rounded-xl text-[11px] font-bold transition-all {{ $currentStatus === 'draft' ? 'bg-slate-700 text-white shadow-xs shadow-slate-700/30' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/80' }}">
                    Draft ({{ $statusCounts['draft'] ?? 0 }})
                </a>

                <a href="{{ route('student.journals', array_merge(request()->except(['page']), ['status' => 'revision'])) }}"
                   class="shrink-0 px-3 py-1.5 rounded-xl text-[11px] font-bold transition-all {{ $currentStatus === 'revision' ? 'bg-rose-600 text-white shadow-xs shadow-rose-600/30' : 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200/80' }}">
                    Revisi ({{ $statusCounts['revision'] ?? 0 }})
                </a>
            </div>

            <!-- Active Filter Reset Notification -->
            @if(request()->filled('search') || (request()->filled('status') && request('status') !== 'all'))
                <div class="flex items-center justify-between text-[11px] bg-indigo-50/80 px-3 py-1.5 rounded-xl border border-indigo-100">
                    <span class="text-indigo-900 font-medium truncate">
                        Filter aktif: 
                        @if(request('search')) <strong>"{{ request('search') }}"</strong> @endif
                        @if(request('status') && request('status') !== 'all') <span class="capitalize font-bold text-indigo-700">({{ request('status') }})</span> @endif
                    </span>
                    <a href="{{ route('student.journals') }}" class="text-indigo-600 hover:text-indigo-800 font-bold shrink-0 ml-2">
                        ✕ Reset
                    </a>
                </div>
            @endif
        </div>

        @if($journals->isEmpty())
            <x-empty-state 
                title="Tidak Ada Jurnal Ditemukan" 
                description="Tidak ada logbook jurnal yang sesuai dengan kriteria filter atau pencarian Anda."
                class="py-6"
            />
        @else
            <div class="space-y-3 pt-1">
                @foreach($journals as $j)
                    <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/70 space-y-2.5 hover:bg-slate-50 transition-colors">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-bold text-slate-900">
                                {{ \Carbon\Carbon::parse($j->date)->isoFormat('dddd, D MMMM Y') }}
                            </span>
                            <x-badge :variant="$j->status" size="xs">
                                {{ strtoupper($j->status) }}
                            </x-badge>
                        </div>

                        <p class="text-xs text-slate-600 leading-relaxed font-medium">{{ $j->activity_description }}</p>

                        <!-- Photos preview -->
                        @if($j->photos->isNotEmpty())
                            <div class="flex space-x-2 pt-1 overflow-x-auto">
                                @foreach($j->photos as $ph)
                                    <img src="{{ asset('storage/' . $ph->photo_path) }}" class="w-16 h-16 rounded-xl object-cover border border-slate-200 shadow-2xs shrink-0" alt="Dokumentasi Kerja">
                                @endforeach
                            </div>
                        @endif

                        <!-- Review feedback history -->
                        @if($j->approvals->isNotEmpty())
                            <div class="mt-3 pt-3 border-t border-slate-200/60 space-y-2">
                                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Catatan & Evaluasi Pembimbing</span>
                                @foreach($j->approvals as $appr)
                                    <div class="p-3 rounded-2xl bg-white border border-slate-200/80 shadow-2xs space-y-1.5">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <div class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100 flex items-center justify-center text-[10px] font-extrabold shrink-0">
                                                    {{ substr($appr->approver->name ?? 'G', 0, 1) }}
                                                </div>
                                                <div class="min-w-0 truncate">
                                                    <span class="text-xs font-bold text-slate-800 truncate block">{{ $appr->approver->name }}</span>
                                                    <span class="text-[10px] text-slate-400 capitalize block">{{ str_replace('_', ' ', $appr->role_level) }}</span>
                                                </div>
                                            </div>
                                            <x-badge :variant="$appr->status === 'approved' ? 'success' : ($appr->status === 'rejected' ? 'danger' : 'warning')" size="xs">
                                                {{ strtoupper($appr->status) }}
                                            </x-badge>
                                        </div>
                                        @if($appr->notes)
                                            <p class="text-xs text-slate-600 bg-slate-50 p-2.5 rounded-xl border border-slate-100 italic font-medium leading-relaxed">
                                                "{{ $appr->notes }}"
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Re-submit button if revision or draft -->
                        @if(in_array($j->status, ['draft', 'revision']))
                            <form action="{{ route('student.journal.submit', $j->id) }}" method="POST" class="pt-1.5">
                                @csrf
                                <button type="submit" class="w-full py-2.5 px-3 rounded-2xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs border border-indigo-200 transition-colors cursor-pointer">
                                    Ajukan Review Ulang
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Modular Pagination -->
            <x-pagination :paginator="$journals" />
        @endif
    </div>

</div>

@push('scripts')
<script>
    function updateCharCount() {
        const textarea = document.getElementById('journal-desc');
        if (!textarea) return;
        const counter = document.getElementById('char-counter');
        const len = textarea.value.length;
        if (len >= 50) {
            counter.className = 'text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200/80 transition-all font-mono';
            counter.innerHTML = '✓ ' + len + ' karakter (Lengkap)';
        } else {
            const remaining = 50 - len;
            counter.className = 'text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200/80 transition-all font-mono';
            counter.innerText = len + ' / min 50 (Kurang ' + remaining + ')';
        }
    }

    function previewJournalPhotos(event) {
        const files = Array.from(event.target.files);
        const container = document.getElementById('photos-preview-container');
        const badge = document.getElementById('photo-count-badge');
        
        if (files.length > 3) {
            alert('Maksimal hanya dapat memilih 3 foto dokumentasi kegiatan.');
            event.target.value = '';
            container.innerHTML = '';
            container.classList.add('hidden');
            badge.innerText = 'Maks. 3 Foto (Opsional)';
            badge.className = 'text-[10px] font-semibold text-slate-400';
            return;
        }

        if (files.length === 0) {
            container.innerHTML = '';
            container.classList.add('hidden');
            badge.innerText = 'Maks. 3 Foto (Opsional)';
            badge.className = 'text-[10px] font-semibold text-slate-400';
            return;
        }

        container.innerHTML = '';
        container.classList.remove('hidden');
        badge.innerText = files.length + ' Foto Terpilih';
        badge.className = 'text-[10px] font-bold text-indigo-600';

        files.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'relative aspect-square rounded-2xl overflow-hidden border border-slate-200 shadow-2xs group bg-slate-100';
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
    }

    document.addEventListener('DOMContentLoaded', updateCharCount);
</script>
@endpush
@endsection
