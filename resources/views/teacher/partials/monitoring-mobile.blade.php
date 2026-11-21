@php
    $currentStatus = request('status', '');
    $currentDate = request('date', '');
    $statusOptions = [
        ''            => 'Semua',
        'hadir'       => 'Hadir',
        'terlambat'   => 'Terlambat',
        'izin'        => 'Izin',
        'sakit'       => 'Sakit',
        'dinas_luar'  => 'Dinas Luar',
        'luar_kantor' => 'WFH / Luar',
        'alfa'        => 'Alfa',
    ];

    $statusBadges = [
        'hadir'       => ['label' => 'Hadir Tepat', 'class' => 'bg-emerald-50 text-emerald-800 border-emerald-200/80', 'dot' => 'bg-emerald-500'],
        'terlambat'   => ['label' => 'Terlambat',   'class' => 'bg-amber-50 text-amber-800 border-amber-200/80',       'dot' => 'bg-amber-500'],
        'izin'        => ['label' => 'Izin',        'class' => 'bg-sky-50 text-sky-800 border-sky-200/80',             'dot' => 'bg-sky-500'],
        'sakit'       => ['label' => 'Sakit',       'class' => 'bg-purple-50 text-purple-800 border-purple-200/80',    'dot' => 'bg-purple-500'],
        'dinas_luar'  => ['label' => 'Dinas Luar',  'class' => 'bg-teal-50 text-teal-800 border-teal-200/80',          'dot' => 'bg-teal-500'],
        'luar_kantor' => ['label' => 'Luar Kantor', 'class' => 'bg-teal-50 text-teal-800 border-teal-200/80',          'dot' => 'bg-teal-500'],
        'alfa'        => ['label' => 'Alfa',        'class' => 'bg-rose-50 text-rose-800 border-rose-200/80',          'dot' => 'bg-rose-500'],
    ];

    $totalLog = $stats['total'] ?? $attendances->total();
    $hadirCount = $stats['hadir'] ?? 0;
    $lateCount = $stats['late'] ?? 0;
    $leaveCount = $stats['leave'] ?? 0;
@endphp

<div class="space-y-3.5 pb-10">

    <!-- ============================================================== -->
    <!-- 1. PERMOHONAN RESET SANDI SISWA (ACCORDION BERSIH)             -->
    <!-- ============================================================== -->
    @if(isset($pendingResetRequests) && $pendingResetRequests->isNotEmpty())
        <section class="rounded-2xl bg-amber-50/90 border border-amber-200/90 p-3 shadow-2xs">
            <div class="flex items-center justify-between gap-2.5">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-xl bg-amber-200/80 text-amber-900 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h4 class="text-xs font-extrabold text-amber-950 leading-tight truncate">
                            {{ $pendingResetRequests->count() }} Permohonan Reset Kata Sandi
                        </h4>
                        <p class="text-[10px] text-amber-800 font-medium truncate mt-0.5">
                            {{ $pendingResetRequests->first()->student->user?->name ?? 'Siswa' }}@if($pendingResetRequests->count() > 1) dan {{ $pendingResetRequests->count() - 1 }} lainnya @endif
                        </p>
                    </div>
                </div>
                <button type="button" 
                        onclick="document.getElementById('mobileResetAccordion').classList.toggle('hidden')" 
                        class="px-3 py-1.5 rounded-xl bg-[#0B2B25] hover:bg-[#12382F] text-[#C6F26B] font-extrabold text-[10.5px] shrink-0 transition-colors cursor-pointer shadow-xs">
                    Tinjau
                </button>
            </div>

            <div id="mobileResetAccordion" class="hidden mt-3 pt-2.5 border-t border-amber-200/80 space-y-2">
                @foreach($pendingResetRequests as $req)
                    <div class="p-2.5 bg-white rounded-xl border border-amber-100 flex items-center justify-between gap-2 text-xs shadow-2xs">
                        <div class="min-w-0">
                            <span class="font-extrabold text-slate-800 block truncate">{{ $req->student->user?->name ?? 'Siswa' }}</span>
                            <span class="text-slate-500 font-mono text-[9.5px] block mt-0.5">{{ $req->student->nisn }} &bull; {{ $req->created_at->diffForHumans() }}</span>
                        </div>
                        <form method="POST" action="{{ route('guru.students.reset-password', $req->student) }}" class="inline shrink-0" onsubmit="var p = prompt('Kata sandi baru sementara siswa (min. 8 karakter). Kosongkan untuk dibuatkan otomatis:'); if (p === null) return false; this.new_password.value = p; return confirm('Reset kata sandi siswa ini sekarang?');">
                            @csrf
                            <input type="hidden" name="new_password" value="">
                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-600 text-white font-extrabold text-[10px] hover:bg-emerald-700 transition-colors shadow-2xs cursor-pointer">
                                Reset
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <!-- ============================================================== -->
    <!-- 2.5 QUICK ACTION: OVERRIDE PRESENSI KENDALA HP                 -->
    <!-- ============================================================== -->
    <div class="flex items-center justify-between gap-2 bg-gradient-to-r from-[#0B2B25] to-[#12382F] p-3 rounded-2xl text-white shadow-sm">
        <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-8 h-8 rounded-xl bg-white/10 text-[#C6F26B] flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            </div>
            <div class="min-w-0">
                <h4 class="text-xs font-extrabold text-white truncate font-space">Kendala HP / Absen Manual?</h4>
                <p class="text-[10px] text-white/70 truncate">Input atau override presensi siswa binaan</p>
            </div>
        </div>
        <button type="button" 
                onclick="openOverrideAttendanceModal()" 
                class="px-3 py-1.5 rounded-xl bg-[#C6F26B] hover:bg-[#d4f884] text-[#0B2B25] font-extrabold text-[11px] shrink-0 transition-all cursor-pointer shadow-xs active:scale-95">
            + Override
        </button>
    </div>

    <!-- ============================================================== -->
    <!-- 3. HORIZONTAL STATUS FILTER PILLS (NO SCROLLBAR)               -->
    <!-- ============================================================== -->
    <div class="flex items-center gap-1.5 overflow-x-auto py-1 -mx-0.5 px-0.5 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        @foreach($statusOptions as $val => $label)
            @php
                $isActive = ($currentStatus === $val) || ($val === '' && !request()->filled('status'));
                $url = route('guru.monitoring', array_filter(['date' => $currentDate, 'status' => $val]));
            @endphp
            <a href="{{ $url }}" 
               class="shrink-0 px-3.5 py-1.5 rounded-full text-[11.5px] font-bold transition-all {{ $isActive ? 'bg-[#0B2B25] text-[#C6F26B] shadow-xs' : 'bg-white text-slate-600 border border-slate-200/90 hover:bg-slate-50 hover:text-slate-900 shadow-2xs' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <!-- ============================================================== -->
    <!-- 4. DAFTAR REKAMAN PRESENSI SISWA BINAAN                        -->
    <!-- ============================================================== -->
    <div class="space-y-2.5">
        @forelse($attendances as $a)
            @php
                $badge = $statusBadges[$a->status] ?? ['label' => ucfirst(str_replace('_', ' ', $a->status)), 'class' => 'bg-slate-100 text-slate-700 border-slate-200', 'dot' => 'bg-slate-400'];
                $studentName = $a->studentPkl->student->user->name ?? 'Siswa';
                $companyName = $a->studentPkl->company->name ?? 'Mitra DU/DI';
                $initial = strtoupper(substr($studentName, 0, 1));
                
                $detailPayload = [
                    'studentName' => $studentName,
                    'studentNisn' => $a->studentPkl->student->nisn ?? '-',
                    'companyName' => $companyName,
                    'date' => \Carbon\Carbon::parse($a->date)->format('d/m/Y'),
                    'checkinTime' => $a->checkin_time ? substr($a->checkin_time, 0, 5) : null,
                    'checkoutTime' => $a->checkout_time ? substr($a->checkout_time, 0, 5) : null,
                    'status' => $a->status,
                    'photoUrl' => $a->checkin_photo_path ? $a->checkin_photo_url : null,
                    'isWithinRadius' => $a->is_within_radius,
                    'studentLat' => $a->checkin_lat ? (float) $a->checkin_lat : null,
                    'studentLng' => $a->checkin_lng ? (float) $a->checkin_lng : null,
                    'companyLat' => $a->studentPkl->company->latitude ? (float) $a->studentPkl->company->latitude : null,
                    'companyLng' => $a->studentPkl->company->longitude ? (float) $a->studentPkl->company->longitude : null,
                    'radiusMeters' => $a->studentPkl->company->radius_meters ?? null,
                    'notes' => $a->checkout_notes ?? '',
                ];
            @endphp

            <div class="bg-white rounded-[22px] border {{ $a->is_within_radius === false ? 'border-rose-200 bg-rose-50/20' : 'border-slate-200/80' }} p-3.5 shadow-2xs space-y-2.5 hover:border-[#0B2B25]/30 transition-all">
                <!-- Header Card: Avatar + Student Info + Status Badge -->
                <div class="flex items-start justify-between gap-2.5">
                    <div class="flex items-center space-x-2.5 min-w-0">
                        <div class="w-9 h-9 rounded-xl bg-[#0B2B25]/5 border border-[#0B2B25]/10 text-[#0B2B25] font-space font-bold text-xs flex items-center justify-center shrink-0">
                            {{ $initial }}
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-bold text-slate-900 text-xs truncate leading-snug">{{ $studentName }}</h4>
                            <p class="text-[10px] text-slate-500 font-mono truncate mt-0.5">{{ $companyName }}</p>
                        </div>
                    </div>
                    
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border shrink-0 {{ $badge['class'] }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $badge['dot'] }}"></span>
                        <span>{{ $badge['label'] }}</span>
                    </span>
                </div>

                <!-- Middle: Time Check-in & Check-out Grid -->
                <div class="grid grid-cols-2 gap-2 text-[11px] bg-slate-50/80 rounded-xl p-2.5 border border-slate-100">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[9.5px] font-bold text-slate-400 uppercase tracking-wider">In:</span>
                        <span class="font-mono font-bold text-slate-800">{{ $a->checkin_time ? substr($a->checkin_time, 0, 5) . ' WIB' : '—' }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 justify-end">
                        <span class="text-[9.5px] font-bold text-slate-400 uppercase tracking-wider">Out:</span>
                        <span class="font-mono font-bold text-slate-800">{{ $a->checkout_time ? substr($a->checkout_time, 0, 5) . ' WIB' : '—' }}</span>
                    </div>
                </div>

                <!-- Anomaly Badge: Di Luar Radius -->
                @if($a->is_within_radius === false)
                    <div class="flex items-center gap-1.5 text-[10.5px] font-bold text-rose-700 bg-rose-50 px-2.5 py-1.5 rounded-xl border border-rose-200">
                        <svg class="w-3.5 h-3.5 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Terdeteksi di luar radius kantor mitra DU/DI</span>
                    </div>
                @endif

                <!-- Footer: Date + Actions (Photo Lightbox & Detail Modal) -->
                <div class="flex items-center justify-between pt-1 border-t border-slate-100">
                    <span class="text-[10px] text-slate-400 font-mono">
                        {{ \Carbon\Carbon::parse($a->date)->isoFormat('dddd, D MMM') }}
                    </span>

                    <div class="flex items-center gap-1.5">
                        @if($a->checkin_photo_path)
                            <button type="button" 
                                    onclick="openImageLightbox('{{ $a->checkin_photo_url }}')" 
                                    class="p-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 transition-colors cursor-pointer" 
                                    title="Lihat Swafoto Siswa">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="4"/></svg>
                            </button>
                        @endif

                        <button type="button" 
                                onclick='openOverrideAttendanceModal({{ $a->student_pkl_id }}, "{{ $a->date }}", "{{ $a->status }}", "{{ $a->checkin_time ? substr($a->checkin_time, 0, 5) : '07:30' }}", "{{ $a->checkout_time ? substr($a->checkout_time, 0, 5) : '' }}")' 
                                class="px-2.5 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-800 font-extrabold text-[11px] inline-flex items-center gap-1 transition-all cursor-pointer shadow-xs border border-indigo-200">
                            <span>Override</span>
                        </button>

                        <button type="button" 
                                onclick='showAttendanceDetail(@json($detailPayload))' 
                                class="px-3 py-1.5 rounded-xl bg-[#0B2B25] hover:bg-[#12382F] text-[#C6F26B] font-extrabold text-[11px] inline-flex items-center gap-1 transition-all cursor-pointer shadow-xs">
                            <span>Detail</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <!-- State Kosong yang Elegan & Selaras Tema VokaTrack -->
            <div class="py-10 px-5 bg-white rounded-[24px] border border-slate-200/80 text-center shadow-2xs">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center mx-auto mb-3 shadow-2xs">
                    <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </div>
                <h4 class="font-space font-bold text-sm text-slate-800">Belum Ada Catatan Presensi</h4>
                <p class="text-xs text-slate-500 max-w-[270px] mx-auto mt-1 leading-relaxed">
                    @if(request()->filled('date') || request()->filled('status'))
                        Tidak ditemukan rekaman presensi siswa dengan kriteria filter yang sedang aktif.
                    @else
                        Belum ada rekaman presensi siswa binaan yang tersimpan dalam sistem.
                    @endif
                </p>
                @if(request()->filled('date') || request()->filled('status'))
                    <a href="{{ route('guru.monitoring') }}" class="inline-flex items-center gap-1.5 mt-4 px-4 py-2 rounded-xl bg-[#0B2B25] text-[#C6F26B] font-extrabold text-xs shadow-xs hover:bg-[#12382F] transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Reset Semua Filter</span>
                    </a>
                @endif
            </div>
        @endforelse
    </div>

    <!-- ============================================================== -->
    <!-- 5. MOBILE PAGINATION                                           -->
    <!-- ============================================================== -->
    @if($attendances->hasPages())
        <div class="pt-2">
            <x-pagination :paginator="$attendances" />
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- 6. REUSABLE DETAIL MODAL                                       -->
    <!-- ============================================================== -->
    <x-attendance-detail-modal />

    <!-- ============================================================== -->
    <!-- 7. OVERRIDE ATTENDANCE MODAL                                   -->
    <!-- ============================================================== -->
    @include('teacher.partials.override-attendance-modal')

</div>
