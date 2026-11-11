@extends('layouts.app')

@section('page_title', 'Master Mitra DU/DI & Geofencing GPS')
@section('page_subtitle', 'Pengaturan lokasi kantor perusahaan rekanan dan aturan geofencing presensi')

@section('content')
<div class="space-y-6">

    <x-page-header 
        title="Dunia Usaha & Dunia Industri (DU/DI)" 
        subtitle="Daftar perusahaan tempat siswa melaksanakan praktik kerja lapangan, koordinat GPS, dan mentor industri."
    >
        <x-button type="button" variant="primary" size="sm" onclick="document.getElementById('modal-add-company').classList.remove('hidden')">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Tambah Mitra DU/DI
        </x-button>
    </x-page-header>

    <!-- Table Card -->
    <x-card noPadding>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/80 text-slate-500 border-b border-slate-200/80 font-extrabold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6">Nama Perusahaan & Alamat</th>
                        <th class="py-3.5 px-4">Kontak PIC</th>
                        <th class="py-3.5 px-4">Geofencing GPS</th>
                        <th class="py-3.5 px-4">Hari Kerja</th>
                        <th class="py-3.5 px-4">Mentor Lapangan</th>
                        <th class="py-3.5 px-6 text-center">Siswa Aktif</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($companies as $cp)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3.5 px-6">
                                <div class="flex items-start space-x-3">
                                    <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100 text-blue-700 font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-4 h-4 stroke-[1.8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    </div>
                                    <div class="min-w-0 max-w-sm">
                                        <span class="font-bold text-slate-900 block truncate">{{ $cp->name }}</span>
                                        <span class="text-[11px] text-slate-500 block truncate leading-snug font-normal mt-0.5">{{ $cp->address }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-slate-800 block">{{ $cp->contact_person }}</span>
                                <span class="text-[11px] text-slate-400 font-mono block mt-0.5">{{ $cp->phone }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($cp->gps_enabled)
                                    <x-badge variant="success" size="xs">
                                        Radius {{ $cp->radius_meters }}m
                                    </x-badge>
                                    <span class="block text-[10px] font-mono text-slate-400 mt-1">
                                        {{ number_format($cp->latitude, 4) }}, {{ number_format($cp->longitude, 4) }}
                                    </span>
                                @else
                                    <x-badge variant="neutral" size="xs">
                                        Radius Bebas
                                    </x-badge>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-semibold text-slate-800">
                                    {{ $cp->work_days === '6_days' ? '6 Hari (Sen-Sab)' : '5 Hari (Sen-Jum)' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                @forelse($cp->supervisors as $sup)
                                    <div class="mb-1 last:mb-0">
                                        <span class="block font-bold text-slate-900 text-xs">{{ $sup->user->name }}</span>
                                        <span class="block text-[10px] text-slate-400 font-mono">{{ $sup->user->email }}</span>
                                    </div>
                                @empty
                                    <span class="text-xs text-slate-400 italic">Belum ada mentor</span>
                                @endforelse
                            </td>
                            <td class="py-3.5 px-6 text-center">
                                <x-badge variant="indigo" size="sm">
                                    {{ $cp->studentPkls->count() }} Siswa
                                </x-badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12">
                                <x-empty-state 
                                    title="Belum Ada Mitra DU/DI" 
                                    description="Tambahkan perusahaan rekanan untuk penempatan PKL dan geofencing radius absensi."
                                    actionLabel="+ Tambah Mitra DU/DI Sekarang"
                                    actionClick="document.getElementById('modal-add-company').classList.remove('hidden')"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($companies->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $companies->links() }}
            </div>
        @endif
    </x-card>

</div>

<!-- Modal Add Company -->
<div id="modal-add-company" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in fade-in duration-150">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Tambah Mitra DU/DI Baru</h3>
                <p class="text-xs text-slate-500 font-medium">Lengkapi profil industri dan akun supervisor lapangan</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-add-company').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('adminsekolah.companies.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Perusahaan / Industri</label>
                <input type="text" name="name" required placeholder="PT Inovasi Teknologi Nusantara" 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-semibold text-slate-900">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Kantor / Pabrik Tempat PKL</label>
                <textarea name="address" rows="2" required placeholder="Jl. Raya Kawasan Industri No. 45..." 
                          class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900 leading-relaxed"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Penanggung Jawab (PIC)</label>
                    <input type="text" name="contact_person" required placeholder="Nama PIC Kantor" 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Telepon Kantor / PIC</label>
                    <input type="text" name="phone" required placeholder="021-12345678" 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Hari Kerja Mingguan</label>
                <select name="work_days" 
                        class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-semibold text-slate-900">
                    <option value="5_days">5 Hari Kerja (Senin s.d. Jumat)</option>
                    <option value="6_days">6 Hari Kerja (Senin s.d. Sabtu)</option>
                </select>
            </div>

            <!-- GPS Geofencing Section -->
            <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200/80 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-900 block">Validasi Presensi Geofencing GPS</span>
                        <span class="text-[11px] text-slate-500 font-medium">Kunci radius presensi hanya di lokasi kantor</span>
                    </div>
                    <label class="flex items-center space-x-2 cursor-pointer select-none">
                        <input type="checkbox" name="gps_enabled" value="1" id="check-gps" onchange="toggleGpsFields()" 
                               class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500/20 cursor-pointer">
                        <span class="text-xs text-indigo-700 font-bold">Aktifkan</span>
                    </label>
                </div>

                <div id="gps-fields" class="hidden grid grid-cols-3 gap-2.5 pt-2 border-t border-slate-200/60">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 mb-1">Latitude</label>
                        <input type="text" name="latitude" id="lat-in" placeholder="-6.2088" 
                               class="w-full text-xs p-2 rounded-xl bg-white border border-slate-200 font-mono text-slate-800">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 mb-1">Longitude</label>
                        <input type="text" name="longitude" id="lng-in" placeholder="106.8456" 
                               class="w-full text-xs p-2 rounded-xl bg-white border border-slate-200 font-mono text-slate-800">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 mb-1">Radius (Meter)</label>
                        <input type="number" name="radius_meters" value="100" min="10" max="5000" 
                               class="w-full text-xs p-2 rounded-xl bg-white border border-slate-200 font-bold text-slate-800">
                    </div>
                </div>
            </div>

            <!-- Initial Supervisor Account -->
            <div class="p-4 rounded-2xl bg-indigo-50/70 border border-indigo-100 space-y-2.5">
                <div class="flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">
                        👤
                    </span>
                    <span class="text-xs font-bold text-indigo-950">Akun Pembimbing DU/DI (Mentor Lapangan)</span>
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[10px] font-bold text-indigo-900 mb-1">Nama Mentor</label>
                        <input type="text" name="supervisor_name" placeholder="Budi Santoso, S.Kom." 
                               class="w-full text-xs p-2 rounded-xl bg-white border border-indigo-200 focus:ring-2 focus:ring-indigo-500/20 font-medium text-slate-900">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-indigo-900 mb-1">Email Login Mentor</label>
                        <input type="email" name="supervisor_email" placeholder="budi@perusahaan.com" 
                               class="w-full text-xs p-2 rounded-xl bg-white border border-indigo-200 focus:ring-2 focus:ring-indigo-500/20 font-medium text-slate-900">
                    </div>
                </div>
                <p class="text-[10px] text-indigo-700 font-medium">Kata sandi default login mentor: <code class="bg-white/80 px-1.5 py-0.5 rounded font-mono font-bold text-indigo-900">password</code></p>
            </div>

            <div class="flex items-center justify-end space-x-2.5 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-add-company').classList.add('hidden')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md">
                    Simpan Mitra DU/DI
                </x-button>
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
