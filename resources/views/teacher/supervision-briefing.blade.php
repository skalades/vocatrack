@extends(\App\Services\DeviceService::teacherLayout())

@section('page_title', 'Briefing Monitoring Siswa (Read-Only)')
@section('page_subtitle', 'Data harian siswa sementara untuk pelaksanaan monitoring di ' . ($briefing['company']->name ?? '-'))

@section('content')
@php
    $assignment = $briefing['assignment'];
    $company = $briefing['company'];
    $studentPkls = $briefing['student_pkls'];
    $attendanceSummary = $briefing['attendance_summary'];
    $recentAttendances = $briefing['recent_attendances'];
    $recentJournals = $briefing['recent_journals'];
    $incidents = $briefing['incidents'];
@endphp

<div class="w-full space-y-6">

    <x-page-header
        title="Briefing Monitoring: {{ $company->name }}"
        subtitle="{{ $company->address ?? '-' }} · {{ $assignment->stage_label }}"
        badge="AKSES READ-ONLY PETUGAS"
    >
        <a href="{{ route('guru.supervisions') }}" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
            &larr; Kembali
        </a>
        <x-button type="button" variant="primary" size="sm" onclick="document.getElementById('modal-briefing-visit').classList.remove('hidden')">
            <span>Catat Laporan &amp; Selesaikan Tugas</span>
        </x-button>
    </x-page-header>

    <!-- Notice Read-Only Lifecycle -->
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-xs text-amber-900 flex items-start gap-3">
        <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <div class="font-bold">Mode Read-Only Petugas Monitoring ({{ $assignment->stage_short_label }})</div>
            <p class="mt-0.5 leading-relaxed">
                Anda hanya dapat melihat ringkasan presensi, jurnal, dan kendala siswa berikut sebagai bahan evaluasi di lapangan.
                Aksi perubahan presensi, review jurnal, dan penilaian tetap menjadi wewenang <strong>Guru Pembimbing Asli</strong>.
                Setelah laporan kunjungan disubmit, akses data harian siswa ini akan <strong>otomatis ditutup</strong>.
            </p>
        </div>
    </div>

    <!-- Info Penugasan & DU/DI -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-white rounded-3xl border border-slate-200/80 p-5 text-xs">
        <div>
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Tahap &amp; Periode</div>
            <div class="font-black text-slate-900 mt-1">{{ $assignment->stage_label }}</div>
            <div class="text-slate-500 mt-0.5">{{ $assignment->pklPeriod->name ?? '-' }}</div>
        </div>
        <div>
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Kontak DU/DI</div>
            <div class="font-bold text-slate-900 mt-1">{{ $company->contact_person ?? '-' }}</div>
            <div class="text-slate-500 mt-0.5">{{ $company->phone ?? '-' }}</div>
        </div>
        <div>
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Surat Tugas &amp; Catatan</div>
            <div class="font-bold text-slate-900 mt-1">{{ $assignment->assignment_number ?: 'Tanpa No. Surat Tugas' }}</div>
            <div class="text-slate-500 mt-0.5">{{ $assignment->notes ?: 'Tidak ada catatan tambahan' }}</div>
        </div>
    </div>

    <!-- Daftar Siswa Read-Only -->
    <div class="space-y-4">
        @foreach($studentPkls as $spkl)
            @php
                $sum = $attendanceSummary->get($spkl->id);
                $attList = $recentAttendances->get($spkl->id, collect());
                $jList = $recentJournals->get($spkl->id, collect());
                $incList = $incidents->get($spkl->id, collect());
            @endphp
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm sm:text-base font-black text-slate-900">{{ $spkl->student->user->name ?? '-' }}</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            NIS: {{ $spkl->student->nis ?? '-' }} &bull;
                            {{ $spkl->student->class->name ?? '-' }} ({{ $spkl->student->class->major->code ?? '-' }}) &bull;
                            Pembimbing Asli: <strong class="text-indigo-700">{{ $spkl->teacher->user->name ?? '-' }}</strong>
                            @if($spkl->teacher?->user?->wa_number)
                                <span class="text-slate-400">({{ $spkl->teacher->user->wa_number }})</span>
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5 text-[11px] font-bold">
                        <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700">Hadir: {{ (int) ($sum->hadir_count ?? 0) }}</span>
                        <span class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700">Telat: {{ (int) ($sum->terlambat_count ?? 0) }}</span>
                        <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700">Izin/Sakit: {{ (int) ($sum->izin_count ?? 0) }}</span>
                        <span class="px-2.5 py-1 rounded-lg bg-rose-50 text-rose-700">Alfa: {{ (int) ($sum->alfa_count ?? 0) }}</span>
                    </div>
                </div>

                <div class="p-5 grid grid-cols-1 lg:grid-cols-3 gap-4 text-xs">
                    <!-- Presensi Terbaru -->
                    <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 space-y-2">
                        <div class="font-extrabold text-slate-700">7 Presensi Terakhir</div>
                        @forelse($attList as $att)
                            <div class="flex items-center justify-between text-[11px] py-1 border-b border-slate-100 last:border-0">
                                <span class="font-medium text-slate-600">{{ \Carbon\Carbon::parse($att->date)->translatedFormat('d M Y') }}</span>
                                <span class="font-bold uppercase {{ $att->status === 'alfa' ? 'text-rose-600' : ($att->status === 'terlambat' ? 'text-amber-600' : 'text-emerald-700') }}">
                                    {{ str_replace('_', ' ', $att->status) }}
                                </span>
                            </div>
                        @empty
                            <p class="text-slate-400 text-[11px]">Belum ada catatan presensi.</p>
                        @endforelse
                    </div>

                    <!-- Jurnal Terbaru -->
                    <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 space-y-2">
                        <div class="font-extrabold text-slate-700">5 Jurnal Kegiatan Terbaru</div>
                        @forelse($jList as $j)
                            <div class="py-1 border-b border-slate-100 last:border-0">
                                <div class="flex items-center justify-between text-[10px] text-slate-400">
                                    <span>{{ \Carbon\Carbon::parse($j->date)->translatedFormat('d M Y') }}</span>
                                    <span class="uppercase font-bold text-slate-600">{{ str_replace('_', ' ', $j->status) }}</span>
                                </div>
                                <p class="text-[11px] text-slate-700 line-clamp-2 mt-0.5">{{ $j->activity_description ?? $j->activities ?? '-' }}</p>
                            </div>
                        @empty
                            <p class="text-slate-400 text-[11px]">Belum ada jurnal terisi.</p>
                        @endforelse
                    </div>

                    <!-- Kendala / SOS -->
                    <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 space-y-2">
                        <div class="font-extrabold text-slate-700">Riwayat Kendala / SOS</div>
                        @forelse($incList->take(4) as $inc)
                            <div class="py-1 border-b border-slate-100 last:border-0">
                                <div class="flex items-center justify-between text-[10px]">
                                    <span class="font-bold uppercase {{ $inc->urgency === 'critical' ? 'text-rose-600' : 'text-amber-700' }}">{{ $inc->urgency }}</span>
                                    <span class="text-slate-400">{{ $inc->status }}</span>
                                </div>
                                <p class="text-[11px] font-semibold text-slate-800 mt-0.5">{{ $inc->subject ?? $inc->category }}</p>
                                <p class="text-[11px] text-slate-600 line-clamp-2">{{ $inc->description }}</p>
                            </div>
                        @empty
                            <p class="text-slate-400 text-[11px]">Tidak ada laporan kendala.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Modal Submit Laporan Kunjungan Langsung dari Briefing -->
<div id="modal-briefing-visit" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto hidden">
    <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-slate-100 overflow-hidden my-8">
        <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div>
                <h3 class="text-base sm:text-lg font-black text-slate-900">Laporkan Kunjungan: {{ $company->name }}</h3>
                <p class="text-xs text-slate-500">{{ $assignment->stage_label }} &bull; Akses read-only ditutup setelah laporan dikirim.</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-briefing-visit').classList.add('hidden')" class="p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('guru.supervisions.store') }}" method="POST" enctype="multipart/form-data" class="p-5 sm:p-6 space-y-4">
            @csrf
            <input type="hidden" name="company_id" value="{{ $company->id }}">
            <input type="hidden" name="pkl_period_id" value="{{ $assignment->pkl_period_id }}">
            <input type="hidden" name="stage" value="{{ $assignment->stage }}">
            <input type="hidden" name="supervision_assignment_id" value="{{ $assignment->id }}">
            @foreach($studentPkls as $spkl)
                <input type="hidden" name="student_pkl_ids[]" value="{{ $spkl->id }}">
            @endforeach

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Tanggal Kunjungan <span class="text-rose-500">*</span></label>
                <input type="date" name="visit_date" value="{{ date('Y-m-d') }}" required class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Kemajuan &amp; Kedisiplinan Siswa <span class="text-rose-500">*</span></label>
                <textarea name="student_progress_summary" rows="3" required placeholder="Jelaskan kondisi dan progres siswa berdasarkan hasil kunjungan lapangan..." class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Masukan &amp; Evaluasi dari DU/DI <span class="text-rose-500">*</span></label>
                <textarea name="company_feedback" rows="3" required placeholder="Catatan dari pembimbing lapangan perusahaan..." class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Kesepakatan / Tindak Lanjut (Opsional)</label>
                <input type="text" name="follow_up_agreement" class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Foto Bersama Siswa</label>
                    <input type="file" name="photo_with_students" accept="image/*" class="w-full text-xs text-slate-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Foto Bersama Pembimbing DU/DI</label>
                    <input type="file" name="photo_with_supervisor" accept="image/*" class="w-full text-xs text-slate-500">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="document.getElementById('modal-briefing-visit').classList.add('hidden')" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl">Batal</button>
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-sm">
                    Simpan, Selesaikan Tugas &amp; Terbitkan Berita Acara
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
