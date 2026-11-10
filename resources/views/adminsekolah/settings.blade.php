@extends('layouts.app')

@section('page_title', 'Konfigurasi Sekolah & Aturan PKL')
@section('page_subtitle', 'Pengaturan jam kerja presensi, toleransi jurnal, bobot penilaian, dan kop surat')

@section('content')
<div class="max-w-4xl space-y-6">

    <form action="{{ route('adminsekolah.settings.update') }}" method="POST" class="space-y-6">
        @csrf

        <!-- 1. Identitas Sekolah -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
            <h3 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">1. Identitas & Kop Surat Resmi</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Resmi Sekolah</label>
                    <input type="text" name="school_name" value="{{ old('school_name', $school->name) }}" required class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Telepon Sekolah</label>
                    <input type="text" name="school_phone" value="{{ old('school_phone', $school->phone) }}" required class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Lengkap</label>
                <textarea name="school_address" rows="2" required class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">{{ old('school_address', $school->address) }}</textarea>
            </div>

            <div class="space-y-2 pt-2 border-t border-slate-100">
                <span class="text-xs font-bold text-slate-700 block">Teks Kop Surat (Muncul pada Dokumen PDF)</span>
                <input type="text" name="kop_header_line1" value="{{ $settings['kop_header_line1'] ?? 'PEMERINTAH DAERAH PROVINSI' }}" placeholder="Baris 1 (Pemerintah Daerah...)" class="w-full text-xs p-2 rounded-xl bg-slate-50 border border-slate-200">
                <input type="text" name="kop_header_line2" value="{{ $settings['kop_header_line2'] ?? 'DINAS PENDIDIKAN' }}" placeholder="Baris 2 (Dinas Pendidikan...)" class="w-full text-xs p-2 rounded-xl bg-slate-50 border border-slate-200">
                <input type="text" name="kop_header_line3" value="{{ $settings['kop_header_line3'] ?? strtoupper($school->name) }}" placeholder="Baris 3 (Nama Lengkap Sekolah...)" class="w-full text-xs p-2 rounded-xl bg-slate-50 border border-slate-200 font-bold">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Kepala Sekolah</label>
                    <input type="text" name="kepsek_name" value="{{ $settings['kepsek_name'] ?? 'Dr. H. Bambang Sudarsono, M.Pd.' }}" required class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200 font-semibold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">NIP Kepala Sekolah</label>
                    <input type="text" name="kepsek_nip" value="{{ $settings['kepsek_nip'] ?? '197003121995121001' }}" class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200 font-mono">
                </div>
            </div>
        </div>

        <!-- 2. Jam Kerja Default & Toleransi -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
            <h3 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">2. Jam Kerja & Batas Presensi Default</h3>
            <p class="text-xs text-slate-500">Berlaku untuk seluruh siswa kecuali mitra DU/DI mengaktifkan jam kerja khusus.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Check-In Aktif</label>
                    <input type="time" step="1" name="checkin_start" value="{{ $settings['checkin_start'] ?? '06:00:00' }}" required class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] text-slate-400 block mt-1">Awal tombol aktif</span>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Batas Tepat Waktu</label>
                    <input type="time" step="1" name="checkin_late" value="{{ $settings['checkin_late'] ?? '08:00:00' }}" required class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] text-amber-600 block mt-1">Melewati ini: Terlambat</span>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Batas Maksimal Masuk</label>
                    <input type="time" step="1" name="checkin_alfa" value="{{ $settings['checkin_alfa'] ?? '11:00:00' }}" required class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] text-rose-600 block mt-1">Melewati ini: Alfa</span>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Check-Out Aktif</label>
                    <input type="time" step="1" name="checkout_start" value="{{ $settings['checkout_start'] ?? '16:00:00' }}" required class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] text-slate-400 block mt-1">Waktu awal pulang</span>
                </div>
            </div>
        </div>

        <!-- 3. Aturan Jurnal & Bobot Penilaian -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
            <h3 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">3. Aturan Jurnal & Bobot Penilaian</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Toleransi Backdate Jurnal (Hari)</label>
                    <input type="number" name="journal_backdate_limit" value="{{ $settings['journal_backdate_limit'] ?? 3 }}" min="0" max="30" required class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] text-slate-400 block mt-1">Default: 3 hari ke belakang</span>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Bobot DU/DI (%)</label>
                    <input type="number" name="weight_dudi" value="{{ $settings['weight_dudi'] ?? 60 }}" min="0" max="100" required class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] text-indigo-600 block mt-1">Default: 60%</span>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Bobot Sekolah / Guru (%)</label>
                    <input type="number" name="weight_guru" value="{{ $settings['weight_guru'] ?? 40 }}" min="0" max="100" required class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] text-indigo-600 block mt-1">Default: 40%</span>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs tracking-wide shadow-md shadow-indigo-200 transition-all active:scale-[0.98]">
                Simpan Semua Perubahan
            </button>
        </div>
    </form>

</div>
@endsection
