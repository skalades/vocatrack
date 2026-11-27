@props([
    'name',
    'id' => null,
    'value' => '',
    'required' => false,
    'withSeconds' => false,
    'wrapperClass' => 'bg-white border-slate-200 text-slate-800',
])

@php
    $inputId = $id ?: str_replace(['[', ']', '.'], '_', $name);
    $raw = trim((string) $value);
    $parts = $raw !== '' ? explode(':', $raw) : [];
    $initHour = isset($parts[0]) && is_numeric($parts[0]) ? str_pad((string) min(23, max(0, (int) $parts[0])), 2, '0', STR_PAD_LEFT) : '';
    $initMinute = isset($parts[1]) && is_numeric($parts[1]) ? str_pad((string) min(59, max(0, (int) $parts[1])), 2, '0', STR_PAD_LEFT) : '';
    $initSecond = isset($parts[2]) && is_numeric($parts[2]) ? str_pad((string) min(59, max(0, (int) $parts[2])), 2, '0', STR_PAD_LEFT) : '00';

    if ($required && $initHour === '') {
        $initHour = '00';
    }
    if ($required && $initMinute === '') {
        $initMinute = '00';
    }

    $normalizedValue = ($initHour !== '' && $initMinute !== '')
        ? ($withSeconds ? "{$initHour}:{$initMinute}:{$initSecond}" : "{$initHour}:{$initMinute}")
        : '';
@endphp

<div class="inline-flex w-full items-center justify-center gap-1.5 px-2.5 py-1.5 rounded-xl border font-mono text-xs font-bold {{ $wrapperClass }}"
     data-time24-root="{{ $inputId }}"
     data-with-seconds="{{ $withSeconds ? '1' : '0' }}"
     data-required="{{ $required ? '1' : '0' }}">
    <input type="hidden" name="{{ $name }}" id="{{ $inputId }}" value="{{ $normalizedValue }}">

    <select id="{{ $inputId }}_hour"
            aria-label="Jam (00-23)"
            onchange="window.syncTime24FromSelects('{{ $inputId }}')"
            style="background-image: none;"
            class="appearance-none bg-none bg-slate-100/80 hover:bg-slate-200/70 border border-slate-200/70 rounded-lg px-2.5 py-1 text-center text-xs font-mono font-bold focus:ring-2 focus:ring-indigo-500/20 focus:outline-none cursor-pointer">
        @unless($required)
            <option value="" {{ $initHour === '' ? 'selected' : '' }}>--</option>
        @endunless
        @for($h = 0; $h <= 23; $h++)
            @php $hh = str_pad((string) $h, 2, '0', STR_PAD_LEFT); @endphp
            <option value="{{ $hh }}" {{ $initHour === $hh ? 'selected' : '' }}>{{ $hh }}</option>
        @endfor
    </select>

    <span class="opacity-60 select-none font-extrabold">:</span>

    <select id="{{ $inputId }}_minute"
            aria-label="Menit (00-59)"
            onchange="window.syncTime24FromSelects('{{ $inputId }}')"
            style="background-image: none;"
            class="appearance-none bg-none bg-slate-100/80 hover:bg-slate-200/70 border border-slate-200/70 rounded-lg px-2.5 py-1 text-center text-xs font-mono font-bold focus:ring-2 focus:ring-indigo-500/20 focus:outline-none cursor-pointer">
        @unless($required)
            <option value="" {{ $initMinute === '' ? 'selected' : '' }}>--</option>
        @endunless
        @for($m = 0; $m <= 59; $m++)
            @php $mm = str_pad((string) $m, 2, '0', STR_PAD_LEFT); @endphp
            <option value="{{ $mm }}" {{ $initMinute === $mm ? 'selected' : '' }}>{{ $mm }}</option>
        @endfor
    </select>

    @if($withSeconds)
        <span class="opacity-50 select-none text-[11px]">:{{ $initSecond }}</span>
    @endif
</div>

@once
<script>
    window.syncTime24FromSelects = function (id) {
        const root = document.querySelector('[data-time24-root="' + id + '"]');
        const hiddenEl = document.getElementById(id);
        const hourEl = document.getElementById(id + '_hour');
        const minEl = document.getElementById(id + '_minute');
        if (!root || !hiddenEl || !hourEl || !minEl) return;

        const withSeconds = root.getAttribute('data-with-seconds') === '1';
        const isRequired = root.getAttribute('data-required') === '1';

        if (!hourEl.value || !minEl.value) {
            if (isRequired) {
                if (!hourEl.value) hourEl.value = '00';
                if (!minEl.value) minEl.value = '00';
            } else {
                // Jika salah satu dikosongkan pada field opsional, kosongkan keduanya
                if (!hourEl.value && minEl.value) minEl.value = '';
                if (!minEl.value && hourEl.value) hourEl.value = '';
                hiddenEl.value = '';
                return;
            }
        }

        hiddenEl.value = withSeconds
            ? (hourEl.value + ':' + minEl.value + ':00')
            : (hourEl.value + ':' + minEl.value);
    };

    window.setTime24Value = function (id, val) {
        const root = document.querySelector('[data-time24-root="' + id + '"]');
        const hiddenEl = document.getElementById(id);
        const hourEl = document.getElementById(id + '_hour');
        const minEl = document.getElementById(id + '_minute');
        if (!root || !hiddenEl || !hourEl || !minEl) return;

        const isRequired = root.getAttribute('data-required') === '1';
        const raw = val ? String(val).trim() : '';

        if (!raw) {
            hourEl.value = isRequired ? '00' : '';
            minEl.value = isRequired ? '00' : '';
            window.syncTime24FromSelects(id);
            return;
        }

        const parts = raw.split(':');
        const hh = String(Math.min(23, Math.max(0, parseInt(parts[0] || '0', 10) || 0))).padStart(2, '0');
        const mm = String(Math.min(59, Math.max(0, parseInt(parts[1] || '0', 10) || 0))).padStart(2, '0');
        hourEl.value = hh;
        minEl.value = mm;
        window.syncTime24FromSelects(id);
    };
</script>
@endonce
