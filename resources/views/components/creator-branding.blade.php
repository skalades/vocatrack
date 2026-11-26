@props([
    'variant' => 'light',
    'url' => 'https://nadirlabs.net',
    'label' => 'Aplikasi ini dibuat oleh',
    'brand' => 'Nadir Labs',
    'logo' => null,
])

@php
    $isDark = $variant === 'dark';
    $logoSrc = $logo ?? asset('images/nadir-logo.png');
    $baseClass = $isDark
        ? 'flex flex-wrap items-center gap-1.5 text-[11px] font-medium text-current'
        : 'flex flex-wrap items-center gap-1.5 text-[11px] font-medium text-slate-500';
    $badgeClass = $isDark
        ? 'group inline-flex items-center px-2 py-1 rounded-lg bg-white/10 hover:bg-white/15 border border-white/10 transition-colors focus:outline-none focus:ring-2 focus:ring-[#C6F26B]/60'
        : 'group inline-flex items-center px-2 py-1 rounded-lg bg-[#0B2B25] hover:bg-[#12382F] shadow-2xs transition-colors focus:outline-none focus:ring-2 focus:ring-[#0B2B25]/30';
@endphp

<div {{ $attributes->merge(['class' => $baseClass]) }}>
    <span>{{ $label }}</span>
    <a href="{{ $url }}"
       target="_blank"
       rel="noopener noreferrer"
       aria-label="{{ $label }} {{ $brand }}"
       title="{{ $brand }} — {{ $url }}"
       class="{{ $badgeClass }}">
        <img src="{{ $logoSrc }}"
             alt="{{ $brand }}"
             class="h-3 w-auto object-contain opacity-95 group-hover:opacity-100 transition-opacity">
    </a>
</div>
