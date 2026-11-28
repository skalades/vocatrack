<div class="text-[10px] font-extrabold text-[#A9C2B9] uppercase tracking-widest px-3 pt-2 pb-1.5 flex items-center justify-between">
    <span>Pimpinan Sekolah</span>
    <span class="w-1.5 h-1.5 rounded-full bg-[#C6F26B]"></span>
</div>
<a href="{{ route('kepsek.dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('kepsek.dashboard') ? $activeClass : $inactiveClass }}">
    <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
    <span>Analitik Agregat PKL</span>
</a>
<a href="{{ route('kepsek.rekap') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('kepsek.rekap*') ? $activeClass : $inactiveClass }}">
    <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    <span>Rekapitulasi Kehadiran</span>
</a>
<a href="{{ route('kepsek.map') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('kepsek.map*') ? $activeClass : $inactiveClass }}">
    <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
    <span>Peta Sebaran PKL</span>
</a>
<a href="{{ route('kepsek.announcements') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs transition-colors {{ request()->routeIs('kepsek.announcements*') ? $activeClass : $inactiveClass }}">
    <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
    <span>Pengumuman</span>
</a>
