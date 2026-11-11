@props([
    'isMobile' => false,
])

@php
    $user = auth()->user();
    $unreadCount = $user ? $user->unreadNotifications()->count() : 0;
    $recentNotifications = $user ? $user->notifications()->take(10)->get() : collect();
@endphp

@if($isMobile)
    <!-- Mobile Notification Bell Trigger -->
    <div class="relative inline-block">
        <button type="button" onclick="openMobileNotificationDrawer()" title="Notifikasi" class="relative p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-colors cursor-pointer">
            <svg class="w-5 h-5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            @if($unreadCount > 0)
                <span class="absolute top-0.5 right-0.5 flex h-4 min-w-4 px-1 items-center justify-center rounded-full bg-rose-500 text-[9px] font-black text-white shadow-xs animate-scale-in">
                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                </span>
            @endif
        </button>
    </div>

    <!-- Mobile Slide-Up / Bottom-Sheet Modal Drawer -->
    <div id="mobile-notification-drawer" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden transition-all flex flex-col justify-end">
        <div onclick="event.stopPropagation()" class="bg-white rounded-t-3xl max-h-[80vh] flex flex-col w-full shadow-2xl border-t border-slate-200 animate-in slide-in-from-bottom duration-200">
            <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </div>
                    <div>
                        <h3 class="text-xs font-extrabold text-slate-900">Pusat Pemberitahuan</h3>
                        <p class="text-[10px] text-slate-400 font-medium">{{ $unreadCount }} belum dibaca</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    @if($unreadCount > 0)
                        <form action="{{ route('notifications.read-all') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800">
                                Tandai Dibaca
                            </button>
                        </form>
                    @endif
                    <button type="button" onclick="closeMobileNotificationDrawer()" class="p-1 rounded-lg text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <div class="p-4 overflow-y-auto space-y-2.5 flex-1 divide-y divide-slate-100">
                @forelse($recentNotifications as $notif)
                    @php
                        $data = $notif->data;
                        $isRead = $notif->read_at !== null;
                        $color = $data['color'] ?? 'indigo';
                    @endphp
                    <div class="pt-2.5 first:pt-0">
                        <div class="p-3 rounded-2xl {{ $isRead ? 'bg-slate-50/60' : 'bg-indigo-50/40 border border-indigo-100/80' }} transition-colors space-y-1">
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-xs font-bold {{ $isRead ? 'text-slate-800' : 'text-indigo-950 font-black' }} leading-snug">
                                    {{ $data['title'] ?? 'Pemberitahuan' }}
                                </span>
                                <span class="text-[10px] text-slate-400 shrink-0 font-medium whitespace-nowrap">
                                    {{ $notif->created_at->diffForHumans() }}
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-600 leading-relaxed font-normal">
                                {{ $data['message'] ?? '' }}
                            </p>
                            @if(!empty($data['url']) && $data['url'] !== '#')
                                <div class="pt-1.5 flex items-center justify-between">
                                    <a href="{{ $data['url'] }}" onclick="markNotifRead('{{ $notif->id }}')" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                                        <span>Buka Detail</span>
                                        <svg class="w-3 h-3 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                    @if(!$isRead)
                                        <form action="{{ route('notifications.read', $notif->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-[10px] text-slate-400 hover:text-slate-600">
                                                Tandai Dibaca
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-slate-400 text-xs">
                        Belum ada notifikasi untuk Anda saat ini.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <script>
        function openMobileNotificationDrawer() {
            document.getElementById('mobile-notification-drawer').classList.remove('hidden');
        }
        function closeMobileNotificationDrawer() {
            document.getElementById('mobile-notification-drawer').classList.add('hidden');
        }
        function markNotifRead(id) {
            fetch('/notifications/' + id + '/read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });
        }
    </script>
@else
    <!-- Desktop Notification Center (Header Dropdown) -->
    <div class="relative" id="desktop-notification-container">
        <button type="button" onclick="toggleDesktopNotificationMenu()" class="relative p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors cursor-pointer" title="Notifikasi Sistem">
            <svg class="w-5 h-5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            @if($unreadCount > 0)
                <span class="absolute top-1 right-1 flex h-4 min-w-4 px-1 items-center justify-center rounded-full bg-rose-500 text-[9px] font-black text-white shadow-xs">
                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                </span>
            @endif
        </button>

        <!-- Dropdown Menu -->
        <div id="desktop-notification-menu" class="hidden absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-200/80 z-50 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h4 class="text-xs font-bold text-slate-900">Notifikasi</h4>
                    <p class="text-[10px] text-slate-400 font-medium">{{ $unreadCount }} belum dibaca</p>
                </div>
                @if($unreadCount > 0)
                    <form action="{{ route('notifications.read-all') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800">
                            Tandai Semua Dibaca
                        </button>
                    </form>
                @endif
            </div>

            <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                @forelse($recentNotifications as $notif)
                    @php
                        $data = $notif->data;
                        $isRead = $notif->read_at !== null;
                    @endphp
                    <div class="p-3.5 hover:bg-slate-50 transition-colors {{ $isRead ? '' : 'bg-indigo-50/30' }}">
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-xs font-bold {{ $isRead ? 'text-slate-800' : 'text-slate-900 font-extrabold' }}">
                                {{ $data['title'] ?? 'Pemberitahuan' }}
                            </span>
                            <span class="text-[10px] text-slate-400 whitespace-nowrap">
                                {{ $notif->created_at->diffForHumans() }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-600 mt-1 leading-relaxed">
                            {{ $data['message'] ?? '' }}
                        </p>
                        @if(!empty($data['url']) && $data['url'] !== '#')
                            <div class="mt-2 flex items-center justify-between">
                                <a href="{{ $data['url'] }}" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                                    <span>Tinjau</span>
                                    <svg class="w-3 h-3 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </a>
                                @if(!$isRead)
                                    <form action="{{ route('notifications.read', $notif->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-[10px] text-slate-400 hover:text-slate-600">
                                            Tandai dibaca
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-slate-400">
                        Belum ada notifikasi baru.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <script>
        function toggleDesktopNotificationMenu() {
            const menu = document.getElementById('desktop-notification-menu');
            if (menu) menu.classList.toggle('hidden');
        }

        document.addEventListener('click', function(e) {
            const container = document.getElementById('desktop-notification-container');
            const menu = document.getElementById('desktop-notification-menu');
            if (container && menu && !container.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });
    </script>
@endif
