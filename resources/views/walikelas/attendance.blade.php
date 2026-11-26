@extends('layouts.app')

@section('page_title', 'Monitoring Presensi Rombel')
@section('page_subtitle', 'Pantau kehadiran harian siswa kelas yang Anda ampu')

@section('content')
<div class="w-full space-y-6">

    <x-page-header 
        title="Monitoring Presensi Rombel" 
        subtitle="Rekapitulasi kehadiran harian peserta didik rombel Anda di lokasi mitra industri (Read-Only)."
        badge="MONITORING WALI KELAS"
    />

    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Total Presensi</span>
            <span class="text-xl font-black text-slate-900 mt-1 block">{{ $stats['total'] }}</span>
        </div>
        <div class="p-4 rounded-2xl bg-emerald-50/60 border border-emerald-200/60 shadow-2xs">
            <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider block">Hadir Tepat</span>
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

    <x-card>
        <form method="GET" action="{{ route('walikelas.attendance') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
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

            @if($classes->count() > 1)
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Rombel Kelas</label>
                    <select name="class_id" class="w-full text-xs sm:text-sm px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                        <option value="">-- Semua Rombel --</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

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
                    <x-button type="submit" variant="primary" size="md" class="shrink-0 px-4">Filter</x-button>
                </div>
            </div>
        </form>
    </x-card>

    <x-card noPadding>
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs min-w-[960px]">
                <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-5">Identitas Siswa</th>
                        <th class="py-3.5 px-4">Tanggal</th>
                        <th class="py-3.5 px-4">Jam Masuk / Pulang</th>
                        <th class="py-3.5 px-4">Mitra DU/DI</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($attendances as $a)
                        @php
                            $spkl = $a->studentPkl;
                            $student = $spkl?->student;
                            $studentName = $student?->user?->name ?? 'Siswa';
                            $companyName = $spkl?->company?->name ?? '-';
                            $detailPayload = [
                                'studentName' => $studentName,
                                'studentNisn' => $student?->nisn ?? '-',
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
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3.5 px-5">
                                <div class="font-bold text-slate-900">{{ $studentName }}</div>
                                <div class="text-[11px] text-slate-400">{{ $student?->schoolClass?->name ?? '-' }} &bull; NISN: {{ $student?->nisn ?? '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-800 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($a->date)->format('d/m/Y') }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[11px] whitespace-nowrap">
                                IN: <strong>{{ $a->checkin_time ? substr($a->checkin_time, 0, 5) : '-' }}</strong> &bull;
                                OUT: <strong>{{ $a->checkout_time ? substr($a->checkout_time, 0, 5) : '-' }}</strong>
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-900">{{ $companyName }}</td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <x-badge :variant="$a->status" size="xs">{{ strtoupper(str_replace('_', ' ', $a->status)) }}</x-badge>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <button type="button"
                                        data-payload="{{ json_encode($detailPayload) }}"
                                        onclick="showAttendanceDetail(JSON.parse(this.dataset.payload))"
                                        class="py-1.5 px-2.5 rounded-xl border border-slate-200 bg-white hover:bg-emerald-50 hover:border-emerald-300 text-slate-700 hover:text-emerald-700 font-bold text-xs cursor-pointer">
                                    Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12">
                                <x-empty-state title="Tidak Ada Data Presensi" description="Belum ada catatan presensi siswa rombel yang sesuai filter." />
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

    <x-attendance-detail-modal />
</div>
@endsection
