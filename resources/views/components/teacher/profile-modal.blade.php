@php
    $profileUser = auth()->user();
    $profileTeacher = $profileUser?->teacher;
    $currentPhone = $profileUser?->wa_number ?: ($profileUser?->profile?->phone ?? '');
@endphp

@if($profileUser && $profileTeacher)
<!-- Modal Profil Mandiri Guru Pembimbing -->
<div id="modal-teacher-profile" onclick="if(event.target === this) closeTeacherProfileModal()" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-2xl border border-slate-100 animate-in zoom-in-95 duration-150 space-y-4 max-h-[90vh] overflow-y-auto">
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center space-x-2.5 min-w-0">
                <div class="w-9 h-9 rounded-2xl bg-[#0B2B25] text-[#C6F26B] flex items-center justify-center font-extrabold text-xs shrink-0">
                    {{ strtoupper(mb_substr(trim($profileUser->name), 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Profil Guru Pembimbing</h3>
                    <p class="text-[11px] text-slate-400 truncate">{{ $profileUser->email }}</p>
                </div>
            </div>
            <button type="button" onclick="closeTeacherProfileModal()" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Ringkasan Jurusan -->
        <div class="p-3 rounded-2xl bg-[#F4F8F6] border border-[#DCE8E2] text-xs space-y-1.5">
            <span class="text-[10px] font-bold uppercase tracking-wider text-[#5A6B65] block">Jurusan yang Diampu</span>
            @if($profileTeacher->majors->isNotEmpty())
                <div class="flex flex-wrap gap-1.5">
                    @foreach($profileTeacher->majors as $mj)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-white text-[#0B2B25] border border-[#0B2B25]/15">
                            {{ $mj->code }} — {{ $mj->name }}
                        </span>
                    @endforeach
                </div>
            @else
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-white text-slate-600 border border-slate-200">
                    Lintas Jurusan
                </span>
            @endif
        </div>

        <!-- Form Update Profil Guru -->
        <form method="POST" action="{{ route('guru.profile.update') }}" class="space-y-3.5">
            @csrf

            <div>
                <label for="teacher-profile-name" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Nama Lengkap &amp; Gelar
                </label>
                <input type="text"
                       name="name"
                       id="teacher-profile-name"
                       required
                       maxlength="100"
                       value="{{ old('name', $profileUser->name) }}"
                       placeholder="Contoh: Herdi Permana, S.Kom"
                       class="w-full text-xs font-semibold px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600">
            </div>

            <div>
                <label for="teacher-profile-nip" class="block text-xs font-bold text-slate-700 mb-1.5">
                    NIP / Nomor Induk Pegawai
                </label>
                <input type="text"
                       name="nip"
                       id="teacher-profile-nip"
                       maxlength="30"
                       value="{{ old('nip', $profileTeacher->nip ?? '') }}"
                       placeholder="Kosongkan jika belum ada NIP"
                       class="w-full text-xs font-mono font-semibold px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600">
            </div>

            <div>
                <label for="teacher-profile-phone" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Nomor HP / WhatsApp Aktif
                </label>
                <input type="text"
                       name="phone"
                       id="teacher-profile-phone"
                       maxlength="25"
                       value="{{ old('phone', $currentPhone) }}"
                       placeholder="Contoh: 08123456789 atau 628123456789"
                       class="w-full text-xs font-mono font-semibold px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600">
                <p class="text-[11px] text-slate-400 mt-1">Dipakai untuk notifikasi darurat SOS siswa dan kontak di portal admin.</p>
            </div>

            <div class="pt-1">
                <button type="button"
                        onclick="closeTeacherProfileModal(); if (window.openChangePasswordModal) openChangePasswordModal();"
                        class="w-full py-2 px-3 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 text-xs font-bold flex items-center justify-center gap-1.5 transition-colors cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    <span>Ubah Kata Sandi Akun</span>
                </button>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <x-button type="button" variant="secondary" size="md" onclick="closeTeacherProfileModal()">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="md">
                    Simpan Profil
                </x-button>
            </div>
        </form>
    </div>
</div>

<script>
    function openTeacherProfileModal() {
        const modal = document.getElementById('modal-teacher-profile');
        if (modal) {
            modal.classList.remove('hidden');
            const nameInput = document.getElementById('teacher-profile-name');
            if (nameInput) nameInput.focus();
        }
    }

    function closeTeacherProfileModal() {
        const modal = document.getElementById('modal-teacher-profile');
        if (modal) {
            modal.classList.add('hidden');
        }
    }
</script>
@endif
