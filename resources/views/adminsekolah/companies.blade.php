@extends('layouts.app')

@section('page_title', 'Master Mitra DU/DI & Geofencing GPS')
@section('page_subtitle', 'Pengaturan lokasi kantor perusahaan rekanan dan aturan geofencing')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-base font-bold text-slate-800">Daftar Dunia Usaha & Industri (DU/DI)</h3>
            <p class="text-xs text-slate-500">Perusahaan tempat siswa melaksanakan praktik kerja lapangan</p>
        </div>
        <button onclick="document.getElementById('modal-add-company').classList.remove('hidden')" class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs flex items-center space-x-1.5 shadow-md shadow-indigo-200">
            <span>+ Tambah Mitra DU/DI</span>
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200/80 font-bold uppercase text-[10px]">
                    <tr>
                        <th class="py-3 px-5">Nama Perusahaan & Alamat</th>
                        <th class="py-3 px-4">Kontak PIC</th>
                        <th class="py-3 px-4">Geofencing GPS</th>
                        <th class="py-3 px-4">Hari Kerja</th>
                        <th class="py-3 px-4 text-center">Mentor DU/DI</th>
                        <th class="py-3 px-4 text-center">Siswa Magang</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($companies as $cp)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-5">
                                <span class="font-bold text-slate-900 block">{{ $cp->name }}</span>
                                <span class="text-[11px] text-slate-500 block truncate max-w-sm">{{ $cp->address }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="block font-semibold text-slate-800">{{ $cp->contact_person }}</span>
                                <span class="text-[11px] text-slate-400 block">{{ $cp->phone }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($cp->gps_enabled)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        GPS Aktif ({{ $cp->radius_meters }}m)
                                    </span>
                                    <span class="block text-[10px] font-mono text-slate-400 mt-0.5">
                                        {{ $cp->latitude }}, {{ $cp->longitude }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                        Bebas Radius
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-700">
                                {{ $cp->work_days === '6_days' ? '6 Hari (Sen-Sab)' : '5 Hari (Sen-Jum)' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @foreach($cp->supervisors as $sup)
                                    <span class="block font-bold text-slate-800 text-[11px]">{{ $sup->user->name }}</span>
                                    <span class="block text-[9px] text-slate-400 font-mono">{{ $sup->user->email }}</span>
                                @endforeach
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold text-indigo-600">
                                {{ $cp->studentPkls->count() }} Siswa
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-400">Belum ada mitra DU/DI terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $companies->links() }}
        </div>
    </div>

</div>

<!-- Modal Add Company -->
<div id="modal-add-company" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <h3 class="text-sm font-bold text-slate-900">Tambah Mitra DU/DI Baru</h3>
            <button type="button" onclick="document.getElementById('modal-add-company').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
        </div>

        <form action="{{ route('adminsekolah.companies.store') }}" method="POST" class="space-y-3.5">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Perusahaan DU/DI</label>
                <input type="text" name="name" required placeholder="PT ..." class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Kantor / Pabrik</label>
                <textarea name="address" rows="2" required placeholder="Jl. Raya Industri..." class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Penanggung Jawab (PIC)</label>
                    <input type="text" name="contact_person" required placeholder="Nama PIC" class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Telepon Kantor / PIC</label>
                    <input type="text" name="phone" required placeholder="021-..." class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Hari Kerja Mingguan</label>
                <select name="work_days" class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200 font-semibold">
                    <option value="5_days">5 Hari Kerja (Senin s.d. Jumat)</option>
                    <option value="6_days">6 Hari Kerja (Senin s.d. Sabtu)</option>
                </select>
            </div>

            <!-- GPS Geofencing Section -->
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-800">Geofencing Presensi GPS</span>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="gps_enabled" value="1" id="check-gps" onchange="toggleGpsFields()" class="w-4 h-4 rounded text-indigo-600">
                        <span class="text-xs text-slate-700 font-semibold">Wajibkan GPS</span>
                    </label>
                </div>

                <div id="gps-fields" class="hidden grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 mb-1">Latitude</label>
                        <input type="text" name="latitude" id="lat-in" placeholder="-6.2088" class="w-full text-xs p-2 rounded-xl bg-white border border-slate-200 font-mono">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 mb-1">Longitude</label>
                        <input type="text" name="longitude" id="lng-in" placeholder="106.8456" class="w-full text-xs p-2 rounded-xl bg-white border border-slate-200 font-mono">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 mb-1">Radius (Meter)</label>
                        <input type="number" name="radius_meters" value="100" min="10" max="5000" class="w-full text-xs p-2 rounded-xl bg-white border border-slate-200">
                    </div>
                </div>
            </div>

            <!-- Initial Supervisor Account -->
            <div class="p-3.5 rounded-2xl bg-indigo-50/70 border border-indigo-100 space-y-2">
                <span class="text-xs font-bold text-indigo-900 block">Akun Pembimbing DU/DI (Mentor Lapangan)</span>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] font-bold text-indigo-950 mb-1">Nama Mentor</label>
                        <input type="text" name="supervisor_name" placeholder="Nama Mentor DU/DI" class="w-full text-xs p-2 rounded-xl bg-white border border-indigo-200">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-indigo-950 mb-1">Email Login Mentor</label>
                        <input type="email" name="supervisor_email" placeholder="mentor@perusahaan.com" class="w-full text-xs p-2 rounded-xl bg-white border border-indigo-200">
                    </div>
                </div>
                <p class="text-[10px] text-indigo-700">Password awal login mentor: <code>password</code></p>
            </div>

            <div class="flex space-x-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-add-company').classList.add('hidden')" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600">Batal</button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-200">Simpan Perusahaan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleGpsFields() {
        const checked = document.getElementById('check-gps').checked;
        const fields = document.getElementById('gps-fields');
        if (checked) {
            fields.classList.remove('hidden');
        } else {
            fields.classList.add('hidden');
        }
    }
</script>
@endsection
