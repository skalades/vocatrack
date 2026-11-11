@props([
    'title' => null,
    'subtitle' => null,
    'headerActions' => null,
    'footer' => null,
    'noPadding' => false,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden transition-all duration-200']) }}>
    @if($title || $headerActions || isset($header))
        <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            @if(isset($header))
                {{ $header }}
            @else
                <div>
                    @if($title)
                        <h3 class="text-sm font-bold text-slate-900 tracking-tight">{{ $title }}</h3>
                    @endif
                    @if($subtitle)
                        <p class="text-xs text-slate-500 mt-0.5 font-medium">{{ $subtitle }}</p>
                    @endif
                </div>
                @if($headerActions)
                    <div class="flex items-center gap-2">
                        {{ $headerActions }}
                    </div>
                @endif
            @endif
        </div>
    @endif

    <div class="{{ $noPadding ? '' : 'p-5' }}">
        {{ $slot }}
    </div>

    @if($footer || isset($cardFooter))
        <div class="px-5 py-3.5 bg-slate-50/70 border-t border-slate-100 text-xs text-slate-500">
            {{ $footer ?? $cardFooter }}
        </div>
    @endif
</div>
