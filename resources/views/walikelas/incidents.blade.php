@extends('layouts.app')

@section('page_title', 'Kendala & Pembinaan Rombel')
@section('page_subtitle', 'Pantau laporan kendala kerja dan SOS siswa pada kelas yang Anda ampu')

@section('content')
<div class="w-full space-y-6">

    <x-page-header 
        title="Kendala & Pembinaan Siswa Rombel" 
        subtitle="Daftar laporan kendala dan darurat (SOS) yang dilaporkan siswa rombel Anda selama PKL (Rahasia dari DU/DI)."
        badge="PEMBINAAN WALI KELAS"
    />

    <x-card>
        <form method="GET" action="{{ route('walikelas.incidents') }}" class="flex flex-col sm:flex-row gap-3 items-end">
            @if($classes->count() > 1)
                <div class="flex-1 w-full">
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Rombel Kelas</label>
                    <select name="class_id" class="w-full text-xs sm:text-sm px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                        <option value="">-- Semua Rombel --</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="flex-1 w-full">
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Status Penanganan</label>
                <select name="status" class="w-full text-xs sm:text-sm px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                    <option value="">-- Semua Status --</option>
                    <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Baru Masuk</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>Dalam Penanganan</option>
                    <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Selesai</option>
                </select>
            </div>

            <x-button type="submit" variant="primary" size="md">Filter</x-button>
        </form>
    </x-card>

    <x-card :title="'Laporan Kendala (' . $incidents->total() . ' Laporan)'">
        @if($incidents->isEmpty())
            <x-empty-state title="Tidak Ada Laporan Kendala" description="Belum ada laporan kendala dari siswa rombel yang Anda ampu." />
        @else
            <div class="divide-y divide-slate-100">
                @foreach($incidents as $inc)
                    <div class="py-4 first:pt-0 last:pb-0 space-y-2">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-slate-900 text-sm">{{ $inc->studentPkl?->student?->user?->name ?? 'Siswa' }}</span>
                                <span class="text-xs text-slate-500">({{ $inc->studentPkl?->student?->schoolClass?->name ?? '-' }})</span>
                                <x-badge :variant="$inc->urgency === 'critical' ? 'rose' : ($inc->urgency === 'high' ? 'amber' : 'indigo')" size="xs">
                                    {{ strtoupper($inc->urgency ?? 'medium') }}
                                </x-badge>
                                <x-badge :variant="$inc->status === 'resolved' ? 'emerald' : 'amber'" size="xs">
                                    {{ strtoupper($inc->status) }}
                                </x-badge>
                            </div>
                            <span class="text-[11px] text-slate-400">{{ $inc->created_at->isoFormat('D MMM Y, HH:mm') }}</span>
                        </div>
                        <p class="text-xs font-bold text-slate-800">{{ $inc->subject }}</p>
                        <p class="text-xs text-slate-600 leading-relaxed">{{ $inc->description }}</p>
                        <div class="text-[11px] text-slate-500">
                            Mitra DU/DI: <strong>{{ $inc->studentPkl?->company?->name ?? '-' }}</strong> &bull;
                            Pembimbing Sekolah: <strong>{{ $inc->studentPkl?->teacher?->user?->name ?? '-' }}</strong>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="pt-4 border-t border-slate-100">
                {{ $incidents->links() }}
            </div>
        @endif
    </x-card>

</div>
@endsection
