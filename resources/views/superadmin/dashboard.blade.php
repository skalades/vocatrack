@extends('layouts.app')

@section('page_title', 'Dashboard SaaS Super Admin')
@section('page_subtitle', 'Pusat kendali multi-tenancy cloud, pemantauan masa sewa, dan pendapatan lisensi berulang')

@section('content')
<div class="w-full space-y-6 animate-in fade-in duration-300">

    <!-- Enterprise Standard Page Header -->
    <x-page-header 
        title="Pusat Kendali Sewa Platform VokaTrack" 
        subtitle="Monitoring performa sewa SaaS, pendapatan berulang bulanan (MRR), masa aktif lisensi, serta alokasi kapasitas sekolah mitra."
        badge="SUPER ADMIN SAAS"
    >
        <a href="{{ route('superadmin.invoices') }}" class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-bold text-slate-200 bg-white/10 hover:bg-white/20 border border-white/20 transition-colors shadow-2xs">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8h4"/></svg>
            Kelola Tagihan
        </a>
        <x-button variant="primary" size="md" onclick="window.location.href='{{ route('superadmin.schools') }}'" class="shadow-lg shadow-indigo-600/30">
            <svg class="w-4 h-4 mr-1.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span>+ Daftarkan Tenant Baru</span>
        </x-button>
    </x-page-header>

    <!-- SaaS Metric KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- 1. Total Tenant Sewa -->
        <x-stat-card 
            title="Total Tenant Sekolah" 
            :value="$metrics['total_schools'] . ' Sekolah'" 
            :subtitle="$metrics['active_tenants'] . ' Aktif • ' . $metrics['trial_tenants'] . ' Masa Trial'"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <!-- 2. Pendapatan Berulang (MRR / ARR) -->
        <x-stat-card 
            title="Estimasi MRR (Bulanan)" 
            :value="'Rp ' . number_format($metrics['mrr_estimate'], 0, ',', '.')" 
            :subtitle="'ARR: Rp ' . number_format($metrics['arr_estimate'], 0, ',', '.') . ' / Thn'"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <!-- 3. Perlu Perpanjangan -->
        <x-stat-card 
            title="Habis Sewa < 30 Hari" 
            :value="$metrics['expiring_soon'] . ' Sekolah'" 
            subtitle="Perlu tindak lanjut perpanjangan sewa"
            :color="$metrics['expiring_soon'] > 0 ? 'amber' : 'blue'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <!-- 4. Utilisasi Kuota Siswa Terdaftar -->
        <x-stat-card 
            title="Utilisasi Siswa PKL" 
            :value="$metrics['total_students'] . ' / ' . $metrics['total_quota_licensed']" 
            :subtitle="round(($metrics['total_students'] / max(1, $metrics['total_quota_licensed'])) * 100) . '% Kapasitas lisensi terpakai'"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

    </div>

    <!-- Urgent Attention Banner for Expiring Subscriptions -->
    @if($expiringSubscriptions->isNotEmpty())
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-amber-900 shadow-xs">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <h3 class="text-xs sm:text-sm font-bold">Pemberitahuan Masa Sewa Menjelang Berakhir</h3>
                    <p class="text-xs text-amber-700">
                        Terdapat <strong>{{ $expiringSubscriptions->count() }} sekolah</strong> mitra yang masa aktif lisensinya akan habis dalam 30 hari ke depan.
                    </p>
                </div>
            </div>
            <a href="{{ route('superadmin.schools', ['status' => 'expiring_soon']) }}" class="px-3.5 py-1.5 rounded-xl bg-amber-600 text-white font-bold text-xs hover:bg-amber-700 transition-colors shrink-0 text-center shadow-xs">
                Lihat & Perpanjang Sewa
            </a>
        </div>
    @endif

    <!-- Main Table: Monitoring Tenant & Status Sewa -->
    <x-card noPadding>
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">Status Sewa & Kuota Tenant Sekolah</h3>
                <p class="text-xs text-slate-500 font-medium">Monitoring masa berlaku lisensi, kapasitas kuota siswa, serta akses remote tenant</p>
            </div>
            <a href="{{ route('superadmin.schools') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 flex items-center">
                Kelola Semua Sekolah &rarr;
            </a>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs min-w-[900px]">
                <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6 min-w-[220px]">Sekolah Mitra</th>
                        <th class="py-3.5 px-4 min-w-[150px]">Paket Sewa</th>
                        <th class="py-3.5 px-4 min-w-[170px]">Kuota Siswa</th>
                        <th class="py-3.5 px-4 min-w-[160px]">Masa Aktif Lisensi</th>
                        <th class="py-3.5 px-4 text-center min-w-[120px]">Status Sewa</th>
                        <th class="py-3.5 px-6 text-center table-sticky-right min-w-[190px]">Aksi Manajemen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($recentSchools as $sch)
                        @php
                            $sub = $sch->activeSubscription();
                            $studentsCount = $sch->students_count ?? 0;
                            $quota = $sub?->student_quota ?? 100;
                            $quotaPct = min(100, round(($studentsCount / max(1, $quota)) * 100));
                            $computedStatus = $sub ? $sub->computed_status : ($sch->is_active ? 'active' : 'suspended');
                            $daysRemaining = $sub ? $sub->daysRemaining() : null;
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <!-- Sekolah & NPSN -->
                            <td class="py-4 px-6">
                                <div class="flex items-start space-x-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-white font-extrabold text-xs flex items-center justify-center shrink-0 shadow-xs">
                                        {{ substr($sch->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0 max-w-xs">
                                        <span class="font-bold text-slate-900 block truncate text-xs">{{ $sch->name }}</span>
                                        <span class="text-[10px] text-slate-500 font-mono block">NPSN: {{ $sch->npsn }}</span>
                                        <span class="text-[11px] text-slate-400 truncate block mt-0.5">{{ $sch->address }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Paket Sewa -->
                            <td class="py-4 px-4">
                                @if($sub)
                                    <div>
                                        <span class="font-bold text-slate-900 block text-xs">{{ $sub->plan_name }}</span>
                                        <span class="text-[11px] font-mono font-semibold text-emerald-600 block">
                                            {{ $sub->formatted_price }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 italic">Belum Ada Paket</span>
                                @endif
                            </td>

                            <!-- Kuota Siswa & Progress -->
                            <td class="py-4 px-4">
                                <div class="space-y-1 max-w-[150px]">
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="font-bold text-slate-800">{{ $studentsCount }} / {{ $quota }}</span>
                                        <span class="font-mono text-[10px] {{ $quotaPct >= 90 ? 'text-rose-600 font-bold' : 'text-slate-500' }}">{{ $quotaPct }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                        <div class="h-full rounded-full transition-all {{ $quotaPct >= 90 ? 'bg-rose-500' : ($quotaPct >= 70 ? 'bg-amber-500' : 'bg-indigo-600') }}" style="width: {{ $quotaPct }}%"></div>
                                    </div>
                                    <span class="text-[10px] text-slate-400 block truncate">Siswa PKL terdaftar</span>
                                </div>
                            </td>

                            <!-- Masa Aktif & Sisa Hari -->
                            <td class="py-4 px-4">
                                @if($sub)
                                    <div class="space-y-0.5">
                                        <span class="text-xs font-semibold text-slate-900 block">{{ $sub->end_date->format('d M Y') }}</span>
                                        @if($sub->isExpired())
                                            <span class="text-[10px] font-bold text-rose-600 block">
                                                Lewat {{ abs($daysRemaining) }} hari lalu
                                            </span>
                                        @elseif($daysRemaining <= 14)
                                            <span class="text-[10px] font-bold text-amber-600 block">
                                                Sisa {{ $daysRemaining }} hari lagi
                                            </span>
                                        @else
                                            <span class="text-[10px] text-emerald-600 font-medium block">
                                                Sisa {{ $daysRemaining }} hari lagi
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 italic">-</span>
                                @endif
                            </td>

                            <!-- Status Sewa dengan x-badge Modular -->
                            <td class="py-4 px-4 text-center">
                                @if(!$sch->is_active)
                                    <x-badge variant="danger" size="xs">Nonaktif</x-badge>
                                @elseif($computedStatus === 'trial')
                                    <x-badge variant="indigo" size="xs">Trial 14 Hari</x-badge>
                                @elseif($computedStatus === 'expiring_soon')
                                    <x-badge variant="warning" size="xs" :pulse="true">Habis Dekat</x-badge>
                                @elseif($computedStatus === 'grace_period')
                                    <x-badge variant="warning" size="xs">Tenggang</x-badge>
                                @elseif($computedStatus === 'expired')
                                    <x-badge variant="danger" size="xs">Expired</x-badge>
                                @else
                                    <x-badge variant="success" size="xs" :pulse="true">Aktif</x-badge>
                                @endif
                            </td>

                            <!-- Aksi Manajemen -->
                            <td class="py-4 px-6 text-center table-sticky-right">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- 1-Click Impersonate Button -->
                                    <form action="{{ route('superadmin.schools.impersonate', $sch->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" title="Masuk Sebagai Admin Sekolah" class="p-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                        </button>
                                    </form>

                                    <!-- Extend Subscription Modal Trigger -->
                                    <button type="button" 
                                            onclick="openExtendModal('{{ $sch->id }}', '{{ addslashes($sch->name) }}', '{{ $quota }}')" 
                                            class="px-2.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-[11px] font-bold transition-colors">
                                        Perpanjang
                                    </button>

                                    <!-- Kelola Detail Sekolah Link -->
                                    <a href="{{ route('superadmin.schools', ['search' => $sch->npsn]) }}" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors" title="Lihat Detail & Kelola">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12">
                                <x-empty-state 
                                    title="Belum Ada Sekolah Terdaftar" 
                                    description="Daftarkan tenant sekolah baru untuk memulai proses onboarding institusi pendidikan."
                                    actionLabel="+ Daftarkan Sekolah Sekarang"
                                    actionUrl="{{ route('superadmin.schools') }}"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <!-- Lower Grid: Recent Invoices & Available Plans -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        
        <!-- 1. Recent Invoices -->
        <x-card noPadding>
            <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 tracking-tight">Tagihan & Faktur Sewa Terbaru</h3>
                    <p class="text-[11px] text-slate-500 font-medium">Riwayat penerbitan invoice sewa multi-tenant</p>
                </div>
                <a href="{{ route('superadmin.invoices') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-700">
                    Lihat Semua &rarr;
                </a>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($recentInvoices as $inv)
                    <div class="p-4 flex items-center justify-between gap-3 text-xs hover:bg-slate-50/50 transition-colors">
                        <div class="min-w-0">
                            <span class="font-bold text-slate-900 block truncate">{{ $inv->school->name ?? 'Sekolah' }}</span>
                            <div class="flex items-center gap-2 text-[10px] text-slate-500 mt-0.5 font-mono">
                                <span class="font-bold text-indigo-700">{{ $inv->invoice_number }}</span>
                                <span>&bull;</span>
                                <span>Jatuh Tempo: {{ $inv->due_date->format('d M Y') }}</span>
                            </div>
                        </div>
                        <div class="text-right shrink-0 flex items-center gap-3">
                            <div>
                                <span class="font-mono font-bold text-slate-900 block">{{ $inv->formatted_amount }}</span>
                                @if($inv->status === 'paid')
                                    <x-badge variant="success" size="xs">Lunas</x-badge>
                                @elseif($inv->status === 'cancelled')
                                    <x-badge variant="danger" size="xs">Batal</x-badge>
                                @else
                                    <x-badge variant="warning" size="xs">Menunggu</x-badge>
                                @endif
                            </div>
                            <a href="{{ route('superadmin.invoices.pdf', $inv->id) }}" title="Unduh PDF Faktur" class="p-1.5 rounded-lg bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-slate-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-slate-400 text-xs">Belum ada riwayat tagihan invoice.</div>
                @endforelse
            </div>
        </x-card>

        <!-- 2. Katalog Paket Langganan Aktif -->
        <x-card noPadding>
            <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 tracking-tight">Katalog Paket Lisensi SaaS</h3>
                    <p class="text-[11px] text-slate-500 font-medium">Struktur harga dan kuota yang ditawarkan ke sekolah</p>
                </div>
                <a href="{{ route('superadmin.plans') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-700">
                    Atur Semua Paket &rarr;
                </a>
            </div>

            <div class="p-4 space-y-3">
                @foreach($plans as $plan)
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center justify-between gap-3 hover:bg-white hover:shadow-xs transition-all">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-900 text-xs">{{ $plan->name }}</span>
                                @if($plan->is_popular)
                                    <span class="px-1.5 py-0.2 rounded-full text-[9px] font-extrabold bg-indigo-600 text-white uppercase tracking-wider">Terpopuler</span>
                                @endif
                            </div>
                            <span class="text-[10px] text-slate-500 block mt-0.5">
                                Kuota: {{ $plan->max_students }} Siswa &bull; Storage: {{ round($plan->max_storage_mb / 1024, 1) }} GB
                            </span>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="font-mono font-bold text-indigo-900 text-xs block">
                                {{ $plan->price > 0 ? $plan->formatted_price : 'GRATIS' }}
                            </span>
                            <span class="text-[10px] text-slate-400 block">{{ $plan->period_label }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>

    </div>

</div>

<!-- Modal Quick Extend Subscription -->
<div id="modal-extend-subscription" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-2xl border border-slate-100 animate-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Perpanjang Masa Sewa Sekolah</h3>
                <p class="text-xs text-slate-500 font-medium" id="extend-school-name">Nama Sekolah</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-extend-subscription').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="form-extend-subscription" method="POST" action="" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilihan Paket Lisensi (Upgrade / Perpanjang)</label>
                <select name="plan_id" id="extend-plan-select-dash" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">-- Tetap Gunakan Paket Saat Ini --</option>
                    @foreach($plans as $p)
                        <option value="{{ $p->id }}" data-price="{{ (int)$p->price }}" data-quota="{{ $p->max_students }}">
                            {{ $p->name }} ({{ $p->price > 0 ? $p->formatted_price : 'Gratis' }} • {{ $p->max_students }} Siswa)
                        </option>
                    @endforeach
                </select>
                <span class="text-[10px] text-slate-500 mt-1 block">Pilih paket jika ingin meng-upgrade tenant dari Demo ke Paket Berbayar.</span>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Durasi Perpanjangan</label>
                <select name="months" required class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
                    <option value="6">1 Semester (6 Bulan)</option>
                    <option value="12" selected>1 Tahun Ajaran (12 Bulan)</option>
                    <option value="24">2 Tahun Ajaran (24 Bulan)</option>
                    <option value="3">3 Bulan (Add-on Masa PKL)</option>
                    <option value="1">1 Bulan</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nominal Biaya Sewa (Rp)</label>
                <input type="number" name="price" id="extend-price-input-dash" required value="6000000" min="0" step="any" 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-mono font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
                <span class="text-[10px] text-slate-500 mt-1 block">Akan otomatis menerbitkan kwitansi / invoice lunas jika > 0.</span>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Penyesuaian Kuota Siswa (Opsional)</label>
                <input type="number" name="new_quota" id="extend-quota-input" min="10" placeholder="Biarkan kosong jika tidak berubah" 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Catatan Perpanjangan (Opsional)</label>
                <input type="text" name="notes" placeholder="Contoh: Perpanjangan TA 2027 via Dana BOS" 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-extend-subscription').classList.add('hidden')">
                    Batal
                </x-button>
                <x-button type="submit" variant="success" size="md">
                    Simpan Perpanjangan
                </x-button>
            </div>
        </form>
    </div>
</div>

<script>
    function openExtendModal(schoolId, schoolName, currentQuota) {
        const modal = document.getElementById('modal-extend-subscription');
        const form = document.getElementById('form-extend-subscription');
        const nameElem = document.getElementById('extend-school-name');
        const quotaInput = document.getElementById('extend-quota-input');

        form.action = `/super-admin/schools/${schoolId}/extend`;
        nameElem.textContent = schoolName;
        quotaInput.value = currentQuota;
        modal.classList.remove('hidden');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const planSelect = document.getElementById('extend-plan-select-dash');
        const priceInput = document.getElementById('extend-price-input-dash');
        const quotaInput = document.getElementById('extend-quota-input');

        planSelect?.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            if (selected.value) {
                if (selected.getAttribute('data-price') !== null) {
                    priceInput.value = selected.getAttribute('data-price');
                }
                if (selected.getAttribute('data-quota') !== null) {
                    quotaInput.value = selected.getAttribute('data-quota');
                }
            }
        });
    });
</script>
@endsection
