@extends('layouts.student-mobile')

@section('title', 'Beranda Siswa — VokaTrack')

@section('content')
<div class="space-y-4">

    <!-- Greeting Hero Banner -->
    <div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-indigo-700 to-indigo-800 rounded-3xl p-5 text-white shadow-lg shadow-indigo-600/20">
        <div class="absolute -right-6 -bottom-6 w-32 h-32 rounded-full bg-white/5 pointer-events-none"></div>
        <div class="relative flex items-center justify-between gap-3">
            <div class="min-w-0">
                <span class="text-[11px] font-bold text-indigo-200 uppercase tracking-wider block">Portal Siswa PKL</span>
                <h2 class="text-lg font-black tracking-tight truncate mt-0.5">{{ auth()->user()->name }}</h2>
                <div class="flex items-center gap-1.5 mt-1 text-[11px] text-indigo-100 font-medium">
                    <span class="truncate">{{ $spkl->student->class->name ?? 'Kelas' }}</span>
                    <span>&bull;</span>
                    <span class="truncate font-semibold">{{ $spkl->company->name ?? 'Mitra DU/DI' }}</span>
                </div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-white font-extrabold text-base shrink-0 shadow-inner">
                {{ substr(auth()->user()->name, 0, 1) }}
            </div>
        </div>
    </div>

    <!-- Papan Pengumuman & Siaran PKL -->
    <div class="space-y-2.5">
        <div class="flex items-center justify-between px-1">
            <div class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Pengumuman & Siaran</span>
            </div>
            <a href="{{ route('student.announcements') }}" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-0.5">
                <span>Lihat Semua</span>
                <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        @if(isset($announcements) && $announcements->count() > 0)
            <!-- Horizontal Carousel / Scrollable Cards -->
            <div class="flex gap-3 overflow-x-auto pb-1.5 -mx-4 px-4 custom-scrollbar snap-x snap-mandatory">
                @foreach($announcements as $ann)
                    <div onclick="openHomeAnnouncementModal({{ $ann->id }})" class="snap-start shrink-0 w-[295px] sm:w-[320px] bg-white rounded-3xl border border-slate-200/80 shadow-xs hover:shadow-md hover:border-indigo-300 transition-all cursor-pointer overflow-hidden p-4 flex flex-col justify-between group active:scale-[0.99]">
                        
                        <div>
                            <!-- Header Row: Badges -->
                            <div class="flex items-center justify-between gap-1.5 mb-2.5">
                                <div class="flex items-center gap-1.5 flex-wrap">
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
                                    <span class="text-[9px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-200/80 px-2 py-0.5 rounded-full truncate max-w-[120px]" title="{{ $ann->major->name }}">
                                        {{ $ann->major->name }}
                                    </span>
                                @else
                                    <span class="text-[9px] font-medium text-slate-400">
                                        Semua Jurusan
                                    </span>
                                @endif
                            </div>

                            <!-- Content Row: Text + Optional Right Thumbnail -->
                            <div class="flex items-start gap-3">
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-xs font-black text-slate-900 group-hover:text-indigo-600 transition-colors line-clamp-2 leading-snug">
                                        {{ $ann->title }}
                                    </h3>
                                    <p class="text-[11px] text-slate-500 line-clamp-2 mt-1 leading-relaxed font-medium">
                                        {{ Str::limit(strip_tags($ann->content), 80) }}
                                    </p>
                                </div>
                                @if($ann->image_path)
                                    <div class="relative group/thumb w-16 h-16 sm:w-18 sm:h-18 rounded-2xl bg-slate-100 overflow-hidden shrink-0 border border-slate-200/80 shadow-2xs cursor-zoom-in" onclick="event.stopPropagation(); openImageLightbox('{{ $ann->image_url }}')" title="Klik untuk memperbesar gambar">
                                        <img src="{{ $ann->image_url }}" alt="{{ $ann->title }}" class="w-full h-full object-cover group-hover/thumb:scale-110 transition-transform duration-300">
                                        <div class="absolute inset-0 bg-slate-950/20 opacity-0 group-hover/thumb:opacity-100 transition-opacity flex items-center justify-center text-white">
                                            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Footer: Author & Relative Time -->
                        <div class="pt-2.5 mt-2.5 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400">
                            <span class="font-bold text-slate-700 truncate max-w-[150px]">{{ $ann->author->name ?? 'Sekolah' }}</span>
                            <span class="font-medium shrink-0">{{ $ann->published_at ? $ann->published_at->diffForHumans() : $ann->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Placeholder empty announcement -->
            <div class="bg-white rounded-3xl p-4 border border-slate-200/80 shadow-2xs flex items-center space-x-3.5">
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h4 class="text-xs font-bold text-slate-900">Belum Ada Pengumuman Baru</h4>
                    <p class="text-[11px] text-slate-500 font-medium truncate mt-0.5">Semua informasi dan instruksi PKL akan muncul di sini.</p>
                </div>
            </div>
        @endif
    </div>

    <!-- 1. Card Status Presensi Hari Ini -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
        <div class="flex items-center justify-between mb-4">
            <div>
                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest block">Status Presensi Hari Ini</span>
                <p class="text-xs font-bold text-slate-800 mt-0.5">{{ \Carbon\Carbon::today()->isoFormat('dddd, D MMMM Y') }}</p>
            </div>

            @if($todayAttendance)
                <x-badge :variant="$todayAttendance->status" size="sm">
                    {{ strtoupper(str_replace('_', ' ', $todayAttendance->status)) }}
                </x-badge>
            @else
                <x-badge variant="neutral" size="sm">
                    Belum Absen
                </x-badge>
            @endif
        </div>

        <!-- Checkin & Checkout times -->
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="bg-slate-50/80 rounded-2xl p-3 border border-slate-100 text-center">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Check-In</span>
                <span class="text-sm font-extrabold text-slate-900 font-mono">
                    {{ $todayAttendance && $todayAttendance->checkin_time ? substr($todayAttendance->checkin_time, 0, 5) . ' WIB' : '--:--' }}
                </span>
            </div>
            <div class="bg-slate-50/80 rounded-2xl p-3 border border-slate-100 text-center">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Check-Out</span>
                <span class="text-sm font-extrabold text-slate-900 font-mono">
                    {{ $todayAttendance && $todayAttendance->checkout_time ? substr($todayAttendance->checkout_time, 0, 5) . ' WIB' : '--:--' }}
                </span>
            </div>
        </div>

        <!-- Primary Action Button (min 48px height) -->
        @if(!$todayAttendance)
            <a href="{{ route('student.attendance') }}#checkin-section" class="w-full min-h-[48px] py-3.5 px-4 rounded-2xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold text-xs tracking-wide shadow-md shadow-indigo-600/20 flex items-center justify-center space-x-2 transition-transform active:scale-[0.98]">
                <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Ambil Selfie & Check-In</span>
            </a>
        @elseif($todayAttendance && !$todayAttendance->checkout_time)
            <button onclick="document.getElementById('checkout-modal').classList.remove('hidden')" class="w-full min-h-[48px] py-3.5 px-4 rounded-2xl bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white font-bold text-xs tracking-wide shadow-md shadow-amber-500/20 flex items-center justify-center space-x-2 transition-transform active:scale-[0.98] cursor-pointer">
                <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                <span>Lakukan Check-Out Hari Ini</span>
            </button>
        @else
            <div class="w-full min-h-[48px] py-3 px-4 rounded-2xl bg-emerald-50 border border-emerald-200/80 text-emerald-800 font-bold text-xs flex items-center justify-center space-x-2">
                <svg class="w-4 h-4 text-emerald-600 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <span>Presensi Hari Ini Telah Lengkap</span>
            </div>
        @endif
    </div>

    <!-- 2. Permohonan Presensi Cepat -->
    <div>
        <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-2 px-1">Permohonan Presensi Cepat</p>
        <div class="grid grid-cols-4 gap-2">
            
            <button onclick="openRequestModal('izin')" class="flex flex-col items-center justify-center p-3 rounded-2xl bg-white border border-slate-200/80 shadow-2xs hover:border-sky-300 hover:shadow-xs transition-all cursor-pointer group active:scale-95">
                <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 border border-sky-100 flex items-center justify-center mb-1.5 group-hover:scale-105 transition-transform">
                    <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <span class="text-[11px] font-bold text-slate-800 group-hover:text-sky-700 transition-colors">Izin</span>
            </button>

            <button onclick="openRequestModal('sakit')" class="flex flex-col items-center justify-center p-3 rounded-2xl bg-white border border-slate-200/80 shadow-2xs hover:border-purple-300 hover:shadow-xs transition-all cursor-pointer group active:scale-95">
                <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center mb-1.5 group-hover:scale-105 transition-transform">
                    <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                </div>
                <span class="text-[11px] font-bold text-slate-800 group-hover:text-purple-700 transition-colors">Sakit</span>
            </button>

            <button onclick="openRequestModal('dinas_luar')" class="flex flex-col items-center justify-center p-3 rounded-2xl bg-white border border-slate-200/80 shadow-2xs hover:border-teal-300 hover:shadow-xs transition-all cursor-pointer group active:scale-95">
                <div class="w-9 h-9 rounded-xl bg-teal-50 text-teal-600 border border-teal-100 flex items-center justify-center mb-1.5 group-hover:scale-105 transition-transform">
                    <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <span class="text-[11px] font-bold text-slate-800 group-hover:text-teal-700 transition-colors">Dinas</span>
            </button>

            <button onclick="openRequestModal('luar_kantor')" class="flex flex-col items-center justify-center p-3 rounded-2xl bg-white border border-slate-200/80 shadow-2xs hover:border-indigo-300 hover:shadow-xs transition-all cursor-pointer group active:scale-95">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center mb-1.5 group-hover:scale-105 transition-transform">
                    <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                </div>
                <span class="text-[11px] font-bold text-slate-800 group-hover:text-indigo-700 transition-colors">WFH</span>
            </button>

        </div>
    </div>

    <!-- 3. Jurnal Hari Ini -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
        <div class="flex items-center justify-between mb-3">
            <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Jurnal Kegiatan Hari Ini</span>
            @if($todayJournal)
                <x-badge :variant="$todayJournal->status" size="xs">
                    {{ strtoupper($todayJournal->status) }}
                </x-badge>
            @else
                <x-badge variant="warning" size="xs">
                    Belum Diisi
                </x-badge>
            @endif
        </div>

        @if($todayJournal)
            <p class="text-xs text-slate-600 line-clamp-2 mb-3 bg-slate-50/80 p-3 rounded-xl border border-slate-100 leading-relaxed font-medium">
                {{ $todayJournal->activity_description }}
            </p>
            <a href="{{ route('student.journals') }}" class="inline-flex items-center text-xs font-bold text-indigo-600 hover:text-indigo-700">
                <span>Buka Detail Jurnal</span>
                <svg class="w-3.5 h-3.5 ml-1 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        @else
            <p class="text-xs text-slate-500 mb-3 leading-relaxed font-medium">
                Dokumentasikan aktivitas belajar dan praktik kerja industri Anda untuk hari ini.
            </p>
            <a href="{{ route('student.journals') }}" class="w-full py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs flex items-center justify-center space-x-2 transition-colors">
                <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span>Tulis Jurnal Harian Sekarang</span>
            </a>
        @endif
    </div>

    <!-- 4. Rekap Presensi Bulan Berjalan -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
        <div class="flex items-center justify-between mb-3">
            <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Rekap Bulan Berjalan</span>
            <span class="text-xs font-bold text-indigo-600">{{ \Carbon\Carbon::today()->isoFormat('MMMM Y') }}</span>
        </div>
        <div class="grid grid-cols-5 gap-2 text-center">
            <div class="bg-emerald-50/70 border border-emerald-100 rounded-2xl p-2.5">
                <span class="text-base font-black text-emerald-700 block">{{ $stats['hadir'] }}</span>
                <span class="text-[9px] font-bold text-emerald-800 uppercase block mt-0.5">Hadir</span>
            </div>
            <div class="bg-amber-50/70 border border-amber-100 rounded-2xl p-2.5">
                <span class="text-base font-black text-amber-700 block">{{ $stats['terlambat'] }}</span>
                <span class="text-[9px] font-bold text-amber-800 uppercase block mt-0.5">Late</span>
            </div>
            <div class="bg-sky-50/70 border border-sky-100 rounded-2xl p-2.5">
                <span class="text-base font-black text-sky-700 block">{{ $stats['izin'] }}</span>
                <span class="text-[9px] font-bold text-sky-800 uppercase block mt-0.5">Izin</span>
            </div>
            <div class="bg-purple-50/70 border border-purple-100 rounded-2xl p-2.5">
                <span class="text-base font-black text-purple-700 block">{{ $stats['sakit'] }}</span>
                <span class="text-[9px] font-bold text-purple-800 uppercase block mt-0.5">Sakit</span>
            </div>
            <div class="bg-rose-50/70 border border-rose-100 rounded-2xl p-2.5">
                <span class="text-base font-black text-rose-700 block">{{ $stats['alfa'] }}</span>
                <span class="text-[9px] font-bold text-rose-800 uppercase block mt-0.5">Alfa</span>
            </div>
        </div>
    </div>

</div>

<!-- Modal Check-Out -->
<div id="checkout-modal" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-end sm:items-center justify-center p-4">
    <div class="bg-white w-full max-w-sm rounded-3xl p-6 shadow-2xl max-h-[90vh] overflow-y-auto border border-slate-100 animate-in fade-in duration-150">
        <h3 class="text-base font-extrabold text-slate-900 tracking-tight mb-1">Check-Out Pekerjaan</h3>
        <p class="text-xs text-slate-500 mb-4 font-medium">Tuliskan ringkasan capaian kerja hari ini sebelum kepulangan.</p>
        <form action="{{ route('student.checkout') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Catatan Capaian (Maks 255 Karakter)</label>
                <textarea name="checkout_notes" rows="3" required maxlength="255"
                          placeholder="Contoh: Menyelesaikan perbaikan bug modul autentikasi dan merapikan dokumentasi..."
                          class="w-full text-xs p-3 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 font-medium leading-relaxed text-slate-800"></textarea>
            </div>
            <div class="flex items-center space-x-2 pt-1">
                <button type="button" onclick="document.getElementById('checkout-modal').classList.add('hidden')" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white text-xs font-bold shadow-md shadow-amber-500/20 cursor-pointer">
                    Kirim Check-Out
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Quick Request (Reusable Component) -->
<x-attendance-request-modal />

    let currentOpenAnnouncementId = null;
    const latestAnnouncementId = {{ $announcements->first()?->id ?? 'null' }};
    const currentUserId = {{ auth()->id() }};

    function populateModalData(data) {
        currentOpenAnnouncementId = data.id;
        document.getElementById('home-modal-title').textContent = data.title;
        document.getElementById('home-modal-content').textContent = data.content;
        document.getElementById('home-modal-author').textContent = data.author_name;
        document.getElementById('home-modal-target').textContent = data.target_scope;
        document.getElementById('home-modal-date').textContent = data.published_at;
        document.getElementById('home-modal-major-badge').textContent = data.major_name;

        const badgeSpan = document.getElementById('home-modal-category-badge');
        badgeSpan.className = `text-[10px] font-black uppercase tracking-wider px-2.5 py-0.5 rounded-full border ${getCategoryBadgeClasses(data.category)}`;
        badgeSpan.textContent = data.category_label;

        const imgContainer = document.getElementById('home-modal-image-container');
        const imgElem = document.getElementById('home-modal-image');
        if (data.image_url) {
            imgElem.src = data.image_url;
            imgContainer.classList.remove('hidden');
        } else {
            imgContainer.classList.add('hidden');
            imgElem.src = '';
        }
    }

    function openHomeAnnouncementModal(id, markSeen = false) {
        fetch(`/siswa/pengumuman/${id}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            populateModalData(data);
            document.getElementById('home-announcement-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            if (markSeen) {
                localStorage.setItem('vokatrack_seen_announcement_' + currentUserId, id);
            }
        })
        .catch(err => {
            console.error('Failed to load announcement details', err);
        });
    }

    function closeHomeAnnouncementModal(markSeen = true) {
        if (markSeen && currentOpenAnnouncementId) {
            localStorage.setItem('vokatrack_seen_announcement_' + currentUserId, currentOpenAnnouncementId);
        }
        document.getElementById('home-announcement-modal').classList.add('hidden');
        document.body.style.overflow = '';
    }

    function getCategoryBadgeClasses(cat) {
        switch(cat) {
            case 'penting': return 'bg-rose-50 text-rose-700 border-rose-200/80';
            case 'kegiatan': return 'bg-indigo-50 text-indigo-700 border-indigo-200/80';
            case 'akademik': return 'bg-teal-50 text-teal-700 border-teal-200/80';
            case 'libur': return 'bg-purple-50 text-purple-700 border-purple-200/80';
            case 'dudi': return 'bg-amber-50 text-amber-700 border-amber-200/80';
            default: return 'bg-sky-50 text-sky-700 border-sky-200/80';
        }
    }

    // Auto-popup pengumuman baru langsung muncul saat siswa membuka aplikasi!
    document.addEventListener('DOMContentLoaded', function() {
        if (latestAnnouncementId) {
            const storageKey = 'vokatrack_seen_announcement_' + currentUserId;
            const lastSeenId = localStorage.getItem(storageKey);
            
            // Jika pengumuman terbaru belum ditandai sudah dibaca, langsung munculkan popup!
            if (!lastSeenId || parseInt(lastSeenId) !== latestAnnouncementId) {
                setTimeout(() => {
                    openHomeAnnouncementModal(latestAnnouncementId, false);
                }, 350);
            }
        }

        // Pengecekan berkala real-time setiap 25 detik
        setInterval(checkRealtimeAnnouncement, 25000);
    });

    function checkRealtimeAnnouncement() {
        fetch('{{ route("student.announcements.latest") }}', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.has_announcement) {
                const storageKey = 'vokatrack_seen_announcement_' + currentUserId;
                const lastSeenId = localStorage.getItem(storageKey);
                if (!lastSeenId || parseInt(lastSeenId) !== data.id) {
                    populateModalData(data);
                    document.getElementById('home-announcement-modal').classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                }
            }
        })
        .catch(() => {});
    }
</script>

<!-- Modal Detail Pengumuman Beranda (Responsive Mobile & Centered Popup) -->
<div id="home-announcement-modal" onclick="if(event.target === this) closeHomeAnnouncementModal(true)" class="hidden fixed inset-0 z-50 bg-slate-950/75 backdrop-blur-md flex items-center justify-center p-3.5 sm:p-4 animate-in fade-in duration-200">
    <div class="bg-white w-full max-w-sm sm:max-w-md rounded-3xl shadow-2xl max-h-[86vh] sm:max-h-[90vh] flex flex-col border border-slate-100/90 overflow-hidden my-auto transform transition-all duration-200">
        
        <!-- Modal Sticky Header -->
        <div class="px-4 py-3 sm:px-5 sm:py-3.5 border-b border-slate-100 flex items-center justify-between gap-2 shrink-0 bg-white">
            <div class="flex items-center gap-1.5 flex-wrap min-w-0 flex-1">
                <span id="home-modal-category-badge"></span>
                <span id="home-modal-major-badge" class="text-[10px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-200/80 px-2.5 py-0.5 rounded-full truncate max-w-[130px] sm:max-w-[180px]"></span>
            </div>
            <button type="button" onclick="closeHomeAnnouncementModal(true)" title="Tutup" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 active:bg-slate-300 text-slate-500 hover:text-slate-800 flex items-center justify-center shrink-0 transition-colors cursor-pointer">
                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="overflow-y-auto px-4 py-4 sm:px-5 sm:py-4 space-y-3.5 custom-scrollbar flex-1">
            <div id="home-modal-image-container" class="hidden relative group/img rounded-2xl overflow-hidden bg-slate-100 border border-slate-200/80 shadow-2xs cursor-zoom-in" onclick="openImageLightbox(document.getElementById('home-modal-image').src)" title="Klik untuk membaca tulisan pada gambar">
                <img id="home-modal-image" src="" alt="Lampiran Pengumuman" class="w-full h-auto max-h-48 sm:max-h-60 object-contain mx-auto block group-hover/img:scale-105 transition-transform duration-300">
                
                <!-- Hover Overlay -->
                <div class="absolute inset-0 bg-slate-950/25 opacity-0 group-hover/img:opacity-100 transition-opacity flex items-center justify-center gap-1.5 text-white text-xs font-black pointer-events-none">
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
                <h3 id="home-modal-title" class="text-base sm:text-lg font-black text-slate-900 tracking-tight leading-snug break-words"></h3>
                
                <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-100 text-[11px] text-slate-500">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 rounded-xl bg-white p-0.5 text-white font-black text-xs flex items-center justify-center shrink-0 shadow-xs border border-slate-200">
                            <img src="{{ asset('images/logo.png') }}" alt="VokaTrack" class="w-full h-full object-contain">
                        </div>
                        <div class="min-w-0">
                            <span id="home-modal-author" class="font-bold text-slate-900 text-xs truncate block max-w-[150px] sm:max-w-[180px]"></span>
                            <span id="home-modal-target" class="text-[10px] text-slate-400 font-medium truncate block"></span>
                        </div>
                    </div>
                    <span id="home-modal-date" class="font-medium text-[10px] text-slate-400 shrink-0 text-right font-mono"></span>
                </div>
            </div>

            <div class="bg-slate-50/90 rounded-2xl p-4 border border-slate-100">
                <p id="home-modal-content" class="text-xs text-slate-700 whitespace-pre-line leading-relaxed font-medium break-words"></p>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-3.5 sm:p-4 bg-white border-t border-slate-100 shrink-0 flex flex-col gap-2">
            <button type="button" onclick="closeHomeAnnouncementModal(true)" class="w-full min-h-[46px] py-3 px-4 rounded-2xl bg-slate-900 hover:bg-slate-800 active:scale-[0.98] text-white text-xs font-black tracking-wide shadow-md shadow-slate-900/10 flex items-center justify-center transition-all cursor-pointer">
                Tutup Pengumuman
            </button>
        </div>
    </div>
</div>
@endsection
