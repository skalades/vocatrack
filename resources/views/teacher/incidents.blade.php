@extends('layouts.app')

@section('page_title', 'Kendala & SOS Siswa Bimbingan')
@section('page_subtitle', 'Kanal aduan privat, whistleblowing, dan keselamatan siswa di industri binaan')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Enterprise Hero Banner -->
    <x-page-header 
        title="Laporan Kendala & Situasi Darurat Siswa" 
        subtitle="Daftar aduan resmi siswa bimbingan PKL. Demi keamanan siswa, laporan ini bersifat rahasia dan tidak dapat diakses pihak DU/DI."
        badge="KESELAMATAN SISWA"
    >
        <x-button type="button" variant="secondary" size="sm" onclick="window.location.href='{{ route('guru.monitoring') }}'">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span>Monitoring Absensi</span>
        </x-button>
        <x-button type="button" variant="primary" size="sm" onclick="window.location.href='{{ route('guru.journals') }}'">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Review Jurnal</span>
        </x-button>
    </x-page-header>

    <!-- KPI Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card 
            title="Total Laporan Masuk" 
            :value="$stats['total']" 
            subtitle="Seluruh aduan yang tercatat"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Butuh Tindak Lanjut" 
            :value="$stats['open']" 
            subtitle="Status Submitted / In Progress"
            :color="$stats['open'] > 0 ? 'amber' : 'emerald'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Darurat / SOS (Critical)" 
            :value="$stats['critical']" 
            subtitle="K3 / Ancaman keselamatan kerja"
            :color="$stats['critical'] > 0 ? 'rose' : 'indigo'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Telah Terselesaikan" 
            :value="$stats['resolved']" 
            subtitle="Selesai ditangani & dievaluasi"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('guru.incidents') }}" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <div class="flex items-center space-x-2">
                <label for="urgency" class="text-xs font-bold text-slate-700">Urgensi:</label>
                <select name="urgency" id="urgency" onchange="this.form.submit()" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-300 bg-white text-slate-800 focus:ring-2 focus:ring-indigo-500">
                    <option value="">Semua Urgensi</option>
                    <option value="critical" {{ request('urgency') === 'critical' ? 'selected' : '' }}>Critical (SOS Darurat)</option>
                    <option value="high" {{ request('urgency') === 'high' ? 'selected' : '' }}>High (Tinggi)</option>
                    <option value="medium" {{ request('urgency') === 'medium' ? 'selected' : '' }}>Medium (Sedang)</option>
                    <option value="low" {{ request('urgency') === 'low' ? 'selected' : '' }}>Low (Rendah)</option>
                </select>
            </div>

            <div class="flex items-center space-x-2">
                <label for="status" class="text-xs font-bold text-slate-700">Status:</label>
                <select name="status" id="status" onchange="this.form.submit()" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-300 bg-white text-slate-800 focus:ring-2 focus:ring-indigo-500">
                    <option value="">Semua Status</option>
                    <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Submitted (Baru)</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved (Selesai)</option>
                    <option value="dismissed" {{ request('status') === 'dismissed' ? 'selected' : '' }}>Dismissed (Ditutup)</option>
                </select>
            </div>

            @if(request()->hasAny(['urgency', 'status']))
                <a href="{{ route('guru.incidents') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 underline">
                    Reset Filter
                </a>
            @endif
        </form>

        <span class="text-xs font-medium text-slate-500">
            Menampilkan {{ $incidents->count() }} laporan
        </span>
    </div>

    <!-- Incident Reports List -->
    <div class="space-y-4">
        @forelse($incidents as $inc)
            @php
                $urgencyBadge = match($inc->urgency) {
                    'critical' => ['color' => 'bg-rose-100 text-rose-800 border-rose-300 animate-pulse', 'label' => 'CRITICAL / SOS'],
                    'high' => ['color' => 'bg-orange-100 text-orange-800 border-orange-300', 'label' => 'HIGH'],
                    'medium' => ['color' => 'bg-amber-100 text-amber-800 border-amber-300', 'label' => 'MEDIUM'],
                    'low' => ['color' => 'bg-slate-100 text-slate-700 border-slate-300', 'label' => 'LOW'],
                };
                $statusBadge = match($inc->status) {
                    'submitted' => ['color' => 'bg-blue-100 text-blue-800 border-blue-200', 'label' => 'Baru Masuk'],
                    'in_progress' => ['color' => 'bg-amber-100 text-amber-800 border-amber-200', 'label' => 'Sedang Ditindak'],
                    'resolved' => ['color' => 'bg-emerald-100 text-emerald-800 border-emerald-200', 'label' => 'Selesai'],
                    'dismissed' => ['color' => 'bg-slate-100 text-slate-600 border-slate-200', 'label' => 'Ditutup'],
                };
            @endphp
            <div class="bg-white rounded-2xl border {{ $inc->urgency === 'critical' ? 'border-rose-400 ring-2 ring-rose-200/50' : 'border-slate-200/80' }} p-5 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                    <div class="flex items-start space-x-3 min-w-0">
                        <div class="w-10 h-10 rounded-2xl {{ $inc->urgency === 'critical' ? 'bg-rose-600 text-white' : 'bg-slate-100 text-slate-700' }} font-black text-sm flex items-center justify-center shrink-0">
                            {{ substr($inc->studentPkl->student->user->name ?? 'S', 0, 1) }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="text-sm font-extrabold text-slate-900 truncate">{{ $inc->studentPkl->student->user->name }}</h4>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $urgencyBadge['color'] }}">
                                    {{ $urgencyBadge['label'] }}
                                </span>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $statusBadge['color'] }}">
                                    {{ $statusBadge['label'] }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 font-medium mt-0.5">
                                Mitra: <strong>{{ $inc->studentPkl->company->name }}</strong> &bull; Kategori: <span class="capitalize">{{ str_replace('_', ' ', $inc->category) }}</span>
                            </p>
                        </div>
                    </div>
                    <span class="text-[11px] text-slate-400 font-mono shrink-0">
                        {{ $inc->created_at->format('d M Y, H:i') }} ({{ $inc->created_at->diffForHumans() }})
                    </span>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60 space-y-2 text-xs">
                    <h5 class="font-bold text-slate-900 text-xs sm:text-sm">{{ $inc->subject }}</h5>
                    <p class="text-slate-700 leading-relaxed whitespace-pre-line">{{ $inc->description }}</p>
                    @if($inc->attachment_path)
                        <div class="pt-2">
                            <a href="{{ asset('storage/' . $inc->attachment_path) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-indigo-600 font-bold hover:bg-indigo-50 transition-colors shadow-2xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                <span>Lihat Foto Bukti Terlampir</span>
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Follow-up Log Timeline -->
                @if($inc->followups->isNotEmpty())
                    <div class="space-y-2 pt-2 border-t border-slate-100">
                        <span class="text-[11px] font-extrabold text-slate-600 uppercase tracking-wider block">Catatan Tindak Lanjut Guru / Admin:</span>
                        <div class="space-y-2">
                            @foreach($inc->followups as $f)
                                <div class="p-3 rounded-xl bg-indigo-50/50 border border-indigo-100 text-xs space-y-1">
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="font-bold text-indigo-950">{{ $f->user->name }} ({{ strtoupper(str_replace('-', ' ', $f->user->role)) }})</span>
                                        <span class="text-slate-400 font-mono">{{ $f->created_at->format('d M Y, H:i') }}</span>
                                    </div>
                                    <p class="text-slate-800 leading-relaxed">{{ $f->action_note }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Action Button to Open Followup Form -->
                <div class="pt-2 flex items-center justify-end">
                    <button type="button" onclick="document.getElementById('modal-followup-{{ $inc->id }}').classList.remove('hidden')" 
                            class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-sm transition-all flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span>+ Catat Tindak Lanjut / Update Status</span>
                    </button>
                </div>
            </div>

            <!-- Modal Followup -->
            <div id="modal-followup-{{ $inc->id }}" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs hidden p-4">
                <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-100 animate-in fade-in zoom-in-95 duration-150">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                        <h4 class="text-sm font-bold text-slate-800">Tindak Lanjut Aduan: {{ $inc->studentPkl->student->user->name }}</h4>
                        <button type="button" onclick="document.getElementById('modal-followup-{{ $inc->id }}').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form action="{{ route('guru.incidents.followup', $inc) }}" method="POST" class="p-6 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tindakan / Konseling / Kunjungan DU/DI <span class="text-rose-500">*</span></label>
                            <textarea name="action_note" rows="3" required placeholder="Tuliskan tindakan yang telah diambil..." class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Perbarui Status Laporan</label>
                            <select name="status" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 font-semibold bg-white text-slate-800">
                                <option value="in_progress" {{ $inc->status === 'in_progress' ? 'selected' : '' }}>Sedang Ditindaklanjuti (In Progress)</option>
                                <option value="resolved" {{ $inc->status === 'resolved' ? 'selected' : '' }}>Masalah Terselesaikan (Resolved)</option>
                                <option value="dismissed" {{ $inc->status === 'dismissed' ? 'selected' : '' }}>Tutup Laporan (Dismissed)</option>
                            </select>
                        </div>
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-3">
                            <button type="button" onclick="document.getElementById('modal-followup-{{ $inc->id }}').classList.add('hidden')" class="px-3.5 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold">
                                Batal
                            </button>
                            <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm">
                                Simpan Tindak Lanjut
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-slate-200/80 p-12 text-center shadow-sm">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h4 class="text-sm font-extrabold text-slate-900 tracking-tight">Kondisi Lapangan Terkendali</h4>
                <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                    Tidak ada laporan kendala atau situasi darurat dari siswa bimbingan Anda saat ini.
                </p>
            </div>
        @endforelse
    </div>

    @if($incidents->hasPages())
        <div class="p-4 bg-white rounded-2xl border border-slate-200/80">
            {{ $incidents->links() }}
        </div>
    @endif

</div>
@endsection
