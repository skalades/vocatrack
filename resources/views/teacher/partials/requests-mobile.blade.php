@php
    $totalRequests = $stats['total'] ?? $requests->total();
    $pendingCount = $stats['pending'] ?? 0;
    $approvedCount = $stats['approved'] ?? 0;
    $rejectedCount = $stats['rejected'] ?? 0;

    $typeBadges = [
        'izin'  => ['label' => 'Izin',  'class' => 'bg-sky-50 text-sky-800 border-sky-200/80',       'dot' => 'bg-sky-500'],
        'sakit' => ['label' => 'Sakit', 'class' => 'bg-purple-50 text-purple-800 border-purple-200/80', 'dot' => 'bg-purple-500'],
    ];

    $statusBadges = [
        'pending'  => ['label' => 'Menunggu Review', 'class' => 'bg-amber-50 text-amber-800 border-amber-200/80',    'dot' => 'bg-amber-500'],
        'approved' => ['label' => 'Disetujui',       'class' => 'bg-emerald-50 text-emerald-800 border-emerald-200/80', 'dot' => 'bg-emerald-500'],
        'rejected' => ['label' => 'Ditolak',         'class' => 'bg-rose-50 text-rose-800 border-rose-200/80',       'dot' => 'bg-rose-500'],
    ];
@endphp

<div class="space-y-3.5 pb-10">

    <!-- ============================================================== -->
    <!-- 1. HORIZONTAL FILTER PILLS (NO SCROLLBAR)                      -->
    <!-- ============================================================== -->
    <div class="flex items-center gap-1.5 overflow-x-auto py-1 -mx-0.5 px-0.5 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        <button type="button" onclick="filterMobileRequests('all')" id="m-pill-all" 
                class="m-pill-filter shrink-0 px-3.5 py-1.5 rounded-full text-[11.5px] font-bold transition-all bg-[#0B2B25] text-[#C6F26B] shadow-xs cursor-pointer">
            Semua ({{ $totalRequests }})
        </button>
        <button type="button" onclick="filterMobileRequests('pending')" id="m-pill-pending" 
                class="m-pill-filter shrink-0 px-3.5 py-1.5 rounded-full text-[11.5px] font-bold transition-all bg-white text-slate-600 border border-slate-200/90 hover:bg-slate-50 cursor-pointer shadow-2xs">
            Menunggu ({{ $pendingCount }})
        </button>
        <button type="button" onclick="filterMobileRequests('approved')" id="m-pill-approved" 
                class="m-pill-filter shrink-0 px-3.5 py-1.5 rounded-full text-[11.5px] font-bold transition-all bg-white text-slate-600 border border-slate-200/90 hover:bg-slate-50 cursor-pointer shadow-2xs">
            Disetujui ({{ $approvedCount }})
        </button>
        <button type="button" onclick="filterMobileRequests('rejected')" id="m-pill-rejected" 
                class="m-pill-filter shrink-0 px-3.5 py-1.5 rounded-full text-[11.5px] font-bold transition-all bg-white text-slate-600 border border-slate-200/90 hover:bg-slate-50 cursor-pointer shadow-2xs">
            Ditolak ({{ $rejectedCount }})
        </button>
    </div>

    <!-- ============================================================== -->
    <!-- 3. DAFTAR PERMOHONAN IZIN & SAKIT SISWA BINAAN                 -->
    <!-- ============================================================== -->
    <div class="space-y-2.5" id="mobile-requests-container">
        @forelse($requests as $req)
            @php
                $studentName = $req->studentPkl->student->user->name ?? 'Siswa';
                $companyName = $req->studentPkl->company->name ?? 'Mitra DU/DI';
                $initial = strtoupper(substr($studentName, 0, 1));
                $tBadge = $typeBadges[$req->type] ?? ['label' => strtoupper($req->type), 'class' => 'bg-slate-100 text-slate-800 border-slate-200', 'dot' => 'bg-slate-400'];
                $sBadge = $statusBadges[$req->status] ?? ['label' => strtoupper($req->status), 'class' => 'bg-slate-100 text-slate-800 border-slate-200', 'dot' => 'bg-slate-400'];
                
                $start = \Carbon\Carbon::parse($req->start_date);
                $end = \Carbon\Carbon::parse($req->end_date);
                $durationDays = $start->diffInDays($end) + 1;
            @endphp

            <div class="m-request-card bg-white rounded-[22px] border border-slate-200/80 p-3.5 shadow-2xs space-y-3 hover:border-[#0B2B25]/30 transition-all" 
                 data-status="{{ $req->status }}" 
                 data-type="{{ $req->type }}">
                
                <!-- Card Header: Avatar + Student Info + Status & Type Badges -->
                <div class="flex items-start justify-between gap-2.5">
                    <div class="flex items-center space-x-2.5 min-w-0">
                        <div class="w-9 h-9 rounded-xl bg-[#0B2B25]/5 border border-[#0B2B25]/10 text-[#0B2B25] font-space font-bold text-xs flex items-center justify-center shrink-0">
                            {{ $initial }}
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-bold text-slate-900 text-xs truncate leading-snug">{{ $studentName }}</h4>
                            <p class="text-[10px] text-slate-500 font-mono truncate mt-0.5">
                                {{ $req->studentPkl->student->class->name ?? 'Kelas' }} &bull; {{ $companyName }}
                            </p>
                        </div>
                    </div>

                    <!-- Badges -->
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $tBadge['class'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $tBadge['dot'] }}"></span>
                            <span>{{ $tBadge['label'] }}</span>
                        </span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $sBadge['class'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $sBadge['dot'] }}"></span>
                            <span>{{ $sBadge['label'] }}</span>
                        </span>
                    </div>
                </div>

                <!-- Periode & Alasan Card -->
                <div class="p-3 rounded-xl bg-slate-50/80 border border-slate-100 text-xs space-y-1.5">
                    <div class="flex items-center justify-between text-slate-600 text-[11px] font-medium">
                        <div class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>{{ $start->format('d M Y') }} &ndash; {{ $end->format('d M Y') }}</span>
                        </div>
                        <span class="px-1.5 py-0.2 rounded font-mono text-[10px] font-bold bg-white border border-slate-200 text-slate-700">
                            {{ $durationDays }} hari
                        </span>
                    </div>
                    <p class="text-slate-800 text-[11.5px] leading-relaxed font-normal">
                        "{{ $req->reason }}"
                    </p>
                </div>

                <!-- Attachment Link (Surat Bukti / Foto Dokter) -->
                @if($req->attachment_path)
                    <div>
                        <a href="{{ $req->attachment_url }}" target="_blank" 
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold border border-emerald-200 transition-colors">
                            <svg class="w-3.5 h-3.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            <span>Lihat Lampiran Surat / Bukti</span>
                        </a>
                    </div>
                @endif

                <!-- Decision Action Form (Jika Pending) / Result Info (Jika Sudah Selesai) -->
                @if($req->status === 'pending')
                    <form action="{{ route('guru.requests.process', $req->id) }}" method="POST" class="pt-2 border-t border-slate-100 space-y-2">
                        @csrf
                        <input type="text" name="reviewer_notes" placeholder="Catatan verifikasi atau instruksi (opsional)..." 
                               class="w-full text-xs p-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0B2B25]/20 font-medium text-slate-800">
                        
                        <div class="flex items-center gap-2">
                            <button type="submit" name="action" value="reject" onclick="return confirm('Tolak permohonan ini?')" 
                                    class="flex-1 py-2 rounded-xl bg-white hover:bg-rose-50 text-rose-600 font-bold text-xs border border-rose-200 transition-colors cursor-pointer">
                                Tolak
                            </button>
                            <button type="submit" name="action" value="approve" 
                                    class="flex-1 py-2 rounded-xl bg-[#0B2B25] hover:bg-[#12382F] text-[#C6F26B] font-extrabold text-xs shadow-xs transition-all cursor-pointer">
                                Setujui Izin
                            </button>
                        </div>
                    </form>
                @else
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                        <span class="text-[10px] font-mono text-slate-400">Diajukan: {{ $req->created_at->diffForHumans() }}</span>
                        @if($req->reviewer_notes)
                            <span class="text-[11px] font-medium italic text-slate-600 truncate max-w-[200px]" title="{{ $req->reviewer_notes }}">
                                Catatan: "{{ $req->reviewer_notes }}"
                            </span>
                        @endif
                    </div>
                @endif

            </div>
        @empty
            <div class="py-10 px-5 bg-white rounded-[24px] border border-slate-200/80 text-center shadow-2xs">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center mx-auto mb-3 shadow-2xs">
                    <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h4 class="font-space font-bold text-sm text-slate-800">Tidak Ada Permohonan Izin / Sakit</h4>
                <p class="text-xs text-slate-500 mt-1 max-w-[270px] mx-auto leading-relaxed">
                    Belum ada permohonan izin atau dispensasi sakit yang diajukan oleh siswa bimbingan Anda.
                </p>
            </div>
        @endforelse

        <div id="m-empty-filtered" class="hidden py-10 px-5 bg-white rounded-[24px] border border-slate-200/80 text-center shadow-2xs">
            <h4 class="font-space font-bold text-sm text-slate-800">Tidak Ada Data</h4>
            <p class="text-xs text-slate-500 mt-1">Tidak ada permohonan dengan status filter yang dipilih.</p>
            <button type="button" onclick="filterMobileRequests('all')" class="mt-3 px-3.5 py-1.5 rounded-xl bg-[#0B2B25] text-[#C6F26B] font-extrabold text-xs cursor-pointer">
                Tampilkan Semua
            </button>
        </div>
    </div>

    <!-- Pagination -->
    @if($requests->hasPages())
        <div class="pt-2">
            <x-pagination :paginator="$requests" />
        </div>
    @endif

</div>

<script>
    function filterMobileRequests(status) {
        const cards = document.querySelectorAll('.m-request-card');
        const emptyMsg = document.getElementById('m-empty-filtered');
        let visibleCount = 0;

        cards.forEach(card => {
            const cardStatus = card.getAttribute('data-status');
            if (status === 'all' || cardStatus === status) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (emptyMsg) {
            emptyMsg.style.display = (cards.length > 0 && visibleCount === 0) ? 'block' : 'none';
        }

        // Update Pill Buttons
        document.querySelectorAll('.m-pill-filter').forEach(btn => {
            btn.classList.remove('bg-[#0B2B25]', 'text-[#C6F26B]', 'shadow-xs');
            btn.classList.add('bg-white', 'text-slate-600', 'border', 'border-slate-200/90');
        });
        const activePill = document.getElementById('m-pill-' + status);
        if (activePill) {
            activePill.classList.add('bg-[#0B2B25]', 'text-[#C6F26B]', 'shadow-xs');
            activePill.classList.remove('bg-white', 'text-slate-600', 'border', 'border-slate-200/90');
        }

        // Update KPI tile styles
        document.querySelectorAll('.m-kpi-filter').forEach(kpi => {
            kpi.classList.remove('bg-[#C6F26B]', 'text-[#0B2B25]', 'shadow-xs');
            kpi.classList.add('bg-white/[0.08]', 'text-white');
        });
        const activeKpi = document.getElementById('m-kpi-' + status);
        if (activeKpi) {
            activeKpi.classList.add('bg-[#C6F26B]', 'text-[#0B2B25]', 'shadow-xs');
            activeKpi.classList.remove('bg-white/[0.08]', 'text-white');
        }
    }
</script>
