@props([
    'variant' => 'neutral',
    'dot' => true,
    'size' => 'md',
    'pulse' => null,
])

@php
    $normalized = strtolower(str_replace([' ', '-'], '_', $variant));
    $shouldPulse = $pulse ?? in_array($normalized, ['pending', 'menunggu', 'proses', 'aktif', 'active', 'hadir']);

    $colorClasses = match ($normalized) {
        'forest' => 'bg-[#0B2B25] text-[#C6F26B] border-[#1D5A4D]',
        'lime' => 'bg-[#EEF6DF] text-[#0B2B25] border-[#CDE4A3]',
        'success', 'hadir', 'approved', 'disetujui', 'aktif', 'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200/80',
        'warning', 'terlambat', 'pending', 'menunggu', 'review', 'proses', 'submitted', 'revision' => 'bg-amber-50 text-amber-700 border-amber-200/80',
        'danger', 'alfa', 'rejected', 'ditolak', 'nonaktif', 'inactive' => 'bg-rose-50 text-rose-700 border-rose-200/80',
        'info', 'izin', 'review_guru' => 'bg-sky-50 text-sky-700 border-sky-200/80',
        'purple', 'sakit' => 'bg-purple-50 text-purple-700 border-purple-200/80',
        'teal', 'dinas_luar', 'wfh' => 'bg-teal-50 text-teal-700 border-teal-200/80',
        'indigo', 'luar_kantor' => 'bg-indigo-50 text-indigo-700 border-indigo-200/80',
        default => 'bg-[#F1F4EC] text-[#0B2B25] border-slate-200/80',
    };

    $dotColors = match ($normalized) {
        'forest' => 'bg-[#C6F26B]',
        'lime' => 'bg-[#0B2B25]',
        'success', 'hadir', 'approved', 'disetujui', 'aktif', 'active' => 'bg-emerald-500',
        'warning', 'terlambat', 'pending', 'menunggu', 'review', 'proses', 'submitted', 'revision' => 'bg-amber-500',
        'danger', 'alfa', 'rejected', 'ditolak', 'nonaktif', 'inactive' => 'bg-rose-500',
        'info', 'izin', 'review_guru' => 'bg-sky-500',
        'purple', 'sakit' => 'bg-purple-500',
        'teal', 'dinas_luar', 'wfh' => 'bg-teal-500',
        'indigo', 'luar_kantor' => 'bg-indigo-500',
        default => 'bg-slate-400',
    };

    $sizeClasses = match ($size) {
        'xs' => 'text-[10px] px-2 py-0.5 font-semibold',
        'sm' => 'text-[11px] px-2.5 py-0.5 font-bold',
        'lg' => 'text-xs px-3 py-1 font-bold',
        default => 'text-[11px] px-2.5 py-0.5 font-semibold',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full border shadow-2xs transition-all duration-200 {$colorClasses} {$sizeClasses}"]) }}>
    @if($dot)
        <span class="relative flex h-1.5 w-1.5 shrink-0">
            @if($shouldPulse)
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $dotColors }} opacity-75"></span>
            @endif
            <span class="relative inline-flex rounded-full h-1.5 w-1.5 {{ $dotColors }}"></span>
        </span>
    @endif
    <span>{{ $slot }}</span>
</span>
