<!-- Leaflet Stylesheet & Script (Bundled Locally with CDN Fallback) -->
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" onerror="this.onerror=null;this.href='https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'">
<script src="{{ asset('vendor/leaflet/leaflet.js') }}" onerror="this.onerror=null;this.src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'"></script>

<style>
    /* Scoped Leaflet Controls & Popups inside Minimap */
    #adm-minimap .leaflet-control-zoom {
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08) !important;
        border-radius: 10px !important;
        overflow: hidden !important;
    }
    #adm-minimap .leaflet-control-zoom a {
        width: 26px !important;
        height: 26px !important;
        line-height: 26px !important;
        font-size: 13px !important;
        color: #334155 !important;
        background-color: #ffffff !important;
    }
    #adm-minimap .leaflet-control-zoom a:hover {
        background-color: #f8fafc !important;
        color: #4f46e5 !important;
    }
    #adm-minimap .leaflet-popup-content-wrapper {
        border-radius: 12px !important;
        padding: 2px !important;
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.15) !important;
        border: 1px solid #e2e8f0 !important;
    }
    #adm-minimap .leaflet-popup-content {
        margin: 8px 10px !important;
        line-height: 1.4 !important;
    }
</style>

<!-- Modal Detail Presensi & Geofencing GPS (Modular Reusable Component) -->
<div id="attendance-detail-modal" onclick="if(event.target === this) closeAttendanceDetailModal()" class="hidden fixed inset-0 z-[70] bg-slate-950/70 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4 transition-all duration-200">
    <div class="bg-white w-full max-w-3xl rounded-t-[32px] sm:rounded-3xl shadow-2xl max-h-[92vh] flex flex-col border-t sm:border border-slate-200/80 animate-in slide-in-from-bottom sm:zoom-in-95 duration-200 overflow-hidden" style="padding-bottom: max(1.25rem, env(safe-area-inset-bottom, 16px));">
        
        <!-- Mobile Bottom-Sheet Pull Handle Bar -->
        <div class="w-10 h-1 bg-slate-300 rounded-full mx-auto mt-2.5 mb-1 sm:hidden shrink-0"></div>

        <!-- Header -->
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between shrink-0 bg-slate-50/60">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-sm font-extrabold text-slate-900 tracking-tight truncate">Detail Bukti Presensi & Lokasi</h3>
                    <p class="text-xs text-slate-500 font-medium truncate" id="adm-student-info">Verifikasi swafoto dan validasi titik koordinat GPS</p>
                </div>
            </div>
            <button type="button" onclick="closeAttendanceDetailModal()" class="w-8 h-8 rounded-full bg-white hover:bg-slate-100 border border-slate-200/80 flex items-center justify-center text-slate-500 hover:text-slate-800 transition-colors cursor-pointer shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Scrollable Content -->
        <div class="p-5 sm:p-6 overflow-y-auto space-y-5 text-xs text-slate-700">
            
            <!-- Grid: Swafoto & Ringkasan Waktu -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-start">
                
                <!-- Swafoto Container (5 Cols) -->
                <div class="md:col-span-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="4"/></svg>
                            Swafoto Check-In
                        </span>
                        <span id="adm-photo-badge" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">Resolusi Web</span>
                    </div>

                    <!-- Photo Viewport -->
                    <div id="adm-photo-container" class="relative aspect-4/3 w-full rounded-2xl bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center group shadow-xs">
                        <img id="adm-photo-img" src="" alt="Swafoto Presensi" class="w-full h-full object-cover hidden">
                        <div id="adm-no-photo" class="text-center p-4">
                            <svg class="w-10 h-10 mx-auto text-slate-300 stroke-[1.5] mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span class="text-[11px] font-semibold text-slate-400 block">Tidak ada berkas swafoto</span>
                            <span class="text-[10px] text-slate-400 block mt-0.5">Check-in tanpa unggahan foto</span>
                        </div>
                        <!-- Zoom Overlay Trigger -->
                        <button type="button" id="adm-photo-zoom-btn" onclick="openImageLightbox(document.getElementById('adm-photo-img').src)" class="hidden absolute inset-0 bg-slate-950/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white gap-1.5 cursor-pointer backdrop-blur-2xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                            <span class="text-xs font-bold">Perbesar Foto</span>
                        </button>
                    </div>

                    <!-- Ringkasan Waktu Presensi -->
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                        <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Log Waktu Kehadiran</span>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-[10px] text-slate-400 block font-medium">Jam Masuk (In)</span>
                                <span class="font-bold text-indigo-700 font-mono text-[13px]" id="adm-checkin-time">-</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 block font-medium">Jam Pulang (Out)</span>
                                <span class="font-bold text-slate-800 font-mono text-[13px]" id="adm-checkout-time">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info DU/DI & Geofencing Minimap (7 Cols) -->
                <div class="md:col-span-7 space-y-3">
                    <!-- Info Mitra & Status -->
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Mitra Tempat PKL</span>
                                <span class="font-extrabold text-slate-900 text-xs block" id="adm-company-name">-</span>
                            </div>
                            <div id="adm-status-badge">
                                <!-- Badge status injected here -->
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-[10px] text-slate-400 block font-medium">Tanggal Presensi</span>
                                <span class="font-bold text-slate-800 font-mono" id="adm-date">-</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 block font-medium">Status Geofencing</span>
                                <span id="adm-radius-pill" class="font-bold">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Geofencing & Interactive Minimap Box -->
                    <div class="p-3.5 rounded-2xl bg-indigo-50/50 border border-indigo-100 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-indigo-950 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><circle cx="12" cy="11" r="2"/></svg>
                                Titik Koordinat GPS & Minimap
                            </span>
                            <span class="text-[10px] text-slate-500 font-mono" id="adm-radius-info">Radius: - m</span>
                        </div>

                        <!-- Interactive Minimap Viewport -->
                        <div class="relative w-full h-48 rounded-xl overflow-hidden border border-indigo-200/80 shadow-2xs bg-slate-100">
                            <!-- Map Element -->
                            <div id="adm-minimap" class="w-full h-full z-0"></div>

                            <!-- Fallback if No Coordinates -->
                            <div id="adm-minimap-empty" class="hidden absolute inset-0 flex flex-col items-center justify-center p-3 text-center bg-slate-100 text-slate-400">
                                <svg class="w-8 h-8 mb-1.5 text-slate-300 stroke-[1.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                <span class="text-[11px] font-semibold text-slate-600">Koordinat GPS Tidak Tersedia</span>
                                <span class="text-[10px] text-slate-400">Presensi ini tidak merekam data latitude & longitude</span>
                            </div>

                            <!-- Quick Map Legend Overlay -->
                            <div id="adm-minimap-legend" class="absolute bottom-2 left-2 z-[400] bg-white/95 backdrop-blur-xs px-2.5 py-1.5 rounded-lg border border-slate-200 shadow-xs flex items-center gap-2.5 text-[10px] font-bold text-slate-700 pointer-events-none">
                                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-indigo-600 ring-2 ring-indigo-200"></span> Siswa</span>
                                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-600 ring-2 ring-emerald-200"></span> DU/DI</span>
                                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full border border-emerald-500 bg-emerald-100"></span> Radius</span>
                            </div>
                        </div>

                        <!-- Coordinates Bar -->
                        <div class="text-[11px] font-mono text-slate-700 bg-white p-2.5 rounded-xl border border-indigo-100/80 flex items-center justify-between">
                            <div>
                                <span class="text-slate-400">Lat, Lng:</span>
                                <strong id="adm-coords-text" class="text-slate-800 ml-1">-</strong>
                            </div>
                            <button type="button" onclick="copyModalCoords()" class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800 px-2 py-0.5 rounded-md hover:bg-indigo-50 transition-colors cursor-pointer" title="Salin Koordinat">
                                Salin
                            </button>
                        </div>

                        <!-- Direct Maps Action Buttons -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-0.5">
                            <a id="adm-btn-gmaps" href="#" target="_blank" rel="noopener noreferrer" class="flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl bg-white hover:bg-slate-50 text-indigo-700 font-bold border border-indigo-200/80 text-xs shadow-2xs transition-all text-center">
                                <svg class="w-3.5 h-3.5 text-rose-500 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 010 5z"/></svg>
                                <span>Buka Google Maps</span>
                            </a>
                            <a id="adm-btn-route" href="#" target="_blank" rel="noopener noreferrer" class="flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-2xs transition-all text-center">
                                <svg class="w-3.5 h-3.5 text-indigo-200 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                <span>Rute ke DU/DI</span>
                            </a>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Catatan Harian & Keterangan Tambahan -->
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1.5">
                <span class="text-xs font-bold text-slate-800 block">Catatan Capaian / Alasan Presensi:</span>
                <p id="adm-notes-text" class="text-xs text-slate-600 leading-relaxed italic bg-white p-3 rounded-xl border border-slate-200/60 min-h-[48px]">
                    -
                </p>
            </div>

        </div>

        <!-- Footer -->
        <div class="px-5 py-3.5 border-t border-slate-100 flex items-center justify-between shrink-0 bg-slate-50/60">
            <span class="text-[11px] text-slate-400 font-medium">VokaTrack Geofencing & Swafoto Verification</span>
            <button type="button" onclick="closeAttendanceDetailModal()" class="py-2 px-4 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs transition-colors cursor-pointer">
                Tutup
            </button>
        </div>

    </div>
</div>

<script>
    let currentCoordsText = '';
    let attendanceMap = null;
    let attendanceMapLayerGroup = null;

    function initOrUpdateMinimap(data) {
        const mapContainer = document.getElementById('adm-minimap');
        const emptyContainer = document.getElementById('adm-minimap-empty');
        const legend = document.getElementById('adm-minimap-legend');

        const studentLat = (data.studentLat !== null && data.studentLat !== undefined && data.studentLat !== '') ? parseFloat(data.studentLat) : null;
        const studentLng = (data.studentLng !== null && data.studentLng !== undefined && data.studentLng !== '') ? parseFloat(data.studentLng) : null;

        if (studentLat === null || studentLng === null || isNaN(studentLat) || isNaN(studentLng)) {
            mapContainer.classList.add('hidden');
            emptyContainer.classList.remove('hidden');
            legend.classList.add('hidden');
            return;
        }

        mapContainer.classList.remove('hidden');
        emptyContainer.classList.add('hidden');
        legend.classList.remove('hidden');

        // Check if Leaflet library is ready
        if (typeof L === 'undefined') {
            setTimeout(() => initOrUpdateMinimap(data), 120);
            return;
        }

        const compLat = (data.companyLat !== null && data.companyLat !== undefined && data.companyLat !== '') ? parseFloat(data.companyLat) : null;
        const compLng = (data.companyLng !== null && data.companyLng !== undefined && data.companyLng !== '') ? parseFloat(data.companyLng) : null;
        const hasCompany = compLat !== null && compLng !== null && !isNaN(compLat) && !isNaN(compLng);
        const radius = parseInt(data.radiusMeters, 10) || 200;
        const isWithin = data.isWithinRadius === true;

        // Initialize Map once with initial center and zoom
        if (!attendanceMap) {
            attendanceMap = L.map('adm-minimap', {
                zoomControl: true,
                attributionControl: false,
                scrollWheelZoom: false
            }).setView([studentLat, studentLng], 15);

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19
            }).addTo(attendanceMap);

            attendanceMapLayerGroup = L.layerGroup().addTo(attendanceMap);
        } else {
            attendanceMap.setView([studentLat, studentLng], 15);
            attendanceMapLayerGroup.clearLayers();
        }

        // 1. Student Marker with Pulse Design
        const studentIcon = L.divIcon({
            className: 'custom-student-pin',
            html: `
                <div class="relative flex items-center justify-center -ml-2 -mt-2">
                    <span class="animate-ping absolute inline-flex h-7 w-7 rounded-full bg-indigo-400 opacity-75"></span>
                    <div class="w-8 h-8 rounded-full bg-indigo-600 text-white border-2 border-white shadow-lg flex items-center justify-center text-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                </div>
            `,
            iconSize: [24, 24],
            iconAnchor: [12, 12],
            popupAnchor: [0, -16]
        });

        const studentMarker = L.marker([studentLat, studentLng], { icon: studentIcon })
            .bindPopup(`
                <div class="p-1 text-xs font-sans">
                    <span class="font-bold text-indigo-700 block">${data.studentName || 'Siswa'}</span>
                    <span class="text-[11px] text-slate-600 block mt-0.5">Waktu: <b>${data.checkinTime ? data.checkinTime + ' WIB' : '-'}</b></span>
                    <span class="text-[10px] text-slate-400 font-mono block mt-0.5">${studentLat.toFixed(6)}, ${studentLng.toFixed(6)}</span>
                </div>
            `);
        attendanceMapLayerGroup.addLayer(studentMarker);

        // 2. DU/DI Company Marker & Geofencing Circle (if available)
        if (hasCompany) {
            const companyIcon = L.divIcon({
                className: 'custom-company-pin',
                html: `
                    <div class="relative flex items-center justify-center -ml-1 -mt-1">
                        <div class="w-7 h-7 rounded-full bg-emerald-600 text-white border-2 border-white shadow-md flex items-center justify-center text-xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                    </div>
                `,
                iconSize: [24, 24],
                iconAnchor: [12, 12],
                popupAnchor: [0, -14]
            });

            const companyMarker = L.marker([compLat, compLng], { icon: companyIcon })
                .bindPopup(`
                    <div class="p-1 text-xs font-sans">
                        <span class="font-bold text-slate-900 block">${data.companyName || 'Kantor DU/DI'}</span>
                        <span class="text-[11px] text-emerald-700 font-semibold block mt-0.5">Radius Resmi: ${radius} m</span>
                    </div>
                `);
            attendanceMapLayerGroup.addLayer(companyMarker);

            // Geofencing Circle
            const circleColor = isWithin ? '#10b981' : '#ef4444';
            const circle = L.circle([compLat, compLng], {
                radius: radius,
                color: circleColor,
                weight: 2,
                fillColor: circleColor,
                fillOpacity: 0.16,
                dashArray: isWithin ? null : '4, 6'
            }).bindPopup(`Area Geofencing Resmi (${radius} meter)`);
            attendanceMapLayerGroup.addLayer(circle);

            // Path Line between Student and Company
            const line = L.polyline([[studentLat, studentLng], [compLat, compLng]], {
                color: isWithin ? '#6366f1' : '#ef4444',
                weight: 2,
                dashArray: '5, 8',
                opacity: 0.75
            });
            attendanceMapLayerGroup.addLayer(line);

            // Safe bounds calculation using pure L.latLngBounds
            const bounds = L.latLngBounds([[studentLat, studentLng], [compLat, compLng]]);
            const latDelta = (radius / 111320);
            const lngDelta = (radius / (111320 * Math.max(0.1, Math.cos(compLat * (Math.PI / 180)))));
            bounds.extend([compLat - latDelta, compLng - lngDelta]);
            bounds.extend([compLat + latDelta, compLng + lngDelta]);

            attendanceMap.fitBounds(bounds, {
                padding: [24, 24],
                maxZoom: 17
            });
        } else {
            attendanceMap.setView([studentLat, studentLng], 16);
        }

        // Trigger Leaflet viewport recalculation after DOM transition
        setTimeout(() => {
            if (attendanceMap) {
                attendanceMap.invalidateSize();
            }
        }, 120);
        setTimeout(() => {
            if (attendanceMap) {
                attendanceMap.invalidateSize();
            }
        }, 320);
    }

    function showAttendanceDetail(data) {
        if (!data) return;

        const modal = document.getElementById('attendance-detail-modal');
        if (!modal) return;

        // Student Info
        document.getElementById('adm-student-info').textContent = `${data.studentName || 'Siswa'} (NISN: ${data.studentNisn || '-'}) • ${data.companyName || 'Mitra PKL'}`;
        document.getElementById('adm-company-name').textContent = data.companyName || '-';
        document.getElementById('adm-date').textContent = data.date || '-';
        document.getElementById('adm-checkin-time').textContent = data.checkinTime ? `${data.checkinTime} WIB` : '-';
        document.getElementById('adm-checkout-time').textContent = data.checkoutTime ? `${data.checkoutTime} WIB` : '-';
        document.getElementById('adm-notes-text').textContent = data.notes || 'Tidak ada catatan yang dicantumkan.';

        // Status Badge
        const statusContainer = document.getElementById('adm-status-badge');
        let statusVariant = data.status || 'hadir';
        let statusLabel = (data.status || 'HADIR').toUpperCase().replace('_', ' ');
        let badgeColorClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
        if (['terlambat'].includes(statusVariant)) badgeColorClass = 'bg-amber-50 text-amber-700 border-amber-200';
        if (['izin', 'sakit'].includes(statusVariant)) badgeColorClass = 'bg-sky-50 text-sky-700 border-sky-200';
        if (['alfa'].includes(statusVariant)) badgeColorClass = 'bg-rose-50 text-rose-700 border-rose-200';
        statusContainer.innerHTML = `<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full border text-[11px] font-bold ${badgeColorClass}"><span class="w-1.5 h-1.5 rounded-full bg-current"></span>${statusLabel}</span>`;

        // Photo
        const photoImg = document.getElementById('adm-photo-img');
        const noPhoto = document.getElementById('adm-no-photo');
        const zoomBtn = document.getElementById('adm-photo-zoom-btn');
        const photoBadge = document.getElementById('adm-photo-badge');

        if (data.photoUrl) {
            photoImg.src = data.photoUrl;
            photoImg.classList.remove('hidden');
            noPhoto.classList.add('hidden');
            zoomBtn.classList.remove('hidden');
            photoBadge.textContent = 'Terlampir';
            photoBadge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200';
        } else {
            photoImg.src = '';
            photoImg.classList.add('hidden');
            noPhoto.classList.remove('hidden');
            zoomBtn.classList.add('hidden');
            photoBadge.textContent = 'Tanpa Foto';
            photoBadge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500';
        }

        // Radius & Geofencing
        const radiusPill = document.getElementById('adm-radius-pill');
        if (data.isWithinRadius === true) {
            radiusPill.innerHTML = `<span class="text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md inline-flex items-center gap-1 font-bold"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> Dalam Radius</span>`;
        } else if (data.isWithinRadius === false) {
            radiusPill.innerHTML = `<span class="text-rose-700 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-md inline-flex items-center gap-1 font-bold"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg> Luar Radius</span>`;
        } else {
            radiusPill.innerHTML = `<span class="text-slate-500 font-mono">Tidak Terdata</span>`;
        }

        // Coordinates & Map Buttons
        const coordsText = document.getElementById('adm-coords-text');
        const radiusInfo = document.getElementById('adm-radius-info');
        const btnGmaps = document.getElementById('adm-btn-gmaps');
        const btnRoute = document.getElementById('adm-btn-route');

        radiusInfo.textContent = data.radiusMeters ? `Radius Toleransi: ${data.radiusMeters} m` : 'Radius: Standar';

        if (data.studentLat && data.studentLng) {
            currentCoordsText = `${data.studentLat}, ${data.studentLng}`;
            coordsText.textContent = currentCoordsText;
            btnGmaps.href = `https://www.google.com/maps?q=${data.studentLat},${data.studentLng}`;
            btnGmaps.classList.remove('pointer-events-none', 'opacity-50');

            if (data.companyLat && data.companyLng) {
                btnRoute.href = `https://www.google.com/maps/dir/?api=1&origin=${data.studentLat},${data.studentLng}&destination=${data.companyLat},${data.companyLng}`;
                btnRoute.classList.remove('hidden');
            } else {
                btnRoute.classList.add('hidden');
            }
        } else {
            currentCoordsText = '';
            coordsText.textContent = 'Koordinat tidak tercatat';
            btnGmaps.href = '#';
            btnGmaps.classList.add('pointer-events-none', 'opacity-50');
            btnRoute.classList.add('hidden');
        }

        // Open Modal
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        // Render / Update Minimap after unhiding modal
        requestAnimationFrame(() => {
            initOrUpdateMinimap(data);
        });
    }

    function closeAttendanceDetailModal() {
        const modal = document.getElementById('attendance-detail-modal');
        if (modal) modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    function copyModalCoords() {
        if (!currentCoordsText) return;
        navigator.clipboard.writeText(currentCoordsText).then(() => {
            alert('Titik koordinat berhasil disalin ke clipboard:\n' + currentCoordsText);
        }).catch(() => {
            prompt('Salin koordinat berikut:', currentCoordsText);
        });
    }

    // Close on Escape key
    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('attendance-detail-modal');
            if (modal && !modal.classList.contains('hidden')) {
                closeAttendanceDetailModal();
            }
        }
    });
</script>