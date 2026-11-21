@extends('layouts.app')

@section('page_title', 'Mitra Perusahaan (DU/DI) Jurusan')
@section('page_subtitle', 'Kelola kemitraan dunia usaha & industri untuk jurusan ' . $major->name)

@section('content')
<div class="w-full space-y-6">

    <!-- Page Header -->
    <x-page-header 
        title="Mitra Perusahaan (DU/DI) Jurusan" 
        subtitle="Daftar mitra industri yang menerima siswa program keahlian {{ $major->name }} ({{ $major->code }})."
        badge="MANAJEMEN KAJUR"
    >
        <x-button type="button" variant="primary" size="sm" onclick="document.getElementById('modal-add-company').classList.remove('hidden')">
            <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span>+ Tambah Mitra DU/DI</span>
        </x-button>
    </x-page-header>

    <!-- Search & Filter Bar -->
    <x-card>
        <form method="GET" action="{{ route('kajur.companies') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama perusahaan, PIC, atau alamat..." 
                       class="w-full pl-9 pr-4 py-2.5 text-xs sm:text-sm rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <div class="flex items-center gap-2">
                <x-button type="submit" variant="secondary" size="md">
                    Cari
                </x-button>
                @if(request('search'))
                    <a href="{{ route('kajur.companies') }}" class="px-3 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Companies List Grid -->
    @if($companies->isEmpty())
        <x-card>
            <x-empty-state 
                title="Belum Ada Mitra Perusahaan Terhubung" 
                description="Tambahkan mitra industri tempat siswa jurusan {{ $major->name }} melaksanakan Praktik Kerja Lapangan."
                actionLabel="+ Tambah Mitra Sekarang"
                actionClick="document.getElementById('modal-add-company').classList.remove('hidden')"
            />
        </x-card>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($companies as $c)
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 hover:border-emerald-300 transition-all shadow-xs flex flex-col justify-between group">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm shrink-0">
                                {{ substr($c->name, 0, 2) }}
                            </div>
                            <div class="flex items-center gap-1">
                                <x-badge :variant="$c->work_days === '7_days' ? 'purple' : ($c->work_days === '6_days' ? 'indigo' : 'blue')" size="xs">
                                    {{ $c->work_days === '7_days' ? '7 Hari (On-Project)' : ($c->work_days === '6_days' ? '6 Hari Kerja' : '5 Hari Kerja') }}
                                </x-badge>
                                @if($c->gps_enabled)
                                    <span class="inline-flex items-center p-1 rounded-md bg-emerald-50 text-emerald-700 text-[10px] font-bold" title="Geofencing GPS Aktif ({{ $c->radius_meters }}m)">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <h4 class="font-bold text-slate-900 text-sm group-hover:text-emerald-700 transition-colors line-clamp-1">
                                {{ $c->name }}
                            </h4>
                            <p class="text-xs text-slate-500 line-clamp-2 mt-1 leading-relaxed">
                                {{ $c->address }}
                            </p>
                        </div>

                        <div class="pt-3 border-t border-slate-100 space-y-1.5 text-xs text-slate-600">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">PIC / HRD:</span>
                                <span class="font-semibold text-slate-900">{{ $c->contact_person }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Kontak:</span>
                                <span class="font-mono text-slate-800">{{ $c->phone }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Siswa Aktif:</span>
                                <span class="font-extrabold text-emerald-700">{{ $c->active_students_count }} Siswa</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Status PKL:</span>
                                <span class="inline-flex items-center text-[10.5px] {{ $c->is_pkl_started ? 'text-emerald-700 font-bold bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200/60' : 'text-slate-600 font-bold bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $c->is_pkl_started ? 'bg-emerald-500' : 'bg-slate-400' }} mr-1 shrink-0"></span>
                                    {{ $c->is_pkl_started ? 'Sudah Mulai' : 'Belum Mulai' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" 
                                onclick="openEditCompanyModal({{ json_encode([
                                    'id' => $c->id,
                                    'name' => $c->name,
                                    'address' => $c->address,
                                    'contact_person' => $c->contact_person,
                                    'phone' => $c->phone,
                                    'work_days' => $c->work_days,
                                    'requires_industry_approval' => (bool) $c->requires_industry_approval,
                                    'is_pkl_started' => (bool) $c->is_pkl_started,
                                    'gps_enabled' => $c->gps_enabled,
                                    'latitude' => $c->latitude,
                                    'longitude' => $c->longitude,
                                    'radius_meters' => $c->radius_meters,
                                    'update_url' => route('kajur.companies.update', $c),
                                ]) }})"
                                class="px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors cursor-pointer">
                            Edit Data
                        </button>

                        @if($c->active_students_count === 0)
                            <form action="{{ route('kajur.companies.destroy', $c) }}" method="POST" class="inline" onsubmit="return confirm('Lepas mitra {{ $c->name }} dari jurusan Anda?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2.5 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer">
                                    Hapus
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pt-2">
            {{ $companies->links() }}
        </div>
    @endif

</div>

<!-- Modal Add Company (Dengan Fitur Buat Akun Pembimbing Lapangan Sekaligus) -->
<div id="modal-add-company" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-2xl border border-slate-100 my-8">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Tambah Mitra Perusahaan (DU/DI)</h3>
                <p class="text-xs text-slate-500 font-medium">Mitra otomatis ditautkan ke jurusan {{ $major->name }}</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-add-company').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('kajur.companies.store') }}" method="POST" class="space-y-4">
            @csrf

            <!-- Section 1: Profil Perusahaan -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Perusahaan / Instansi</label>
                <input type="text" name="name" required placeholder="Contoh: PT Telkom Akses / Bengkel Astra" 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-bold text-slate-900">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Lengkap Perusahaan</label>
                <textarea name="address" rows="2" required placeholder="Alamat jalan, gedung, kota..." 
                          class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900"></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Penanggung Jawab (PIC / HRD)</label>
                    <input type="text" name="contact_person" required placeholder="Nama lengkap PIC" 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">No. Telepon / WhatsApp</label>
                    <input type="text" name="phone" required placeholder="08xxxxxxxxxx" 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-mono text-slate-900">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Hari Kerja</label>
                <select name="work_days" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                    <option value="5_days">5 Hari Kerja (Senin - Jumat)</option>
                    <option value="6_days">6 Hari Kerja (Senin - Sabtu)</option>
                    <option value="7_days">7 Hari Penuh (Senin - Minggu / On Project)</option>
                </select>
            </div>

            <!-- Section 2: Geofencing GPS (Opsional) -->
            <div class="pt-3 border-t border-slate-100">
                <div class="flex items-center justify-between mb-2">
                    <label class="text-xs font-bold text-slate-700">Validasi Radius Presensi GPS</label>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="gps_enabled" value="1" id="add-gps-toggle" onchange="document.getElementById('add-gps-inputs').classList.toggle('hidden', !this.checked)" class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>

                <div id="add-gps-inputs" class="hidden space-y-2.5 bg-slate-50 p-3 rounded-2xl border border-slate-200/60 text-xs">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">Latitude</label>
                            <input type="text" name="latitude" placeholder="-6.2088" class="w-full p-2 bg-white rounded-lg border border-slate-200 font-mono text-xs">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">Longitude</label>
                            <input type="text" name="longitude" placeholder="106.8456" class="w-full p-2 bg-white rounded-lg border border-slate-200 font-mono text-xs">
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 mb-1">Radius Toleransi (Meter)</label>
                        <input type="number" name="radius_meters" value="100" min="10" max="5000" class="w-full p-2 bg-white rounded-lg border border-slate-200 font-mono text-xs">
                    </div>
                </div>
            </div>

            <!-- Status Pelaksanaan PKL -->
            <div class="pt-3 border-t border-slate-100">
                <div class="flex items-center justify-between">
                    <div class="pr-3">
                        <label class="text-xs font-bold text-slate-700 block">Status Pelaksanaan PKL</label>
                        <span class="text-[11px] text-slate-500 font-medium">Jika diaktifkan, siswa wajib menjalankan seluruh kewajiban sistem. Jika mati (posisi off), siswa dianulir/belum diwajibkan absen dan tidak tercatat alfa.</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="checkbox" name="is_pkl_started" value="1" class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>
            </div>

            <!-- Verifikasi Jurnal oleh Industri -->
            <div class="pt-3 border-t border-slate-100">
                <div class="flex items-center justify-between">
                    <div class="pr-3">
                        <label class="text-xs font-bold text-slate-700 block">Verifikasi Jurnal oleh Industri</label>
                        <span class="text-[11px] text-slate-500 font-medium">Jika industri sibuk / tidak berkenan memverifikasi, matikan opsi ini agar jurnal langsung diverifikasi Guru Pembimbing.</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="checkbox" name="requires_industry_approval" value="1" checked class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>
            </div>

            <!-- Section 3: Akun Mentor DU/DI (Opsional) -->
            <div class="pt-3 border-t border-slate-100">
                <h4 class="text-xs font-bold text-slate-700 mb-2">Akun Pembimbing Lapangan (DU/DI) <span class="text-slate-400 font-normal">(Opsional)</span></h4>
                <div class="space-y-2.5 bg-emerald-50/50 p-3 rounded-2xl border border-emerald-100 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">Nama Pembimbing</label>
                            <input type="text" name="supervisor_name" placeholder="Nama mentor industri" class="w-full p-2 bg-white rounded-lg border border-slate-200 text-xs font-medium">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">Jabatan / Posisi</label>
                            <input type="text" name="supervisor_position" placeholder="Contoh: Senior Tech / Supervisor" class="w-full p-2 bg-white rounded-lg border border-slate-200 text-xs font-medium">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">Email Login</label>
                            <input type="email" name="supervisor_email" placeholder="mentor@perusahaan.com" class="w-full p-2 bg-white rounded-lg border border-slate-200 text-xs font-mono">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">No. WhatsApp</label>
                            <input type="text" name="supervisor_phone" placeholder="08xxxxxxxxxx" class="w-full p-2 bg-white rounded-lg border border-slate-200 text-xs font-mono">
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-500">Kata sandi awal akun pembimbing otomatis diatur: <code class="font-bold text-emerald-800">password</code></p>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2.5 pt-4 border-t border-slate-100">
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

<!-- Modal Edit Company -->
<div id="modal-edit-company" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-2xl border border-slate-100 my-8">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Edit Data Mitra Perusahaan</h3>
                <p class="text-xs text-slate-500 font-medium">Perbarui profil dan kontak perusahaan</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-edit-company').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="edit-company-form" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Perusahaan / Instansi</label>
                <input type="text" id="edit-name" name="name" required 
                       class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-bold text-slate-900">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Lengkap Perusahaan</label>
                <textarea id="edit-address" name="address" rows="2" required 
                          class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900"></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Penanggung Jawab (PIC / HRD)</label>
                    <input type="text" id="edit-contact-person" name="contact_person" required 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">No. Telepon / WhatsApp</label>
                    <input type="text" id="edit-phone" name="phone" required 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-mono text-slate-900">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Hari Kerja</label>
                <select id="edit-work-days" name="work_days" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 font-medium text-slate-900">
                    <option value="5_days">5 Hari Kerja (Senin - Jumat)</option>
                    <option value="6_days">6 Hari Kerja (Senin - Sabtu)</option>
                    <option value="7_days">7 Hari Penuh (Senin - Minggu / On Project)</option>
                </select>
            </div>

            <!-- Geofencing GPS Edit -->
            <div class="pt-3 border-t border-slate-100">
                <div class="flex items-center justify-between mb-2">
                    <label class="text-xs font-bold text-slate-700">Validasi Radius Presensi GPS</label>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="gps_enabled" value="1" id="edit-gps-toggle" onchange="document.getElementById('edit-gps-inputs').classList.toggle('hidden', !this.checked)" class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>

                <div id="edit-gps-inputs" class="hidden space-y-2.5 bg-slate-50 p-3 rounded-2xl border border-slate-200/60 text-xs">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">Latitude</label>
                            <input type="text" id="edit-latitude" name="latitude" class="w-full p-2 bg-white rounded-lg border border-slate-200 font-mono text-xs">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">Longitude</label>
                            <input type="text" id="edit-longitude" name="longitude" class="w-full p-2 bg-white rounded-lg border border-slate-200 font-mono text-xs">
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 mb-1">Radius Toleransi (Meter)</label>
                        <input type="number" id="edit-radius" name="radius_meters" min="10" max="5000" class="w-full p-2 bg-white rounded-lg border border-slate-200 font-mono text-xs">
                    </div>
                </div>
            </div>

            <!-- Status Pelaksanaan PKL Edit -->
            <div class="pt-3 border-t border-slate-100">
                <div class="flex items-center justify-between">
                    <div class="pr-3">
                        <label class="text-xs font-bold text-slate-700 block">Status Pelaksanaan PKL</label>
                        <span class="text-[11px] text-slate-500 font-medium">Jika diaktifkan, siswa wajib menjalankan seluruh kewajiban sistem. Jika mati (posisi off), siswa dianulir/belum diwajibkan absen dan tidak tercatat alfa.</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="checkbox" name="is_pkl_started" value="1" id="edit-is-pkl-started" class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>
            </div>

            <!-- Verifikasi Jurnal oleh Industri Edit -->
            <div class="pt-3 border-t border-slate-100">
                <div class="flex items-center justify-between">
                    <div class="pr-3">
                        <label class="text-xs font-bold text-slate-700 block">Verifikasi Jurnal oleh Industri</label>
                        <span class="text-[11px] text-slate-500 font-medium">Jika industri sibuk / tidak berkenan memverifikasi, matikan opsi ini agar jurnal langsung diverifikasi Guru Pembimbing.</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="checkbox" name="requires_industry_approval" value="1" id="edit-requires-industry-approval" class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2.5 pt-4 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-edit-company').classList.add('hidden')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md">
                    Simpan Perubahan
                </x-button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openEditCompanyModal(data) {
        document.getElementById('edit-company-form').action = data.update_url;
        document.getElementById('edit-name').value = data.name;
        document.getElementById('edit-address').value = data.address;
        document.getElementById('edit-contact-person').value = data.contact_person;
        document.getElementById('edit-phone').value = data.phone;
        document.getElementById('edit-work-days').value = data.work_days;
        
        const pklStartedToggle = document.getElementById('edit-is-pkl-started');
        if (pklStartedToggle) {
            pklStartedToggle.checked = Boolean(data.is_pkl_started);
        }

        const industryToggle = document.getElementById('edit-requires-industry-approval');
        if (industryToggle) {
            industryToggle.checked = data.requires_industry_approval !== undefined ? Boolean(data.requires_industry_approval) : true;
        }

        const gpsToggle = document.getElementById('edit-gps-toggle');
        gpsToggle.checked = Boolean(data.gps_enabled);
        document.getElementById('edit-gps-inputs').classList.toggle('hidden', !gpsToggle.checked);
        document.getElementById('edit-latitude').value = data.latitude || '';
        document.getElementById('edit-longitude').value = data.longitude || '';
        document.getElementById('edit-radius').value = data.radius_meters || 100;

        document.getElementById('modal-edit-company').classList.remove('hidden');
    }
</script>
@endpush
@endsection
