@extends('layouts.app')

@section('page_title', 'Manajemen Tenant & Lisensi Sekolah')
@section('page_subtitle', 'Kelola hak akses tenant multi-sekolah, paket sewa, kapasitas kuota, dan masa aktif lisensi')

@section('content')
<div class="w-full space-y-6 sm:space-y-8 animate-in fade-in duration-300">

    <!-- Enterprise Hero Header -->
    <x-page-header 
        title="Daftar Tenant Sekolah Mitra" 
        subtitle="Setiap sekolah memiliki database tenant terisolasi secara aman dengan hak akses dan kuota sewa terkontrol."
        badge="SUPER ADMIN SAAS"
    >
        <x-button type="button" variant="primary" size="md" onclick="document.getElementById('modal-add-school').classList.remove('hidden')" class="shadow-lg shadow-indigo-600/25">
            <svg class="w-4 h-4 mr-1.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Daftarkan Sekolah Baru
        </x-button>
    </x-page-header>

    <!-- Top KPI Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <x-stat-card 
            label="Total Satuan Pendidikan" 
            :value="$schools->total()" 
            description="Tenant terdaftar di platform"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            label="Tenant Berstatus Aktif" 
            :value="$schools->where('is_active', true)->count()" 
            description="Operasional berjalan normal"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            label="Kapasitas Siswa PKL" 
            :value="$schools->sum('students_count')" 
            description="Total peserta aktif platform"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            label="Mitra Industri DU/DI" 
            :value="$schools->sum('companies_count')" 
            description="Jalinan kemitraan industri"
            color="teal"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Search & Filter Bar -->
    <x-card>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="relative flex-1">
                <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" id="school-search" placeholder="Cari nama sekolah, NPSN, email atau kota..." 
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs sm:text-sm font-medium text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-colors">
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-500 whitespace-nowrap">Status:</span>
                <select id="status-filter" class="text-xs font-bold px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    <option value="all">Semua Status</option>
                    <option value="active">Hanya Aktif</option>
                    <option value="inactive">Hanya Non-Aktif</option>
                </select>
            </div>
        </div>
    </x-card>

    <!-- Table Card -->
    <x-card noPadding>
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs min-w-[980px]" id="schools-table">
                <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6 min-w-[220px]">Satuan Pendidikan & NPSN</th>
                        <th class="py-3.5 px-4 min-w-[150px]">Paket Sewa</th>
                        <th class="py-3.5 px-4 min-w-[160px]">Kuota Siswa</th>
                        <th class="py-3.5 px-4 min-w-[150px]">Masa Aktif Lisensi</th>
                        <th class="py-3.5 px-4 text-center min-w-[110px]">Status</th>
                        <th class="py-3.5 px-6 text-center table-sticky-right min-w-[190px]">Aksi Manajemen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($schools as $sch)
                        @php
                            $sub = $sch->activeSubscription();
                            $studentsCount = $sch->students_count ?? 0;
                            $quota = $sub?->student_quota ?? 100;
                            $quotaPct = min(100, round(($studentsCount / max(1, $quota)) * 100));
                            $computedStatus = $sub ? $sub->computed_status : ($sch->is_active ? 'active' : 'suspended');
                            $daysRemaining = $sub ? $sub->daysRemaining() : null;
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors school-row group" data-name="{{ strtolower($sch->name) }}" data-npsn="{{ $sch->npsn }}" data-status="{{ $sch->is_active ? 'active' : 'inactive' }}">
                            <!-- Sekolah & Alamat -->
                            <td class="py-4 px-6">
                                <div class="flex items-start space-x-3.5">
                                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-white font-extrabold text-sm flex items-center justify-center shrink-0 shadow-sm shadow-indigo-500/20">
                                        {{ substr($sch->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0 max-w-sm">
                                        <span class="font-bold text-slate-900 block truncate text-xs sm:text-sm">{{ $sch->name }}</span>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 text-slate-600 border border-slate-200/70">
                                                NPSN: {{ $sch->npsn }}
                                            </span>
                                            <span class="text-[10px] text-slate-400 font-mono">{{ $sch->phone }}</span>
                                        </div>
                                        <span class="text-[11px] text-slate-500 block truncate leading-snug font-normal mt-1">{{ $sch->address }}</span>
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

                            <!-- Kuota Siswa PKL -->
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

                            <!-- Masa Aktif Lisensi -->
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

                            <!-- Status Sewa -->
                            <td class="py-4 px-4 text-center">
                                @if(!$sch->is_active)
                                    <x-badge variant="danger" size="xs">Nonaktif</x-badge>
                                @elseif($computedStatus === 'trial')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">Trial</span>
                                @elseif($computedStatus === 'expiring_soon')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 animate-pulse">Habis Dekat</span>
                                @elseif($computedStatus === 'grace_period')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-orange-50 text-orange-700 border border-orange-200">Tenggang</span>
                                @elseif($computedStatus === 'expired')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">Expired</span>
                                @else
                                    <x-badge variant="success" size="xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse mr-1"></span>
                                        Aktif
                                    </x-badge>
                                @endif
                            </td>

                            <!-- Aksi Manajemen -->
                            <td class="py-4 px-6 text-center whitespace-nowrap table-sticky-right">
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

                                    <!-- Toggle Active/Inactive -->
                                    <form action="{{ route('superadmin.schools.toggle', $sch->id) }}" method="POST" onsubmit="return confirm('Ubah status aktifasi sekolah ini?')">
                                        @csrf
                                        <button type="submit" class="px-2 py-1.5 rounded-lg text-[11px] font-bold transition-colors {{ $sch->is_active ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200' }}">
                                            {{ $sch->is_active ? 'Suspend' : 'Aktifkan' }}
                                        </button>
                                    </form>
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
                                    actionClick="document.getElementById('modal-add-school').classList.remove('hidden')"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($schools->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $schools->links() }}
            </div>
        @endif
    </x-card>

</div>

<!-- Modal Add School -->
<div id="modal-add-school" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 sm:p-7 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Registrasi Tenant Sekolah Baru</h3>
                <p class="text-xs text-slate-500 font-medium">Buat tenant terisolasi baru, paket sewa, dan akun administrator</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-add-school').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('superadmin.schools.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">NPSN (8 Digit)</label>
                    <input type="text" name="npsn" maxlength="8" required placeholder="Contoh: 20108899" 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-mono text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Resmi Sekolah</label>
                    <input type="text" name="name" required placeholder="SMK Negeri 1 Surabaya" 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Lengkap Sekolah</label>
                <textarea name="address" rows="2" required placeholder="Jl. Raya Pendidikan No. 123..." 
                          class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900 leading-relaxed"></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Telepon Resmi Sekolah</label>
                    <input type="text" name="phone" required placeholder="031-1234567" 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Email Resmi Sekolah</label>
                    <input type="email" name="email" required placeholder="info@smkn1.sch.id" 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                </div>
            </div>

            <!-- Pilih Paket Sewa -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                <span class="text-xs font-bold text-slate-900 block">Pilihan Paket Sewa & Lisensi</span>
                <div class="grid grid-cols-1 gap-2">
                    @foreach($plans as $plan)
                        <label class="flex items-center justify-between p-2.5 rounded-xl bg-white border border-slate-200 hover:border-indigo-500 cursor-pointer transition-colors">
                            <div class="flex items-center gap-2.5">
                                <input type="radio" name="plan_id" value="{{ $plan->id }}" {{ $loop->first ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <span class="text-xs font-bold text-slate-900 block">{{ $plan->name }}</span>
                                    <span class="text-[10px] text-slate-500">Maks {{ $plan->max_students }} Siswa &bull; {{ round($plan->max_storage_mb/1024, 1) }} GB Storage</span>
                                </div>
                            </div>
                            <span class="text-xs font-mono font-bold text-indigo-700">
                                {{ $plan->price > 0 ? $plan->formatted_price : 'GRATIS' }}
                            </span>
                        </label>
                    @endforeach
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Custom Kuota Siswa (Opsional)</label>
                    <input type="number" name="custom_quota" placeholder="Biarkan kosong untuk kuota default paket" 
                           class="w-full text-xs p-2.5 rounded-xl bg-white border border-slate-200 focus:ring-2 focus:ring-indigo-500/20 font-medium text-slate-900">
                </div>
            </div>

            <!-- Akun Administrator -->
            <div class="p-4 rounded-2xl bg-indigo-50/70 border border-indigo-100 space-y-3">
                <span class="text-xs font-bold text-indigo-950 block">Akun Administrator Sekolah Pertama</span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-indigo-900 mb-1">Nama Petugas Admin</label>
                        <input type="text" name="admin_name" required placeholder="Nama Admin" 
                               class="w-full text-xs p-2.5 rounded-xl bg-white border border-indigo-200 focus:ring-2 focus:ring-indigo-500/20 font-medium text-slate-900">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-indigo-900 mb-1">Email Login Admin</label>
                        <input type="email" name="admin_email" required placeholder="admin@smkn1.sch.id" 
                               class="w-full text-xs p-2.5 rounded-xl bg-white border border-indigo-200 focus:ring-2 focus:ring-indigo-500/20 font-medium text-slate-900">
                    </div>
                </div>
                <p class="text-[10px] text-indigo-700 font-medium">Kata sandi default login: <code class="bg-white/80 px-1.5 py-0.5 rounded font-mono font-bold text-indigo-900">password</code></p>
            </div>

            <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-add-school').classList.add('hidden')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md" class="shadow-md shadow-indigo-600/20">
                    Daftarkan & Aktifkan Sewa
                </x-button>
            </div>
        </form>
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
                <input type="number" name="price" required value="6000000" min="0" step="50000" 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-mono font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
                <span class="text-[10px] text-slate-500 mt-1 block">Akan otomatis menerbitkan kwitansi / invoice lunas.</span>
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
                <x-button type="submit" variant="primary" size="md" class="bg-emerald-600 hover:bg-emerald-700 text-white">
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
        const searchInput = document.getElementById('school-search');
        const statusFilter = document.getElementById('status-filter');
        const rows = document.querySelectorAll('.school-row');

        function filterSchools() {
            const query = (searchInput?.value || '').toLowerCase().trim();
            const status = statusFilter?.value || 'all';

            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const npsn = row.getAttribute('data-npsn') || '';
                const rowStatus = row.getAttribute('data-status') || '';

                const matchesQuery = !query || name.includes(query) || npsn.includes(query);
                const matchesStatus = status === 'all' || rowStatus === status;

                row.style.display = (matchesQuery && matchesStatus) ? '' : 'none';
            });
        }

        searchInput?.addEventListener('input', filterSchools);
        statusFilter?.addEventListener('change', filterSchools);
    });
</script>
@endsection
