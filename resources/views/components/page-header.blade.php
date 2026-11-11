@props([
    'title',
    'subtitle' => null,
    'badge' => null,
    'badgeVariant' => 'indigo',
])

<div {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-1 mb-6']) }}>
    <div>
        <div class="flex items-center gap-2.5">
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                {{ $title }}
            </h1>
            @if($badge)
                <x-badge :variant="$badgeVariant" size="xs">
                    {{ $badge }}
                </x-badge>
            @endif
        </div>
        @if($subtitle)
            <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">
                {{ $subtitle }}
            </p>
        @endif
    </div>

    @if(trim($slot))
        <div class="flex flex-wrap items-center gap-2.5">
            {{ $slot }}
        </div>
    @endif
</div>
