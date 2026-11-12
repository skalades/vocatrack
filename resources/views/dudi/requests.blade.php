@extends('layouts.app')

@section('page_title', 'Verifikasi Dinas Luar & WFH (DU/DI)')
@section('page_subtitle', 'Tinjau dan setujui permohonan penugasan luar kantor siswa magang')

@section('content')
<div class="w-full space-y-6">

    <!-- Enterprise Hero Banner -->
    <x-page-header 
        title="Verifikasi Dinas Luar & WFH Siswa Magang" 
        subtitle="Mentor industri DU/DI bertindak sebagai verifikator utama permohonan penugasan dinas luar dan kerja fleksibel/dari rumah (WFH)."
        badge="PEMBIMBING INDUSTRI DU/DI"
    >
        <x-button type="button" variant="secondary" size="sm" onclick="window.location.href='{{ route('dudi.attendances') }}'">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            <span>Validasi Presensi Harian</span>
        </x-button>
        <x-button type="button" variant="primary" size="sm" onclick="window.location.href='{{ route('dudi.journals') }}'">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Approval Jurnal (Tingkat 1)</span>
        </x-button>
    </x-page-header>

    <!-- Top KPI Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card 
            title="Total Pengajuan Dinas / WFH" 
            :value="$stats['total'] ?? $requests->total()" 
            subtitle="Seluruh arsip penugasan"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Menunggu Persetujuan" 
            :value="$stats['pending'] ?? 0" 
            subtitle="Perlu verifikasi mentor"
            :color="($stats['pending'] ?? 0) > 0 ? 'amber' : 'indigo'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Telah Disetujui" 
            :value="$stats['approved'] ?? 0" 
            subtitle="Otomatis valid di presensi"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Pengajuan Ditolak" 
            :value="$stats['rejected'] ?? 0" 
            subtitle="Tidak disetujui mentor"
            color="rose"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Requests List Card -->
    <x-card noPadding>
        <div class="p-4 sm:p-5 bg-white border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <input type="checkbox" id="master-checkbox-dudi-requests" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" title="Pilih Semua Permohonan Pending">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Antrean Pengajuan Dinas Luar & WFH</h3>
                    <p class="text-xs text-slate-500 font-medium">Validasi lokasi tujuan dan alasan tugas dinas siswa</p>
                </div>
            </div>
            <span class="text-xs text-slate-500 font-semibold">
                {{ $requests->count() }} data di halaman ini
            </span>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($requests as $req)
                <div class="p-5 sm:p-6 flex flex-col md:flex-row md:items-start justify-between gap-5 hover:bg-slate-50/60 transition-colors">
                    
                    <div class="space-y-3 flex-1 min-w-0">
                        <div class="flex items-center space-x-3.5">
                            @if($req->status === 'pending')
                                <input type="checkbox" value="{{ $req->id }}" class="bulk-checkbox-dudi-requests rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 shrink-0">
                            @else
                                <span class="w-4 h-4 shrink-0"></span>
                            @endif
                            <div class="w-10 h-10 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-700 font-black text-xs flex items-center justify-center shrink-0 shadow-xs shadow-indigo-600/10">
                                {{ substr($req->studentPkl->student->user->name, 0, 1) }}
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-extrabold text-slate-900 text-sm truncate">{{ $req->studentPkl->student->user->name }}</span>
                                    <x-badge :variant="$req->type" size="xs">
                                        {{ strtoupper(str_replace('_', ' ', $req->type)) }}
                                    </x-badge>
                                    <x-badge :variant="$req->status" size="xs">
                                        {{ strtoupper($req->status) }}
                                    </x-badge>
                                </div>
                                <span class="text-xs text-slate-500 font-medium block truncate mt-0.5">
                                    {{ $req->studentPkl->student->class->name ?? 'Kelas' }} &bull; NISN: {{ $req->studentPkl->student->nisn }}
                                </span>
                            </div>
                        </div>

                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 text-xs text-slate-800 leading-relaxed font-medium space-y-2">
                            <div class="flex flex-wrap items-center gap-3 text-slate-500 font-semibold">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span>Periode: <strong class="text-slate-900">{{ \Carbon\Carbon::parse($req->start_date)->format('d M Y') }}</strong> s.d. <strong class="text-slate-900">{{ \Carbon\Carbon::parse($req->end_date)->format('d M Y') }}</strong></span>
                                </span>
                                @if($req->destination_location)
                                    <span>&bull;</span>
                                    <span class="flex items-center gap-1.5 text-indigo-700">
                                        <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                        <span>Lokasi: <strong class="text-slate-900">{{ $req->destination_location }}</strong></span>
                                    </span>
                                @endif
                            </div>
                            <p class="text-slate-700">"{{ $req->reason }}"</p>
                        </div>
                    </div>

                    <!-- Action form -->
                    <div class="md:w-80 shrink-0">
                        @if($req->status === 'pending')
                            <form action="{{ route('dudi.requests.process', $req->id) }}" method="POST" class="p-4.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3">
                                @csrf
                                <label class="block text-xs font-bold text-slate-800">Keputusan Mentor DU/DI</label>
                                <input type="text" name="reviewer_notes" placeholder="Catatan atau instruksi mentor..." 
                                       class="w-full text-xs p-2.5 rounded-xl bg-white border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 font-medium text-slate-900">
                                
                                <div class="flex items-center gap-2 pt-1">
                                    <button type="submit" name="action" value="reject" onclick="return confirm('Tolak permohonan dinas luar/WFH ini?')" 
                                            class="flex-1 py-2 rounded-xl bg-white hover:bg-rose-50 text-rose-600 font-bold text-xs border border-rose-200 hover:border-rose-300 transition-colors cursor-pointer">
                                        Tolak
                                    </button>
                                    <button type="submit" name="action" value="approve" 
                                            class="flex-1 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs shadow-emerald-600/20 transition-all cursor-pointer">
                                        Setujui
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/70 text-right space-y-1">
                                <span class="text-xs font-bold text-slate-600 block">Status: {{ ucfirst(str_replace('_', ' ', $req->status)) }}</span>
                                @if($req->reviewer_notes)
                                    <p class="text-[11px] text-slate-500 italic">"{{ $req->reviewer_notes }}"</p>
                                @endif
                            </div>
                        @endif
                    </div>

                </div>
            @empty
                <div class="py-12">
                    <x-empty-state 
                        title="Tidak Ada Permohonan Dinas / WFH" 
                        description="Belum ada permohonan tugas dinas luar atau WFH dari siswa magang di perusahaan Anda."
                    />
                </div>
            @endforelse
        </div>

        @if($requests->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $requests->links() }}
            </div>
        @endif
    </x-card>

    <!-- Bulk Action Bar -->
    <x-bulk-action-bar 
        barId="dudi-requests"
        :formAction="route('dudi.requests.bulk')"
        idParam="request_ids[]"
        itemLabel="permohonan tugas dinas"
        rejectActionLabel="Tolak Permohonan"
        rejectActionValue="reject"
        :notesRequired="false"
    />

</div>
@endsection
