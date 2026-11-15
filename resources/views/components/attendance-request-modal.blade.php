<!-- Modal Quick Request (Centered Modal Dialog) -->
<div id="request-modal" onclick="if(event.target === this) closeRequestModal()" class="hidden fixed inset-0 z-[60] bg-slate-950/75 backdrop-blur-md flex items-center justify-center p-3.5 sm:p-4 animate-in fade-in duration-200">
    <div class="bg-white w-full max-w-sm sm:max-w-md rounded-3xl p-5 sm:p-6 shadow-2xl max-h-[88vh] overflow-y-auto border border-slate-100 animate-in zoom-in-95 duration-200 space-y-4 my-auto custom-scrollbar">

        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-extrabold text-[#0B2B25] tracking-tight font-space" id="modal-req-title">Pengajuan Presensi Khusus</h3>
                <p id="modal-req-hint" class="text-[11px] text-[#0F7A66] font-semibold mt-0.5">Approval: Guru Pembimbing</p>
            </div>
            <button type="button" onclick="closeRequestModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 hover:text-slate-800 transition-colors cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('student.request.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3.5">
            @csrf
            <input type="hidden" name="type" id="modal-req-type" value="izin">

            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Mulai Tanggal</label>
                    <input type="date" name="start_date" value="{{ \Carbon\Carbon::today()->toDateString() }}" required 
                           class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0B2B25]/20 focus:border-[#0B2B25] font-medium text-slate-800">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ \Carbon\Carbon::today()->toDateString() }}" required 
                           class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0B2B25]/20 focus:border-[#0B2B25] font-medium text-slate-800">
                </div>
            </div>

            <div id="dest-container" class="hidden">
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Lokasi Tujuan / Dinas</label>
                <input type="text" name="destination_location" placeholder="Contoh: Kantor Cabang Tangerang" 
                       class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0B2B25]/20 focus:border-[#0B2B25] font-medium text-slate-800">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Alasan Pengajuan (Min. 10 Karakter)</label>
                <textarea name="reason" rows="3" required placeholder="Jelaskan alasan pengajuan secara rinci..." 
                          class="w-full text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0B2B25]/20 focus:border-[#0B2B25] font-medium leading-relaxed text-slate-800"></textarea>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Foto Bukti / Surat Keterangan (Opsional)</label>
                <input type="file" name="attachment" accept="image/jpeg,image/png,image/webp" 
                       class="w-full text-[11px] text-slate-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:bg-slate-100 file:text-slate-700 file:font-semibold cursor-pointer">
                <p class="text-[10px] text-slate-400 mt-1">Format: JPG, PNG, atau WEBP (Maksimal 2MB)</p>
            </div>

            <div class="flex items-center space-x-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeRequestModal()" class="flex-1 py-3 rounded-2xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 cursor-pointer transition-colors active:scale-95">
                    Batal
                </button>
                <button type="submit" class="flex-1 py-3 rounded-2xl bg-[#0B2B25] hover:bg-[#12382F] text-white text-xs font-bold shadow-md shadow-[#0B2B25]/20 cursor-pointer transition-all active:scale-95">
                    Ajukan Permohonan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    if (typeof openRequestModal === 'undefined') {
        function openRequestModal(type) {
            const typeInput = document.getElementById('modal-req-type');
            if (typeInput) typeInput.value = type;

            const titleMap = {
                'izin': 'Permohonan Izin (Verifikator: Guru)',
                'sakit': 'Permohonan Sakit (Verifikator: Guru)',
                'dinas_luar': 'Pengajuan Dinas Luar (DU/DI)',
                'luar_kantor': 'Pengajuan WFH / Luar Kantor'
            };

            const titleElem = document.getElementById('modal-req-title');
            if (titleElem) {
                titleElem.innerText = titleMap[type] || 'Pengajuan Presensi Khusus';
            }

            const hintMap = {
                'izin': 'Approval: Guru Pembimbing Sekolah',
                'sakit': 'Approval: Guru Pembimbing Sekolah',
                'dinas_luar': 'Approval: Mentor / Pembimbing Industri (Perusahaan DU/DI)',
                'luar_kantor': 'Approval: Mentor / Pembimbing Industri (Perusahaan DU/DI)'
            };
            const hintElem = document.getElementById('modal-req-hint');
            if (hintElem) {
                hintElem.innerText = hintMap[type] || 'Approval: Pembimbing';
            }

            const dest = document.getElementById('dest-container');
            if (dest) {
                if (type === 'dinas_luar') {
                    dest.classList.remove('hidden');
                } else {
                    dest.classList.add('hidden');
                }
            }

            const modal = document.getElementById('request-modal');
            if (modal) {
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeRequestModal() {
            const modal = document.getElementById('request-modal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }
    }
</script>
