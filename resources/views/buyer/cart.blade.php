<x-layouts.buyer title="Keranjang Belanja">
    <x-page-header title="Keranjang Belanja" :backUrl="url('/')">
        @if (!empty($cart['items']))
            <x-slot name="actions">
                <form action="{{ route('cart.clear') }}" method="POST" onsubmit="return confirm('Kosongkan semua isi keranjang?');">
                    @csrf
                    <button type="submit" class="text-xs font-semibold text-danger hover:opacity-80 transition">
                        Kosongkan
                    </button>
                </form>
            </x-slot>
        @endif
    </x-page-header>

    <div class="p-4 space-y-4">
        @if (!empty($syncMessages))
            <div class="space-y-2">
                @foreach ($syncMessages as $msg)
                    <div class="p-3 bg-butter/20 border border-butter/40 rounded-2xl flex items-start gap-2.5 text-xs text-text-main font-medium">
                        <span class="text-sm">⚠️</span>
                        <span>{{ $msg }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        @if (empty($cart['items']))
            <!-- Empty State -->
            <div class="py-16 text-center bg-surface rounded-3xl border border-line shadow-sm p-6 space-y-4 mt-6">
                <div class="w-20 h-20 mx-auto rounded-full bg-sage-tint flex items-center justify-center text-4xl">
                    🛒
                </div>
                <div>
                    <h2 class="text-base font-bold text-text-main">Keranjangmu masih kosong</h2>
                    <p class="text-xs text-muted mt-1 max-w-xs mx-auto">
                        Yuk, pilih menu lezat dari kantin kampus favoritmu sekarang!
                    </p>
                </div>
                <div class="pt-2">
                    <x-button :href="url('/')" variant="primary" size="md" class="w-full">
                        Mulai Belanja Makanan
                    </x-button>
                </div>
            </div>
        @else
            <!-- Canteen Info Banner -->
            <div class="flex items-center justify-between p-3 bg-surface rounded-2xl border border-line shadow-sm">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-sage-tint flex items-center justify-center text-brand font-bold text-xs">
                        🏪
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-muted">Pesanan dari:</span>
                        <h3 class="text-xs font-bold text-text-main">{{ $cart['canteen_name'] }}</h3>
                    </div>
                </div>
                <a href="{{ url('/kantin/' . $cart['canteen_id']) }}" class="text-xs font-semibold text-accent-sage hover:underline">
                    + Tambah Menu
                </a>
            </div>

            <!-- Item Rows -->
            <div class="bg-surface rounded-3xl border border-line shadow-sm p-4 divide-y divide-line">
                @foreach ($cart['items'] as $item)
                    <div class="py-4 first:pt-1 last:pb-1 space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-bold text-text-main leading-snug truncate">{{ $item['name'] }}</h4>
                                <p class="text-xs font-semibold text-brand mt-0.5">
                                    @rupiah($item['price'])
                                </p>
                            </div>

                            <!-- Quantity Stepper -->
                            <div class="flex items-center border border-line rounded-xl bg-page p-0.5 shadow-sm">
                                <form action="{{ route('cart.decrease', $item['id']) }}" method="POST">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="w-7 h-7 flex items-center justify-center rounded-lg text-text-main hover:bg-surface hover:text-brand transition active:scale-90 font-bold"
                                        aria-label="Kurangi kuantitas"
                                    >-</button>
                                </form>

                                <span class="w-8 text-center text-xs font-bold text-text-main">
                                    {{ $item['quantity'] }}
                                </span>

                                <form action="{{ route('cart.increase', $item['id']) }}" method="POST">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="w-7 h-7 flex items-center justify-center rounded-lg text-text-main hover:bg-surface hover:text-brand transition active:scale-90 font-bold"
                                        aria-label="Tambah kuantitas"
                                    >+</button>
                                </form>
                            </div>
                        </div>

                        <!-- Note Field -->
                        <form action="{{ route('cart.setNote', $item['id']) }}" method="POST" class="flex gap-2">
                            @csrf
                            <input
                                type="text"
                                name="note"
                                value="{{ $item['note'] ?? '' }}"
                                placeholder="Catatan, mis. tanpa es"
                                class="flex-1 text-xs px-3 py-2 bg-page rounded-xl border border-line focus:bg-surface focus:outline-none focus:ring-1 focus:ring-accent-sage placeholder-muted text-text-main"
                            />
                            <button
                                type="submit"
                                class="px-3 py-2 bg-page hover:bg-sage-tint text-text-main text-xs font-semibold rounded-xl border border-line transition"
                            >
                                Simpan
                            </button>
                        </form>

                        <!-- Item Subtotal -->
                        <div class="flex items-center justify-between text-xs pt-1">
                            <span class="text-muted">Subtotal:</span>
                            <span class="font-bold text-text-main">@rupiah($item['subtotal'])</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Price Breakdown Summary -->
            <div class="bg-surface rounded-3xl border border-line shadow-sm p-4 space-y-2.5">
                <div class="flex justify-between text-xs text-muted">
                    <span>Subtotal</span>
                    <span class="font-medium text-text-main">@rupiah($cart['total'])</span>
                </div>
                <div class="flex justify-between text-xs text-muted">
                    <span>Biaya Layanan Kampus</span>
                    <span class="font-medium text-accent-sage font-bold">GRATIS</span>
                </div>
                <div class="border-t border-line pt-2.5 flex justify-between items-center">
                    <span class="text-sm font-bold text-text-main">Total Pembayaran</span>
                    <span class="text-base font-extrabold text-brand">@rupiah($cart['total'])</span>
                </div>
            </div>

            <!-- Primary button "Lanjut" -->
            <div class="pt-2">
                <x-button :href="route('order.delivery')" variant="primary" size="lg" class="w-full">
                    <span>Lanjut</span>
                    <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </x-button>
            </div>
        @endif
    </div>
</x-layouts.buyer>
