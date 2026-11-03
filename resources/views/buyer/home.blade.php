<x-layouts.buyer title="Kantin Kampus - Beranda">
    <div
        x-data="{
            query: '{{ addslashes($search) }}',
            activeFilter: '{{ addslashes($filter) }}',
            isLoading: false,
            searchResults: null,
            searchAbortController: null,
            conflictModalOpen: false,
            pendingMenu: null,
            cartTotal: {{ (int) ($cart['total'] ?? 0) }},
            cartCount: {{ (int) ($cart['count'] ?? 0) }},
            isAdding: false,

            init() {
                if (this.query.trim().length >= 2 || (this.activeFilter && this.activeFilter !== 'Semua')) {
                    this.performSearch();
                }
            },

            setFilter(chip) {
                if (this.activeFilter === chip) {
                    this.activeFilter = 'Semua';
                } else {
                    this.activeFilter = chip;
                }
                this.performSearch();
            },

            clearQuery() {
                this.query = '';
                if (this.activeFilter && this.activeFilter !== 'Semua') {
                    this.performSearch();
                } else {
                    if (this.searchAbortController) {
                        this.searchAbortController.abort();
                    }
                    this.searchResults = null;
                    this.isLoading = false;
                }
            },

            async performSearch() {
                if (this.searchAbortController) {
                    this.searchAbortController.abort();
                }

                const trimmed = this.query.trim();
                const hasValidQuery = trimmed.length >= 2;
                const hasFilter = this.activeFilter && this.activeFilter !== 'Semua';

                if (!hasValidQuery && !hasFilter) {
                    this.searchResults = null;
                    this.isLoading = false;
                    return;
                }

                this.searchAbortController = new AbortController();
                this.isLoading = true;

                try {
                    const params = new URLSearchParams();
                    if (hasValidQuery) params.append('q', trimmed);
                    if (hasFilter) params.append('filter', this.activeFilter);

                    const res = await fetch(`/cari?${params.toString()}`, {
                        signal: this.searchAbortController.signal,
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    this.searchResults = data;
                } catch (e) {
                    if (e.name !== 'AbortError') {
                        console.error('Search error:', e);
                    }
                } finally {
                    this.isLoading = false;
                }
            },

            async addToCart(menuId, menuName, isOpen) {
                if (!isOpen || this.isAdding) return;
                this.isAdding = true;

                try {
                    const res = await fetch('{{ route('cart.add') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ menu_id: menuId, quantity: 1 })
                    });
                    const data = await res.json();

                    if (data.conflict) {
                        this.pendingMenu = {
                            id: menuId,
                            name: menuName,
                            currentCanteen: data.current_canteen,
                            newCanteen: data.new_canteen
                        };
                        this.conflictModalOpen = true;
                    } else if (data.success) {
                        this.cartTotal = data.cart.total;
                        this.cartCount = data.cart.count;
                    } else if (data.message) {
                        alert(data.message);
                    }
                } catch (e) {
                    console.error('Error adding to cart:', e);
                } finally {
                    this.isAdding = false;
                }
            },

            async confirmReplaceCart() {
                if (!this.pendingMenu || this.isAdding) return;
                this.isAdding = true;

                try {
                    const res = await fetch('{{ route('cart.add') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ menu_id: this.pendingMenu.id, quantity: 1, force: true })
                    });
                    const data = await res.json();

                    if (data.success) {
                        this.cartTotal = data.cart.total;
                        this.cartCount = data.cart.count;
                        this.conflictModalOpen = false;
                        this.pendingMenu = null;
                    }
                } catch (e) {
                    console.error('Error replacing cart:', e);
                } finally {
                    this.isAdding = false;
                }
            }
        }"
        class="relative min-h-screen pb-24"
    >
        <!-- Top Greeting Section -->
        <div class="px-5 pt-8 pb-4">
            <div class="flex items-start justify-between gap-4">
                <h1 class="text-[28px] font-extrabold font-heading text-text-main leading-tight tracking-tight">
                    Mau makan apa hari ini?
                </h1>
                <div class="flex-shrink-0 pt-0.5">
                    <x-theme-switcher />
                </div>
            </div>

            <!-- Search Bar -->
            <div class="mt-4">
                <label for="home-search-input" class="sr-only">Cari makanan, minuman, atau kantin</label>
                <div class="relative">
                    <input
                        id="home-search-input"
                        type="search"
                        x-model="query"
                        @input.debounce.300ms="performSearch()"
                        @keydown.enter.prevent
                        placeholder="Cari menu atau kantin..."
                        autocomplete="off"
                        class="w-full pl-11 pr-16 py-3 bg-surface rounded-[14px] border border-line text-[15px] text-text-main placeholder-muted focus:outline-none focus:border-accent-sage focus:ring-2 focus:ring-accent-sage/20 transition"
                    />
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-muted">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <!-- Right Controls: Spinner & Clear Button -->
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center gap-1.5">
                        <template x-if="isLoading">
                            <svg class="animate-spin w-4 h-4 text-accent-sage" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>

                        <button
                            type="button"
                            x-show="query.length > 0"
                            @click="clearQuery()"
                            class="p-1 rounded-full text-muted hover:text-text-main hover:bg-page transition"
                            aria-label="Hapus pencarian"
                            style="display: none;"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Chips (Horizontal Scroll) -->
        <div class="px-5 py-1">
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-1" style="scrollbar-width: none; -ms-overflow-style: none;">
                @foreach ($filterChips as $chip)
                    <button
                        type="button"
                        @click="setFilter('{{ $chip }}')"
                        :class="activeFilter === '{{ $chip }}' ? 'bg-brand text-on-brand' : 'bg-surface text-text-main border border-line hover:border-accent-sage'"
                        class="flex-shrink-0 px-4 py-2 rounded-full text-[13px] font-medium transition-colors"
                    >
                        {{ $chip }}
                    </button>
                @endforeach
                <div class="w-3 flex-shrink-0"></div>
            </div>
        </div>

        <!-- Active Search / Filter Feedback (SSR) -->
        @if ($isSearchingOrFiltering)
            <div class="px-5 pt-3 pb-1 flex items-center justify-between text-[13px] text-muted">
                <span>
                    Hasil untuk:
                    @if ($filter !== 'Semua')
                        <span class="font-medium text-brand">"{{ $filter }}"</span>
                    @endif
                    @if ($search)
                        kata kunci <span class="font-medium text-text-main">"{{ $search }}"</span>
                    @endif
                </span>
                <a href="{{ url('/') }}" class="text-[13px] font-semibold text-accent-sage hover:underline">Reset</a>
            </div>
        @endif

        <!-- ========================================== -->
        <!-- LIVE SEARCH & FILTER RESULTS VIEW          -->
        <!-- ========================================== -->
        <template x-if="searchResults !== null">
            <div class="px-5 pt-4 pb-8 space-y-5">
                <!-- Result heading & Reset button -->
                <div class="flex items-center justify-between">
                    <h2
                        class="text-[17px] font-bold font-heading text-text-main"
                        x-text="searchResults.query ? `${searchResults.menus.length} menu ditemukan untuk '${searchResults.query}'` : `${searchResults.menus.length} menu ditemukan`"
                    ></h2>
                    <button
                        type="button"
                        @click="clearQuery(); activeFilter = 'Semua'; searchResults = null;"
                        class="text-[13px] font-semibold text-accent-sage hover:underline"
                    >
                        Reset
                    </button>
                </div>

                <!-- Empty State -->
                <template x-if="searchResults.menus.length === 0 && searchResults.canteens.length === 0">
                    <div class="text-center py-12 px-6 bg-surface rounded-[20px] border border-line space-y-4">
                        <div class="w-16 h-16 mx-auto rounded-full bg-sage-tint flex items-center justify-center text-3xl">
                            🔍
                        </div>
                        <div>
                            <h3 class="text-[16px] font-bold text-text-main">
                                Tidak ada menu yang cocok dengan "<span x-text="searchResults.query || activeFilter"></span>"
                            </h3>
                            <p class="text-[13px] text-muted mt-1 max-w-xs mx-auto">
                                Coba kata lain, misalnya "es teh" atau "ayam".
                            </p>
                        </div>
                        <div class="pt-2">
                            <a
                                href="{{ url('/pilihin-aku') }}"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand text-on-brand text-[13px] font-semibold rounded-full hover:opacity-90 transition shadow-sm"
                            >
                                <svg class="w-4 h-4 text-accent-sage" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 3h5v5"/><path d="M4 20L21 3"/><path d="M21 16v5h-5"/><path d="M15 15l6 6"/><path d="M4 4l5 5"/>
                                </svg>
                                <span>Bingung mau makan apa? Pilihin aku</span>
                            </a>
                        </div>
                    </div>
                </template>

                <!-- Menu Results List -->
                <div class="space-y-3">
                    <template x-for="item in searchResults.menus" :key="item.id">
                        <div
                            @click="window.location.href = `/kantin/${item.canteen.id}?menu=${item.id}`"
                            :class="!item.is_orderable ? 'opacity-60 bg-page/50' : 'hover:border-accent-sage/60 cursor-pointer'"
                            class="bg-surface rounded-2xl border border-line p-3 shadow-sm flex items-center justify-between gap-3 transition"
                        >
                            <!-- Square Photo -->
                            <div class="relative w-20 h-20 flex-shrink-0 rounded-xl overflow-hidden bg-sage-tint">
                                <img
                                    :src="item.photo_url"
                                    :alt="item.name"
                                    class="w-full h-full object-cover"
                                    :class="!item.is_orderable ? 'grayscale' : ''"
                                    loading="lazy"
                                />
                                <template x-if="!item.is_available || item.is_out_of_stock">
                                    <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                                        <span class="bg-danger text-white font-bold text-[10px] px-1.5 py-0.5 rounded shadow">Habis</span>
                                    </div>
                                </template>
                            </div>

                            <!-- Details -->
                            <div class="flex-1 min-w-0 pr-1">
                                <h3
                                    class="text-sm font-bold text-text-main truncate leading-snug"
                                    :class="!item.is_orderable ? 'text-muted' : ''"
                                    x-text="item.name"
                                ></h3>

                                <!-- Canteen line & badges -->
                                <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                    <span class="text-xs text-muted" x-text="`di ${item.canteen.name}`"></span>

                                    <!-- Status badge -->
                                    <template x-if="!item.canteen.is_open">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-danger/10 text-danger border border-danger/20">
                                            Kantin tutup
                                        </span>
                                    </template>
                                    <template x-if="item.canteen.is_open && (item.is_out_of_stock || !item.is_available)">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-danger/10 text-danger border border-danger/20">
                                            Habis
                                        </span>
                                    </template>
                                    <template x-if="item.canteen.is_open && item.is_available && !item.is_out_of_stock">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-sage-tint text-brand">
                                            Buka
                                        </span>
                                    </template>

                                    <!-- Stock badge -->
                                    <template x-if="item.daily_stock !== null && item.daily_stock > 0 && item.daily_stock <= 5">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-butter/20 text-text-main border border-butter/40" x-text="`Sisa ${item.daily_stock}`"></span>
                                    </template>

                                    <!-- Promo badge -->
                                    <template x-if="item.is_promo_active">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-butter/20 text-text-main border border-butter/40">
                                            Promo
                                        </span>
                                    </template>
                                </div>

                                <!-- Price -->
                                <div class="mt-2 flex items-center gap-2">
                                    <template x-if="item.is_promo_active">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-sm font-extrabold text-brand" x-text="'Rp ' + Number(item.promo_price).toLocaleString('id-ID')"></span>
                                            <span class="text-xs text-muted line-through" x-text="'Rp ' + Number(item.price).toLocaleString('id-ID')"></span>
                                        </div>
                                    </template>
                                    <template x-if="!item.is_promo_active">
                                        <span class="text-sm font-extrabold text-brand" x-text="'Rp ' + Number(item.price).toLocaleString('id-ID')"></span>
                                    </template>
                                </div>
                            </div>

                            <!-- Round '+' Button -->
                            <div class="flex-shrink-0" @click.stop>
                                <template x-if="item.is_orderable">
                                    <button
                                        type="button"
                                        @click="addToCart(item.id, item.name, item.canteen.is_open)"
                                        class="w-9 h-9 rounded-full bg-brand hover:opacity-90 active:scale-90 text-on-brand flex items-center justify-center shadow-md transition-all focus:outline-none"
                                        :aria-label="`Tambah ${item.name} ke keranjang`"
                                    >
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </button>
                                </template>
                                <template x-if="!item.is_orderable">
                                    <button
                                        type="button"
                                        disabled
                                        class="w-9 h-9 rounded-full bg-line text-muted flex items-center justify-center cursor-not-allowed"
                                        :title="!item.canteen.is_open ? 'Kantin tutup' : 'Menu habis'"
                                        aria-label="Tidak dapat dipesan"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Matching Canteens (appears after menu results) -->
                <template x-if="searchResults.canteens.length > 0">
                    <div class="pt-4 space-y-3">
                        <h3 class="text-xs font-bold text-muted uppercase tracking-wider px-1">Kantin</h3>
                        <div class="space-y-2.5">
                            <template x-for="c in searchResults.canteens" :key="c.id">
                                <a
                                    :href="`/kantin/${c.id}`"
                                    class="bg-surface border border-line rounded-[16px] p-3 flex items-center gap-3.5 transition-colors hover:border-accent-sage block group"
                                    :class="!c.is_open ? 'opacity-70' : ''"
                                >
                                    <div class="w-14 h-14 rounded-[12px] overflow-hidden bg-sage-tint flex-shrink-0">
                                        <img :src="c.photo_url" :alt="c.name" class="w-full h-full object-cover" :class="!c.is_open ? 'grayscale' : ''" loading="lazy" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <h4 class="text-[14px] font-bold font-heading text-text-main truncate" x-text="c.name"></h4>
                                            <template x-if="c.is_open">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-sage-tint text-brand flex-shrink-0">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-accent-sage"></span>
                                                    Buka
                                                </span>
                                            </template>
                                            <template x-if="!c.is_open">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-danger text-white flex-shrink-0">
                                                    Tutup
                                                </span>
                                            </template>
                                        </div>
                                        <p class="text-[12px] text-muted line-clamp-1 mt-0.5" x-text="c.description || 'Menyediakan hidangan pilihan.'"></p>
                                    </div>
                                </a>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <!-- ========================================== -->
        <!-- DEFAULT HOME VIEW (When no search active)  -->
        <!-- ========================================== -->
        <div x-show="searchResults === null">
            <!-- "Pilihin Aku" Decision Helper Entry Point -->
            <div class="px-5 pt-3 pb-1">
                <a
                    href="{{ url('/pilihin-aku') }}"
                    class="w-full flex items-center justify-center gap-2.5 py-3 px-4 rounded-2xl bg-surface border border-line hover:border-accent-sage text-text-main text-[14px] font-bold shadow-xs hover:bg-sage-tint/40 transition group"
                >
                    <svg class="w-4 h-4 text-accent-sage transition-transform group-hover:rotate-12 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 3h5v5"/>
                        <path d="M4 20L21 3"/>
                        <path d="M21 16v5h-5"/>
                        <path d="M15 15l6 6"/>
                        <path d="M4 4l5 5"/>
                    </svg>
                    <span>Bingung mau makan apa? Pilihin aku</span>
                </a>
            </div>

            <!-- 1. Auto-sliding Canteen Carousel -->
            @if ($canteens->isNotEmpty() && !$isSearchingOrFiltering)
                <x-canteen-carousel :canteens="$canteens" />
            @endif

            <!-- 2. Mood Recommendations -->
            @if (!empty($moodRows))
                <div class="pt-6 space-y-5">
                    <div class="px-5">
                        <h2 class="text-[17px] font-bold font-heading text-text-main">Lagi pengen apa?</h2>
                    </div>

                    @foreach ($moodRows as $row)
                        <div>
                            <div class="px-5 mb-2.5">
                                <h3 class="text-[15px] font-bold font-heading text-text-main">{{ $row['title'] }}</h3>
                            </div>
                            <div class="flex gap-3 overflow-x-auto no-scrollbar px-5 pb-1" style="scrollbar-width: none; -ms-overflow-style: none;">
                                @foreach ($row['menus'] as $menu)
                                    <x-mood-card :menu="$menu" />
                                @endforeach
                                <div class="w-1 flex-shrink-0"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- 3. Promo Sore Ini Row (Phase 11b) -->
            @if (isset($promoMenus) && $promoMenus->isNotEmpty())
                <div class="pt-6">
                    <div class="px-5 flex items-center justify-between mb-3">
                        <h2 class="text-[17px] font-bold font-heading text-text-main flex items-center gap-2">
                            <span>Promo sore ini</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-butter/20 text-text-main border border-butter/40">Hemat</span>
                        </h2>
                    </div>
                    <div class="flex gap-3 overflow-x-auto no-scrollbar px-5 pb-1" style="scrollbar-width: none; -ms-overflow-style: none;">
                        @foreach ($promoMenus as $pMenu)
                            <a href="{{ url('/kantin/' . $pMenu->canteen_id) }}" class="flex-shrink-0 w-36 bg-surface border border-line rounded-[16px] p-2.5 flex flex-col justify-between hover:border-accent-sage transition-colors group">
                                <div>
                                    <div class="w-full h-24 rounded-[14px] overflow-hidden bg-sage-tint mb-2 relative">
                                        <img src="{{ $pMenu->photo_url }}" alt="{{ $pMenu->name }}" class="w-full h-full object-cover transition duration-200 group-hover:scale-105" loading="lazy" />
                                        <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 rounded text-[10px] font-bold bg-butter text-text-main shadow">
                                            Promo
                                        </span>
                                    </div>
                                    <h3 class="text-[13px] font-semibold text-text-main line-clamp-1 leading-tight group-hover:text-accent-sage transition-colors">{{ $pMenu->name }}</h3>
                                    <p class="text-[11px] text-muted line-clamp-1 mt-0.5">{{ $pMenu->canteen->name ?? '' }}</p>
                                </div>
                                <div class="mt-2 pt-1 border-t border-line flex items-baseline gap-1.5">
                                    <span class="text-[13px] font-bold text-brand">
                                        @rupiah($pMenu->promo_price)
                                    </span>
                                    <span class="text-[10px] text-muted line-through">
                                        @rupiah($pMenu->price)
                                    </span>
                                </div>
                            </a>
                        @endforeach
                        <div class="w-1 flex-shrink-0"></div>
                    </div>
                </div>
            @endif

            <!-- 4. Menu Populer Row -->
            @php
                $popularMenus = $canteens->flatMap(function($c) {
                    return $c->menus->where('is_available', true)->take(2)->map(function($m) use ($c) {
                        $m->canteen_name = $c->name;
                        $m->canteen_id = $c->id;
                        return $m;
                    });
                })->unique('id')->take(6);
            @endphp

            @if ($popularMenus->isNotEmpty())
                <div class="pt-6">
                    <div class="px-5 flex items-center justify-between mb-3">
                        <h2 class="text-[17px] font-bold font-heading text-text-main">Menu populer</h2>
                    </div>
                    <div class="flex gap-3 overflow-x-auto no-scrollbar px-5 pb-1" style="scrollbar-width: none; -ms-overflow-style: none;">
                        @foreach ($popularMenus as $menu)
                            <a href="{{ url('/kantin/' . $menu->canteen_id) }}" class="flex-shrink-0 w-36 bg-surface border border-line rounded-[16px] p-2.5 flex flex-col justify-between hover:border-accent-sage transition-colors group">
                                <div>
                                    <div class="w-full h-24 rounded-[14px] overflow-hidden bg-sage-tint mb-2">
                                        <img src="{{ $menu->photo_url ?? $menu->photo }}" alt="{{ $menu->name }}" class="w-full h-full object-cover transition duration-200 group-hover:scale-105" loading="lazy" />
                                    </div>
                                    <h3 class="text-[13px] font-semibold text-text-main line-clamp-1 leading-tight group-hover:text-accent-sage transition-colors">{{ $menu->name }}</h3>
                                    <p class="text-[11px] text-muted line-clamp-1 mt-0.5">{{ $menu->canteen_name }}</p>
                                </div>
                                <div class="mt-2 pt-1 border-t border-line">
                                    <span class="text-[13px] font-bold text-brand">
                                        <x-rupiah :amount="$menu->price" />
                                    </span>
                                </div>
                            </a>
                        @endforeach
                        <div class="w-1 flex-shrink-0"></div>
                    </div>
                </div>
            @endif

            <!-- 5. Full List: Semua Kantin -->
            @if ($canteens->isNotEmpty())
                <div class="px-5 pt-6 pb-6 space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-[17px] font-bold font-heading text-text-main">Semua kantin</h2>
                        <span class="text-[13px] text-muted">{{ $canteens->count() }} kantin</span>
                    </div>

                    @foreach ($canteens as $canteen)
                        <a href="{{ url('/kantin/' . $canteen->id) }}" class="block group">
                            <div class="bg-surface border border-line rounded-[16px] p-3 flex items-center gap-3.5 transition-colors hover:border-accent-sage {{ !$canteen->is_open ? 'opacity-70' : '' }}">
                                <div class="w-20 h-20 rounded-[14px] overflow-hidden bg-sage-tint flex-shrink-0">
                                    <img src="{{ $canteen->photo_url }}" alt="{{ $canteen->name }}" class="w-full h-full object-cover {{ !$canteen->is_open ? 'grayscale' : '' }}" loading="lazy" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <h3 class="text-[15px] font-bold font-heading text-text-main truncate">{{ $canteen->name }}</h3>
                                        @if ($canteen->is_open)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-sage-tint text-brand flex-shrink-0">
                                                <span class="w-1.5 h-1.5 rounded-full bg-accent-sage"></span>
                                                Buka
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-danger text-white flex-shrink-0">Tutup</span>
                                        @endif
                                    </div>
                                    <p class="text-[13px] text-muted line-clamp-1 mt-0.5">{{ $canteen->description ?? 'Menyediakan hidangan pilihan.' }}</p>
                                    @if ($canteen->popular_menu_names)
                                        <p class="text-[11px] text-accent-sage font-medium truncate mt-1">{{ $canteen->popular_menu_names }}</p>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- ========================================== -->
        <!-- FLOATING CART BAR                          -->
        <!-- ========================================== -->
        <div
            x-show="cartCount > 0"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="fixed bottom-20 left-4 right-4 z-40 max-w-md mx-auto"
            style="display: none;"
        >
            <a
                href="{{ route('cart.index') }}"
                class="flex items-center justify-between px-4 py-3 bg-brand text-on-brand rounded-2xl shadow-xl hover:opacity-95 active:scale-98 transition"
            >
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-accent-sage/30 flex items-center justify-center text-xs font-bold" x-text="cartCount"></span>
                    <span class="text-xs font-medium">Item di keranjang</span>
                </div>
                <div class="flex items-center gap-1.5 font-bold text-sm">
                    <span x-text="'Rp ' + Number(cartTotal).toLocaleString('id-ID')"></span>
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </a>
        </div>

        <!-- ========================================== -->
        <!-- CANTEEN CONFLICT MODAL                     -->
        <!-- ========================================== -->
        <div
            x-show="conflictModalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4"
            style="display: none;"
        >
            <div
                x-show="conflictModalOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="bg-surface rounded-3xl p-6 max-w-sm w-full space-y-4 shadow-2xl border border-line"
                @click.away="conflictModalOpen = false"
            >
                <div class="w-12 h-12 rounded-full bg-butter/20 border border-butter/40 flex items-center justify-center text-2xl mx-auto">
                    ⚠️
                </div>
                <div class="text-center space-y-1">
                    <h3 class="text-base font-bold text-text-main">Ganti Kantin?</h3>
                    <p class="text-xs text-muted leading-relaxed">
                        Keranjangmu saat ini berisi menu dari <span class="font-bold text-text-main" x-text="pendingMenu ? pendingMenu.currentCanteen : ''"></span>.
                        Menambahkan menu dari <span class="font-bold text-text-main" x-text="pendingMenu ? pendingMenu.newCanteen : ''"></span> akan mengosongkan keranjang sebelumnya.
                    </p>
                </div>
                <div class="flex items-center gap-2 pt-2">
                    <button
                        type="button"
                        @click="conflictModalOpen = false; pendingMenu = null"
                        class="flex-1 py-2.5 text-xs font-bold text-text-main bg-page hover:bg-sage-tint rounded-xl border border-line transition"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="confirmReplaceCart()"
                        :disabled="isAdding"
                        class="flex-1 py-2.5 text-xs font-bold text-on-brand bg-brand hover:opacity-90 active:scale-95 rounded-xl shadow-md transition disabled:opacity-50"
                    >
                        Ya, Ganti Kantin
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-layouts.buyer>
