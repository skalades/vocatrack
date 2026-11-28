@props([
    'announcements' => collect(),
    'viewAllUrl' => '#',
    'subtitle' => 'Informasi resmi terbaru dari pihak sekolah',
    'emptyMessage' => 'Belum ada pengumuman aktif.',
])

<x-card title="Pengumuman & Siaran Sekolah" :subtitle="$subtitle">
    <x-slot:headerActions>
        <a href="{{ $viewAllUrl }}" class="text-xs font-bold text-[#12382F] hover:underline">Lihat Semua &rarr;</a>
    </x-slot:headerActions>

    <div class="space-y-2.5">
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
            <button type="button" onclick='openAnnouncementDetailModal(@json($annPayload))' class="w-full text-left flex items-start justify-between gap-3 p-3.5 rounded-2xl bg-slate-50/70 border border-slate-200/80 hover:bg-slate-50 hover:border-indigo-200 transition-all cursor-pointer">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-0.5">
                        <x-badge :variant="$ann->category_badge_variant" size="xs">{{ $ann->category_label }}</x-badge>
                        @if($ann->is_pinned)<span class="text-[10px] font-bold text-amber-700">&bull; Disematkan</span>@endif
                    </div>
                    <span class="font-bold text-slate-900 text-xs block truncate">{{ $ann->title }}</span>
                    <span class="text-[11px] text-slate-500 block line-clamp-1 mt-0.5">{{ Str::limit(strip_tags($ann->content), 110) }}</span>
                </div>
                <span class="text-[10px] text-slate-400 font-mono shrink-0">{{ ($ann->published_at ?? $ann->created_at)->diffForHumans() }}</span>
            </button>
        @empty
            <p class="text-xs text-slate-400 py-3 text-center">{{ $emptyMessage }}</p>
        @endforelse
    </div>
</x-card>
