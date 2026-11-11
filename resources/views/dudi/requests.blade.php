@extends('layouts.app')

@section('page_title', 'Verifikasi Dinas Luar & WFH (DU/DI)')
@section('page_subtitle', 'Tinjau dan setujui permohonan penugasan luar kantor siswa magang')

@section('content')
<div class="space-y-6">

    <x-page-header 
        title="Verifikasi Dinas Luar & WFH" 
        subtitle="Mentor industri DU/DI bertindak sebagai verifikator utama permohonan penugasan dinas luar dan kerja dari rumah (WFH)."
    />

    <x-card noPadding>
        <div class="divide-y divide-slate-100">
            @forelse($requests as $req)
                <div class="p-5 sm:p-6 flex flex-col md:flex-row md:items-start justify-between gap-5 hover:bg-slate-50/60 transition-colors">
                    
                    <div class="space-y-2 flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-extrabold text-slate-900 text-sm">{{ $req->studentPkl->student->user->name }}</span>
                            <x-badge :variant="$req->type" size="xs">
                                {{ strtoupper(str_replace('_', ' ', $req->type)) }}
                            </x-badge>
                        </div>

                        <p class="text-xs text-slate-700 font-medium flex flex-wrap items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>Periode: <strong>{{ \Carbon\Carbon::parse($req->start_date)->format('d M Y') }}</strong> s.d. <strong>{{ \Carbon\Carbon::parse($req->end_date)->format('d M Y') }}</strong></span>
                            @if($req->destination_location)
                                <span>&bull;</span>
                                <span>Lokasi Tujuan: <strong class="text-slate-900">{{ $req->destination_location }}</strong></span>
                            @endif
                        </p>

                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 text-xs text-slate-700 leading-relaxed font-medium">
                            "{{ $req->reason }}"
                        </div>
                    </div>

                    <!-- Action form -->
                    <div class="md:w-80 shrink-0">
                        @if($req->status === 'pending')
                            <form action="{{ route('dudi.requests.process', $req->id) }}" method="POST" class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2.5">
                                @csrf
                                <label class="block text-[11px] font-bold text-slate-700">Keputusan Pembimbing DU/DI</label>
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
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Status Pengajuan</span>
                                <x-badge :variant="$req->status" size="sm">
                                    {{ strtoupper($req->status) }}
                                </x-badge>
                                @if($req->reviewer_notes)
                                    <p class="text-xs text-slate-600 italic mt-1 font-medium">"{{ $req->reviewer_notes }}"</p>
                                @endif
                            </div>
                        @endif
                    </div>

                </div>
            @empty
                <div class="py-12">
                    <x-empty-state 
                        title="Tidak Ada Pengajuan Masuk" 
                        description="Belum ada permohonan dinas luar atau kerja dari rumah (WFH) yang diajukan oleh siswa magang."
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

</div>
@endsection
