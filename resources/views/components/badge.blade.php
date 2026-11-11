@props([
    'variant' => 'neutral',
    'dot' => true,
    'size' => 'md',
])

@php
    $normalized = strtolower(str_replace([' ', '-'], '_', $variant));

    $colorClasses = match ($normalized) {
        'success', 'hadir', 'approved', 'disetujui', 'aktif', 'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200/80',
        'warning', 'terlambat', 'pending', 'menunggu', 'review', 'proses', 'submitted', 'revision' => 'bg-amber-50 text-amber-700 border-amber-200/80',
        'danger', 'alfa', 'rejected', 'ditolak', 'nonaktif', 'inactive' => 'bg-rose-50 text-rose-700 border-rose-200/80',
        'info', 'izin', 'review_guru' => 'bg-sky-50 text-sky-700 border-sky-200/80',
        'purple', 'sakit' => 'bg-purple-50 text-purple-700 border-purple-200/80',
        'teal', 'dinas_luar', 'wfh' => 'bg-teal-50 text-teal-700 border-teal-200/80',
        'indigo', 'luar_kantor' => 'bg-indigo-50 text-indigo-700 border-indigo-200/80',
        default => 'bg-slate-50 text-slate-700 border-slate-200/80',
    };

    $dotColors = match ($normalized) {
        'success', 'hadir', 'approved', 'disetujui', 'aktif', 'active' => 'bg-emerald-500',
        'warning', 'terlambat', 'pending', 'menunggu', 'review', 'proses', 'submitted', 'revision' => 'bg-amber-500',
        'danger', 'alfa', 'rejected', 'ditolak', 'nonaktif', 'inactive' => 'bg-rose-500',
        'info', 'izin', 'review_guru' => 'bg-sky-500',
        'purple', 'sakit' => 'bg-purple-500',
        'teal', 'dinas_luar', 'wfh' => 'bg-teal-500',
        'indigo', 'luar_kantor' => 'bg-indigo-500',
        default => 'bg-slate-400',
    };

    $sizeClasses = match ($size) {
        'xs' => 'text-[10px] px-2 py-0.5 font-semibold',
        'sm' => 'text-[11px] px-2.5 py-0.5 font-bold',
        'lg' => 'text-xs px-3 py-1 font-bold',
        default => 'text-[11px] px-2.5 py-0.5 font-semibold',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full border shadow-2xs {$colorClasses} {$sizeClasses}"]) }}>
    @if($dot)
        <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $dotColors }}"></span>
    @endif
    <span>{{ $slot }}</span>
</span>
