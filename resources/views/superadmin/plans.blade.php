@extends('layouts.app')

@section('page_title', 'Paket & Lisensi Sewa SaaS')
@section('page_subtitle', 'Konfigurasi tiering paket sewa, harga langganan, kuota siswa, dan pembatasan fitur')

@section('content')
<div class="w-full space-y-6 sm:space-y-8 animate-in fade-in duration-300">

    <!-- Enterprise Hero Header -->
    <x-page-header 
        title="Katalog Paket Sewa Platform" 
        subtitle="Atur skema penentuan harga, kapasitas kuota siswa per paket, batasan penyimpanan, serta fitur add-on."
        badge="TIERING & LICENSING"
    >
        <x-button type="button" variant="primary" size="md" onclick="document.getElementById('modal-create-plan').classList.remove('hidden')" class="shadow-lg shadow-indigo-600/25">
            <svg class="w-4 h-4 mr-1.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Tambah Paket Baru
        </x-button>
    </x-page-header>

    <!-- Plan Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        @forelse($plans as $plan)
            <div class="relative flex flex-col justify-between rounded-3xl bg-white border {{ $plan->is_popular ? 'border-indigo-500 shadow-xl shadow-indigo-500/10 ring-2 ring-indigo-500/20' : 'border-slate-200/90 shadow-sm' }} p-6 transition-all hover:shadow-md">
                
                @if($plan->is_popular)
                    <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-600 text-white shadow-sm">
                            Paling Diminati
                        </span>
                    </div>
                @endif

                <div class="space-y-4">
                    <!-- Header Plan -->
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="font-extrabold text-slate-900 text-base">{{ $plan->name }}</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold font-mono {{ $plan->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $plan->is_active ? 'Aktif' : 'Non-Aktif' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium mt-1 leading-relaxed">{{ $plan->description }}</p>
                    </div>

                    <!-- Price -->
                    <div class="py-3 border-y border-slate-100">
                        <div class="flex items-baseline gap-1">
                            <span class="text-2xl font-black text-slate-900 font-mono tracking-tight">
                                {{ $plan->price > 0 ? $plan->formatted_price : 'GRATIS' }}
                            </span>
                        </div>
                        <span class="text-xs text-slate-400 font-medium">{{ $plan->period_label }}</span>
                    </div>

                    <!-- Quota & Storage Highlights -->
                    <div class="space-y-2.5 text-xs text-slate-700 font-medium">
                        <div class="flex items-center gap-2.5">
                            <div class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                            <span>Kuota: <strong>{{ $plan->max_students }} Siswa PKL</strong></span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <div class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7z"/></svg>
                            </div>
                            <span>Storage: <strong>{{ round($plan->max_storage_mb / 1024, 1) }} GB</strong></span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <div class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <span>Terpasang di <strong>{{ $plan->subscriptions_count }} Sekolah</strong></span>
                        </div>
                    </div>

                    <!-- Feature Checklist -->
                    <div class="pt-2 border-t border-slate-100 space-y-1.5 text-[11px] text-slate-600">
                        @php $features = $plan->features ?? []; @endphp
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Presensi GPS & Anti-Fake GPS</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Jurnal PKL & Foto Bukti</span>
                        </div>
                        <div class="flex items-center gap-2">
                            @if(!empty($features['certificate']))
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Penilaian DU/DI & Sertifikat</span>
                            @else
                                <svg class="w-4 h-4 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span class="text-slate-400">Sertifikat Digital</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            @if(!empty($features['whatsapp_gateway']))
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span class="font-bold text-slate-800">WhatsApp Gateway Alert</span>
                            @else
                                <svg class="w-4 h-4 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span class="text-slate-400">WhatsApp Gateway</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            @if(!empty($features['custom_domain']))
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span class="font-bold text-slate-800">Custom Subdomain / Domain</span>
                            @else
                                <svg class="w-4 h-4 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span class="text-slate-400">Custom Domain</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="pt-5 mt-4 border-t border-slate-100 flex items-center gap-2">
                    <button type="button" 
                            onclick="openEditPlanModal('{{ $plan->id }}', '{{ addslashes($plan->name) }}', '{{ $plan->price }}', '{{ $plan->max_students }}', '{{ $plan->max_storage_mb }}', {{ $plan->is_popular ? 'true' : 'false' }}, {{ $plan->is_active ? 'true' : 'false' }}, '{{ addslashes($plan->description) }}', {{ !empty($features['certificate']) ? 'true' : 'false' }}, {{ !empty($features['whatsapp_gateway']) ? 'true' : 'false' }}, {{ !empty($features['custom_domain']) ? 'true' : 'false' }})"
                            class="flex-1 py-2.5 px-3 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors text-center">
                        Edit Paket
                    </button>
                    @if($plan->subscriptions_count == 0)
                        <form action="{{ route('superadmin.plans.destroy', $plan->id) }}" method="POST" onsubmit="return confirm('Hapus paket sewa ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2.5 rounded-xl text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors" title="Hapus Paket">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    @endif
                </div>

            </div>
        @empty
            <div class="col-span-full py-12">
                <x-card>
                    <div class="text-center py-8 space-y-3">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <h4 class="text-base font-extrabold text-slate-900">Belum Ada Paket Sewa</h4>
                        <p class="text-xs text-slate-500 max-w-sm mx-auto">Buat paket sewa baru untuk mulai menawarkan lisensi multi-tenancy kepada sekolah mitra.</p>
                        <div class="pt-2">
                            <x-button type="button" variant="primary" size="md" onclick="document.getElementById('modal-create-plan').classList.remove('hidden')">
                                + Buat Paket Sewa Pertama
                            </x-button>
                        </div>
                    </div>
                </x-card>
            </div>
        @endforelse
    </div>

</div>

<!-- Modal Tambah Paket Baru -->
<div id="modal-create-plan" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 sm:p-7 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Tambah Paket Sewa Baru</h3>
                <p class="text-xs text-slate-500 font-medium">Buat tiering lisensi baru untuk penawaran sewa sekolah</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-create-plan').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('superadmin.plans.store') }}" method="POST" class="space-y-4">
            @csrf
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Kode Unik Paket</label>
                    <input type="text" name="code" required placeholder="contoh: ultra-yearly" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-mono text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Paket</label>
                    <input type="text" name="name" required placeholder="Paket Ultra Tahunan" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Harga Sewa (Rp)</label>
                    <input type="number" name="price" required min="0" step="any" placeholder="8000000" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-mono font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Siklus Tagihan</label>
                    <select name="billing_period" required class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
                        <option value="monthly">Per Bulan</option>
                        <option value="semester">Per Semester (6 Bulan)</option>
                        <option value="yearly" selected>Per Tahun (12 Bulan)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Batas Kuota Siswa</label>
                    <input type="number" name="max_students" required min="1" step="any" placeholder="500" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Storage Kuota (MB)</label>
                    <input type="number" name="max_storage_mb" required min="100" step="any" value="20480" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
                </div>
            </div>

            <!-- Fitur Add-on Checklist -->
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
                <span class="text-xs font-bold text-slate-800 block">Fitur Unggulan Termasuk</span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                    <label class="flex items-center gap-2 text-slate-700 font-medium cursor-pointer">
                        <input type="checkbox" name="feat_certificate" value="1" checked class="rounded text-indigo-600 focus:ring-indigo-500">
                        Sertifikat Digital PDF
                    </label>
                    <label class="flex items-center gap-2 text-slate-700 font-medium cursor-pointer">
                        <input type="checkbox" name="feat_wa" value="1" class="rounded text-indigo-600 focus:ring-indigo-500">
                        WhatsApp Gateway Alert
                    </label>
                    <label class="flex items-center gap-2 text-slate-700 font-medium cursor-pointer">
                        <input type="checkbox" name="feat_domain" value="1" class="rounded text-indigo-600 focus:ring-indigo-500">
                        Custom Subdomain/Domain
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Deskripsi Ringkas Paket</label>
                <textarea name="description" rows="2" placeholder="Cocok untuk sekolah menengah dengan ratusan peserta PKL..." 
                          class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 leading-relaxed"></textarea>
            </div>

            <div class="flex items-center gap-4 pt-1">
                <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-700">
                    <input type="checkbox" name="is_popular" value="1" class="rounded text-indigo-600 focus:ring-indigo-500">
                    Tandai Paling Diminati
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-700">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded text-emerald-600 focus:ring-emerald-500">
                    Paket Aktif Ditawarkan
                </label>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-create-plan').classList.add('hidden')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md" class="shadow-md shadow-indigo-600/20">
                    Simpan Paket Baru
                </x-button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Plan -->
<div id="modal-edit-plan" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto animate-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Edit Paket Langganan</h3>
                <p class="text-xs text-slate-500 font-medium">Ubah harga, kuota siswa, atau batas penyimpanan</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-edit-plan').classList.add('hidden')" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="form-edit-plan" method="POST" action="" class="space-y-4">
            @csrf
            @method('PUT')
            
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Paket</label>
                <input type="text" name="name" id="edit-plan-name" required 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Harga Sewa (Rp)</label>
                <input type="number" name="price" id="edit-plan-price" required min="0" step="any" 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-mono font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Batas Siswa PKL</label>
                    <input type="number" name="max_students" id="edit-plan-students" required min="1" step="any" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Storage (MB)</label>
                    <input type="number" name="max_storage_mb" id="edit-plan-storage" required min="100" step="any" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
                </div>
            </div>

            <!-- Fitur Add-on Checklist -->
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
                <span class="text-xs font-bold text-slate-800 block">Fitur Unggulan Termasuk</span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                    <label class="flex items-center gap-2 text-slate-700 font-medium cursor-pointer">
                        <input type="checkbox" name="feat_certificate" id="edit-plan-feat-cert" value="1" class="rounded text-indigo-600 focus:ring-indigo-500">
                        Sertifikat Digital
                    </label>
                    <label class="flex items-center gap-2 text-slate-700 font-medium cursor-pointer">
                        <input type="checkbox" name="feat_wa" id="edit-plan-feat-wa" value="1" class="rounded text-indigo-600 focus:ring-indigo-500">
                        WhatsApp Gateway
                    </label>
                    <label class="flex items-center gap-2 text-slate-700 font-medium cursor-pointer">
                        <input type="checkbox" name="feat_domain" id="edit-plan-feat-domain" value="1" class="rounded text-indigo-600 focus:ring-indigo-500">
                        Custom Domain
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Deskripsi Ringkas</label>
                <textarea name="description" id="edit-plan-desc" rows="2" 
                          class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 leading-relaxed"></textarea>
            </div>

            <div class="flex items-center gap-4 pt-1">
                <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-700">
                    <input type="checkbox" name="is_popular" id="edit-plan-popular" value="1" class="rounded text-indigo-600 focus:ring-indigo-500">
                    Tandai Paling Diminati
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-700">
                    <input type="checkbox" name="is_active" id="edit-plan-active" value="1" class="rounded text-emerald-600 focus:ring-emerald-500">
                    Paket Aktif Ditawarkan
                </label>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="document.getElementById('modal-edit-plan').classList.add('hidden')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md" class="shadow-md shadow-indigo-600/20">
                    Simpan Perubahan
                </x-button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditPlanModal(planId, name, price, students, storage, isPopular, isActive, desc, featCert, featWa, featDomain) {
        const modal = document.getElementById('modal-edit-plan');
        const form = document.getElementById('form-edit-plan');

        form.action = `/super-admin/plans/${planId}`;
        document.getElementById('edit-plan-name').value = name;
        document.getElementById('edit-plan-price').value = price;
        document.getElementById('edit-plan-students').value = students;
        document.getElementById('edit-plan-storage').value = storage;
        document.getElementById('edit-plan-desc').value = desc;
        document.getElementById('edit-plan-popular').checked = isPopular;
        document.getElementById('edit-plan-active').checked = isActive;
        
        const certEl = document.getElementById('edit-plan-feat-cert');
        if (certEl) certEl.checked = featCert;
        const waEl = document.getElementById('edit-plan-feat-wa');
        if (waEl) waEl.checked = featWa;
        const domEl = document.getElementById('edit-plan-feat-domain');
        if (domEl) domEl.checked = featDomain;

        modal.classList.remove('hidden');
    }
</script>
@endsection
