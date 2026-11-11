@props([
    'formAction',
    'idParam' => 'selected_ids[]',
    'itemLabel' => 'item',
    'rejectActionLabel' => 'Minta Revisi',
    'rejectActionValue' => 'revision',
    'barId' => 'bulk-action-bar',
    'notesRequired' => true,
])

<!-- Sticky Floating Batch Action Bar -->
<div id="{{ $barId }}" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 transition-all duration-300 transform translate-y-28 opacity-0 pointer-events-none w-[92%] max-w-2xl">
    <div class="bg-slate-900/95 backdrop-blur-md text-white px-5 py-3.5 rounded-2xl shadow-2xl border border-slate-700/80 flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="flex items-center space-x-3">
            <span class="flex h-3 w-3 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
            </span>
            <span class="text-xs font-bold text-slate-100">
                <span id="{{ $barId }}-count">0</span> {{ $itemLabel }} dipilih
            </span>
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
            <button type="button" onclick="{{ $barId }}DeselectAll()" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold transition-colors">
                Batal
            </button>
            <button type="button" onclick="{{ $barId }}PromptReject()" class="px-3 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-sm transition-colors flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                <span>{{ $rejectActionLabel }}</span>
            </button>
            <button type="button" onclick="{{ $barId }}SubmitApprove()" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition-colors flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <span>Setujui Semua</span>
            </button>
        </div>
    </div>
</div>

<!-- Modal Catatan / Alasan Penolakan/Revisi Massal -->
<div id="{{ $barId }}-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs hidden p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-100 animate-in fade-in zoom-in duration-200">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-800">{{ $rejectActionLabel }} Massal</h3>
            <button type="button" onclick="{{ $barId }}CloseModal()" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <p class="text-xs text-slate-600">
                Tuliskan catatan arahan yang akan dikirimkan kepada seluruh <strong id="{{ $barId }}-modal-count">0</strong> siswa terpilih:
            </p>
            <textarea id="{{ $barId }}-modal-notes" rows="3" placeholder="Contoh: Format penulisan aktivitas belum sesuai..." class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
            
            <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-3">
                <button type="button" onclick="{{ $barId }}CloseModal()" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                    Batal
                </button>
                <button type="button" onclick="{{ $barId }}SubmitReject()" class="px-3.5 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-sm transition-colors">
                    Kirim {{ $rejectActionLabel }}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Form for Bulk Action Submission -->
<form id="{{ $barId }}-form" action="{{ $formAction }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="action" id="{{ $barId }}-form-action" value="approve">
    <input type="hidden" name="notes" id="{{ $barId }}-form-notes" value="">
    <div id="{{ $barId }}-form-inputs"></div>
</form>

<script>
(function() {
    const barId = '{{ $barId }}';
    const bar = document.getElementById(barId);
    const counter = document.getElementById(barId + '-count');
    const modal = document.getElementById(barId + '-modal');
    const modalCount = document.getElementById(barId + '-modal-count');
    const modalNotes = document.getElementById(barId + '-modal-notes');
    const form = document.getElementById(barId + '-form');
    const formAction = document.getElementById(barId + '-form-action');
    const formNotes = document.getElementById(barId + '-form-notes');
    const formInputs = document.getElementById(barId + '-form-inputs');
    const idParam = '{{ $idParam }}';
    const rejectVal = '{{ $rejectActionValue }}';
    const isNotesRequired = {{ $notesRequired ? 'true' : 'false' }};

    function getCheckedBoxes() {
        return Array.from(document.querySelectorAll('input.bulk-checkbox-' + barId + ':checked'));
    }

    window[barId + 'UpdateState'] = function() {
        const checked = getCheckedBoxes();
        const count = checked.length;
        if (counter) counter.innerText = count;
        if (modalCount) modalCount.innerText = count;

        if (count > 0) {
            bar.classList.remove('translate-y-28', 'opacity-0', 'pointer-events-none');
            bar.classList.add('translate-y-0', 'opacity-100', 'pointer-events-auto');
        } else {
            bar.classList.remove('translate-y-0', 'opacity-100', 'pointer-events-auto');
            bar.classList.add('translate-y-28', 'opacity-0', 'pointer-events-none');
        }
    };

    window[barId + 'DeselectAll'] = function() {
        document.querySelectorAll('input.bulk-checkbox-' + barId).forEach(cb => cb.checked = false);
        const master = document.getElementById('master-checkbox-' + barId);
        if (master) master.checked = false;
        window[barId + 'UpdateState']();
    };

    window[barId + 'ToggleMaster'] = function(masterCb) {
        document.querySelectorAll('input.bulk-checkbox-' + barId).forEach(cb => cb.checked = masterCb.checked);
        window[barId + 'UpdateState']();
    };

    window[barId + 'PromptReject'] = function() {
        const checked = getCheckedBoxes();
        if (checked.length === 0) return;
        modal.classList.remove('hidden');
        modalNotes.value = '';
        modalNotes.focus();
    };

    window[barId + 'CloseModal'] = function() {
        modal.classList.add('hidden');
    };

    window[barId + 'SubmitApprove'] = function() {
        const checked = getCheckedBoxes();
        if (checked.length === 0) return;
        if (!confirm('Apakah Anda yakin ingin menyetujui ' + checked.length + ' {{ $itemLabel }} terpilih secara massal?')) return;

        formAction.value = 'approve';
        formNotes.value = '';
        formInputs.innerHTML = '';
        checked.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = idParam;
            input.value = cb.value;
            formInputs.appendChild(input);
        });
        form.submit();
    };

    window[barId + 'SubmitReject'] = function() {
        const checked = getCheckedBoxes();
        if (checked.length === 0) return;
        const notes = modalNotes.value.trim();
        if (isNotesRequired && !notes) {
            alert('Catatan/alasan wajib diisi untuk tindakan ini.');
            return;
        }

        formAction.value = rejectVal;
        formNotes.value = notes;
        formInputs.innerHTML = '';
        checked.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = idParam;
            input.value = cb.value;
            formInputs.appendChild(input);
        });
        form.submit();
    };

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('input.bulk-checkbox-' + barId).forEach(cb => {
            cb.addEventListener('change', window[barId + 'UpdateState']);
        });
        const master = document.getElementById('master-checkbox-' + barId);
        if (master) {
            master.addEventListener('change', function() {
                window[barId + 'ToggleMaster'](this);
            });
        }
    });
})();
</script>
