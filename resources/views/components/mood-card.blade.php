@props(['menu'])

<a
    href="{{ url('/kantin/' . $menu->canteen_id) }}"
    class="flex-shrink-0 w-[140px] bg-surface border border-line rounded-[16px] p-2 flex flex-col justify-between hover:border-accent-sage transition-colors group"
>
    <div>
        <div class="w-full h-28 rounded-[16px] overflow-hidden bg-sage-tint mb-2">
            <img
                src="{{ $menu->photo_url }}"
                alt="{{ $menu->name }}"
                class="w-full h-full object-cover transition duration-200 group-hover:scale-105"
                loading="lazy"
            />
        </div>
        <h4 class="text-[13px] font-semibold text-text-main line-clamp-1 leading-tight group-hover:text-accent-sage transition-colors">
            {{ $menu->name }}
        </h4>
        <p class="text-[11px] text-muted line-clamp-1 mt-0.5">
            {{ $menu->canteen->name ?? '' }}
        </p>
    </div>
    <div class="mt-2 pt-1 border-t border-line">
        <span class="text-[13px] font-bold text-brand">
            <x-rupiah :amount="$menu->price" />
        </span>
    </div>
</a>
