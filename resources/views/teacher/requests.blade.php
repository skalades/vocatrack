@extends('layouts.app')

@section('page_title', 'Verifikasi Permohonan Izin & Sakit')
@section('page_subtitle', 'Tinjau dan setujui permohonan presensi khusus siswa bimbingan')

@section('content')
<div class="space-y-6">

    <x-page-header 
        title="Verifikasi Izin & Sakit Siswa" 
        subtitle="Guru pembimbing bertindak sebagai verifikator utama permohonan dispensasi presensi khusus siswa."
    />

    <x-card noPadding>
        <div class="divide-y divide-slate-100">
            @forelse($requests as $req)
                <div class="p-5 sm:p-6 flex flex-col md:flex-row md:items-start justify-between gap-5 hover:bg-slate-50/60 transition-colors">
                    
                    <div class="space-y-2 flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-extrabold text-slate-900 text-sm">{{ $req->studentPkl->student->user->name }}</span>
                            <span class="text-xs text-slate-500 font-medium">({{ $req->studentPkl->company->name }})</span>
                            <x-badge :variant="$req->type" size="xs">
                                {{ strtoupper($req->type) }}
                            </x-badge>
                        </div>

                        <p class="text-xs text-slate-700 font-medium flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>Periode: <strong>{{ \Carbon\Carbon::parse($req->start_date)->format('d M Y') }}</strong> s.d. <strong>{{ \Carbon\Carbon::parse($req->end_date)->format('d M Y') }}</strong></span>
                        </p>

                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 text-xs text-slate-700 leading-relaxed font-medium">
                            "{{ $req->reason }}"
                        </div>

                        @if($req->attachment_path)
                            <a href="{{ asset('storage/' . $req->attachment_path) }}" target="_blank" 
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold transition-colors">
                                <svg class="w-3.5 h-3.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                <span>Lihat Lampiran Bukti Surat</span>
                            </a>
                        @endif
                    </div>

                    <!-- Action Decision Form -->
                    <div class="md:w-80 shrink-0">
                        @if($req->status === 'pending')
                            <form action="{{ route('guru.requests.process', $req->id) }}" method="POST" class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2.5">
                                @csrf
                                <label class="block text-[11px] font-bold text-slate-700">Keputusan Verifikasi Guru</label>
                                <input type="text" name="reviewer_notes" placeholder="Tuliskan catatan atau instruksi..." 
                                       class="w-full text-xs p-2.5 rounded-xl bg-white border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 font-medium text-slate-900">
                                
                                <div class="flex items-center gap-2 pt-1">
                                    <button type="submit" name="action" value="reject" onclick="return confirm('Tolak permohonan ini?')" 
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
                        title="Tidak Ada Permohonan Masuk" 
                        description="Seluruh permohonan izin dan sakit siswa binaan telah diproses atau belum ada yang mengajukan."
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
