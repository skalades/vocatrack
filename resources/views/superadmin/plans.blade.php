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
    />

    <!-- Plan Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        @foreach($plans as $plan)
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
                            <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Penilaian DU/DI & Sertifikat</span>
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
                <div class="pt-5 mt-4 border-t border-slate-100">
                    <button type="button" 
                            onclick="openEditPlanModal('{{ $plan->id }}', '{{ addslashes($plan->name) }}', '{{ $plan->price }}', '{{ $plan->max_students }}', '{{ $plan->max_storage_mb }}', {{ $plan->is_popular ? 'true' : 'false' }}, {{ $plan->is_active ? 'true' : 'false' }}, '{{ addslashes($plan->description) }}')"
                            class="w-full py-2.5 px-4 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors text-center">
                        Edit Konfigurasi Paket
                    </button>
                </div>

            </div>
        @endforeach
    </div>

</div>

<!-- Modal Edit Plan -->
<div id="modal-edit-plan" onclick="if(event.target === this) this.classList.add('hidden')" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-2xl border border-slate-100 animate-in zoom-in-95 duration-150">
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
                <input type="number" name="price" id="edit-plan-price" required min="0" step="50000" 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-mono font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Batas Siswa PKL</label>
                    <input type="number" name="max_students" id="edit-plan-students" required min="1" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Storage (MB)</label>
                    <input type="number" name="max_storage_mb" id="edit-plan-storage" required min="500" step="512" 
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
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
    function openEditPlanModal(planId, name, price, students, storage, isPopular, isActive, desc) {
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

        modal.classList.remove('hidden');
    }
</script>
@endsection
