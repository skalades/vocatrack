<!-- Modal Form Lapor Kendala & SOS Siswa (Floating Top/Centered Modal Dialog) -->
<div id="modal-incident-report" onclick="if(event.target === this) this.classList.add('hidden')" class="fixed inset-0 z-[85] bg-slate-900/60 backdrop-blur-xs hidden flex flex-col justify-start sm:justify-center items-center p-3 pt-14 sm:pt-16 transition-all">
    <div class="bg-white rounded-3xl shadow-2xl max-w-[400px] w-full overflow-hidden border border-slate-200/80 max-h-[85vh] flex flex-col animate-in zoom-in-95 duration-150">
        <!-- Modal Header -->
        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between shrink-0 bg-white">
            <div class="flex items-center space-x-2.5 min-w-0 flex-1 pr-2">
                <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-xs shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-xs sm:text-sm font-extrabold text-[#10211D] truncate leading-tight font-space">Lapor Kendala Siswa</h3>
                    <p class="text-[10px] sm:text-[11px] text-slate-400 font-medium truncate mt-0.5 leading-tight">Kanal aduan & keselamatan siswa PKL</p>
                </div>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
                <a href="{{ route('student.incidents') }}" class="px-2.5 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-bold transition-colors">
                    Riwayat
                </a>
                <button type="button" onclick="document.getElementById('modal-incident-report').classList.add('hidden')" aria-label="Tutup" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <!-- Privacy Shield Notice -->
        <div class="p-3 bg-amber-50/90 border-b border-amber-200/80 text-amber-900 text-[11px] leading-relaxed flex items-start gap-2.5 shrink-0">
            <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <span>
                <strong>Jaminan Privasi:</strong> Laporan ini <strong>TIDAK DAPAT DILIHAT</strong> oleh mentor DU/DI. Hanya diteruskan secara rahasia ke Guru Pembimbing & Admin Sekolah.
            </span>
        </div>

        <!-- Form Body -->
        <form action="{{ route('student.incident.store') }}" method="POST" enctype="multipart/form-data" class="p-4 sm:p-5 space-y-3.5 overflow-y-auto flex-1 custom-scrollbar">
            @csrf
            <div>
                <label for="category" class="block text-xs font-bold text-slate-700 mb-1">Kategori Kendala <span class="text-rose-500">*</span></label>
                <select name="category" id="category" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white font-medium text-slate-800 focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                    <option value="administrative">Masalah Administrasi / Jam Kerja Tidak Sesuai</option>
                    <option value="curriculum_mismatch">Penugasan Tidak Sesuai Jurusan / Kompetensi</option>
                    <option value="interpersonal">Konflik Personal / Ketidaknyamanan Lingkungan</option>
                    <option value="safety_health">Kesehatan & Keselamatan Kerja (K3 Kurang Memadai)</option>
                    <option value="emergency">Situasi Darurat / Kecelakaan Kerja / Pelecehan (SOS)</option>
                </select>
            </div>

            <div>
                <label for="urgency" class="block text-xs font-bold text-slate-700 mb-1">Tingkat Urgensi <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-4 gap-2">
                    <label class="cursor-pointer">
                        <input type="radio" name="urgency" value="low" class="peer sr-only">
                        <div class="p-2 rounded-xl border border-slate-200 text-center text-[11px] font-bold peer-checked:bg-slate-100 peer-checked:border-slate-400 peer-checked:text-slate-900 transition-all">
                            Rendah
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="urgency" value="medium" checked class="peer sr-only">
                        <div class="p-2 rounded-xl border border-slate-200 text-center text-[11px] font-bold peer-checked:bg-amber-100 peer-checked:border-amber-400 peer-checked:text-amber-900 transition-all">
                            Sedang
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="urgency" value="high" class="peer sr-only">
                        <div class="p-2 rounded-xl border border-slate-200 text-center text-[11px] font-bold peer-checked:bg-orange-100 peer-checked:border-orange-400 peer-checked:text-orange-900 transition-all">
                            Tinggi
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="urgency" value="critical" class="peer sr-only">
                        <div class="p-2 rounded-xl border border-slate-200 text-center text-[11px] font-bold peer-checked:bg-rose-100 peer-checked:border-rose-500 peer-checked:text-rose-900 transition-all">
                            SOS Darurat
                        </div>
                    </label>
                </div>
            </div>

            <div>
                <label for="subject" class="block text-xs font-bold text-slate-700 mb-1">Subjek / Inti Masalah <span class="text-rose-500">*</span></label>
                <input type="text" name="subject" id="subject" required placeholder="Contoh: Mesin produksi tidak dilengkapi pelindung tangan" 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 font-medium">
            </div>

            <div>
                <label for="description" class="block text-xs font-bold text-slate-700 mb-1">Kronologi & Uraian Kendala <span class="text-rose-500">*</span></label>
                <textarea name="description" id="description" rows="3" required placeholder="Ceritakan secara jelas kendala yang Anda alami..." 
                          class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 font-medium"></textarea>
            </div>

            <div>
                <label for="attachment" class="block text-xs font-bold text-slate-700 mb-1">Foto Bukti / Dokumen (Opsional)</label>
                <input type="file" name="attachment" id="attachment" accept="image/*" 
                       class="w-full text-xs p-2 rounded-xl border border-slate-300 bg-slate-50 font-medium file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-slate-200 file:text-slate-700">
            </div>

            <div class="pt-2 border-t border-slate-100 flex items-center gap-2.5">
                <button type="button" onclick="document.getElementById('modal-incident-report').classList.add('hidden')" 
                        class="flex-1 h-11 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs sm:text-sm font-bold transition-all active:scale-[0.98] cursor-pointer">
                    Batal
                </button>
                <button type="submit" 
                        class="flex-1 h-11 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white text-xs sm:text-sm font-bold shadow-xs transition-all active:scale-[0.98] cursor-pointer">
                    Kirim Laporan
                </button>
            </div>
        </form>
    </div>
</div>
