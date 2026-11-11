@extends('layouts.student-mobile')

@section('title', 'Papan Pengumuman — VokaTrack Siswa')

@section('content')
<div class="space-y-4">

    <!-- Header Navigation Back & Title -->
    <div class="flex items-center justify-between pb-1">
        <a href="{{ route('student.home') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-indigo-600 transition-colors">
            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            <span>Kembali ke Beranda</span>
        </a>
        <span class="text-[11px] font-extrabold text-indigo-600 bg-indigo-50 px-2.5 py-0.5 rounded-full border border-indigo-200/80">
            {{ $announcements->total() }} Pengumuman
        </span>
    </div>

    <!-- Hero Title Banner -->
    <div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-indigo-700 to-indigo-800 rounded-3xl p-5 text-white shadow-lg shadow-indigo-600/20">
        <div class="relative z-10">
            <span class="text-[10px] font-bold text-indigo-200 uppercase tracking-widest block">Informasi & Siaran Resmi</span>
            <h2 class="text-lg font-black tracking-tight mt-0.5">Papan Pengumuman PKL</h2>
            <p class="text-xs text-indigo-100 font-medium mt-1 leading-relaxed">
                Pengumuman dan instruksi resmi dari pihak sekolah serta guru pembimbing khusus untuk Anda.
            </p>
        </div>
        <div class="absolute -right-4 -bottom-6 w-28 h-28 rounded-full bg-white/10 pointer-events-none"></div>
    </div>

    <!-- Search Form -->
    <form method="GET" action="{{ route('student.announcements') }}" class="relative">
        @if(request('category') && request('category') !== 'all')
            <input type="hidden" name="category" value="{{ request('category') }}">
        @endif
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Cari kata kunci pengumuman..."
               class="w-full text-xs pl-10 pr-4 py-3 rounded-2xl bg-white border border-slate-200/80 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 shadow-2xs font-medium text-slate-800 placeholder:text-slate-400">
        <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        @if(request('search'))
            <a href="{{ route('student.announcements', ['category' => request('category')]) }}" class="absolute right-3 top-3 text-slate-400 hover:text-slate-600 text-xs font-bold p-0.5">
                ✕
            </a>
        @endif
    </form>

    <!-- Category Filter Pills -->
    <div class="overflow-x-auto pb-1 -mx-4 px-4 custom-scrollbar">
        <div class="flex items-center gap-1.5 w-max">
            @php
                $cats = [
                    'all' => ['label' => 'Semua', 'count' => $categoryCounts['all']],
                    'penting' => ['label' => 'Penting', 'count' => $categoryCounts['penting']],
                    'kegiatan' => ['label' => 'Kegiatan PKL', 'count' => $categoryCounts['kegiatan']],
                    'akademik' => ['label' => 'Akademik', 'count' => $categoryCounts['akademik']],
                    'libur' => ['label' => 'Jadwal / Libur', 'count' => $categoryCounts['libur']],
                    'umum' => ['label' => 'Umum', 'count' => $categoryCounts['umum']],
                ];
                $activeCat = request('category', 'all');
            @endphp

            @foreach($cats as $key => $cat)
                <a href="{{ route('student.announcements', array_merge(request()->query(), ['category' => $key, 'page' => 1])) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ $activeCat === $key ? 'bg-indigo-600 text-white shadow-xs shadow-indigo-600/30' : 'bg-white text-slate-600 border border-slate-200/80 hover:bg-slate-50' }}">
                    <span>{{ $cat['label'] }}</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded-full font-mono {{ $activeCat === $key ? 'bg-indigo-700/80 text-white' : 'bg-slate-100 text-slate-600' }}">
                        {{ $cat['count'] }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Announcement List -->
    @if($announcements->count() > 0)
        <div class="space-y-3.5">
            @foreach($announcements as $ann)
                <article onclick="openDetailModal({{ $ann->id }})" class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden cursor-pointer hover:border-indigo-300 hover:shadow-md transition-all active:scale-[0.99] group">
                    
                    @if($ann->image_path)
                        <div class="relative aspect-16/9 w-full bg-slate-100 overflow-hidden">
                            <img src="{{ $ann->image_url }}" alt="{{ $ann->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                            @if($ann->is_pinned)
                                <div class="absolute top-3 left-3 px-2 py-0.5 rounded-full bg-amber-500/90 backdrop-blur-xs text-white text-[10px] font-extrabold flex items-center gap-1 shadow-sm">
                                    <svg class="w-2.5 h-2.5 fill-current" viewBox="0 0 24 24"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg>
                                    <span>Pinned</span>
                                </div>
                            @endif
                            <div class="absolute bottom-2 right-2">
                                <x-badge :variant="$ann->category_badge_variant" size="xs">
                                    {{ strtoupper($ann->category_label) }}
                                </x-badge>
                            </div>
                        </div>
                    @endif

                    <div class="p-4 space-y-2.5">
                        <div class="flex items-center justify-between gap-2">
                            @if(!$ann->image_path)
                                <div class="flex items-center gap-1.5">
                                    @if($ann->is_pinned)
                                        <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-extrabold flex items-center gap-1 border border-amber-200">
                                            <svg class="w-2.5 h-2.5 fill-current" viewBox="0 0 24 24"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg>
                                            <span>Pinned</span>
                                        </span>
                                    @endif
                                    <x-badge :variant="$ann->category_badge_variant" size="xs">
                                        {{ strtoupper($ann->category_label) }}
                                    </x-badge>
                                </div>
                            @else
                                <div></div>
                            @endif

                            @if($ann->major)
                                <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-200/80 px-2 py-0.5 rounded-full truncate max-w-[170px]" title="Khusus {{ $ann->major->name }}">
                                    {{ $ann->major->name }}
                                </span>
                            @else
                                <span class="text-[10px] font-medium text-slate-400">
                                    Semua Jurusan
                                </span>
                            @endif
                        </div>

                        <h3 class="text-sm font-extrabold text-slate-900 group-hover:text-indigo-600 transition-colors leading-snug line-clamp-2">
                            {{ $ann->title }}
                        </h3>

                        <p class="text-xs text-slate-600 line-clamp-2 font-medium leading-relaxed">
                            {{ Str::limit(strip_tags($ann->content), 120) }}
                        </p>

                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                            <div class="flex items-center gap-2 min-w-0">
                                <div class="w-5 h-5 rounded-lg bg-indigo-100 text-indigo-700 font-black text-[10px] flex items-center justify-center shrink-0">
                                    {{ substr($ann->author->name ?? 'S', 0, 1) }}
                                </div>
                                <span class="font-bold text-slate-800 truncate">{{ $ann->author->name ?? 'Pihak Sekolah' }}</span>
                            </div>
                            <span class="font-medium shrink-0 text-slate-400">
                                {{ $ann->published_at ? $ann->published_at->diffForHumans() : $ann->created_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="pt-2">
            {{ $announcements->links() }}
        </div>
    @else
        <div class="bg-white rounded-3xl p-8 border border-slate-200/80 text-center shadow-xs space-y-3">
            <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center mx-auto">
                <svg class="w-7 h-7 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                </svg>
            </div>
            <div>
                <h4 class="text-sm font-extrabold text-slate-900">Belum Ada Pengumuman</h4>
                <p class="text-xs text-slate-500 font-medium mt-1 leading-relaxed max-w-xs mx-auto">
                    Saat ini belum ada siaran pengumuman aktif untuk kategori atau pencarian yang dipilih.
                </p>
            </div>
            @if(request('search') || request('category'))
                <a href="{{ route('student.announcements') }}" class="inline-flex items-center text-xs font-bold text-indigo-600 hover:text-indigo-700">
                    Reset Filter Pencarian
                </a>
            @endif
        </div>
    @endif

</div>

<!-- Modal Detail Pengumuman -->
<div id="announcement-modal" onclick="if(event.target === this) closeDetailModal()" class="hidden fixed inset-0 z-50 bg-slate-950/75 backdrop-blur-md flex items-center justify-center p-3.5 sm:p-4 animate-in fade-in duration-200">
    <div class="bg-white w-full max-w-sm sm:max-w-md rounded-3xl shadow-2xl max-h-[86vh] sm:max-h-[90vh] flex flex-col border border-slate-100 overflow-hidden my-auto transform animate-in zoom-in-95 duration-200">
        
        <!-- Modal Sticky Header -->
        <div class="px-4 py-3 sm:px-5 sm:py-3.5 border-b border-slate-100 flex items-center justify-between gap-2 shrink-0 bg-white">
            <div class="flex items-center gap-1.5 flex-wrap min-w-0 flex-1">
                <span id="modal-category-badge"></span>
                <span id="modal-major-badge" class="text-[10px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-200/80 px-2.5 py-0.5 rounded-full truncate max-w-[130px] sm:max-w-[180px]"></span>
            </div>
            <button type="button" onclick="closeDetailModal()" title="Tutup" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 active:bg-slate-300 text-slate-500 hover:text-slate-800 flex items-center justify-center shrink-0 transition-colors cursor-pointer">
                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="overflow-y-auto px-4 py-4 sm:px-5 sm:py-4 space-y-3.5 custom-scrollbar flex-1">
            <div id="modal-image-container" class="hidden relative group/modalimg rounded-2xl overflow-hidden bg-slate-100 border border-slate-200/80 shadow-2xs cursor-zoom-in" onclick="openImageLightbox(document.getElementById('modal-image').src)" title="Klik untuk membaca tulisan pada gambar">
                <img id="modal-image" src="" alt="Lampiran Pengumuman" class="w-full h-auto max-h-48 sm:max-h-60 object-contain mx-auto block group-hover/modalimg:scale-105 transition-transform duration-300">
                
                <!-- Hover Overlay -->
                <div class="absolute inset-0 bg-slate-950/25 opacity-0 group-hover/modalimg:opacity-100 transition-opacity flex items-center justify-center gap-1.5 text-white text-xs font-black pointer-events-none">
                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                    <span>Klik untuk Memperbesar Gambar</span>
                </div>

                <!-- Floating Mobile Pill Button -->
                <div class="absolute bottom-2.5 right-2.5 px-2.5 py-1 rounded-xl bg-slate-950/80 backdrop-blur-md text-white text-[10px] font-black flex items-center gap-1.5 shadow-md pointer-events-none">
                    <svg class="w-3.5 h-3.5 text-indigo-300 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                    <span>Ketuk untuk Zoom</span>
                </div>
            </div>

            <div class="space-y-1.5">
                <h3 id="modal-title" class="text-base sm:text-lg font-black text-slate-900 tracking-tight leading-snug break-words"></h3>
                
                <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-100 text-[11px] text-slate-500">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 rounded-xl bg-white p-0.5 text-white font-black text-xs flex items-center justify-center shrink-0 shadow-xs border border-slate-200">
                            <img src="{{ asset('images/logo.png') }}" alt="VokaTrack" class="w-full h-full object-contain">
                        </div>
                        <div class="min-w-0">
                            <span id="modal-author" class="font-bold text-slate-900 text-xs truncate block max-w-[150px] sm:max-w-[180px]"></span>
                            <span id="modal-target" class="text-[10px] text-slate-400 font-medium truncate block"></span>
                        </div>
                    </div>
                    <span id="modal-date" class="font-medium text-[10px] text-slate-400 shrink-0 text-right font-mono"></span>
                </div>
            </div>

            <div class="bg-slate-50/90 rounded-2xl p-4 border border-slate-100">
                <p id="modal-content" class="text-xs text-slate-700 whitespace-pre-line leading-relaxed font-medium break-words"></p>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-3.5 sm:p-4 bg-white border-t border-slate-100 shrink-0 flex flex-col gap-2">
            <button type="button" onclick="closeDetailModal()" class="w-full min-h-[46px] py-3 px-4 rounded-2xl bg-slate-900 hover:bg-slate-800 active:scale-[0.98] text-white text-xs font-black tracking-wide shadow-md shadow-slate-900/10 flex items-center justify-center transition-all cursor-pointer">
                Tutup Pengumuman
            </button>
        </div>
    </div>
</div>

<script>
    function openDetailModal(id) {
        fetch(`/siswa/pengumuman/${id}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('modal-title').textContent = data.title;
            document.getElementById('modal-content').textContent = data.content;
            document.getElementById('modal-author').textContent = data.author_name;
            document.getElementById('modal-target').textContent = data.target_scope;
            document.getElementById('modal-date').textContent = data.published_at;
            document.getElementById('modal-major-badge').textContent = data.major_name;

            const badgeSpan = document.getElementById('modal-category-badge');
            badgeSpan.className = `text-[10px] font-extrabold uppercase px-2.5 py-0.5 rounded-full border ${getCategoryClasses(data.category)}`;
            badgeSpan.textContent = data.category_label;

            const imgContainer = document.getElementById('modal-image-container');
            const imgElem = document.getElementById('modal-image');
            if (data.image_url) {
                imgElem.src = data.image_url;
                imgContainer.classList.remove('hidden');
            } else {
                imgContainer.classList.add('hidden');
                imgElem.src = '';
            }

            document.getElementById('announcement-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        })
        .catch(err => {
            console.error('Failed to load announcement details', err);
        });
    }

    function closeDetailModal() {
        document.getElementById('announcement-modal').classList.add('hidden');
        document.body.style.overflow = '';
    }

    function getCategoryClasses(cat) {
        switch(cat) {
            case 'penting': return 'bg-rose-50 text-rose-700 border-rose-200/80';
            case 'kegiatan': return 'bg-indigo-50 text-indigo-700 border-indigo-200/80';
            case 'akademik': return 'bg-teal-50 text-teal-700 border-teal-200/80';
            case 'libur': return 'bg-purple-50 text-purple-700 border-purple-200/80';
            case 'dudi': return 'bg-amber-50 text-amber-700 border-amber-200/80';
            default: return 'bg-sky-50 text-sky-700 border-sky-200/80';
        }
    }
</script>
@endsection
