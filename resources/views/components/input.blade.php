@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'type' => 'text',
    'placeholder' => '',
    'value' => '',
    'error' => null,
    'icon' => null,
])

@php
    $inputId = $id ?? $name ?? uniqid('input_');
@endphp

<div class="w-full">
    @if ($label)
        <label for="{{ $inputId }}" class="block text-[13px] font-medium text-[#1E2A24] mb-1.5">
            {{ $label }}
        </label>
    @endif

    <div class="relative rounded-[14px]">
        @if ($icon)
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-[#78857D]">
                {!! $icon !!}
            </div>
        @endif

        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $inputId }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            {{ $attributes->merge([
                'class' => 'block w-full min-h-[44px] rounded-[14px] border bg-white text-[15px] text-[#1E2A24] placeholder:text-[#78857D] focus:border-[#5B8C6B] focus:ring-2 focus:ring-[#5B8C6B]/20 focus:outline-none transition-colors ' . ($icon ? 'pl-10 pr-4' : 'px-3.5') . ' py-2.5 ' . ($error ? 'border-[#D64545] focus:border-[#D64545] focus:ring-[#D64545]/20' : 'border-[#E3E8E1]')
            ]) }}
        />
    </div>

    @if ($error)
        <p class="mt-1 text-[13px] text-[#D64545]">{{ $error }}</p>
    @elseif ($name && $errors->has($name))
        <p class="mt-1 text-[13px] text-[#D64545]">{{ $errors->first($name) }}</p>
    @endif
</div>
