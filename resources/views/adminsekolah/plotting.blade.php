@extends('layouts.app')

@section('page_title', 'Plotting Penugasan PKL')
@section('page_subtitle', 'Pemetaan penempatan siswa ke DU/DI, guru pembimbing, dan mentor industri')

@section('content')
<div class="space-y-6">

    <x-page-header 
        title="Plotting Penugasan Siswa PKL" 
        subtitle="Alokasi penempatan peserta PKL ke mitra DU/DI rekanan, guru pembimbing sekolah, dan mentor lapangan."
    >
        <x-button type="button" variant="primary" size="sm" onclick="document.getElementById('modal-add-plotting').classList.remove('hidden')">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Plotting Siswa Baru
        </x-button>
    </x-page-header>

    <!-- Table Card -->
    <x-card noPadding>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6">Nama Siswa & Kelas</th>
                        <th class="py-3.5 px-4">Perusahaan Mitra (DU/DI)</th>
                        <th class="py-3.5 px-4">Guru Pembimbing</th>
                        <th class="py-3.5 px-4">Mentor DU/DI</th>
                        <th class="py-3.5 px-4">Periode PKL</th>
                        <th class="py-3.5 px-6 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($studentPkls as $sp)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3.5 px-6">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-700 font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ substr($sp->student->user->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <span class="font-bold text-slate-900 block truncate">{{ $sp->student->user->name }}</span>
                                        <span class="text-[11px] text-slate-500 font-medium">{{ $sp->student->class->name }} &bull; NISN: {{ $sp->student->nisn }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-indigo-900 block">{{ $sp->company->name }}</span>
                                <span class="text-[11px] text-slate-400 block truncate max-w-xs font-normal mt-0.5">{{ $sp->company->address }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-semibold text-slate-900 block">{{ $sp->teacher->user->name }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">NIP: {{ $sp->teacher->nip ?? '-' }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($sp->supervisor)
                                    <span class="font-semibold text-slate-900 block">{{ $sp->supervisor->user->name }}</span>
                                    <span class="text-[10px] text-slate-400 font-mono">{{ $sp->supervisor->user->email }}</span>
                                @else
                                    <span class="text-slate-400 text-xs italic">Belum Ditentukan</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="text-xs font-semibold text-slate-700 block">{{ $sp->pklPeriod->name }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">{{ \Carbon\Carbon::parse($sp->pklPeriod->start_date)->format('d M') }} - {{ \Carbon\Carbon::parse($sp->pklPeriod->end_date)->format('d M Y') }}</span>
                            </td>
                            <td class="py-3.5 px-6 text-center">
                                <x-badge variant="success" size="xs">
                                    {{ strtoupper($sp->status) }}
                                </x-badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12">
                                <x-empty-state 
                                    title="Belum Ada Plotting Penugasan" 
                                    description="Hubungkan siswa dengan mitra industri DU/DI dan guru pembimbing sekolah."
                                    actionLabel="+ Plotting Siswa Sekarang"
                                    actionClick="document.getElementById('modal-add-plotting').classList.remove('hidden')"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($studentPkls->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $studentPkls->links() }}
            </div>
        @endif
    </x-card>

</div>

<!-- Modal Add Plotting -->
<div id="modal-add-plotting" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in fade-in duration-150">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Plotting Penempatan Siswa</h3>
                <p class="text-xs text-slate-500 font-medium">Petakan siswa ke perusahaan, mentor, dan guru</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-add-plotting').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('adminsekolah.plotting.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilih Siswa</label>
                <select name="student_id" required 
                        class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                    @foreach($students as $st)
                        <option value="{{ $st->id }}">{{ $st->user->name }} ({{ $st->class->name }} - {{ $st->nisn }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilih Perusahaan Mitra (DU/DI)</label>
                <select name="company_id" id="sel-company" onchange="updateSupervisors()" required 
                        class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-bold text-slate-900">
                    @foreach($companies as $cp)
                        <option value="{{ $cp->id }}">{{ $cp->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Mentor DU/DI (Opsional)</label>
                <select name="supervisor_id" id="sel-supervisor" 
                        class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                    <option value="">-- Pilih Mentor Lapangan --</option>
                    @foreach($companies as $cp)
                        @foreach($cp->supervisors as $sup)
                            <option value="{{ $sup->id }}" data-company="{{ $cp->id }}">
                                {{ $sup->user->name }} ({{ $cp->name }})
                            </option>
                        @endforeach
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Guru Pembimbing Sekolah</label>
                <select name="teacher_id" required 
                        class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                    @foreach($teachers as $tc)
                        <option value="{{ $tc->id }}">{{ $tc->user->name }} (NIP: {{ $tc->nip ?? '-' }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Periode Pelaksanaan PKL</label>
                <select name="pkl_period_id" required 
                        class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                    @foreach($periods as $pr)
                        <option value="{{ $pr->id }}">{{ $pr->name }} ({{ \Carbon\Carbon::parse($pr->start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($pr->end_date)->format('d M Y') }})</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-add-plotting').classList.add('hidden')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md">
                    Simpan Plotting
                </x-button>
            </div>
        </form>
    </div>
</div>

<script>
    function updateSupervisors() {
        const selComp = document.getElementById('sel-company');
        const selSup = document.getElementById('sel-supervisor');
        if (!selComp || !selSup) return;
        const companyId = selComp.value;
        const supOptions = selSup.querySelectorAll('option');
        let hasSelectedValid = false;
        supOptions.forEach(opt => {
            if (!opt.value) return;
            const cId = opt.getAttribute('data-company');
            if (cId === companyId) {
                opt.style.display = '';
                if (opt.selected) hasSelectedValid = true;
            } else {
                opt.style.display = 'none';
            }
        });
        if (!hasSelectedValid && selSup.value) {
            selSup.value = '';
        }
    }
    document.addEventListener('DOMContentLoaded', updateSupervisors);
</script>
@endsection
