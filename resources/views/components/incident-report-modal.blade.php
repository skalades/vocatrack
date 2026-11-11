<!-- Floating SOS / Lapor Kendala Pill Button (Mobile Siswa) -->
<div class="fixed bottom-20 right-4 z-40">
    <button type="button" onclick="document.getElementById('modal-incident-report').classList.remove('hidden')" 
            class="px-3.5 py-2 rounded-full bg-gradient-to-r from-rose-600 to-red-700 hover:from-rose-500 hover:to-red-600 text-white font-bold text-xs shadow-lg shadow-rose-600/35 border border-rose-400/30 flex items-center gap-1.5 transition-all active:scale-95 cursor-pointer">
        <span class="relative flex h-2.5 w-2.5">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-white"></span>
        </span>
        <span>Lapor Kendala / SOS</span>
    </button>
</div>

<!-- Modal Form Lapor Kendala & SOS Siswa -->
<div id="modal-incident-report" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-xs hidden p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-100 max-h-[92vh] flex flex-col animate-in zoom-in-95 duration-150">
        <!-- Modal Header -->
        <div class="px-5 py-4 bg-gradient-to-r from-rose-500 to-red-600 text-white flex items-center justify-between shrink-0">
            <div class="flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center text-white font-black text-sm">
                    !
                </div>
                <div>
                    <h3 class="text-sm font-extrabold tracking-tight">Lapor Kendala / Situasi Darurat</h3>
                    <p class="text-[10px] text-rose-100 font-medium">Kanal aduan & keselamatan siswa PKL</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modal-incident-report').classList.add('hidden')" class="p-1 rounded-xl text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Privacy Shield Notice -->
        <div class="p-3.5 bg-amber-50/90 border-b border-amber-200/80 text-amber-900 text-[11px] leading-relaxed flex items-start gap-2.5">
            <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <span>
                <strong>Jaminan Privasi:</strong> Laporan ini <strong>TIDAK DAPAT DILIHAT</strong> oleh mentor atau pihak DU/DI. Hanya diteruskan secara rahasia ke <strong>Guru Pembimbing</strong> dan <strong>Admin Sekolah</strong>.
            </span>
        </div>

        <!-- Form Body -->
        <form action="{{ route('student.incident.store') }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-3.5 overflow-y-auto flex-1">
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

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2.5">
                <button type="button" onclick="document.getElementById('modal-incident-report').classList.add('hidden')" 
                        class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                    Batal
                </button>
                <button type="submit" 
                        class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-500 hover:to-red-500 text-white text-xs font-bold shadow-md shadow-rose-600/30 transition-all">
                    Kirim Laporan Kendala
                </button>
            </div>
        </form>
    </div>
</div>
