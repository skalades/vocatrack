@extends('layouts.app')

@section('page_title', 'Pusat Audit Trail & Log Keamanan')
@section('page_subtitle', 'Pemantauan rekam jejak aktivitas, mutasi data krusial, dan riwayat keamanan lintas platform')

@section('content')
<div class="w-full space-y-6 sm:space-y-8 animate-in fade-in duration-300">

    <!-- Enterprise Hero Header -->
    <x-page-header 
        title="Pusat Audit Trail & Log Keamanan" 
        subtitle="Setiap tindakan login, impersonasi, perpanjangan sewa, dan mutasi data sensitif terekam secara permanen untuk integritas forensik."
        badge="PLATFORM SECURITY AUDIT"
    />

    <!-- Top KPI Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <x-stat-card 
            title="Aktivitas Hari Ini" 
            :value="$totalLogsToday" 
            subtitle="Rekaman transaksi 24 jam terakhir"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-indigo-600 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Sesi Impersonasi" 
            :value="$impersonationEventsCount" 
            subtitle="Riwayat akses lintas tenant"
            color="amber"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-amber-600 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Insiden Keamanan" 
            :value="$securityEventsCount" 
            subtitle="Reset sandi & event keamanan"
            color="rose"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-rose-600 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Total Rekaman Log" 
            :value="$totalLogsAll" 
            subtitle="Total arsip forensik basis data"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-emerald-600 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Filter & Search Card -->
    <x-card>
        <form method="GET" action="{{ route('superadmin.audit-logs') }}" class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <div class="relative flex-1">
                <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari deskripsi aktivitas, alamat IP, email pelaku, atau nama sekolah..." 
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs sm:text-sm font-medium text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-colors">
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Filter Modul -->
                <select name="module" onchange="this.form.submit()" class="text-xs font-bold px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
                    <option value="all" {{ request('module') === 'all' || !request('module') ? 'selected' : '' }}>Semua Modul</option>
                    <option value="schools" {{ request('module') === 'schools' ? 'selected' : '' }}>Tenant & Sekolah</option>
                    <option value="billing" {{ request('module') === 'billing' ? 'selected' : '' }}>Billing & Invoices</option>
                    <option value="security" {{ request('module') === 'security' ? 'selected' : '' }}>Keamanan & Impersonasi</option>
                    <option value="settings" {{ request('module') === 'settings' ? 'selected' : '' }}>Pengaturan Platform</option>
                    <option value="system" {{ request('module') === 'system' ? 'selected' : '' }}>Sistem & Cadangan</option>
                </select>

                <!-- Filter Tanggal -->
                <input type="date" name="date" value="{{ request('date') }}" onchange="this.form.submit()" 
                       class="text-xs font-bold px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">

                @if(request()->hasAny(['search', 'module', 'action', 'school_id', 'date']))
                    <a href="{{ route('superadmin.audit-logs') }}" class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition-colors">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Table of Audit Logs -->
    <x-card noPadding>
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs min-w-[950px]">
                <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6 min-w-[170px]">Waktu & Alamat IP</th>
                        <th class="py-3.5 px-4 min-w-[180px]">Pengguna Pelaku</th>
                        <th class="py-3.5 px-4 min-w-[130px]">Aksi / Modul</th>
                        <th class="py-3.5 px-4 min-w-[280px]">Deskripsi Aktivitas</th>
                        <th class="py-3.5 px-4 min-w-[160px]">Tenant Sekolah</th>
                        <th class="py-3.5 px-6 text-center min-w-[100px]">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($logs as $log)
                        @php
                            $actionColor = match($log->action) {
                                'school_create', 'invoice_verify', 'school_request_approved' => 'emerald',
                                'impersonation_start', 'subscription_extend' => 'indigo',
                                'password_reset', 'school_toggle' => 'amber',
                                'school_delete', 'invoice_delete', 'invoice_cancel' => 'rose',
                                default => 'slate',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-6">
                                <span class="font-bold text-slate-900 block font-mono">{{ $log->created_at->format('d/m/Y H:i:s') }}</span>
                                <span class="text-[11px] text-slate-400 font-mono flex items-center gap-1 mt-0.5">
                                    <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                    {{ $log->ip_address ?: '127.0.0.1' }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4">
                                @if($log->user)
                                    <span class="font-bold text-slate-900 block">{{ $log->user->name }}</span>
                                    <span class="text-[11px] text-slate-500 block truncate max-w-[170px]">{{ $log->user->email }}</span>
                                    <span class="inline-block px-1.5 py-0.5 mt-0.5 rounded text-[9px] font-bold font-mono bg-slate-100 text-slate-600">
                                        {{ $log->user->role }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic">Sistem Platform</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4">
                                <x-badge :color="$actionColor" size="sm">
                                    {{ str_replace('_', ' ', strtoupper($log->action)) }}
                                </x-badge>
                                <span class="text-[10px] text-slate-400 font-bold block uppercase mt-1 tracking-wider">
                                    {{ $log->module }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-xs font-medium text-slate-800 leading-relaxed">
                                {{ $log->description }}
                            </td>

                            <td class="py-3.5 px-4">
                                @if($log->school)
                                    <span class="font-bold text-indigo-700 block truncate max-w-[180px]">{{ $log->school->name }}</span>
                                    <span class="text-[10px] text-slate-400 font-mono">NPSN: {{ $log->school->npsn }}</span>
                                @else
                                    <span class="text-slate-400 italic">Platform Global</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-6 text-center">
                                @if($log->payload_before || $log->payload_after)
                                    <button type="button" 
                                            onclick="viewLogDetail({{ json_encode($log) }})"
                                            class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-bold text-[11px] transition-colors">
                                        Payload
                                    </button>
                                @else
                                    <span class="text-slate-300 text-[11px]">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center">
                                <x-empty-state 
                                    title="Belum Ada Rekaman Log Aktivitas" 
                                    description="Aktivitas yang dilakukan di platform akan terekam secara otomatis di tabel audit trail ini."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $logs->links() }}
            </div>
        @endif
    </x-card>

</div>

<!-- Modal Detail Payload JSON -->
<div id="modal-log-detail" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
            <div>
                <h3 class="text-sm font-bold" id="modal-log-title">Rincian Perubahan Data</h3>
                <p class="text-[11px] text-slate-400" id="modal-log-meta">Metadata Audit Trail</p>
            </div>
            <button type="button" onclick="closeLogModal()" class="text-slate-400 hover:text-white p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto custom-scrollbar">
            <div>
                <span class="text-xs font-bold text-slate-700 block mb-1">Deskripsi Lengkap:</span>
                <p id="modal-log-desc" class="text-xs text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-200 font-medium"></p>
            </div>

            <div id="modal-log-payload-before-box" class="hidden">
                <span class="text-xs font-bold text-rose-700 block mb-1">Data Sebelum Diubah (Payload Before):</span>
                <pre id="modal-log-before" class="text-[11px] font-mono bg-rose-50 text-rose-950 p-3 rounded-xl border border-rose-200 overflow-x-auto"></pre>
            </div>

            <div id="modal-log-payload-after-box" class="hidden">
                <span class="text-xs font-bold text-emerald-700 block mb-1">Data Sesudah Diubah (Payload After):</span>
                <pre id="modal-log-after" class="text-[11px] font-mono bg-emerald-50 text-emerald-950 p-3 rounded-xl border border-emerald-200 overflow-x-auto"></pre>
            </div>
        </div>

        <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex justify-end">
            <button type="button" onclick="closeLogModal()" class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs transition-colors">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
    function viewLogDetail(log) {
        document.getElementById('modal-log-title').textContent = 'Detail Log: ' + (log.action || '').toUpperCase();
        document.getElementById('modal-log-meta').textContent = (log.created_at || '') + ' • IP: ' + (log.ip_address || '-');
        document.getElementById('modal-log-desc').textContent = log.description || '-';

        const beforeBox = document.getElementById('modal-log-payload-before-box');
        const afterBox = document.getElementById('modal-log-payload-after-box');

        if (log.payload_before) {
            beforeBox.classList.remove('hidden');
            document.getElementById('modal-log-before').textContent = JSON.stringify(log.payload_before, null, 2);
        } else {
            beforeBox.classList.add('hidden');
        }

        if (log.payload_after) {
            afterBox.classList.remove('hidden');
            document.getElementById('modal-log-after').textContent = JSON.stringify(log.payload_after, null, 2);
        } else {
            afterBox.classList.add('hidden');
        }

        document.getElementById('modal-log-detail').classList.remove('hidden');
    }

    function closeLogModal() {
        document.getElementById('modal-log-detail').classList.add('hidden');
    }
</script>
@endsection
