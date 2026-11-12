<!-- Leaflet Stylesheet & Script (Bundled Locally with CDN Fallback) -->
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" onerror="this.onerror=null;this.href='https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'">
<script src="{{ asset('vendor/leaflet/leaflet.js') }}" onerror="this.onerror=null;this.src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'"></script>

<style>
    /* Custom Leaflet Map Container & Control Styling */
    #pkl-leaflet-map {
        z-index: 10;
    }
    .leaflet-popup-content-wrapper {
        border-radius: 1rem;
        padding: 0;
        overflow: hidden;
        box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        border: 1px solid #e2e8f0;
    }
    .leaflet-popup-content {
        margin: 0;
        line-height: 1.4;
    }
    .leaflet-container {
        font-family: inherit;
    }
    .custom-marker-pin {
        transition: transform 0.2s ease-in-out;
    }
    .custom-marker-pin:hover {
        transform: scale(1.15);
        z-index: 9999 !important;
    }
</style>

@php
    $currentDate = $filters['date'] ?? now()->toDateString();
    $currentMajor = $filters['major_id'] ?? 'all';
    $currentClass = $filters['class_id'] ?? 'all';
    $currentStatus = $filters['status_filter'] ?? 'all';
    $currentSearch = $filters['search'] ?? '';
    $currentScope = $filters['scope'] ?? 'bimbingan';
@endphp

<div class="w-full space-y-6">

    <!-- Enterprise Hero Banner -->
    <x-page-header 
        title="Peta Sebaran PKL & Monitoring Wilayah" 
        subtitle="Visualisasi geografis interaktif lokasi penempatan DU/DI dan status kehadiran siswa secara real-time di seluruh wilayah."
        :badge="$roleType === 'admin' ? 'ADMIN SEKOLAH' : ($roleType === 'guru' ? 'GURU PEMBIMBING' : 'KEPALA SEKOLAH')"
    >
        @if($roleType === 'admin')
            <a href="{{ route('adminsekolah.companies') }}" class="inline-flex items-center justify-center font-bold rounded-xl transition-all shadow-xs cursor-pointer bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 px-3 py-1.5 text-xs">
                <svg class="w-4 h-4 mr-1.5 text-slate-600 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Kelola GPS DU/DI</span>
            </a>
        @endif

        <button type="button" onclick="window.location.reload()" class="inline-flex items-center justify-center font-bold rounded-xl transition-all shadow-xs cursor-pointer bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 px-3 py-1.5 text-xs">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            <span>Muat Ulang Data</span>
        </button>
    </x-page-header>

    <!-- Top KPI Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card 
            title="Total Mitra Terpetakan" 
            :value="$mapData['stats']['total_mapped'] . ' Industri'" 
            :subtitle="$mapData['stats']['total_students'] . ' Siswa Aktif Tersebar'"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Aman & Tertib" 
            :value="$mapData['stats']['green_count'] . ' Titik'" 
            subtitle="Seluruh siswa hadir tepat waktu"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Perlu Perhatian" 
            :value="$mapData['stats']['yellow_count'] . ' Titik'" 
            subtitle="Ada terlambat / izin / sakit"
            :color="$mapData['stats']['yellow_count'] > 0 ? 'amber' : 'emerald'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Kritis / Ada Kendala" 
            :value="$mapData['stats']['red_count'] . ' Titik'" 
            subtitle="Ada siswa Alfa / SOS kendala"
            :color="$mapData['stats']['red_count'] > 0 ? 'rose' : 'slate'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Filter Card -->
    <x-card>
        <form method="GET" action="{{ route($routePrefix) }}" id="map-filter-form" class="space-y-4">
            @if($roleType === 'guru')
                <div class="flex items-center gap-4 p-3 bg-slate-50 border border-slate-200/80 rounded-xl text-xs font-semibold text-slate-700">
                    <span class="text-slate-500 font-bold uppercase tracking-wider text-[11px]">Cakupan Guru:</span>
                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" name="scope" value="bimbingan" {{ $currentScope === 'bimbingan' ? 'checked' : '' }} onchange="this.form.submit()" class="text-indigo-600 focus:ring-indigo-500">
                        <span>Hanya Industri Siswa Bimbingan Saya</span>
                    </label>
                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" name="scope" value="all" {{ $currentScope === 'all' ? 'checked' : '' }} onchange="this.form.submit()" class="text-indigo-600 focus:ring-indigo-500">
                        <span>Semua Industri Sekolah</span>
                    </label>
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <!-- Tanggal -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Tanggal Monitoring</label>
                    <input type="date" name="date" value="{{ $currentDate }}" class="w-full text-xs font-semibold py-2 px-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all">
                </div>

                <!-- Jurusan -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Jurusan</label>
                    <select name="major_id" id="map-filter-major" onchange="syncClassOptions()" class="w-full text-xs font-semibold py-2 px-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all">
                        <option value="all">Semua Jurusan</option>
                        @foreach($majors as $m)
                            <option value="{{ $m->id }}" {{ $currentMajor == $m->id ? 'selected' : '' }}>
                                {{ $m->name }} ({{ $m->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Rombel / Kelas -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Rombel / Kelas</label>
                    <select name="class_id" id="map-filter-class" class="w-full text-xs font-semibold py-2 px-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all">
                        <option value="all" data-major="all">Semua Rombel</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" data-major="{{ $c->major_id }}" {{ $currentClass == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Pin -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Indikator Status</label>
                    <select name="status_filter" class="w-full text-xs font-semibold py-2 px-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all">
                        <option value="all" {{ $currentStatus === 'all' ? 'selected' : '' }}>Semua Status</option>
                        <option value="green" {{ $currentStatus === 'green' ? 'selected' : '' }}>🟢 Hijau (Aman & Tertib)</option>
                        <option value="yellow" {{ $currentStatus === 'yellow' ? 'selected' : '' }}>🟡 Kuning (Perlu Perhatian)</option>
                        <option value="red" {{ $currentStatus === 'red' ? 'selected' : '' }}>🔴 Merah (Kritis / Alfa / Kendala)</option>
                    </select>
                </div>

                <!-- Cari DU/DI -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Cari Industri</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ $currentSearch }}" placeholder="Nama DU/DI, alamat..." 
                               class="w-full text-xs font-semibold pl-3 pr-8 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all">
                        @if($currentSearch)
                            <a href="{{ route($routePrefix, array_merge(request()->except('search'))) }}" class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 text-xs font-bold">✕</a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                <span class="text-slate-400 text-[11px]">
                    Status dievaluasi per tanggal <strong>{{ \Carbon\Carbon::parse($currentDate)->translatedFormat('l, d F Y') }}</strong>.
                </span>
                <div class="flex items-center gap-2">
                    <a href="{{ route($routePrefix) }}" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold transition-colors">
                        Reset Filter
                    </a>
                    <button type="submit" class="px-4 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold transition-colors shadow-xs">
                        Terapkan Filter
                    </button>
                </div>
            </div>
        </form>
    </x-card>

    <!-- Notice for Unmapped Companies -->
    @if(!empty($mapData['unmapped_companies']))
        <div class="p-4 rounded-2xl bg-amber-50/80 border border-amber-200 text-amber-900 text-xs shadow-2xs flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <span class="font-bold block">Terdapat {{ count($mapData['unmapped_companies']) }} Mitra DU/DI belum diatur titik koordinat GPS-nya</span>
                    <span class="text-[11px] text-amber-700">Mitra tanpa koordinat tidak dapat ditampilkan di atas peta ({{ implode(', ', array_slice(array_column($mapData['unmapped_companies'], 'name'), 0, 3)) }}@if(count($mapData['unmapped_companies']) > 3)...@endif).</span>
                </div>
            </div>
            @if($roleType === 'admin')
                <a href="{{ route('adminsekolah.companies') }}" class="px-3 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold transition-colors shrink-0">
                    Atur Koordinat Sekarang
                </a>
            @endif
        </div>
    @endif

    <!-- Interactive Map & Company List Split Panel -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left: Map Container (8 cols) -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden relative">
            
            <!-- Map Floating Top Header -->
            <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200/80 flex items-center justify-between text-xs">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="font-bold text-slate-800">Peta Spasial DU/DI Terpasang</span>
                    <span class="text-slate-400 font-medium">({{ count($mapData['markers']) }} Lokasi Aktif)</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="resetMapView()" class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-[11px] font-semibold transition-colors" title="Kembalikan zoom ke seluruh pin">
                        Reset Zoom Wilayah
                    </button>
                </div>
            </div>

            <!-- Leaflet Map Div -->
            <div id="pkl-leaflet-map" class="w-full h-[620px] bg-slate-100 relative"></div>

            <!-- Map Floating Legend Overlay -->
            <div class="absolute bottom-4 left-4 z-20 bg-white/95 backdrop-blur-md px-3.5 py-2.5 rounded-xl border border-slate-200/80 shadow-lg text-[11px] font-semibold text-slate-700 space-y-1.5 pointer-events-auto">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block border-b border-slate-100 pb-1">Keterangan Warna Pin:</span>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-emerald-500 ring-2 ring-emerald-200"></span>
                    <span>Aman (Seluruh Siswa Hadir)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-amber-500 ring-2 ring-amber-200"></span>
                    <span>Perhatian (Terlambat/Izin/Sakit)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-rose-500 ring-2 ring-rose-200 animate-ping"></span>
                    <span class="font-bold text-rose-700">Kritis (Alfa / Kendala SOS)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-slate-400 ring-2 ring-slate-200"></span>
                    <span>Netral (Belum Ada Siswa Aktif)</span>
                </div>
            </div>
        </div>

        <!-- Right: Company Directory List Panel (4 cols) -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col h-[680px]">
            <div class="p-4 bg-slate-50 border-b border-slate-200/80 space-y-2.5">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Direktori Industri</h3>
                    <span class="px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 text-[10px] font-bold">
                        <span id="filtered-company-count">{{ count($mapData['markers']) }}</span> DU/DI
                    </span>
                </div>
                <!-- Client-side quick filter input -->
                <div class="relative">
                    <input type="text" id="sidebar-quick-search" onkeyup="filterSidebarCompanies()" placeholder="Saring daftar di samping..." 
                           class="w-full text-xs pl-8 pr-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>

            <!-- List Container -->
            <div class="flex-1 overflow-y-auto divide-y divide-slate-100 custom-scrollbar p-2 space-y-1.5" id="company-sidebar-list">
                @forelse($mapData['markers'] as $marker)
                    <div class="p-3 rounded-xl border border-transparent hover:border-slate-200 hover:bg-slate-50/90 transition-all cursor-pointer company-card-item group" 
                         onclick="focusCompanyMarker({{ $marker['id'] }})"
                         data-name="{{ strtolower($marker['name']) }}"
                         data-address="{{ strtolower($marker['address']) }}">
                        <div class="flex items-start justify-between gap-2 mb-1">
                            <span class="font-bold text-slate-900 text-xs block group-hover:text-indigo-600 transition-colors truncate" title="{{ $marker['name'] }}">
                                {{ $marker['name'] }}
                            </span>
                            @if($marker['status'] === 'green')
                                <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-[10px] font-bold shrink-0 border border-emerald-200">
                                    🟢 Aman
                                </span>
                            @elseif($marker['status'] === 'yellow')
                                <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 text-[10px] font-bold shrink-0 border border-amber-200">
                                    🟡 Perhatian
                                </span>
                            @elseif($marker['status'] === 'red')
                                <span class="px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 text-[10px] font-bold shrink-0 border border-rose-200 animate-pulse">
                                    🔴 Kritis
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[10px] font-bold shrink-0">
                                    ⚪ Kosong
                                </span>
                            @endif
                        </div>

                        <p class="text-[11px] text-slate-500 line-clamp-1 mb-2">
                            📍 {{ $marker['address'] }}
                        </p>

                        <div class="flex items-center justify-between text-[11px] pt-1.5 border-t border-slate-100/80">
                            <span class="text-slate-600 font-semibold flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                <span>{{ $marker['total_students'] }} Siswa PKL</span>
                            </span>
                            <span class="text-[10px] text-slate-400 font-medium">
                                {{ $marker['status_detail'] }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="py-12 px-4 text-center">
                        <p class="text-xs text-slate-400 font-medium">Tidak ada industri dengan titik GPS yang cocok dengan filter saat ini.</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>

<script>
    // Data GeoJSON / Markers dari Controller
    const rawMarkers = @json($mapData['markers']);
    let mapInstance = null;
    let markersLayerGroup = null;
    let markerInstancesMap = {};

    function initLeafletMap() {
        if (!document.getElementById('pkl-leaflet-map')) return;

        // Koordinat default Jawa Barat / Indonesia tengah
        let defaultCenter = [-7.1, 107.8];
        let defaultZoom = 10;

        if (rawMarkers.length > 0) {
            defaultCenter = [rawMarkers[0].lat, rawMarkers[0].lng];
            defaultZoom = 12;
        }

        mapInstance = L.map('pkl-leaflet-map', {
            center: defaultCenter,
            zoom: defaultZoom,
            zoomControl: false,
        });

        // Posisi Zoom Control di kanan atas
        L.control.zoom({ position: 'topright' }).addTo(mapInstance);

        // Basemap OpenStreetMap resmi
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(mapInstance);

        markersLayerGroup = L.featureGroup().addTo(mapInstance);

        // Pasang seluruh marker DU/DI
        renderPinsOnMap();

        // Fit bounds jika terdapat marker
        if (rawMarkers.length > 0) {
            mapInstance.fitBounds(markersLayerGroup.getBounds().pad(0.12));
        }
    }

    function renderPinsOnMap() {
        markersLayerGroup.clearLayers();
        markerInstancesMap = {};

        rawMarkers.forEach(company => {
            const pinColor = company.status_color || '#10b981';
            const status = company.status;

            let pulseHtml = '';
            if (status === 'red') {
                pulseHtml = `<span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>`;
            } else if (status === 'yellow') {
                pulseHtml = `<span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-50"></span>`;
            }

            const iconHtml = `
                <div class="relative flex items-center justify-center custom-marker-pin" style="width: 36px; height: 36px;">
                    ${pulseHtml}
                    <div class="relative w-8 h-8 rounded-full flex items-center justify-center text-white font-black text-xs shadow-md border-2 border-white" style="background-color: ${pinColor};">
                        <span>${company.total_students}</span>
                    </div>
                </div>
            `;

            const customDivIcon = L.divIcon({
                html: iconHtml,
                className: 'custom-leaflet-pin',
                iconSize: [36, 36],
                iconAnchor: [18, 18],
                popupAnchor: [0, -20]
            });

            const marker = L.marker([company.lat, company.lng], { icon: customDivIcon });

            // Popup HTML Konten Interaktif
            const popupHtml = buildCompanyPopupContent(company);
            marker.bindPopup(popupHtml, { maxWidth: 360, minWidth: 280 });

            markersLayerGroup.addLayer(marker);
            markerInstancesMap[company.id] = marker;
        });
    }

    function buildCompanyPopupContent(company) {
        let badgeColorClass = 'bg-slate-100 text-slate-700';
        if (company.status === 'green') badgeColorClass = 'bg-emerald-100 text-emerald-800 border border-emerald-200';
        else if (company.status === 'yellow') badgeColorClass = 'bg-amber-100 text-amber-800 border border-amber-200';
        else if (company.status === 'red') badgeColorClass = 'bg-rose-100 text-rose-800 border border-rose-200';

        let studentsListHtml = '';
        if (company.students && company.students.length > 0) {
            studentsListHtml = company.students.map(st => {
                let statusBadge = '<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600">Belum Absen</span>';
                if (st.status === 'hadir') statusBadge = '<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700">Hadir (' + st.checkin_time + ')</span>';
                else if (st.status === 'terlambat') statusBadge = '<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700">Terlambat (' + st.checkin_time + ')</span>';
                else if (st.status === 'izin') statusBadge = '<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700">Izin</span>';
                else if (st.status === 'sakit') statusBadge = '<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">Sakit</span>';
                else if (st.status === 'alfa') statusBadge = '<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-200 text-rose-900">ALFA</span>';

                let incidentIcon = st.has_incident ? '<span class="text-rose-600 font-bold ml-1" title="Siswa memiliki laporan kendala/SOS aktif">⚠️ SOS</span>' : '';

                return `
                    <div class="flex items-center justify-between py-1 border-b border-slate-100 text-[11px] last:border-b-0">
                        <div class="min-w-0 pr-2">
                            <span class="font-bold text-slate-800 block truncate">${st.name} ${incidentIcon}</span>
                            <span class="text-[10px] text-slate-400 block">${st.class_name} &bull; ${st.teacher_name}</span>
                        </div>
                        <div class="shrink-0">
                            ${statusBadge}
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            studentsListHtml = '<p class="text-[11px] text-slate-400 italic py-1">Tidak ada siswa terplot aktif.</p>';
        }

        return `
            <div class="p-4 space-y-3">
                <div class="border-b border-slate-100 pb-2">
                    <div class="flex items-start justify-between gap-2 mb-1">
                        <h4 class="font-bold text-slate-900 text-xs">${company.name}</h4>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold ${badgeColorClass}">
                            ${company.status_label}
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500">${company.address}</p>
                    <p class="text-[10px] text-slate-400 mt-1 font-medium">${company.status_detail}</p>
                </div>

                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Status Kehadiran Siswa (${company.total_students}):</span>
                    <div class="max-h-36 overflow-y-auto custom-scrollbar pr-1">
                        ${studentsListHtml}
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                    <span class="text-[10px] text-slate-400">Radius GPS: ${company.radius}m</span>
                    <a href="${company.gmaps_url}" target="_blank" class="px-3 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-bold inline-flex items-center gap-1 shadow-xs transition-colors">
                        <span>🧭 Rute Navigasi</span>
                    </a>
                </div>
            </div>
        `;
    }

    function focusCompanyMarker(companyId) {
        const marker = markerInstancesMap[companyId];
        if (!marker) return;

        mapInstance.flyTo(marker.getLatLng(), 16, {
            animate: true,
            duration: 1.2
        });

        setTimeout(() => {
            marker.openPopup();
        }, 1250);
    }

    function resetMapView() {
        if (markersLayerGroup && rawMarkers.length > 0) {
            mapInstance.fitBounds(markersLayerGroup.getBounds().pad(0.12));
        }
    }

    // Client-side instant filter untuk sidebar list
    function filterSidebarCompanies() {
        const input = document.getElementById('sidebar-quick-search');
        const filter = input.value.toLowerCase().trim();
        const items = document.querySelectorAll('.company-card-item');
        let visibleCount = 0;

        items.forEach(item => {
            const name = item.getAttribute('data-name') || '';
            const address = item.getAttribute('data-address') || '';
            if (name.includes(filter) || address.includes(filter)) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        const countSpan = document.getElementById('filtered-company-count');
        if (countSpan) countSpan.innerText = visibleCount;
    }

    function syncClassOptions() {
        const majorSelect = document.getElementById('map-filter-major');
        const classSelect = document.getElementById('map-filter-class');
        if (!majorSelect || !classSelect) return;

        const selectedMajor = majorSelect.value;
        const options = classSelect.querySelectorAll('option');

        options.forEach(opt => {
            const optMajor = opt.getAttribute('data-major');
            if (opt.value === 'all') {
                opt.style.display = '';
                return;
            }
            if (selectedMajor === 'all' || optMajor === selectedMajor) {
                opt.style.display = '';
            } else {
                opt.style.display = 'none';
            }
        });

        const currentSelected = classSelect.querySelector('option:checked');
        if (currentSelected && currentSelected.style.display === 'none') {
            classSelect.value = 'all';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        initLeafletMap();
        syncClassOptions();
    });
</script>
