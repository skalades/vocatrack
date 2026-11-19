@extends('layouts.app')

@section('page_title', 'Monitoring Presensi Jurusan')
@section('page_subtitle', 'Pantau kehadiran harian peserta PKL jurusan ' . $major->name)

@section('content')
<div class="w-full space-y-6">

    <x-page-header 
        title="Monitoring Presensi Jurusan" 
        subtitle="Rekapitulasi kehadiran harian siswa jurusan {{ $major->name }} di lokasi mitra industri."
        badge="MONITORING KAJUR"
    />

    <!-- Summary Metrics -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Total Presensi</span>
            <span class="text-xl font-black text-slate-900 mt-1 block">{{ $stats['total'] }}</span>
        </div>
        <div class="p-4 rounded-2xl bg-emerald-50/60 border border-emerald-200/60 shadow-2xs">
            <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider block">Hadir Tepat Waktu</span>
            <span class="text-xl font-black text-emerald-900 mt-1 block">{{ $stats['hadir'] }}</span>
        </div>
        <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200/60 shadow-2xs">
            <span class="text-[11px] font-bold text-amber-700 uppercase tracking-wider block">Terlambat</span>
            <span class="text-xl font-black text-amber-900 mt-1 block">{{ $stats['terlambat'] }}</span>
        </div>
        <div class="p-4 rounded-2xl bg-blue-50/60 border border-blue-200/60 shadow-2xs">
            <span class="text-[11px] font-bold text-blue-700 uppercase tracking-wider block">Izin / Sakit / Dinas</span>
            <span class="text-xl font-black text-blue-900 mt-1 block">{{ $stats['izin'] }}</span>
        </div>
        <div class="p-4 rounded-2xl bg-rose-50/60 border border-rose-200/60 shadow-2xs">
            <span class="text-[11px] font-bold text-rose-700 uppercase tracking-wider block">Mangkir (Alfa)</span>
            <span class="text-xl font-black text-rose-900 mt-1 block">{{ $stats['alfa'] }}</span>
        </div>
    </div>

    <!-- Filter Form -->
    <x-card>
        <form method="GET" action="{{ route('kajur.attendance') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Cari Siswa / DU/DI</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik nama siswa, NISN, atau DU/DI..."
                       class="w-full text-xs sm:text-sm px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 text-slate-900">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Tanggal Presensi</label>
                <input type="date" name="date" value="{{ $selectedDate }}" 
                       class="w-full text-xs sm:text-sm px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-mono text-slate-900">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Rombel Kelas</label>
                <select name="class_id" class="w-full text-xs sm:text-sm px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                    <option value="">-- Semua Kelas --</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Status Kehadiran</label>
                <div class="flex items-center gap-2">
                    <select name="status" class="w-full text-xs sm:text-sm px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                        <option value="">-- Semua Status --</option>
                        <option value="hadir" {{ request('status') === 'hadir' ? 'selected' : '' }}>Hadir</option>
                        <option value="terlambat" {{ request('status') === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                        <option value="izin" {{ request('status') === 'izin' ? 'selected' : '' }}>Izin</option>
                        <option value="sakit" {{ request('status') === 'sakit' ? 'selected' : '' }}>Sakit</option>
                        <option value="alfa" {{ request('status') === 'alfa' ? 'selected' : '' }}>Alfa</option>
                    </select>
                    <x-button type="submit" variant="primary" size="md" class="shrink-0 px-4">
                        Filter
                    </x-button>
                    @if(request()->hasAny(['search', 'class_id', 'status']) || request('date') !== \Carbon\Carbon::today()->toDateString())
                        <a href="{{ route('kajur.attendance') }}" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors shrink-0" title="Reset Filter">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </x-card>

    <!-- Table Card -->
    <x-card noPadding>
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs min-w-[1100px]">
                <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-5 min-w-[200px]">Identitas Siswa</th>
                        <th class="py-3.5 px-4 min-w-[110px]">Tanggal</th>
                        <th class="py-3.5 px-3 text-center min-w-[100px]">Swafoto</th>
                        <th class="py-3.5 px-4 min-w-[140px]">Jam Presensi</th>
                        <th class="py-3.5 px-4 min-w-[160px]">Mitra DU/DI</th>
                        <th class="py-3.5 px-4 min-w-[140px]">Lokasi & GPS</th>
                        <th class="py-3.5 px-4 min-w-[110px]">Status</th>
                        <th class="py-3.5 px-5 min-w-[150px]">Catatan Capaian</th>
                        <th class="py-3.5 px-4 text-center table-sticky-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($attendances as $a)
                        @php
                            $spkl = $a->studentPkl;
                            $student = $spkl?->student ?? $a->student;
                            $studentName = $student?->user?->name ?? $student?->name ?? 'Siswa';
                            $studentNisn = $student?->nisn ?? '-';
                            $companyName = $spkl?->company?->name ?? '-';

                            $detailPayload = [
                                'studentName' => $studentName,
                                'studentNisn' => $studentNisn,
                                'companyName' => $companyName,
                                'date' => \Carbon\Carbon::parse($a->date)->format('d/m/Y'),
                                'checkinTime' => $a->checkin_time ? substr($a->checkin_time, 0, 5) : null,
                                'checkoutTime' => $a->checkout_time ? substr($a->checkout_time, 0, 5) : null,
                                'status' => $a->status,
                                'photoUrl' => $a->checkin_photo_path ? $a->checkin_photo_url : null,
                                'isWithinRadius' => $a->is_within_radius,
                                'studentLat' => $a->checkin_lat ? (float) $a->checkin_lat : null,
                                'studentLng' => $a->checkin_lng ? (float) $a->checkin_lng : null,
                                'companyLat' => $spkl?->company?->latitude ? (float) $spkl->company->latitude : null,
                                'companyLng' => $spkl?->company?->longitude ? (float) $spkl->company->longitude : null,
                                'radiusMeters' => $spkl?->company?->radius_meters ?? null,
                                'notes' => $a->checkout_notes ?? '',
                            ];
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors group">
                            <td class="py-3.5 px-5">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-full bg-emerald-50 border border-emerald-200/80 text-emerald-800 font-black text-xs flex items-center justify-center shrink-0 shadow-2xs">
                                        {{ strtoupper(substr($studentName, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <span class="font-bold text-slate-900 block truncate text-xs sm:text-sm uppercase tracking-tight">{{ $studentName }}</span>
                                        <span class="text-[11px] text-slate-400 font-mono">NISN: {{ $studentNisn }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-800 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($a->date)->format('d/m/Y') }}
                            </td>
                            <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                @if($a->checkin_photo_path)
                                    <button type="button" 
                                            onclick="openImageLightbox('{{ $a->checkin_photo_url }}')" 
                                            class="inline-block group cursor-pointer relative" 
                                            title="Klik untuk melihat swafoto ukuran besar">
                                        <img src="{{ $a->checkin_photo_url }}" 
                                             class="w-10 h-10 rounded-xl object-cover border border-slate-200 shadow-2xs group-hover:scale-105 group-hover:border-emerald-500 transition-all" 
                                             alt="Swafoto {{ $studentName }}">
                                        <span class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-[#0B2B25] text-white flex items-center justify-center shadow-xs">
                                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                        </span>
                                    </button>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[10px] text-slate-400 font-medium italic bg-slate-100/80 px-2 py-1 rounded-lg">
                                        <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="4"/></svg>
                                        Tanpa Foto
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-700 whitespace-nowrap">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">IN :</span>
                                        @if($a->checkin_time)
                                            <span class="font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded text-[11px]">{{ substr($a->checkin_time, 0, 5) }}</span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">OUT:</span>
                                        @if($a->checkout_time)
                                            <span class="font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded text-[11px]">{{ substr($a->checkout_time, 0, 5) }}</span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-slate-900 block truncate max-w-[200px]" title="{{ $companyName }}">{{ $companyName }}</span>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="space-y-1">
                                    <div>
                                        @if($a->is_within_radius === true)
                                            <x-badge variant="success" size="xs">Dalam Radius</x-badge>
                                        @elseif($a->is_within_radius === false)
                                            <x-badge variant="danger" size="xs">Luar Radius</x-badge>
                                        @else
                                            <span class="text-[10px] text-slate-400 font-mono">-</span>
                                        @endif
                                    </div>
                                    @if($a->checkin_lat && $a->checkin_lng)
                                        <div>
                                            <a href="https://www.google.com/maps?q={{ $a->checkin_lat }},{{ $a->checkin_lng }}" 
                                               target="_blank" 
                                               rel="noopener noreferrer" 
                                               class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-600 hover:text-rose-800 hover:underline" 
                                               title="Buka titik koordinat presensi siswa di Google Maps">
                                                <svg class="w-3.5 h-3.5 text-rose-500 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 010 5z"/></svg>
                                                <span>Peta GPS</span>
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <x-badge :variant="$a->status" size="xs">
                                    {{ strtoupper(str_replace('_', ' ', $a->status)) }}
                                </x-badge>
                            </td>
                            <td class="py-3.5 px-5 text-[11px] text-slate-500 max-w-[180px] truncate font-medium" title="{{ $a->checkout_notes }}">
                                {{ $a->checkout_notes ?: '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap table-sticky-right">
                                <div class="flex items-center justify-center gap-1.5">
                                    @if($student)
                                        <form method="POST" action="{{ route('kajur.students.reset-password', $student) }}" class="inline" onsubmit="var p = prompt('Kata sandi sementara siswa (min. 8 karakter). Kosongkan untuk dibuatkan otomatis:'); if (p === null) return false; this.new_password.value = p; return confirm('Reset kata sandi siswa ini? Siswa wajib menggantinya saat login berikutnya.');">
                                            @csrf
                                            <input type="hidden" name="new_password" value="">
                                            <button type="submit" class="py-1.5 px-2.5 rounded-xl border border-amber-300 bg-amber-50 hover:bg-amber-100 text-amber-900 font-bold text-xs cursor-pointer shadow-2xs transition-all" title="Reset kata sandi siswa">Reset sandi</button>
                                        </form>
                                    @endif
                                    <button type="button"
                                            data-payload="{{ json_encode($detailPayload) }}"
                                            onclick="showAttendanceDetail(JSON.parse(this.dataset.payload))"
                                            class="py-1.5 px-2.5 rounded-xl border border-slate-200 bg-white hover:bg-emerald-50 hover:border-emerald-300 text-slate-700 hover:text-emerald-700 font-bold text-xs shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1.5"
                                            title="Buka rincian lengkap foto swafoto, jam, dan titik lokasi presensi">
                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <span>Detail</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12">
                                <x-empty-state 
                                    title="Tidak Ada Data Presensi" 
                                    description="Belum ada catatan presensi siswa jurusan yang sesuai dengan filter yang dipilih."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($attendances->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                <x-pagination :paginator="$attendances" />
            </div>
        @endif
    </x-card>

    <!-- Modal Reusable Detail Presensi (Geofencing Leaflet Minimap + Swafoto) -->
    <x-attendance-detail-modal />

</div>
@endsection
