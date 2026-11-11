@props(['paginator'])

@if ($paginator->hasPages())
    <div {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-slate-100 text-xs']) }}>
        <!-- Information Summary -->
        <div class="text-[11px] text-slate-500 font-medium text-center sm:text-left">
            Menampilkan <span class="font-bold text-slate-800">{{ $paginator->firstItem() }}</span>–<span class="font-bold text-slate-800">{{ $paginator->lastItem() }}</span> dari <span class="font-bold text-slate-800">{{ $paginator->total() }}</span> data
        </div>

        <!-- Navigation Buttons -->
        <nav role="navigation" aria-label="Navigasi Halaman" class="flex items-center gap-1.5">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-300 font-bold text-xs cursor-not-allowed select-none">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                    <span>Prev</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 active:bg-slate-100 text-slate-700 font-bold text-xs shadow-2xs transition-all active:scale-95">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                    <span>Prev</span>
                </a>
            @endif

            {{-- Compact Page Numbers --}}
            <div class="flex items-center gap-1">
                @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 1), min($paginator->lastPage(), $paginator->currentPage() + 1)) as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="w-8 h-8 rounded-xl bg-indigo-600 text-white font-extrabold text-xs flex items-center justify-center shadow-xs shadow-indigo-600/30">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}" class="w-8 h-8 rounded-xl bg-white hover:bg-slate-50 active:bg-slate-100 border border-slate-200 text-slate-700 font-bold text-xs flex items-center justify-center shadow-2xs transition-all active:scale-95">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach
            </div>

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 active:bg-slate-100 text-slate-700 font-bold text-xs shadow-2xs transition-all active:scale-95">
                    <span>Next</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </a>
            @else
                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-300 font-bold text-xs cursor-not-allowed select-none">
                    <span>Next</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </span>
            @endif
        </nav>
    </div>
@endif
