@extends('layouts.app')

@section('page_title', 'Ringkasan')
@section('page_subtitle', 'Hal yang perlu ditindaklanjuti hari ini dan kesehatan bisnis platform')

@php
    $rupiah = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $sc = $metrics['stage_counts'];
    $todoTotal = $pendingRequestsCount + $openInvoices->count() + $attentionCount + ($failedJobsCount > 0 ? 1 : 0);
    $stageBar = [
        'trial' => ['Trial', 'bg-sky-500'],
        'active' => ['Aktif', 'bg-emerald-500'],
        'expiring' => ['Akan Habis', 'bg-amber-500'],
        'grace' => ['Tenggang', 'bg-orange-500'],
        'expired' => ['Kedaluwarsa', 'bg-rose-500'],
        'suspended' => ['Ditangguhkan', 'bg-slate-400'],
    ];
    $barTotal = max(1, array_sum(array_intersect_key($sc, $stageBar)));
@endphp

@section('content')
<div class="w-full space-y-6 animate-in fade-in duration-300">

    <x-page-header title="Ringkasan Platform"
                   :subtitle="$todoTotal > 0 ? 'Ada ' . $todoTotal . ' hal yang menunggu tindakan Anda.' : 'Semua beres. Tidak ada yang menunggu tindakan.'">
        <a href="{{ route('superadmin.schools') }}" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-extrabold bg-[#C6F26B] text-[#0B2B25] hover:brightness-95">Kelola Sekolah</a>
    </x-page-header>

    {{-- Angka bisnis --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <x-card>
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Sekolah berbayar</div>
            <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ $metrics['active_tenants'] }}</div>
            <div class="text-[11px] text-slate-500">{{ $metrics['trial_tenants'] }} sedang trial · {{ $metrics['total_schools'] }} total</div>
        </x-card>
        <x-card>
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">MRR</div>
            <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ $rupiah($metrics['mrr_estimate']) }}</div>
            <div class="text-[11px] text-slate-500">ARR {{ $rupiah($metrics['arr_estimate']) }}</div>
        </x-card>
        <x-card>
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Piutang berjalan</div>
            <div class="text-2xl font-extrabold {{ $metrics['outstanding_amount'] > 0 ? 'text-amber-700' : 'text-slate-900' }} mt-1">{{ $rupiah($metrics['outstanding_amount']) }}</div>
            <div class="text-[11px] text-slate-500">{{ $metrics['pending_invoices_count'] }} tagihan terbuka</div>
        </x-card>
        <x-card>
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Pendapatan diterima</div>
            <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ $rupiah($metrics['total_revenue']) }}</div>
            <div class="text-[11px] text-slate-500">{{ number_format($metrics['total_students']) }} siswa di platform</div>
        </x-card>
    </div>

    {{-- Distribusi tahap --}}
    <x-card title="Sebaran Tahap Sekolah">
        <div class="flex h-3 rounded-full overflow-hidden bg-slate-100">
            @foreach($stageBar as $key => [$text, $color])
                @if($sc[$key] > 0)
                    <div class="{{ $color }}" style="width: {{ $sc[$key] / $barTotal * 100 }}%" title="{{ $text }}: {{ $sc[$key] }}"></div>
                @endif
            @endforeach
        </div>
        <div class="flex flex-wrap gap-x-5 gap-y-2 mt-3">
            @foreach($stageBar as $key => [$text, $color])
                <a href="{{ route('superadmin.schools', ['stage' => $key]) }}" class="flex items-center gap-1.5 text-xs text-slate-600 hover:text-slate-900">
                    <span class="w-2 h-2 rounded-full {{ $color }}"></span>{{ $text }} <span class="font-bold text-slate-900">{{ $sc[$key] }}</span>
                </a>
            @endforeach
        </div>
    </x-card>

    {{-- Kotak tindakan --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <x-card title="Sekolah Perlu Perhatian" :subtitle="$attentionCount . ' sekolah akan habis, dalam tenggang, atau trial hampir selesai'" :no-padding="true">
            <ul class="divide-y divide-slate-100">
                @forelse($attention as $row)
                    @php $d = $row['sub']->daysRemaining(); @endphp
                    <li>
                        <a href="{{ route('superadmin.schools.show', $row['school']) }}" class="px-5 py-3 flex items-center justify-between gap-3 hover:bg-slate-50">
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-slate-900 truncate">{{ $row['school']->name }}</div>
                                <div class="text-[11px] text-slate-500">{{ $row['sub']->plan_name }} · {{ $d < 0 ? 'lewat ' . abs($d) . ' hari' : 'berakhir ' . $d . ' hari lagi' }}</div>
                            </div>
                            <x-superadmin.stage-badge :stage="$row['stage']" size="sm" />
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-xs text-slate-400">Tidak ada sekolah yang mendekati akhir masa sewa.</li>
                @endforelse
            </ul>
            @if($attentionCount > 8)
                <x-slot:cardFooter><a href="{{ route('superadmin.schools', ['stage' => 'attention']) }}" class="font-bold text-emerald-700">Lihat semua</a></x-slot:cardFooter>
            @endif
        </x-card>

        <x-card title="Tagihan Menunggu Pembayaran" :subtitle="$metrics['pending_invoices_count'] . ' tagihan · ' . $rupiah($metrics['outstanding_amount'])" :no-padding="true">
            <ul class="divide-y divide-slate-100">
                @forelse($openInvoices as $inv)
                    <li>
                        <a href="{{ route('superadmin.schools.show', [$inv->school_id, 'tab' => 'tagihan']) }}" class="px-5 py-3 flex items-center justify-between gap-3 hover:bg-slate-50">
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-slate-900 truncate">{{ $inv->school?->name }}</div>
                                <div class="text-[11px] text-slate-500 truncate">{{ $inv->type_label }} · {{ $inv->invoice_number }}</div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-xs font-mono font-bold text-slate-900">{{ $inv->formatted_amount }}</div>
                                <div class="text-[11px] font-semibold {{ $inv->isOverdue() ? 'text-rose-600' : 'text-slate-400' }}">
                                    {{ $inv->isOverdue() ? 'lewat jatuh tempo' : 'jatuh tempo ' . $inv->due_date->translatedFormat('d M') }}
                                </div>
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-xs text-slate-400">Semua tagihan sudah lunas.</li>
                @endforelse
            </ul>
            @if($metrics['pending_invoices_count'] > $openInvoices->count())
                <x-slot:cardFooter><a href="{{ route('superadmin.invoices', ['status' => 'unpaid']) }}" class="font-bold text-emerald-700">Lihat semua tagihan</a></x-slot:cardFooter>
            @endif
        </x-card>

        <x-card title="Permohonan Demo Baru" :subtitle="$pendingRequestsCount . ' menunggu ditinjau'" :no-padding="true">
            <ul class="divide-y divide-slate-100">
                @forelse($pendingRequests as $req)
                    <li class="px-5 py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-slate-900 truncate">{{ $req->school_name }}</div>
                            <div class="text-[11px] text-slate-500 truncate">{{ $req->contact_name }} · {{ $req->created_at->diffForHumans() }}</div>
                        </div>
                        <a href="{{ route('superadmin.school-requests', ['status' => 'pending']) }}" class="px-2.5 py-1 rounded-lg bg-slate-50 text-slate-700 border border-slate-200 text-[11px] font-bold shrink-0">Tinjau</a>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-xs text-slate-400">Tidak ada permohonan baru.</li>
                @endforelse
            </ul>
        </x-card>

        <x-card title="Aktivitas Terakhir" :no-padding="true">
            @if($failedJobsCount > 0)
                <a href="{{ route('superadmin.system-health') }}" class="mx-5 mt-4 flex items-center justify-between p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs font-bold text-rose-800">
                    <span>{{ $failedJobsCount }} job antrean gagal</span><span>Periksa</span>
                </a>
            @endif
            <ul class="divide-y divide-slate-100">
                @forelse($recentActivity as $log)
                    <li class="px-5 py-3">
                        <div class="text-xs text-slate-800 line-clamp-2">{{ $log->description }}</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">{{ $log->user?->name ?? 'Sistem' }} · {{ $log->created_at->diffForHumans() }}</div>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-xs text-slate-400">Belum ada aktivitas.</li>
                @endforelse
            </ul>
        </x-card>
    </div>
</div>
@endsection
