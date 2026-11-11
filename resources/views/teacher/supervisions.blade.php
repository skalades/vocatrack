@extends('layouts.app')

@section('page_title', 'Supervisi & Berita Acara Kunjungan')
@section('page_subtitle', 'Dokumentasi resmi kunjungan pembimbingan lapangan dan pencetakan Berita Acara standar Ditjen Vokasi')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Enterprise Hero Banner -->
    <x-page-header 
        title="Supervisi & Berita Acara Kunjungan" 
        subtitle="Dokumentasi resmi kunjungan pembimbingan lapangan dan pencetakan Berita Acara standar Ditjen Vokasi."
        badge="SUPERVISI LAPANGAN"
    >
        <x-button type="button" variant="primary" size="sm" onclick="document.getElementById('modal-create-visit').classList.remove('hidden')">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span>Catat Kunjungan Lapangan</span>
        </x-button>
    </x-page-header>

    <!-- STATS CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <x-stat-card 
            title="Total Kunjungan" 
            :value="$stats['total']" 
            subtitle="Kunjungan supervisi tercatat"
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
            title="Mitra DU/DI Terbimbing" 
            :value="$stats['companies_count']" 
            subtitle="Perusahaan lokasi magang"
            color="amber"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- FILTER & RIWAYAT KUNJUNGAN -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <!-- Filter Bar -->
        <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="font-black text-slate-900 text-sm sm:text-base flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <span>Riwayat Supervisi & Berita Acara</span>
            </div>
            <form method="GET" action="{{ route('guru.supervisions') }}" class="flex flex-wrap items-center gap-3">
                <select name="company_id" onchange="this.form.submit()" class="text-xs font-semibold rounded-xl border-slate-200 bg-white shadow-2xs focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua DU/DI</option>
                    @foreach ($companies as $c)
                        <option value="{{ $c->id }}" {{ request('company_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>

                <select name="stage" onchange="this.form.submit()" class="text-xs font-semibold rounded-xl border-slate-200 bg-white shadow-2xs focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua Tahapan</option>
                    <option value="penyerahan" {{ request('stage') == 'penyerahan' ? 'selected' : '' }}>Penyerahan Siswa</option>
                    <option value="monitoring_1" {{ request('stage') == 'monitoring_1' ? 'selected' : '' }}>Monitoring 1 (Awal)</option>
                    <option value="monitoring_2" {{ request('stage') == 'monitoring_2' ? 'selected' : '' }}>Monitoring 2 (Tengah)</option>
                    <option value="penjemputan" {{ request('stage') == 'penjemputan' ? 'selected' : '' }}>Penjemputan Siswa</option>
                    <option value="insidental" {{ request('stage') == 'insidental' ? 'selected' : '' }}>Kunjungan Insidental</option>
                </select>

                @if(request()->hasAny(['company_id', 'stage']))
                    <a href="{{ route('guru.supervisions') }}" class="text-xs font-bold text-slate-500 hover:text-rose-600 transition-colors">Reset</a>
                @endif
            </form>
        </div>

        <!-- List Kunjungan -->
        <div class="divide-y divide-slate-100">
            @forelse ($visits as $visit)
                <div class="p-5 sm:p-6 hover:bg-slate-50/60 transition-colors flex flex-col lg:flex-row lg:items-start justify-between gap-5">
                    <div class="space-y-3 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border {{ $visit->stage_badge_class }}">
                                {{ $visit->stage_label }}
                            </span>
                            <span class="text-xs font-bold text-slate-400">&bull;</span>
                            <span class="text-xs font-bold text-slate-700 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ $visit->visit_date ? $visit->visit_date->translatedFormat('d F Y') : '-' }}
                            </span>
                            <span class="text-xs font-bold text-slate-400">&bull;</span>
                            <span class="text-xs font-medium text-slate-500">{{ $visit->pklPeriod->name ?? 'Periode PKL' }}</span>
                        </div>

                        <div>
                            <h3 class="text-sm sm:text-base font-black text-slate-900">{{ $visit->company->name ?? 'Perusahaan Mitra' }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $visit->company->address ?? '-' }}</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2">
                            <div class="bg-slate-50 border border-slate-100 rounded-2xl p-3.5 text-xs text-slate-700">
                                <div class="font-bold text-indigo-700 mb-1 flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Kemajuan & Kedisiplinan Siswa:
                                </div>
                                <p class="line-clamp-3 leading-relaxed">{{ $visit->student_progress_summary }}</p>
                            </div>

                            <div class="bg-slate-50 border border-slate-100 rounded-2xl p-3.5 text-xs text-slate-700">
                                <div class="font-bold text-emerald-700 mb-1 flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                    </svg>
                                    Masukan dari Pembimbing DU/DI:
                                </div>
                                <p class="line-clamp-3 leading-relaxed">{{ $visit->company_feedback }}</p>
                            </div>
                        </div>

                        @if($visit->follow_up_agreement)
                            <div class="text-xs bg-amber-50/70 border border-amber-100 text-amber-900 rounded-xl p-3">
                                <span class="font-bold">Tindak Lanjut:</span> {{ $visit->follow_up_agreement }}
                            </div>
                        @endif

                        @if($visit->photo_with_students_path || $visit->photo_with_supervisor_path)
                            <div class="flex items-center gap-3 pt-1">
                                <span class="text-xs font-bold text-slate-400">Dokumentasi Foto:</span>
                                @if($visit->photo_with_students_path)
                                    <a href="{{ Storage::url($visit->photo_with_students_path) }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:underline">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Foto Siswa
                                    </a>
                                @endif
                                @if($visit->photo_with_supervisor_path)
                                    <a href="{{ Storage::url($visit->photo_with_supervisor_path) }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:underline">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Foto Pembimbing DU/DI
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Action Button -->
                    <div class="shrink-0 flex items-center lg:self-center">
                        <a href="{{ route('guru.supervisions.pdf', $visit->id) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200/80 rounded-2xl text-xs font-bold transition-all shadow-2xs">
                            <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Unduh Berita Acara (PDF)</span>
                        </a>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-slate-100 rounded-3xl flex items-center justify-center text-slate-400">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h4 class="text-sm sm:text-base font-bold text-slate-800">Belum Ada Catatan Kunjungan</h4>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Klik tombol "Catat Kunjungan Lapangan" di atas untuk mendokumentasikan supervisi dan mengunduh Berita Acara resmi.</p>
                </div>
            @endforelse
        </div>

        @if($visits->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $visits->links() }}
            </div>
        @endif
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
                    <h3 class="text-base sm:text-lg font-black text-slate-900">Catat Kunjungan Supervisi</h3>
                    <p class="text-xs text-slate-500">Formulir logbook resmi supervisi lapangan pembimbing PKL.</p>
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
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Mitra DU/DI <span class="text-rose-500">*</span></label>
                    <select name="company_id" required class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">-- Pilih Tempat PKL --</option>
                        @foreach ($companies as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Periode PKL <span class="text-rose-500">*</span></label>
                    <select name="pkl_period_id" required class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
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
                    <select name="stage" required class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="penyerahan">Penyerahan Siswa ke Industri</option>
                        <option value="monitoring_1" selected>Monitoring 1 (Bulan Ke-1)</option>
                        <option value="monitoring_2">Monitoring 2 (Pertengahan)</option>
                        <option value="penjemputan">Penjemputan / Penarikan Siswa</option>
                        <option value="insidental">Kunjungan Insidental / Khusus</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Tanggal Kunjungan <span class="text-rose-500">*</span></label>
                    <input type="date" name="visit_date" value="{{ date('Y-m-d') }}" required class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Kemajuan & Kedisiplinan Siswa <span class="text-rose-500">*</span></label>
                <textarea name="student_progress_summary" rows="3" required placeholder="Jelaskan adaptasi kerja siswa, kehadiran, kepatuhan SOP perusahaan, dan progres tugas..." class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 placeholder-slate-400"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Masukan & Evaluasi dari DU/DI <span class="text-rose-500">*</span></label>
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
                    Simpan & Terbitkan Berita Acara
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
