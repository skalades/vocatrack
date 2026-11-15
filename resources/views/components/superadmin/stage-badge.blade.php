@props(['stage', 'size' => 'md'])

@php
    $meta = \App\Services\TenantLifecycleService::stageMeta($stage);
    $classes = match ($meta['color']) {
        'sky' => 'bg-sky-50 text-sky-700 border-sky-200',
        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'amber' => 'bg-amber-50 text-amber-800 border-amber-200',
        'orange' => 'bg-orange-50 text-orange-700 border-orange-200',
        'rose' => 'bg-rose-50 text-rose-700 border-rose-200',
        default => 'bg-slate-100 text-slate-700 border-slate-200',
    };
    $dot = match ($meta['color']) {
        'sky' => 'bg-sky-500',
        'emerald' => 'bg-emerald-500',
        'amber' => 'bg-amber-500',
        'orange' => 'bg-orange-500',
        'rose' => 'bg-rose-500',
        default => 'bg-slate-400',
    };
    $sizeClasses = $size === 'sm' ? 'text-[10px] px-2 py-0.5' : 'text-xs px-2.5 py-1';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full border font-bold whitespace-nowrap {$classes} {$sizeClasses}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $dot }}"></span>
    {{ $meta['label'] }}
</span>
