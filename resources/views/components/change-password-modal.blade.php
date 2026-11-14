<!-- Modal Ubah Kata Sandi Mandiri (Universal untuk Seluruh Level Pengguna) -->
<div id="modal-change-password" onclick="if(event.target === this) closeChangePasswordModal()" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-2xl border border-slate-100 animate-in zoom-in-95 duration-150 space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center space-x-2.5">
                <div class="w-9 h-9 rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center font-bold text-sm shrink-0">
                    <svg class="w-5 h-5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Ubah Kata Sandi Akun</h3>
                    <p class="text-[11px] text-slate-400">Jaga keamanan akun Anda secara mandiri</p>
                </div>
            </div>
            <button type="button" onclick="closeChangePasswordModal()" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Form Ubah Password -->
        <form method="POST" action="{{ route('profile.change-password') }}" class="space-y-4">
            @csrf

            <!-- Kata Sandi Saat Ini -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Kata Sandi Saat Ini</label>
                <div class="relative">
                    <input type="password" name="current_password" id="cp_current_password" required placeholder="Masukkan kata sandi lama Anda"
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 pr-10">
                    <button type="button" onclick="togglePasswordVisibility('cp_current_password')" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 p-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
            </div>

            <!-- Kata Sandi Baru -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Kata Sandi Baru</label>
                <div class="relative">
                    <input type="password" name="password" id="cp_new_password" required minlength="8" placeholder="Minimal 8 karakter"
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 pr-10">
                    <button type="button" onclick="togglePasswordVisibility('cp_new_password')" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 p-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
            </div>

            <!-- Konfirmasi Kata Sandi Baru -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Ulangi Kata Sandi Baru</label>
                <div class="relative">
                    <input type="password" name="password_confirmation" id="cp_confirm_password" required minlength="8" placeholder="Ketik ulang kata sandi baru"
                           class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 pr-10">
                    <button type="button" onclick="togglePasswordVisibility('cp_confirm_password')" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 p-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
            </div>

            <!-- Petunjuk Keamanan -->
            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
                <span class="text-[10px] font-bold text-slate-600 uppercase tracking-wider block">Ketentuan Keamanan:</span>
                <ul class="text-[11px] text-slate-500 space-y-0.5 list-disc list-inside">
                    <li>Minimal 8 karakter.</li>
                    <li>Tidak boleh sama dengan kata sandi saat ini.</li>
                    <li>Disarankan memadukan huruf besar, huruf kecil, dan angka.</li>
                </ul>
            </div>

            <!-- Tombol Aksi -->
            <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="closeChangePasswordModal()">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md">
                    Simpan Kata Sandi
                </x-button>
            </div>
        </form>
    </div>
</div>

<script>
    function openChangePasswordModal() {
        const modal = document.getElementById('modal-change-password');
        if (modal) {
            modal.classList.remove('hidden');
            const currentPass = document.getElementById('cp_current_password');
            if (currentPass) currentPass.focus();
        }
    }

    function closeChangePasswordModal() {
        const modal = document.getElementById('modal-change-password');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    function togglePasswordVisibility(id) {
        const input = document.getElementById(id);
        if (input) {
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    }
</script>
