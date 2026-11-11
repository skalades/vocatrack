@extends('layouts.app')

@section('page_title', 'Konfigurasi Sekolah & Aturan PKL')
@section('page_subtitle', 'Pengaturan jam kerja presensi, toleransi jurnal, bobot penilaian, dan kop surat resmi')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <x-page-header 
        title="Konfigurasi Sekolah & Aturan PKL" 
        subtitle="Kelola jam kerja harian, toleransi terlambat, bobot nilai akhir, dan template kop dinas resmi."
    />

    <form action="{{ route('adminsekolah.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- 1. Identitas Sekolah & Kop Surat -->
        <x-card 
            title="1. Identitas & Kop Surat Resmi" 
            subtitle="Informasi ini dicetak pada sertifikat, rekap absensi, dan lembar laporan PKL resmi PDF."
        >
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Lengkap Sekolah</label>
                    <textarea name="school_address" rows="2" required 
                              class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all text-slate-900 leading-relaxed">{{ old('school_address', $school->address) }}</textarea>
                </div>

                <!-- Logo Resmi Sekolah untuk PDF & Kop Dokumen -->
                <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span class="text-xs font-bold text-slate-800">Logo Resmi Sekolah (Kop Surat PDF)</span>
                        </div>
                        <span class="text-[10px] text-slate-500 font-medium">PNG, JPG, WEBP (Maks. 2MB)</span>
                    </div>

                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 pt-1">
                        <!-- Current Logo / Preview Container -->
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
                                Logo akan dicetak di sisi kiri atas Kop Surat Laporan Penilaian PKL & Rekapitulasi Siswa berformat PDF. Disarankan menggunakan logo berlatar transparan (PNG).
                            </p>
                            @if($school->logo_path)
                                <label class="inline-flex items-center gap-1.5 pt-1 text-xs font-semibold text-rose-600 cursor-pointer">
                                    <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                    <span>Hapus logo saat ini (kembalikan ke kop surat tanpa logo)</span>
                                </label>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Template Kop Surat PDF -->
                <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-3">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span class="text-xs font-bold text-slate-800">Format Baris Header Kop Surat (PDF)</span>
                    </div>
                    <div class="space-y-2">
                        <input type="text" name="kop_header_line1" value="{{ $settings['kop_header_line1'] ?? 'PEMERINTAH DAERAH PROVINSI' }}" placeholder="Baris 1: PEMERINTAH DAERAH..." 
                               class="w-full text-xs px-3.5 py-2 rounded-xl bg-white border border-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium text-slate-800">
                        <input type="text" name="kop_header_line2" value="{{ $settings['kop_header_line2'] ?? 'DINAS PENDIDIKAN' }}" placeholder="Baris 2: DINAS PENDIDIKAN..." 
                               class="w-full text-xs px-3.5 py-2 rounded-xl bg-white border border-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium text-slate-800">
                        <input type="text" name="kop_header_line3" value="{{ $settings['kop_header_line3'] ?? strtoupper($school->name) }}" placeholder="Baris 3: NAMA LENGKAP SEKOLAH..." 
                               class="w-full text-xs px-3.5 py-2 rounded-xl bg-white border border-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-bold text-slate-900">
                    </div>
                </div>

                <!-- Kepala Sekolah Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Lengkap Kepala Sekolah Beserta Gelar</label>
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

        <!-- 2. Jam Kerja Default & Toleransi Kehadiran -->
        <x-card 
            title="2. Jam Kerja & Batas Presensi Default" 
            subtitle="Berlaku secara global untuk seluruh siswa PKL, kecuali industri terkait menerapkan shift khusus."
        >
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <div class="p-3.5 rounded-2xl bg-slate-50/70 border border-slate-200/80">
                    <label class="block text-xs font-bold text-slate-700 mb-1">Check-In Dimulai</label>
                    <input type="time" step="1" name="checkin_start" value="{{ $settings['checkin_start'] ?? '06:00:00' }}" required 
                           class="w-full text-xs font-mono font-bold p-2.5 rounded-xl bg-white border border-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                    <span class="text-[10px] text-slate-500 font-medium block mt-1.5">Waktu tombol absen aktif</span>
                </div>

                <div class="p-3.5 rounded-2xl bg-amber-50/40 border border-amber-200/60">
                    <label class="block text-xs font-bold text-amber-900 mb-1">Batas Tepat Waktu</label>
                    <input type="time" step="1" name="checkin_late" value="{{ $settings['checkin_late'] ?? '08:00:00' }}" required 
                           class="w-full text-xs font-mono font-bold p-2.5 rounded-xl bg-white border border-amber-200 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-600 text-amber-900">
                    <span class="text-[10px] text-amber-700 font-medium block mt-1.5">Lewat jam ini: Terlambat</span>
                </div>

                <div class="p-3.5 rounded-2xl bg-rose-50/40 border border-rose-200/60">
                    <label class="block text-xs font-bold text-rose-900 mb-1">Batas Maksimal Masuk</label>
                    <input type="time" step="1" name="checkin_alfa" value="{{ $settings['checkin_alfa'] ?? '11:00:00' }}" required 
                           class="w-full text-xs font-mono font-bold p-2.5 rounded-xl bg-white border border-rose-200 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-600 text-rose-900">
                    <span class="text-[10px] text-rose-700 font-medium block mt-1.5">Lewat jam ini: Otomatis Alfa</span>
                </div>

                <div class="p-3.5 rounded-2xl bg-emerald-50/40 border border-emerald-200/60">
                    <label class="block text-xs font-bold text-emerald-900 mb-1">Check-Out Dimulai</label>
                    <input type="time" step="1" name="checkout_start" value="{{ $settings['checkout_start'] ?? '16:00:00' }}" required 
                           class="w-full text-xs font-mono font-bold p-2.5 rounded-xl bg-white border border-emerald-200 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 text-emerald-900">
                    <span class="text-[10px] text-emerald-700 font-medium block mt-1.5">Waktu minimal kepulangan</span>
                </div>

            </div>
        </x-card>

        <!-- 3. Aturan Jurnal & Bobot Penilaian PKL -->
        <x-card 
            title="3. Aturan Jurnal & Bobot Nilai Akhir" 
            subtitle="Tetapkan batasan pengisian log harian serta pembagian persentase penilaian sertifikat."
        >
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/80">
                    <label class="block text-xs font-bold text-slate-800 mb-1">Toleransi Backdate Jurnal</label>
                    <div class="relative mt-1">
                        <input type="number" name="journal_backdate_limit" value="{{ $settings['journal_backdate_limit'] ?? 3 }}" min="0" max="30" required 
                               class="w-full text-xs sm:text-sm font-bold px-3 py-2.5 rounded-xl bg-white border border-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                        <span class="absolute inset-y-0 right-3 flex items-center text-xs text-slate-400 font-semibold pointer-events-none">Hari</span>
                    </div>
                    <span class="text-[10px] text-slate-500 block mt-1.5 font-medium">Batas maksimal hari susulan jurnal ke belakang</span>
                </div>

                <div class="p-4 rounded-2xl bg-indigo-50/40 border border-indigo-200/60">
                    <label class="block text-xs font-bold text-indigo-900 mb-1">Bobot Nilai DU/DI (Industri)</label>
                    <div class="relative mt-1">
                        <input type="number" name="weight_dudi" value="{{ $settings['weight_dudi'] ?? 60 }}" min="0" max="100" required 
                               class="w-full text-xs sm:text-sm font-bold px-3 py-2.5 rounded-xl bg-white border border-indigo-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-indigo-900">
                        <span class="absolute inset-y-0 right-3 flex items-center text-xs text-indigo-500 font-bold pointer-events-none">%</span>
                    </div>
                    <span class="text-[10px] text-indigo-700 block mt-1.5 font-medium">Standar kurikulum: 60%</span>
                </div>

                <div class="p-4 rounded-2xl bg-indigo-50/40 border border-indigo-200/60">
                    <label class="block text-xs font-bold text-indigo-900 mb-1">Bobot Nilai Sekolah (Guru)</label>
                    <div class="relative mt-1">
                        <input type="number" name="weight_guru" value="{{ $settings['weight_guru'] ?? 40 }}" min="0" max="100" required 
                               class="w-full text-xs sm:text-sm font-bold px-3 py-2.5 rounded-xl bg-white border border-indigo-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 text-indigo-900">
                        <span class="absolute inset-y-0 right-3 flex items-center text-xs text-indigo-500 font-bold pointer-events-none">%</span>
                    </div>
                    <span class="text-[10px] text-indigo-700 block mt-1.5 font-medium">Standar kurikulum: 40%</span>
                </div>

            </div>
        </x-card>

        <!-- Submit Button Bar -->
        <div class="flex items-center justify-end pt-2">
            <x-button type="submit" variant="primary" size="lg" class="w-full sm:w-auto">
                <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <span>Simpan Konfigurasi Sekolah</span>
            </x-button>
        </div>
    </form>

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
</script>
@endpush
@endsection
