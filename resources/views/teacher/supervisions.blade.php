@extends(\App\Services\DeviceService::teacherLayout())

@section('page_title', 'Supervisi & Berita Acara Kunjungan')
@section('page_subtitle', 'Dokumentasi resmi kunjungan monitoring lapangan dan pencetakan Berita Acara standar Ditjen Vokasi')

@section('content')
<div class="w-full space-y-6">

    <!-- Enterprise Hero Banner -->
    <x-page-header
        title="Supervisi & Berita Acara Kunjungan"
        subtitle="Dokumentasi resmi kunjungan 2x monitoring lapangan, tugas monitoring lintas pembimbing, dan pencetakan Berita Acara."
        badge="SUPERVISI LAPANGAN"
    >
        <x-button type="button" variant="primary" size="sm" onclick="openCreateVisitModal()">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span>Catat Kunjungan Lapangan</span>
        </x-button>
    </x-page-header>

    @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-4 text-xs space-y-1">
            <div class="font-bold">Gagal menyimpan laporan kunjungan:</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- TUGAS MONITORING LAPANGAN YANG DITUGASKAN -->
    @if($pendingAssignedTargets->isNotEmpty())
        <div class="bg-white rounded-3xl border border-indigo-200/80 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-indigo-100 bg-indigo-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h2 class="font-black text-slate-900 text-sm sm:text-base flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                        Tugas Monitoring Lapangan Aktif ({{ $pendingAssignedTargets->count() }} Perusahaan)
                    </h2>
                    <p class="text-xs text-slate-600 mt-0.5">
                        Anda mendapat akses <strong>read-only</strong> presensi, jurnal, dan kendala siswa berikut hingga laporan kunjungan disubmit.
                    </p>
                </div>
            </div>

            <div class="divide-y divide-slate-100">
                @foreach($pendingAssignedTargets as $target)
                    @php
                        $assign = $target['assignment'];
                        $comp = $target['company'];
                        $period = $target['pkl_period'];
                    @endphp
                    <div class="p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4 hover:bg-slate-50/60 transition-colors">
                        <div class="space-y-2 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border {{ $target['stage_badge_class'] }}">
                                    {{ $target['stage_label'] }}
                                </span>
                                @if($assign->assignment_number)
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        ST: {{ $assign->assignment_number }}
                                    </span>
                                @endif
                                @if($assign->scheduled_date)
                                    <span class="text-xs font-semibold text-amber-700">
                                        Target: {{ $assign->scheduled_date->translatedFormat('d M Y') }}
                                    </span>
                                @endif
                                <span class="text-xs text-slate-500">&bull; {{ $period->name ?? '-' }}</span>
                            </div>

                            <div>
                                <h3 class="text-sm sm:text-base font-black text-slate-900">{{ $comp->name }}</h3>
                                <p class="text-xs text-slate-500">{{ $comp->address ?? '-' }}</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="text-[11px] font-bold text-slate-400">Siswa ({{ $target['total_count'] }}):</span>
                                @foreach($target['students'] as $spkl)
                                    <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 text-[11px] font-semibold">
                                        {{ $spkl->student->user->name ?? '-' }}
                                        <span class="text-slate-400 font-normal">(Pemb: {{ $spkl->teacher->user->name ?? '-' }})</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <div class="shrink-0 flex flex-wrap items-center gap-2">
                            <a href="{{ route('guru.supervisions.briefing', [$assign->id, $comp->id]) }}"
                               class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition-colors">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <span>Briefing Siswa (Read-Only)</span>
                            </a>
                            <button type="button"
                                    onclick="openCreateVisitModal({{ $comp->id }}, {{ $period->id }}, '{{ $target['stage'] }}', {{ $assign->id }})"
                                    class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-xs transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Catat Laporan &amp; Selesaikan</span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- STATS CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <x-stat-card
            title="Tugas Menunggu"
            :value="$stats['pending_tasks']"
            subtitle="Penugasan monitoring aktif"
            :color="$stats['pending_tasks'] > 0 ? 'amber' : 'emerald'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card
            title="Total Kunjungan"
            :value="$stats['total']"
            subtitle="Sebagai petugas / pembimbing"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card
            title="Kunjungan Bulan Ini"
            :value="$stats['this_month']"
            subtitle="Periode bulan berjalan"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card
            title="Mitra DU/DI Terkait"
            :value="$stats['companies_count']"
            subtitle="Bimbingan & penugasan"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- FILTER & RIWAYAT KUNJUNGAN -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="font-black text-slate-900 text-sm sm:text-base flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <span>Riwayat Supervisi & Berita Acara</span>
            </div>
            <form method="GET" action="{{ route('guru.supervisions') }}" class="flex flex-wrap items-center gap-3">
                <select name="role_scope" onchange="this.form.submit()" class="text-xs font-semibold rounded-xl border-slate-200 bg-white shadow-2xs focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua Peran</option>
                    <option value="visitor" {{ request('role_scope') === 'visitor' ? 'selected' : '' }}>Sebagai Petugas Lapangan</option>
                    <option value="advisor" {{ request('role_scope') === 'advisor' ? 'selected' : '' }}>Sebagai Pembimbing Siswa</option>
                </select>

                <select name="company_id" onchange="this.form.submit()" class="text-xs font-semibold rounded-xl border-slate-200 bg-white shadow-2xs focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua DU/DI</option>
                    @foreach ($filterCompanies as $c)
                        <option value="{{ $c->id }}" {{ request('company_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>

                <select name="stage" onchange="this.form.submit()" class="text-xs font-semibold rounded-xl border-slate-200 bg-white shadow-2xs focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua Tahapan</option>
                    <option value="monitoring_1" {{ request('stage') == 'monitoring_1' ? 'selected' : '' }}>Monitoring 1 (Bulan Ke-1)</option>
                    <option value="monitoring_2" {{ request('stage') == 'monitoring_2' ? 'selected' : '' }}>Monitoring 2 (Pertengahan)</option>
                    <option value="insidental" {{ request('stage') == 'insidental' ? 'selected' : '' }}>Kunjungan Insidental</option>
                </select>

                @if(request()->hasAny(['company_id', 'stage', 'role_scope']))
                    <a href="{{ route('guru.supervisions') }}" class="text-xs font-bold text-slate-500 hover:text-rose-600 transition-colors">Reset</a>
                @endif
            </form>
        </div>

        <x-supervision.visits-list
            :visits="$visits"
            pdfRouteName="guru.supervisions.pdf"
            :currentTeacherId="$teacher->id"
            emptyDescription='Klik tombol "Catat Kunjungan Lapangan" di atas untuk mendokumentasikan monitoring dan mengunduh Berita Acara resmi.'
        />
    </div>
</div>

<!-- MODAL CATAT KUNJUNGAN LAPANGAN -->
<div id="modal-create-visit" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto hidden">
    <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-slate-100 overflow-hidden my-8 animate-scale-up">
        <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-black text-slate-900">Catat Kunjungan Monitoring</h3>
                    <p class="text-xs text-slate-500">Formulir logbook resmi monitoring lapangan dan penerbitan Berita Acara.</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modal-create-visit').classList.add('hidden')" class="p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form action="{{ route('guru.supervisions.store') }}" method="POST" enctype="multipart/form-data" class="p-5 sm:p-6 space-y-4">
            @csrf
            <input type="hidden" name="supervision_assignment_id" id="visit-assignment-id" value="">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Mitra DU/DI <span class="text-rose-500">*</span></label>
                    <select name="company_id" id="visit-company-id" required class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">-- Pilih Tempat PKL --</option>
                        @foreach ($companies as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Periode PKL <span class="text-rose-500">*</span></label>
                    <select name="pkl_period_id" id="visit-period-id" required class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">-- Pilih Periode --</option>
                        @foreach ($periods as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Tahapan Kunjungan <span class="text-rose-500">*</span></label>
                    <select name="stage" id="visit-stage" required class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="monitoring_1" selected>Monitoring 1 (Bulan Ke-1)</option>
                        <option value="monitoring_2">Monitoring 2 (Pertengahan)</option>
                        <option value="insidental">Kunjungan Insidental / Khusus</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Tanggal Kunjungan <span class="text-rose-500">*</span></label>
                    <input type="date" name="visit_date" value="{{ date('Y-m-d') }}" required class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Kemajuan &amp; Kedisiplinan Siswa <span class="text-rose-500">*</span></label>
                <textarea name="student_progress_summary" rows="3" required placeholder="Jelaskan adaptasi kerja siswa, kehadiran, kepatuhan SOP perusahaan, dan progres tugas..." class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 placeholder-slate-400"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Masukan &amp; Evaluasi dari DU/DI <span class="text-rose-500">*</span></label>
                <textarea name="company_feedback" rows="3" required placeholder="Catatan atau saran dari pembimbing lapangan perusahaan mengenai performa siswa..." class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 placeholder-slate-400"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Kesepakatan / Tindak Lanjut (Opsional)</label>
                <input type="text" name="follow_up_agreement" placeholder="Contoh: Penugasan proyek integrasi PLC atau penyesuaian jam piket..." class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 placeholder-slate-400">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Foto Bersama Siswa</label>
                    <input type="file" name="photo_with_students" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Foto Bersama Pembimbing DU/DI</label>
                    <input type="file" name="photo_with_supervisor" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="document.getElementById('modal-create-visit').classList.add('hidden')" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold text-xs rounded-xl shadow-sm shadow-indigo-200 transition-all">
                    Simpan &amp; Terbitkan Berita Acara
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCreateVisitModal(companyId = '', periodId = '', stage = 'monitoring_1', assignmentId = '') {
        document.getElementById('visit-company-id').value = companyId || '';
        document.getElementById('visit-period-id').value = periodId || '';
        document.getElementById('visit-stage').value = stage || 'monitoring_1';
        document.getElementById('visit-assignment-id').value = assignmentId || '';
        document.getElementById('modal-create-visit').classList.remove('hidden');
    }
</script>
@endsection
