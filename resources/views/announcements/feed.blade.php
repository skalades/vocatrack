@extends('layouts.app')

@section('page_title', $title)
@section('page_subtitle', $subtitle)

@section('content')
<div class="w-full space-y-6">

    <x-page-header
        :title="$title"
        :subtitle="$subtitle"
        :badge="$badge"
    />

    <x-card noPadding>
        <div class="p-4 bg-slate-50/60 border-b border-slate-100 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <form method="GET" action="{{ route($routeName) }}" class="flex flex-wrap items-center gap-2.5 flex-1">
                <div class="min-w-[160px]">
                    <select name="category" onchange="this.form.submit()" class="w-full text-xs font-semibold py-2 px-3 rounded-xl bg-white border border-slate-200 text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        <option value="all" {{ ($filters['category'] ?? 'all') === 'all' ? 'selected' : '' }}>Semua Kategori</option>
                        <option value="penting" {{ ($filters['category'] ?? '') === 'penting' ? 'selected' : '' }}>Penting / Urgent</option>
                        <option value="kegiatan" {{ ($filters['category'] ?? '') === 'kegiatan' ? 'selected' : '' }}>Kegiatan PKL</option>
                        <option value="akademik" {{ ($filters['category'] ?? '') === 'akademik' ? 'selected' : '' }}>Akademik</option>
                        <option value="libur" {{ ($filters['category'] ?? '') === 'libur' ? 'selected' : '' }}>Hari Libur & Jadwal</option>
                        <option value="dudi" {{ ($filters['category'] ?? '') === 'dudi' ? 'selected' : '' }}>Mitra DU/DI</option>
                        <option value="umum" {{ ($filters['category'] ?? '') === 'umum' ? 'selected' : '' }}>Informasi Umum</option>
                    </select>
                </div>

                @if($majors->isNotEmpty())
                    <div class="min-w-[170px]">
                        <select name="major_id" onchange="this.form.submit()" class="w-full text-xs font-semibold py-2 px-3 rounded-xl bg-white border border-slate-200 text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                            <option value="all" {{ ($filters['major_id'] ?? 'all') === 'all' ? 'selected' : '' }}>Semua Jurusan</option>
                            @foreach($majors as $m)
                                <option value="{{ $m->id }}" {{ ($filters['major_id'] ?? '') == $m->id ? 'selected' : '' }}>
                                    Jurusan: {{ $m->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="relative flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari judul atau isi pengumuman..."
                           class="w-full text-xs font-medium pl-9 pr-8 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    @if(!empty($filters['search']))
                        <a href="{{ route($routeName) }}" class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 text-xs font-bold flex items-center justify-center">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                </div>

                <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition-colors cursor-pointer">
                    Filter
                </button>
            </form>
        </div>

        <div class="p-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($announcements as $ann)
                @php
                    $annPayload = [
                        'id' => $ann->id,
                        'title' => $ann->title,
                        'content' => $ann->content,
                        'category' => $ann->category,
                        'category_label' => $ann->category_label,
                        'image_path' => $ann->image_path,
                        'image_url' => $ann->image_url,
                        'author' => ['name' => $ann->author->name ?? 'Pihak Sekolah'],
                        'major' => $ann->major ? ['name' => $ann->major->name] : null,
                        'published_at_human' => ($ann->published_at ?? $ann->created_at)->isoFormat('D MMM Y • HH:mm'),
                    ];
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-all overflow-hidden flex flex-col justify-between group">
                    <div>
                        @if($ann->image_path)
                            <div class="relative w-full h-40 bg-slate-100 overflow-hidden cursor-zoom-in group/img" onclick="openImageLightbox('{{ $ann->image_url }}')" title="Klik untuk membaca tulisan pada gambar">
                                <img src="{{ $ann->image_url }}" alt="{{ $ann->title }}" class="w-full h-full object-cover group-hover/img:scale-105 transition-transform duration-300">
                                @if($ann->is_pinned)
                                    <span class="absolute top-2.5 left-2.5 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-500/90 text-white text-[9px] font-extrabold shadow-sm">
                                        Pinned
                                    </span>
                                @endif
                                <div class="absolute bottom-2 right-2">
                                    <x-badge :variant="$ann->category_badge_variant" size="xs">
                                        {{ strtoupper($ann->category_label) }}
                                    </x-badge>
                                </div>
                            </div>
                        @endif

                        <div class="p-4 space-y-2">
                            @if(!$ann->image_path)
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center gap-1.5">
                                        @if($ann->is_pinned)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[9px] font-extrabold border border-amber-200">
                                                Pinned
                                            </span>
                                        @endif
                                        <x-badge :variant="$ann->category_badge_variant" size="xs">
                                            {{ strtoupper($ann->category_label) }}
                                        </x-badge>
                                    </div>
                                    @if($ann->major)
                                        <span class="text-[9px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-full truncate max-w-[120px]">
                                            {{ $ann->major->name }}
                                        </span>
                                    @endif
                                </div>
                            @elseif($ann->major)
                                <div>
                                    <span class="text-[9px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-200/80 px-2 py-0.5 rounded-full inline-block truncate max-w-[200px]">
                                        Khusus: {{ $ann->major->name }}
                                    </span>
                                </div>
                            @endif

                            <h3 class="text-xs font-extrabold text-slate-900 group-hover:text-indigo-600 transition-colors line-clamp-2 leading-snug">
                                {{ $ann->title }}
                            </h3>

                            <p class="text-[11px] text-slate-500 line-clamp-3 leading-relaxed font-medium">
                                {{ Str::limit(strip_tags($ann->content), 120) }}
                            </p>
                        </div>
                    </div>

                    <div class="p-4 pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <span class="text-[10px] font-bold text-slate-700 block truncate">{{ $ann->author->name ?? 'Pihak Sekolah' }}</span>
                            <span class="text-[9px] text-slate-400 font-mono">{{ ($ann->published_at ?? $ann->created_at)->diffForHumans() }}</span>
                        </div>
                        <button type="button" onclick='openAnnouncementDetailModal(@json($annPayload))' class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 text-[11px] font-bold transition-colors shrink-0 cursor-pointer">
                            Baca
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center text-slate-400">
                    <p class="text-xs">Belum ada pengumuman untuk ditampilkan.</p>
                </div>
            @endforelse
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $announcements->links() }}
        </div>
    </x-card>

</div>

<x-announcement-detail-modal />
@endsection
