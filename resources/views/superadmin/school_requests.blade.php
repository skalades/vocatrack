@extends('layouts.app')

@section('page_title', 'Pipeline Permohonan Demo & Onboarding Sekolah')
@section('page_subtitle', 'Tinjau permohonan pendaftaran akun demo dari calon sekolah mitra dan aktifkan tenant secara instan')

@section('content')
<div class="w-full space-y-6 sm:space-y-8 animate-in fade-in duration-300">

    <!-- Enterprise Hero Header -->
    <x-page-header 
        title="Pipeline Calon Sekolah Mitra & Permohonan Demo" 
        subtitle="Verifikasi pendaftaran formulir mandiri dari sekolah baru, aktifkan akun demo 14 hari dengan 1-klik, dan perluas jangkauan platform."
        badge="ONBOARDING PIPELINE"
    />

    <!-- Top KPI Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
        <x-stat-card 
            title="Total Permohonan Masuk" 
            :value="$totalRequests" 
            subtitle="Semua formulir pendaftaran"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-indigo-600 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Menunggu Tinjauan" 
            :value="$pendingRequests" 
            subtitle="Perlu tindak lanjut Super Admin"
            :color="$pendingRequests > 0 ? 'amber' : 'emerald'"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 stroke-[1.8] {{ $pendingRequests > 0 ? 'text-amber-600' : 'text-emerald-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Berhasil Diaktivasi" 
            :value="$approvedRequests" 
            subtitle="Telah menjadi tenant resmi"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-emerald-600 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Filter & Search Bar -->
    <x-card>
        <form method="GET" action="{{ route('superadmin.school-requests') }}" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="relative flex-1">
                <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama sekolah, NPSN, nama kontak, atau email..." 
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs sm:text-sm font-medium text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-colors">
            </div>

            <div class="flex items-center gap-2">
                <select name="status" onchange="this.form.submit()" class="text-xs font-bold px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
                    <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>Semua Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Menunggu Review (Pending)</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui (Approved)</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak (Rejected)</option>
                </select>

                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('superadmin.school-requests') }}" class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition-colors">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Table of School Requests -->
    <x-card noPadding>
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs min-w-[950px]">
                <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3 px-6 min-w-[240px]">Satuan Pendidikan & NPSN</th>
                        <th class="py-3 px-4 min-w-[200px]">Kontak Penanggung Jawab</th>
                        <th class="py-3 px-4 min-w-[200px]">Alamat & Catatan</th>
                        <th class="py-3 px-4 min-w-[140px]">Waktu Pengajuan</th>
                        <th class="py-3 px-4 text-center min-w-[120px]">Status</th>
                        <th class="py-3 px-6 text-center min-w-[180px]">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($requests as $req)
                        @php
                            $statusColor = match($req->status) {
                                'approved' => 'emerald',
                                'rejected' => 'rose',
                                default => 'amber',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-6">
                                <span class="font-bold text-slate-900 block text-sm">{{ $req->school_name }}</span>
                                <span class="text-[11px] text-slate-400 font-mono">NPSN: {{ $req->npsn }}</span>
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="font-bold text-slate-900 block">{{ $req->contact_name }}</span>
                                <span class="text-[11px] text-slate-500 block">{{ $req->email }}</span>
                                <span class="text-[11px] text-slate-400 block font-mono">{{ $req->phone }}</span>
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="text-xs text-slate-700 block truncate max-w-xs">{{ $req->address ?: '-' }}</span>
                                @if($req->notes)
                                    <span class="text-[11px] text-slate-400 italic block mt-0.5 truncate max-w-xs">"{{ $req->notes }}"</span>
                                @endif
                                @if($req->rejection_reason)
                                    <span class="text-[11px] text-rose-600 block mt-1">Alasan Tolak: {{ $req->rejection_reason }}</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                {{ $req->created_at->format('d/m/Y H:i') }}
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <x-badge :color="$statusColor" size="sm">
                                    {{ strtoupper($req->status) }}
                                </x-badge>
                            </td>

                            <td class="py-3.5 px-6 text-center">
                                @if($req->status === 'pending')
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button" 
                                                onclick="openApproveModal({{ json_encode($req) }})"
                                                class="px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition-colors shadow-xs">
                                            Setujui
                                        </button>

                                        <button type="button" 
                                                onclick="openRejectModal({{ json_encode($req) }})"
                                                class="px-2.5 py-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 font-bold text-xs transition-colors">
                                            Tolak
                                        </button>
                                    </div>
                                @elseif($req->status === 'approved' && $req->school_id)
                                    <a href="{{ route('superadmin.schools', ['search' => $req->npsn]) }}" class="px-2.5 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-bold text-xs transition-colors inline-block">
                                        Lihat Tenant
                                    </a>
                                @else
                                    <span class="text-slate-400 text-xs italic">Selesai</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center">
                                <x-empty-state 
                                    title="Belum Ada Permohonan Pendaftaran" 
                                    description="Permohonan demo yang dikirimkan calon sekolah mitra akan muncul di halaman antrean ini."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $requests->links() }}
            </div>
        @endif
    </x-card>

</div>

<!-- Modal Persetujuan & Pembuatan Tenant -->
<div id="modal-approve-request" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
            <div>
                <h3 class="text-sm font-bold">Setujui Permohonan & Buat Tenant</h3>
                <p class="text-[11px] text-slate-400" id="approve-school-name">-</p>
            </div>
            <button type="button" onclick="closeApproveModal()" class="text-slate-400 hover:text-white p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="form-approve-request" method="POST" class="p-6 space-y-4">
            @csrf

            <p class="text-xs text-slate-600">
                Menyetujui permohonan ini akan secara otomatis membuat tenant sekolah baru, membuat akun Admin Sekolah dengan password default: <strong>password</strong>, dan mengaktifkan paket sewa pilihan Anda.
            </p>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Paket Lisensi Sewa</label>
                <select name="plan_id" required class="w-full text-xs font-bold px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ $plan->code === 'trial-14d' ? 'selected' : '' }}>
                            {{ $plan->name }} (Rp {{ number_format($plan->price, 0, ',', '.') }}) — Kuota: {{ $plan->max_students }} Siswa
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Kustomisasi Kuota Siswa (Opsional)</label>
                <input type="number" name="custom_quota" placeholder="Biarkan kosong untuk kuota bawaan paket" min="10"
                       class="w-full text-xs px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 focus:bg-white">
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeApproveModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition-colors shadow-xs">
                    Konfirmasi & Buat Tenant
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tolak Permohonan -->
<div id="modal-reject-request" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
            <div>
                <h3 class="text-sm font-bold">Tolak Permohonan Pendaftaran</h3>
                <p class="text-[11px] text-slate-400" id="reject-school-name">-</p>
            </div>
            <button type="button" onclick="closeRejectModal()" class="text-slate-400 hover:text-white p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="form-reject-request" method="POST" class="p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Alasan Penolakan</label>
                <textarea name="rejection_reason" required rows="3" placeholder="Tuliskan alasan penolakan, misalnya: NPSN tidak valid, duplikasi, dll..."
                          class="w-full text-xs px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:ring-2 focus:ring-rose-500/20 text-slate-800"></textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition-colors shadow-xs">
                    Tolak Permohonan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openApproveModal(req) {
        document.getElementById('approve-school-name').textContent = req.school_name + ' (NPSN: ' + req.npsn + ')';
        document.getElementById('form-approve-request').action = "{{ url('/super-admin/school-requests') }}/" + req.id + "/approve";
        document.getElementById('modal-approve-request').classList.remove('hidden');
    }

    function closeApproveModal() {
        document.getElementById('modal-approve-request').classList.add('hidden');
    }

    function openRejectModal(req) {
        document.getElementById('reject-school-name').textContent = req.school_name + ' (NPSN: ' + req.npsn + ')';
        document.getElementById('form-reject-request').action = "{{ url('/super-admin/school-requests') }}/" + req.id + "/reject";
        document.getElementById('modal-reject-request').classList.remove('hidden');
    }

    function closeRejectModal() {
        document.getElementById('modal-reject-request').classList.add('hidden');
    }
</script>
@endsection
