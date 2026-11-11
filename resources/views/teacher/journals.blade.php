@extends('layouts.app')

@section('page_title', 'Review Jurnal PKL (Tingkat 2 — Guru Pembimbing)')
@section('page_subtitle', 'Verifikasi kesesuaian kurikulum kejuruan siswa setelah disetujui pihak industri')

@section('content')
<div class="space-y-6">

    <x-page-header 
        title="Review Jurnal PKL (Tingkat 2)" 
        subtitle="Jurnal kegiatan harian siswa yang telah disetujui pihak DU/DI akan tampil untuk persetujuan akhir Anda."
    />

    <x-card noPadding>
        <div class="divide-y divide-slate-100">
            @forelse($journals as $j)
                <div class="p-5 sm:p-6 flex flex-col md:flex-row md:items-start justify-between gap-5 hover:bg-slate-50/60 transition-colors">
                    
                    <div class="space-y-2.5 flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-extrabold text-slate-900 text-sm">{{ $j->studentPkl->student->user->name }}</span>
                            <span class="text-xs text-slate-500 font-mono">{{ \Carbon\Carbon::parse($j->date)->isoFormat('dddd, D MMMM Y') }}</span>
                            <x-badge :variant="$j->status" size="xs">
                                {{ strtoupper($j->status) }}
                            </x-badge>
                        </div>

                        <p class="text-xs text-slate-700 leading-relaxed bg-slate-50 p-3.5 rounded-2xl border border-slate-100 font-medium">
                            {{ $j->activity_description }}
                        </p>

                        <!-- Photos Preview -->
                        @if($j->photos->isNotEmpty())
                            <div class="flex items-center gap-2 pt-1 overflow-x-auto">
                                @foreach($j->photos as $ph)
                                    <a href="{{ asset('storage/' . $ph->photo_path) }}" target="_blank" class="shrink-0 group">
                                        <img src="{{ asset('storage/' . $ph->photo_path) }}" class="w-16 h-16 rounded-xl object-cover border border-slate-200 shadow-2xs group-hover:opacity-90 group-hover:scale-105 transition-all" alt="Dokumentasi Kerja">
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <!-- History of 2-tier approvals -->
                        @if($j->approvals->isNotEmpty())
                            <div class="pt-2 text-[11px] text-slate-500 space-y-1 bg-slate-50/50 p-3 rounded-xl border border-slate-100">
                                @foreach($j->approvals as $ap)
                                    <p class="font-medium">
                                        <strong class="text-slate-800">{{ $ap->approver->name }} ({{ $ap->role_level }}):</strong>
                                        <span class="{{ $ap->status === 'approved' ? 'text-emerald-700 font-bold' : 'text-rose-700 font-bold' }}">
                                            [{{ strtoupper($ap->status) }}]
                                        </span>
                                        @if($ap->notes)
                                            <span class="italic text-slate-600 block mt-0.5">"{{ $ap->notes }}"</span>
                                        @endif
                                    </p>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Action form (Review Guru) -->
                    <div class="md:w-80 shrink-0">
                        @if($j->status === 'review_guru')
                            <form action="{{ route('guru.journals.process', $j->id) }}" method="POST" class="p-4 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-2.5">
                                @csrf
                                <label class="block text-xs font-bold text-slate-900">Aksi Persetujuan Tingkat 2</label>
                                <textarea name="notes" rows="2" placeholder="Catatan evaluasi kurikulum atau alasan revisi..." 
                                          class="w-full text-xs p-2.5 rounded-xl bg-white border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 font-medium text-slate-800 leading-relaxed"></textarea>
                                
                                <div class="flex items-center gap-2 pt-1">
                                    <button type="submit" name="action" value="revision" onclick="return confirm('Minta siswa merevisi jurnal ini?')" 
                                            class="flex-1 py-2 rounded-xl bg-white hover:bg-rose-50 text-rose-600 font-bold text-xs border border-rose-200 hover:border-rose-300 transition-colors cursor-pointer">
                                        Minta Revisi
                                    </button>
                                    <button type="submit" name="action" value="approve" 
                                            class="flex-1 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs shadow-emerald-600/20 transition-all cursor-pointer">
                                        Setujui (Final)
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/70 text-right">
                                <span class="text-xs text-slate-400 font-medium">Verifikasi guru telah selesai.</span>
                            </div>
                        @endif
                    </div>

                </div>
            @empty
                <div class="py-12">
                    <x-empty-state 
                        title="Belum Ada Jurnal Siap Review" 
                        description="Jurnal harian siswa akan masuk ke antrean Anda setelah disetujui terlebih dahulu oleh pembimbing industri DU/DI."
                    />
                </div>
            @endforelse
        </div>

        @if($journals->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $journals->links() }}
            </div>
        @endif
    </x-card>

</div>
@endsection
