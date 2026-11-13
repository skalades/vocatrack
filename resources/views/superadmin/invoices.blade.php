@extends('layouts.app')

@section('page_title', 'Tagihan & Faktur Sewa SaaS')
@section('page_subtitle', 'Penerbitan faktur sewa, pelacakan pembayaran sekolah, dan rekapitulasi invoice')

@section('content')
<div class="w-full space-y-6 sm:space-y-8 animate-in fade-in duration-300">

    <!-- Enterprise Hero Header -->
    <x-page-header 
        title="Tagihan & Invoice Sewa Platform" 
        subtitle="Kelola penerbitan faktur tagihan sewa sekolah mitra, verifikasi pelunasan, dan pencatatan pendapatan SaaS."
        badge="BILLING & INVOICING"
    >
        <x-button type="button" variant="primary" size="md" onclick="document.getElementById('modal-create-invoice').classList.remove('hidden')" class="shadow-lg shadow-indigo-600/25">
            <svg class="w-4 h-4 mr-1.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Terbitkan Tagihan Baru
        </x-button>
    </x-page-header>

    <!-- Stat Metric KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <x-stat-card 
            label="Total Faktur Diterbitkan" 
            :value="$invoices->total()" 
            description="Semua transaksi tercatat"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8h4"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            label="Faktur Lunas" 
            :value="$invoices->where('status', 'paid')->count()" 
            description="Pembayaran terverifikasi"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            label="Menunggu Pembayaran" 
            :value="$invoices->where('status', 'unpaid')->count()" 
            description="Belum dilunasi oleh sekolah"
            color="amber"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            label="Total Nominal Invoice" 
            :value="'Rp ' . number_format($invoices->sum('amount'), 0, ',', '.')" 
            description="Akumulasi tagihan sewa"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Search & Filter Card -->
    <x-card>
        <form method="GET" action="{{ route('superadmin.invoices') }}" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="relative flex-1">
                <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor invoice, nama sekolah mitra, atau judul..." 
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs sm:text-sm font-medium text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-colors">
            </div>
            <div class="flex items-center gap-2">
                <select name="status" onchange="this.form.submit()" class="text-xs font-bold px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
                    <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Lunas</option>
                    <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Belum Dibayar</option>
                </select>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('superadmin.invoices') }}" class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Table of Invoices -->
    <x-card noPadding>
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs min-w-[900px]">
                <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6 min-w-[180px]">Nomor & Judul Tagihan</th>
                        <th class="py-3.5 px-4 min-w-[200px]">Sekolah Mitra</th>
                        <th class="py-3.5 px-4 min-w-[140px]">Nominal (Rp)</th>
                        <th class="py-3.5 px-4 min-w-[130px]">Jatuh Tempo</th>
                        <th class="py-3.5 px-4 text-center min-w-[110px]">Status</th>
                        <th class="py-3.5 px-6 text-center table-sticky-right min-w-[140px]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($invoices as $inv)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-4 px-6">
                                <span class="font-mono font-bold text-slate-900 block text-xs">{{ $inv->invoice_number }}</span>
                                <span class="text-[11px] text-slate-500 block truncate mt-0.5">{{ $inv->title }}</span>
                            </td>
                            <td class="py-4 px-4">
                                <span class="font-bold text-slate-900 block text-xs">{{ $inv->school->name ?? 'Sekolah Tidak Ditemukan' }}</span>
                                <span class="text-[10px] text-slate-500 font-mono block">NPSN: {{ $inv->school->npsn ?? '-' }}</span>
                            </td>
                            <td class="py-4 px-4">
                                <span class="font-mono font-bold text-slate-900 text-xs block">{{ $inv->formatted_amount }}</span>
                                <span class="text-[10px] text-slate-400 block">{{ $inv->payment_method ?? 'Metode belum ditentukan' }}</span>
                            </td>
                            <td class="py-4 px-4">
                                <span class="text-xs font-semibold text-slate-800 block">{{ $inv->due_date->format('d M Y') }}</span>
                                @if($inv->status === 'paid' && $inv->paid_at)
                                    <span class="text-[10px] text-emerald-600 block">Dibayar: {{ $inv->paid_at->format('d/m/Y') }}</span>
                                @elseif($inv->due_date->isPast())
                                    <span class="text-[10px] text-rose-600 font-bold block">Jatuh tempo terlewati</span>
                                @endif
                            </td>
                            <td class="py-4 px-4 text-center">
                                @if($inv->status === 'paid')
                                    <x-badge variant="success" size="xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1"></span>
                                        Lunas
                                    </x-badge>
                                @elseif($inv->status === 'pending_verification')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 animate-pulse">
                                        Verifikasi Bukti
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        Belum Bayar
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-center table-sticky-right">
                                @if($inv->status !== 'paid')
                                    <form action="{{ route('superadmin.invoices.verify', $inv->id) }}" method="POST" onsubmit="return confirm('Konfirmasi bahwa pembayaran invoice ini telah diterima dan lunas?')">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-bold transition-colors shadow-xs">
                                            Tandai Lunas
                                        </button>
                                    </form>
                                @else
                                    <span class="text-[11px] text-slate-400 italic font-mono">Terverifikasi</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12">
                                <x-empty-state 
                                    title="Belum Ada Tagihan Invoice" 
                                    description="Faktur invoice diterbitkan otomatis saat sekolah berlangganan atau secara manual."
                                    actionLabel="+ Terbitkan Invoice Baru"
                                    actionClick="document.getElementById('modal-create-invoice').classList.remove('hidden')"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $invoices->links() }}
            </div>
        @endif
    </x-card>

</div>

<!-- Modal Terbitkan Invoice Manual -->
<div id="modal-create-invoice" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 sm:p-7 shadow-2xl border border-slate-100 animate-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Terbitkan Tagihan Invoice Manual</h3>
                <p class="text-xs text-slate-500 font-medium">Buat invoice tagihan baru untuk sekolah mitra tertentu</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-create-invoice').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('superadmin.invoices.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilih Sekolah Mitra</label>
                <select name="school_id" required class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 font-medium text-slate-900">
                    <option value="">-- Pilih Sekolah --</option>
                    @foreach($schools as $sch)
                        <option value="{{ $sch->id }}">{{ $sch->name }} (NPSN: {{ $sch->npsn }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Judul / Peruntukan Tagihan</label>
                <input type="text" name="title" required placeholder="Contoh: Biaya Sewa Lisensi VokaTrack Semester Genap 2026/2027" 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 font-medium text-slate-900">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Nominal Tagihan (Rp)</label>
                    <input type="number" name="amount" required min="1000" step="50000" placeholder="5000000" 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 font-mono font-bold text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Batas Jatuh Tempo</label>
                    <input type="date" name="due_date" required value="{{ \Carbon\Carbon::today()->addDays(14)->format('Y-m-d') }}" 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 font-medium text-slate-900">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Catatan Tambahan (Opsional)</label>
                <textarea name="notes" rows="2" placeholder="Rekening pembayaran BNI 12345678 a.n. VokaTrack Cloud..." 
                          class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 font-medium text-slate-900 leading-relaxed"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-create-invoice').classList.add('hidden')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md" class="shadow-md shadow-indigo-600/20">
                    Terbitkan Invoice
                </x-button>
            </div>
        </form>
    </div>
</div>
@endsection
