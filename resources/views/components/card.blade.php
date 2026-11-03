@props([
    'padding' => 'p-4',
    'hover' => false,
])

<div {{ $attributes->merge(['class' => 'bg-surface rounded-[16px] border border-line overflow-hidden ' . ($hover ? 'transition-colors duration-150 hover:border-accent-sage' : '') . ' ' . $padding]) }}>
    {{ $slot }}
</div>
