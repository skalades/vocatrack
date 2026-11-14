@props([
    'id',
    'title' => '',
    'subtitle' => null,
    'maxWidth' => 'max-w-lg',
])

<div id="{{ $id }}" 
     class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 transition-all duration-200" 
     onclick="if(event.target === this) this.classList.add('hidden')"
     role="dialog" 
     aria-modal="true">
    <div class="bg-white rounded-2xl {{ $maxWidth }} w-full shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in-95 duration-200 my-8 max-h-[90vh] flex flex-col">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800 shrink-0">
            <div>
                <h3 class="text-sm sm:text-base font-bold tracking-tight text-white">{{ $title }}</h3>
                @if($subtitle)
                    <p class="text-xs text-slate-400 mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>
            <button type="button" 
                    onclick="document.getElementById('{{ $id }}').classList.add('hidden')" 
                    class="p-1.5 text-slate-400 hover:text-white rounded-xl hover:bg-slate-800 transition-colors cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Modal Content Scrollable -->
        <div class="p-6 overflow-y-auto flex-1 custom-scrollbar">
            {{ $slot }}
        </div>
    </div>
</div>
