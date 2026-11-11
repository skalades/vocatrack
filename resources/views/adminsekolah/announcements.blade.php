@extends('layouts.app')

@section('page_title', 'Manajemen Pengumuman & Siaran')
@section('page_subtitle', 'Publikasikan informasi, agenda, instruksi PKL, dan pengumuman dengan gambar berdasarkan kategori dan jurusan')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Enterprise Hero Banner -->
    <x-page-header 
        title="Pengumuman & Siaran Sekolah" 
        subtitle="Kelola siaran berita resmi sekolah. Targetkan pengumuman ke seluruh civitas atau sebagian pengguna berdasarkan kategori peran dan jurusan."
        badge="MANAJEMEN SEKOLAH"
    >
        <x-button type="button" variant="primary" size="sm" onclick="openAddModal()">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            <span>+ Buat Pengumuman Baru</span>
        </x-button>
    </x-page-header>

    <!-- Stat Cards Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card 
            title="Total Pengumuman" 
            :value="$stats['total']" 
            subtitle="Seluruh arsip siaran"
            color="indigo" 
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Pengumuman Aktif" 
            :value="$stats['active']" 
            subtitle="Sedang ditayangkan"
            color="emerald" 
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Disematkan (Pinned)" 
            :value="$stats['pinned']" 
            subtitle="Prioritas posisi atas"
            color="amber" 
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Penting / Mendesak" 
            :value="$stats['important']" 
            subtitle="Instruksi kritikal"
            color="rose" 
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Filters & Search Bar -->
    <x-card noPadding>
        <div class="p-4 bg-slate-50/60 border-b border-slate-100 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <form method="GET" action="{{ route('adminsekolah.announcements') }}" class="flex flex-wrap items-center gap-2.5 flex-1">
                
                <!-- Category Filter -->
                <div class="min-w-[160px]">
                    <select name="category" onchange="this.form.submit()" class="w-full text-xs font-semibold py-2 px-3 rounded-xl bg-white border border-slate-200 text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        <option value="all" {{ ($filters['category'] ?? 'all') === 'all' ? 'selected' : '' }}>Semua Kategori</option>
                        <option value="umum" {{ ($filters['category'] ?? '') === 'umum' ? 'selected' : '' }}>Informasi Umum</option>
                        <option value="penting" {{ ($filters['category'] ?? '') === 'penting' ? 'selected' : '' }}>Penting & Mendesak</option>
                        <option value="kegiatan" {{ ($filters['category'] ?? '') === 'kegiatan' ? 'selected' : '' }}>Kegiatan PKL</option>
                        <option value="akademik" {{ ($filters['category'] ?? '') === 'akademik' ? 'selected' : '' }}>Akademik & Kurikulum</option>
                        <option value="libur" {{ ($filters['category'] ?? '') === 'libur' ? 'selected' : '' }}>Hari Libur & Jadwal</option>
                        <option value="dudi" {{ ($filters['category'] ?? '') === 'dudi' ? 'selected' : '' }}>Mitra DU/DI</option>
                    </select>
                </div>

                <!-- Major Filter -->
                <div class="min-w-[170px]">
                    <select name="major_id" onchange="this.form.submit()" class="w-full text-xs font-semibold py-2 px-3 rounded-xl bg-white border border-slate-200 text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        <option value="all" {{ ($filters['major_id'] ?? 'all') === 'all' ? 'selected' : '' }}>Semua Target Jurusan</option>
                        @foreach($majors as $m)
                            <option value="{{ $m->id }}" {{ ($filters['major_id'] ?? '') == $m->id ? 'selected' : '' }}>
                                Jurusan: {{ $m->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Search Input -->
                <div class="relative flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari judul atau isi pengumuman..."
                           class="w-full text-xs font-medium pl-9 pr-8 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    @if(!empty($filters['search']))
                        <a href="{{ route('adminsekolah.announcements', ['category' => $filters['category'], 'major_id' => $filters['major_id']]) }}" class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 text-xs font-bold">✕</a>
                    @endif
                </div>

                <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition-colors">
                    Filter
                </button>
            </form>

            @if(!empty($filters['search']) || ($filters['category'] ?? 'all') !== 'all' || ($filters['major_id'] ?? 'all') !== 'all')
                <a href="{{ route('adminsekolah.announcements') }}" class="text-xs font-bold text-rose-600 hover:text-rose-700 whitespace-nowrap self-center">
                    Reset Filter
                </a>
            @endif
        </div>

        <!-- Announcements Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6">Informasi & Judul</th>
                        <th class="py-3.5 px-4">Kategori</th>
                        <th class="py-3.5 px-4">Target Audiens</th>
                        <th class="py-3.5 px-4">Jurusan Target</th>
                        <th class="py-3.5 px-4">Penulis & Tanggal</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($announcements as $ann)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3.5 px-6">
                                <div class="flex items-start gap-3">
                                    @if($ann->image_path)
                                        <div class="relative group/thumb w-14 h-11 rounded-xl bg-slate-100 overflow-hidden border border-slate-200 shrink-0 cursor-zoom-in" onclick="openImageLightbox('{{ $ann->image_url }}')" title="Klik untuk membaca tulisan pada gambar">
                                            <img src="{{ $ann->image_url }}" alt="{{ $ann->title }}" class="w-full h-full object-cover group-hover/thumb:scale-110 transition-transform duration-200">
                                            <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover/thumb:opacity-100 transition-opacity flex items-center justify-center text-white">
                                                <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                                            </div>
                                        </div>
                                    @else
                                        <div class="w-11 h-11 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
                                            <svg class="w-5 h-5 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                                        </div>
                                    @endif

                                    <div class="min-w-0 max-w-md">
                                        <div class="flex items-center gap-1.5 mb-0.5">
                                            @if($ann->is_pinned)
                                                <span class="inline-flex items-center gap-1 text-[10px] font-extrabold text-amber-700 bg-amber-50 border border-amber-200/80 px-2 py-0.5 rounded-full">
                                                    <svg class="w-2.5 h-2.5 fill-current" viewBox="0 0 24 24"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg>
                                                    Pinned
                                                </span>
                                            @endif
                                            <span class="font-bold text-slate-900 text-xs truncate">{{ $ann->title }}</span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 line-clamp-1 font-normal">
                                            {{ Str::limit(strip_tags($ann->content), 85) }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <x-badge :variant="$ann->category_badge_variant" size="xs">
                                    {{ $ann->category_label }}
                                </x-badge>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $ann->target_role_label }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($ann->major)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200/80">
                                        {{ $ann->major->name }}
                                    </span>
                                @else
                                    <span class="text-[11px] text-slate-400 font-medium">Semua Jurusan</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="font-bold text-slate-800 block text-xs">{{ $ann->author->name ?? 'Admin Sekolah' }}</span>
                                <span class="text-[10px] text-slate-400 block mt-0.5 font-mono">
                                    {{ $ann->published_at ? $ann->published_at->isoFormat('D MMM Y • HH:mm') : $ann->created_at->isoFormat('D MMM Y • HH:mm') }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($ann->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-6 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    
                                    <!-- Toggle Pin -->
                                    <form action="{{ route('adminsekolah.announcements.toggle-pin', $ann) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" title="{{ $ann->is_pinned ? 'Lepas Sematan' : 'Sematkan ke Teratas' }}" class="p-1.5 rounded-lg {{ $ann->is_pinned ? 'text-amber-600 bg-amber-50 hover:bg-amber-100' : 'text-slate-400 hover:text-amber-600 hover:bg-amber-50' }} transition-colors cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                                        </button>
                                    </form>

                                    <!-- Edit Button -->
                                    <button type="button" onclick="openEditModal({{ json_encode($ann) }})" title="Edit Pengumuman" class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>

                                    <!-- Delete Button -->
                                    <form action="{{ route('adminsekolah.announcements.destroy', $ann) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengumuman ini?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus Pengumuman" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400 font-medium">
                                <div class="max-w-xs mx-auto space-y-2">
                                    <svg class="w-10 h-10 text-slate-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                                    <p class="text-xs">Belum ada pengumuman yang sesuai kriteria pencarian.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 bg-slate-50/50">
            {{ $announcements->links() }}
        </div>
    </x-card>

</div>

<!-- Modal Tambah Pengumuman -->
<div id="modal-add-announcement" onclick="if(event.target === this) closeAddModal()" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-xl rounded-3xl p-6 shadow-2xl max-h-[90vh] overflow-y-auto border border-slate-100 animate-in fade-in duration-150">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Buat Pengumuman Baru</h3>
                <p class="text-xs text-slate-500 font-medium">Siarkan informasi ke seluruh pengguna atau targetkan jurusan & peran tertentu.</p>
            </div>
            <button type="button" onclick="closeAddModal()" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('adminsekolah.announcements.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Judul Pengumuman <span class="text-rose-500">*</span></label>
                <input type="text" name="title" required placeholder="Contoh: Jadwal Pembekalan PKL Gelombang II Tahun 2026/2027"
                       class="w-full text-xs font-medium p-3 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kategori <span class="text-rose-500">*</span></label>
                    <select name="category" required class="w-full text-xs font-medium p-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900">
                        <option value="umum">Informasi Umum</option>
                        <option value="penting">Penting / Urgent</option>
                        <option value="kegiatan">Kegiatan PKL</option>
                        <option value="akademik">Akademik</option>
                        <option value="libur">Hari Libur / Jadwal</option>
                        <option value="dudi">Mitra DU/DI</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Target Audiens <span class="text-rose-500">*</span></label>
                    <select name="target_role" required class="w-full text-xs font-medium p-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900">
                        <option value="all">Semua Pengguna</option>
                        <option value="siswa">Khusus Siswa PKL</option>
                        <option value="guru-pembimbing">Guru Pembimbing</option>
                        <option value="pembimbing-dudi">Mentor DU/DI</option>
                        <option value="kepala-sekolah">Kepala Sekolah</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Target Jurusan</label>
                    <select name="target_major_id" class="w-full text-xs font-medium p-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900">
                        <option value="">Semua Jurusan</option>
                        @foreach($majors as $m)
                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Upload Gambar / Banner -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Gambar / Banner Pengumuman (Opsional)</label>
                <div class="flex items-center gap-3">
                    <input type="file" name="image" id="add-image-input" accept="image/png,image/jpeg,image/webp" onchange="previewImage(this, 'add-image-preview')"
                           class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:bg-indigo-50 file:text-indigo-700 file:font-bold hover:file:bg-indigo-100 cursor-pointer">
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Format didukung: JPG, PNG, WEBP. Ukuran file maksimal 2MB.</p>
                
                <div id="add-image-preview" class="hidden mt-2 rounded-xl overflow-hidden bg-slate-100 max-h-48 border border-slate-200">
                    <img src="" alt="Pratinjau Gambar" class="w-full h-auto object-cover max-h-48">
                </div>
            </div>

            <!-- Isi Pengumuman -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Uraian / Isi Pengumuman <span class="text-rose-500">*</span></label>
                <textarea name="content" rows="5" required placeholder="Tuliskan isi pengumuman secara rinci..."
                          class="w-full text-xs font-medium p-3 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 leading-relaxed text-slate-900"></textarea>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" name="is_pinned" id="add_is_pinned" value="1" class="rounded text-indigo-600 focus:ring-indigo-500">
                <label for="add_is_pinned" class="text-xs font-bold text-slate-700 cursor-pointer">Sematkan ke posisi teratas (Pinned Announcement)</label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <x-button type="button" variant="outline" size="sm" onclick="closeAddModal()">Batal</x-button>
                <x-button type="submit" variant="primary" size="sm">Terbitkan Pengumuman</x-button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Pengumuman -->
<div id="modal-edit-announcement" onclick="if(event.target === this) closeEditModal()" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-xl rounded-3xl p-6 shadow-2xl max-h-[90vh] overflow-y-auto border border-slate-100 animate-in fade-in duration-150">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Edit Pengumuman</h3>
                <p class="text-xs text-slate-500 font-medium">Perbarui judul, target audiens, gambar, atau isi teks.</p>
            </div>
            <button type="button" onclick="closeEditModal()" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="edit-form" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Judul Pengumuman <span class="text-rose-500">*</span></label>
                <input type="text" name="title" id="edit-title" required
                       class="w-full text-xs font-medium p-3 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kategori <span class="text-rose-500">*</span></label>
                    <select name="category" id="edit-category" required class="w-full text-xs font-medium p-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900">
                        <option value="umum">Informasi Umum</option>
                        <option value="penting">Penting / Urgent</option>
                        <option value="kegiatan">Kegiatan PKL</option>
                        <option value="akademik">Akademik</option>
                        <option value="libur">Hari Libur / Jadwal</option>
                        <option value="dudi">Mitra DU/DI</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Target Audiens <span class="text-rose-500">*</span></label>
                    <select name="target_role" id="edit-target-role" required class="w-full text-xs font-medium p-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900">
                        <option value="all">Semua Pengguna</option>
                        <option value="siswa">Khusus Siswa PKL</option>
                        <option value="guru-pembimbing">Guru Pembimbing</option>
                        <option value="pembimbing-dudi">Mentor DU/DI</option>
                        <option value="kepala-sekolah">Kepala Sekolah</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Target Jurusan</label>
                    <select name="target_major_id" id="edit-target-major" class="w-full text-xs font-medium p-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-900">
                        <option value="">Semua Jurusan</option>
                        @foreach($majors as $m)
                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Current image preview & replace option -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Gambar / Banner Pengumuman</label>
                <div id="edit-current-image-wrapper" class="hidden mb-2">
                    <p class="text-[10px] text-slate-400 mb-1 font-semibold">Gambar Saat Ini:</p>
                    <div class="relative w-full max-h-40 rounded-xl overflow-hidden bg-slate-100 border border-slate-200">
                        <img id="edit-current-image" src="" alt="Gambar Saat Ini" class="w-full h-auto max-h-40 object-cover">
                    </div>
                    <div class="mt-1.5 flex items-center gap-2">
                        <input type="checkbox" name="remove_image" id="edit-remove-image" value="1" class="rounded text-rose-600 focus:ring-rose-500">
                        <label for="edit-remove-image" class="text-xs font-bold text-rose-600 cursor-pointer">Hapus gambar ini</label>
                    </div>
                </div>

                <input type="file" name="image" accept="image/png,image/jpeg,image/webp" onchange="previewImage(this, 'edit-image-preview')"
                       class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:bg-indigo-50 file:text-indigo-700 file:font-bold hover:file:bg-indigo-100 cursor-pointer">
                <div id="edit-image-preview" class="hidden mt-2 rounded-xl overflow-hidden bg-slate-100 max-h-48 border border-slate-200">
                    <img src="" alt="Pratinjau Baru" class="w-full h-auto object-cover max-h-48">
                </div>
            </div>

            <!-- Isi Pengumuman -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Uraian / Isi Pengumuman <span class="text-rose-500">*</span></label>
                <textarea name="content" id="edit-content" rows="5" required
                          class="w-full text-xs font-medium p-3 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 leading-relaxed text-slate-900"></textarea>
            </div>

            <div class="flex items-center gap-4 pt-1">
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_pinned" id="edit-is-pinned" value="1" class="rounded text-indigo-600">
                    <label for="edit-is-pinned" class="text-xs font-bold text-slate-700 cursor-pointer">Sematkan Pinned</label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="edit-is-active" value="1" class="rounded text-emerald-600">
                    <label for="edit-is-active" class="text-xs font-bold text-slate-700 cursor-pointer">Status Aktif</label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <x-button type="button" variant="outline" size="sm" onclick="closeEditModal()">Batal</x-button>
                <x-button type="submit" variant="primary" size="sm">Simpan Perubahan</x-button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('modal-add-announcement').classList.remove('hidden');
    }
    function closeAddModal() {
        document.getElementById('modal-add-announcement').classList.add('hidden');
    }

    function openEditModal(ann) {
        const form = document.getElementById('edit-form');
        form.action = `/admin-sekolah/announcements/${ann.id}`;
        
        document.getElementById('edit-title').value = ann.title;
        document.getElementById('edit-category').value = ann.category;
        document.getElementById('edit-target-role').value = ann.target_role;
        document.getElementById('edit-target-major').value = ann.target_major_id || '';
        document.getElementById('edit-content').value = ann.content;
        document.getElementById('edit-is-pinned').checked = Boolean(ann.is_pinned);
        document.getElementById('edit-is-active').checked = Boolean(ann.is_active);

        const currentImgWrapper = document.getElementById('edit-current-image-wrapper');
        const currentImg = document.getElementById('edit-current-image');
        document.getElementById('edit-remove-image').checked = false;

        if (ann.image_path) {
            currentImg.src = `/storage/${ann.image_path}`;
            currentImgWrapper.classList.remove('hidden');
        } else {
            currentImgWrapper.classList.add('hidden');
            currentImg.src = '';
        }

        document.getElementById('edit-image-preview').classList.add('hidden');
        document.getElementById('modal-edit-announcement').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('modal-edit-announcement').classList.add('hidden');
    }

    function previewImage(input, previewId) {
        const previewContainer = document.getElementById(previewId);
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = previewContainer.querySelector('img');
                img.src = e.target.result;
                previewContainer.classList.remove('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endsection
