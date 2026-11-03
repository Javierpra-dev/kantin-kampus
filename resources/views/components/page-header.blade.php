@props([
    'title',
    'subtitle' => null,
    'backUrl' => null,
])

<header {{ $attributes->merge(['class' => 'sticky top-0 z-30 bg-surface border-b border-line px-5 py-3.5 flex items-center justify-between transition-colors']) }}>
    <div class="flex items-center gap-3 min-w-0">
        @if ($backUrl)
            <a href="{{ $backUrl }}" class="flex items-center justify-center w-11 h-11 rounded-full bg-page hover:bg-sage-tint text-text-main transition active:scale-95" aria-label="Kembali">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
        @endif
        <div class="truncate">
            <h1 class="text-[17px] font-bold font-heading text-text-main truncate leading-tight">{{ $title }}</h1>
            @if ($subtitle)
                <p class="text-[13px] text-muted truncate mt-0.5">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    @if (isset($actions))
        <div class="flex items-center gap-2">
            {{ $actions }}
        </div>
    @endif
</header>
