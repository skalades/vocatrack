@extends('layouts.student-mobile')

@section('title', 'Beranda Siswa — VokaTrack')

@section('content')
<div class="space-y-4">

    <!-- Greeting Card -->
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 rounded-3xl p-5 text-white shadow-lg shadow-indigo-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-indigo-200 font-medium">Selamat Datang,</p>
                <h2 class="text-lg font-extrabold tracking-tight">{{ auth()->user()->name }}</h2>
                <p class="text-[11px] text-indigo-100 mt-0.5">
                    {{ $spkl->student->class->name ?? 'Kelas' }} &bull; {{ $spkl->company->name ?? 'Mitra DU/DI' }}
                </p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center text-xl font-bold border border-white/20">
                👨‍🎓
            </div>
        </div>
    </div>

    <!-- 1. Card Status Hari Ini -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Status Presensi Hari Ini</span>
                <p class="text-xs font-semibold text-slate-700">{{ \Carbon\Carbon::today()->isoFormat('dddd, D MMMM Y') }}</p>
            </div>

            @if($todayAttendance)
                @php
                    $statusColor = match($todayAttendance->status) {
                        'hadir' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'terlambat' => 'bg-amber-50 text-amber-700 border-amber-200',
                        'izin' => 'bg-blue-50 text-blue-700 border-blue-200',
                        'sakit' => 'bg-purple-50 text-purple-700 border-purple-200',
                        'dinas_luar' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                        'luar_kantor' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                        default => 'bg-rose-50 text-rose-700 border-rose-200'
                    };
                @endphp
                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $statusColor }}">
                    {{ strtoupper(str_replace('_', ' ', $todayAttendance->status)) }}
                </span>
            @else
                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                    Belum Absen
                </span>
            @endif
        </div>

        <!-- Checkin & Checkout times -->
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="bg-slate-50 rounded-2xl p-3 border border-slate-100 text-center">
                <span class="text-[10px] font-bold text-slate-400 uppercase block mb-0.5">Check-In</span>
                <span class="text-sm font-extrabold text-slate-800">
                    {{ $todayAttendance && $todayAttendance->checkin_time ? substr($todayAttendance->checkin_time, 0, 5) . ' WIB' : '--:--' }}
                </span>
            </div>
            <div class="bg-slate-50 rounded-2xl p-3 border border-slate-100 text-center">
                <span class="text-[10px] font-bold text-slate-400 uppercase block mb-0.5">Check-Out</span>
                <span class="text-sm font-extrabold text-slate-800">
                    {{ $todayAttendance && $todayAttendance->checkout_time ? substr($todayAttendance->checkout_time, 0, 5) . ' WIB' : '--:--' }}
                </span>
            </div>
        </div>

        <!-- Primary Action Button (min 48px height) -->
        @if(!$todayAttendance)
            <a href="{{ route('student.attendance') }}#checkin-section" class="w-full min-h-[48px] py-3 px-4 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm tracking-wide shadow-md shadow-indigo-200 flex items-center justify-center space-x-2 transition-transform active:scale-[0.98]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>📷 Ambil Selfie & Check-In</span>
            </a>
        @elseif($todayAttendance && !$todayAttendance->checkout_time)
            <button onclick="document.getElementById('checkout-modal').classList.remove('hidden')" class="w-full min-h-[48px] py-3 px-4 rounded-2xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-sm tracking-wide shadow-md shadow-amber-200 flex items-center justify-center space-x-2 transition-transform active:scale-[0.98]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                <span>Lakukan Check-Out Hari Ini</span>
            </button>
        @else
            <div class="w-full min-h-[48px] py-2.5 px-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 font-bold text-xs flex items-center justify-center space-x-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Presensi Hari Ini Telah Lengkap</span>
            </div>
        @endif
    </div>

    <!-- 2. Baris Permohonan Cepat -->
    <div>
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-1">Permohonan Presensi Cepat</p>
        <div class="grid grid-cols-4 gap-2">
            <button onclick="openRequestModal('izin')" class="flex flex-col items-center justify-center p-2.5 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:border-indigo-300 transition-colors">
                <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-bold mb-1">📝</span>
                <span class="text-[10px] font-bold text-slate-700">Izin</span>
            </button>
            <button onclick="openRequestModal('sakit')" class="flex flex-col items-center justify-center p-2.5 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:border-indigo-300 transition-colors">
                <span class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-sm font-bold mb-1">🩺</span>
                <span class="text-[10px] font-bold text-slate-700">Sakit</span>
            </button>
            <button onclick="openRequestModal('dinas_luar')" class="flex flex-col items-center justify-center p-2.5 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:border-indigo-300 transition-colors">
                <span class="w-8 h-8 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-sm font-bold mb-1">🚗</span>
                <span class="text-[10px] font-bold text-slate-700">Dinas Luar</span>
            </button>
            <button onclick="openRequestModal('luar_kantor')" class="flex flex-col items-center justify-center p-2.5 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:border-indigo-300 transition-colors">
                <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-bold mb-1">🏠</span>
                <span class="text-[10px] font-bold text-slate-700">WFH</span>
            </button>
        </div>
    </div>

    <!-- 3. Card Status Jurnal Hari Ini -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Jurnal Kegiatan Hari Ini</span>
            @if($todayJournal)
                @php
                    $jurnalBadge = match($todayJournal->status) {
                        'draft' => 'bg-slate-100 text-slate-700 border-slate-200',
                        'submitted' => 'bg-amber-50 text-amber-700 border-amber-200',
                        'review_guru' => 'bg-blue-50 text-blue-700 border-blue-200',
                        'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'revision' => 'bg-rose-50 text-rose-700 border-rose-200',
                        default => 'bg-slate-100 text-slate-600'
                    };
                @endphp
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $jurnalBadge }}">
                    {{ strtoupper($todayJournal->status) }}
                </span>
            @else
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                    Belum Diisi
                </span>
            @endif
        </div>

        @if($todayJournal)
            <p class="text-xs text-slate-600 line-clamp-2 mb-3 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                {{ $todayJournal->activity_description }}
            </p>
            <a href="{{ route('student.journals') }}" class="inline-flex items-center text-xs font-bold text-indigo-600 hover:text-indigo-700">
                <span>Buka Detail Jurnal</span>
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        @else
            <p class="text-xs text-slate-500 mb-3">
                Dokumentasikan aktivitas belajar dan praktik kerja industri Anda untuk hari ini.
            </p>
            <a href="{{ route('student.journals') }}" class="w-full py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs flex items-center justify-center space-x-2 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Tulis Jurnal Harian</span>
            </a>
        @endif
    </div>

    <!-- 4. Card Rekap Presensi Bulan Berjalan -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Rekap Bulan Berjalan</span>
            <span class="text-xs font-bold text-indigo-600">{{ \Carbon\Carbon::today()->isoFormat('MMMM Y') }}</span>
        </div>
        <div class="grid grid-cols-5 gap-2 text-center">
            <div class="bg-emerald-50/70 border border-emerald-100 rounded-2xl p-2">
                <span class="text-sm font-black text-emerald-700 block">{{ $stats['hadir'] }}</span>
                <span class="text-[9px] font-bold text-emerald-800 uppercase block">Hadir</span>
            </div>
            <div class="bg-amber-50/70 border border-amber-100 rounded-2xl p-2">
                <span class="text-sm font-black text-amber-700 block">{{ $stats['terlambat'] }}</span>
                <span class="text-[9px] font-bold text-amber-800 uppercase block">Terlambat</span>
            </div>
            <div class="bg-blue-50/70 border border-blue-100 rounded-2xl p-2">
                <span class="text-sm font-black text-blue-700 block">{{ $stats['izin'] }}</span>
                <span class="text-[9px] font-bold text-blue-800 uppercase block">Izin</span>
            </div>
            <div class="bg-purple-50/70 border border-purple-100 rounded-2xl p-2">
                <span class="text-sm font-black text-purple-700 block">{{ $stats['sakit'] }}</span>
                <span class="text-[9px] font-bold text-purple-800 uppercase block">Sakit</span>
            </div>
            <div class="bg-rose-50/70 border border-rose-100 rounded-2xl p-2">
                <span class="text-sm font-black text-rose-700 block">{{ $stats['alfa'] }}</span>
                <span class="text-[9px] font-bold text-rose-800 uppercase block">Alfa</span>
            </div>
        </div>
    </div>

</div>

<!-- Modal Check-Out -->
<div id="checkout-modal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-end sm:items-center justify-center p-4">
    <div class="bg-white w-full max-w-sm rounded-3xl p-5 shadow-2xl">
        <h3 class="text-sm font-bold text-slate-900 mb-1">Check-Out Pekerjaan</h3>
        <p class="text-xs text-slate-500 mb-4">Tuliskan ringkasan capaian kerja hari ini sebelum pulang.</p>
        <form action="{{ route('student.checkout') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Capaian (Maks 255 Karakter)</label>
                <textarea name="checkout_notes" rows="3" required maxlength="255"
                          placeholder="Contoh: Menyelesaikan perbaikan bug modul autentikasi dan merapikan dokumentasi..."
                          class="w-full text-xs p-3 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500"></textarea>
            </div>
            <div class="flex space-x-2">
                <button type="button" onclick="document.getElementById('checkout-modal').classList.add('hidden')" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50">
                    Batal
                </button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold shadow-md shadow-amber-200">
                    Kirim Check-Out
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Quick Request -->
<div id="request-modal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-end sm:items-center justify-center p-4">
    <div class="bg-white w-full max-w-sm rounded-3xl p-5 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-slate-900" id="modal-req-title">Pengajuan Presensi Khusus</h3>
            <button type="button" onclick="closeRequestModal()" class="text-slate-400 hover:text-slate-600 text-sm font-bold">&times;</button>
        </div>
        <form action="{{ route('student.request.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <input type="hidden" name="type" id="modal-req-type" value="izin">

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Mulai Tanggal</label>
                    <input type="date" name="start_date" value="{{ \Carbon\Carbon::today()->toDateString() }}" required class="w-full text-xs p-2 rounded-xl bg-slate-50 border border-slate-200">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ \Carbon\Carbon::today()->toDateString() }}" required class="w-full text-xs p-2 rounded-xl bg-slate-50 border border-slate-200">
                </div>
            </div>

            <div id="dest-container" class="hidden">
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Lokasi Tujuan / Dinas</label>
                <input type="text" name="destination_location" placeholder="Contoh: Kantor Cabang Tangerang" class="w-full text-xs p-2 rounded-xl bg-slate-50 border border-slate-200">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Alasan Lengkap (Min. 10 Karakter)</label>
                <textarea name="reason" rows="3" required placeholder="Jelaskan alasan pengajuan secara rinci..." class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200"></textarea>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Foto Bukti / Surat (Opsional)</label>
                <input type="file" name="attachment" accept="image/*" class="w-full text-[11px] text-slate-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:bg-slate-100 file:text-slate-700 file:font-semibold">
            </div>

            <div class="flex space-x-2 pt-2">
                <button type="button" onclick="closeRequestModal()" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600">
                    Batal
                </button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-200">
                    Ajukan Permohonan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRequestModal(type) {
        document.getElementById('modal-req-type').value = type;
        const titleMap = {
            'izin': 'Permohonan Izin (Verifikator: Guru Pembimbing)',
            'sakit': 'Permohonan Sakit (Verifikator: Guru Pembimbing)',
            'dinas_luar': 'Pengajuan Dinas Luar (Verifikator: DU/DI)',
            'luar_kantor': 'Pengajuan Luar Kantor/WFH (Verifikator: DU/DI)'
        };
        document.getElementById('modal-req-title').innerText = titleMap[type] || 'Pengajuan Presensi';
        const dest = document.getElementById('dest-container');
        if (type === 'dinas_luar') {
            dest.classList.remove('hidden');
        } else {
            dest.classList.add('hidden');
        }
        document.getElementById('request-modal').classList.remove('hidden');
    }

    function closeRequestModal() {
        document.getElementById('request-modal').classList.add('hidden');
    }
</script>
@endsection
