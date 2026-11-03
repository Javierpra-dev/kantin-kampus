<x-layouts.buyer :title="$canteen->name" :hideNav="false">
    <div
        x-data="{
            cartTotal: {{ $isCartFromThisCanteen ? $cart['total'] : 0 }},
            cartCount: {{ $isCartFromThisCanteen ? $cart['count'] : 0 }},
            conflictModalOpen: false,
            pendingMenu: null,
            conflictCurrentCanteen: '',
            conflictNewCanteen: '',
            isAdding: false,

            formatRupiah(num) {
                return 'Rp' + new Intl.NumberFormat('id-ID').format(num);
            },

            async addToCart(menuId, menuName, isAvailable, canteenIsOpen) {
                if (!isAvailable || !canteenIsOpen) return;
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
                        this.pendingMenu = { id: menuId, name: menuName };
                        this.conflictCurrentCanteen = data.current_canteen;
                        this.conflictNewCanteen = data.new_canteen;
                        this.conflictModalOpen = true;
                    } else if (data.success) {
                        this.cartTotal = data.cart.total;
                        this.cartCount = data.cart.count;
                    }
                } catch (e) {
                    console.error('Error adding to cart:', e);
                } finally {
                    this.isAdding = false;
                }
            },

            init() {
                const urlParams = new URLSearchParams(window.location.search);
                const targetMenuId = urlParams.get('menu');
                if (targetMenuId) {
                    this.$nextTick(() => {
                        const el = document.getElementById('menu-' + targetMenuId);
                        if (el) {
                            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            el.classList.add('ring-2', 'ring-accent-sage', 'border-accent-sage');
                            setTimeout(() => {
                                el.classList.remove('ring-2', 'ring-accent-sage', 'border-accent-sage');
                            }, 2000);
                        }
                    });
                }
            },
            async confirmReplaceCart() {
                if (!this.pendingMenu) return;
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
        class="relative min-h-screen pb-28"
    >
        <!-- Page Header -->
        <header class="sticky top-0 z-30 bg-surface/95 backdrop-blur-md border-b border-line px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ url('/') }}" class="flex items-center justify-center w-9 h-9 rounded-full bg-page hover:bg-sage-tint text-text-main transition active:scale-95" aria-label="Kembali ke Beranda">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-base font-bold text-text-main leading-tight">{{ $canteen->name }}</h1>
                    <p class="text-[11px] text-muted">{{ $canteen->opening_hours }}</p>
                </div>
            </div>

            <!-- Open / Closed Badge -->
            <div>
                @if ($canteen->is_open)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-sage-tint text-brand">
                        <span class="w-1.5 h-1.5 rounded-full bg-accent-sage animate-pulse"></span>
                        Buka
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-danger text-white">
                        Tutup
                    </span>
                @endif
            </div>
        </header>

        <!-- Closed Canteen Notice -->
        @if (!$canteen->is_open)
            <div class="m-4 p-3.5 bg-butter/20 border border-butter/40 rounded-2xl flex items-start gap-3 text-text-main shadow-sm">
                <span class="text-lg flex-shrink-0">⚠️</span>
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider">Kantin Sedang Tutup</h4>
                    <p class="text-xs text-muted mt-0.5 leading-relaxed">
                        Saat ini kantin belum menerima pesanan baru. Anda tetap dapat melihat menu yang tersedia di bawah.
                    </p>
                </div>
            </div>
        @endif

        <!-- Canteen Cover Photo Banner -->
        <div class="px-4 pt-3">
            <div class="relative w-full h-44 rounded-2xl overflow-hidden bg-sage-tint border border-line">
                <img
                    src="{{ $canteen->photo_url }}"
                    alt="{{ $canteen->name }}"
                    class="w-full h-full object-cover {{ !$canteen->is_open ? 'grayscale' : '' }}"
                    loading="lazy"
                />
            </div>
        </div>

        <!-- Canteen Description -->
        <div class="px-5 pt-3 pb-2">
            <p class="text-xs text-muted leading-relaxed">{{ $canteen->description }}</p>
        </div>

        <!-- Menu List -->
        <div class="px-4 py-2 space-y-3">
            <h2 class="text-xs font-bold text-muted uppercase tracking-wider px-1">Daftar Menu Makanan & Minuman</h2>

            @forelse ($canteen->menus as $item)
                @php
                    $isAvailable = $item->is_available && !$item->isOutOfStock() && $canteen->is_open;
                @endphp
                <div id="menu-{{ $item->id }}" class="bg-surface rounded-2xl border border-line p-3 shadow-sm flex items-center justify-between gap-3 transition {{ !$isAvailable ? 'opacity-60' : 'hover:border-accent-sage/50' }}">
                    <!-- Square photo -->
                    <div class="relative w-20 h-20 flex-shrink-0 rounded-xl overflow-hidden bg-sage-tint">
                        <img
                            src="{{ $item->photo_url }}"
                            alt="{{ $item->name }}"
                            class="w-full h-full object-cover {{ !$isAvailable ? 'grayscale' : '' }}"
                            loading="lazy"
                        />
                        @if (!$item->is_available || $item->isOutOfStock())
                            <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                                <span class="bg-danger text-white font-bold text-[10px] px-1.5 py-0.5 rounded shadow">Habis</span>
                            </div>
                        @endif
                    </div>

                    <!-- Details -->
                    <div class="flex-1 min-w-0 pr-1">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <h3 class="text-sm font-bold text-text-main truncate leading-snug {{ !$isAvailable ? 'text-muted' : '' }}">
                                {{ $item->name }}
                            </h3>
                            @if (!$item->is_available || $item->isOutOfStock())
                                <span class="text-[10px] font-bold text-danger bg-danger/10 border border-danger/20 px-1 rounded flex-shrink-0">Habis</span>
                            @endif
                            @if ($item->daily_stock !== null && $item->daily_stock > 0 && $item->daily_stock <= 5)
                                <span class="text-[10px] font-bold bg-butter/20 text-text-main border border-butter/40 px-1.5 py-0.5 rounded flex-shrink-0">
                                    Sisa {{ $item->daily_stock }}
                                </span>
                            @endif
                            @if ($item->isPromoActive())
                                <span class="text-[10px] font-bold bg-butter/20 text-text-main border border-butter/40 px-1.5 py-0.5 rounded flex-shrink-0">
                                    Promo
                                </span>
                            @endif
                        </div>

                        <p class="text-xs text-muted truncate mt-0.5">
                            {{ $item->description ?? 'Hidangan istimewa' }}
                        </p>

                        <div class="mt-2 flex items-center gap-2">
                            @if ($item->isPromoActive())
                                <span class="text-sm font-extrabold text-brand">
                                    @rupiah($item->promo_price)
                                </span>
                                <span class="text-xs text-muted line-through">
                                    @rupiah($item->price)
                                </span>
                            @else
                                <span class="text-sm font-extrabold text-brand">
                                    @rupiah($item->price)
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Round "+" Button -->
                    <div class="flex-shrink-0">
                        @if ($isAvailable)
                            <button
                                type="button"
                                @click="addToCart({{ $item->id }}, '{{ addslashes($item->name) }}', true, {{ $canteen->is_open ? 'true' : 'false' }})"
                                class="w-9 h-9 rounded-full bg-brand hover:opacity-90 active:scale-90 text-on-brand flex items-center justify-center shadow-md transition-all focus:outline-none"
                                aria-label="Tambah {{ $item->name }} ke keranjang"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                </svg>
                            </button>
                        @else
                            <button
                                type="button"
                                disabled
                                class="w-9 h-9 rounded-full bg-line text-muted flex items-center justify-center cursor-not-allowed"
                                title="Menu tidak tersedia"
                                aria-label="Menu habis"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-12 text-center bg-surface rounded-2xl border border-line p-6 space-y-2">
                    <div class="text-3xl">🍲</div>
                    <h3 class="text-sm font-bold text-text-main">Belum ada menu yang ditampilkan</h3>
                    <p class="text-xs text-muted">Kantin ini sedang memperbarui daftar menunya.</p>
                </div>
            @endforelse
        </div>

        <!-- Sticky Bottom Cart Bar -->
        <div
            x-show="cartCount > 0"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-6"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-6"
            class="fixed bottom-16 left-0 right-0 z-30 px-4 py-2 pointer-events-none"
        >
            <div class="max-w-md mx-auto pointer-events-auto">
                <div class="bg-text-main text-page px-4 py-3 rounded-2xl shadow-xl flex items-center justify-between border border-line/20">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-brand flex items-center justify-center font-bold text-xs text-on-brand">
                            <span x-text="cartCount"></span>
                        </div>
                        <div>
                            <span class="text-sm font-bold">
                                <span x-text="cartCount"></span> item • <span x-text="formatRupiah(cartTotal)"></span>
                            </span>
                        </div>
                    </div>

                    <a
                        href="{{ route('cart.index') }}"
                        class="px-4 py-2 bg-brand hover:opacity-90 active:scale-95 text-on-brand text-xs font-bold rounded-xl transition shadow-md flex items-center gap-1.5"
                    >
                        <span>Lihat keranjang</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Conflict Modal -->
        <div
            x-show="conflictModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        >
            <div
                @click.away="conflictModalOpen = false"
                class="bg-surface rounded-3xl max-w-sm w-full p-5 shadow-2xl text-center space-y-4"
            >
                <div class="w-14 h-14 mx-auto rounded-full bg-butter/30 flex items-center justify-center text-2xl">
                    ⚠️
                </div>
                <div>
                    <h3 class="text-base font-bold text-text-main">Ganti Kantin?</h3>
                    <p class="text-xs text-muted mt-1 leading-relaxed">
                        Keranjangmu saat ini berisi menu dari <span class="font-bold text-text-main" x-text="conflictCurrentCanteen"></span>. Satu keranjang hanya boleh berisi menu dari satu kantin.
                    </p>
                    <p class="text-xs text-brand font-semibold mt-2">
                        Kosongkan keranjang dan mulai memesan dari <span x-text="conflictNewCanteen"></span>?
                    </p>
                </div>
                <div class="flex gap-2 pt-2">
                    <button
                        type="button"
                        @click="conflictModalOpen = false"
                        class="flex-1 py-2.5 px-3 border border-line rounded-xl text-xs font-semibold text-text-main hover:bg-page transition"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="confirmReplaceCart()"
                        class="flex-1 py-2.5 px-3 bg-brand hover:opacity-90 text-on-brand rounded-xl text-xs font-bold transition shadow-sm"
                    >
                        Ya, Ganti Kantin
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-layouts.buyer>
