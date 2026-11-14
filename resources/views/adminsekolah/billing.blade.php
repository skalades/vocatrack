@extends('layouts.app')

@section('page_title', 'Langganan & Tagihan Sekolah')
@section('page_subtitle', 'Pantau paket lisensi aktif, alokasi kuota siswa, kapasitas penyimpanan, dan riwayat faktur tagihan sekolah.')

@section('content')
<div class="w-full space-y-6 sm:space-y-8 animate-in fade-in duration-300">

    <!-- Header Section -->
    <x-page-header 
        title="Langganan & Tagihan Lisensi Sekolah" 
        subtitle="Informasi status sewa platform SaaS VokaTrack, utilisasi kuota siswa dan ruang disk sekolah Anda."
        badge="PORTAL LISENSI"
    >
        <div class="flex items-center gap-2">
            <span class="text-xs text-slate-400 font-mono">NPSN: {{ $school->npsn }}</span>
        </div>
    </x-page-header>

    <!-- Top Status & Quota KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        
        <!-- 1. Paket Aktif -->
        <x-stat-card 
            title="Paket Lisensi Aktif" 
            :value="$activeSubscription?->plan_name ?? 'Belum Berlangganan'" 
            :subtitle="$activeSubscription ? $activeSubscription->formatted_price . ' ' . ($activeSubscription->plan?->period_label ?? '') : 'Hubungi Super Admin'"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-indigo-600 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <!-- 2. Masa Aktif Lisensi -->
        @php
            $daysLeft = $activeSubscription ? $activeSubscription->daysRemaining() : 0;
            $color = $daysLeft > 14 ? 'emerald' : ($daysLeft >= 0 ? 'amber' : 'rose');
        @endphp
        <x-stat-card 
            title="Masa Berlaku Lisensi" 
            :value="$activeSubscription ? $activeSubscription->end_date->format('d M Y') : '-'" 
            :subtitle="$activeSubscription ? ($daysLeft >= 0 ? 'Sisa ' . $daysLeft . ' hari lagi' : 'Kedaluwarsa ' . abs($daysLeft) . ' hari lalu') : 'Status tidak aktif'"
            :color="$color"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <!-- 3. Kuota Siswa Terdaftar -->
        <x-stat-card 
            title="Utilisasi Kuota Siswa" 
            :value="$currentStudentsCount . ' / ' . $studentQuota" 
            :subtitle="$quotaUsagePercent . '% kapasitas siswa terpakai'"
            :color="$quotaUsagePercent >= 90 ? 'rose' : ($quotaUsagePercent >= 75 ? 'amber' : 'blue')"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <!-- 4. Utilisasi Ruang Penyimpanan -->
        <x-stat-card 
            title="Penyimpanan Cloud" 
            :value="$storageUsedMb . ' MB / ' . $storageQuotaMb . ' MB'" 
            :subtitle="$storageUsagePercent . '% ruang disk terpakai'"
            :color="$storageUsagePercent >= 90 ? 'rose' : ($storageUsagePercent >= 75 ? 'amber' : 'teal')"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
            </x-slot:icon>
        </x-stat-card>

    </div>

    <!-- Features Entitlement Grid -->
    <x-card>
        <div class="space-y-4">
            <div class="pb-3 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">Fitur Yang Termasuk Dalam Paket Anda</h3>
                    <p class="text-xs text-slate-500 font-medium">Hak akses modul fungsional sesuai lisensi sewa aktif.</p>
                </div>
                @if($activeSubscription)
                    @php $status = $activeSubscription->computed_status; @endphp
                    @if($status === 'trial')
                        <x-badge variant="indigo" size="sm">Trial 14 Hari</x-badge>
                    @elseif($status === 'active')
                        <x-badge variant="success" size="sm" :pulse="true">Langganan Aktif</x-badge>
                    @elseif($status === 'grace_period')
                        <x-badge variant="warning" size="sm">Masa Tenggang</x-badge>
                    @elseif($status === 'expiring_soon')
                        <x-badge variant="warning" size="sm" :pulse="true">Segera Berakhir</x-badge>
                    @else
                        <x-badge variant="danger" size="sm">Kedaluwarsa</x-badge>
                    @endif
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @php
                    $featuresList = [
                        ['key' => 'attendance', 'label' => 'Presensi Selfie & GPS Geofencing', 'desc' => 'Validasi radius dan foto swafoto harian'],
                        ['key' => 'journal', 'label' => 'Jurnal Harian & Review Bertingkat', 'desc' => 'Verifikasi jurnal 2 tingkat Guru & DUDI'],
                        ['key' => 'assessment', 'label' => 'Penilaian PKL & Pembobotan Nilai', 'desc' => 'Kombinasi penilaian industri & akademik'],
                        ['key' => 'certificate', 'label' => 'E-Sertifikat & Transkrip Terpadu', 'desc' => 'Generator sertifikat PDF & QR Code publik'],
                        ['key' => 'whatsapp_gateway', 'label' => 'WhatsApp Gateway Otomatis', 'desc' => 'Notifikasi real-time presensi ke orang tua'],
                        ['key' => 'custom_domain', 'label' => 'Domain Kustom Sekolah', 'desc' => 'Gunakan subdomain atau domain mandiri'],
                    ];
                @endphp

                @foreach($featuresList as $feat)
                    @php
                        $isEnabled = $school->hasFeature($feat['key']);
                    @endphp
                    <div class="p-3.5 rounded-xl border {{ $isEnabled ? 'bg-emerald-50/40 border-emerald-200' : 'bg-slate-50 border-slate-200 opacity-60' }} flex items-start space-x-3">
                        <div class="mt-0.5 w-5 h-5 rounded-lg flex items-center justify-center shrink-0 {{ $isEnabled ? 'bg-emerald-500 text-white' : 'bg-slate-300 text-slate-600' }}">
                            @if($isEnabled)
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            @else
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <span class="text-xs font-bold {{ $isEnabled ? 'text-slate-900' : 'text-slate-500' }} block">{{ $feat['label'] }}</span>
                            <span class="text-[11px] text-slate-500 block leading-tight mt-0.5">{{ $feat['desc'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Notice for Renewal / Support -->
            <div class="mt-4 p-4 rounded-xl bg-slate-900 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm">
                <div>
                    <h4 class="text-xs sm:text-sm font-extrabold text-white">Butuh Tambahan Kuota Siswa atau Perpanjangan Sewa?</h4>
                    <p class="text-[11px] text-slate-300 mt-0.5">Hubungi Super Administrator melalui WhatsApp resmi atau email pengelola platform.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-mono text-indigo-300 bg-indigo-950/80 px-3 py-1.5 rounded-lg border border-indigo-800">
                        admin@vokatrack.id
                    </span>
                </div>
            </div>
        </div>
    </x-card>

    <!-- Table: Invoices History -->
    <x-card noPadding>
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">Riwayat Tagihan & Faktur Sewa</h3>
                <p class="text-xs text-slate-500 font-medium">Daftar invoice resmi sewa sistem yang diterbitkan untuk {{ $school->name }}.</p>
            </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs min-w-[700px]">
                <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6">No. Invoice</th>
                        <th class="py-3.5 px-4">Deskripsi / Peruntukan</th>
                        <th class="py-3.5 px-4">Total Tagihan</th>
                        <th class="py-3.5 px-4">Jatuh Tempo</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-6 text-center">Dokumen PDF</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($invoices as $inv)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-4 px-6 font-mono font-bold text-slate-900">
                                {{ $inv->invoice_number }}
                            </td>
                            <td class="py-4 px-4">
                                <span class="font-bold text-slate-900 block">{{ $inv->title }}</span>
                                <span class="text-[11px] text-slate-400 block">{{ $inv->payment_method ?? 'Transfer Bank' }}</span>
                            </td>
                            <td class="py-4 px-4 font-mono font-bold text-slate-900">
                                {{ $inv->formatted_amount }}
                            </td>
                            <td class="py-4 px-4 font-mono text-slate-600">
                                {{ $inv->due_date ? $inv->due_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-4 px-4 text-center">
                                @if($inv->status === 'paid')
                                    <x-badge variant="success" size="xs">LUNAS</x-badge>
                                @elseif($inv->status === 'cancelled')
                                    <x-badge variant="danger" size="xs">BATAL</x-badge>
                                @else
                                    <x-badge variant="warning" size="xs">MENUNGGU PEMBAYARAN</x-badge>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-center">
                                <a href="{{ route('adminsekolah.invoices.pdf', $inv->id) }}" class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-[11px] transition-colors border border-indigo-200">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span>Unduh PDF</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center">
                                <x-empty-state 
                                    title="Belum Ada Riwayat Faktur Tagihan" 
                                    description="Faktur invoice resmi akan muncul di sini setelah diterbitkan oleh Super Administrator."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
            <div class="p-4 border-t border-slate-100">
                <x-pagination :paginator="$invoices" />
            </div>
        @endif
    </x-card>

</div>
@endsection
