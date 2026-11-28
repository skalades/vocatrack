@props([
    'variant' => 'desktop',
])

@php
    $user = auth()->user();
    $showSwitcher = $user && ($user->isKajur() || $user->isWaliKelas());
@endphp

@if($showSwitcher)
    @php
        $bimbinganCount = $user->teacher?->studentPkls()->where('status', 'active')->count() ?? 0;
        $isMobile = $variant === 'mobile';
        $wrapperClass = $isMobile
            ? 'flex items-center gap-1 p-1 rounded-2xl bg-slate-100 border border-slate-200/80 overflow-x-auto'
            : 'hidden sm:inline-flex items-center gap-0.5 p-0.5 rounded-xl bg-slate-100 border border-slate-200';
        $baseItemClass = $isMobile
            ? 'flex-1 min-w-0 inline-flex items-center justify-center gap-1.5 px-2.5 py-1.5 text-[11px] rounded-xl whitespace-nowrap transition-all'
            : 'inline-flex items-center gap-1.5 px-2.5 py-1 text-xs rounded-lg whitespace-nowrap transition-all';
    @endphp

    <div class="{{ $wrapperClass }}">
        @if($user->isKajur())
            <a href="{{ route('kajur.dashboard') }}"
               class="{{ $baseItemClass }} {{ request()->is('kajur*') ? 'bg-white text-emerald-800 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium' }}">
                <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                <span class="truncate">Kajur {{ $user->headedMajor()?->code }}</span>
            </a>
        @endif

        @if($user->isWaliKelas())
            <a href="{{ route('walikelas.dashboard') }}"
               class="{{ $baseItemClass }} {{ request()->is('wali-kelas*') ? 'bg-white text-teal-800 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium' }}">
                <span class="w-2 h-2 rounded-full bg-teal-500 shrink-0"></span>
                <span class="truncate">Wali Kelas {{ $user->primaryHomeroomClass()?->name }}</span>
            </a>
        @endif

        <a href="{{ route('guru.dashboard') }}"
           class="{{ $baseItemClass }} {{ request()->is('guru*') ? 'bg-white text-indigo-800 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium' }}">
            <span class="w-2 h-2 rounded-full bg-indigo-500 shrink-0"></span>
            <span class="truncate">Bimbingan ({{ $bimbinganCount }})</span>
        </a>
    </div>
@endif
