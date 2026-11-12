@props([
    'minWidth' => null,
])

<div {{ $attributes->merge(['class' => 'overflow-x-auto custom-scrollbar relative w-full min-w-0']) }}>
    {{ $slot }}
</div>
