@extends(auth()->user()->role === 'siswa' ? 'layouts.student-mobile' : 'layouts.app')

@section('title', 'Pusat Pemberitahuan — VokaTrack')
@section('page_title', 'Pusat Pemberitahuan')

@section('content')
<div class="space-y-6">

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Pusat Pemberitahuan</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Daftar semua notifikasi, status persetujuan, dan pengumuman terbaru untuk akun Anda.</p>
        </div>

        <div class="flex items-center gap-2">
            @if($unreadCount > 0)
                <form action="{{ route('notifications.read-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors border border-indigo-200 cursor-pointer">
                        <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span>Tandai Semua Dibaca</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Notification Cards List -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs divide-y divide-slate-100 overflow-hidden">
        @forelse($notifications as $notif)
            @php
                $data = $notif->data;
                $isRead = $notif->read_at !== null;
                $color = $data['color'] ?? 'indigo';
            @endphp
            <div class="p-4 sm:p-5 transition-colors hover:bg-slate-50/70 {{ $isRead ? 'bg-white' : 'bg-indigo-50/30' }}">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 {{ $isRead ? 'bg-slate-100 text-slate-500' : 'bg-indigo-100 text-indigo-700' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h4 class="text-xs sm:text-sm font-bold text-slate-900 leading-snug">
                                    {{ $data['title'] ?? 'Pemberitahuan' }}
                                </h4>
                                @if(!$isRead)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-100 text-indigo-700">
                                        Baru
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs sm:text-sm text-slate-600 font-normal leading-relaxed">
                                {{ $data['message'] ?? '' }}
                            </p>
                            <span class="text-[11px] text-slate-400 font-medium inline-block">
                                {{ $notif->created_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        @if(!empty($data['url']) && $data['url'] !== '#')
                            <a href="{{ $data['url'] }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-xl transition-colors">
                                <span>Lihat</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        @endif

                        @if(!$isRead)
                            <form action="{{ route('notifications.read', $notif->id) }}" method="POST">
                                @csrf
                                <button type="submit" title="Tandai telah dibaca" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer">
                                    <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="py-16 text-center">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 stroke-[1.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-slate-800">Tidak Ada Notifikasi</h3>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Kotak masuk pemberitahuan Anda bersih. Notifikasi baru akan muncul di sini secara otomatis.</p>
            </div>
        @endforelse
    </div>

</div>
@endsection
