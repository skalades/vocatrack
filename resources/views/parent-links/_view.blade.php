@php
    $currentMajor = request('major_id', 'all');
    $currentClass = request('class_id', 'all');
    $currentScope = request('scope', 'bimbingan');
    $currentTokenStatus = request('token_status', 'all');
    $currentPortalStatus = request('portal_status', 'all');
    $currentSearch = request('search', '');
@endphp

<div class="w-full space-y-6">

    <!-- Hero Header Banner -->
    <x-page-header 
        :title="$roleType === 'admin' ? 'Kelola Link Portal Monitoring Orang Tua' : 'Link Monitoring Orang Tua Siswa'" 
        :subtitle="$roleType === 'admin' 
            ? 'Generate tautan pemantauan PKL untuk orang tua/wali siswa secara massal dan ekspor data per jurusan & rombel untuk dibagikan.' 
            : 'Generate dan kelola tautan pemantauan PKL untuk orang tua siswa bimbingan atau rombel binaan Anda, serta ekspor file untuk dibagikan ke paguyuban orang tua.'"
        :badge="$roleType === 'admin' ? 'MANAJEMEN SEKOLAH' : 'GURU PEMBIMBING'"
    >
        <button type="button" 
                onclick="document.getElementById('modal-wa-broadcast').classList.remove('hidden')" 
                class="inline-flex items-center justify-center font-bold rounded-xl transition-all shadow-xs cursor-pointer bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 px-3 py-1.5 text-xs">
            <svg class="w-4 h-4 mr-1.5 text-emerald-600 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <span>Format Broadcast WA</span>
        </button>

        <a href="{{ route($routePrefix . '.export', request()->query()) }}" 
           class="inline-flex items-center justify-center font-bold rounded-xl transition-all shadow-xs cursor-pointer bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 px-3 py-1.5 text-xs" 
           title="Unduh data tautan monitoring orang tua siswa dalam format spreadsheet CSV (kompatibel Excel)">
            <svg class="w-4 h-4 mr-1.5 text-slate-600 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            <span>Ekspor Spreadsheet (CSV)</span>
        </a>

        <x-button type="button" variant="primary" size="sm" onclick="openBatchGenerateModal()">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
            <span>+ Generate Link Massal</span>
        </x-button>
    </x-page-header>

    <!-- Top KPI Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card 
            title="Total Siswa Terdata" 
            :value="$stats['total']" 
            subtitle="Siswa dalam jangkauan filter"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Tautan Siap & Aktif" 
            :value="$stats['has_token']" 
            subtitle="Memiliki link monitoring aktif"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Belum Memiliki Tautan" 
            :value="$stats['missing_token']" 
            subtitle="Memerlukan pembuatan token"
            :color="$stats['missing_token'] > 0 ? 'amber' : 'emerald'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Portal Dinonaktifkan" 
            :value="$stats['inactive_portal']" 
            subtitle="Akses ditutup oleh sekolah/guru"
            :color="$stats['inactive_portal'] > 0 ? 'rose' : 'slate'"
        >
            <x-slot:icon>
                <svg class="w-6 h-6 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Filter Card: Jurusan, Rombel, Status, & Search -->
    <x-card>
        <form method="GET" action="{{ route($routePrefix) }}" id="parent-filter-form" class="space-y-4">
            
            @if($roleType === 'guru')
                <!-- Scope Bimbingan vs Seluruh Siswa Rombel -->
                <div class="flex items-center gap-4 p-3 bg-slate-50 border border-slate-200/80 rounded-xl text-xs font-semibold text-slate-700">
                    <span class="text-slate-500 font-bold uppercase tracking-wider text-[11px]">Cakupan Data:</span>
                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" name="scope" value="bimbingan" {{ $currentScope === 'bimbingan' ? 'checked' : '' }} onchange="this.form.submit()" class="text-indigo-600 focus:ring-indigo-500">
                        <span>Hanya Siswa Bimbingan Saya</span>
                    </label>
                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" name="scope" value="all" {{ $currentScope === 'all' ? 'checked' : '' }} onchange="this.form.submit()" class="text-indigo-600 focus:ring-indigo-500">
                        <span>Semua Siswa Sekolah (Wali Kelas / Kajur)</span>
                    </label>
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <!-- Dropdown Jurusan -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Jurusan</label>
                    <select name="major_id" id="filter-major" onchange="onMajorChange()" class="w-full text-xs font-semibold py-2 px-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all">
                        <option value="all">Semua Jurusan</option>
                        @foreach($majors as $m)
                            <option value="{{ $m->id }}" {{ $currentMajor == $m->id ? 'selected' : '' }}>
                                {{ $m->name }} ({{ $m->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Dropdown Rombel / Kelas -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Rombel / Kelas</label>
                    <select name="class_id" id="filter-class" class="w-full text-xs font-semibold py-2 px-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all">
                        <option value="all" data-major="all">Semua Rombel</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" data-major="{{ $c->major_id }}" {{ $currentClass == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Token -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Status Link</label>
                    <select name="token_status" class="w-full text-xs font-semibold py-2 px-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all">
                        <option value="all" {{ $currentTokenStatus === 'all' ? 'selected' : '' }}>Semua Status</option>
                        <option value="has_token" {{ $currentTokenStatus === 'has_token' ? 'selected' : '' }}>Sudah Memiliki Link</option>
                        <option value="missing" {{ $currentTokenStatus === 'missing' ? 'selected' : '' }}>Belum Memiliki Link</option>
                    </select>
                </div>

                <!-- Status Portal Aktif -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Akses Portal</label>
                    <select name="portal_status" class="w-full text-xs font-semibold py-2 px-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all">
                        <option value="all" {{ $currentPortalStatus === 'all' ? 'selected' : '' }}>Semua Akses</option>
                        <option value="active" {{ $currentPortalStatus === 'active' ? 'selected' : '' }}>Portal Aktif</option>
                        <option value="inactive" {{ $currentPortalStatus === 'inactive' ? 'selected' : '' }}>Portal Dinonaktifkan</option>
                    </select>
                </div>

                <!-- Search Input -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Cari Siswa</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ $currentSearch }}" placeholder="Nama, NISN, NIS..." 
                               class="w-full text-xs font-semibold pl-3 pr-8 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all">
                        @if($currentSearch)
                            <a href="{{ route($routePrefix, array_merge(request()->except('search'))) }}" class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 text-xs font-bold">✕</a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Action buttons: Filter & Reset -->
            <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                <span class="text-slate-400 text-[11px]">
                    Menampilkan hasil terfilter secara terurut berdasarkan Rombel dan Nama Siswa.
                </span>
                <div class="flex items-center gap-2">
                    <a href="{{ route($routePrefix) }}" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold transition-colors">
                        Reset Filter
                    </a>
                    <button type="submit" class="px-4 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold transition-colors shadow-xs">
                        Terapkan Filter
                    </button>
                </div>
            </div>
        </form>
    </x-card>

    <!-- Table Card -->
    <x-card noPadding>
        <!-- Info Bar -->
        <div class="p-4 sm:p-5 bg-white border-b border-slate-100 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-700 font-bold">
                    Daftar Siswa ({{ $students->total() }} Siswa Terdata)
                </span>
                <span class="text-[11px] px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 font-medium hidden md:inline">
                    Halaman {{ $students->currentPage() }} dari {{ $students->lastPage() }}
                </span>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-[11px] text-slate-400 font-medium">
                    💡 Klik tombol "Salin" untuk menyalin tautan, atau gunakan "Pesan WA" untuk mengirim format broadcast.
                </span>
            </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs min-w-[1080px]" id="parent-links-table">
                <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 w-10 text-center">
                            <input type="checkbox" id="master-checkbox-parent" onchange="toggleAllCheckboxes(this)" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                        </th>
                        <th class="py-3.5 px-4 min-w-[200px]">Identitas Siswa</th>
                        <th class="py-3.5 px-4 min-w-[150px]">Rombel & Jurusan</th>
                        <th class="py-3.5 px-4 min-w-[180px]">Penempatan PKL</th>
                        <th class="py-3.5 px-4 min-w-[100px] text-center">Status Akses</th>
                        <th class="py-3.5 px-4 min-w-[300px]">Tautan Monitoring Orang Tua</th>
                        <th class="py-3.5 px-4 text-right table-sticky-right min-w-[150px]">Aksi Cepat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium" id="parent-links-tbody">
                    @forelse($students as $st)
                        @php
                            $spkl = $st->activePkl;
                            $hasToken = !empty($st->parent_token);
                            $waMessage = app(\App\Services\ParentMonitoringService::class)->buildWhatsAppMessage($st);
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors student-parent-row group" data-student-id="{{ $st->id }}">
                            <!-- Checkbox Row -->
                            <td class="py-3 px-4 text-center">
                                <input type="checkbox" value="{{ $st->id }}" class="bulk-parent-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" onchange="updateBatchBarState()">
                            </td>

                            <!-- Identitas Siswa -->
                            <td class="py-3 px-4 min-w-[200px]">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-700 font-black text-xs flex items-center justify-center shrink-0">
                                        {{ substr($st->user->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <span class="font-bold text-slate-900 block truncate text-xs" title="{{ $st->user->name }}">
                                            {{ $st->user->name }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 font-mono block">
                                            NISN: {{ $st->nisn ?? '-' }} &bull; NIS: {{ $st->nis ?? '-' }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Rombel & Jurusan -->
                            <td class="py-3 px-4 min-w-[150px]">
                                <span class="font-bold text-slate-900 block">{{ $st->class->name ?? '-' }}</span>
                                <span class="text-[11px] text-slate-500 font-medium block truncate" title="{{ $st->class->major->name ?? '-' }}">
                                    {{ $st->class->major->name ?? '-' }}
                                </span>
                            </td>

                            <!-- Penempatan PKL -->
                            <td class="py-3 px-4 min-w-[180px]">
                                @if($spkl)
                                    <span class="font-bold text-slate-800 block truncate text-[11px]" title="{{ $spkl->company->name }}">
                                        🏢 {{ $spkl->company->name }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 block truncate">
                                        Pembimbing: {{ $spkl->teacher->user->name ?? 'Belum Diplot' }}
                                    </span>
                                @else
                                    <span class="text-[11px] text-slate-400 italic">Belum plotting PKL</span>
                                @endif
                            </td>

                            <!-- Status Akses Portal -->
                            <td class="py-3 px-4 text-center min-w-[100px]">
                                @if(!$hasToken)
                                    <x-badge variant="warning" size="xs">Belum Ada Link</x-badge>
                                @elseif($st->is_parent_portal_active)
                                    <x-badge variant="success" size="xs">Aktif</x-badge>
                                @else
                                    <x-badge variant="danger" size="xs">Dinonaktifkan</x-badge>
                                @endif
                            </td>

                            <!-- Tautan Monitoring Orang Tua -->
                            <td class="py-3 px-4 min-w-[300px]">
                                @if($hasToken)
                                    <div class="flex items-center gap-1.5">
                                        <div class="relative flex-1">
                                            <input type="text" readonly value="{{ $st->parent_monitoring_url }}" 
                                                   id="input-token-{{ $st->id }}"
                                                   class="w-full text-[11px] font-mono py-1 px-2.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-600 select-all focus:outline-none">
                                        </div>
                                        <button type="button" 
                                                onclick="copyInputText('input-token-{{ $st->id }}', this)" 
                                                class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-[11px] font-bold border border-indigo-200 transition-colors inline-flex items-center gap-1 shrink-0 cursor-pointer" 
                                                title="Salin Tautan Monitoring">
                                            <svg class="w-3.5 h-3.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                            <span class="copy-label">Salin</span>
                                        </button>
                                        <a href="{{ $st->parent_monitoring_url }}" target="_blank" 
                                           class="p-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 transition-colors shrink-0" 
                                           title="Buka Portal Monitoring di Tab Baru">
                                            <svg class="w-3.5 h-3.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-amber-600 font-medium italic">Link belum digenerate</span>
                                        <form action="{{ route($routePrefix . '.regenerate', $st) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="px-2 py-0.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-[10px] font-bold shadow-2xs">
                                                + Buat Link
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </td>

                            <!-- Aksi Cepat Row -->
                            <td class="py-3 px-4 text-right whitespace-nowrap space-x-1 table-sticky-right">
                                <!-- Tombol Salin Pesan WhatsApp -->
                                @if($hasToken)
                                    <button type="button" 
                                            onclick="copyTextToClipboard({{ json_encode($waMessage) }}, this)"
                                            class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 transition-colors inline-flex items-center gap-1 cursor-pointer" 
                                            title="Salin Pesan Format WhatsApp Siap Kirim ke Orang Tua">
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.174.086.275.072.376-.044.101-.116.433-.506.549-.68.116-.173.231-.145.39-.086s1.011.477 1.184.564.289.13.332.202c.043.073.043.419-.101.824z"/>
                                        </svg>
                                        <span class="text-[10px] font-bold">Pesan WA</span>
                                    </button>
                                @endif

                                <!-- Regenerate Token Single -->
                                @if($hasToken)
                                    <form action="{{ route($routePrefix . '.regenerate', $st) }}" method="POST" class="inline" onsubmit="return confirm('Buat ulang tautan untuk {{ $st->user->name }}? Tautan lama tidak akan dapat dibuka lagi.')">
                                        @csrf
                                        <button type="submit" class="p-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200 transition-colors inline-flex items-center cursor-pointer" title="Perbarui Token (Jika Link Bocor)">
                                            <svg class="w-3.5 h-3.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        </button>
                                    </form>

                                    <!-- Toggle Active Single -->
                                    <form action="{{ route($routePrefix . '.toggle', $st) }}" method="POST" class="inline">
                                        @csrf
                                        @if($st->is_parent_portal_active)
                                            <button type="submit" class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 transition-colors inline-flex items-center cursor-pointer" title="Nonaktifkan Akses Portal untuk Siswa Ini">
                                                <svg class="w-3.5 h-3.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            </button>
                                        @else
                                            <button type="submit" class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-200 transition-colors inline-flex items-center cursor-pointer" title="Aktifkan Kembali Akses Portal">
                                                <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            </button>
                                        @endif
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12">
                                <x-empty-state 
                                    title="Tidak Ada Data Siswa Ditemukan" 
                                    description="Tidak ada data siswa yang cocok dengan filter atau kata kunci pencarian yang diterapkan saat ini."
                                >
                                    <x-slot:action>
                                        <a href="{{ route($routePrefix) }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-colors">
                                            Reset Semua Filter
                                        </a>
                                    </x-slot:action>
                                </x-empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($students->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $students->links() }}
            </div>
        @endif
    </x-card>
</div>

<!-- Floating Sticky Batch Action Bar -->
<div id="batch-parent-action-bar" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 transition-all duration-300 transform translate-y-28 opacity-0 pointer-events-none w-[94%] max-w-3xl">
    <div class="bg-slate-900/95 backdrop-blur-md text-white px-5 py-3.5 rounded-2xl shadow-2xl border border-slate-700/80 flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="flex items-center space-x-3">
            <span class="flex h-3 w-3 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
            </span>
            <span class="text-xs font-bold text-slate-100">
                <span id="batch-selected-count">0</span> siswa dipilih
            </span>
        </div>

        <div class="flex items-center flex-wrap gap-2 w-full sm:w-auto justify-end">
            <button type="button" onclick="deselectAllCheckboxes()" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold transition-colors">
                Batal
            </button>
            <button type="button" onclick="submitBatchToggle(1)" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition-colors flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <span>Aktifkan Portal</span>
            </button>
            <button type="button" onclick="submitBatchToggle(0)" class="px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-sm transition-colors flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                <span>Nonaktifkan</span>
            </button>
            <button type="button" onclick="openBatchGenerateModal(true)" class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm transition-colors flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                <span>Generate Terpilih</span>
            </button>
            <button type="button" onclick="exportSelectedStudents()" class="px-3 py-1.5 rounded-xl bg-slate-700 hover:bg-slate-600 text-white text-xs font-bold shadow-sm transition-colors flex items-center gap-1.5" title="Ekspor siswa terpilih ke CSV">
                <svg class="w-3.5 h-3.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span>Ekspor CSV</span>
            </button>
        </div>
    </div>
</div>

<!-- Modal: Generate Link Massal -->
<div id="modal-batch-generate" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs hidden p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-100 animate-in fade-in zoom-in duration-200">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-700 flex items-center justify-center">
                    <svg class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Generate Tautan Monitoring Massal</h3>
                    <p class="text-[11px] text-slate-400">Buat atau perbarui tautan monitoring orang tua dalam jumlah banyak</p>
                </div>
            </div>
            <button type="button" onclick="closeBatchGenerateModal()" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route($routePrefix . '.batch-generate') }}" method="POST" id="form-batch-generate" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="major_id" value="{{ $currentMajor }}">
            <input type="hidden" name="class_id" value="{{ $currentClass }}">
            @if($roleType === 'guru')
                <input type="hidden" name="scope" value="{{ $currentScope }}">
            @endif

            <!-- Target Cakupan -->
            <div class="space-y-2">
                <label class="block text-xs font-bold text-slate-700">Pilih Sasaran Siswa:</label>
                
                <div class="space-y-2">
                    <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition-colors" id="label-mode-selected">
                        <input type="radio" name="mode" value="selected" id="radio-mode-selected" class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                        <div>
                            <span class="block text-xs font-bold text-slate-800">Hanya Siswa Terpilih</span>
                            <span class="block text-[11px] text-slate-500" id="text-mode-selected">Generate untuk siswa yang dicentang saat ini (<span id="modal-selected-count">0</span> siswa)</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition-colors" id="label-mode-filter">
                        <input type="radio" name="mode" value="filter" id="radio-mode-filter" checked class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                        <div>
                            <span class="block text-xs font-bold text-slate-800">Seluruh Siswa pada Filter Aktif Saat Ini</span>
                            <span class="block text-[11px] text-slate-500">
                                Sesuai filter: 
                                <strong>{{ $currentMajor !== 'all' ? 'Jurusan Terpilih' : 'Semua Jurusan' }}</strong> &bull; 
                                <strong>{{ $currentClass !== 'all' ? 'Rombel Terpilih' : 'Semua Rombel' }}</strong>
                            </span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Opsi Kebijakan Regenerate -->
            <div class="space-y-2 pt-2 border-t border-slate-100">
                <label class="block text-xs font-bold text-slate-700">Kebijakan Pembuatan Link:</label>

                <div class="space-y-2">
                    <label class="flex items-start gap-3 p-3 rounded-xl bg-emerald-50/60 border border-emerald-200/80 cursor-pointer">
                        <input type="radio" name="force_regenerate" value="0" checked class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="block text-xs font-bold text-emerald-900">Hanya Buat untuk Siswa yang Belum Memiliki Link</span>
                            <span class="block text-[11px] text-emerald-700">Siswa yang sudah memiliki tautan monitoring tidak akan diubah (Aman & Tidak merusak link yang sudah dibagikan).</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-3 rounded-xl bg-amber-50/60 border border-amber-200/80 cursor-pointer">
                        <input type="radio" name="force_regenerate" value="1" class="mt-0.5 text-amber-600 focus:ring-amber-500">
                        <div>
                            <span class="block text-xs font-bold text-amber-900">Regenerate / Perbarui SEMUA Tautan Baru</span>
                            <span class="block text-[11px] text-amber-700">Semua siswa sasaran akan dibuatkan tautan acak baru. Tautan lama yang sudah pernah dibagikan akan hangus secara permanen.</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Tempat Penampung ID Terpilih -->
            <div id="batch-generate-student-ids"></div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-3">
                <button type="button" onclick="closeBatchGenerateModal()" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm transition-colors">
                    Mulai Generate Massal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Format Broadcast WhatsApp -->
<div id="modal-wa-broadcast" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs hidden p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden border border-slate-100 animate-in fade-in zoom-in duration-200">
        <div class="px-6 py-4 bg-emerald-50 border-b border-emerald-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center shadow-xs">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.174.086.275.072.376-.044.101-.116.433-.506.549-.68.116-.173.231-.145.39-.086s1.011.477 1.184.564.289.13.332.202c.043.073.043.419-.101.824z"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Template Pesan Broadcast WhatsApp Orang Tua</h3>
                    <p class="text-[11px] text-slate-500">Format pesan ramah siap dibagikan ke paguyuban WhatsApp rombel</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modal-wa-broadcast').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="p-6 space-y-4">
            <p class="text-xs text-slate-600">
                Berikut adalah template pesan standar yang dapat Anda salin dan bagikan secara personal atau melalui pesan siaran ke nomor WhatsApp orang tua/wali siswa:
            </p>

            <div class="relative">
                <textarea id="wa-template-box" rows="8" readonly class="w-full text-xs font-mono p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 leading-relaxed focus:outline-none">Yth. Bapak/Ibu Orang Tua/Wali dari [Nama Siswa] ([Rombel] - [Jurusan]),

Berikut kami sampaikan tautan resmi Portal Pemantauan Aktivitas PKL (Praktik Kerja Lapangan) putra/putri Anda:
👉 [Tautan Monitoring Orang Tua]

Melalui tautan di atas, Bapak/Ibu dapat memantau presensi harian siswa, status jurnal kegiatan industri, serta rekap kehadiran secara langsung tanpa perlu login atau memasukkan kata sandi.

Terima kasih atas bimbingan dan kerja samanya.</textarea>
                <button type="button" 
                        onclick="copyInputText('wa-template-box', this)" 
                        class="absolute top-2.5 right-2.5 px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold shadow-xs transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    <span class="copy-label">Salin Template</span>
                </button>
            </div>

            <div class="p-3.5 bg-amber-50/80 border border-amber-200 rounded-xl text-amber-800 text-[11px] space-y-1">
                <span class="font-bold flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Tips Praktis Pembagian Link:
                </span>
                <p>
                    Anda juga dapat mengekspor spreadsheet CSV melalui tombol <strong>Ekspor Spreadsheet (CSV)</strong> di bagian atas. File tersebut sudah berisi kolom <em>Pesan Siap Kirim WhatsApp</em> yang telah dipersonalisasi dengan nama dan link masing-masing siswa untuk kemudahan copy-paste per baris!
                </p>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
                <button type="button" onclick="document.getElementById('modal-wa-broadcast').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Batch Toggle Form -->
<form id="form-batch-toggle" action="{{ route($routePrefix . '.batch-toggle') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="is_active" id="batch-toggle-is-active" value="1">
    @if($roleType === 'guru')
        <input type="hidden" name="scope" value="{{ $currentScope }}">
    @endif
    <div id="batch-toggle-student-ids"></div>
</form>

<!-- Toast Feedback Notification -->
<div id="copy-toast" class="fixed bottom-6 right-6 z-50 bg-slate-900 text-white text-xs font-semibold px-4 py-2.5 rounded-xl shadow-xl border border-slate-700 transition-all duration-300 opacity-0 pointer-events-none transform translate-y-3 flex items-center gap-2">
    <svg class="w-4 h-4 text-emerald-400 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
    <span id="copy-toast-msg">Teks berhasil disalin ke clipboard!</span>
</div>

<script>
    // Sinkronisasi otomatis filter Rombel berdasarkan Jurusan yang dipilih
    function onMajorChange() {
        const majorSelect = document.getElementById('filter-major');
        const classSelect = document.getElementById('filter-class');
        if (!majorSelect || !classSelect) return;

        const selectedMajor = majorSelect.value;
        const options = classSelect.querySelectorAll('option');

        options.forEach(opt => {
            const optMajor = opt.getAttribute('data-major');
            if (opt.value === 'all') {
                opt.style.display = '';
                return;
            }
            if (selectedMajor === 'all' || optMajor === selectedMajor) {
                opt.style.display = '';
            } else {
                opt.style.display = 'none';
            }
        });

        // Jika opsi yang sedang terpilih tersembunyi, kembalikan ke 'all'
        const currentSelected = classSelect.querySelector('option:checked');
        if (currentSelected && currentSelected.style.display === 'none') {
            classSelect.value = 'all';
        }
    }

    // Jalankan sekali saat DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function() {
        onMajorChange();
    });

    // Checkbox and Batch Bar Management
    function getCheckedBoxes() {
        return Array.from(document.querySelectorAll('input.bulk-parent-checkbox:checked'));
    }

    function updateBatchBarState() {
        const checked = getCheckedBoxes();
        const count = checked.length;
        const bar = document.getElementById('batch-parent-action-bar');
        const countSpan = document.getElementById('batch-selected-count');
        const modalCountSpan = document.getElementById('modal-selected-count');

        if (countSpan) countSpan.innerText = count;
        if (modalCountSpan) modalCountSpan.innerText = count;

        if (count > 0) {
            bar.classList.remove('translate-y-28', 'opacity-0', 'pointer-events-none');
            bar.classList.add('translate-y-0', 'opacity-100', 'pointer-events-auto');
        } else {
            bar.classList.remove('translate-y-0', 'opacity-100', 'pointer-events-auto');
            bar.classList.add('translate-y-28', 'opacity-0', 'pointer-events-none');
        }
    }

    function toggleAllCheckboxes(masterCb) {
        document.querySelectorAll('input.bulk-parent-checkbox').forEach(cb => {
            cb.checked = masterCb.checked;
        });
        updateBatchBarState();
    }

    function deselectAllCheckboxes() {
        document.querySelectorAll('input.bulk-parent-checkbox').forEach(cb => cb.checked = false);
        const master = document.getElementById('master-checkbox-parent');
        if (master) master.checked = false;
        updateBatchBarState();
    }

    // Batch Toggle Active / Inactive
    function submitBatchToggle(isActive) {
        const checked = getCheckedBoxes();
        if (checked.length === 0) return;

        const actionText = isActive === 1 ? 'mengaktifkan' : 'menonaktifkan';
        if (!confirm('Apakah Anda yakin ingin ' + actionText + ' akses portal orang tua untuk ' + checked.length + ' siswa terpilih?')) {
            return;
        }

        const form = document.getElementById('form-batch-toggle');
        const statusInput = document.getElementById('batch-toggle-is-active');
        const container = document.getElementById('batch-toggle-student-ids');

        statusInput.value = isActive;
        container.innerHTML = '';

        checked.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'student_ids[]';
            input.value = cb.value;
            container.appendChild(input);
        });

        form.submit();
    }

    // Modal Batch Generate Open & Close
    function openBatchGenerateModal(forceSelected = false) {
        const checked = getCheckedBoxes();
        const modal = document.getElementById('modal-batch-generate');
        const radioSelected = document.getElementById('radio-mode-selected');
        const radioFilter = document.getElementById('radio-mode-filter');
        const labelSelected = document.getElementById('label-mode-selected');

        if (checked.length > 0) {
            radioSelected.disabled = false;
            labelSelected.classList.remove('opacity-50', 'pointer-events-none');
            if (forceSelected) {
                radioSelected.checked = true;
            }
        } else {
            radioSelected.disabled = true;
            labelSelected.classList.add('opacity-50', 'pointer-events-none');
            radioFilter.checked = true;
        }

        // Siapkan input student_ids tersembunyi
        const container = document.getElementById('batch-generate-student-ids');
        container.innerHTML = '';
        checked.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'student_ids[]';
            input.value = cb.value;
            container.appendChild(input);
        });

        modal.classList.remove('hidden');
    }

    function closeBatchGenerateModal() {
        document.getElementById('modal-batch-generate').classList.add('hidden');
    }

    // Export Selected Students Only
    function exportSelectedStudents() {
        const checked = getCheckedBoxes();
        if (checked.length === 0) return;

        const exportUrl = new URL("{{ route($routePrefix . '.export') }}", window.location.origin);
        checked.forEach(cb => {
            exportUrl.searchParams.append('student_ids[]', cb.value);
        });

        window.location.href = exportUrl.toString();
    }

    // Clipboard Copy Helper with Feedback
    function copyInputText(elementId, btnElement) {
        const el = document.getElementById(elementId);
        if (!el) return;

        copyTextToClipboard(el.value, btnElement);
    }

    function copyTextToClipboard(text, btnElement) {
        if (!text) return;

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                showCopyFeedback(btnElement);
            }).catch(() => {
                fallbackExecCopy(text, btnElement);
            });
        } else {
            fallbackExecCopy(text, btnElement);
        }
    }

    function fallbackExecCopy(text, btnElement) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            showCopyFeedback(btnElement);
        } catch (err) {
            alert('Gagal menyalin teks. Silakan salin secara manual.');
        }
        document.body.removeChild(textArea);
    }

    function showCopyFeedback(btnElement) {
        // Update label tombol jika ada
        if (btnElement) {
            const label = btnElement.querySelector('.copy-label');
            if (label) {
                const original = label.innerText;
                label.innerText = 'Tersalin!';
                setTimeout(() => { label.innerText = original; }, 2000);
            }
        }

        // Tampilkan floating toast notification
        const toast = document.getElementById('copy-toast');
        if (toast) {
            toast.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-3');
            toast.classList.add('opacity-100', 'translate-y-0');
            setTimeout(() => {
                toast.classList.remove('opacity-100', 'translate-y-0');
                toast.classList.add('opacity-0', 'pointer-events-none', 'translate-y-3');
            }, 2500);
        }
    }
</script>
