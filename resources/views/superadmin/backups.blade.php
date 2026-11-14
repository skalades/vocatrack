@extends('layouts.app')

@section('page_title', 'Pusat Cadangan Data & Pemulihan')
@section('page_subtitle', 'Pembuatan snapshot cadangan basis data platform dan ekspor arsip data sekolah (offboarding packager)')

@section('content')
<div class="w-full space-y-6 sm:space-y-8 animate-in fade-in duration-300">

    <!-- Enterprise Hero Header -->
    <x-page-header 
        title="Pusat Cadangan Data & Pemulihan (Disaster Recovery)" 
        subtitle="Buat snapshot instan seluruh tabel basis data sistem, unduh berkas arsip offboarding per sekolah mitra, dan amankan kelangsungan data."
        badge="DISASTER RECOVERY & BACKUP"
    >
        <form action="{{ route('superadmin.backups.create') }}" method="POST" onsubmit="return confirm('Buat snapshot cadangan data platform sekarang?')">
            @csrf
            <x-button type="submit" variant="primary" size="md" class="shadow-lg shadow-indigo-600/25">
                <svg class="w-4 h-4 mr-1.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span>Buat Snapshot Cadangan Baru</span>
            </x-button>
        </form>
    </x-page-header>

    <!-- Top KPI Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <x-stat-card 
            title="Total Berkas Cadangan" 
            :value="$totalBackupsCount" 
            subtitle="Snapshot tersimpan di storage"
            color="indigo"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-indigo-600 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Cadangan Terkini" 
            :value="!empty($backups) ? $backups[0]['size_formatted'] : '-'" 
            :subtitle="!empty($backups) ? $backups[0]['created_at']->diffForHumans() : 'Belum ada snapshot'"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-emerald-600 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Format Berkas Snapshot" 
            value="JSON Structured" 
            subtitle="Cross-platform & zero-dependency"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-blue-600 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card 
            title="Sekolah Terdaftar" 
            :value="$schools->count() . ' Tenant'" 
            subtitle="Siap untuk ekspor offboarding"
            color="teal"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-teal-600 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Section: Ekspor Mandiri Offboarding Sekolah -->
    <x-card>
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="max-w-xl">
                <h3 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">Paket Ekspor Arsip Mandiri Sekolah (Tenant Offboarding Packager)</h3>
                <p class="text-xs text-slate-500 font-medium mt-1">
                    Jika sebuah sekolah mitra menyelesaikan masa sewa dan ingin menarik seluruh arsip historisnya, Anda dapat men-generate satu berkas ZIP lengkap berisi rekapitulasi siswa, presensi, jurnal, nilai, dan profil sekolah.
                </p>
            </div>

            <div class="flex items-center gap-2 w-full md:w-auto">
                <select id="select-offboard-school" class="text-xs font-bold px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 w-full md:w-64">
                    <option value="">-- Pilih Sekolah Mitra --</option>
                    @foreach($schools as $sch)
                        <option value="{{ $sch->id }}">{{ $sch->name }} (NPSN: {{ $sch->npsn }})</option>
                    @endforeach
                </select>

                <button type="button" onclick="downloadOffboardingArchive()" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition-colors shrink-0 shadow-xs flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Unduh Arsip ZIP</span>
                </button>
            </div>
        </div>
    </x-card>

    <!-- Table of Snapshots -->
    <x-card noPadding>
        <div class="p-5 border-b border-slate-100">
            <h3 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">Daftar Snapshot Cadangan Basis Data</h3>
            <p class="text-xs text-slate-500 font-medium">Snapshot tersimpan secara aman di direktori <code>storage/app/backups/</code>.</p>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs min-w-[700px]">
                <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3 px-6 min-w-[280px]">Nama Berkas Cadangan</th>
                        <th class="py-3 px-4 min-w-[120px]">Ukuran Berkas</th>
                        <th class="py-3 px-4 min-w-[180px]">Waktu Pembuatan</th>
                        <th class="py-3 px-6 text-center min-w-[150px]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($backups as $b)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-6 font-mono font-bold text-slate-900 flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </div>
                                <span>{{ $b['filename'] }}</span>
                            </td>

                            <td class="py-3.5 px-4 font-mono font-bold text-slate-700">
                                {{ $b['size_formatted'] }}
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="font-bold text-slate-900 block font-mono">{{ $b['created_at']->format('d/m/Y H:i:s') }}</span>
                                <span class="text-[11px] text-slate-400 block">{{ $b['created_at']->diffForHumans() }}</span>
                            </td>

                            <td class="py-3.5 px-6 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('superadmin.backups.download', $b['filename']) }}" class="px-2.5 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-bold text-xs transition-colors inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        <span>Unduh</span>
                                    </a>

                                    <form action="{{ route('superadmin.backups.delete', $b['filename']) }}" method="POST" class="inline" onsubmit="return confirm('Hapus berkas cadangan {{ $b['filename'] }}?')">
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
                            <td colspan="4" class="py-12 text-center">
                                <x-empty-state 
                                    title="Belum Ada Snapshot Cadangan" 
                                    description="Tekan tombol 'Buat Snapshot Cadangan Baru' di bagian atas untuk mencadangkan basis data."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

</div>

<script>
    function downloadOffboardingArchive() {
        const select = document.getElementById('select-offboard-school');
        const schoolId = select.value;
        if (!schoolId) {
            alert('Silakan pilih sekolah mitra terlebih dahulu.');
            return;
        }

        const url = "{{ url('/super-admin/schools') }}/" + schoolId + "/offboard-export";
        window.location.href = url;
    }
</script>
@endsection
