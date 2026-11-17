@extends('layouts.app')

@section('page_title', 'Monitoring Jurnal Jurusan')
@section('page_subtitle', 'Pengawasan pengisian & verifikasi jurnal kerja harian jurusan ' . $major->name)

@section('content')
<div class="w-full space-y-6">

    <x-page-header 
        title="Monitoring Jurnal Harian Jurusan" 
        subtitle="Evaluasi aktivitas kerja lapangan dan kecepatan verifikasi jurnal siswa jurusan {{ $major->name }}."
        badge="MONITORING KAJUR"
    />

    <!-- Filter Form -->
    <x-card>
        <form method="GET" action="{{ route('kajur.journals') }}" class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Rombel Kelas</label>
                <select name="class_id" class="w-full text-xs sm:text-sm px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                    <option value="">-- Semua Kelas --</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Status Verifikasi</label>
                <select name="status" class="w-full text-xs sm:text-sm px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                    <option value="">-- Semua Status --</option>
                    <option value="pending_teacher" {{ request('status') === 'pending_teacher' ? 'selected' : '' }}>Menunggu Guru</option>
                    <option value="pending_dudi" {{ request('status') === 'pending_dudi' ? 'selected' : '' }}>Menunggu DU/DI</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui (Approved)</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak / Revisi</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Tanggal Kegiatan</label>
                <input type="date" name="date" value="{{ request('date') }}" 
                       class="w-full text-xs sm:text-sm px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-mono text-slate-900">
            </div>

            <div class="flex items-end">
                <x-button type="submit" variant="primary" size="md" class="w-full justify-center">
                    Terapkan Filter
                </x-button>
            </div>
        </form>
    </x-card>

    <!-- Journals Table -->
    <x-card :title="'Daftar Jurnal Kerja Siswa (' . $journals->total() . ' Jurnal Terdata)'">
        @if($journals->isEmpty())
            <x-empty-state 
                title="Tidak Ada Data Jurnal" 
                description="Belum ada catatan jurnal kerja yang sesuai dengan filter yang dipilih."
            />
        @else
            <div class="overflow-x-auto -mx-6">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                            <th class="py-3 px-6">Siswa & Kelas</th>
                            <th class="py-3 px-4">Tanggal Kerja</th>
                            <th class="py-3 px-6">Ringkasan Aktivitas / Pekerjaan</th>
                            <th class="py-3 px-4 text-center">Status Approval</th>
                            <th class="py-3 px-6">Guru Pembimbing</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach($journals as $j)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3 px-6">
                                    <div class="font-bold text-slate-900">{{ $j->student->name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $j->student->schoolClass?->name ?? '-' }}</div>
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-700 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($j->date)->isoFormat('D MMM Y') }}
                                </td>
                                <td class="py-3 px-6">
                                    <p class="text-slate-800 line-clamp-2 leading-relaxed font-medium">
                                        {{ $j->activity ?? $j->content ?? '-' }}
                                    </p>
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <x-badge :variant="match($j->status) {
                                        'approved' => 'emerald',
                                        'pending_teacher' => 'amber',
                                        'pending_dudi' => 'blue',
                                        default => 'rose'
                                    }" size="xs">
                                        {{ match($j->status) {
                                            'approved' => 'Disetujui',
                                            'pending_teacher' => 'Menunggu Guru',
                                            'pending_dudi' => 'Menunggu DU/DI',
                                            default => 'Ditolak'
                                        } }}
                                    </x-badge>
                                </td>
                                <td class="py-3 px-6 text-slate-600 whitespace-nowrap">
                                    {{ $j->student->studentPkl?->teacher?->user->name ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pt-4 border-t border-slate-100">
                {{ $journals->links() }}
            </div>
        @endif
    </x-card>

</div>
@endsection
