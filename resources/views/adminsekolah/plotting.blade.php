@extends('layouts.app')

@section('page_title', 'Plotting Penugasan PKL')
@section('page_subtitle', 'Pemetaan penempatan siswa ke DU/DI, guru pembimbing, dan mentor industri')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-base font-bold text-slate-800">Daftar Penugasan Siswa PKL</h3>
            <p class="text-xs text-slate-500">Relasi penempatan aktif dalam periode PKL berjalan</p>
        </div>
        <button onclick="document.getElementById('modal-add-plotting').classList.remove('hidden')" class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs flex items-center space-x-1.5 shadow-md shadow-indigo-200">
            <span>+ Plotting Siswa Baru</span>
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200/80 font-bold uppercase text-[10px]">
                    <tr>
                        <th class="py-3 px-5">Nama Siswa & Kelas</th>
                        <th class="py-3 px-4">Perusahaan Mitra (DU/DI)</th>
                        <th class="py-3 px-4">Guru Pembimbing</th>
                        <th class="py-3 px-4">Mentor DU/DI</th>
                        <th class="py-3 px-4">Periode</th>
                        <th class="py-3 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($studentPkls as $sp)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-5">
                                <span class="font-bold text-slate-900 block">{{ $sp->student->user->name }}</span>
                                <span class="text-[11px] text-slate-400 block">{{ $sp->student->class->name }} &bull; NISN: {{ $sp->student->nisn }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-indigo-700 block">{{ $sp->company->name }}</span>
                                <span class="text-[10px] text-slate-400 block truncate max-w-xs">{{ $sp->company->address }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-800">
                                {{ $sp->teacher->user->name }}
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-800">
                                {{ $sp->supervisor->user->name ?? 'Belum Ditentukan' }}
                            </td>
                            <td class="py-3.5 px-4 text-[11px] text-slate-500">
                                {{ $sp->pklPeriod->name }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    {{ strtoupper($sp->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-400">Belum ada plotting penugasan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $studentPkls->links() }}
        </div>
    </div>

</div>

<!-- Modal Add Plotting -->
<div id="modal-add-plotting" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-3xl p-5 shadow-2xl">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-slate-900">Plotting Penempatan Siswa PKL</h3>
            <button type="button" onclick="document.getElementById('modal-add-plotting').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
        </div>
        <form action="{{ route('adminsekolah.plotting.store') }}" method="POST" class="space-y-3.5">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Siswa</label>
                <select name="student_id" required class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                    @foreach($students as $st)
                        <option value="{{ $st->id }}">{{ $st->user->name }} ({{ $st->class->name }} - {{ $st->nisn }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Perusahaan Mitra (DU/DI)</label>
                <select name="company_id" id="sel-company" onchange="updateSupervisors()" required class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200 font-semibold text-slate-800">
                    @foreach($companies as $cp)
                        <option value="{{ $cp->id }}">{{ $cp->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Mentor DU/DI (Opsional)</label>
                <select name="supervisor_id" id="sel-supervisor" class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
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
                <label class="block text-xs font-bold text-slate-700 mb-1">Guru Pembimbing Sekolah</label>
                <select name="teacher_id" required class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                    @foreach($teachers as $tc)
                        <option value="{{ $tc->id }}">{{ $tc->user->name }} (NIP: {{ $tc->nip ?? '-' }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Periode Pelaksanaan PKL</label>
                <select name="pkl_period_id" required class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                    @foreach($periods as $pr)
                        <option value="{{ $pr->id }}">{{ $pr->name }} ({{ \Carbon\Carbon::parse($pr->start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($pr->end_date)->format('d M Y') }})</option>
                    @endforeach
                </select>
            </div>

            <div class="flex space-x-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-add-plotting').classList.add('hidden')" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600">Batal</button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold">Simpan Plotting</button>
            </div>
        </form>
    </div>
</div>

<script>
    function updateSupervisors() {
        const companyId = document.getElementById('sel-company').value;
        const supOptions = document.querySelectorAll('#sel-supervisor option');
        supOptions.forEach(opt => {
            if (!opt.value) return;
            const cId = opt.getAttribute('data-company');
            if (cId === companyId) {
                opt.style.display = '';
            } else {
                opt.style.display = 'none';
            }
        });
    }
    document.addEventListener('DOMContentLoaded', updateSupervisors);
</script>
@endsection
