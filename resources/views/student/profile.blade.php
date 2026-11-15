@extends('layouts.student-mobile')

@section('title', 'Profil & Penempatan PKL — VokaTrack Siswa')

@section('content')
<div class="space-y-3.5">

    <!-- 1. Digital Student Identity Card -->
    <div class="relative overflow-hidden rounded-[26px] bg-[#0B2B25] text-white shadow-[0_12px_30px_rgba(11,43,37,0.18)]">
        <!-- Animated decorative rings -->
        <svg viewBox="0 0 340 220" preserveAspectRatio="xMaxYMin slice" aria-hidden="true" class="hdr-rings absolute inset-0 w-full h-full pointer-events-none">
            <g class="hdr-ring-group hdr-ring-group--a" style="transform-origin:310px 30px">
                <circle cx="310" cy="30" r="50" fill="none" stroke="#1C4A3E" stroke-width="2"/>
                <circle cx="310" cy="30" r="90" fill="none" stroke="#1C4A3E" stroke-width="2"/>
                <circle cx="310" cy="30" r="130" fill="none" stroke="#1C4A3E" stroke-width="2" stroke-dasharray="6 10"/>
                <circle class="hdr-ring-wave" cx="310" cy="30" r="50" fill="none" stroke="#C6F26B" stroke-width="1.5"/>
            </g>
        </svg>

        <div class="relative p-5">
            <!-- Card label row -->
            <div class="flex items-center justify-between gap-2">
                <span class="inline-flex items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-[0.12em] text-[#C6F26B]">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#C6F26B] animate-pulse"></span>
                    Kartu Siswa PKL
                </span>
                <span class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-white/10 border border-white/15 text-white/90 truncate max-w-[55%]">
                    {{ auth()->user()->school->name ?? 'SMK Indonesia' }}
                </span>
            </div>

            <!-- Identity row: avatar + name -->
            <div class="flex items-center gap-4 mt-4">
                <div class="relative shrink-0">
                    <div class="w-16 h-16 rounded-[20px] bg-[#C6F26B] text-[#0B2B25] font-space font-bold text-2xl flex items-center justify-center select-none shadow-md">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <span class="absolute -bottom-1 -right-1 flex h-4 w-4" title="Siswa Aktif">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-4 w-4 bg-emerald-500 border-2 border-[#0B2B25]"></span>
                    </span>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 class="font-space text-lg font-bold leading-tight tracking-tight truncate">{{ auth()->user()->name }}</h2>
                    <p class="text-xs text-[#A9C2B9] font-medium mt-0.5 truncate">{{ auth()->user()->email }}</p>
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-[#C6F26B] text-[#0B2B25]">
                            Kelas {{ $student->class->name ?? '-' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Major -->
            <div class="flex items-center gap-2 mt-4 px-3 py-2 rounded-2xl bg-white/[0.07] border border-white/10">
                <svg class="w-4 h-4 text-[#C6F26B] shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                <span class="text-xs font-semibold text-white/90 truncate">{{ $student->class->major->name ?? '-' }}</span>
            </div>

            <!-- NISN & NIS -->
            <div class="grid grid-cols-2 gap-2 mt-2">
                <div class="rounded-2xl bg-white/[0.07] border border-white/10 px-3 py-2">
                    <span class="text-[10px] font-bold text-[#A9C2B9] uppercase tracking-wider block">NISN</span>
                    <span class="text-sm font-bold text-white font-mono tracking-wide block mt-0.5">{{ $student->nisn ?? '-' }}</span>
                </div>
                <div class="rounded-2xl bg-white/[0.07] border border-white/10 px-3 py-2">
                    <span class="text-[10px] font-bold text-[#A9C2B9] uppercase tracking-wider block">NIS</span>
                    <span class="text-sm font-bold text-white font-mono tracking-wide block mt-0.5">{{ $student->nis ?? '-' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Reusable look: white card, 24px radius, soft border, section header (icon tile + title) ===== --}}

    @php
        // Tab default menyesuaikan fase PKL: nilai/sertifikat sudah terbit → buka tab Nilai.
        $profileTabs = [
            'penempatan' => 'Penempatan',
            'nilai'      => 'Nilai',
            'akun'       => 'Akun',
        ];
        $defaultTab = ($assessment || (isset($certificate) && $certificate)) ? 'nilai' : 'penempatan';
    @endphp

    <!-- Tab bar -->
    <div>
        <div role="tablist" aria-label="Bagian profil" data-default-tab="{{ $defaultTab }}" id="profile-tablist"
             class="relative grid grid-cols-3 p-1 rounded-full bg-white border border-[#E3ECE8]">
            <span id="profile-tab-indicator" aria-hidden="true"
                  class="absolute top-1 bottom-1 left-1 rounded-full bg-[#0B2B25] transition-transform duration-300 ease-out"
                  style="width: calc((100% - 0.5rem) / 3)"></span>
            @foreach($profileTabs as $key => $label)
                <button type="button" role="tab" id="tab-btn-{{ $key }}" data-tab="{{ $key }}"
                        aria-controls="tab-{{ $key }}" aria-selected="false" tabindex="-1"
                        class="profile-tab relative z-10 h-9 rounded-full text-[13px] font-bold text-[#5A6B65] transition-colors cursor-pointer aria-selected:text-white">
                    {{ $label }}
                    @if($key === 'nilai' && $assessment)
                        <span class="ml-1 inline-block w-1.5 h-1.5 rounded-full bg-[#C6F26B] align-middle"></span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    <!-- ===== TAB: Penempatan ===== -->
    <div role="tabpanel" id="tab-penempatan" aria-labelledby="tab-btn-penempatan" data-tab-panel="penempatan" class="space-y-3.5" @if($defaultTab !== 'penempatan') hidden @endif>

    <!-- 2. Penempatan PKL -->
    @if($spkl)
        @php
            $statusMap = [
                'completed' => ['PKL Selesai', 'bg-[#DDF3EC] text-[#0B5E4E]'],
                'active'    => ['Aktif', 'bg-[#DDF3EC] text-[#0B5E4E]'],
                'assigned'  => ['Menunggu Mulai', 'bg-[#FFF1D6] text-[#7A4B00]'],
            ];
            [$statusLabel, $statusClass] = $statusMap[$spkl->status] ?? [ucfirst($spkl->status), 'bg-slate-100 text-slate-600'];
            $mentorName  = $spkl->supervisor->user->name ?? 'Belum ditentukan';
            $teacherName = $spkl->teacher->user->name ?? 'Belum ditentukan';
            $initialsOf  = fn ($n) => collect(preg_split('/\s+/', trim($n)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
        @endphp

        <section class="bg-white rounded-[24px] border border-[#E3ECE8] p-4">
            <header class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-xl bg-[#DDF3EC] text-[#0B5E4E] flex items-center justify-center shrink-0">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </span>
                <h3 class="flex-1 min-w-0 font-space font-bold text-[15px] text-[#10211D] truncate">Informasi Penempatan PKL</h3>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold whitespace-nowrap {{ $statusClass }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>{{ $statusLabel }}
                </span>
            </header>

            <!-- Perusahaan -->
            <div class="mt-4 rounded-2xl bg-[#F4F8F6] p-3.5">
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#5A6B65]">Perusahaan mitra</span>
                <h4 class="font-bold text-[15px] leading-snug text-[#10211D] mt-1">{{ $spkl->company->name ?? 'Perusahaan Mitra' }}</h4>
                @if(!empty($spkl->company->address))
                    <p class="flex items-start gap-1.5 text-xs text-[#5A6B65] leading-relaxed mt-1.5">
                        <svg class="w-3.5 h-3.5 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"/><circle cx="12" cy="10" r="3"/></svg>
                        <span>{{ $spkl->company->address }}</span>
                    </p>
                @endif
            </div>

            <!-- Pembimbing -->
            <ul class="mt-3 divide-y divide-[#EEF3F1]">
                <li class="flex items-center gap-3 py-3">
                    <span class="w-10 h-10 rounded-full bg-[#FFF1D6] text-[#7A4B00] font-bold text-xs flex items-center justify-center shrink-0">{{ $initialsOf($mentorName) ?: '?' }}</span>
                    <div class="min-w-0 flex-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#7A4B00]">Mentor DU/DI</span>
                        <p class="text-[13.5px] font-bold text-[#10211D] leading-snug truncate">{{ $mentorName }}</p>
                        <p class="text-[11.5px] text-[#5A6B65] leading-snug truncate">{{ $spkl->supervisor->position ?? 'Pembimbing Industri' }}</p>
                    </div>
                </li>
                <li class="flex items-center gap-3 py-3">
                    <span class="w-10 h-10 rounded-full bg-[#DDF3EC] text-[#0B5E4E] font-bold text-xs flex items-center justify-center shrink-0">{{ $initialsOf($teacherName) ?: '?' }}</span>
                    <div class="min-w-0 flex-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#0B5E4E]">Guru pembimbing</span>
                        <p class="text-[13.5px] font-bold text-[#10211D] leading-snug truncate">{{ $teacherName }}</p>
                        <p class="text-[11.5px] text-[#5A6B65] leading-snug truncate font-mono">NIP {{ $spkl->teacher->nip ?? '-' }}</p>
                    </div>
                </li>
            </ul>

            <!-- Periode -->
            <div class="flex items-center gap-3 pt-3 border-t border-[#EEF3F1]">
                <span class="w-10 h-10 rounded-full bg-[#F4F8F6] text-[#0B2B25] flex items-center justify-center shrink-0">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/></svg>
                </span>
                <div class="min-w-0 flex-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#5A6B65]">Periode pelaksanaan</span>
                    <p class="text-[13px] font-bold text-[#10211D] leading-snug">
                        @if($spkl->pklPeriod)
                            {{ \Carbon\Carbon::parse($spkl->pklPeriod->start_date)->isoFormat('D MMM Y') }} – {{ \Carbon\Carbon::parse($spkl->pklPeriod->end_date)->isoFormat('D MMM Y') }}
                        @else
                            -
                        @endif
                    </p>
                    @if($spkl->pklPeriod)
                        <p class="text-[11.5px] text-[#5A6B65] leading-snug">{{ $spkl->pklPeriod->name }}</p>
                    @endif
                </div>
            </div>
        </section>
    @else
        <section class="bg-white rounded-[24px] border border-[#E3ECE8] p-4">
            <header class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-xl bg-[#FFF1D6] text-[#7A4B00] flex items-center justify-center shrink-0">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 6v6l4 2"/></svg>
                </span>
                <h3 class="flex-1 min-w-0 font-space font-bold text-[15px] text-[#10211D] truncate">Informasi Penempatan PKL</h3>
                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 whitespace-nowrap">Belum Ditempatkan</span>
            </header>
            <p class="mt-3 text-xs leading-relaxed text-[#5A6B65]">
                Akun Anda belum dipetakan ke perusahaan mitra DU/DI. Silakan hubungi Ketua Program Keahlian atau Admin BKK/Hubin sekolah.
            </p>
        </section>
    @endif

    </div>

    <!-- ===== TAB: Nilai ===== -->
    <div role="tabpanel" id="tab-nilai" aria-labelledby="tab-btn-nilai" data-tab-panel="nilai" class="space-y-3.5" @if($defaultTab !== 'nilai') hidden @endif>

    <!-- 3. Nilai Akhir PKL -->
    @if($assessment)
        @php
            $finalScore = (float) $assessment->final_score;
            [$grade, $gradeText] = match (true) {
                $finalScore >= 90 => ['A', 'Sangat memuaskan'],
                $finalScore >= 80 => ['B', 'Memuaskan'],
                $finalScore >= 70 => ['C', 'Cukup'],
                default => ['D', 'Perlu bimbingan'],
            };
            $ringC = 2 * M_PI * 34;
            $ringOffset = $ringC * (1 - min(100, max(0, $finalScore)) / 100);
            $scoreGroups = [
                ['DU/DI', '60%', 'bg-[#E0A21B]', [
                    ['Kedisiplinan & etos', $assessment->score_dudi_discipline],
                    ['Kompetensi teknis', $assessment->score_dudi_technical],
                    ['Inisiatif & tanggung jawab', $assessment->score_dudi_initiative],
                ]],
                ['Sekolah', '40%', 'bg-[#0F7A66]', [
                    ['Laporan & jurnal PKL', $assessment->score_teacher_report],
                ]],
            ];
        @endphp

        <section class="bg-white rounded-[24px] border border-[#E3ECE8] p-4">
            <header class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-xl bg-[#FFF1D6] text-[#7A4B00] flex items-center justify-center shrink-0">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                </span>
                <h3 class="flex-1 min-w-0 font-space font-bold text-[15px] text-[#10211D] truncate">Nilai Akhir PKL</h3>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#DDF3EC] text-[#0B5E4E] whitespace-nowrap">
                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>Final
                </span>
            </header>

            <!-- Skor utama -->
            <div class="mt-4 flex items-center gap-4 rounded-2xl bg-[#0B2B25] text-white p-4">
                <div class="relative w-[84px] h-[84px] shrink-0">
                    <svg viewBox="0 0 80 80" class="w-full h-full -rotate-90">
                        <circle cx="40" cy="40" r="34" fill="none" stroke="rgba(255,255,255,.12)" stroke-width="7"/>
                        <circle cx="40" cy="40" r="34" fill="none" stroke="#C6F26B" stroke-width="7" stroke-linecap="round"
                                stroke-dasharray="{{ $ringC }}" stroke-dashoffset="{{ $ringOffset }}"/>
                    </svg>
                    <span class="absolute inset-0 flex items-center justify-center font-space font-bold text-[22px] tabular-nums">{{ number_format($finalScore, 1) }}</span>
                </div>
                <div class="min-w-0">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#A9C2B9]">Predikat</span>
                    <div class="flex items-baseline gap-2 mt-0.5">
                        <span class="font-space font-bold text-[32px] leading-none text-[#C6F26B]">{{ $grade }}</span>
                        <span class="text-sm font-bold leading-tight">{{ $gradeText }}</span>
                    </div>
                    <p class="text-[11px] text-[#A9C2B9] leading-snug mt-1.5">60% DU/DI + 40% sekolah</p>
                </div>
            </div>

            <!-- Toggle rincian (ringkas dulu, detail saat diminta) -->
            <button type="button" id="score-detail-toggle" aria-expanded="false" aria-controls="score-detail"
                    class="mt-3 w-full h-10 rounded-full bg-[#F4F8F6] hover:bg-[#EEF3F1] text-[12.5px] font-bold text-[#0B2B25] flex items-center justify-center gap-1.5 transition-colors cursor-pointer">
                <span data-label>Lihat rincian nilai</span>
                <svg class="w-4 h-4 transition-transform duration-300" data-chevron fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
            </button>

            <div id="score-detail" class="grid grid-rows-[0fr] transition-[grid-template-rows] duration-300 ease-out">
            <div class="overflow-hidden">

            <!-- Rincian -->
            <div class="mt-4 space-y-4">
                @foreach($scoreGroups as [$groupName, $weight, $barClass, $items])
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-[#5A6B65]">{{ $groupName }}</span>
                            <span class="text-[11px] font-bold text-[#5A6B65]">Bobot {{ $weight }}</span>
                        </div>
                        <ul class="space-y-2.5">
                            @foreach($items as [$label, $val])
                                @php $v = (float) $val; @endphp
                                <li>
                                    <div class="flex items-center justify-between text-[12.5px]">
                                        <span class="text-[#36504A] font-medium">{{ $label }}</span>
                                        <span class="font-bold text-[#10211D] tabular-nums">{{ number_format($v, 0) }}</span>
                                    </div>
                                    <div class="h-1.5 rounded-full bg-[#EEF3F1] mt-1 overflow-hidden">
                                        <div class="h-full rounded-full {{ $barClass }}" style="width: {{ min(100, max(0, $v)) }}%"></div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>

            <!-- Catatan pembimbing -->
            @if($assessment->notes_dudi || $assessment->notes_teacher)
                <div class="mt-4 pt-4 border-t border-[#EEF3F1] space-y-3">
                    @foreach([['Catatan mentor DU/DI', $assessment->notes_dudi, 'border-[#E0A21B]'], ['Catatan guru pembimbing', $assessment->notes_teacher, 'border-[#0F7A66]']] as [$noteLabel, $noteText, $noteBorder])
                        @if($noteText)
                            <figure class="pl-3 border-l-[3px] {{ $noteBorder }}">
                                <figcaption class="text-[10px] font-bold uppercase tracking-wider text-[#5A6B65]">{{ $noteLabel }}</figcaption>
                                <blockquote class="text-[12.5px] leading-relaxed text-[#36504A] mt-1">“{{ $noteText }}”</blockquote>
                            </figure>
                        @endif
                    @endforeach
                </div>
            @endif

            </div>
            </div>
        </section>
    @else
        <section class="bg-white rounded-[24px] border border-[#E3ECE8] p-4 flex items-center gap-3">
            <span class="w-9 h-9 rounded-xl bg-[#F4F8F6] text-[#5A6B65] flex items-center justify-center shrink-0">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 6v6l4 2"/></svg>
            </span>
            <div class="min-w-0">
                <h3 class="font-space font-bold text-[15px] text-[#10211D]">Nilai belum terbit</h3>
                <p class="text-xs text-[#5A6B65] leading-relaxed">Muncul setelah dinilai mentor DU/DI dan guru pembimbing.</p>
            </div>
        </section>
    @endif

    <!-- 4. E-Sertifikat -->
    @if(isset($certificate) && $certificate)
        <section class="relative overflow-hidden rounded-[24px] bg-[#0B2B25] text-white p-4">
            <div class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-xl bg-[#C6F26B] text-[#0B2B25] flex items-center justify-center shrink-0">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><circle cx="12" cy="8" r="6"/><path stroke-linecap="round" stroke-linejoin="round" d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
                </span>
                <div class="flex-1 min-w-0">
                    <h3 class="font-space font-bold text-[15px] truncate">E-Sertifikat PKL</h3>
                    <p class="text-[11px] text-[#A9C2B9] truncate font-mono">{{ $certificate->certificate_number }}</p>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-white/10 text-[#C6F26B] whitespace-nowrap">{{ $certificate->final_predicate }}</span>
            </div>
            <div class="grid grid-cols-2 gap-2 mt-4">
                <div class="rounded-2xl bg-white/[0.07] border border-white/10 px-3 py-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#A9C2B9]">Skor akhir</span>
                    <p class="text-sm font-bold tabular-nums">{{ number_format($certificate->final_score, 1) }} / 100</p>
                </div>
                <div class="rounded-2xl bg-white/[0.07] border border-white/10 px-3 py-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#A9C2B9]">Terbit</span>
                    <p class="text-sm font-bold">{{ $certificate->issued_date ? $certificate->issued_date->translatedFormat('d M Y') : '-' }}</p>
                </div>
            </div>
            <div class="flex gap-2 mt-3">
                <a href="{{ route('student.certificate.download') }}" class="flex-1 h-11 rounded-[22px] bg-[#C6F26B] text-[#0B2B25] font-bold text-xs flex items-center justify-center gap-1.5 active:scale-[0.98] transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0-4-4m4 4 4-4M5 21h14"/></svg>
                    Unduh PDF
                </a>
                <a href="{{ $certificate->verification_url }}" target="_blank" rel="noopener" class="h-11 px-4 rounded-[22px] bg-white/10 border border-white/15 font-bold text-xs flex items-center justify-center gap-1.5 active:scale-[0.98] transition-all">
                    Validasi QR
                </a>
            </div>
        </section>
    @endif

    </div>

    <!-- ===== TAB: Akun ===== -->
    <div role="tabpanel" id="tab-akun" aria-labelledby="tab-btn-akun" data-tab-panel="akun" class="space-y-3.5" hidden>

    <!-- 5. Portal Orang Tua -->
    @php
        $monitorUrl = $student->parent_monitoring_url ?? route('parent.monitor', ['token' => $student->parent_token ?? '']);
        $waMessage = rawurlencode("Halo Ayah/Bunda, ini link khusus untuk memantau kehadiran & jurnal kegiatan PKL saya di VokaTrack tanpa perlu login:\n" . $monitorUrl);
    @endphp
    <section class="bg-white rounded-[24px] border border-[#E3ECE8] p-4">
        <header class="flex items-center gap-3">
            <span class="w-9 h-9 rounded-xl bg-[#DDF3EC] text-[#0B5E4E] flex items-center justify-center shrink-0">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </span>
            <div class="flex-1 min-w-0">
                <h3 class="font-space font-bold text-[15px] text-[#10211D] truncate">Portal Orang Tua</h3>
                <p class="text-[11.5px] text-[#5A6B65] truncate">Pantau kehadiran & jurnal tanpa login</p>
            </div>
        </header>

        <div class="mt-3.5 flex items-center gap-2 rounded-2xl bg-[#F4F8F6] border border-[#E3ECE8] p-1.5 pl-3.5">
            <input type="text" id="parent-portal-link" readonly value="{{ $monitorUrl }}" aria-label="Tautan portal orang tua"
                   class="flex-1 min-w-0 bg-transparent text-xs text-[#36504A] font-mono truncate focus:outline-none">
            <button type="button" onclick="copyParentPortalLink()" class="shrink-0 h-9 px-3.5 rounded-xl bg-[#0B2B25] text-white font-bold text-xs flex items-center gap-1.5 active:scale-95 transition-all cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                <span id="copy-text">Salin</span>
            </button>
        </div>

        <div class="flex gap-2 mt-2.5">
            <a href="https://wa.me/?text={{ $waMessage }}" target="_blank" rel="noopener" class="flex-1 h-11 rounded-[22px] bg-[#1F9D55] hover:bg-[#188548] text-white font-bold text-xs flex items-center justify-center gap-2 active:scale-[0.98] transition-all">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                Kirim via WhatsApp
            </a>
            <form action="{{ route('student.parent-token.regenerate') }}" method="POST" data-confirm="Tautan lama yang sudah dibagikan ke orang tua tidak akan bisa dipakai lagi." data-confirm-title="Buat ulang tautan monitoring?" data-confirm-ok="Ya, buat ulang">
                @csrf
                <button type="submit" title="Buat ulang tautan" aria-label="Buat ulang tautan" class="h-11 w-11 rounded-full bg-[#F4F8F6] border border-[#E3ECE8] text-[#36504A] flex items-center justify-center active:scale-95 transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-3-6.7L21 8M21 3v5h-5"/></svg>
                </button>
            </form>
        </div>
    </section>

    <!-- 6. Laporan kendala & akun (menu list) -->
    <section class="bg-white rounded-[24px] border border-[#E3ECE8] overflow-hidden divide-y divide-[#EEF3F1]">
        <button type="button" onclick="document.getElementById('modal-incident-report').classList.remove('hidden')" class="w-full flex items-center gap-3 p-4 text-left hover:bg-[#F8FBF9] transition-colors cursor-pointer">
            <span class="w-9 h-9 rounded-xl bg-[#FBE4E1] text-[#B42318] flex items-center justify-center shrink-0">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4M12 17h.01"/><path stroke-linecap="round" stroke-linejoin="round" d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
            </span>
            <span class="flex-1 min-w-0">
                <span class="block text-[13.5px] font-bold text-[#10211D]">Buat laporan kendala</span>
                <span class="block text-[11.5px] text-[#5A6B65] truncate">Rahasia, hanya untuk guru & sekolah</span>
            </span>
            <svg class="w-4 h-4 text-[#9AABA5] shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
        <a href="{{ route('student.incidents') }}" class="flex items-center gap-3 p-4 hover:bg-[#F8FBF9] transition-colors">
            <span class="w-9 h-9 rounded-xl bg-[#F4F8F6] text-[#36504A] flex items-center justify-center shrink-0">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 14l4-4 4 4 5-5"/></svg>
            </span>
            <span class="flex-1 min-w-0">
                <span class="block text-[13.5px] font-bold text-[#10211D]">Riwayat laporan & SOS</span>
                <span class="block text-[11.5px] text-[#5A6B65] truncate">Pantau status tindak lanjut</span>
            </span>
            <svg class="w-4 h-4 text-[#9AABA5] shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
        <button type="button" onclick="openChangePasswordModal()" class="w-full flex items-center gap-3 p-4 text-left hover:bg-[#F8FBF9] transition-colors cursor-pointer">
            <span class="w-9 h-9 rounded-xl bg-[#DDF3EC] text-[#0B5E4E] flex items-center justify-center shrink-0">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path stroke-linecap="round" d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </span>
            <span class="flex-1 min-w-0">
                <span class="block text-[13.5px] font-bold text-[#10211D]">Ubah kata sandi</span>
                <span class="block text-[11.5px] text-[#5A6B65] truncate">Jaga keamanan akun siswa</span>
            </span>
            <svg class="w-4 h-4 text-[#9AABA5] shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
        <form action="{{ route('logout') }}" method="POST" data-confirm="Anda perlu masuk kembali untuk presensi dan mengisi jurnal." data-confirm-title="Keluar dari akun Siswa?" data-confirm-ok="Ya, keluar" data-confirm-tone="logout">
            @csrf
            <button type="submit" class="w-full flex items-center gap-3 p-4 text-left hover:bg-[#FDF5F4] transition-colors cursor-pointer">
                <span class="w-9 h-9 rounded-xl bg-[#FBE4E1] text-[#B42318] flex items-center justify-center shrink-0">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path stroke-linecap="round" stroke-linejoin="round" d="m16 17 5-5-5-5M21 12H9"/></svg>
                </span>
                <span class="flex-1 text-[13.5px] font-bold text-[#B42318]">Keluar dari akun</span>
            </button>
        </form>
    </section>

    </div>

    <p class="text-[10.5px] text-[#9AABA5] text-center font-medium pt-1">VokaTrack · Sistem Monitoring PKL Vokasi</p>

</div>
@endsection

@push('scripts')
<script>
    /* ---------- Tab profil (Penempatan / Nilai / Akun) ---------- */
    (function () {
        const list = document.getElementById('profile-tablist');
        if (!list) return;
        const tabs = Array.from(list.querySelectorAll('[role="tab"]'));
        const keys = tabs.map(t => t.dataset.tab);
        const indicator = document.getElementById('profile-tab-indicator');

        function activate(key, { focus = false, updateHash = true } = {}) {
            if (!keys.includes(key)) key = list.dataset.defaultTab;
            tabs.forEach((t, i) => {
                const on = t.dataset.tab === key;
                t.setAttribute('aria-selected', on ? 'true' : 'false');
                t.tabIndex = on ? 0 : -1;
                if (on) {
                    indicator.style.transform = `translateX(${i * 100}%)`;
                    if (focus) t.focus();
                }
            });
            document.querySelectorAll('[data-tab-panel]').forEach(p => { p.hidden = p.dataset.tabPanel !== key; });
            if (updateHash) history.replaceState(null, '', '#' + key);
        }

        tabs.forEach((t, i) => {
            t.addEventListener('click', () => {
                activate(t.dataset.tab);
                const top = list.getBoundingClientRect().top + window.scrollY - 76;
                if (window.scrollY > top) window.scrollTo({ top, behavior: 'smooth' });
            });
            t.addEventListener('keydown', (e) => {
                if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
                e.preventDefault();
                const next = (i + (e.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
                activate(tabs[next].dataset.tab, { focus: true });
            });
        });

        const fromHash = location.hash.replace('#', '');
        indicator.style.transition = 'none';   // posisi awal tanpa animasi geser
        activate(keys.includes(fromHash) ? fromHash : list.dataset.defaultTab, { updateHash: false });
        requestAnimationFrame(() => requestAnimationFrame(() => { indicator.style.transition = ''; }));
        window.addEventListener('hashchange', () => activate(location.hash.replace('#', ''), { updateHash: false }));
    })();

    /* ---------- Rincian nilai (expand/collapse) ---------- */
    (function () {
        const btn = document.getElementById('score-detail-toggle');
        const panel = document.getElementById('score-detail');
        if (!btn || !panel) return;
        btn.addEventListener('click', () => {
            const open = btn.getAttribute('aria-expanded') !== 'true';
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            panel.classList.toggle('grid-rows-[0fr]', !open);
            panel.classList.toggle('grid-rows-[1fr]', open);
            btn.querySelector('[data-label]').textContent = open ? 'Sembunyikan rincian' : 'Lihat rincian nilai';
            btn.querySelector('[data-chevron]').classList.toggle('rotate-180', open);
        });
    })();
</script>
<script>
    function copyParentPortalLink() {
        const input = document.getElementById('parent-portal-link');
        const copyText = document.getElementById('copy-text');
        if (!input) return;

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(input.value).then(() => {
                if (copyText) {
                    copyText.innerText = 'Tersalin!';
                    setTimeout(() => { copyText.innerText = 'Salin'; }, 2000);
                }
            }).catch(() => {
                fallbackCopy(input, copyText);
            });
        } else {
            fallbackCopy(input, copyText);
        }
    }

    function fallbackCopy(input, copyText) {
        input.select();
        input.setSelectionRange(0, 99999);
        document.execCommand('copy');
        if (copyText) {
            copyText.innerText = 'Tersalin!';
            setTimeout(() => { copyText.innerText = 'Salin'; }, 2000);
        }
    }
</script>
@endpush
