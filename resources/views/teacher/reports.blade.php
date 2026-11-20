@extends(\App\Services\DeviceService::teacherLayout())

@section('page_title', 'Ekspor Laporan PDF Siswa PKL')
@section('page_subtitle', 'Unduh dokumen laporan individu lengkap dengan kop dinas resmi sekolah dan lembar pengesahan')

@section('content')
<div class="w-full space-y-6">

    <!-- Enterprise Hero Banner -->
    <x-page-header 
        title="Dokumen Laporan PDF Siswa PKL" 
        subtitle="Unduh berkas laporan individu yang merangkum kop dinas resmi, biodata peserta, rekap presensi harian, log jurnal terverifikasi, dan lembar nilai terpadu."
        badge="GURU PEMBIMBING"
    >
        <x-button type="button" variant="secondary" size="sm" onclick="window.location.href='{{ route('guru.monitoring') }}'">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span>Monitoring Absensi</span>
        </x-button>
        <x-button type="button" variant="primary" size="sm" onclick="window.location.href='{{ route('guru.assessments') }}'">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <span>Input Penilaian</span>
        </x-button>
    </x-page-header>

    @php
        $completeScore = $studentPkls->filter(fn($sp) => $sp->assessment && $sp->assessment->final_score !== null)->count();
        $companiesCount = $studentPkls->pluck('company_id')->unique()->count();
    @endphp

    <!-- Top KPI Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card 
            title="Total Siswa Binaan" 
            :value="$studentPkls->count() . ' Siswa'" 
            subtitle="Peserta bimbingan aktif"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Laporan Lengkap Nilai" 
            :value="$completeScore . ' Siswa'" 
            subtitle="Nilai industri & sekolah lengkap"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Mitra DU/DI Terkait" 
            :value="$companiesCount . ' Perusahaan'" 
            subtitle="Tempat magang siswa binaan"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Status Ekspor PDF" 
            value="Siap Unduh" 
            subtitle="Format A4 resmi berstandar dinas"
            color="amber"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Reports Table Card -->
    <x-card noPadding>
        <div class="p-4 sm:p-5 bg-white border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Daftar Dokumen Laporan PKL Siswa</h3>
                <p class="text-xs text-slate-500 font-medium">Klik unduh untuk men-generate file PDF resmi ber-kop sekolah</p>
            </div>
            <span class="text-xs text-slate-500 font-semibold">
                {{ $studentPkls->count() }} siswa bimbingan
            </span>
        </div>

        <!-- Opsi Ekspor Laporan: Include Foto Lapangan -->
        <div class="p-4 sm:p-5 bg-slate-50/90 border-b border-slate-200/80 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start sm:items-center space-x-3.5">
                <label class="relative inline-flex items-center cursor-pointer shrink-0 mt-0.5 sm:mt-0">
                    <input type="checkbox" id="toggle-include-photos" class="sr-only peer" onchange="togglePhotoOption(this.checked)">
                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                </label>
                <div>
                    <label for="toggle-include-photos" class="text-xs font-bold text-slate-900 cursor-pointer flex items-center gap-1.5 select-none">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Sertakan Foto Dokumentasi Lapangan dalam PDF</span>
                    </label>
                    <p class="text-[11px] text-slate-500 font-medium">
                        Menyematkan galeri foto bukti aktivitas kerja harian yang telah diverifikasi industri ke dalam logbook jurnal PDF.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span id="photo-status-badge" class="px-2.5 py-1 rounded-xl text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                    Format Standar (Tanpa Foto)
                </span>
            </div>
        </div>

        @if($studentPkls->isEmpty())
            <div class="py-12">
                <x-empty-state 
                    title="Belum Ada Dokumen Siswa" 
                    description="Anda belum memiliki siswa bimbingan aktif untuk diekspor laporannya."
                />
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($studentPkls as $sp)
                    <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/60 transition-colors">
                        <div class="space-y-2 min-w-0">
                            <div class="flex items-center space-x-3.5">
                                <div class="w-10 h-10 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-700 font-black text-xs flex items-center justify-center shrink-0 shadow-xs shadow-indigo-600/10">
                                    {{ substr($sp->student->user->name, 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <span class="font-extrabold text-sm text-slate-900 block truncate">{{ $sp->student->user->name }}</span>
                                    <span class="text-xs text-slate-500 font-medium block truncate mt-0.5">
                                        {{ $sp->student->class->name }} &bull; NISN: {{ $sp->student->nisn }} &bull; DU/DI: {{ $sp->company->name }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 pt-0.5 pl-13">
                                <x-badge variant="success" size="xs">
                                    {{ $sp->attendances_count }} Hari Presensi
                                </x-badge>
                                <x-badge variant="info" size="xs">
                                    {{ $sp->approved_journals_count }} Jurnal Disetujui
                                </x-badge>
                                @if($sp->assessment && $sp->assessment->final_score)
                                    <x-badge variant="warning" size="xs">
                                        Nilai Akhir: {{ number_format($sp->assessment->final_score, 1) }}
                                    </x-badge>
                                @else
                                    <x-badge variant="neutral" size="xs">
                                        Nilai Belum Lengkap
                                    </x-badge>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                            <!-- Tombol Unduh Utama (Menyesuaikan Switch Opsi) -->
                            <a id="btn-download-{{ $sp->id }}" 
                               href="{{ route('guru.reports.download', $sp->id) }}" 
                               data-base-url="{{ route('guru.reports.download', $sp->id) }}"
                               class="download-report-btn inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold text-xs shadow-xs shadow-indigo-600/20 transition-all cursor-pointer">
                                <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span class="download-btn-text">Unduh Laporan PDF</span>
                            </a>

                            <!-- Menu Pilihan Format Cepat -->
                            <div class="relative inline-block text-left">
                                <button type="button" 
                                        onclick="toggleRowDropdown('dropdown-{{ $sp->id }}', event)" 
                                        class="p-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 hover:text-slate-900 transition-colors cursor-pointer"
                                        title="Pilihan Opsi Unduh">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div id="dropdown-{{ $sp->id }}" class="row-dropdown-menu hidden absolute right-0 mt-2 w-56 rounded-2xl bg-white shadow-2xl border border-slate-100 py-1.5 z-30 text-xs text-slate-700 animate-in fade-in zoom-in-95 duration-100">
                                    <div class="px-3 py-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                                        Pilih Format PDF
                                    </div>
                                    <a href="{{ route('guru.reports.download', $sp->id) }}" class="flex items-center gap-2.5 px-3.5 py-2 hover:bg-slate-50 text-slate-700 font-semibold transition-colors">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span>Format Standar (Tanpa Foto)</span>
                                    </a>
                                    <a href="{{ route('guru.reports.download', ['spkl' => $sp->id, 'include_photos' => 1]) }}" class="flex items-center gap-2.5 px-3.5 py-2 hover:bg-indigo-50 text-indigo-700 font-semibold transition-colors">
                                        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span>Lengkap (+ Foto Lapangan)</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>

</div>

<script>
    function togglePhotoOption(isIncluded) {
        const badge = document.getElementById('photo-status-badge');
        const buttons = document.querySelectorAll('.download-report-btn');

        if (isIncluded) {
            if (badge) {
                badge.innerText = 'Lengkap (+ Foto Lapangan)';
                badge.className = 'px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200';
            }
            buttons.forEach(btn => {
                const base = btn.getAttribute('data-base-url');
                const url = new URL(base, window.location.origin);
                url.searchParams.set('include_photos', '1');
                btn.href = url.pathname + url.search;
                const textSpan = btn.querySelector('.download-btn-text');
                if (textSpan) textSpan.innerText = 'Unduh PDF (+ Foto Lapangan)';
            });
            try {
                localStorage.setItem('vocatrack_report_include_photos', '1');
            } catch (e) {}
        } else {
            if (badge) {
                badge.innerText = 'Format Standar (Tanpa Foto)';
                badge.className = 'px-2.5 py-1 rounded-xl text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200';
            }
            buttons.forEach(btn => {
                const base = btn.getAttribute('data-base-url');
                btn.href = base;
                const textSpan = btn.querySelector('.download-btn-text');
                if (textSpan) textSpan.innerText = 'Unduh Laporan PDF';
            });
            try {
                localStorage.setItem('vocatrack_report_include_photos', '0');
            } catch (e) {}
        }
    }

    function toggleRowDropdown(id, event) {
        if (event) event.stopPropagation();
        const menu = document.getElementById(id);
        const allMenus = document.querySelectorAll('.row-dropdown-menu');
        
        allMenus.forEach(m => {
            if (m.id !== id) m.classList.add('hidden');
        });

        if (menu) {
            menu.classList.toggle('hidden');
        }
    }

    // Auto-close dropdowns when clicking anywhere outside
    document.addEventListener('click', function() {
        document.querySelectorAll('.row-dropdown-menu').forEach(m => m.classList.add('hidden'));
    });

    // Initialize toggle state from localStorage or query param
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        let shouldInclude = urlParams.get('include_photos') === '1';

        if (!urlParams.has('include_photos')) {
            try {
                shouldInclude = localStorage.getItem('vocatrack_report_include_photos') === '1';
            } catch (e) {}
        }

        const checkbox = document.getElementById('toggle-include-photos');
        if (checkbox && shouldInclude) {
            checkbox.checked = true;
            togglePhotoOption(true);
        }
    });
</script>
@endsection
