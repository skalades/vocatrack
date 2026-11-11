@extends('layouts.app')

@section('page_title', 'Konfigurasi Sekolah & Aturan PKL')
@section('page_subtitle', 'Pengaturan jam kerja presensi, toleransi jurnal, bobot penilaian, dan kop surat resmi')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Enterprise Hero Banner -->
    <x-page-header 
        title="Konfigurasi Sekolah & Aturan Operasional PKL" 
        subtitle="Kelola jam kerja harian, batas toleransi presensi, bobot penilaian akhir, kop dinas resmi, dan gelombang periode PKL."
        badge="MANAJEMEN SEKOLAH"
    >
        <x-button type="button" variant="secondary" size="sm" onclick="openAddPeriodModal()">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>+ Periode Baru</span>
        </x-button>
        <x-button type="button" variant="primary" size="sm" onclick="document.getElementById('form-settings').submit()">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            <span>Simpan Semua Perubahan</span>
        </x-button>
    </x-page-header>

    <!-- Top KPI Rule Summary -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card 
            title="Jam Masuk & Toleransi" 
            :value="substr($settings['checkin_start'] ?? '06:00', 0, 5) . ' – ' . substr($settings['checkin_late'] ?? '08:00', 0, 5)" 
            :subtitle="'Alfa setelah ' . substr($settings['checkin_alfa'] ?? '11:00', 0, 5) . ' WIB'"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Jam Pulang Efektif" 
            :value="substr($settings['checkout_start'] ?? '16:00', 0, 5) . ' WIB'" 
            subtitle="Waktu minimal kepulangan siswa"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Bobot Penilaian Akhir" 
            :value="($settings['weight_dudi'] ?? 60) . '% : ' . ($settings['weight_guru'] ?? 40) . '%'" 
            subtitle="DU/DI Industri vs Sekolah Guru"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Periode PKL Aktif" 
            :value="$periods->count() . ' Gelombang'" 
            :subtitle="$periods->firstWhere('is_active', true)?->name ?? 'Belum ada periode aktif'"
            color="amber"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Main Configuration Form -->
    <form id="form-settings" action="{{ route('adminsekolah.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- Left Column: School Identity & Official Letterhead (Kop) -->
            <div class="lg:col-span-7 space-y-6">
                <!-- 1. Identitas Sekolah & Kop Surat -->
                <x-card 
                    title="1. Identitas Lembaga & Kop Surat Resmi (PDF)" 
                    subtitle="Informasi ini dicetak pada sertifikat resmi, rekap presensi, dan lembar laporan evaluasi PKL."
                >
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Resmi Satuan Pendidikan</label>
                                <input type="text" name="school_name" value="{{ old('school_name', $school->name) }}" required 
                                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-semibold text-slate-900">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nomor Telepon / Kontak Resmi</label>
                                <input type="text" name="school_phone" value="{{ old('school_phone', $school->phone) }}" required 
                                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-semibold text-slate-900">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Lengkap Satuan Pendidikan</label>
                            <textarea name="school_address" rows="2" required 
                                      class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all text-slate-900 leading-relaxed">{{ old('school_address', $school->address) }}</textarea>
                        </div>

                        <!-- Logo Resmi Sekolah -->
                        <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span class="text-xs font-bold text-slate-800">Logo Resmi Sekolah (Kop Surat PDF)</span>
                                </div>
                                <span class="text-[10px] text-slate-500 font-medium">PNG, JPG, WEBP (Maks. 2MB)</span>
                            </div>

                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 pt-1">
                                <!-- Preview Container -->
                                <div class="relative w-20 h-20 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex items-center justify-center p-2 overflow-hidden shrink-0 group">
                                    @if($school->logo_path)
                                        <img id="logo-preview-img" src="{{ asset('storage/' . $school->logo_path) }}" alt="{{ $school->name }}" class="w-full h-full object-contain">
                                    @else
                                        <img id="logo-preview-img" src="" alt="Preview Logo" class="hidden w-full h-full object-contain">
                                        <div id="logo-placeholder" class="text-center">
                                            <svg class="w-7 h-7 text-slate-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                            <span class="text-[9px] text-slate-400 font-bold block mt-0.5">Belum Ada</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Upload Controls -->
                                <div class="flex-1 space-y-2 w-full">
                                    <input type="file" name="school_logo" id="school_logo_input" accept="image/png,image/jpeg,image/webp,image/svg+xml" onchange="previewSchoolLogo(this)" 
                                           class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all cursor-pointer">
                                    <p class="text-[11px] text-slate-500 leading-normal">
                                        Disarankan menggunakan logo resmi berlatar transparan (.PNG). Dicetak di sudut kiri atas dokumen resmi PDF.
                                    </p>
                                    @if($school->logo_path)
                                        <label class="inline-flex items-center gap-1.5 pt-1 text-xs font-semibold text-rose-600 cursor-pointer">
                                            <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                            <span>Hapus logo saat ini (kembalikan tanpa logo)</span>
                                        </label>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Format Teks Kop Surat -->
                        <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-3">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span class="text-xs font-bold text-slate-800">Format Baris Header Kop Surat (PDF)</span>
                            </div>
                            <div class="space-y-2">
                                <input type="text" name="kop_header_line1" value="{{ $settings['kop_header_line1'] ?? 'PEMERINTAH DAERAH PROVINSI' }}" placeholder="Baris 1: PEMERINTAH DAERAH..." 
                                       class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium text-slate-800">
                                <input type="text" name="kop_header_line2" value="{{ $settings['kop_header_line2'] ?? 'DINAS PENDIDIKAN' }}" placeholder="Baris 2: DINAS PENDIDIKAN..." 
                                       class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium text-slate-800">
                                <input type="text" name="kop_header_line3" value="{{ $settings['kop_header_line3'] ?? strtoupper($school->name) }}" placeholder="Baris 3: NAMA LENGKAP SEKOLAH..." 
                                       class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-bold text-slate-900">
                            </div>
                        </div>

                        <!-- Data Kepala Sekolah -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Kepala Sekolah & Gelar</label>
                                <input type="text" name="kepsek_name" value="{{ $settings['kepsek_name'] ?? 'Dr. H. Bambang Sudarsono, M.Pd.' }}" required 
                                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-semibold text-slate-900">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">NIP Kepala Sekolah</label>
                                <input type="text" name="kepsek_nip" value="{{ $settings['kepsek_nip'] ?? '197003121995121001' }}" 
                                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-mono text-slate-900">
                            </div>
                        </div>
                    </div>
                </x-card>
            </div>

            <!-- Right Column: Working Hours, Rules, Weights & Periods -->
            <div class="lg:col-span-5 space-y-6">

                <!-- 2. Jam Kerja Default & Toleransi -->
                <x-card 
                    title="2. Jam Kerja Presensi Default" 
                    subtitle="Berlaku global untuk seluruh siswa peserta PKL."
                >
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div class="p-3.5 rounded-2xl bg-slate-50/70 border border-slate-200/80">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Check-In Buka</label>
                            <input type="time" step="1" name="checkin_start" value="{{ $settings['checkin_start'] ?? '06:00:00' }}" required 
                                   class="w-full text-xs font-mono font-bold p-2.5 rounded-xl bg-white border border-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                            <span class="text-[10px] text-slate-500 font-medium block mt-1">Tombol check-in aktif</span>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-amber-50/50 border border-amber-200/70">
                            <label class="block text-xs font-bold text-amber-900 mb-1">Batas Tepat Waktu</label>
                            <input type="time" step="1" name="checkin_late" value="{{ $settings['checkin_late'] ?? '08:00:00' }}" required 
                                   class="w-full text-xs font-mono font-bold p-2.5 rounded-xl bg-white border border-amber-200 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-600 text-amber-900">
                            <span class="text-[10px] text-amber-700 font-medium block mt-1">Status jadi Terlambat</span>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-rose-50/50 border border-rose-200/70">
                            <label class="block text-xs font-bold text-rose-900 mb-1">Batas Akhir Masuk</label>
                            <input type="time" step="1" name="checkin_alfa" value="{{ $settings['checkin_alfa'] ?? '11:00:00' }}" required 
                                   class="w-full text-xs font-mono font-bold p-2.5 rounded-xl bg-white border border-rose-200 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-600 text-rose-900">
                            <span class="text-[10px] text-rose-700 font-medium block mt-1">Otomatis Alfa</span>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-emerald-50/50 border border-emerald-200/70">
                            <label class="block text-xs font-bold text-emerald-900 mb-1">Check-Out Buka</label>
                            <input type="time" step="1" name="checkout_start" value="{{ $settings['checkout_start'] ?? '16:00:00' }}" required 
                                   class="w-full text-xs font-mono font-bold p-2.5 rounded-xl bg-white border border-emerald-200 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 text-emerald-900">
                            <span class="text-[10px] text-emerald-700 font-medium block mt-1">Waktu minimal pulang</span>
                        </div>
                    </div>
                </x-card>

                <!-- 3. Aturan Jurnal & Bobot Penilaian PKL -->
                <x-card 
                    title="3. Bobot Penilaian & Toleransi Jurnal" 
                    subtitle="Proporsi nilai akhir sertifikat dan toleransi susulan logbook."
                >
                    <div class="space-y-3.5">
                        <div class="p-3.5 rounded-2xl bg-slate-50/70 border border-slate-200/80">
                            <label class="block text-xs font-bold text-slate-800 mb-1">Toleransi Susulan Jurnal (Backdate)</label>
                            <div class="relative mt-1">
                                <input type="number" name="journal_backdate_limit" value="{{ $settings['journal_backdate_limit'] ?? 3 }}" min="0" max="30" required 
                                       class="w-full text-xs sm:text-sm font-bold px-3 py-2.5 rounded-xl bg-white border border-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                                <span class="absolute inset-y-0 right-3 flex items-center text-xs text-slate-400 font-semibold pointer-events-none">Hari</span>
                            </div>
                            <span class="text-[10px] text-slate-500 block mt-1.5 font-medium">Batas maksimal hari susulan jurnal ke belakang</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3.5 rounded-2xl bg-indigo-50/50 border border-indigo-200/70">
                                <label class="block text-xs font-bold text-indigo-900 mb-1">DU/DI Industri</label>
                                <div class="relative mt-1">
                                    <input type="number" name="weight_dudi" value="{{ $settings['weight_dudi'] ?? 60 }}" min="0" max="100" required 
                                           class="w-full text-xs sm:text-sm font-bold px-3 py-2.5 rounded-xl bg-white border border-indigo-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-indigo-900">
                                    <span class="absolute inset-y-0 right-3 flex items-center text-xs text-indigo-500 font-bold pointer-events-none">%</span>
                                </div>
                                <span class="text-[10px] text-indigo-700 block mt-1.5 font-medium">Nilai teknis lapangan</span>
                            </div>

                            <div class="p-3.5 rounded-2xl bg-indigo-50/50 border border-indigo-200/70">
                                <label class="block text-xs font-bold text-indigo-900 mb-1">Sekolah (Guru)</label>
                                <div class="relative mt-1">
                                    <input type="number" name="weight_guru" value="{{ $settings['weight_guru'] ?? 40 }}" min="0" max="100" required 
                                           class="w-full text-xs sm:text-sm font-bold px-3 py-2.5 rounded-xl bg-white border border-indigo-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-indigo-900">
                                    <span class="absolute inset-y-0 right-3 flex items-center text-xs text-indigo-500 font-bold pointer-events-none">%</span>
                                </div>
                                <span class="text-[10px] text-indigo-700 block mt-1.5 font-medium">Nilai laporan & seminar</span>
                            </div>
                        </div>
                    </div>
                </x-card>

                <!-- 4. Save Button -->
                <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-900 block">Simpan Pengaturan</span>
                        <span class="text-[11px] text-slate-500">Terapkan konfigurasi ke seluruh sistem</span>
                    </div>
                    <x-button type="submit" variant="primary" size="md">
                        <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span>Simpan</span>
                    </x-button>
                </div>

            </div>
        </div>
    </form>

    <!-- 5. Periode Pelaksanaan PKL (Gelombang / Angkatan) -->
    <x-card 
        title="4. Periode & Gelombang Pelaksanaan PKL" 
        subtitle="Kelola gelombang pelaksanaan PKL, rentang tanggal pelaksanaan, dan status keaktifan periode."
    >
        <x-slot:headerActions>
            <x-button type="button" variant="primary" size="xs" onclick="openAddPeriodModal()">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                + Tambah Periode
            </x-button>
        </x-slot:headerActions>

        <div class="border border-slate-200/80 rounded-2xl overflow-hidden divide-y divide-slate-100 bg-white">
            @forelse($periods as $pr)
                <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/70 transition-colors">
                    <div class="min-w-0">
                        <div class="flex items-center space-x-2.5">
                            <span class="text-sm font-extrabold text-slate-900 block truncate">{{ $pr->name }}</span>
                            @if($pr->is_active)
                                <x-badge variant="success" size="xs">
                                    Periode Aktif
                                </x-badge>
                            @else
                                <x-badge variant="neutral" size="xs">
                                    Nonaktif
                                </x-badge>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 mt-1.5 font-medium">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span>{{ \Carbon\Carbon::parse($pr->start_date)->format('d M Y') }} s.d. {{ \Carbon\Carbon::parse($pr->end_date)->format('d M Y') }}</span>
                            </span>
                            <span>&bull;</span>
                            <span class="flex items-center gap-1.5 text-indigo-700 font-semibold">
                                <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                <span>{{ $pr->student_pkls_count ?? 0 }} Siswa Telah Diplot</span>
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 shrink-0">
                        <form action="{{ route('adminsekolah.periods.toggle-active', $pr) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-colors cursor-pointer {{ $pr->is_active ? 'bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200' }}">
                                {{ $pr->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>

                        <button type="button" 
                                onclick="openEditPeriodModal({{ json_encode([
                                    'id' => $pr->id,
                                    'name' => $pr->name,
                                    'start_date' => $pr->start_date,
                                    'end_date' => $pr->end_date,
                                    'is_active' => (bool)$pr->is_active,
                                ]) }})"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 transition-colors cursor-pointer">
                            Edit
                        </button>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-slate-400 text-xs">
                    Belum ada periode PKL yang dibuat. Klik tombol di atas untuk membuat gelombang baru.
                </div>
            @endforelse
        </div>
    </x-card>

</div>

<!-- Modal Form Period (Add / Edit) -->
<div id="modal-period-form" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-2xl border border-slate-100 animate-in fade-in duration-150">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <h3 id="period-form-title" class="text-base font-extrabold text-slate-900 tracking-tight">Tambah Periode PKL Baru</h3>
            <button type="button" onclick="document.getElementById('modal-period-form').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="form-period" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" id="period-form-method" value="POST">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Periode / Angkatan PKL</label>
                <input type="text" name="name" id="period-name" required placeholder="Contoh: PKL Gelombang 1 TP 2026/2027" 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-semibold text-slate-900">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Tanggal Mulai</label>
                    <input type="date" name="start_date" id="period-start" required 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Tanggal Berakhir</label>
                    <input type="date" name="end_date" id="period-end" required 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                </div>
            </div>

            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-800 block">Jadikan Periode Aktif</span>
                    <span class="text-[10px] text-slate-500">Otomatis dipilih saat memetakan plotting siswa</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" id="period-active" value="1" class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500/20 cursor-pointer">
                </label>
            </div>

            <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-period-form').classList.add('hidden')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md">
                    Simpan Periode
                </x-button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function previewSchoolLogo(input) {
        const preview = document.getElementById('logo-preview-img');
        const placeholder = document.getElementById('logo-placeholder');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                if (placeholder) placeholder.classList.add('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function openAddPeriodModal() {
        const form = document.getElementById('form-period');
        form.action = "{{ route('adminsekolah.periods.store') }}";
        document.getElementById('period-form-method').value = "POST";
        document.getElementById('period-form-title').textContent = "Tambah Periode PKL Baru";
        document.getElementById('period-name').value = "";
        document.getElementById('period-start').value = "";
        document.getElementById('period-end').value = "";
        document.getElementById('period-active').checked = true;

        document.getElementById('modal-period-form').classList.remove('hidden');
    }

    function openEditPeriodModal(data) {
        const form = document.getElementById('form-period');
        form.action = `/adminsekolah/periods/${data.id}`;
        document.getElementById('period-form-method').value = "PUT";
        document.getElementById('period-form-title').textContent = "Edit Periode PKL";
        document.getElementById('period-name').value = data.name || "";
        document.getElementById('period-start').value = data.start_date ? data.start_date.substring(0, 10) : "";
        document.getElementById('period-end').value = data.end_date ? data.end_date.substring(0, 10) : "";
        document.getElementById('period-active').checked = !!data.is_active;

        document.getElementById('modal-period-form').classList.remove('hidden');
    }
</script>
@endpush
@endsection
