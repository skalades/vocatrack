@extends('layouts.app')

@section('page_title', 'Approval Jurnal Siswa Magang (Tingkat 1 - DU/DI)')
@section('page_subtitle', 'Verifikasi kesesuaian aktivitas teknis di lapangan sebelum diteruskan ke sekolah')

@section('content')
<div class="space-y-6">

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-800">Daftar Jurnal Kerja Siswa Magang</h3>
            <p class="text-xs text-slate-500">Tinjau jurnal yang telah disubmit siswa untuk diteruskan ke guru sekolah</p>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($journals as $j)
                <div class="p-5 flex flex-col md:flex-row justify-between gap-4 hover:bg-slate-50/50 transition-colors">
                    <div class="space-y-2 flex-1">
                        <div class="flex items-center space-x-2">
                            <span class="font-bold text-slate-900 text-xs">{{ $j->studentPkl->student->user->name }}</span>
                            <span class="text-[11px] text-slate-500 font-mono">{{ \Carbon\Carbon::parse($j->date)->isoFormat('dddd, D MMMM Y') }}</span>
                            @php
                                $badge = match($j->status) {
                                    'draft' => 'bg-slate-100 text-slate-600',
                                    'submitted' => 'bg-amber-50 text-amber-700 border-amber-200 font-extrabold',
                                    'review_guru' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'revision' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-slate-100'
                                };
                            @endphp
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $badge }}">
                                {{ strtoupper($j->status) }}
                            </span>
                        </div>

                        <p class="text-xs text-slate-700 leading-relaxed bg-slate-50 p-3 rounded-xl border border-slate-100">
                            {{ $j->activity_description }}
                        </p>

                        <!-- Photos -->
                        @if($j->photos->isNotEmpty())
                            <div class="flex space-x-2 pt-1">
                                @foreach($j->photos as $ph)
                                    <a href="{{ asset('storage/' . $ph->photo_path) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $ph->photo_path) }}" class="w-16 h-16 rounded-xl object-cover border border-slate-200 hover:scale-105 transition-transform" alt="Dokumentasi">
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <!-- History of approvals -->
                        @if($j->approvals->isNotEmpty())
                            <div class="pt-2 text-[11px] text-slate-500 space-y-1">
                                @foreach($j->approvals as $ap)
                                    <p>
                                        <strong class="text-slate-700">{{ $ap->approver->name }} ({{ $ap->role_level }}):</strong>
                                        <span class="{{ $ap->status === 'approved' ? 'text-emerald-600 font-bold' : 'text-rose-600 font-bold' }}">
                                            {{ strtoupper($ap->status) }}
                                        </span>
                                        @if($ap->notes)
                                            <span class="italic text-slate-600">"{{ $ap->notes }}"</span>
                                        @endif
                                    </p>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Action form (DU/DI approval) -->
                    <div class="md:w-80">
                        @if($j->status === 'submitted')
                            <form action="{{ route('dudi.journals.process', $j->id) }}" method="POST" class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 space-y-2.5">
                                @csrf
                                <label class="block text-xs font-bold text-slate-700">Verifikasi Lapangan DU/DI</label>
                                <textarea name="notes" rows="2" placeholder="Catatan instruktur lapangan..." class="w-full text-xs p-2 rounded-xl bg-white border border-slate-200"></textarea>
                                <div class="flex space-x-2">
                                    <button type="submit" name="action" value="revision" onclick="return confirm('Minta revisi pada jurnal ini?')" class="flex-1 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs border border-rose-200">
                                        Minta Revisi
                                    </button>
                                    <button type="submit" name="action" value="approve" class="flex-1 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-200">
                                        Setujui Tingkat 1
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="text-right p-3">
                                <span class="text-xs text-slate-400 font-medium">Telah diproses.</span>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-8 text-center text-slate-400 text-xs">
                    Belum ada jurnal yang diajukan siswa magang.
                </div>
            @endforelse
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $journals->links() }}
        </div>
    </div>

</div>
@endsection
