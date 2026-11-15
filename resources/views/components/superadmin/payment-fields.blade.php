{{-- Status pembayaran invoice yang diterbitkan otomatis (hanya untuk nominal > 0). --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5">Status Pembayaran Invoice</label>
        <select name="payment_status" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
            <option value="unpaid">Belum dibayar (terbitkan tagihan)</option>
            <option value="paid">Sudah diterima (lunas)</option>
        </select>
    </div>
    <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5">Metode Pembayaran (Opsional)</label>
        <input type="text" name="payment_method" maxlength="100" placeholder="Contoh: Transfer BNI, Dana BOS"
               class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500/20">
    </div>
</div>
