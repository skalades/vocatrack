@extends('layouts.student-mobile')

@section('title', 'Absensi — VokaTrack Siswa')

@section('content')
<div class="space-y-4">

    <!-- Header Title -->
    <div>
        <h2 class="text-base font-extrabold text-slate-900">Presensi Harian</h2>
        <p class="text-xs text-slate-500">Rekam kehadiran Anda dengan foto selfie langsung dan koordinat GPS.</p>
    </div>

    <!-- Check-In Module Card -->
    <div id="checkin-section" class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm">
        @if(!$todayAttendance)
            <div class="space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <span class="text-xs font-bold text-slate-800">Check-In Hari Ini</span>
                        <p class="text-[11px] text-slate-500">{{ \Carbon\Carbon::today()->isoFormat('dddd, D MMMM Y') }}</p>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        Belum Presensi
                    </span>
                </div>

                <!-- GPS Status Card -->
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-700 flex items-center space-x-1">
                            <span>📍</span> <span>Lokasi Anda</span>
                        </span>
                        <span id="gps-status-badge" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-600">
                            Mencari GPS...
                        </span>
                    </div>
                    <p id="gps-coords-text" class="text-[11px] font-mono text-slate-500">Mengambil koordinat satelit perangkat...</p>
                    @if($spkl->company->gps_enabled)
                        <p class="text-[10px] text-slate-400">
                            Titik DU/DI: {{ $spkl->company->name }} (Radius Maks: {{ $spkl->company->radius_meters }} meter)
                        </p>
                    @endif
                </div>

                <!-- Outside Radius Warning & Reason Form (PRD F-05 requirement) -->
                <div id="outside-radius-box" class="hidden p-3 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 space-y-2">
                    <div class="flex items-center space-x-2">
                        <span class="text-base">⚠️</span>
                        <div>
                            <span class="text-xs font-bold block">Peringatan di Luar Radius Kantor</span>
                            <span class="text-[10px] text-amber-800">Jarak Anda melebihi radius lokasi DU/DI. Wajib mengisi alasan lokasi.</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-amber-900 mb-1">Alasan Lokasi di Luar Radius</label>
                        <input type="text" name="location_reason" id="input-location-reason" oninput="validateForm()" placeholder="Misal: Penugasan proyek di klien / izin dinas lapangan..." class="w-full text-xs p-2 rounded-xl bg-white border border-amber-300 focus:outline-none focus:ring-1 focus:ring-amber-500">
                    </div>
                </div>

                <!-- Photo Section -->
                <div class="space-y-3">
                    <div class="relative w-full aspect-4/3 rounded-2xl overflow-hidden bg-slate-900 border border-slate-800 flex items-center justify-center">
                        <video id="webcam-video" autoplay playsinline class="w-full h-full object-cover hidden"></video>
                        <canvas id="photo-canvas" class="hidden"></canvas>
                        <img id="photo-preview" class="hidden w-full h-full object-cover" alt="Preview Foto">

                        <!-- Overlay Placeholder -->
                        <div id="camera-overlay" class="absolute inset-0 bg-slate-900/80 backdrop-blur-xs flex flex-col items-center justify-center p-4 text-center">
                            <span class="text-3xl mb-2">📷</span>
                            <p class="text-xs font-bold text-white mb-1">Ambil Foto Selfie Kehadiran</p>
                            <p class="text-[10px] text-slate-300 mb-3 max-w-[220px]">Gunakan kamera browser langsung atau ambil dari kamera perangkat Anda.</p>
                            <div class="flex flex-col sm:flex-row gap-2 w-full max-w-xs">
                                <button type="button" onclick="startCamera()" class="flex-1 px-3 py-2 rounded-xl bg-indigo-600 text-white font-bold text-xs shadow-md shadow-indigo-500/50 hover:bg-indigo-500">
                                    Buka Kamera Langsung
                                </button>
                                <label class="flex-1 px-3 py-2 rounded-xl bg-slate-700 hover:bg-slate-600 text-white font-bold text-xs text-center cursor-pointer">
                                    <span>Pilih / Foto File</span>
                                    <input type="file" accept="image/*" capture="user" onchange="handleFileInput(event)" class="hidden">
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Snapshot / Retake Controls -->
                    <div id="camera-controls" class="hidden flex space-x-2">
                        <button type="button" onclick="takeSnapshot()" id="btn-snap" class="flex-1 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-200 flex items-center justify-center space-x-1">
                            <span>📸 Ambil Jepretan</span>
                        </button>
                        <button type="button" onclick="retakeSnapshot()" id="btn-retake" class="hidden flex-1 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs flex items-center justify-center space-x-1">
                            <span>🔄 Foto Ulang</span>
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

                    <button type="submit" id="btn-submit-checkin" disabled class="w-full min-h-[48px] py-3 px-4 rounded-2xl bg-slate-300 text-slate-500 font-bold text-xs tracking-wide cursor-not-allowed transition-all flex items-center justify-center space-x-2">
                        <span>Ambil Selfie Dulu untuk Check-In</span>
                    </button>
                </form>
            </div>
        @elseif($todayAttendance && !$todayAttendance->checkout_time)
            <!-- Section Checkout -->
            <div class="space-y-3">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <span class="text-xs font-bold text-slate-800">Check-In Tercatat</span>
                        <p class="text-[11px] text-slate-500">Masuk pada: {{ substr($todayAttendance->checkin_time, 0, 5) }} WIB</p>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        {{ strtoupper($todayAttendance->status) }}
                    </span>
                </div>

                <div class="p-4 rounded-2xl bg-amber-50/70 border border-amber-200/80 space-y-3">
                    <div class="flex items-center space-x-2 text-amber-800">
                        <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-xs font-bold">Waktunya Check-Out Pulang?</span>
                    </div>
                    <p class="text-xs text-amber-900 leading-relaxed">
                        Pastikan Anda telah merapikan pekerjaan sebelum check-out. Tuliskan ringkasan singkat capaian Anda.
                    </p>
                    <form action="{{ route('student.checkout') }}" method="POST" class="space-y-2.5">
                        @csrf
                        <textarea name="checkout_notes" rows="2" required maxlength="255" placeholder="Contoh: Menyelesaikan tugas modul 4 dan berkoordinasi dengan tim..." class="w-full text-xs p-3 rounded-xl bg-white border border-amber-200 focus:outline-none focus:ring-2 focus:ring-amber-500"></textarea>
                        <button type="submit" class="w-full min-h-[48px] py-2.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-md shadow-amber-200">
                            Check-Out Sekarang
                        </button>
                    </form>
                </div>
            </div>
        @else
            <!-- Checked out -->
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-center space-y-2">
                <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-lg mx-auto">
                    ✓
                </div>
                <h4 class="text-xs font-bold text-emerald-900">Presensi Hari Ini Sudah Lengkap!</h4>
                <p class="text-[11px] text-emerald-700">
                    Check-in: {{ substr($todayAttendance->checkin_time, 0, 5) }} WIB &bull; Check-out: {{ substr($todayAttendance->checkout_time, 0, 5) }} WIB
                </p>
                @if($todayAttendance->checkout_notes)
                    <p class="text-[10px] text-slate-500 italic bg-white p-2 rounded-lg border border-emerald-100">
                        "{{ $todayAttendance->checkout_notes }}"
                    </p>
                @endif
            </div>
        @endif
    </div>

    <!-- Attendance History List -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm space-y-3">
        <h3 class="text-xs font-bold text-slate-800">Riwayat Presensi</h3>

        @if($history->isEmpty())
            <p class="text-xs text-slate-400 py-3 text-center">Belum ada riwayat kehadiran.</p>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($history as $item)
                    <div class="py-2.5 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-slate-800 block">
                                {{ \Carbon\Carbon::parse($item->date)->isoFormat('dddd, D MMM Y') }}
                            </span>
                            <span class="text-[10px] text-slate-400 block">
                                Masuk: {{ $item->checkin_time ? substr($item->checkin_time, 0, 5) : '-' }} &bull; Pulang: {{ $item->checkout_time ? substr($item->checkout_time, 0, 5) : '-' }}
                            </span>
                        </div>
                        @php
                            $badge = match($item->status) {
                                'hadir' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'terlambat' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'izin' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'sakit' => 'bg-purple-50 text-purple-700 border-purple-200',
                                'dinas_luar' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                                'luar_kantor' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                default => 'bg-rose-50 text-rose-700 border-rose-200'
                            };
                        @endphp
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $badge }}">
                            {{ strtoupper(str_replace('_', ' ', $item->status)) }}
                        </span>
                    </div>
                @endforeach
            </div>
            <div class="pt-2">
                {{ $history->links() }}
            </div>
        @endif
    </div>

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
            })
            .catch(err => {
                alert('Akses kamera tidak diizinkan atau tidak didukung pada browser ini. Anda dapat menggunakan opsi "Pilih / Foto File".');
                video.classList.add('hidden');
            });
    }

    function handleFileInput(e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(evt) {
            photoData = evt.target.result;
            const preview = document.getElementById('photo-preview');
            preview.src = photoData;
            preview.classList.remove('hidden');
            document.getElementById('camera-overlay').classList.add('hidden');
            document.getElementById('input-photo').value = photoData;
            document.getElementById('camera-controls').classList.remove('hidden');
            document.getElementById('btn-snap').classList.add('hidden');
            document.getElementById('btn-retake').classList.remove('hidden');
            validateForm();
        };
        reader.readAsDataURL(file);
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
        preview.classList.remove('hidden');
        video.classList.add('hidden');

        document.getElementById('input-photo').value = photoData;
        document.getElementById('btn-snap').classList.add('hidden');
        document.getElementById('btn-retake').classList.remove('hidden');

        validateForm();
    }

    function retakeSnapshot() {
        const video = document.getElementById('webcam-video');
        const preview = document.getElementById('photo-preview');

        preview.classList.add('hidden');
        photoData = null;
        document.getElementById('input-photo').value = '';

        document.getElementById('camera-overlay').classList.remove('hidden');
        document.getElementById('camera-controls').classList.add('hidden');
        document.getElementById('btn-snap').classList.remove('hidden');
        document.getElementById('btn-retake').classList.add('hidden');

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
                        badge.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200';
                        badge.innerText = 'Dalam Radius (' + Math.round(dist) + 'm)';
                        reasonBox.classList.add('hidden');
                    } else {
                        isOutside = true;
                        badge.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200';
                        badge.innerText = 'Luar Radius (' + Math.round(dist) + 'm)';
                        reasonBox.classList.remove('hidden');
                    }
                } else {
                    const badge = document.getElementById('gps-status-badge');
                    badge.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700';
                    badge.innerText = 'GPS Terdeteksi';
                }

                validateForm();
            },
            err => {
                const badge = document.getElementById('gps-status-badge');
                badge.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700';
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
            submitBtn.className = 'w-full min-h-[48px] py-3 px-4 rounded-2xl bg-slate-300 text-slate-500 font-bold text-xs tracking-wide cursor-not-allowed flex items-center justify-center space-x-2';
            submitBtn.innerHTML = '<span>Ambil Selfie Dulu untuk Check-In</span>';
            return;
        }

        if (isOutside && (!reasonInput || !reasonInput.value.trim())) {
            submitBtn.disabled = true;
            submitBtn.className = 'w-full min-h-[48px] py-3 px-4 rounded-2xl bg-amber-200 text-amber-800 font-bold text-xs tracking-wide cursor-not-allowed flex items-center justify-center space-x-2';
            submitBtn.innerHTML = '<span>Isi Alasan Luar Radius Terlebih Dahulu</span>';
            return;
        }

        submitBtn.disabled = false;
        submitBtn.className = 'w-full min-h-[48px] py-3 px-4 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs tracking-wide shadow-md shadow-indigo-200 cursor-pointer flex items-center justify-center space-x-2 transition-transform active:scale-[0.98]';
        submitBtn.innerHTML = '<span>Kirim Check-In Sekarang</span>';
    }
</script>
@endpush
