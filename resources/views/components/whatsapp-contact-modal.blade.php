<!-- Modal Pengaturan Kontak WhatsApp Mandiri (Universal) -->
<div id="modal-whatsapp-contact" onclick="if(event.target === this) closeWhatsAppContactModal()" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-2xl border border-slate-100 animate-in zoom-in-95 duration-150 space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center space-x-2.5">
                <div class="w-9 h-9 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center font-bold text-sm shrink-0">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2zm.01 1.67c4.54 0 8.24 3.7 8.24 8.24 0 2.2-.86 4.28-2.42 5.84a8.19 8.19 0 0 1-5.82 2.41c-1.46 0-2.9-.38-4.17-1.11l-.3-.17-3.1.81.83-3.02-.2-.31a8.16 8.16 0 0 1-1.25-4.45c0-4.54 3.7-8.24 8.24-8.24zm4.51 11.66c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.24-.75-.67-1.26-1.49-1.41-1.74-.15-.25-.02-.39.11-.51.11-.11.25-.29.37-.43.13-.15.17-.25.25-.42.08-.17.04-.32-.02-.45-.06-.13-.56-1.35-.77-1.84-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1 0 1.24.9 2.44 1.03 2.61.13.17 1.77 2.7 4.29 3.79.6.26 1.07.41 1.43.53.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.15-1.18-.06-.11-.22-.18-.47-.31z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Nomor Kontak WhatsApp</h3>
                    <p class="text-[11px] text-slate-400">Penerima Peringatan Darurat SOS Siswa</p>
                </div>
            </div>
            <button type="button" onclick="closeWhatsAppContactModal()" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Info Card -->
        <div class="p-3.5 rounded-2xl bg-emerald-50/80 border border-emerald-200 text-xs text-emerald-950 space-y-1">
            <div class="flex items-center gap-1.5 font-bold text-emerald-900">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Penting untuk Respon Cepat SOS</span>
            </div>
            <p class="text-[11.5px] leading-relaxed text-emerald-900/90">
                Nomor WhatsApp ini digunakan sistem secara otomatis untuk meneruskan <strong>sinyal bahaya darurat (SOS)</strong> langsung dari siswa PKL bimbingan Anda.
            </p>
        </div>

        <!-- Form Update Kontak WA -->
        <form method="POST" action="{{ route('profile.contact-preferences') }}" class="space-y-4">
            @csrf

            <div>
                <label for="wa_contact_number" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Nomor WhatsApp Aktif
                </label>
                <div class="relative">
                    <input type="text" name="wa_number" id="wa_contact_number" required 
                           value="{{ old('wa_number', auth()->user()->wa_number ?: (auth()->user()->profile?->phone ?? '')) }}"
                           placeholder="Contoh: 08123456789 atau 628123456789"
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500/20">
                </div>
                <p class="text-[11px] text-slate-400 mt-1">Gunakan format angka Indonesia (08xx atau 628xx).</p>
            </div>

            <!-- Tombol Aksi -->
            <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="closeWhatsAppContactModal()">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md" class="!bg-emerald-600 hover:!bg-emerald-700 !border-emerald-600">
                    Simpan Nomor WhatsApp
                </x-button>
            </div>
        </form>
    </div>
</div>

<script>
    function openWhatsAppContactModal() {
        const modal = document.getElementById('modal-whatsapp-contact');
        if (modal) {
            modal.classList.remove('hidden');
            const waInput = document.getElementById('wa_contact_number');
            if (waInput) waInput.focus();
        }
    }

    function closeWhatsAppContactModal() {
        const modal = document.getElementById('modal-whatsapp-contact');
        if (modal) {
            modal.classList.add('hidden');
        }
    }
</script>
