@php
    $pendingGuru = $stats['pending_guru'] ?? 0;
    $approvedCount = $stats['approved'] ?? 0;
    $revisionCount = $stats['revision'] ?? 0;
    $allPendingJournals = $studentPkls->flatMap->journals->filter(fn($j) => $j->status === 'review_guru' || ($j->status === 'submitted' && !($j->studentPkl->company->requires_industry_approval ?? true)));

    $initialTab = session('active_tab') ?? request('tab') ?? 'students';
    if (!in_array($initialTab, ['students', 'pending', 'table'], true)) {
        $initialTab = 'students';
    }
@endphp

<div class="space-y-3.5 pb-10">

    <!-- ============================================================== -->
    <!-- 1. SEGMENTED VIEW CONTROLLER (TABS)                            -->
    <!-- ============================================================== -->
    <div class="flex items-center gap-1 p-1 bg-slate-200/70 rounded-2xl border border-slate-200 text-xs font-bold">
        <button type="button" id="m-tab-btn-students" onclick="switchMobileJournalView('students')" 
                class="m-view-tab-btn flex-1 py-2 px-2.5 rounded-xl text-center transition-all cursor-pointer {{ $initialTab === 'students' ? 'bg-[#0B2B25] text-[#C6F26B] shadow-xs font-extrabold' : 'text-slate-700 hover:bg-white/60' }}">
            <span>Per Siswa</span>
            <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] {{ $initialTab === 'students' ? 'bg-white/20 text-[#C6F26B]' : 'bg-slate-300/80 text-slate-800' }} font-mono">
                {{ $studentPkls->count() }}
            </span>
        </button>

        <button type="button" id="m-tab-btn-pending" onclick="switchMobileJournalView('pending')" 
                class="m-view-tab-btn flex-1 py-2 px-2.5 rounded-xl text-center transition-all cursor-pointer {{ $initialTab === 'pending' ? 'bg-[#0B2B25] text-[#C6F26B] shadow-xs font-extrabold' : 'text-slate-700 hover:bg-white/60' }}">
            <span>Antrean</span>
            @if($pendingGuru > 0)
                <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] bg-amber-400 text-[#0B2B25] font-mono animate-pulse">
                    {{ $pendingGuru }}
                </span>
            @endif
        </button>

        <button type="button" id="m-tab-btn-table" onclick="switchMobileJournalView('table')" 
                class="m-view-tab-btn flex-1 py-2 px-2.5 rounded-xl text-center transition-all cursor-pointer {{ $initialTab === 'table' ? 'bg-[#0B2B25] text-[#C6F26B] shadow-xs font-extrabold' : 'text-slate-700 hover:bg-white/60' }}">
            <span>Semua Log</span>
        </button>
    </div>

    <!-- ============================================================== -->
    <!-- VIEW 1: KELOMPOK PER SISWA BIMBINGAN                           -->
    <!-- ============================================================== -->
    <div id="m-view-students" class="{{ $initialTab === 'students' ? 'space-y-3' : 'hidden space-y-3' }}">
        @forelse($studentPkls as $sp)
            @php
                $sJournals = $sp->journals ?? collect();
                $sPending = $sJournals->filter(fn($j) => $j->status === 'review_guru' || ($j->status === 'submitted' && !($sp->company->requires_industry_approval ?? true)))->count();
                $sApproved = $sJournals->where('status', 'approved')->count();
                $sRevision = $sJournals->where('status', 'revision')->count();
                $studentName = $sp->student->user->name ?? 'Siswa';
                $initial = strtoupper(substr($studentName, 0, 1));
            @endphp

            <div class="bg-white rounded-[22px] border border-slate-200/80 shadow-2xs hover:border-[#0B2B25]/30 transition-all overflow-hidden">
                <!-- Card Header (Clickable Accordion) -->
                <div onclick="toggleMobileStudentJournals('{{ $sp->id }}')" 
                     class="p-3.5 flex flex-col gap-2.5 cursor-pointer hover:bg-slate-50/70 transition-colors">
                    
                    <!-- Identity Row -->
                    <div class="flex items-start justify-between gap-2.5">
                        <div class="flex items-center space-x-2.5 min-w-0">
                            <div class="w-9 h-9 rounded-xl bg-[#0B2B25]/5 border border-[#0B2B25]/10 text-[#0B2B25] font-space font-bold text-xs flex items-center justify-center shrink-0">
                                {{ $initial }}
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <h4 class="text-xs font-bold text-slate-900 truncate leading-snug">{{ $studentName }}</h4>
                                    <span class="px-1.5 py-0.2 rounded-md text-[9.5px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ $sp->student->class->name ?? 'Kelas' }}
                                    </span>
                                </div>
                                <p class="text-[10px] text-slate-500 font-mono truncate mt-0.5">{{ $sp->company->name ?? 'Mitra DU/DI' }}</p>
                            </div>
                        </div>

                        <!-- Pending Alert Pill -->
                        @if($sPending > 0)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-200 shrink-0 animate-pulse">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                <span>{{ $sPending }} Review</span>
                            </span>
                        @endif
                    </div>

                    <!-- Progress Chips & Action -->
                    <div class="flex items-center justify-between pt-2 border-t border-slate-100/80 text-xs">
                        <div class="flex items-center gap-1.5">
                            <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-[10.5px] font-bold text-slate-700">
                                {{ $sJournals->count() }} Total
                            </span>
                            <span class="px-2 py-0.5 rounded-lg bg-emerald-50 text-[10.5px] text-emerald-800 font-bold border border-emerald-200">
                                {{ $sApproved }} Disetujui
                            </span>
                        </div>

                        <div class="inline-flex items-center gap-1 text-[11px] font-bold text-[#0B2B25]">
                            <span>Riwayat</span>
                            <svg id="m-chevron-{{ $sp->id }}" class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                </div>

                <!-- Accordion Body: Journals of this Student -->
                <div id="m-journals-{{ $sp->id }}" class="hidden border-t border-slate-100 bg-slate-50/60 p-3 space-y-2.5">
                    @forelse($sJournals as $sj)
                        @php
                            $isPendingItem = $sj->status === 'review_guru' || ($sj->status === 'submitted' && !($sp->company->requires_industry_approval ?? true));
                        @endphp
                        <div class="bg-white p-3 rounded-xl border border-slate-200/80 shadow-2xs space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-mono text-[10.5px] font-bold text-slate-800">
                                    {{ \Carbon\Carbon::parse($sj->date)->isoFormat('dddd, D MMM Y') }}
                                </span>
                                <x-badge :variant="$sj->status" size="xs">
                                    {{ strtoupper(str_replace('_', ' ', $sj->status)) }}
                                </x-badge>
                            </div>

                            <p class="text-xs text-slate-700 leading-relaxed line-clamp-3">
                                {{ $sj->activity_description }}
                            </p>

                            @if($sj->photos->isNotEmpty())
                                <div class="flex items-center gap-1.5 pt-1 overflow-x-auto [scrollbar-width:none]">
                                    @foreach($sj->photos as $ph)
                                        <button type="button" 
                                                onclick="openImageLightbox('{{ $ph->photo_url }}', 'Dokumentasi: {{ addslashes($sp->student->user->name ?? 'Siswa') }}')" 
                                                class="w-12 h-12 rounded-lg overflow-hidden border border-slate-200 shadow-2xs shrink-0 block cursor-pointer text-left" 
                                                title="Lihat foto">
                                            <img src="{{ $ph->photo_url }}" class="w-full h-full object-cover" alt="Foto">
                                        </button>
                                    @endforeach
                                </div>
                            @endif

                            <div class="flex items-center justify-end pt-1.5 border-t border-slate-100">
                                <button type="button" onclick="openJournalModal('{{ $sj->id }}')" 
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-extrabold transition-all cursor-pointer {{ $isPendingItem ? 'bg-[#0B2B25] text-[#C6F26B] shadow-2xs hover:bg-[#12382F]' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                                    {{ $isPendingItem ? 'Tinjau & Putuskan' : 'Lihat Detail' }}
                                </button>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 text-center py-4">Belum ada riwayat jurnal yang dikirimkan siswa ini.</p>
                    @endforelse
                </div>
            </div>
        @empty
            <div class="py-10 px-5 bg-white rounded-[24px] border border-slate-200/80 text-center shadow-2xs">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <h4 class="font-space font-bold text-sm text-slate-800">Belum Ada Siswa Bimbingan</h4>
                <p class="text-xs text-slate-500 mt-1 max-w-[260px] mx-auto leading-relaxed">
                    Anda belum memiliki alokasi siswa bimbingan PKL yang aktif saat ini.
                </p>
            </div>
        @endforelse
    </div>

    <!-- ============================================================== -->
    <!-- VIEW 2: ANTREAN BUTUH TINDAKAN (ACTION ITEMS QUEUE)            -->
    <!-- ============================================================== -->
    <div id="m-view-pending" class="{{ $initialTab === 'pending' ? 'space-y-3' : 'hidden space-y-3' }}">
        @if($allPendingJournals->isEmpty())
            <div class="py-10 px-5 bg-white rounded-[24px] border border-slate-200/80 text-center shadow-2xs">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center mx-auto mb-3 shadow-2xs">
                    <svg class="w-6 h-6 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h4 class="font-space font-bold text-sm text-slate-800">Semua Jurnal Telah Diperiksa</h4>
                <p class="text-xs text-slate-500 mt-1 max-w-[270px] mx-auto leading-relaxed">
                    Tidak ada antrean jurnal siswa yang membutuhkan validasi kurikulum tingkat 2 saat ini.
                </p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($allPendingJournals as $pj)
                    <div class="bg-white rounded-[22px] border border-amber-200 p-3.5 shadow-2xs space-y-3">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-900 font-space font-bold text-xs flex items-center justify-center shrink-0">
                                {{ substr($pj->studentPkl->student->user->name, 0, 1) }}
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-xs font-bold text-slate-900 truncate leading-snug">{{ $pj->studentPkl->student->user->name }}</h4>
                                <p class="text-[10px] text-slate-500 font-mono truncate mt-0.5">
                                    {{ $pj->studentPkl->company->name }} &bull; {{ \Carbon\Carbon::parse($pj->date)->isoFormat('dddd, D MMM Y') }}
                                </p>
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs text-slate-800 leading-relaxed font-normal">
                            {{ $pj->activity_description }}
                        </div>

                        @if($pj->photos->isNotEmpty())
                            <div class="flex items-center gap-1.5 overflow-x-auto [scrollbar-width:none]">
                                @foreach($pj->photos as $ph)
                                    <button type="button" 
                                            onclick="openImageLightbox('{{ $ph->photo_url }}', 'Dokumentasi: {{ addslashes($pj->studentPkl->student->user->name ?? 'Siswa') }}')" 
                                            class="w-14 h-14 rounded-xl overflow-hidden border border-slate-200 shadow-2xs shrink-0 block cursor-pointer text-left" 
                                            title="Lihat foto">
                                        <img src="{{ $ph->photo_url }}" class="w-full h-full object-cover" alt="Foto">
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        <!-- Form Evaluasi Guru -->
                        <form action="{{ route('guru.journals.process', $pj->id) }}" method="POST" class="pt-2 border-t border-slate-100 space-y-2.5">
                            @csrf
                            <input type="hidden" name="tab" value="pending">
                            <textarea name="notes" rows="2" placeholder="Catatan evaluasi kurikulum atau alasan revisi..." 
                                      class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0B2B25]/20 font-medium text-slate-800"></textarea>
                            
                            <div class="flex items-center gap-2">
                                <button type="submit" name="action" value="revision" onclick="return confirm('Minta siswa merevisi jurnal ini?')" 
                                        class="flex-1 py-2 rounded-xl bg-white hover:bg-rose-50 text-rose-600 font-bold text-xs border border-rose-200 transition-colors cursor-pointer">
                                    Minta Revisi
                                </button>
                                <button type="submit" name="action" value="approve" 
                                        class="flex-1 py-2 rounded-xl bg-[#0B2B25] hover:bg-[#12382F] text-[#C6F26B] font-extrabold text-xs shadow-xs transition-all cursor-pointer">
                                    Setujui Final
                                </button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- ============================================================== -->
    <!-- VIEW 3: SEMUA LOG JURNAL (FILTER & LIST CARD)                  -->
    <!-- ============================================================== -->
    <div id="m-view-table" class="{{ $initialTab === 'table' ? 'space-y-3' : 'hidden space-y-3' }}">
        <!-- Quick Search & Filter Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-3 shadow-2xs">
            <form method="GET" action="{{ route('guru.journals') }}" class="space-y-2">
                <input type="hidden" name="tab" value="table">
                <div class="relative">
                    <svg class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari aktivitas atau siswa..." 
                           class="w-full pl-9 pr-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-medium text-slate-900 focus:bg-white focus:outline-none">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <select name="student_pkl_id" class="w-full px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-medium text-slate-900 focus:bg-white focus:outline-none truncate">
                        <option value="">Semua Siswa</option>
                        @foreach($studentPkls as $sp)
                            <option value="{{ $sp->id }}" {{ request('student_pkl_id') == $sp->id ? 'selected' : '' }}>
                                {{ $sp->student->user->name }}
                            </option>
                        @endforeach
                    </select>

                    <input type="date" name="date" value="{{ request('date') }}" 
                           class="w-full px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-mono text-slate-900 focus:bg-white focus:outline-none">
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <button type="submit" class="flex-1 py-2 rounded-xl bg-[#0B2B25] text-[#C6F26B] font-extrabold text-xs shadow-xs hover:bg-[#12382F] transition-all cursor-pointer">
                        Terapkan Filter
                    </button>
                    @if(request()->filled('search') || request()->filled('student_pkl_id') || request()->filled('date'))
                        <a href="{{ route('guru.journals', ['tab' => 'table']) }}" class="px-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 font-bold text-xs">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Journal Card List -->
        <div class="space-y-2.5">
            @forelse($journals as $j)
                @php
                    $canReview = ($j->status === 'review_guru') || ($j->status === 'submitted' && !($j->studentPkl->company->requires_industry_approval ?? true));
                    $jName = $j->studentPkl->student->user->name ?? 'Siswa';
                    $jInit = strtoupper(substr($jName, 0, 1));
                @endphp
                <div class="bg-white rounded-[22px] border border-slate-200/80 p-3.5 shadow-2xs space-y-2.5 hover:border-[#0B2B25]/30 transition-all">
                    <div class="flex items-start justify-between gap-2.5">
                        <div class="flex items-center space-x-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-xl bg-[#0B2B25]/5 border border-[#0B2B25]/10 text-[#0B2B25] font-space font-bold text-xs flex items-center justify-center shrink-0">
                                {{ $jInit }}
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-bold text-slate-900 text-xs truncate leading-snug">{{ $jName }}</h4>
                                <p class="text-[10px] text-slate-500 font-mono truncate mt-0.5">{{ $j->studentPkl->company->name }}</p>
                            </div>
                        </div>

                        <x-badge :variant="$j->status" size="xs">
                            {{ strtoupper(str_replace('_', ' ', $j->status)) }}
                        </x-badge>
                    </div>

                    <p class="text-xs text-slate-700 leading-relaxed line-clamp-2">
                        {{ $j->activity_description }}
                    </p>

                    <div class="flex items-center justify-between pt-1 border-t border-slate-100 text-[10px] text-slate-400 font-mono">
                        <span>{{ \Carbon\Carbon::parse($j->date)->isoFormat('dddd, D MMM') }}</span>

                        <button type="button" onclick="openJournalModal('{{ $j->id }}')" 
                                class="px-2.5 py-1 rounded-lg text-[11px] font-extrabold transition-all cursor-pointer {{ $canReview ? 'bg-[#0B2B25] text-[#C6F26B] shadow-2xs hover:bg-[#12382F]' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                            {{ $canReview ? 'Tinjau' : 'Detail' }}
                        </button>
                    </div>
                </div>
            @empty
                <div class="py-10 px-5 bg-white rounded-[24px] border border-slate-200/80 text-center shadow-2xs">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h4 class="font-space font-bold text-sm text-slate-800">Tidak Ada Data Jurnal</h4>
                    <p class="text-xs text-slate-500 mt-1 max-w-[260px] mx-auto leading-relaxed">
                        Tidak ditemukan catatan logbook jurnal siswa pada kriteria filter ini.
                    </p>
                </div>
            @endforelse
        </div>

        @if($journals->hasPages())
            <div class="pt-2">
                <x-pagination :paginator="$journals" />
            </div>
        @endif
    </div>

</div>

<script>
    function switchMobileJournalView(viewName) {
        const validViews = ['students', 'pending', 'table'];
        if (!validViews.includes(viewName)) {
            viewName = 'students';
        }

        ['students', 'pending', 'table'].forEach(v => {
            const el = document.getElementById('m-view-' + v);
            const btn = document.getElementById('m-tab-btn-' + v);
            if (el) el.classList.add('hidden');
            if (btn) {
                btn.classList.remove('bg-[#0B2B25]', 'text-[#C6F26B]', 'shadow-xs', 'font-extrabold');
                btn.classList.add('text-slate-700');
            }
        });

        const activeEl = document.getElementById('m-view-' + viewName);
        const activeBtn = document.getElementById('m-tab-btn-' + viewName);
        if (activeEl) activeEl.classList.remove('hidden');
        if (activeBtn) {
            activeBtn.classList.add('bg-[#0B2B25]', 'text-[#C6F26B]', 'shadow-xs', 'font-extrabold');
            activeBtn.classList.remove('text-slate-700');
        }

        try {
            if (window.history && window.history.replaceState) {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', viewName);
                window.history.replaceState(null, '', url.toString());
            }
            localStorage.setItem('active_journal_tab_guru', viewName);
        } catch (e) {}
    }

    function toggleMobileStudentJournals(spId) {
        const content = document.getElementById('m-journals-' + spId);
        const chevron = document.getElementById('m-chevron-' + spId);
        if (!content) return;

        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
        } else {
            content.classList.add('hidden');
            if (chevron) chevron.classList.remove('rotate-180');
        }
    }
</script>
