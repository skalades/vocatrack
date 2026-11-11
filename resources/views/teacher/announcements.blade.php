@extends('layouts.app')

@section('page_title', 'Pengumuman Bimbingan PKL')
@section('page_subtitle', 'Sampaikan arahan, jadwal monitoring, dan instruksi penting kepada siswa bimbingan atau jurusan')

@section('content')
<div class="space-y-6">

    <x-page-header 
        title="Pengumuman Bimbingan PKL" 
        subtitle="Publikasikan pengumuman untuk siswa bimbingan Anda atau seluruh siswa dalam jurusan terkait. Lampirkan gambar jika diperlukan."
    >
        <x-button type="button" variant="primary" size="sm" onclick="openGuruAddModal()">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Tulis Pengumuman
        </x-button>
    </x-page-header>

    <!-- Filters & Search -->
    <x-card noPadding>
        <div class="p-4 bg-slate-50/60 border-b border-slate-100 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <form method="GET" action="{{ route('guru.announcements') }}" class="flex flex-wrap items-center gap-2.5 flex-1">
                
                <div class="min-w-[160px]">
                    <select name="category" onchange="this.form.submit()" class="w-full text-xs font-semibold py-2 px-3 rounded-xl bg-white border border-slate-200 text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        <option value="all" {{ ($filters['category'] ?? 'all') === 'all' ? 'selected' : '' }}>Semua Kategori</option>
                        <option value="kegiatan" {{ ($filters['category'] ?? '') === 'kegiatan' ? 'selected' : '' }}>Kegiatan PKL</option>
                        <option value="penting" {{ ($filters['category'] ?? '') === 'penting' ? 'selected' : '' }}>Penting / Urgent</option>
                        <option value="akademik" {{ ($filters['category'] ?? '') === 'akademik' ? 'selected' : '' }}>Akademik</option>
                        <option value="libur" {{ ($filters['category'] ?? '') === 'libur' ? 'selected' : '' }}>Hari Libur & Jadwal</option>
                        <option value="umum" {{ ($filters['category'] ?? '') === 'umum' ? 'selected' : '' }}>Informasi Umum</option>
                    </select>
                </div>

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

                <div class="relative flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari judul pengumuman..."
                           class="w-full text-xs font-medium pl-9 pr-8 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    @if(!empty($filters['search']))
                        <a href="{{ route('guru.announcements') }}" class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 text-xs font-bold">✕</a>
                    @endif
                </div>

                <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition-colors">
                    Filter
                </button>
            </form>
        </div>

        <!-- Grid of Announcements -->
        <div class="p-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($announcements as $ann)
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-all overflow-hidden flex flex-col justify-between group">
                    
                    <div>
                        @if($ann->image_path)
                            <div class="relative w-full h-40 bg-slate-100 overflow-hidden cursor-zoom-in group/img" onclick="openImageLightbox('{{ $ann->image_url }}')" title="Klik untuk membaca tulisan pada gambar">
                                <img src="{{ $ann->image_url }}" alt="{{ $ann->title }}" class="w-full h-full object-cover group-hover/img:scale-105 transition-transform duration-300">
                                <div class="absolute inset-0 bg-slate-950/20 opacity-0 group-hover/img:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold gap-1">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                                    <span>Perbesar</span>
                                </div>
                                @if($ann->is_pinned)
                                    <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded-full bg-amber-500/90 text-white text-[9px] font-extrabold shadow-sm">
                                        📌 Pinned
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
                                            <span class="px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[9px] font-extrabold border border-amber-200">
                                                📌 Pinned
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

                    <div class="p-4 pt-2 border-t border-slate-100 flex items-center justify-between">
                        <div class="min-w-0">
                            <span class="text-[10px] font-bold text-slate-700 block truncate">{{ $ann->author->name ?? 'Pihak Sekolah' }}</span>
                            <span class="text-[9px] text-slate-400 font-mono">{{ $ann->published_at ? $ann->published_at->diffForHumans() : $ann->created_at->diffForHumans() }}</span>
                        </div>

                        <!-- Actions if owned by this teacher -->
                        @if($ann->author_id === auth()->id())
                            <div class="flex items-center gap-1">
                                <button type="button" onclick="openGuruEditModal({{ json_encode($ann) }})" title="Edit" class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </button>
                                <form action="{{ route('guru.announcements.destroy', $ann) }}" method="POST" onsubmit="return confirm('Hapus pengumuman ini?')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Hapus" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        @else
                            <span class="text-[9px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">
                                Siaran Sekolah
                            </span>
                        @endif
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

<!-- Modal Tulis Pengumuman Guru -->
<div id="modal-guru-add-announcement" onclick="if(event.target === this) closeGuruAddModal()" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-2xl max-h-[90vh] overflow-y-auto border border-slate-100">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Tulis Pengumuman Bimbingan</h3>
                <p class="text-xs text-slate-500 font-medium">Kirimkan instruksi atau informasi penting untuk siswa bimbingan.</p>
            </div>
            <button type="button" onclick="closeGuruAddModal()" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('guru.announcements.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Judul Pengumuman <span class="text-rose-500">*</span></label>
                <input type="text" name="title" required placeholder="Contoh: Jadwal Kunjungan Monitoring PKL Minggu Ini"
                       class="w-full text-xs font-medium p-3 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kategori <span class="text-rose-500">*</span></label>
                    <select name="category" required class="w-full text-xs font-medium p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900">
                        <option value="kegiatan">Kegiatan PKL</option>
                        <option value="penting">Penting / Mendesak</option>
                        <option value="akademik">Akademik</option>
                        <option value="libur">Hari Libur / Jadwal</option>
                        <option value="umum">Informasi Umum</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Target Audiens <span class="text-rose-500">*</span></label>
                    <select name="target_role" required class="w-full text-xs font-medium p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900">
                        <option value="siswa">Khusus Siswa PKL</option>
                        <option value="all">Semua Pengguna</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Target Jurusan</label>
                    <select name="target_major_id" class="w-full text-xs font-medium p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900">
                        <option value="">Semua Jurusan</option>
                        @foreach($majors as $m)
                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Upload Gambar -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Foto / Gambar Pengumuman (Opsional)</label>
                <input type="file" name="image" accept="image/png,image/jpeg,image/webp" onchange="previewGuruImage(this, 'guru-add-preview')"
                       class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:bg-indigo-50 file:text-indigo-700 file:font-bold hover:file:bg-indigo-100 cursor-pointer">
                <div id="guru-add-preview" class="hidden mt-2 rounded-xl overflow-hidden bg-slate-100 max-h-48 border border-slate-200">
                    <img src="" alt="Pratinjau" class="w-full h-auto object-cover max-h-48">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Isi Pengumuman <span class="text-rose-500">*</span></label>
                <textarea name="content" rows="4" required placeholder="Tuliskan arahan bimbingan Anda secara lengkap..."
                          class="w-full text-xs font-medium p-3 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 leading-relaxed text-slate-900"></textarea>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" name="is_pinned" id="guru_add_pinned" value="1" class="rounded text-indigo-600">
                <label for="guru_add_pinned" class="text-xs font-bold text-slate-700 cursor-pointer">Sematkan di atas (Pinned)</label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <x-button type="button" variant="outline" size="sm" onclick="closeGuruAddModal()">Batal</x-button>
                <x-button type="submit" variant="primary" size="sm">Publikasikan Pengumuman</x-button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Guru -->
<div id="modal-guru-edit-announcement" onclick="if(event.target === this) closeGuruEditModal()" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-2xl max-h-[90vh] overflow-y-auto border border-slate-100">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Edit Pengumuman Bimbingan</h3>
            </div>
            <button type="button" onclick="closeGuruEditModal()" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="guru-edit-form" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Judul Pengumuman <span class="text-rose-500">*</span></label>
                <input type="text" name="title" id="guru-edit-title" required
                       class="w-full text-xs font-medium p-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kategori <span class="text-rose-500">*</span></label>
                    <select name="category" id="guru-edit-category" required class="w-full text-xs font-medium p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900">
                        <option value="kegiatan">Kegiatan PKL</option>
                        <option value="penting">Penting / Mendesak</option>
                        <option value="akademik">Akademik</option>
                        <option value="libur">Hari Libur / Jadwal</option>
                        <option value="umum">Informasi Umum</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Target Audiens <span class="text-rose-500">*</span></label>
                    <select name="target_role" id="guru-edit-target-role" required class="w-full text-xs font-medium p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900">
                        <option value="siswa">Khusus Siswa PKL</option>
                        <option value="all">Semua Pengguna</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Target Jurusan</label>
                    <select name="target_major_id" id="guru-edit-target-major" class="w-full text-xs font-medium p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900">
                        <option value="">Semua Jurusan</option>
                        @foreach($majors as $m)
                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Ganti Foto / Gambar (Opsional)</label>
                <div id="guru-current-img-box" class="hidden mb-2">
                    <img id="guru-current-img" src="" alt="Gambar" class="w-full h-auto max-h-36 object-cover rounded-xl border border-slate-200">
                    <div class="mt-1 flex items-center gap-2">
                        <input type="checkbox" name="remove_image" id="guru-remove-image" value="1" class="rounded text-rose-600">
                        <label for="guru-remove-image" class="text-xs font-bold text-rose-600 cursor-pointer">Hapus foto ini</label>
                    </div>
                </div>
                <input type="file" name="image" accept="image/png,image/jpeg,image/webp" onchange="previewGuruImage(this, 'guru-edit-preview')"
                       class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:bg-indigo-50 file:text-indigo-700 file:font-bold hover:file:bg-indigo-100 cursor-pointer">
                <div id="guru-edit-preview" class="hidden mt-2 rounded-xl overflow-hidden bg-slate-100 max-h-48 border border-slate-200">
                    <img src="" alt="Pratinjau Baru" class="w-full h-auto object-cover max-h-48">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Isi Pengumuman <span class="text-rose-500">*</span></label>
                <textarea name="content" id="guru-edit-content" rows="4" required
                          class="w-full text-xs font-medium p-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 leading-relaxed"></textarea>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" name="is_pinned" id="guru-edit-pinned" value="1" class="rounded text-indigo-600">
                <label for="guru-edit-pinned" class="text-xs font-bold text-slate-700 cursor-pointer">Sematkan Pinned</label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <x-button type="button" variant="outline" size="sm" onclick="closeGuruEditModal()">Batal</x-button>
                <x-button type="submit" variant="primary" size="sm">Simpan Perubahan</x-button>
            </div>
        </form>
    </div>
</div>

<script>
    function openGuruAddModal() {
        document.getElementById('modal-guru-add-announcement').classList.remove('hidden');
    }
    function closeGuruAddModal() {
        document.getElementById('modal-guru-add-announcement').classList.add('hidden');
    }

    function openGuruEditModal(ann) {
        const form = document.getElementById('guru-edit-form');
        form.action = `/guru/announcements/${ann.id}`;

        document.getElementById('guru-edit-title').value = ann.title;
        document.getElementById('guru-edit-category').value = ann.category;
        document.getElementById('guru-edit-target-role').value = ann.target_role;
        document.getElementById('guru-edit-target-major').value = ann.target_major_id || '';
        document.getElementById('guru-edit-content').value = ann.content;
        document.getElementById('guru-edit-pinned').checked = Boolean(ann.is_pinned);

        const imgBox = document.getElementById('guru-current-img-box');
        const imgElem = document.getElementById('guru-current-img');
        document.getElementById('guru-remove-image').checked = false;

        if (ann.image_path) {
            imgElem.src = `/storage/${ann.image_path}`;
            imgBox.classList.remove('hidden');
        } else {
            imgBox.classList.add('hidden');
            imgElem.src = '';
        }

        document.getElementById('guru-edit-preview').classList.add('hidden');
        document.getElementById('modal-guru-edit-announcement').classList.remove('hidden');
    }

    function closeGuruEditModal() {
        document.getElementById('modal-guru-edit-announcement').classList.add('hidden');
    }

    function previewGuruImage(input, previewId) {
        const container = document.getElementById(previewId);
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = container.querySelector('img');
                img.src = e.target.result;
                container.classList.remove('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endsection
