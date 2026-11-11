@props([
    'title' => 'Tidak Ada Data',
    'description' => 'Belum ada data yang tersedia untuk ditampilkan.',
    'actionLabel' => null,
    'actionUrl' => null,
    'actionClick' => null,
])

<div {{ $attributes->merge(['class' => 'py-12 px-4 text-center flex flex-col items-center justify-center']) }}>
    <div class="w-14 h-14 rounded-2xl bg-slate-100/80 border border-slate-200/80 flex items-center justify-center text-slate-400 mb-3.5 shadow-2xs">
        @if(isset($icon))
            {{ $icon }}
        @else
            <svg class="w-6 h-6 stroke-[1.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
        @endif
    </div>

    <h4 class="text-sm font-bold text-slate-800 tracking-tight mb-1">{{ $title }}</h4>
    <p class="text-xs text-slate-500 max-w-sm font-medium mb-4">{{ $description }}</p>

    @if($actionLabel)
        @if($actionUrl)
            <a href="{{ $actionUrl }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-xs shadow-indigo-600/20 transition-all active:scale-95">
                {{ $actionLabel }}
            </a>
        @elseif($actionClick)
            <button type="button" onclick="{{ $actionClick }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-xs shadow-indigo-600/20 transition-all active:scale-95">
                {{ $actionLabel }}
            </button>
        @endif
    @endif
</div>
