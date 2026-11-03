@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'disabled' => false,
])

@php
    $baseStyles = "inline-flex items-center justify-center font-semibold rounded-full transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none active:scale-[0.98] btn-bounce";

    $sizes = [
        'sm' => 'px-4 py-2 text-[13px] min-h-[44px] gap-1.5',
        'md' => 'px-5 py-3 text-[15px] min-h-[48px] gap-2',
        'lg' => 'px-6 h-[52px] min-h-[52px] text-[17px] gap-2.5',
    ];

    $variants = [
        'primary'   => 'bg-brand hover:opacity-90 text-on-brand focus:ring-accent-sage',
        'secondary' => 'bg-accent-sage hover:opacity-90 text-on-brand focus:ring-brand',
        'soft'      => 'bg-sage-tint hover:bg-line text-brand focus:ring-accent-sage',
        'outline'   => 'border border-line bg-surface hover:bg-page text-text-main focus:ring-accent-sage',
        'danger'    => 'bg-danger hover:opacity-90 text-white focus:ring-danger',
        'ghost'     => 'bg-transparent hover:bg-sage-tint text-text-main focus:ring-accent-sage',
    ];

    $classes = $baseStyles . ' ' . ($sizes[$size] ?? $sizes['md']) . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
