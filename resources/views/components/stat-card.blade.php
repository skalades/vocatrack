@props([
    'title' => null,
    'label' => null,
    'value' => 0,
    'subtitle' => null,
    'description' => null,
    'color' => 'indigo',
    'badge' => null,
    'badgeVariant' => 'neutral',
])

@php
    $displayTitle = $title ?? $label ?? '';
    $displaySubtitle = $subtitle ?? $description ?? null;

    $colorMap = [
        'indigo' => [
            'bg' => 'bg-indigo-50/80',
            'text' => 'text-indigo-600',
            'border' => 'border-indigo-100',
            'glow' => 'group-hover:shadow-indigo-500/10',
        ],
        'emerald' => [
            'bg' => 'bg-emerald-50/80',
            'text' => 'text-emerald-600',
            'border' => 'border-emerald-100',
            'glow' => 'group-hover:shadow-emerald-500/10',
        ],
        'amber' => [
            'bg' => 'bg-amber-50/80',
            'text' => 'text-amber-600',
            'border' => 'border-amber-100',
            'glow' => 'group-hover:shadow-amber-500/10',
        ],
        'blue' => [
            'bg' => 'bg-blue-50/80',
            'text' => 'text-blue-600',
            'border' => 'border-blue-100',
            'glow' => 'group-hover:shadow-blue-500/10',
        ],
        'rose' => [
            'bg' => 'bg-rose-50/80',
            'text' => 'text-rose-600',
            'border' => 'border-rose-100',
            'glow' => 'group-hover:shadow-rose-500/10',
        ],
        'purple' => [
            'bg' => 'bg-purple-50/80',
            'text' => 'text-purple-600',
            'border' => 'border-purple-100',
            'glow' => 'group-hover:shadow-purple-500/10',
        ],
        'teal' => [
            'bg' => 'bg-teal-50/80',
            'text' => 'text-teal-600',
            'border' => 'border-teal-100',
            'glow' => 'group-hover:shadow-teal-500/10',
        ],
    ];

    $scheme = $colorMap[$color] ?? $colorMap['indigo'];
@endphp

<div {{ $attributes->merge(['class' => 'group relative overflow-hidden bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover-lift hover:border-slate-300 hover:shadow-lg transition-all duration-300']) }}>
    <div class="flex items-start justify-between gap-4">
        <div class="flex-1 min-w-0">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5 truncate">
                {{ $displayTitle }}
            </span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight stat-counter" data-target="{{ is_numeric($value) ? $value : '' }}">
                    {{ $value }}
                </span>
                @if($badge)
                    <x-badge :variant="$badgeVariant" size="xs">
                        {{ $badge }}
                    </x-badge>
                @endif
            </div>
            @if($displaySubtitle)
                <p class="text-xs text-slate-500 mt-1.5 font-medium truncate">
                    {{ $displaySubtitle }}
                </p>
            @endif
        </div>

        @if(isset($icon))
            <div class="w-12 h-12 rounded-xl {{ $scheme['bg'] }} {{ $scheme['text'] }} border {{ $scheme['border'] }} flex items-center justify-center shrink-0 shadow-2xs group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                {{ $icon }}
            </div>
        @endif
    </div>
</div>
