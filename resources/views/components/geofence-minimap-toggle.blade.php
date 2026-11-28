@props([
    'uid',
    'latInputId',
    'lngInputId',
    'radiusInputId',
])

@once
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" onerror="this.onerror=null;this.href='https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'">
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}" onerror="this.onerror=null;this.src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'"></script>
    <script>
        window.VokaGeofenceMinimap = window.VokaGeofenceMinimap || (function () {
            const instances = {};

            function getValues(cfg) {
                const latEl = document.getElementById(cfg.latInputId);
                const lngEl = document.getElementById(cfg.lngInputId);
                const radEl = document.getElementById(cfg.radiusInputId);
                const lat = latEl ? parseFloat(String(latEl.value).trim()) : NaN;
                const lng = lngEl ? parseFloat(String(lngEl.value).trim()) : NaN;
                const radius = radEl ? (parseInt(radEl.value, 10) || 100) : 100;
                const valid = !Number.isNaN(lat) && !Number.isNaN(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180;
                return { latEl, lngEl, radEl, lat, lng, radius, valid };
            }

            function sync(uid) {
                const inst = instances[uid];
                if (!inst) return;

                const wrap = document.getElementById('geofence-map-wrap-' + uid);
                const mapEl = document.getElementById('geofence-map-' + uid);
                const emptyEl = document.getElementById('geofence-map-empty-' + uid);
                const gmapsEl = document.getElementById('geofence-gmaps-' + uid);
                const vals = getValues(inst.cfg);

                if (gmapsEl) {
                    if (vals.valid) {
                        gmapsEl.href = 'https://www.google.com/maps?q=' + vals.lat + ',' + vals.lng;
                        gmapsEl.classList.remove('hidden');
                    } else {
                        gmapsEl.classList.add('hidden');
                    }
                }

                if (!wrap || wrap.classList.contains('hidden')) return;

                if (!vals.valid) {
                    mapEl.classList.add('hidden');
                    emptyEl.classList.remove('hidden');
                    return;
                }

                mapEl.classList.remove('hidden');
                emptyEl.classList.add('hidden');

                if (typeof L === 'undefined') {
                    setTimeout(() => sync(uid), 120);
                    return;
                }

                if (!inst.map) {
                    inst.map = L.map(mapEl, {
                        zoomControl: true,
                        attributionControl: false,
                        scrollWheelZoom: false
                    }).setView([vals.lat, vals.lng], 16);

                    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(inst.map);

                    inst.marker = L.marker([vals.lat, vals.lng], { draggable: true }).addTo(inst.map);
                    inst.circle = L.circle([vals.lat, vals.lng], {
                        radius: vals.radius,
                        color: '#10b981',
                        weight: 2,
                        fillColor: '#10b981',
                        fillOpacity: 0.16
                    }).addTo(inst.map);

                    const applyPoint = (newLat, newLng) => {
                        if (vals.latEl) vals.latEl.value = newLat.toFixed(7);
                        if (vals.lngEl) vals.lngEl.value = newLng.toFixed(7);
                        sync(uid);
                    };

                    inst.marker.on('dragend', function (e) {
                        const pos = e.target.getLatLng();
                        applyPoint(pos.lat, pos.lng);
                    });

                    inst.map.on('click', function (e) {
                        applyPoint(e.latlng.lat, e.latlng.lng);
                    });
                } else {
                    inst.marker.setLatLng([vals.lat, vals.lng]);
                    inst.circle.setLatLng([vals.lat, vals.lng]);
                    inst.circle.setRadius(vals.radius);
                    inst.map.setView([vals.lat, vals.lng], inst.map.getZoom());
                }

                setTimeout(() => {
                    if (!inst.map) return;
                    inst.map.invalidateSize();
                    inst.map.fitBounds(inst.circle.getBounds(), { padding: [20, 20], maxZoom: 17 });
                }, 100);
            }

            function register(cfg) {
                if (instances[cfg.uid]) return;
                instances[cfg.uid] = { cfg, map: null, marker: null, circle: null };

                [cfg.latInputId, cfg.lngInputId, cfg.radiusInputId].forEach((id) => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.addEventListener('input', () => sync(cfg.uid));
                        el.addEventListener('change', () => sync(cfg.uid));
                    }
                });
            }

            function toggle(uid) {
                const wrap = document.getElementById('geofence-map-wrap-' + uid);
                const label = document.getElementById('geofence-toggle-label-' + uid);
                if (!wrap) return;

                const willShow = wrap.classList.contains('hidden');
                wrap.classList.toggle('hidden', !willShow);
                if (label) {
                    label.textContent = willShow ? 'Sembunyikan Peta' : 'Tampilkan Peta Verifikasi';
                }
                if (willShow) {
                    sync(uid);
                }
            }

            function hide(uid) {
                const wrap = document.getElementById('geofence-map-wrap-' + uid);
                const label = document.getElementById('geofence-toggle-label-' + uid);
                if (wrap) wrap.classList.add('hidden');
                if (label) label.textContent = 'Tampilkan Peta Verifikasi';
            }

            return { register, toggle, sync, hide };
        })();
    </script>
@endonce

<div class="col-span-full space-y-2 pt-1">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <button type="button"
                onclick="window.VokaGeofenceMinimap.toggle('{{ $uid }}')"
                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl bg-white hover:bg-indigo-50 text-indigo-700 font-bold border border-indigo-200 text-[11px] transition-colors cursor-pointer">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
            <span id="geofence-toggle-label-{{ $uid }}">Tampilkan Peta Verifikasi</span>
        </button>

        <a id="geofence-gmaps-{{ $uid }}"
           href="#"
           target="_blank"
           rel="noopener noreferrer"
           class="hidden inline-flex items-center gap-1 text-[11px] font-bold text-slate-600 hover:text-indigo-700 transition-colors">
            <span>Buka di Google Maps</span>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        </a>
    </div>

    <div id="geofence-map-wrap-{{ $uid }}" class="hidden space-y-1.5">
        <div id="geofence-map-{{ $uid }}" class="w-full h-48 rounded-2xl border border-slate-200 bg-slate-100 z-0"></div>
        <div id="geofence-map-empty-{{ $uid }}" class="hidden w-full h-32 rounded-2xl border border-dashed border-slate-300 bg-white flex items-center justify-center text-[11px] text-slate-400 font-medium p-4 text-center">
            Masukkan Latitude &amp; Longitude yang valid untuk menampilkan peta verifikasi.
        </div>
        <p class="text-[10px] text-slate-500">
            Klik pada peta atau geser pin untuk menyesuaikan titik koordinat kantor secara langsung.
        </p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.VokaGeofenceMinimap.register({
            uid: @js($uid),
            latInputId: @js($latInputId),
            lngInputId: @js($lngInputId),
            radiusInputId: @js($radiusInputId),
        });
    });
</script>
