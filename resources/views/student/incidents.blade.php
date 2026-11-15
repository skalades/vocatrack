@extends('layouts.student-mobile')

@section('title', 'Laporan Kendala & SOS — VokaTrack Siswa')

@section('content')
<div class="space-y-4">

    <!-- Top Navigation & Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <a href="{{ route('student.profile') }}" class="p-2 rounded-2xl bg-white border border-slate-200/80 text-slate-600 hover:text-slate-900 shadow-2xs active:scale-95 transition-all">
                <svg class="w-4 h-4 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-base sm:text-lg font-black text-slate-900 tracking-tight leading-snug">Laporan Kendala & SOS</h1>
                <p class="text-xs text-slate-500 font-medium">Pantau tindak lanjut laporan keselamatan Anda</p>
            </div>
        </div>

        <button type="button" onclick="document.getElementById('modal-incident-report').classList.remove('hidden')" 
                class="px-3 py-2 rounded-2xl bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-500 hover:to-red-500 text-white font-bold text-xs shadow-md shadow-rose-600/25 border border-rose-400/30 flex items-center gap-1.5 active:scale-95 transition-all cursor-pointer">
            <svg class="w-4 h-4 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span>Lapor Baru</span>
        </button>
    </div>

    <!-- Confidentiality Banner -->
    <div class="p-3.5 rounded-2xl bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200/80 text-amber-900 text-xs leading-relaxed flex items-start gap-2.5 shadow-2xs">
        <div class="w-7 h-7 rounded-xl bg-amber-100 text-amber-700 border border-amber-300/80 flex items-center justify-center shrink-0 mt-0.5">
            <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        </div>
        <div>
            <span class="font-extrabold block text-amber-950">Kerahasiaan Terjamin (Strict Privacy)</span>
            <p class="text-[11px] text-amber-800 font-medium mt-0.5">
                Semua laporan bersifat rahasia dan <strong>tidak dapat dilihat oleh pihak DU/DI</strong>. Laporan ditujukan khusus ke Guru Pembimbing dan Admin Sekolah untuk melindungi hak dan keselamatan Anda.
            </p>
        </div>
    </div>

    <!-- List of Reports -->
    @if($incidents->isEmpty())
        <!-- Empty State -->
        <div class="p-8 rounded-3xl bg-white border border-slate-200/80 shadow-xs text-center space-y-3">
            <div class="w-16 h-16 mx-auto rounded-3xl bg-slate-50 border border-slate-200/80 flex items-center justify-center text-slate-400">
                <svg class="w-8 h-8 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-extrabold text-slate-900">Belum Ada Laporan Kendala</h3>
                <p class="text-xs text-slate-500 font-medium max-w-xs mx-auto mt-1 leading-relaxed">
                    Jika Anda mengalami ketidaksesuaian tugas, masalah administrasi, perlakuan tidak menyenangkan, atau situasi darurat di tempat PKL, sampaikan melalui tombol di bawah.
                </p>
            </div>
            <div class="pt-2">
                <button type="button" onclick="document.getElementById('modal-incident-report').classList.remove('hidden')" 
                        class="px-4 py-2.5 rounded-2xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs shadow-md shadow-rose-600/30 transition-all cursor-pointer">
                    Buat Laporan Kendala / SOS
                </button>
            </div>
        </div>
    @else
        <div class="space-y-3.5">
            @foreach($incidents as $incident)
                @php
                    $categoryLabels = [
                        'administrative' => 'Administrasi / Jam Kerja',
                        'curriculum_mismatch' => 'Mismatch Kompetensi',
                        'interpersonal' => 'Relasi Lingkungan',
                        'safety_health' => 'K3 & Keselamatan',
                        'emergency' => 'Darurat & Pelecehan (SOS)',
                    ];
                    $categoryName = $categoryLabels[$incident->category] ?? ucfirst($incident->category);

                    $urgencyConfig = match ($incident->urgency) {
                        'critical' => ['label' => 'SOS Kritis', 'badge' => 'bg-rose-50 text-rose-700 border-rose-200/80'],
                        'high' => ['label' => 'Tinggi', 'badge' => 'bg-orange-50 text-orange-700 border-orange-200/80'],
                        'medium' => ['label' => 'Sedang', 'badge' => 'bg-amber-50 text-amber-700 border-amber-200/80'],
                        default => ['label' => 'Rendah', 'badge' => 'bg-slate-100 text-slate-700 border-slate-200/80'],
                    };

                    $statusConfig = match ($incident->status) {
                        'resolved' => ['label' => 'Selesai Ditangani', 'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200/80', 'dot' => 'bg-emerald-500'],
                        'in_progress' => ['label' => 'Sedang Ditindaklanjuti', 'badge' => 'bg-[#DDF3EC] text-indigo-700 border-[#A3DFC8]/80', 'dot' => 'bg-[#DDF3EC]0 animate-pulse'],
                        default => ['label' => 'Menunggu Respon Guru', 'badge' => 'bg-amber-50 text-amber-700 border-amber-200/80', 'dot' => 'bg-amber-500 animate-pulse'],
                    };
                @endphp

                <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs space-y-3.5">
                    <!-- Card Top Header -->
                    <div class="flex items-start justify-between gap-2 pb-3 border-b border-slate-100">
                        <div class="space-y-1">
                            <div class="flex items-center flex-wrap gap-1.5">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border {{ $urgencyConfig['badge'] }}">
                                    {{ $urgencyConfig['label'] }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-50 text-slate-600 border border-slate-200/80">
                                    {{ $categoryName }}
                                </span>
                            </div>
                            <span class="text-[11px] text-slate-400 font-medium block">
                                Dilaporkan {{ $incident->created_at->isoFormat('D MMMM Y, HH:mm') }} WIB
                            </span>
                        </div>

                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-extrabold border shrink-0 {{ $statusConfig['badge'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $statusConfig['dot'] }}"></span>
                            <span>{{ $statusConfig['label'] }}</span>
                        </span>
                    </div>

                    <!-- Subject & Description -->
                    <div class="space-y-1.5">
                        <h3 class="text-sm font-black text-slate-900 leading-snug">
                            {{ $incident->subject }}
                        </h3>
                        <p class="text-xs text-slate-600 leading-relaxed font-medium whitespace-pre-line">
                            {{ $incident->description }}
                        </p>
                    </div>

                    <!-- Attachment if present -->
                    @if($incident->attachment_path)
                        <div class="pt-1">
                            <a href="{{ $incident->attachment_url }}" target="_blank" 
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-xs font-bold text-slate-700 transition-colors">
                                <svg class="w-3.5 h-3.5 text-slate-500 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                <span>Lihat Lampiran Foto Bukti</span>
                            </a>
                        </div>
                    @endif

                    <!-- Teacher / School Staff Followup Logs -->
                    <div class="pt-3 border-t border-slate-100 space-y-2.5">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">
                            Catatan Tindak Lanjut Guru & Sekolah
                        </span>

                        @if($incident->followups->isEmpty())
                            <div class="p-3 rounded-2xl bg-slate-50/70 border border-slate-200/60 text-[11px] text-slate-500 font-medium flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Belum ada catatan tindak lanjut. Guru pembimbing telah menerima notifikasi laporan ini.</span>
                            </div>
                        @else
                            <div class="space-y-2">
                                @foreach($incident->followups as $followup)
                                    <div class="p-3.5 rounded-2xl bg-[#DDF3EC]/60 border border-indigo-100/90 space-y-1.5">
                                        <div class="flex items-center justify-between text-[10px]">
                                            <span class="font-black text-indigo-900 flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 text-[#0B5E4E] stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                <span>{{ $followup->user->name ?? 'Staf Sekolah' }}</span>
                                            </span>
                                            <span class="text-slate-400 font-medium font-mono">
                                                {{ $followup->created_at->isoFormat('D MMM Y, HH:mm') }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-700 leading-relaxed font-medium pl-5">
                                            {{ $followup->action_note }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if($incident->status !== 'resolved')
                            <form action="{{ route('student.incident.followup', $incident->id) }}" method="POST" class="pt-2 border-t border-slate-100 flex items-center gap-2">
                                @csrf
                                <input type="text" name="action_note" required placeholder="Tulis update perkembangan kondisi untuk guru..." 
                                       class="flex-1 h-10 rounded-xl bg-slate-50 border border-slate-200 px-3 text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-[#0F7A66] focus:bg-white">
                                <button type="submit" class="h-10 px-3.5 rounded-xl bg-[#0B2B25] hover:bg-[#12382F] text-white text-xs font-bold active:scale-95 transition-all shrink-0 cursor-pointer">
                                    Kirim
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
