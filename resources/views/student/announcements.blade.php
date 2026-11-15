@extends('layouts.student-mobile')

@section('title', 'Papan Pengumuman — VokaTrack Siswa')

@php
    // Palet kategori selaras tema VokaTrack: [teks, latar, titik]
    $catStyle = [
        'penting'  => ['text-[#B42318]', 'bg-[#FBE4E1]', 'bg-[#B42318]'],
        'kegiatan' => ['text-[#0B5E4E]', 'bg-[#DDF3EC]', 'bg-[#0F7A66]'],
        'akademik' => ['text-[#1D4E89]', 'bg-[#E3EEFA]', 'bg-[#2E6BB8]'],
        'libur'    => ['text-[#6B3FA0]', 'bg-[#EFE6FA]', 'bg-[#8A5CC7]'],
        'dudi'     => ['text-[#7A4B00]', 'bg-[#FFF1D6]', 'bg-[#E0A21B]'],
        'umum'     => ['text-[#36504A]', 'bg-[#EEF3F1]', 'bg-[#5A6B65]'],
    ];
    $cats = [
        'all'      => ['label' => 'Semua',          'count' => $categoryCounts['all']],
        'penting'  => ['label' => 'Penting',        'count' => $categoryCounts['penting']],
        'kegiatan' => ['label' => 'Kegiatan PKL',   'count' => $categoryCounts['kegiatan']],
        'akademik' => ['label' => 'Akademik',       'count' => $categoryCounts['akademik']],
        'libur'    => ['label' => 'Jadwal / Libur', 'count' => $categoryCounts['libur']],
        'umum'     => ['label' => 'Umum',           'count' => $categoryCounts['umum']],
    ];
    $activeCat = request('category', 'all');
@endphp

@section('content')
<div class="space-y-3.5">

    <!-- Hero -->
    <section class="relative overflow-hidden rounded-[24px] bg-[#0B2B25] text-white p-5">
        <svg viewBox="0 0 340 160" preserveAspectRatio="xMaxYMin slice" aria-hidden="true" class="hdr-rings absolute inset-0 w-full h-full pointer-events-none">
            <g class="hdr-ring-group hdr-ring-group--a" style="transform-origin:310px 20px">
                <circle cx="310" cy="20" r="45" fill="none" stroke="#1C4A3E" stroke-width="2"/>
                <circle cx="310" cy="20" r="80" fill="none" stroke="#1C4A3E" stroke-width="2"/>
                <circle cx="310" cy="20" r="115" fill="none" stroke="#1C4A3E" stroke-width="2" stroke-dasharray="6 10"/>
                <circle class="hdr-ring-wave" cx="310" cy="20" r="45" fill="none" stroke="#C6F26B" stroke-width="1.5"/>
            </g>
        </svg>
        <div class="relative">
            <span class="inline-flex items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-[0.12em] text-[#C6F26B]">
                <span class="w-1.5 h-1.5 rounded-full bg-[#C6F26B]"></span>
                {{ $announcements->total() }} pengumuman
            </span>
            <h2 class="font-space font-bold text-xl leading-tight mt-1.5">Papan Pengumuman PKL</h2>
            <p class="text-xs text-[#A9C2B9] leading-relaxed mt-1 max-w-[260px]">Info resmi dari sekolah dan guru pembimbing untuk Anda.</p>
        </div>
    </section>

    <!-- Search -->
    <form method="GET" action="{{ route('student.announcements') }}" class="relative">
        @if(request('category') && request('category') !== 'all')
            <input type="hidden" name="category" value="{{ request('category') }}">
        @endif
        <svg class="w-4 h-4 text-[#9AABA5] absolute left-4 top-1/2 -translate-y-1/2 z-10 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3.5-3.5"/></svg>
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari pengumuman…"
               class="w-full h-12 pl-11 pr-11 rounded-[22px] bg-white border border-[#E3ECE8] text-[13px] text-[#10211D] placeholder:text-[#9AABA5] font-medium focus:outline-none focus:border-[#0F7A66] focus:ring-4 focus:ring-[#0F7A66]/10 transition">
        @if(request('search'))
            <a href="{{ route('student.announcements', ['category' => request('category')]) }}" aria-label="Hapus pencarian"
               class="absolute right-2.5 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full flex items-center justify-center text-[#5A6B65] hover:bg-[#EEF3F1]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M18 6 6 18M6 6l12 12"/></svg>
            </a>
        @endif
    </form>

    <!-- Category chips (horizontal scroll, scrollbar disembunyikan) -->
    <nav class="-mx-4 px-4 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="Filter kategori">
        <div class="flex items-center gap-2 w-max pr-4">
            @foreach($cats as $key => $cat)
                @php $isActive = $activeCat === $key; @endphp
                <a href="{{ route('student.announcements', array_merge(request()->query(), ['category' => $key, 'page' => 1])) }}"
                   @if($isActive) aria-current="page" @endif
                   class="inline-flex items-center gap-1.5 h-9 pl-3.5 pr-2 rounded-full text-[12.5px] font-bold whitespace-nowrap transition-colors
                          {{ $isActive ? 'bg-[#0B2B25] text-white' : 'bg-white text-[#36504A] border border-[#E3ECE8] hover:bg-[#F4F8F6]' }}">
                    {{ $cat['label'] }}
                    <span class="min-w-[20px] h-5 px-1.5 rounded-full text-[10.5px] flex items-center justify-center tabular-nums
                                 {{ $isActive ? 'bg-[#C6F26B] text-[#0B2B25]' : 'bg-[#EEF3F1] text-[#5A6B65]' }}">{{ $cat['count'] }}</span>
                </a>
            @endforeach
        </div>
    </nav>

    <!-- List -->
    @if($announcements->count() > 0)
        <div class="space-y-2.5">
            @foreach($announcements as $ann)
                @php
                    [$cText, $cBg, $cDot] = $catStyle[$ann->category] ?? $catStyle['umum'];
                    $when = ($ann->published_at ?? $ann->created_at);
                @endphp
                <article role="button" tabindex="0"
                         onclick="openDetailModal({{ $ann->id }})" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openDetailModal({{ $ann->id }})}"
                         class="relative bg-white rounded-[22px] border {{ $ann->is_pinned ? 'border-[#E0A21B]/50' : 'border-[#E3ECE8]' }} p-4 cursor-pointer hover:border-[#0F7A66]/40 hover:shadow-[0_8px_24px_rgba(11,43,37,0.08)] active:scale-[0.99] transition-all focus:outline-none focus-visible:ring-4 focus-visible:ring-[#0F7A66]/20">

                    <div class="flex gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                @if($ann->is_pinned)
                                    <span class="inline-flex items-center gap-1 h-5 px-2 rounded-full bg-[#FFF1D6] text-[#7A4B00] text-[10.5px] font-bold">
                                        <svg class="w-2.5 h-2.5 fill-current" viewBox="0 0 24 24"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg>
                                        Disematkan
                                    </span>
                                @endif
                                <span class="inline-flex items-center gap-1.5 h-5 px-2 rounded-full text-[10.5px] font-bold {{ $cBg }} {{ $cText }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $cDot }}"></span>{{ $ann->category_label }}
                                </span>
                            </div>

                            <h3 class="font-bold text-[14.5px] leading-snug text-[#10211D] mt-2 line-clamp-2">{{ $ann->title }}</h3>
                            <p class="text-[12.5px] leading-relaxed text-[#5A6B65] mt-1 line-clamp-2">{{ Str::limit(strip_tags($ann->content), 120) }}</p>
                        </div>

                        @if($ann->image_path)
                            <div class="relative w-[76px] h-[76px] rounded-2xl overflow-hidden bg-[#EEF3F1] shrink-0 self-start">
                                <svg class="absolute inset-0 m-auto w-6 h-6 text-[#9AABA5]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="9" cy="9" r="2"/><path stroke-linecap="round" stroke-linejoin="round" d="m21 15-5-5L5 21"/></svg>
                                <img src="{{ $ann->image_url }}" alt="" loading="lazy"
                                     class="absolute inset-0 w-full h-full object-cover"
                                     onerror="this.remove()">
                            </div>
                        @endif
                    </div>

                    <footer class="flex items-center gap-2 mt-3 pt-3 border-t border-[#EEF3F1] text-[11.5px] text-[#5A6B65]">
                        <span class="w-6 h-6 rounded-full bg-[#DDF3EC] text-[#0B5E4E] font-bold text-[10px] flex items-center justify-center shrink-0">
                            {{ mb_strtoupper(mb_substr($ann->author->name ?? 'S', 0, 1)) }}
                        </span>
                        <span class="font-semibold text-[#36504A] truncate min-w-0">{{ $ann->author->name ?? 'Pihak Sekolah' }}</span>
                        <span class="text-[#C5D2CD]">·</span>
                        <time class="shrink-0" datetime="{{ $when->toIso8601String() }}">{{ $when->diffForHumans() }}</time>
                        @if($ann->major)
                            <span class="ml-auto shrink-0 max-w-[110px] truncate px-2 py-0.5 rounded-full bg-[#EEF3F1] text-[10.5px] font-semibold" title="Khusus {{ $ann->major->name }}">{{ $ann->major->name }}</span>
                        @endif
                    </footer>
                </article>
            @endforeach
        </div>

        @if($announcements->hasPages())
            <div class="pt-1">{{ $announcements->links() }}</div>
        @endif
    @else
        <section class="bg-white rounded-[24px] border border-[#E3ECE8] p-8 text-center">
            <span class="w-14 h-14 rounded-2xl bg-[#DDF3EC] text-[#0B5E4E] flex items-center justify-center mx-auto">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
            </span>
            <h4 class="font-space font-bold text-[15px] text-[#10211D] mt-3">Belum ada pengumuman</h4>
            <p class="text-xs text-[#5A6B65] leading-relaxed mt-1 max-w-[240px] mx-auto">Tidak ada pengumuman untuk kategori atau pencarian ini.</p>
            @if(request('search') || (request('category') && request('category') !== 'all'))
                <a href="{{ route('student.announcements') }}" class="inline-flex items-center h-10 px-4 mt-4 rounded-full bg-[#0B2B25] text-white text-xs font-bold">Reset filter</a>
            @endif
        </section>
    @endif

</div>

<!-- Modal Detail Pengumuman (bottom sheet) -->
<div id="announcement-modal" onclick="if(event.target === this) closeDetailModal()"
     class="hidden fixed inset-0 z-50 bg-[#071915]/70 backdrop-blur-sm flex items-end sm:items-center justify-center p-3 sm:p-4">
    <div class="bg-white w-full max-w-[420px] rounded-[28px] shadow-[0_24px_60px_rgba(7,25,21,0.45)] max-h-[88vh] flex flex-col overflow-hidden">

        <div class="px-4 pt-4 pb-3 flex items-center gap-2 shrink-0">
            <div class="flex items-center gap-1.5 flex-wrap min-w-0 flex-1">
                <span id="modal-category-badge"></span>
                <span id="modal-major-badge" class="inline-flex items-center h-6 px-2.5 rounded-full bg-[#EEF3F1] text-[#36504A] text-[11px] font-semibold truncate max-w-[160px]"></span>
            </div>
            <button type="button" onclick="closeDetailModal()" aria-label="Tutup" class="w-9 h-9 rounded-full bg-[#EEF3F1] hover:bg-[#E3ECE8] text-[#36504A] flex items-center justify-center shrink-0 transition-colors cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="overflow-y-auto px-4 pb-4 space-y-3.5 flex-1">
            <div id="modal-image-container" class="hidden relative rounded-2xl overflow-hidden bg-[#EEF3F1] cursor-zoom-in" onclick="openImageLightbox(document.getElementById('modal-image').src)" title="Ketuk untuk memperbesar">
                <img id="modal-image" src="" alt="Lampiran pengumuman" class="w-full h-auto max-h-60 object-contain mx-auto block"
                     onerror="document.getElementById('modal-image-container').classList.add('hidden')">
                <span class="absolute bottom-2.5 right-2.5 inline-flex items-center gap-1.5 h-7 px-2.5 rounded-full bg-[#0B2B25]/85 text-white text-[10.5px] font-bold pointer-events-none">
                    <svg class="w-3.5 h-3.5 text-[#C6F26B]" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                    Perbesar
                </span>
            </div>

            <h3 id="modal-title" class="font-space font-bold text-lg leading-snug text-[#10211D] break-words"></h3>

            <div class="flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-full bg-white border border-[#E3ECE8] p-1 flex items-center justify-center shrink-0">
                    <img src="{{ $systemLogo ?? asset('images/logo.png') }}" alt="" class="w-full h-full object-contain">
                </span>
                <div class="min-w-0 flex-1">
                    <span id="modal-author" class="block text-[13px] font-bold text-[#10211D] truncate"></span>
                    <span class="block text-[11px] text-[#5A6B65] truncate"><span id="modal-target"></span> · <span id="modal-date"></span></span>
                </div>
            </div>

            <div class="rounded-2xl bg-[#F4F8F6] p-4">
                <p id="modal-content" class="text-[13px] leading-relaxed text-[#36504A] whitespace-pre-line break-words"></p>
            </div>
        </div>

        <div class="p-4 pt-3 border-t border-[#EEF3F1] shrink-0">
            <button type="button" onclick="closeDetailModal()" class="w-full h-12 rounded-[22px] bg-[#0B2B25] hover:bg-[#12382F] text-white text-sm font-bold active:scale-[0.98] transition-all cursor-pointer">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
    const ANN_CAT_STYLE = @json(collect($catStyle)->map(fn ($c) => implode(' ', $c)));

    function openDetailModal(id) {
        fetch(`/siswa/pengumuman/${id}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('modal-title').textContent = data.title;
            document.getElementById('modal-content').textContent = data.content;
            document.getElementById('modal-author').textContent = data.author_name;
            document.getElementById('modal-target').textContent = data.target_scope;
            document.getElementById('modal-date').textContent = data.published_at;

            const major = document.getElementById('modal-major-badge');
            major.textContent = data.major_name || '';
            major.classList.toggle('hidden', !data.major_name);

            const [txt, bg, dot] = (ANN_CAT_STYLE[data.category] || ANN_CAT_STYLE.umum).split(' ');
            const badge = document.getElementById('modal-category-badge');
            badge.className = `inline-flex items-center gap-1.5 h-6 px-2.5 rounded-full text-[11px] font-bold ${bg} ${txt}`;
            badge.innerHTML = '';
            const d = document.createElement('span');
            d.className = `w-1.5 h-1.5 rounded-full ${dot}`;
            badge.append(d, document.createTextNode(data.category_label));

            const imgContainer = document.getElementById('modal-image-container');
            const imgElem = document.getElementById('modal-image');
            if (data.image_url) {
                imgElem.src = data.image_url;
                imgContainer.classList.remove('hidden');
            } else {
                imgContainer.classList.add('hidden');
                imgElem.removeAttribute('src');
            }

            document.getElementById('announcement-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        })
        .catch(err => console.error('Failed to load announcement details', err));
    }

    function closeDetailModal() {
        document.getElementById('announcement-modal').classList.add('hidden');
        document.body.style.overflow = '';
    }
</script>
@endsection
