@props([
    'title',
    'subtitle' => null,
    'badge' => null,
    'badgeVariant' => 'indigo',
    'context' => null,
    'showClock' => true,
    'icon' => null,
])

@php
    $user = auth()->user();
    $roleName = match($user?->role ?? '') {
        'admin-sekolah' => 'ADMIN SEKOLAH',
        'guru-pembimbing' => 'GURU PEMBIMBING',
        'pembimbing-dudi' => 'PEMBIMBING INDUSTRI DU/DI',
        'kepala-sekolah' => 'KEPALA SEKOLAH',
        'super-admin' => 'SUPER ADMIN PLATFORM',
        'siswa' => 'SISWA PKL',
        default => strtoupper(str_replace('-', ' ', $user?->role ?? 'VOKATRACK'))
    };

    $displayBadge = $badge ?? $roleName;

    $displayContext = $context ?? (
        $user?->school?->name 
        ?? ($user?->supervisor?->company?->name 
        ?? ($user?->role === 'super-admin' ? 'VokaTrack Enterprise Cloud' : 'SMK Terpadu'))
    );
@endphp

<div {{ $attributes->merge(['class' => 'relative overflow-hidden bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-7 text-white shadow-xl shadow-slate-950/20 border border-slate-800 mb-6']) }}>
    <!-- Radial Glow Light Orb -->
    <div class="absolute -right-10 -bottom-10 w-64 h-64 rounded-full bg-indigo-500/15 blur-3xl pointer-events-none"></div>

    <div class="relative space-y-4">
        <!-- Top Meta Bar: Role / Module Badge + Context / School / Company + Synchronized Live Clock & Date -->
        <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-white/10">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-500/20 text-indigo-300 border border-indigo-400/30 uppercase tracking-wider">
                    {{ $displayBadge }}
                </span>
                <span class="text-xs text-slate-500">&bull;</span>
                <span class="text-xs font-semibold text-slate-300 truncate max-w-sm">{{ $displayContext }}</span>
            </div>

            @if($showClock)
                <!-- Synchronized Live Date & Clock Widget -->
                <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 text-xs text-slate-200 shadow-inner">
                    <div class="flex items-center gap-1.5 font-semibold text-slate-200">
                        <svg class="w-3.5 h-3.5 text-indigo-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span class="live-clock-date">{{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}</span>
                    </div>
                    <span class="text-white/30">&bull;</span>
                    <div class="flex items-center gap-1.5 font-mono font-bold text-amber-300">
                        <svg class="w-3.5 h-3.5 text-amber-300 shrink-0 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="live-clock-time">{{ \Carbon\Carbon::now()->format('H:i:s') }} WIB</span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Main Title & Action Buttons -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div class="space-y-1.5 min-w-0">
                <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white flex items-center gap-2.5">
                    @if($icon)
                        <span class="shrink-0">{!! $icon !!}</span>
                    @endif
                    <span>{{ $title }}</span>
                </h1>
                @if($subtitle)
                    <p class="text-xs sm:text-sm text-slate-300 font-medium max-w-3xl leading-relaxed">
                        {{ $subtitle }}
                    </p>
                @endif
            </div>

            @if(trim($slot))
                <!-- Header Quick Actions Slot -->
                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    {{ $slot }}
                </div>
            @endif
        </div>
    </div>
</div>
