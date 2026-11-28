<!-- Shared Announcement Detail Modal (Reusable across Teacher, DUDI, and Kepsek views) -->
<div id="modal-shared-announcement-detail" onclick="if(event.target === this) closeAnnouncementDetailModal()" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-2xl max-h-[90vh] overflow-y-auto border border-slate-100 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2 flex-wrap">
                <span id="shared-ann-category" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200 uppercase">
                    PENGUMUMAN
                </span>
                <span id="shared-ann-major" class="hidden inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200"></span>
            </div>
            <button type="button" onclick="closeAnnouncementDetailModal()" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div id="shared-ann-img-container" class="hidden rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 cursor-zoom-in max-h-64" onclick="zoomSharedAnnouncementImage()">
            <img id="shared-ann-img" src="" alt="Lampiran Pengumuman" class="w-full h-full object-cover">
        </div>

        <div class="space-y-1.5">
            <h3 id="shared-ann-title" class="text-base font-extrabold text-slate-900 tracking-tight leading-snug"></h3>
            <div class="flex items-center gap-2 text-xs text-slate-400 flex-wrap">
                <span id="shared-ann-author" class="font-bold text-slate-700"></span>
                <span>&bull;</span>
                <span id="shared-ann-time"></span>
            </div>
        </div>

        <div id="shared-ann-content" class="text-xs text-slate-700 leading-relaxed font-medium bg-slate-50/70 p-4 rounded-2xl border border-slate-100 whitespace-pre-wrap"></div>

        <div class="pt-2 flex justify-end">
            <x-button type="button" variant="outline" size="sm" onclick="closeAnnouncementDetailModal()">Tutup</x-button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
    let sharedAnnouncementImageUrl = null;

    function openAnnouncementDetailModal(ann) {
        if (!ann) return;

        document.getElementById('shared-ann-title').textContent = ann.title || '';
        document.getElementById('shared-ann-content').textContent = ann.content ? String(ann.content).replace(/<[^>]*>?/gm, '') : '';
        document.getElementById('shared-ann-category').textContent = (ann.category_label || ann.category || 'UMUM').toUpperCase();
        document.getElementById('shared-ann-author').textContent = (ann.author && ann.author.name) ? ann.author.name : 'Pihak Sekolah';
        document.getElementById('shared-ann-time').textContent = ann.published_at_human || ann.published_at || ann.created_at || '';

        const majorElem = document.getElementById('shared-ann-major');
        if (ann.major && ann.major.name) {
            majorElem.textContent = 'Jurusan: ' + ann.major.name;
            majorElem.classList.remove('hidden');
        } else {
            majorElem.classList.add('hidden');
        }

        const imgContainer = document.getElementById('shared-ann-img-container');
        const imgElem = document.getElementById('shared-ann-img');

        if (ann.image_path) {
            sharedAnnouncementImageUrl = ann.image_url || ('/storage/' + ann.image_path);
            imgElem.src = sharedAnnouncementImageUrl;
            imgContainer.classList.remove('hidden');
        } else {
            sharedAnnouncementImageUrl = null;
            imgContainer.classList.add('hidden');
            imgElem.src = '';
        }

        document.getElementById('modal-shared-announcement-detail').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeAnnouncementDetailModal() {
        const modal = document.getElementById('modal-shared-announcement-detail');
        if (modal) {
            modal.classList.add('hidden');
        }
        document.body.style.overflow = '';
    }

    function zoomSharedAnnouncementImage() {
        if (sharedAnnouncementImageUrl && typeof openImageLightbox === 'function') {
            openImageLightbox(sharedAnnouncementImageUrl, document.getElementById('shared-ann-title').textContent);
        }
    }
</script>
@endpush
@endonce
