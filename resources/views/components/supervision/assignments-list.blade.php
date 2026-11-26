@props([
    'assignments',
    'destroyRouteName',
])

<div class="divide-y divide-slate-100">
    @forelse($assignments as $assignment)
        @php
            $groupedCompanies = $assignment->items->groupBy('company_id');
            $totalItems = $assignment->items->count();
            $completedItems = $assignment->items->whereNotNull('completed_at')->count();
            $hasPending = $completedItems < $totalItems;
        @endphp
        <div class="p-5 hover:bg-slate-50/60 transition-colors flex flex-col lg:flex-row lg:items-start justify-between gap-4">
            <div class="space-y-2.5 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border {{ $assignment->stage_badge_class }}">
                        {{ $assignment->stage_label }}
                    </span>
                    @if($assignment->assignment_number)
                        <span class="px-2 py-0.5 rounded-md text-[11px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            {{ $assignment->assignment_number }}
                        </span>
                    @endif
                    <span class="text-xs text-slate-500 font-medium">
                        {{ $assignment->pklPeriod->name ?? '-' }}
                    </span>
                    @if($assignment->scheduled_date)
                        <span class="text-xs text-slate-400">&bull; Target: <strong class="text-slate-700">{{ $assignment->scheduled_date->translatedFormat('d M Y') }}</strong></span>
                    @endif
                </div>

                <div>
                    <div class="text-sm font-black text-slate-900">
                        Petugas: {{ $assignment->teacher->user->name ?? '-' }}
                        @if($assignment->teacher?->headedMajors?->isNotEmpty())
                            <span class="ml-1 px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Kajur {{ $assignment->teacher->headedMajors->pluck('code')->implode(', ') }}
                            </span>
                        @endif
                    </div>
                    @if($assignment->notes)
                        <p class="text-xs text-slate-500 mt-0.5">{{ $assignment->notes }}</p>
                    @endif
                </div>

                <!-- Multi-DU/DI Pills -->
                <div class="flex flex-wrap gap-2 pt-1">
                    @foreach($groupedCompanies as $companyId => $items)
                        @php
                            $comp = $items->first()->company;
                            $compDone = $items->whereNotNull('completed_at')->count();
                            $compTotal = $items->count();
                            $isCompDone = $compDone === $compTotal;
                        @endphp
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border text-xs {{ $isCompDone ? 'bg-emerald-50/70 border-emerald-200 text-emerald-800' : 'bg-amber-50/70 border-amber-200 text-amber-900' }}">
                            <span class="font-bold">{{ $comp->name ?? 'DU/DI' }}</span>
                            <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded bg-white/80">
                                {{ $compDone }}/{{ $compTotal }} selesai
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="shrink-0 flex items-center gap-2 lg:self-center">
                <span class="px-3 py-1.5 rounded-xl text-xs font-extrabold {{ $hasPending ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                    {{ $hasPending ? "{$completedItems}/{$totalItems} Selesai" : 'Semua Selesai' }}
                </span>

                @if($hasPending)
                    <form action="{{ route($destroyRouteName, $assignment->id) }}" method="POST" onsubmit="return confirm('Batalkan target siswa yang belum selesai pada penugasan ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-colors">
                            Batalkan
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <div class="p-8 text-center text-xs text-slate-500">
            Belum ada penugasan monitoring khusus. Tanpa penugasan khusus, guru pembimbing asli otomatis bertanggung jawab atas Monitoring 1 dan Monitoring 2 siswa binaannya.
        </div>
    @endforelse
</div>

@if($assignments->hasPages())
    <div class="p-4 border-t border-slate-100">
        {{ $assignments->links() }}
    </div>
@endif
