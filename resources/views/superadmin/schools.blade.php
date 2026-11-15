@extends('layouts.app')

@section('page_title', 'Sekolah')
@section('page_subtitle', 'Seluruh tenant sekolah dikelompokkan menurut tahap siklus langganan')

@php
    $input = 'w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600';
    $label = 'block text-xs font-bold text-slate-700 mb-1.5';
    $filters = [
        'all' => ['Semua', $totalCount],
        'attention' => ['Perlu Tindakan', $attentionCount],
        'trial' => ['Trial', $stageCounts['trial']],
        'active' => ['Aktif', $stageCounts['active']],
        'expiring' => ['Akan Habis', $stageCounts['expiring']],
        'grace' => ['Tenggang', $stageCounts['grace']],
        'expired' => ['Kedaluwarsa', $stageCounts['expired']],
        'suspended' => ['Ditangguhkan', $stageCounts['suspended']],
    ];
@endphp

@section('content')
<div class="w-full space-y-6 animate-in fade-in duration-300">

    <x-page-header title="Tenant Sekolah" subtitle="Pilih sekolah untuk mengelola langganan, tagihan, pengguna, dan pengaturannya dari satu halaman." :show-clock="false">
        <a href="{{ route('superadmin.schools.export') }}" class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-white/10 hover:bg-white/20 border border-white/20">Ekspor CSV</a>
        <button type="button" onclick="document.getElementById('modal-add-school').classList.remove('hidden')" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-extrabold bg-[#C6F26B] text-[#0B2B25] hover:brightness-95">
            + Daftarkan Sekolah
        </button>
    </x-page-header>

    {{-- Filter tahap --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
        <div class="flex gap-1.5 overflow-x-auto pb-1">
            @foreach($filters as $key => [$text, $count])
                <a href="{{ route('superadmin.schools', array_filter(['stage' => $key === 'all' ? null : $key, 'search' => request('search')])) }}"
                   class="px-3 py-1.5 rounded-full text-xs font-bold whitespace-nowrap border transition-colors {{ $stageFilter === $key ? 'bg-[#0B2B25] text-white border-[#0B2B25]' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300' }}">
                    {{ $text }} <span class="ml-1 {{ $stageFilter === $key ? 'text-[#C6F26B]' : 'text-slate-400' }}">{{ $count }}</span>
                </a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('superadmin.schools') }}" class="flex gap-2">
            @if($stageFilter !== 'all')
                <input type="hidden" name="stage" value="{{ $stageFilter }}">
            @endif
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama, NPSN, email" class="{{ $input }} lg:w-64">
            <button class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-900 text-white">Cari</button>
        </form>
    </div>

    <x-card :no-padding="true">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="text-left px-5 py-3 font-bold">Sekolah</th>
                        <th class="text-left px-4 py-3 font-bold">Tahap</th>
                        <th class="text-left px-4 py-3 font-bold">Paket</th>
                        <th class="text-left px-4 py-3 font-bold">Berlaku s.d.</th>
                        <th class="text-right px-4 py-3 font-bold">Siswa</th>
                        <th class="text-left px-4 py-3 font-bold">Tagihan</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($schools as $sch)
                        @php
                            $sub = $sch->activeSubscription();
                            $days = $sub?->daysRemaining();
                            $students = (int) ($studentCounts[$sch->id] ?? 0);
                        @endphp
                        <tr class="hover:bg-slate-50/70 cursor-pointer" onclick="window.location='{{ route('superadmin.schools.show', $sch) }}'">
                            <td class="px-5 py-3">
                                <a href="{{ route('superadmin.schools.show', $sch) }}" class="font-bold text-slate-900 hover:text-emerald-700">{{ $sch->name }}</a>
                                <div class="text-[11px] text-slate-400 font-mono">{{ $sch->npsn }}</div>
                            </td>
                            <td class="px-4 py-3"><x-superadmin.stage-badge :stage="$stages[$sch->id]" size="sm" /></td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800">{{ $sub?->plan_name ?? '-' }}</div>
                                <div class="text-[11px] text-slate-400">{{ $sub ? ((float) $sub->price_paid > 0 ? 'Rp ' . number_format($sub->price_paid, 0, ',', '.') : 'Gratis') : '' }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($sub)
                                    <div class="font-semibold text-slate-800">{{ $sub->end_date->translatedFormat('d M Y') }}</div>
                                    <div class="text-[11px] font-semibold {{ $days < 0 ? 'text-rose-600' : ($days <= 30 ? 'text-amber-600' : 'text-slate-400') }}">
                                        {{ $days < 0 ? 'lewat ' . abs($days) . ' hari' : $days . ' hari lagi' }}
                                    </div>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <span class="font-bold text-slate-900">{{ number_format($students) }}</span>
                                <span class="text-slate-400">/ {{ $sub ? number_format($sub->student_quota) : '-' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if($sch->open_invoices_count > 0)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800">{{ $sch->open_invoices_count }} terbuka</span>
                                @else
                                    <span class="text-[11px] text-slate-400">Lunas</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <svg class="w-4 h-4 text-slate-300 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center">
                                <div class="text-sm font-bold text-slate-700">Tidak ada sekolah pada filter ini</div>
                                <p class="text-xs text-slate-400 mt-1">Ubah filter tahap atau kata pencarian.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($schools->hasPages())
            <div class="px-5 py-3 border-t border-slate-100">{{ $schools->links() }}</div>
        @endif
    </x-card>
</div>

{{-- Modal: Daftarkan sekolah --}}
<div id="modal-add-school" onclick="if(event.target === this) this.classList.add('hidden')" class="{{ $errors->any() && old('admin_email') ? '' : 'hidden' }} fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-2xl rounded-3xl p-6 shadow-2xl max-h-[92vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Daftarkan Sekolah Baru</h3>
                <p class="text-xs text-slate-500">Paket berbayar yang belum lunas otomatis mendapat trial {{ \App\Services\SubscriptionService::TRIAL_DAYS }} hari sampai tagihan aktivasi dibayar.</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-add-school').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100" aria-label="Tutup">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('superadmin.schools.store') }}" method="POST" class="space-y-5">
            @csrf
            <fieldset class="space-y-3">
                <legend class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 mb-2">1. Identitas sekolah</legend>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div><label class="{{ $label }}">NPSN (8 digit)</label><input type="text" name="npsn" value="{{ old('npsn') }}" inputmode="numeric" pattern="[0-9]{8}" maxlength="8" required class="{{ $input }} font-mono"></div>
                    <div><label class="{{ $label }}">Nama resmi</label><input type="text" name="name" value="{{ old('name') }}" required maxlength="150" class="{{ $input }}"></div>
                    <div><label class="{{ $label }}">Telepon</label><input type="text" name="phone" value="{{ old('phone') }}" required maxlength="20" class="{{ $input }}"></div>
                    <div><label class="{{ $label }}">Email sekolah</label><input type="email" name="email" value="{{ old('email') }}" required class="{{ $input }}"></div>
                </div>
                <div><label class="{{ $label }}">Alamat</label><textarea name="address" rows="2" required class="{{ $input }}">{{ old('address') }}</textarea></div>
            </fieldset>

            <fieldset class="space-y-3">
                <legend class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 mb-2">2. Paket langganan</legend>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach($plans as $plan)
                        <label class="flex items-start gap-2.5 p-3 rounded-xl border border-slate-200 hover:border-emerald-500 cursor-pointer has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/50">
                            <input type="radio" name="plan_id" value="{{ $plan->id }}" {{ (string) old('plan_id', $plans->first()?->id) === (string) $plan->id ? 'checked' : '' }} class="mt-0.5 text-emerald-600">
                            <span>
                                <span class="block text-xs font-bold text-slate-900">{{ $plan->name }}</span>
                                <span class="block text-[11px] text-slate-500">{{ $plan->price > 0 ? $plan->formatted_price . ' ' . $plan->period_label : 'Gratis ' . \App\Services\SubscriptionService::TRIAL_DAYS . ' hari' }} · {{ $plan->max_students }} siswa</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <x-superadmin.payment-fields />
                <details class="rounded-xl border border-slate-200 p-3">
                    <summary class="text-xs font-bold text-slate-600 cursor-pointer">Kuota kustom (opsional)</summary>
                    <div class="grid grid-cols-2 gap-3 mt-3">
                        <div><label class="{{ $label }}">Kuota siswa</label><input type="number" name="custom_quota" min="10" placeholder="Ikuti paket" class="{{ $input }}"></div>
                        <div><label class="{{ $label }}">Storage (MB)</label><input type="number" name="custom_storage_mb" min="100" placeholder="Ikuti paket" class="{{ $input }}"></div>
                    </div>
                </details>
            </fieldset>

            <fieldset class="space-y-3">
                <legend class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 mb-2">3. Admin sekolah pertama</legend>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div><label class="{{ $label }}">Nama</label><input type="text" name="admin_name" value="{{ old('admin_name') }}" required maxlength="100" class="{{ $input }}"></div>
                    <div><label class="{{ $label }}">Email login</label><input type="email" name="admin_email" value="{{ old('admin_email') }}" required class="{{ $input }}"></div>
                </div>
                <p class="text-[11px] text-slate-500">Kata sandi sementara dibuat otomatis dan ditampilkan sekali setelah disimpan.</p>
            </fieldset>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modal-add-school').classList.add('hidden')" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200">Batal</button>
                <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-extrabold bg-emerald-600 text-white hover:bg-emerald-700">Daftarkan</button>
            </div>
        </form>
    </div>
</div>
@endsection
