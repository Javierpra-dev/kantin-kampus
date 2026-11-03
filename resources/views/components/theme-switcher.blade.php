@php
    $items = [
        ['light', 'Terang'],
        ['auto',  'Otomatis'],
        ['dark',  'Gelap'],
    ];
@endphp

<div class="relative" style="position:relative">
    {{-- Toggle button: min 44x44px tap target, z-index above siblings --}}
    <button
        type="button"
        data-theme-toggle
        aria-label="Ganti tema"
        aria-haspopup="true"
        aria-expanded="false"
        style="position:relative;z-index:10;"
        class="flex items-center justify-center w-11 h-11 min-w-[44px] min-h-[44px] rounded-full bg-sage-tint hover:bg-line text-text-main transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-sage"
    >
        {{-- Sun icon (light / auto-light) --}}
        <svg class="theme-icon-sun w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="5"/>
            <line x1="12" y1="1" x2="12" y2="3"/>
            <line x1="12" y1="21" x2="12" y2="23"/>
            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
            <line x1="1" y1="12" x2="3" y2="12"/>
            <line x1="21" y1="12" x2="23" y2="12"/>
            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
        </svg>
        {{-- Moon icon (dark) --}}
        <svg class="theme-icon-moon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             style="display:none">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
        </svg>
    </button>

    {{-- Dropdown menu --}}
    <div
        class="theme-menu hidden absolute right-0 top-full mt-2 z-[200] bg-surface border border-line rounded-2xl shadow-lg overflow-hidden min-w-[130px]"
        role="menu"
    >
        @foreach ($items as [$val, $label])
        <button
            type="button"
            data-theme-choice="{{ $val }}"
            aria-checked="false"
            class="flex items-center justify-between w-full text-left px-4 py-2.5 text-[13px] text-text-main hover:bg-page transition"
            role="menuitemradio"
        >
            <span>{{ $label }}</span>
            {{-- Check mark shown when active (set by theme.js syncUI) --}}
            <svg class="theme-check w-4 h-4 text-brand flex-shrink-0" fill="none" stroke="currentColor"
                 viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                 style="display:none">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </button>
        @endforeach
    </div>
</div>
