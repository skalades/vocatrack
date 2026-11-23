@php
    $saPendingRequests = \App\Models\SchoolRequest::where('status', 'pending')->count();
    $saOpenInvoices = \App\Models\Invoice::whereIn('status', ['unpaid', 'pending_verification'])->count();
    $saGroups = [
        null => [
            ['superadmin.dashboard', 'superadmin.dashboard', 'Ringkasan', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 0],
        ],
        'Pelanggan' => [
            ['superadmin.schools', 'superadmin.schools*', 'Sekolah', 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 0],
            ['superadmin.school-requests', 'superadmin.school-requests*', 'Permohonan Demo', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', $saPendingRequests],
        ],
        'Keuangan' => [
            ['superadmin.invoices', 'superadmin.invoices*', 'Tagihan', 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8h4', $saOpenInvoices],
            ['superadmin.plans', 'superadmin.plans*', 'Paket Langganan', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 0],
        ],
        'Platform' => [
            ['superadmin.broadcasts', 'superadmin.broadcasts*', 'Pengumuman', 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z', 0],
            ['superadmin.audit-logs', 'superadmin.audit-logs*', 'Audit Log', 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 0],
            ['superadmin.backups', 'superadmin.backups*', 'Backup Data', 'M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2', 0],
            ['superadmin.system-health', 'superadmin.system-health*', 'Kesehatan Sistem', 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 0],
            ['superadmin.whatsapp', 'superadmin.whatsapp*', 'WhatsApp Gateway', 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', 0],
            ['superadmin.settings', 'superadmin.settings*', 'Pengaturan', 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4', 0],
        ],
    ];
@endphp

@foreach($saGroups as $groupName => $items)
    @if($groupName)
        <div class="text-[10px] font-extrabold text-[#A9C2B9] uppercase tracking-widest px-3 pt-4 pb-1.5">{{ $groupName }}</div>
    @endif
    @foreach($items as [$routeName, $pattern, $text, $iconPath, $badgeCount])
        <a href="{{ route($routeName) }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs($pattern) ? $activeClass : $inactiveClass }}">
            <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"/></svg>
            <span class="flex-1">{{ $text }}</span>
            @if($badgeCount > 0)
                <span class="min-w-[20px] h-5 px-1.5 rounded-full bg-[#C6F26B] text-[#0B2B25] text-[10px] font-extrabold flex items-center justify-center">{{ $badgeCount }}</span>
            @endif
        </a>
    @endforeach
@endforeach
