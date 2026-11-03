@props([
    'variant' => 'neutral',
    'size' => 'sm',
])

@php
    $variants = [
        'success' => 'bg-sage-tint text-brand border border-line',
        'danger'  => 'bg-danger/10 text-danger border border-danger/20',
        'warning' => 'bg-butter/20 text-text-main border border-butter/40',
        'butter'  => 'bg-butter text-[#2F4A3A] border-transparent font-bold',
        'primary' => 'bg-brand text-on-brand border-transparent',
        'sage'    => 'bg-accent-sage text-white border-transparent',
        'neutral' => 'bg-page text-muted border border-line',
    ];

    $sizes = [
        'xs' => 'px-2 py-0.5 text-[11px]',
        'sm' => 'px-2.5 py-1 text-[13px]',
        'md' => 'px-3 py-1.5 text-[14px]',
    ];

    $classes = 'inline-flex items-center font-medium rounded-full ' . ($variants[$variant] ?? $variants['neutral']) . ' ' . ($sizes[$size] ?? $sizes['sm']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
