<!-- Modal Override Presensi Siswa (Kendala HP / Manual Guru) -->
<div id="override-attendance-modal" 
     onclick="if(event.target === this) closeOverrideAttendanceModal()" 
     class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 transition-all duration-200"
     role="dialog" 
     aria-modal="true"
     aria-labelledby="override-modal-title">
    <div class="bg-white rounded-3xl w-full max-w-lg shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in-95 duration-200 my-6 max-h-[92vh] flex flex-col">
        <!-- Header Modal -->
        <div class="px-5 sm:px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800 shrink-0">
            <div class="flex items-center gap-3 min-w-0 pr-2">
                <div class="w-9 h-9 rounded-2xl bg-indigo-500/20 border border-indigo-400/30 text-indigo-300 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <div class="min-w-0">
                    <h3 id="override-modal-title" class="text-sm sm:text-base font-extrabold tracking-tight text-white font-space truncate">
                        Override Presensi Siswa Binaan
                    </h3>
                    <p class="text-[11px] text-slate-400 font-medium truncate mt-0.5">
                        Solusi resmi saat siswa mengalami kendala perangkat (HP rusak, mati total, dll.)
                    </p>
                </div>
            </div>
            <button type="button" 
                    onclick="closeOverrideAttendanceModal()" 
                    aria-label="Tutup" 
                    class="p-1.5 text-slate-400 hover:text-white rounded-xl hover:bg-slate-800 transition-colors cursor-pointer shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Form Modal -->
        <form action="{{ route('guru.attendance.override') }}" method="POST" class="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1 custom-scrollbar">
            @csrf

            <!-- 1. Pilih Siswa Binaan -->
            <div>
                <label for="override_student_pkl_id" class="block text-xs font-bold text-slate-800 mb-1.5">
                    Nama Siswa Binaan <span class="text-rose-500">*</span>
                </label>
                <select name="student_pkl_id" id="override_student_pkl_id" required 
                        class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-semibold text-slate-900">
                    <option value="">-- Pilih Siswa Bimbingan --</option>
                    @if(isset($myStudents))
                        @foreach($myStudents as $sp)
                            <option value="{{ $sp->id }}">
                                {{ $sp->student->user->name ?? 'Siswa' }} (NISN: {{ $sp->student->nisn ?? '-' }}) — {{ $sp->company->name ?? 'DU/DI' }}
                            </option>
                        @endforeach
                    @endif
                </select>
                <p class="text-[10.5px] text-slate-400 mt-1">Hanya menampilkan siswa yang aktif berada dalam bimbingan Anda.</p>
            </div>

            <!-- 2. Tanggal & Status Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label for="override_date" class="block text-xs font-bold text-slate-800 mb-1.5">
                        Tanggal Presensi <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="date" id="override_date" 
                           max="{{ \Carbon\Carbon::today()->toDateString() }}" 
                           value="{{ \Carbon\Carbon::today()->toDateString() }}" required 
                           class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-semibold text-slate-900">
                    <p class="text-[10px] text-slate-400 mt-1">Maksimal hari ini.</p>
                </div>
                <div>
                    <label for="override_status" class="block text-xs font-bold text-slate-800 mb-1.5">
                        Status Kehadiran <span class="text-rose-500">*</span>
                    </label>
                    <select name="status" id="override_status" required 
                            class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-bold text-slate-900">
                        <option value="hadir">Hadir Tepat Waktu</option>
                        <option value="terlambat">Terlambat</option>
                        <option value="izin">Izin Resmi</option>
                        <option value="sakit">Sakit</option>
                        <option value="dinas_luar">Dinas Luar</option>
                        <option value="luar_kantor">WFH / Luar Kantor</option>
                        <option value="alfa">Alfa (Tanpa Keterangan)</option>
                    </select>
                </div>
            </div>

            <!-- 3. Jam Masuk & Jam Pulang -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                <div>
                    <label for="override_checkin_time" class="block text-[11px] font-bold text-slate-700 mb-1">
                        Jam Masuk (WIB)
                    </label>
                    <input type="time" name="checkin_time" id="override_checkin_time" value="07:30" 
                           class="w-full text-xs px-3 py-2 rounded-xl bg-white border border-slate-200 font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                    <p class="text-[9.5px] text-slate-400 mt-0.5">Default 07:30 WIB jika hadir.</p>
                </div>
                <div>
                    <label for="override_checkout_time" class="block text-[11px] font-bold text-slate-700 mb-1">
                        Jam Pulang (Opsional)
                    </label>
                    <input type="time" name="checkout_time" id="override_checkout_time" 
                           class="w-full text-xs px-3 py-2 rounded-xl bg-white border border-slate-200 font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                    <p class="text-[9.5px] text-slate-400 mt-0.5">Bisa dikosongkan jika belum pulang.</p>
                </div>
            </div>

            <!-- 4. Alasan Override / Bukti -->
            <div>
                <label for="override_reason" class="block text-xs font-bold text-slate-800 mb-1.5">
                    Alasan Override / Catatan Guru <span class="text-rose-500">*</span>
                </label>
                <textarea name="reason" id="override_reason" rows="3" required minlength="5" maxlength="255"
                          placeholder="Contoh: HP siswa rusak/mati total, siswa hadir fisik di lokasi DU/DI dan telah dikonfirmasi langsung oleh mentor lapangan..." 
                          class="w-full text-xs sm:text-sm p-3 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900 leading-relaxed"></textarea>
                <div class="flex items-center gap-1.5 mt-1.5 text-[10.5px] text-slate-500">
                    <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                    <span>Catatan ini tersimpan resmi di log audit dan rekap presensi sebagai pertanggungjawaban guru.</span>
                </div>
            </div>

            <!-- Footer Buttons -->
            <div class="flex items-center gap-2.5 pt-3 border-t border-slate-100">
                <button type="button" 
                        onclick="closeOverrideAttendanceModal()" 
                        class="flex-1 py-2.5 px-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs sm:text-sm font-bold transition-all cursor-pointer">
                    Batal
                </button>
                <button type="submit" 
                        class="flex-1 py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs sm:text-sm font-bold shadow-md shadow-indigo-600/20 transition-all cursor-pointer inline-flex items-center justify-center gap-1.5">
                    <svg class="w-4 h-4 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span>Simpan Override</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openOverrideAttendanceModal(studentPklId = null, date = null, status = 'hadir', checkinTime = '07:30', checkoutTime = '') {
        const modal = document.getElementById('override-attendance-modal');
        if (!modal) return;

        if (studentPklId) {
            const selectEl = document.getElementById('override_student_pkl_id');
            if (selectEl) {
                selectEl.value = studentPklId;
            }
        }
        if (date) {
            const dateEl = document.getElementById('override_date');
            if (dateEl) {
                dateEl.value = date;
            }
        }
        if (status) {
            const statusEl = document.getElementById('override_status');
            if (statusEl) {
                statusEl.value = status;
            }
        }
        const inEl = document.getElementById('override_checkin_time');
        if (inEl) {
            inEl.value = checkinTime || '07:30';
        }
        const outEl = document.getElementById('override_checkout_time');
        if (outEl) {
            outEl.value = checkoutTime || '';
        }
        const reasonEl = document.getElementById('override_reason');
        if (reasonEl && !reasonEl.value) {
            reasonEl.value = '';
        }

        modal.classList.remove('hidden');
    }

    function closeOverrideAttendanceModal() {
        const modal = document.getElementById('override-attendance-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }
</script>
