@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'icon' => null,
])

@php
    $variantClasses = match ($variant) {
        'primary' => 'bg-[#0B2B25] hover:bg-[#124239] active:bg-[#071E1A] text-[#C6F26B] font-extrabold shadow-xs shadow-[#0B2B25]/25 focus:ring-[#C6F26B]/50 border border-[#1D5A4D]',
        'lime' => 'bg-[#C6F26B] hover:bg-[#b5e855] active:bg-[#a2d844] text-[#0B2B25] font-extrabold shadow-xs shadow-black/15 focus:ring-[#0B2B25]/30',
        'forest' => 'bg-[#0B2B25] hover:bg-[#124239] active:bg-[#071E1A] text-white font-bold shadow-xs shadow-[#0B2B25]/25 focus:ring-[#0B2B25]/30',
        'secondary' => 'bg-white hover:bg-[#F1F4EC] active:bg-slate-100 text-[#0B2B25] border border-slate-200/90 shadow-2xs hover:border-[#0B2B25]/30 focus:ring-[#0B2B25]/15 font-bold',
        'danger' => 'bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white shadow-xs shadow-rose-600/20 focus:ring-rose-500/30',
        'danger-outline' => 'bg-white hover:bg-rose-50 text-rose-600 border border-rose-200 hover:border-rose-300 focus:ring-rose-500/20',
        'success' => 'bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white shadow-xs shadow-emerald-600/20 focus:ring-emerald-500/30',
        'ghost' => 'bg-transparent hover:bg-[#F1F4EC] active:bg-slate-200 text-[#0B2B25] hover:text-[#0B2B25] focus:ring-[#0B2B25]/20 font-bold',
        'outline' => 'bg-transparent hover:bg-[#EEF6DF] active:bg-[#E1EFC6] text-[#0B2B25] border border-[#0B2B25]/30 focus:ring-[#0B2B25]/20 font-bold',
        default => 'bg-[#0B2B25] hover:bg-[#124239] text-[#C6F26B] font-extrabold',
    };

    $sizeClasses = match ($size) {
        'xs' => 'text-[11px] font-semibold px-2.5 py-1 rounded-lg gap-1.5',
        'sm' => 'text-xs font-semibold px-3 py-1.5 rounded-xl gap-1.5',
        'lg' => 'text-sm font-bold px-5 py-2.5 rounded-2xl gap-2.5',
        default => 'text-xs font-bold px-4 py-2 rounded-xl gap-2',
    };
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => "group inline-flex items-center justify-center font-sans tracking-tight transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.97] focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:opacity-50 disabled:pointer-events-none cursor-pointer {$variantClasses} {$sizeClasses}"]) }}
>
    @if(isset($icon))
        <span class="shrink-0 transition-transform duration-200 group-hover:scale-105">{{ $icon }}</span>
    @endif
    {{ $slot }}
</button>
