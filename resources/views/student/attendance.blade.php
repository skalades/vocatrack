@extends('layouts.student-mobile')

@section('title', 'Absensi GPS & Kamera — VokaTrack Siswa')

@section('content')
<div class="space-y-4">

    <!-- Header Title -->
    <div>
        <h2 class="text-lg font-black text-slate-900 tracking-tight">Presensi Harian</h2>
        <p class="text-xs text-slate-500 font-medium">Rekam kehadiran dengan swafoto langsung dan validasi lokasi geofencing GPS.</p>
    </div>

    <!-- Check-In Module Card -->
    <div id="checkin-section" class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
        @if(!$todayAttendance)
            <div class="space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <span class="text-xs font-bold text-slate-900 block">Check-In Hari Ini</span>
                        <p class="text-[11px] text-slate-500 font-medium">{{ \Carbon\Carbon::today()->isoFormat('dddd, D MMMM Y') }}</p>
                    </div>
                    <x-badge variant="warning" size="xs">
                        Belum Presensi
                    </x-badge>
                </div>

                <!-- GPS Geofencing Status Card -->
                <div class="p-4 rounded-2xl bg-slate-50/90 border border-slate-200/80 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <div class="w-7 h-7 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <div>
                                <span class="text-xs font-bold text-slate-900 block leading-tight">Geofencing Lokasi</span>
                                <span class="text-[10px] text-slate-500 font-medium">Validasi radius presensi kantor</span>
                            </div>
                        </div>
                        <span id="gps-status-badge" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700 transition-colors">
                            Mencari GPS...
                        </span>
                    </div>

                    <div class="bg-white p-3 rounded-xl border border-slate-200/70 space-y-1.5 shadow-2xs">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-800 truncate">{{ $spkl->company->name ?? 'Mitra DU/DI' }}</span>
                            @if($spkl->company->gps_enabled)
                                <span class="text-[10px] font-extrabold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-100 shrink-0">
                                    Maks: {{ $spkl->company->radius_meters }}m
                                </span>
                            @endif
                        </div>
                        <div class="flex items-center text-[10px] text-slate-500 font-mono gap-1">
                            <span>📍 Posisi:</span>
                            <span id="gps-coords-text" class="truncate font-semibold text-slate-700">Mendeteksi sensor satelit...</span>
                        </div>
                    </div>

                    <!-- Outside Radius Alert Box (Integrated cleanly) -->
                    <div id="outside-radius-box" class="hidden p-3.5 rounded-xl bg-amber-50 border border-amber-200/80 text-amber-900 space-y-2">
                        <div class="flex items-start space-x-2">
                            <span class="w-5 h-5 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 text-xs font-black mt-0.5">!</span>
                            <div>
                                <span class="text-xs font-bold text-amber-950 block">Di Luar Radius Kantor DU/DI</span>
                                <span class="text-[11px] text-amber-800 font-medium leading-relaxed block mt-0.5">
                                    Jarak Anda melebihi batas radius mitra. Wajib menuliskan alasan penugasan luar kantor:
                                </span>
                            </div>
                        </div>
                        <div>
                            <input type="text" name="location_reason" id="input-location-reason" oninput="validateForm()" 
                                   placeholder="Contoh: Dinas luar di lokasi klien / instalasi teknis..." 
                                   class="w-full text-xs p-2.5 rounded-xl bg-white border border-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 text-slate-900 font-medium placeholder-slate-400">
                        </div>
                    </div>
                </div>

                <!-- Camera / Swafoto Viewport -->
                <div class="space-y-3">
                    <div class="relative w-full aspect-4/3 rounded-3xl overflow-hidden bg-gradient-to-b from-slate-900 to-slate-950 border border-slate-800 flex items-center justify-center shadow-lg">
                        <!-- Video Stream -->
                        <video id="webcam-video" autoplay playsinline class="w-full h-full object-cover hidden"></video>
                        <canvas id="photo-canvas" class="hidden"></canvas>
                        
                        <!-- Photo Preview -->
                        <div id="photo-preview-wrapper" class="hidden relative w-full h-full">
                            <img id="photo-preview" class="w-full h-full object-cover" alt="Preview Foto Selfie">
                            <div class="absolute top-3 left-3 bg-emerald-500/90 backdrop-blur-md text-white text-[10px] font-bold px-2.5 py-1 rounded-full shadow-md flex items-center gap-1">
                                <svg class="w-3 h-3 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Foto Siap Dikirim</span>
                            </div>
                        </div>

                        <!-- Face Guideline Oval (Visible during live camera stream) -->
                        <div id="face-guide" class="hidden pointer-events-none absolute inset-x-12 inset-y-6 border-2 border-dashed border-white/60 rounded-[48%] flex flex-col items-center justify-end pb-3 z-10 transition-opacity">
                            <span class="px-3 py-1 rounded-full bg-slate-900/70 backdrop-blur-md text-[10px] font-bold text-white tracking-wide shadow-sm">
                                Posisikan Wajah di Sini
                            </span>
                        </div>

                        <!-- Viewport Placeholder (Before Camera Started) -->
                        <div id="camera-overlay" class="absolute inset-0 flex flex-col items-center justify-center p-6 text-center z-10">
                            <!-- Viewfinder Corner Brackets -->
                            <div class="pointer-events-none absolute inset-4">
                                <div class="w-5 h-5 border-t-2 border-l-2 border-indigo-400 rounded-tl-lg absolute top-0 left-0"></div>
                                <div class="w-5 h-5 border-t-2 border-r-2 border-indigo-400 rounded-tr-lg absolute top-0 right-0"></div>
                                <div class="w-5 h-5 border-b-2 border-l-2 border-indigo-400 rounded-bl-lg absolute bottom-0 left-0"></div>
                                <div class="w-5 h-5 border-b-2 border-r-2 border-indigo-400 rounded-br-lg absolute bottom-0 right-0"></div>
                            </div>

                            <div class="relative mb-3">
                                <div class="w-14 h-14 rounded-2xl bg-indigo-500/20 border border-indigo-400/30 text-indigo-300 flex items-center justify-center shadow-lg shadow-indigo-500/10">
                                    <svg class="w-7 h-7 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </div>
                            </div>
                            
                            <p class="text-xs font-bold text-white mb-1">Ambil Swafoto Kehadiran</p>
                            <p class="text-[11px] text-slate-300 mb-4 max-w-[240px] leading-relaxed">
                                Wajib swafoto langsung menggunakan kamera browser untuk verifikasi kehadiran.
                            </p>
                            
                            <div class="w-full max-w-xs">
                                <button type="button" onclick="startCamera()" class="w-full py-3 px-5 rounded-2xl bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition-all cursor-pointer flex items-center justify-center space-x-2 active:scale-95">
                                    <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span>Buka Kamera Sekarang</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Snapshot / Retake Controls -->
                    <div id="camera-controls" class="hidden flex space-x-2">
                        <button type="button" onclick="takeSnapshot()" id="btn-snap" class="flex-1 py-3.5 rounded-2xl bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white font-bold text-xs shadow-lg shadow-indigo-600/25 flex items-center justify-center space-x-2 cursor-pointer transition-all active:scale-95">
                            <span class="w-3 h-3 rounded-full bg-white animate-pulse"></span>
                            <span>Ambil Jepretan Wajah</span>
                        </button>
                        <button type="button" onclick="retakeSnapshot()" id="btn-retake" class="hidden flex-1 py-3.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs flex items-center justify-center space-x-2 cursor-pointer border border-slate-200 transition-all active:scale-95">
                            <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Foto Ulang</span>
                        </button>
                    </div>
                </div>

                <!-- Submit Form -->
                <form action="{{ route('student.checkin') }}" method="POST" id="form-checkin" class="space-y-3">
                    @csrf
                    <input type="hidden" name="latitude" id="input-lat">
                    <input type="hidden" name="longitude" id="input-lng">
                    <input type="hidden" name="photo" id="input-photo">
                    <input type="hidden" name="reason" id="input-form-reason">

                    <button type="submit" id="btn-submit-checkin" disabled 
                            class="w-full min-h-[48px] py-3.5 px-4 rounded-2xl bg-slate-100 text-slate-400 border border-slate-200 font-bold text-xs tracking-wide cursor-not-allowed transition-all flex items-center justify-center space-x-2">
                        <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Ambil Selfie Dulu untuk Check-In</span>
                    </button>
                </form>

                <!-- Shortcut Link to Leave/Sick Request if cannot attend -->
                <div class="pt-2 text-center border-t border-slate-100">
                    <p class="text-[11px] text-slate-500 font-medium">
                        Berhalangan hadir atau sedang sakit?
                        <button type="button" onclick="openRequestModal('izin')" class="font-bold text-indigo-600 hover:text-indigo-700 underline cursor-pointer ml-0.5">
                            Ajukan Izin / Sakit Di Sini
                        </button>
                    </p>
                </div>
            </div>
        @elseif($todayAttendance && !$todayAttendance->checkout_time)
            <!-- Section Checkout Form -->
            <div class="space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <span class="text-xs font-bold text-slate-900 block">Check-In Telah Tercatat</span>
                        <p class="text-[11px] text-slate-500 font-medium">Jam Masuk: {{ substr($todayAttendance->checkin_time, 0, 5) }} WIB</p>
                    </div>
                    <x-badge :variant="$todayAttendance->status" size="sm">
                        {{ strtoupper($todayAttendance->status) }}
                    </x-badge>
                </div>

                <div class="p-4 rounded-2xl bg-amber-50/70 border border-amber-200/80 space-y-3">
                    <div class="flex items-center space-x-2 text-amber-900">
                        <svg class="w-4 h-4 text-amber-600 shrink-0 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-xs font-bold">Waktunya Check-Out Pulang?</span>
                    </div>
                    <p class="text-xs text-amber-900/90 leading-relaxed font-medium">
                        Pastikan seluruh pekerjaan telah selesai dirapikan. Tuliskan ringkasan singkat capaian Anda sebelum pulang.
                    </p>
                    <form action="{{ route('student.checkout') }}" method="POST" class="space-y-3">
                        @csrf
                        <textarea name="checkout_notes" rows="2" required maxlength="255" 
                                  placeholder="Contoh: Menyelesaikan perbaikan modul 4 dan berkoordinasi dengan mentor..." 
                                  class="w-full text-xs p-3 rounded-xl bg-white border border-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-500/20 font-medium text-slate-800 leading-relaxed"></textarea>
                        <button type="submit" class="w-full min-h-[48px] py-3 px-4 rounded-2xl bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white font-bold text-xs tracking-wide shadow-md shadow-amber-500/20 cursor-pointer transition-transform active:scale-[0.98]">
                            Check-Out Pulang Sekarang
                        </button>
                    </form>
                </div>
            </div>
        @else
            <!-- Completely Checked Out -->
            <div class="p-5 rounded-2xl bg-emerald-50/80 border border-emerald-200/80 text-center space-y-2">
                <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-lg mx-auto shadow-2xs">
                    ✓
                </div>
                <h4 class="text-xs font-extrabold text-emerald-950">Presensi Hari Ini Sudah Lengkap!</h4>
                <p class="text-[11px] text-emerald-800 font-medium">
                    Masuk: {{ substr($todayAttendance->checkin_time, 0, 5) }} WIB &bull; Pulang: {{ substr($todayAttendance->checkout_time, 0, 5) }} WIB
                </p>
                @if($todayAttendance->checkout_notes)
                    <p class="text-[11px] text-slate-600 italic bg-white p-3 rounded-xl border border-emerald-100 font-medium leading-relaxed">
                        "{{ $todayAttendance->checkout_notes }}"
                    </p>
                @endif
            </div>
        @endif
    </div>

    <!-- Permohonan Presensi Cepat (Izin, Sakit, Dinas Luar, WFH) -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xs font-extrabold text-slate-900 tracking-tight">Permohonan Presensi Cepat</h3>
                <p class="text-[11px] text-slate-500 font-medium">Berhalangan hadir atau dinas luar? Ajukan izin ke pembimbing.</p>
            </div>
        </div>
        
        <div class="grid grid-cols-4 gap-2">
            <button type="button" onclick="openRequestModal('izin')" class="flex flex-col items-center justify-center p-3 rounded-2xl bg-slate-50/70 hover:bg-white border border-slate-200/80 hover:border-sky-300 shadow-2xs hover:shadow-xs transition-all cursor-pointer group active:scale-95">
                <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 border border-sky-100 flex items-center justify-center mb-1.5 group-hover:scale-105 transition-transform">
                    <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <span class="text-[11px] font-bold text-slate-800 group-hover:text-sky-700 transition-colors">Izin</span>
            </button>

            <button type="button" onclick="openRequestModal('sakit')" class="flex flex-col items-center justify-center p-3 rounded-2xl bg-slate-50/70 hover:bg-white border border-slate-200/80 hover:border-purple-300 shadow-2xs hover:shadow-xs transition-all cursor-pointer group active:scale-95">
                <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center mb-1.5 group-hover:scale-105 transition-transform">
                    <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                </div>
                <span class="text-[11px] font-bold text-slate-800 group-hover:text-purple-700 transition-colors">Sakit</span>
            </button>

            <button type="button" onclick="openRequestModal('dinas_luar')" class="flex flex-col items-center justify-center p-3 rounded-2xl bg-slate-50/70 hover:bg-white border border-slate-200/80 hover:border-teal-300 shadow-2xs hover:shadow-xs transition-all cursor-pointer group active:scale-95">
                <div class="w-9 h-9 rounded-xl bg-teal-50 text-teal-600 border border-teal-100 flex items-center justify-center mb-1.5 group-hover:scale-105 transition-transform">
                    <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <span class="text-[11px] font-bold text-slate-800 group-hover:text-teal-700 transition-colors">Dinas</span>
            </button>

            <button type="button" onclick="openRequestModal('luar_kantor')" class="flex flex-col items-center justify-center p-3 rounded-2xl bg-slate-50/70 hover:bg-white border border-slate-200/80 hover:border-indigo-300 shadow-2xs hover:shadow-xs transition-all cursor-pointer group active:scale-95">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center mb-1.5 group-hover:scale-105 transition-transform">
                    <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                </div>
                <span class="text-[11px] font-bold text-slate-800 group-hover:text-indigo-700 transition-colors">WFH</span>
            </button>
        </div>
    </div>

    <!-- Status Pengajuan Presensi Terkini -->
    @if(isset($pendingRequests) && $pendingRequests->isNotEmpty())
        <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-extrabold text-slate-900 tracking-tight">Status Pengajuan Terkini</h3>
                    <p class="text-[11px] text-slate-500 font-medium">Permohonan izin atau dispensasi yang telah diajukan.</p>
                </div>
                <span class="text-[10px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">{{ $pendingRequests->count() }} Pengajuan</span>
            </div>

            <div class="space-y-2.5">
                @foreach($pendingRequests as $req)
                    <div class="p-3.5 rounded-2xl bg-slate-50/90 border border-slate-200/70 space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center space-x-2">
                                <x-badge :variant="$req->type" size="xs">
                                    {{ strtoupper(str_replace('_', ' ', $req->type)) }}
                                </x-badge>
                                <span class="text-[11px] font-bold text-slate-700">
                                    {{ \Carbon\Carbon::parse($req->start_date)->isoFormat('D MMM') }}
                                    @if($req->start_date !== $req->end_date)
                                        - {{ \Carbon\Carbon::parse($req->end_date)->isoFormat('D MMM Y') }}
                                    @else
                                        {{ \Carbon\Carbon::parse($req->start_date)->isoFormat('Y') }}
                                    @endif
                                </span>
                            </div>
                            <x-badge :variant="$req->status" size="xs">
                                {{ $req->status === 'pending' ? 'Menunggu' : ($req->status === 'approved' ? 'Disetujui' : 'Ditolak') }}
                            </x-badge>
                        </div>

                        <p class="text-xs text-slate-600 font-medium leading-relaxed">
                            {{ $req->reason }}
                        </p>

                        @if($req->reviewer_notes)
                            <div class="text-[11px] text-slate-600 bg-white p-2.5 rounded-xl border border-slate-200/60 font-medium">
                                <span class="font-bold text-slate-800">Catatan Pembimbing:</span> {{ $req->reviewer_notes }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Attendance History List -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs space-y-3">
        <h3 class="text-xs font-extrabold text-slate-900 tracking-tight">Riwayat Presensi</h3>

        @if($history->isEmpty())
            <x-empty-state 
                title="Belum Ada Presensi" 
                description="Belum ada riwayat kehadiran yang tercatat untuk akun Anda."
                class="py-6"
            />
        @else
            <div class="divide-y divide-slate-100">
                @foreach($history as $item)
                    <div class="py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <span class="text-xs font-bold text-slate-900 block truncate">
                                {{ \Carbon\Carbon::parse($item->date)->isoFormat('dddd, D MMM Y') }}
                            </span>
                            <span class="text-[10px] text-slate-500 font-mono block mt-0.5">
                                Masuk: {{ $item->checkin_time ? substr($item->checkin_time, 0, 5) : '-' }} &bull; Pulang: {{ $item->checkout_time ? substr($item->checkout_time, 0, 5) : '-' }}
                            </span>
                        </div>
                        <x-badge :variant="$item->status" size="xs">
                            {{ strtoupper(str_replace('_', ' ', $item->status)) }}
                        </x-badge>
                    </div>
                @endforeach
            </div>
            <x-pagination :paginator="$history" />
        @endif
    </div>

    <!-- Modal Quick Request (Reusable Component) -->
    <x-attendance-request-modal />

</div>
@endsection

@push('scripts')
<script>
    let streamObj = null;
    let userLat = null;
    let userLng = null;
    let photoData = null;
    let isOutside = false;

    const targetLat = {{ $spkl->company->latitude ?? -6.2235 }};
    const targetLng = {{ $spkl->company->longitude ?? 106.8310 }};
    const maxRadius = {{ $spkl->company->radius_meters ?? 150 }};
    const gpsEnabled = {{ $spkl->company->gps_enabled ? 'true' : 'false' }};

    function startCamera() {
        const video = document.getElementById('webcam-video');
        video.classList.remove('hidden');
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false })
            .then(stream => {
                streamObj = stream;
                video.srcObject = stream;
                document.getElementById('camera-overlay').classList.add('hidden');
                document.getElementById('camera-controls').classList.remove('hidden');
                document.getElementById('face-guide').classList.remove('hidden');
                document.getElementById('photo-preview-wrapper').classList.add('hidden');
            })
            .catch(err => {
                alert('Akses kamera tidak diizinkan atau tidak didukung pada browser ini. Harap izinkan akses kamera untuk melakukan absensi kehadiran.');
                video.classList.add('hidden');
            });
    }

    function takeSnapshot() {
        const video = document.getElementById('webcam-video');
        const canvas = document.getElementById('photo-canvas');
        const preview = document.getElementById('photo-preview');

        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        photoData = canvas.toDataURL('image/jpeg', 0.85);
        preview.src = photoData;
        document.getElementById('photo-preview-wrapper').classList.remove('hidden');
        document.getElementById('face-guide').classList.add('hidden');
        video.classList.add('hidden');

        document.getElementById('input-photo').value = photoData;
        document.getElementById('btn-snap').classList.add('hidden');
        document.getElementById('btn-retake').classList.remove('hidden');

        validateForm();
    }

    function retakeSnapshot() {
        const video = document.getElementById('webcam-video');
        document.getElementById('photo-preview-wrapper').classList.add('hidden');
        photoData = null;
        document.getElementById('input-photo').value = '';

        if (streamObj && streamObj.active) {
            video.classList.remove('hidden');
            document.getElementById('face-guide').classList.remove('hidden');
            document.getElementById('camera-overlay').classList.add('hidden');
            document.getElementById('camera-controls').classList.remove('hidden');
            document.getElementById('btn-snap').classList.remove('hidden');
            document.getElementById('btn-retake').classList.add('hidden');
        } else {
            document.getElementById('camera-overlay').classList.remove('hidden');
            document.getElementById('camera-controls').classList.add('hidden');
            document.getElementById('btn-snap').classList.remove('hidden');
            document.getElementById('btn-retake').classList.add('hidden');
        }

        validateForm();
    }

    // Geolocation API
    if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition(
            pos => {
                userLat = pos.coords.latitude;
                userLng = pos.coords.longitude;
                document.getElementById('input-lat').value = userLat;
                document.getElementById('input-lng').value = userLng;

                const coordText = userLat.toFixed(6) + ', ' + userLng.toFixed(6);
                document.getElementById('gps-coords-text').innerText = coordText;

                if (gpsEnabled) {
                    const dist = calculateDistance(userLat, userLng, targetLat, targetLng);
                    const badge = document.getElementById('gps-status-badge');
                    const reasonBox = document.getElementById('outside-radius-box');

                    if (dist <= maxRadius) {
                        isOutside = false;
                        badge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200';
                        badge.innerText = 'Dalam Radius (' + Math.round(dist) + 'm)';
                        reasonBox.classList.add('hidden');
                    } else {
                        isOutside = true;
                        badge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200';
                        badge.innerText = 'Luar Radius (' + Math.round(dist) + 'm)';
                        reasonBox.classList.remove('hidden');
                    }
                } else {
                    const badge = document.getElementById('gps-status-badge');
                    badge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200';
                    badge.innerText = 'GPS Terdeteksi';
                }

                validateForm();
            },
            err => {
                const badge = document.getElementById('gps-status-badge');
                badge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200';
                badge.innerText = 'GPS Default / Sesuai Kantor';
                userLat = targetLat;
                userLng = targetLng;
                document.getElementById('input-lat').value = userLat;
                document.getElementById('input-lng').value = userLng;
                document.getElementById('gps-coords-text').innerText = userLat.toFixed(6) + ', ' + userLng.toFixed(6) + ' (Default DU/DI)';
                validateForm();
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    } else {
        userLat = targetLat;
        userLng = targetLng;
        document.getElementById('input-lat').value = userLat;
        document.getElementById('input-lng').value = userLng;
    }

    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371000;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

    function validateForm() {
        const submitBtn = document.getElementById('btn-submit-checkin');
        if (!submitBtn) return;

        const reasonInput = document.getElementById('input-location-reason');
        if (reasonInput) {
            document.getElementById('input-form-reason').value = reasonInput.value;
        }

        if (!photoData) {
            submitBtn.disabled = true;
            submitBtn.className = 'w-full min-h-[48px] py-3.5 px-4 rounded-2xl bg-slate-100 text-slate-400 border border-slate-200 font-bold text-xs tracking-wide cursor-not-allowed transition-all flex items-center justify-center space-x-2';
            submitBtn.innerHTML = '<svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg><span>Ambil Selfie Dulu untuk Check-In</span>';
            return;
        }

        if (isOutside && (!reasonInput || !reasonInput.value.trim())) {
            submitBtn.disabled = true;
            submitBtn.className = 'w-full min-h-[48px] py-3.5 px-4 rounded-2xl bg-amber-100 text-amber-800 border border-amber-200 font-bold text-xs tracking-wide cursor-not-allowed transition-all flex items-center justify-center space-x-2';
            submitBtn.innerHTML = '<svg class="w-4 h-4 text-amber-600 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg><span>Wajib Isi Alasan Luar Radius</span>';
            return;
        }

        submitBtn.disabled = false;
        submitBtn.className = 'w-full min-h-[48px] py-3.5 px-4 rounded-2xl bg-gradient-to-r from-indigo-600 via-indigo-700 to-indigo-800 hover:opacity-95 text-white font-bold text-xs tracking-wide shadow-lg shadow-indigo-600/30 cursor-pointer flex items-center justify-center space-x-2 transition-all active:scale-[0.98]';
        submitBtn.innerHTML = '<svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span>Kirim Check-In Sekarang</span>';
    }
</script>
@endpush
