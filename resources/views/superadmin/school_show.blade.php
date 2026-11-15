@extends('layouts.app')

@section('page_title', $school->name)
@section('page_subtitle', 'Detail sekolah: siklus langganan, tagihan, pengguna, dan pengaturan tenant')

@php
    $input = 'w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600';
    $label = 'block text-xs font-bold text-slate-700 mb-1.5';
    $rupiah = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');

    $studentQuota = max(1, (int) ($subscription?->student_quota ?? 0));
    $storageQuota = max(1, (int) ($subscription?->storage_quota_mb ?? 0));
    $studentPct = $subscription ? min(100, round($studentsCount / $studentQuota * 100)) : 0;
    $storagePct = $subscription ? min(100, round($storageUsedMb / $storageQuota * 100)) : 0;

    $days = $subscription?->daysRemaining();
    $accessEnds = $subscription ? $subscription->end_date->copy()->addDays(\App\Services\TenantLifecycleService::GRACE_DAYS) : null;

    $tabLabels = [
        'ringkasan' => 'Ringkasan',
        'langganan' => 'Langganan',
        'tagihan' => 'Tagihan',
        'pengguna' => 'Pengguna',
        'aktivitas' => 'Aktivitas',
        'pengaturan' => 'Pengaturan',
    ];

    $timeline = ['trial' => 'Trial', 'active' => 'Aktif', 'expiring' => 'Akan Habis', 'grace' => 'Tenggang', 'expired' => 'Kedaluwarsa'];
    $timelineKeys = array_keys($timeline);
    $currentIndex = array_search($stage, $timelineKeys, true);
@endphp

@section('content')
<div class="w-full space-y-6 animate-in fade-in duration-300">

    <a href="{{ route('superadmin.schools') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-slate-900">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Semua sekolah
    </a>

    <x-page-header :title="$school->name" :subtitle="'NPSN ' . $school->npsn . ' · ' . $school->email" :badge="$stageMeta['label']" :show-clock="false">
        <form action="{{ route('superadmin.schools.impersonate', $school) }}" method="POST">
            @csrf
            <button type="submit" class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-white/10 hover:bg-white/20 border border-white/20 transition-colors">
                Masuk sebagai Admin
            </button>
        </form>

        @if($stage === 'suspended')
            <form action="{{ route('superadmin.schools.toggle', $school) }}" method="POST" onsubmit="return confirm('Aktifkan kembali akses sekolah ini?')">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-extrabold bg-[#C6F26B] text-[#0B2B25] hover:brightness-95">Aktifkan Kembali</button>
            </form>
        @elseif($openInvoice)
            <a href="#tagihan-terbuka" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-extrabold bg-[#C6F26B] text-[#0B2B25] hover:brightness-95">Konfirmasi Pembayaran</a>
        @elseif(in_array($stage, ['trial', 'no_plan'], true))
            <a href="{{ route('superadmin.schools.show', [$school, 'tab' => 'langganan']) }}#form-perpanjang" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-extrabold bg-[#C6F26B] text-[#0B2B25] hover:brightness-95">Aktifkan Paket Berbayar</a>
        @else
            <a href="{{ route('superadmin.schools.show', [$school, 'tab' => 'langganan']) }}#form-perpanjang" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-extrabold bg-[#C6F26B] text-[#0B2B25] hover:brightness-95">Perpanjang / Upgrade</a>
        @endif
    </x-page-header>

    {{-- Status siklus hidup --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <x-card class="lg:col-span-2">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                <div class="space-y-2 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <x-superadmin.stage-badge :stage="$stage" />
                        @if($subscription)
                            <span class="text-xs font-bold text-slate-700">{{ $subscription->plan_name }}</span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed">{{ $stageMeta['hint'] }}</p>
                </div>
                @if($subscription)
                    <div class="grid grid-cols-2 gap-4 shrink-0 text-right">
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Berlaku s.d.</div>
                            <div class="text-sm font-extrabold text-slate-900">{{ $subscription->end_date->translatedFormat('d M Y') }}</div>
                            <div class="text-[11px] font-semibold {{ $days < 0 ? 'text-rose-600' : ($days <= 30 ? 'text-amber-600' : 'text-slate-500') }}">
                                {{ $days < 0 ? 'Lewat ' . abs($days) . ' hari' : $days . ' hari lagi' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Akses ditutup</div>
                            <div class="text-sm font-extrabold text-slate-900">{{ $accessEnds->translatedFormat('d M Y') }}</div>
                            <div class="text-[11px] font-semibold text-slate-500">termasuk tenggang {{ \App\Services\TenantLifecycleService::GRACE_DAYS }} hari</div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Garis waktu tahap --}}
            <ol class="mt-5 grid grid-cols-5 gap-1.5">
                @foreach($timeline as $key => $text)
                    @php
                        $idx = array_search($key, $timelineKeys, true);
                        $isCurrent = $key === $stage;
                        $isPast = $currentIndex !== false && $idx < $currentIndex;
                    @endphp
                    <li class="space-y-1.5">
                        <div class="h-1.5 rounded-full {{ $isCurrent ? 'bg-emerald-600' : ($isPast ? 'bg-emerald-200' : 'bg-slate-200') }}"></div>
                        <div class="text-[10px] font-bold {{ $isCurrent ? 'text-emerald-700' : 'text-slate-400' }}">{{ $text }}</div>
                    </li>
                @endforeach
            </ol>
        </x-card>

        {{-- Tagihan terbuka --}}
        <x-card id="tagihan-terbuka" class="{{ $openInvoice ? 'ring-2 ring-amber-300' : '' }}">
            @if($openInvoice)
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider {{ $openInvoice->isOverdue() ? 'text-rose-600' : 'text-amber-700' }}">
                            {{ $openInvoice->isOverdue() ? 'Tagihan lewat jatuh tempo' : 'Tagihan menunggu pembayaran' }}
                        </span>
                        <span class="text-[10px] font-mono text-slate-400">{{ $openInvoice->invoice_number }}</span>
                    </div>
                    <div>
                        <div class="text-lg font-extrabold text-slate-900">{{ $openInvoice->formatted_amount }}</div>
                        <div class="text-xs text-slate-500">{{ $openInvoice->title }}</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">Jatuh tempo {{ $openInvoice->due_date->translatedFormat('d M Y') }}</div>
                    </div>
                    <form action="{{ route('superadmin.invoices.verify', $openInvoice) }}" method="POST" class="space-y-2"
                          onsubmit="return confirm('Konfirmasi pembayaran {{ $openInvoice->invoice_number }} sudah diterima?')">
                        @csrf
                        <input type="text" name="payment_method" maxlength="100" placeholder="Metode bayar (mis. Transfer BNI)" class="{{ $input }}">
                        <button type="submit" class="w-full px-4 py-2.5 rounded-xl text-xs font-extrabold bg-emerald-600 text-white hover:bg-emerald-700">
                            Tandai Lunas{{ $openInvoice->createsPeriod() ? ' & Aktifkan Periode' : '' }}
                        </button>
                    </form>
                </div>
            @else
                <div class="h-full flex flex-col justify-center text-center py-4">
                    <div class="text-sm font-bold text-slate-900">Tidak ada tagihan terbuka</div>
                    <p class="text-xs text-slate-500 mt-1">Tagihan perpanjangan terbit otomatis {{ \App\Services\TenantLifecycleService::RENEWAL_NOTICE_DAYS }} hari sebelum masa sewa berakhir.</p>
                </div>
            @endif
        </x-card>
    </div>

    {{-- Tab --}}
    <nav class="flex gap-1 overflow-x-auto border-b border-slate-200 -mb-2">
        @foreach($tabLabels as $key => $text)
            <a href="{{ route('superadmin.schools.show', [$school, 'tab' => $key]) }}"
               class="px-4 py-2.5 text-xs font-bold whitespace-nowrap border-b-2 -mb-px transition-colors {{ $tab === $key ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-900' }}">
                {{ $text }}
            </a>
        @endforeach
    </nav>

    {{-- ============ RINGKASAN ============ --}}
    @if($tab === 'ringkasan')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-card title="Pemakaian Kuota">
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-xs font-bold text-slate-700 mb-1.5">
                            <span>Siswa terdaftar</span>
                            <span>{{ number_format($studentsCount) }} / {{ $subscription ? number_format($subscription->student_quota) : '-' }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full {{ $studentPct >= 90 ? 'bg-rose-500' : ($studentPct >= 75 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $studentPct }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-xs font-bold text-slate-700 mb-1.5">
                            <span>Storage media</span>
                            <span>{{ number_format($storageUsedMb, 1) }} MB / {{ $subscription ? number_format($subscription->storage_quota_mb) . ' MB' : '-' }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full {{ $storagePct >= 90 ? 'bg-rose-500' : ($storagePct >= 75 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $storagePct }}%"></div>
                        </div>
                    </div>
                </div>
            </x-card>

            <x-card title="Data Sekolah">
                <dl class="grid grid-cols-2 gap-4 text-xs">
                    <div><dt class="text-slate-400 font-bold">Rombel</dt><dd class="text-lg font-extrabold text-slate-900">{{ $classesCount }}</dd></div>
                    <div><dt class="text-slate-400 font-bold">Mitra DU/DI</dt><dd class="text-lg font-extrabold text-slate-900">{{ $companiesCount }}</dd></div>
                    <div><dt class="text-slate-400 font-bold">Guru pembimbing</dt><dd class="text-lg font-extrabold text-slate-900">{{ $roleCounts['guru-pembimbing'] ?? 0 }}</dd></div>
                    <div><dt class="text-slate-400 font-bold">Total akun</dt><dd class="text-lg font-extrabold text-slate-900">{{ $roleCounts->sum() }}</dd></div>
                </dl>
            </x-card>

            <x-card title="Profil" class="md:col-span-2">
                <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div><dt class="text-slate-400 font-bold">Telepon</dt><dd class="font-semibold text-slate-800">{{ $school->phone ?: '-' }}</dd></div>
                    <div><dt class="text-slate-400 font-bold">Email</dt><dd class="font-semibold text-slate-800 break-all">{{ $school->email ?: '-' }}</dd></div>
                    <div><dt class="text-slate-400 font-bold">Terdaftar</dt><dd class="font-semibold text-slate-800">{{ $school->created_at?->translatedFormat('d M Y') }}</dd></div>
                    <div class="sm:col-span-3"><dt class="text-slate-400 font-bold">Alamat</dt><dd class="font-semibold text-slate-800 whitespace-pre-line">{{ $school->address ?: '-' }}</dd></div>
                </dl>
            </x-card>
        </div>
    @endif

    {{-- ============ LANGGANAN ============ --}}
    @if($tab === 'langganan')
        <div class="grid grid-cols-1 xl:grid-cols-5 gap-4">
            <x-card id="form-perpanjang" class="xl:col-span-2"
                    :title="in_array($stage, ['trial', 'no_plan'], true) ? 'Aktifkan Paket Berbayar' : 'Perpanjang atau Ubah Paket'"
                    subtitle="Periode baru aktif begitu tagihan lunas.">
                <form action="{{ route('superadmin.schools.extend', $school) }}" method="POST" class="space-y-3" id="renew-form">
                    @csrf
                    <div>
                        <label class="{{ $label }}">Paket</label>
                        @php
                            $renewPlans = $plans->filter(fn ($p) => (float) $p->price > 0)->values();
                            $currentPaid = $subscription && $subscription->status !== 'trial' ? $renewPlans->firstWhere('id', $subscription->plan_id) : null;
                            $defaultPlanId = ($currentPaid ?? $renewPlans->firstWhere('is_popular', true) ?? $renewPlans->first())?->id;
                        @endphp
                        <select name="plan_id" id="renew-plan" class="{{ $input }}">
                            @foreach($renewPlans as $p)
                                <option value="{{ $p->id }}" data-price="{{ (float) $p->price }}" data-months="{{ $p->periodMonths() }}"
                                    {{ $p->id === $defaultPlanId ? 'selected' : '' }}>
                                    {{ $p->name }} — {{ $p->formatted_price }} {{ $p->period_label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="{{ $label }}">Durasi</label>
                            <select name="months" id="renew-months" class="{{ $input }}">
                                @foreach([1, 3, 6, 12, 24] as $m)
                                    <option value="{{ $m }}" {{ $m === 12 ? 'selected' : '' }}>{{ $m }} bulan</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Nominal (Rp)</label>
                            <input type="number" name="price" id="renew-price" min="0" step="any" required class="{{ $input }} font-mono">
                        </div>
                    </div>
                    <x-superadmin.payment-fields />
                    <details class="rounded-xl border border-slate-200 p-3">
                        <summary class="text-xs font-bold text-slate-600 cursor-pointer">Kuota kustom (opsional)</summary>
                        <div class="grid grid-cols-2 gap-3 mt-3">
                            <div>
                                <label class="{{ $label }}">Kuota siswa</label>
                                <input type="number" name="new_quota" min="10" placeholder="Ikuti paket" class="{{ $input }}">
                            </div>
                            <div>
                                <label class="{{ $label }}">Storage (MB)</label>
                                <input type="number" name="custom_storage_mb" min="100" placeholder="Ikuti paket" class="{{ $input }}">
                            </div>
                        </div>
                    </details>
                    <div>
                        <label class="{{ $label }}">Catatan</label>
                        <input type="text" name="notes" maxlength="255" class="{{ $input }}">
                    </div>
                    <button type="submit" class="w-full px-4 py-2.5 rounded-xl text-xs font-extrabold bg-emerald-600 text-white hover:bg-emerald-700">Simpan</button>
                </form>
            </x-card>

            <x-card class="xl:col-span-3" title="Riwayat Periode" subtitle="Setiap perpanjangan tercatat sebagai periode tersendiri." :no-padding="true">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="text-left px-4 py-2.5 font-bold">Paket</th>
                                <th class="text-left px-4 py-2.5 font-bold">Periode</th>
                                <th class="text-right px-4 py-2.5 font-bold">Nilai</th>
                                <th class="text-right px-4 py-2.5 font-bold">Kuota</th>
                                <th class="text-left px-4 py-2.5 font-bold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($school->subscriptions as $period)
                                @php
                                    $state = match (true) {
                                        $period->status === 'converted' => ['Dikonversi', 'bg-slate-100 text-slate-600'],
                                        $period->start_date->isFuture() => ['Akan datang', 'bg-sky-50 text-sky-700'],
                                        $period->end_date->lt(today()) => ['Selesai', 'bg-slate-100 text-slate-600'],
                                        $period->status === 'trial' => ['Trial berjalan', 'bg-sky-50 text-sky-700'],
                                        default => ['Berjalan', 'bg-emerald-50 text-emerald-700'],
                                    };
                                @endphp
                                <tr class="{{ $subscription && $period->id === $subscription->id ? 'bg-emerald-50/40' : '' }}">
                                    <td class="px-4 py-3 font-bold text-slate-900">{{ $period->plan_name }}</td>
                                    <td class="px-4 py-3 text-slate-600 whitespace-nowrap">{{ $period->start_date->format('d/m/Y') }} – {{ $period->end_date->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-slate-800">{{ (float) $period->price_paid > 0 ? $rupiah($period->price_paid) : 'Gratis' }}</td>
                                    <td class="px-4 py-3 text-right text-slate-600 whitespace-nowrap">{{ $period->student_quota }} siswa · {{ number_format($period->storage_quota_mb) }} MB</td>
                                    <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $state[1] }}">{{ $state[0] }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">Belum ada periode langganan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    @endif

    {{-- ============ TAGIHAN ============ --}}
    @if($tab === 'tagihan')
        <div class="grid grid-cols-1 xl:grid-cols-4 gap-4">
            <x-card class="xl:col-span-3" title="Semua Tagihan" :no-padding="true">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="text-left px-4 py-2.5 font-bold">Invoice</th>
                                <th class="text-left px-4 py-2.5 font-bold">Jenis</th>
                                <th class="text-right px-4 py-2.5 font-bold">Nominal</th>
                                <th class="text-left px-4 py-2.5 font-bold">Jatuh Tempo</th>
                                <th class="text-left px-4 py-2.5 font-bold">Status</th>
                                <th class="text-right px-4 py-2.5 font-bold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($invoices as $inv)
                                @php
                                    $statusClass = match ($inv->status) {
                                        'paid' => 'bg-emerald-50 text-emerald-700',
                                        'cancelled' => 'bg-slate-100 text-slate-500',
                                        default => $inv->isOverdue() ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-800',
                                    };
                                @endphp
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-mono font-bold text-slate-900">{{ $inv->invoice_number }}</div>
                                        <div class="text-[11px] text-slate-500">{{ $inv->title }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ $inv->type_label }}</td>
                                    <td class="px-4 py-3 text-right font-mono font-bold text-slate-900">{{ $inv->formatted_amount }}</td>
                                    <td class="px-4 py-3 text-slate-600 whitespace-nowrap">{{ $inv->due_date->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $statusClass }}">{{ $inv->isOverdue() ? 'Lewat Jatuh Tempo' : $inv->status_label }}</span></td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-1.5">
                                            @if($inv->isOpen())
                                                <form action="{{ route('superadmin.invoices.verify', $inv) }}" method="POST" onsubmit="return confirm('Tandai {{ $inv->invoice_number }} lunas?')">
                                                    @csrf
                                                    <button class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold">Lunas</button>
                                                </form>
                                                <form action="{{ route('superadmin.invoices.cancel', $inv) }}" method="POST" onsubmit="return confirm('Batalkan {{ $inv->invoice_number }}?')">
                                                    @csrf
                                                    <button class="px-2.5 py-1 rounded-lg bg-slate-50 text-slate-600 border border-slate-200 font-bold">Batal</button>
                                                </form>
                                            @endif
                                            <a href="{{ route('superadmin.invoices.pdf', $inv) }}" class="px-2.5 py-1 rounded-lg bg-slate-50 text-slate-600 border border-slate-200 font-bold">PDF</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Belum ada tagihan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($invoices->hasPages())
                    <div class="px-4 py-3 border-t border-slate-100">{{ $invoices->links() }}</div>
                @endif
            </x-card>

            <x-card title="Tagihan Lain" subtitle="Add-on, pelatihan, atau biaya di luar langganan.">
                <form action="{{ route('superadmin.invoices.store') }}" method="POST" class="space-y-3">
                    @csrf
                    <input type="hidden" name="school_id" value="{{ $school->id }}">
                    <div><label class="{{ $label }}">Judul</label><input type="text" name="title" required maxlength="150" class="{{ $input }}"></div>
                    <div><label class="{{ $label }}">Nominal (Rp)</label><input type="number" name="amount" required min="1000" class="{{ $input }} font-mono"></div>
                    <div><label class="{{ $label }}">Jatuh tempo</label><input type="date" name="due_date" required value="{{ today()->addDays(14)->toDateString() }}" class="{{ $input }}"></div>
                    <div><label class="{{ $label }}">Catatan</label><input type="text" name="notes" class="{{ $input }}"></div>
                    <button type="submit" class="w-full px-4 py-2.5 rounded-xl text-xs font-extrabold bg-slate-900 text-white hover:bg-slate-800">Terbitkan</button>
                </form>
            </x-card>
        </div>
    @endif

    {{-- ============ PENGGUNA ============ --}}
    @if($tab === 'pengguna')
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
            <x-card class="xl:col-span-2" title="Pengelola Sekolah" subtitle="Admin sekolah dan kepala sekolah." :no-padding="true">
                <ul class="divide-y divide-slate-100">
                    @forelse($managers as $u)
                        <li class="px-5 py-3 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-slate-900 truncate">{{ $u->name }}</div>
                                <div class="text-[11px] text-slate-500 truncate">{{ $u->email }} · {{ $u->role === 'admin-sekolah' ? 'Admin Sekolah' : 'Kepala Sekolah' }}</div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                @if($u->must_change_password)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800">Sandi sementara</span>
                                @endif
                                @if(!$u->is_active)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500">Nonaktif</span>
                                @else
                                    <form action="{{ route('impersonate.start', $u) }}" method="POST">
                                        @csrf
                                        <button class="px-2.5 py-1 rounded-lg bg-slate-50 text-slate-700 border border-slate-200 text-[11px] font-bold">Masuk sebagai</button>
                                    </form>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-xs text-slate-400">Belum ada akun pengelola.</li>
                    @endforelse
                </ul>
            </x-card>

            <div class="space-y-4">
                <x-card title="Reset Sandi Admin Sekolah" subtitle="Admin wajib mengganti sandi saat login berikutnya.">
                    <form action="{{ route('superadmin.schools.reset-admin-password', $school) }}" method="POST" class="space-y-3"
                          onsubmit="return confirm('Reset kata sandi admin sekolah? Sesi aktifnya akan diputus.')">
                        @csrf
                        <input type="text" name="new_password" minlength="8" maxlength="100" autocomplete="off" placeholder="Kosongkan untuk sandi acak" class="{{ $input }} font-mono">
                        <button type="submit" class="w-full px-4 py-2.5 rounded-xl text-xs font-extrabold bg-slate-900 text-white hover:bg-slate-800">Reset Sandi</button>
                    </form>
                </x-card>
                <x-card title="Jumlah Akun per Peran">
                    <dl class="space-y-2 text-xs">
                        @foreach(['admin-sekolah' => 'Admin Sekolah', 'kepala-sekolah' => 'Kepala Sekolah', 'guru-pembimbing' => 'Guru Pembimbing', 'pembimbing-dudi' => 'Pembimbing DU/DI', 'siswa' => 'Siswa'] as $role => $text)
                            <div class="flex justify-between"><dt class="text-slate-500">{{ $text }}</dt><dd class="font-bold text-slate-900">{{ $roleCounts[$role] ?? 0 }}</dd></div>
                        @endforeach
                    </dl>
                </x-card>
            </div>
        </div>
    @endif

    {{-- ============ AKTIVITAS ============ --}}
    @if($tab === 'aktivitas')
        <x-card title="Jejak Aktivitas Sekolah" :no-padding="true">
            <ul class="divide-y divide-slate-100">
                @forelse($logs as $log)
                    <li class="px-5 py-3 flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="text-xs font-semibold text-slate-800">{{ $log->description }}</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">{{ $log->user?->name ?? 'Sistem' }} · {{ $log->module }} · {{ $log->action }}</div>
                        </div>
                        <time class="text-[11px] text-slate-400 whitespace-nowrap">{{ $log->created_at->translatedFormat('d M Y H:i') }}</time>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-xs text-slate-400">Belum ada aktivitas tercatat.</li>
                @endforelse
            </ul>
            @if($logs->hasPages())
                <div class="px-4 py-3 border-t border-slate-100">{{ $logs->links() }}</div>
            @endif
        </x-card>
    @endif

    {{-- ============ PENGATURAN ============ --}}
    @if($tab === 'pengaturan')
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <x-card title="Profil Sekolah">
                <form action="{{ route('superadmin.schools.update', $school) }}" method="POST" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div><label class="{{ $label }}">Nama sekolah</label><input type="text" name="name" required maxlength="150" value="{{ old('name', $school->name) }}" class="{{ $input }}"></div>
                        <div><label class="{{ $label }}">NPSN</label><input type="text" name="npsn" required inputmode="numeric" pattern="[0-9]{8}" maxlength="8" value="{{ old('npsn', $school->npsn) }}" class="{{ $input }} font-mono"></div>
                        <div><label class="{{ $label }}">Email</label><input type="email" name="email" required value="{{ old('email', $school->email) }}" class="{{ $input }}"></div>
                        <div><label class="{{ $label }}">Telepon</label><input type="text" name="phone" required maxlength="20" value="{{ old('phone', $school->phone) }}" class="{{ $input }}"></div>
                    </div>
                    <div><label class="{{ $label }}">Alamat</label><textarea name="address" rows="2" required class="{{ $input }}">{{ old('address', $school->address) }}</textarea></div>
                    <button type="submit" class="px-4 py-2.5 rounded-xl text-xs font-extrabold bg-slate-900 text-white hover:bg-slate-800">Simpan Profil</button>
                </form>
            </x-card>

            <x-card title="Kuota Storage Periode Berjalan">
                @if($subscription)
                    <form action="{{ route('superadmin.schools.update-storage-quota', $school) }}" method="POST" class="space-y-3">
                        @csrf
                        <div class="flex flex-wrap gap-2">
                            @foreach([512, 1024, 2048, 5120, 10240] as $preset)
                                <button type="button" onclick="this.form.storage_quota_mb.value={{ $preset }}" class="px-2.5 py-1 rounded-lg border border-slate-200 text-[11px] font-bold text-slate-600 hover:bg-slate-50">{{ $preset >= 1024 ? ($preset / 1024) . ' GB' : $preset . ' MB' }}</button>
                            @endforeach
                        </div>
                        <input type="number" name="storage_quota_mb" min="100" max="1048576" required value="{{ $subscription->storage_quota_mb }}" class="{{ $input }} font-mono">
                        <div class="flex gap-2">
                            <button type="submit" class="px-4 py-2.5 rounded-xl text-xs font-extrabold bg-slate-900 text-white hover:bg-slate-800">Simpan Kuota</button>
                        </div>
                    </form>
                    <form action="{{ route('superadmin.schools.recalculate-storage', $school) }}" method="POST" class="mt-3">
                        @csrf
                        <button type="submit" class="text-xs font-bold text-emerald-700 hover:underline">Hitung ulang pemakaian storage ({{ number_format($storageUsedMb, 1) }} MB)</button>
                    </form>
                @else
                    <p class="text-xs text-slate-500">Sekolah belum memiliki periode langganan.</p>
                @endif
            </x-card>

            <x-card title="Zona Bahaya" class="xl:col-span-2 border-rose-200">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="space-y-2">
                        <div class="text-xs font-bold text-slate-900">{{ $school->is_active ? 'Tangguhkan sekolah' : 'Aktifkan kembali' }}</div>
                        <p class="text-[11px] text-slate-500">{{ $school->is_active ? 'Seluruh pengguna sekolah langsung tidak bisa login.' : 'Pengguna sekolah dapat login kembali.' }}</p>
                        <form action="{{ route('superadmin.schools.toggle', $school) }}" method="POST" onsubmit="return confirm('{{ $school->is_active ? 'Tangguhkan' : 'Aktifkan kembali' }} sekolah ini?')">
                            @csrf
                            <button class="px-3.5 py-2 rounded-xl text-xs font-bold border {{ $school->is_active ? 'border-rose-200 text-rose-700 bg-rose-50 hover:bg-rose-100' : 'border-emerald-200 text-emerald-700 bg-emerald-50 hover:bg-emerald-100' }}">
                                {{ $school->is_active ? 'Tangguhkan' : 'Aktifkan Kembali' }}
                            </button>
                        </form>
                    </div>
                    <div class="space-y-2">
                        <div class="text-xs font-bold text-slate-900">Ekspor arsip data</div>
                        <p class="text-[11px] text-slate-500">ZIP berisi data siswa, presensi, jurnal, dan mitra untuk serah terima.</p>
                        <a href="{{ route('superadmin.schools.offboard-export', $school) }}" class="inline-block px-3.5 py-2 rounded-xl text-xs font-bold border border-slate-200 text-slate-700 bg-white hover:bg-slate-50">Unduh Arsip</a>
                    </div>
                    <div class="space-y-2">
                        <div class="text-xs font-bold text-slate-900">Hapus permanen</div>
                        <p class="text-[11px] text-slate-500">
                            @if($school->is_active)
                                Tangguhkan sekolah terlebih dahulu.
                            @elseif($studentsCount > 0)
                                Masih ada {{ $studentsCount }} siswa. Ekspor lalu hapus data siswa dahulu.
                            @else
                                Tidak dapat dibatalkan. Log audit tetap disimpan.
                            @endif
                        </p>
                        @if(!$school->is_active && $studentsCount === 0)
                            <form action="{{ route('superadmin.schools.destroy', $school) }}" method="POST" data-npsn="{{ $school->npsn }}" onsubmit="return confirmDestroySchool(this)">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="confirm_npsn" value="">
                                <button class="px-3.5 py-2 rounded-xl text-xs font-bold bg-rose-600 text-white hover:bg-rose-700">Hapus Sekolah</button>
                            </form>
                        @endif
                    </div>
                </div>
            </x-card>
        </div>
    @endif
</div>

<script>
    function confirmDestroySchool(form) {
        const npsn = form.dataset.npsn;
        const typed = prompt('Penghapusan bersifat PERMANEN.\nKetik NPSN sekolah (' + npsn + ') untuk konfirmasi:');
        if (typed === null) return false;
        if (typed.trim() !== npsn) { alert('NPSN tidak cocok. Penghapusan dibatalkan.'); return false; }
        form.querySelector('input[name="confirm_npsn"]').value = typed.trim();
        return true;
    }

    (function () {
        const plan = document.getElementById('renew-plan');
        const months = document.getElementById('renew-months');
        const price = document.getElementById('renew-price');
        if (!plan || !months || !price) return;
        let touched = false;
        price.addEventListener('input', () => { touched = true; });
        const sync = (fromPlan) => {
            const opt = plan.selectedOptions[0];
            if (!opt) return;
            if (fromPlan) months.value = String(opt.dataset.months);
            if (touched) return;
            const perMonth = Number(opt.dataset.price) / Number(opt.dataset.months || 12);
            price.value = Math.round(perMonth * Number(months.value));
        };
        plan.addEventListener('change', () => sync(true));
        months.addEventListener('change', () => sync(false));
        sync(true);
    })();
</script>
@endsection
