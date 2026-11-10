@extends('layouts.student-mobile')

@section('title', 'Jurnal PKL — VokaTrack Siswa')

@section('content')
<div class="space-y-4">

    <!-- Header Title -->
    <div>
        <h2 class="text-base font-extrabold text-slate-900">Jurnal Harian PKL</h2>
        <p class="text-xs text-slate-500">Catat pekerjaan, kendala teknis, dan bukti kegiatan industri Anda.</p>
    </div>

    <!-- Journal Input Card -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-xs font-bold text-slate-800">
                {{ $todayJournal ? 'Sunting Jurnal Hari Ini' : 'Tulis Jurnal Baru' }}
            </h3>
            @if($todayJournal)
                @php
                    $badge = match($todayJournal->status) {
                        'draft' => 'bg-slate-100 text-slate-700 border-slate-200',
                        'submitted' => 'bg-amber-50 text-amber-700 border-amber-200',
                        'review_guru' => 'bg-blue-50 text-blue-700 border-blue-200',
                        'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'revision' => 'bg-rose-50 text-rose-700 border-rose-200',
                        default => 'bg-slate-100 text-slate-600'
                    };
                @endphp
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $badge }}">
                    {{ strtoupper($todayJournal->status) }}
                </span>
            @endif
        </div>

        @if($todayJournal && in_array($todayJournal->status, ['submitted', 'review_guru', 'approved']))
            <div class="p-3.5 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-900 space-y-2">
                <p class="text-xs font-semibold">Jurnal hari ini telah berstatus <strong>{{ strtoupper($todayJournal->status) }}</strong>.</p>
                <p class="text-[11px] text-indigo-700">Data jurnal terkunci untuk proses verifikasi oleh mentor industri dan guru pembimbing.</p>
                <div class="bg-white/80 p-3 rounded-xl border border-indigo-100 text-xs text-slate-700">
                    {{ $todayJournal->activity_description }}
                </div>
            </div>
        @else
            <form action="{{ route('student.journal.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Kegiatan</label>
                    <input type="date" name="date" value="{{ old('date', $todayJournal ? $todayJournal->date->toDateString() : \Carbon\Carbon::today()->toDateString()) }}" required
                           class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs font-bold text-slate-700">Uraian Pekerjaan / Kompetensi</label>
                        <span id="char-counter" class="text-[10px] text-slate-400 font-mono">0 / min 50 karakter</span>
                    </div>
                    <textarea name="activity_description" id="journal-desc" rows="4" required minlength="50" oninput="updateCharCount()"
                              placeholder="Uraikan secara spesifik apa saja yang dikerjakan, alat/teknologi yang digunakan, serta solusi atas kendala..."
                              class="w-full text-xs p-3 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('activity_description', $todayJournal->activity_description ?? '') }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Foto Dokumentasi (1–3 Foto)</label>
                    <input type="file" name="photos[]" multiple accept="image/*" class="w-full text-xs text-slate-600 file:mr-2 file:py-2 file:px-3 file:rounded-xl file:border-0 file:bg-slate-100 file:text-slate-700 file:font-semibold">
                </div>

                <div class="grid grid-cols-2 gap-2 pt-2">
                    <button type="submit" name="action_type" value="draft" class="py-2.5 px-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition-colors">
                        Simpan Draft
                    </button>
                    <button type="submit" name="action_type" value="submit" class="py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-200 transition-colors">
                        Ajukan Review (Submit)
                    </button>
                </div>
            </form>
        @endif
    </div>

    <!-- Previous Journals List -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm space-y-3">
        <h3 class="text-xs font-bold text-slate-800">Daftar Jurnal Terkirim</h3>

        @if($journals->isEmpty())
            <p class="text-xs text-slate-400 py-4 text-center">Belum ada jurnal yang dicatat.</p>
        @else
            <div class="space-y-3">
                @foreach($journals as $j)
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-800">
                                {{ \Carbon\Carbon::parse($j->date)->isoFormat('dddd, D MMMM Y') }}
                            </span>
                            @php
                                $badge = match($j->status) {
                                    'draft' => 'bg-slate-200 text-slate-700',
                                    'submitted' => 'bg-amber-100 text-amber-800',
                                    'review_guru' => 'bg-blue-100 text-blue-800',
                                    'approved' => 'bg-emerald-100 text-emerald-800',
                                    'revision' => 'bg-rose-100 text-rose-800',
                                    default => 'bg-slate-100'
                                };
                            @endphp
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $badge }}">
                                {{ strtoupper($j->status) }}
                            </span>
                        </div>

                        <p class="text-xs text-slate-600 leading-relaxed">{{ $j->activity_description }}</p>

                        <!-- Photos preview -->
                        @if($j->photos->isNotEmpty())
                            <div class="flex space-x-2 pt-1 overflow-x-auto">
                                @foreach($j->photos as $ph)
                                    <img src="{{ asset('storage/' . $ph->photo_path) }}" class="w-14 h-14 rounded-lg object-cover border border-slate-200" alt="Dokumentasi">
                                @endforeach
                            </div>
                        @endif

                        <!-- Review feedback history -->
                        @if($j->approvals->isNotEmpty())
                            <div class="mt-2 pt-2 border-t border-slate-200/60 space-y-1">
                                @foreach($j->approvals as $appr)
                                    <div class="text-[10px] text-slate-500">
                                        <span class="font-bold text-slate-700">{{ $appr->approver->name }} ({{ $appr->role_level }}):</span>
                                        <span class="{{ $appr->status === 'approved' ? 'text-emerald-600' : 'text-rose-600' }} font-bold">[{{ strtoupper($appr->status) }}]</span>
                                        @if($appr->notes)
                                            <span class="italic text-slate-600">"{{ $appr->notes }}"</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Re-submit button if revision or draft -->
                        @if(in_array($j->status, ['draft', 'revision']))
                            <form action="{{ route('student.journal.submit', $j->id) }}" method="POST" class="pt-1">
                                @csrf
                                <button type="submit" class="w-full py-1.5 px-3 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-[11px] border border-indigo-200 transition-colors">
                                    Ajukan Review Ulang
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="pt-2">
                {{ $journals->links() }}
            </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
    function updateCharCount() {
        const textarea = document.getElementById('journal-desc');
        const counter = document.getElementById('char-counter');
        const len = textarea.value.length;
        counter.innerText = len + ' / min 50 karakter';
        if (len >= 50) {
            counter.className = 'text-[10px] text-emerald-600 font-bold font-mono';
        } else {
            counter.className = 'text-[10px] text-slate-400 font-mono';
        }
    }
    document.addEventListener('DOMContentLoaded', updateCharCount);
</script>
@endpush
@endsection
