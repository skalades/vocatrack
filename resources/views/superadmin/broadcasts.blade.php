@extends('layouts.app')

@section('page_title', 'Siaran Pengumuman Global')
@section('page_subtitle', 'Pengelolaan maklumat, notifikasi pembaruan, dan banner pengumuman serentak lintas sekolah')

@section('content')
<div class="w-full space-y-6 sm:space-y-8 animate-in fade-in duration-300">

    <!-- Enterprise Hero Header -->
    <x-page-header 
        title="Siaran Pengumuman Global (Platform Broadcast)" 
        subtitle="Kirimkan maklumat pembaruan fitur, pengumuman penting, atau jadwal libur nasional serentak ke seluruh antarmuka sekolah mitra."
        badge="GLOBAL BROADCAST MANAGER"
    >
        <x-button type="button" variant="primary" size="md" onclick="openAddBroadcastModal()" class="shadow-lg shadow-indigo-600/25">
            <svg class="w-4 h-4 mr-1.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span>Terbitkan Siaran Baru</span>
        </x-button>
    </x-page-header>

    <!-- Top KPI Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <x-stat-card 
            title="Total Siaran Pengumuman" 
            :value="$totalBroadcasts" 
            subtitle="Semua riwayat siaran"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-indigo-600 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Siaran Tayang Aktif" 
            :value="$activeBroadcasts" 
            subtitle="Tampil di banner antarmuka"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-emerald-600 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Target Sasaran Fleksibel" 
            value="Multi-Peran" 
            subtitle="Admin, Guru, DU/DI, Siswa"
            color="amber"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-amber-600 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Mekanisme Penutupan" 
            value="Dismissible" 
            subtitle="Dapat ditutup oleh pengguna"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-blue-600 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Table of Broadcasts -->
    <x-card noPadding>
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs min-w-[900px]">
                <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3 px-6 min-w-[240px]">Judul & Deskripsi Siaran</th>
                        <th class="py-3 px-4 min-w-[120px]">Tipe Pesan</th>
                        <th class="py-3 px-4 min-w-[160px]">Target Peran</th>
                        <th class="py-3 px-4 min-w-[160px]">Jadwal Penayangan</th>
                        <th class="py-3 px-4 text-center min-w-[100px]">Status</th>
                        <th class="py-3 px-6 text-center min-w-[160px]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($broadcasts as $bc)
                        @php
                            $typeColor = match($bc->type) {
                                'emergency' => 'rose',
                                'warning' => 'amber',
                                'update' => 'indigo',
                                default => 'blue',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-6">
                                <span class="font-bold text-slate-900 block text-sm">{{ $bc->title }}</span>
                                <p class="text-xs text-slate-500 line-clamp-2 mt-0.5 max-w-md">{{ $bc->content }}</p>
                            </td>

                            <td class="py-3.5 px-4">
                                <x-badge :color="$typeColor" size="sm">
                                    {{ strtoupper($bc->type) }}
                                </x-badge>
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded font-mono font-bold text-[10px] bg-slate-100 text-slate-700">
                                    {{ $bc->target_role === 'all' ? 'SEMUA PERAN' : strtoupper(str_replace('-', ' ', $bc->target_role)) }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-600">
                                @if($bc->starts_at || $bc->expires_at)
                                    <span>Mulai: {{ $bc->starts_at ? $bc->starts_at->format('d/m/Y') : 'Sekarang' }}</span>
                                    <span class="block text-slate-400">Sampai: {{ $bc->expires_at ? $bc->expires_at->format('d/m/Y') : 'Selamanya' }}</span>
                                @else
                                    <span class="text-slate-400 italic">Tayang Terus</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <form action="{{ route('superadmin.broadcasts.toggle', $bc->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 rounded-full text-[11px] font-bold transition-colors {{ $bc->is_active ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                                        {{ $bc->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </form>
                            </td>

                            <td class="py-3.5 px-6 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button type="button" 
                                            onclick="openEditBroadcastModal({{ json_encode($bc) }})"
                                            class="px-2.5 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-bold text-xs transition-colors">
                                        Edit
                                    </button>

                                    <form action="{{ route('superadmin.broadcasts.destroy', $bc->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus siaran pengumuman {{ $bc->title }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 font-bold text-xs transition-colors">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center">
                                <x-empty-state 
                                    title="Belum Ada Siaran Pengumuman Global" 
                                    description="Buat pengumuman baru untuk menyampaikan informasi penting ke seluruh pengguna aplikasi."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($broadcasts->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                <x-pagination :paginator="$broadcasts" />
            </div>
        @endif
    </x-card>

</div>

<!-- Modal Form Tambah / Edit Broadcast -->
<div id="modal-broadcast" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
            <div>
                <h3 class="text-sm font-bold" id="modal-broadcast-title">Terbitkan Siaran Pengumuman Baru</h3>
                <p class="text-[11px] text-slate-400">Siaran ini akan langsung muncul di antarmuka seluruh sekolah.</p>
            </div>
            <button type="button" onclick="closeBroadcastModal()" class="text-slate-400 hover:text-white p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="broadcast-form" action="{{ route('superadmin.broadcasts.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div id="method-container"></div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Judul Pengumuman</label>
                <input type="text" name="title" id="form-broadcast-title" required placeholder="Contoh: Pembaruan Sistem VokaTrack 1.4"
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 font-bold text-slate-900">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Isi Maklumat / Pesan Pengumuman</label>
                <textarea name="content" id="form-broadcast-content" rows="4" required placeholder="Tuliskan isi pengumuman secara jelas..."
                          class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 text-slate-800"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tipe Siaran</label>
                    <select name="type" id="form-broadcast-type" class="w-full text-xs font-bold px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 focus:bg-white">
                        <option value="info">Informasi (Biru)</option>
                        <option value="update">Pembaruan Fitur (Indigo)</option>
                        <option value="warning">Peringatan (Kuning)</option>
                        <option value="emergency">Penting / Darurat (Merah)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Target Sasaran</label>
                    <select name="target_role" id="form-broadcast-target" class="w-full text-xs font-bold px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 focus:bg-white">
                        <option value="all">Semua Pengguna</option>
                        <option value="admin-sekolah">Khusus Admin Sekolah</option>
                        <option value="guru-pembimbing">Khusus Guru Pembimbing</option>
                        <option value="siswa">Khusus Siswa PKL</option>
                        <option value="pembimbing-dudi">Khusus Pembimbing DU/DI</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Mulai Tayang (Opsional)</label>
                    <input type="datetime-local" name="starts_at" id="form-broadcast-starts"
                           class="w-full text-xs px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 focus:bg-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Selesai Tayang (Opsional)</label>
                    <input type="datetime-local" name="expires_at" id="form-broadcast-expires"
                           class="w-full text-xs px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 focus:bg-white">
                </div>
            </div>

            <div class="pt-2">
                <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_dismissible" id="form-broadcast-dismissible" value="1" checked class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Izinkan pengguna menutup / menyembunyikan pengumuman ini</span>
                </label>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeBroadcastModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition-colors shadow-xs">
                    Simpan & Publikasikan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddBroadcastModal() {
        document.getElementById('modal-broadcast-title').textContent = 'Terbitkan Siaran Pengumuman Baru';
        const form = document.getElementById('broadcast-form');
        form.action = "{{ route('superadmin.broadcasts.store') }}";
        document.getElementById('method-container').innerHTML = '';

        document.getElementById('form-broadcast-title').value = '';
        document.getElementById('form-broadcast-content').value = '';
        document.getElementById('form-broadcast-type').value = 'info';
        document.getElementById('form-broadcast-target').value = 'all';
        document.getElementById('form-broadcast-starts').value = '';
        document.getElementById('form-broadcast-expires').value = '';
        document.getElementById('form-broadcast-dismissible').checked = true;

        document.getElementById('modal-broadcast').classList.remove('hidden');
    }

    function openEditBroadcastModal(bc) {
        document.getElementById('modal-broadcast-title').textContent = 'Perbarui Siaran Pengumuman';
        const form = document.getElementById('broadcast-form');
        form.action = "{{ url('/super-admin/broadcasts') }}/" + bc.id;
        document.getElementById('method-container').innerHTML = '<input type="hidden" name="_method" value="PUT">';

        document.getElementById('form-broadcast-title').value = bc.title || '';
        document.getElementById('form-broadcast-content').value = bc.content || '';
        document.getElementById('form-broadcast-type').value = bc.type || 'info';
        document.getElementById('form-broadcast-target').value = bc.target_role || 'all';
        document.getElementById('form-broadcast-starts').value = bc.starts_at ? bc.starts_at.substring(0, 16) : '';
        document.getElementById('form-broadcast-expires').value = bc.expires_at ? bc.expires_at.substring(0, 16) : '';
        document.getElementById('form-broadcast-dismissible').checked = Boolean(bc.is_dismissible);

        document.getElementById('modal-broadcast').classList.remove('hidden');
    }

    function closeBroadcastModal() {
        document.getElementById('modal-broadcast').classList.add('hidden');
    }
</script>
@endsection
