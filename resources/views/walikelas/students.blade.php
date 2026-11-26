@extends('layouts.app')

@section('page_title', 'Siswa & Status PKL Rombel')
@section('page_subtitle', 'Daftar siswa, penempatan DU/DI, guru pembimbing, dan reset kata sandi kelas ampuannya')

@section('content')
<div class="w-full space-y-6">

    <x-page-header 
        title="Siswa & Status PKL Rombel" 
        subtitle="Pantau penempatan industri dan status akun siswa pada rombongan belajar yang Anda ampu."
        badge="WALI KELAS"
    />

    <x-card>
        <form method="GET" action="{{ route('walikelas.students') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="relative {{ $classes->count() > 1 ? '' : 'sm:col-span-2' }}">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama siswa, NISN, atau NIS..." 
                       class="w-full pl-9 pr-4 py-2.5 text-xs sm:text-sm rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            @if($classes->count() > 1)
                <div>
                    <select name="class_id" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                        <option value="">-- Semua Rombel Ampuan --</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->academic_year }})</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <select name="status" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                    <option value="">-- Semua Status PKL --</option>
                    <option value="plotted" {{ request('status') === 'plotted' ? 'selected' : '' }}>Sudah Terplotting</option>
                    <option value="unplotted" {{ request('status') === 'unplotted' ? 'selected' : '' }}>Belum Terplotting</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <x-button type="submit" variant="primary" size="md" class="w-full justify-center">Filter</x-button>
                @if(request()->hasAny(['search', 'status', 'class_id']))
                    <a href="{{ route('walikelas.students') }}" class="px-3 py-2.5 text-xs font-semibold text-slate-500 hover:text-slate-800">Reset</a>
                @endif
            </div>
        </form>
    </x-card>

    <x-card :title="'Daftar Peserta Didik (' . $students->total() . ' Siswa)'">
        @if($students->isEmpty())
            <x-empty-state title="Data Siswa Tidak Ditemukan" description="Tidak ada siswa yang sesuai dengan filter pencarian Anda." />
        @else
            <div class="overflow-x-auto -mx-6">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                            <th class="py-3 px-6">Identitas Siswa</th>
                            <th class="py-3 px-4">Rombel</th>
                            <th class="py-3 px-4">Mitra DU/DI</th>
                            <th class="py-3 px-4">Guru Pembimbing</th>
                            <th class="py-3 px-4 text-center">Status PKL</th>
                            <th class="py-3 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach($students as $s)
                            @php
                                $spkl = $s->activePkl;
                                $isPlotted = (bool) $spkl;
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3 px-6">
                                    <div class="font-bold text-slate-900">{{ $s->user?->name ?? 'Siswa' }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">NISN: {{ $s->nisn ?? '-' }} &bull; NIS: {{ $s->nis ?? '-' }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md font-semibold text-slate-700 bg-slate-100 text-[11px]">
                                        {{ $s->schoolClass?->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    @if($isPlotted && $spkl->company)
                                        <div class="font-semibold text-slate-900">{{ $spkl->company->name }}</div>
                                        <div class="text-[11px] text-slate-400 line-clamp-1">{{ $spkl->company->address }}</div>
                                    @else
                                        <span class="text-slate-400 italic text-xs">Belum ditentukan</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    @if($isPlotted && $spkl->teacher)
                                        <span class="font-semibold text-slate-900">{{ $spkl->teacher->user->name }}</span>
                                    @else
                                        <span class="text-slate-400 italic text-xs">Belum ditunjuk</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <x-badge :variant="$isPlotted ? 'emerald' : 'amber'" size="xs">
                                        {{ $isPlotted ? 'Aktif PKL' : 'Belum Plotting' }}
                                    </x-badge>
                                </td>
                                <td class="py-3 px-6 text-right">
                                    <form method="POST" action="{{ route('walikelas.students.reset-password', $s) }}" class="inline" onsubmit="var p = prompt('Kata sandi sementara siswa (min. 8 karakter). Kosongkan untuk dibuatkan otomatis:'); if (p === null) return false; this.new_password.value = p; return confirm('Reset kata sandi siswa ini? Siswa wajib menggantinya saat login berikutnya.');">
                                        @csrf
                                        <input type="hidden" name="new_password" value="">
                                        <button type="submit" class="py-1.5 px-2.5 rounded-xl border border-amber-300 bg-amber-50 hover:bg-amber-100 text-amber-900 font-bold text-xs cursor-pointer shadow-2xs transition-all">
                                            Reset Sandi
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pt-4 border-t border-slate-100">
                {{ $students->links() }}
            </div>
        @endif
    </x-card>

</div>
@endsection
