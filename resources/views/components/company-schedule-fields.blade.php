@props([
    'prefix' => 'add',
    'accent' => 'indigo',
])

@php
    $focusClasses = $accent === 'emerald'
        ? 'focus:ring-emerald-500/20 focus:border-emerald-600'
        : 'focus:ring-indigo-500/20 focus:border-indigo-600';
    $checkClasses = $accent === 'emerald'
        ? 'text-emerald-600 focus:ring-emerald-500/20'
        : 'text-indigo-600 focus:ring-indigo-500/20';
    $badgeBg = $accent === 'emerald'
        ? 'peer-checked:bg-emerald-600 peer-checked:border-emerald-600'
        : 'peer-checked:bg-indigo-600 peer-checked:border-indigo-600';
    $days = \App\Models\Company::DAY_NAMES_SHORT;
@endphp

<div class="space-y-3" data-schedule-group="{{ $prefix }}">
    <div>
        <label class="block text-xs font-bold text-slate-700 mb-1.5">Hari Kerja Mingguan</label>
        <select name="work_days" id="{{ $prefix }}-work-days"
                onchange="toggleCompanyScheduleSection('{{ $prefix }}')"
                class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 {{ $focusClasses }} font-semibold text-slate-900">
            <option value="5_days">5 Hari Kerja (Senin s.d. Jumat)</option>
            <option value="6_days">6 Hari Kerja (Senin s.d. Sabtu)</option>
            <option value="7_days">7 Hari Penuh (Senin s.d. Minggu / On Project)</option>
            <option value="custom">Hari Kerja Khusus (Pilih Hari Sen–Min)</option>
        </select>
    </div>

    <div id="{{ $prefix }}-custom-days-box" class="hidden p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-slate-700">Pilih Hari Masuk Kerja</span>
            <span class="text-[10px] text-slate-400">Hari yang tidak dicentang dianggap libur</span>
        </div>
        <div class="grid grid-cols-7 gap-1.5">
            @foreach($days as $isoNum => $shortName)
                <label class="cursor-pointer select-none">
                    <input type="checkbox"
                           name="custom_work_days[]"
                           value="{{ $isoNum }}"
                           id="{{ $prefix }}-custom-day-{{ $isoNum }}"
                           class="sr-only peer {{ $prefix }}-custom-day-check"
                           {{ in_array($isoNum, [1, 2, 3, 4, 5], true) ? 'checked' : '' }}>
                    <span class="block text-center py-1.5 rounded-xl text-[11px] font-bold border border-slate-200 bg-white text-slate-600 transition-all {{ $badgeBg }} peer-checked:text-white">
                        {{ $shortName }}
                    </span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="p-3.5 rounded-2xl bg-slate-50/80 border border-slate-200/80 space-y-3">
        <div class="flex items-center justify-between gap-3">
            <div>
                <span class="text-xs font-bold text-slate-900 block">Jam Masuk & Pulang Khusus DU/DI</span>
                <span class="text-[11px] text-slate-500 font-medium">Aktifkan jika berbeda dari jam default sekolah</span>
            </div>
            <label class="flex items-center space-x-2 cursor-pointer select-none shrink-0">
                <input type="hidden" name="override_hours" value="0">
                <input type="checkbox"
                       name="override_hours"
                       value="1"
                       id="{{ $prefix }}-override-hours"
                       onchange="toggleCompanyScheduleSection('{{ $prefix }}')"
                       class="w-4 h-4 rounded border-slate-300 cursor-pointer {{ $checkClasses }}">
                <span class="text-xs font-bold text-slate-700">Khusus</span>
            </label>
        </div>

        <div id="{{ $prefix }}-custom-hours-box" class="hidden grid grid-cols-2 gap-3 pt-2.5 border-t border-slate-200/60">
            <div>
                <label class="block text-[10px] font-bold text-slate-600 mb-1">Mulai Masuk</label>
                <x-time-24-input name="checkin_start" id="{{ $prefix }}-checkin-start" value="06:00" :required="true" />
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-600 mb-1">Batas Telat</label>
                <x-time-24-input name="checkin_late" id="{{ $prefix }}-checkin-late" value="08:00" :required="true" />
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-600 mb-1">Batas Alfa</label>
                <x-time-24-input name="checkin_alfa" id="{{ $prefix }}-checkin-alfa" value="11:00" :required="true" />
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-600 mb-1">Jam Pulang</label>
                <x-time-24-input name="checkout_start" id="{{ $prefix }}-checkout-start" value="16:00" :required="true" />
            </div>
        </div>
    </div>
</div>

@once
<script>
    window.toggleCompanyScheduleSection = function (prefix) {
        const workDaysEl = document.getElementById(prefix + '-work-days');
        const customDaysBox = document.getElementById(prefix + '-custom-days-box');
        if (workDaysEl && customDaysBox) {
            customDaysBox.classList.toggle('hidden', workDaysEl.value !== 'custom');
        }

        const overrideCheck = document.getElementById(prefix + '-override-hours');
        const customHoursBox = document.getElementById(prefix + '-custom-hours-box');
        if (overrideCheck && customHoursBox) {
            customHoursBox.classList.toggle('hidden', !overrideCheck.checked);
        }
    };

    window.populateCompanyScheduleFields = function (prefix, data) {
        const workDaysEl = document.getElementById(prefix + '-work-days');
        if (workDaysEl) {
            workDaysEl.value = data.work_days || '5_days';
        }

        const selectedDays = Array.isArray(data.custom_work_days) && data.custom_work_days.length > 0
            ? data.custom_work_days.map(Number)
            : [1, 2, 3, 4, 5];

        document.querySelectorAll('.' + prefix + '-custom-day-check').forEach(el => {
            el.checked = selectedDays.includes(Number(el.value));
        });

        const overrideCheck = document.getElementById(prefix + '-override-hours');
        if (overrideCheck) {
            overrideCheck.checked = Boolean(data.override_hours);
        }

        const hours = data.custom_work_hours || {};
        const formatTime = (val, fallback) => val ? String(val).slice(0, 5) : fallback;
        const setVal = (id, val) => {
            if (typeof window.setTime24Value === 'function') {
                window.setTime24Value(id, val);
            } else {
                const el = document.getElementById(id);
                if (el) el.value = val;
            }
        };

        setVal(prefix + '-checkin-start', formatTime(hours.checkin_start, '06:00'));
        setVal(prefix + '-checkin-late', formatTime(hours.checkin_late, '08:00'));
        setVal(prefix + '-checkin-alfa', formatTime(hours.checkin_alfa, '11:00'));
        setVal(prefix + '-checkout-start', formatTime(hours.checkout_start, '16:00'));

        window.toggleCompanyScheduleSection(prefix);
    };
</script>
@endonce
