@props([
    'active' => 'beranda', // 'beranda', 'pesanan', 'riwayat'
])

@php
    $currentRoute = request()->path();
    if ($currentRoute === '/' || $currentRoute === '') {
        $activeTab = 'beranda';
    } elseif (str_contains($currentRoute, 'pesanan') || str_contains($currentRoute, 'keranjang')) {
        $activeTab = 'pesanan';
    } elseif (str_contains($currentRoute, 'riwayat')) {
        $activeTab = 'riwayat';
    } else {
        $activeTab = $active;
    }

    $latestOrderCode = session('latest_order_code');
    $pesananUrl = $latestOrderCode ? url('/pesanan/' . $latestOrderCode) : url('/keranjang');
    $cartCount = count(session('cart.items', []));
@endphp

<nav class="fixed bottom-0 left-0 right-0 z-40 bg-surface border-t border-line shadow-[0_-2px_12px_rgba(30,42,36,0.04)] safe-area-bottom">
    <div class="max-w-[480px] mx-auto px-6 h-16 flex items-center justify-around">
        <!-- Beranda -->
        <a href="{{ url('/') }}" class="flex flex-col items-center justify-center min-w-[56px] min-h-[44px] py-1 transition group {{ $activeTab === 'beranda' ? 'text-brand font-semibold' : 'text-muted hover:text-text-main' }}">
            <svg class="w-5 h-5 transition-transform group-active:scale-95 {{ $activeTab === 'beranda' ? 'stroke-brand' : 'stroke-current' }}" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <span class="text-[13px] mt-1">Beranda</span>
        </a>

        <!-- Pesanan / Status / Keranjang -->
        <a href="{{ $pesananUrl }}" class="relative flex flex-col items-center justify-center min-w-[56px] min-h-[44px] py-1 transition group {{ $activeTab === 'pesanan' ? 'text-brand font-semibold' : 'text-muted hover:text-text-main' }}">
            <div class="relative">
                <svg class="w-5 h-5 transition-transform group-active:scale-95 {{ $activeTab === 'pesanan' ? 'stroke-brand' : 'stroke-current' }}" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="8" cy="21" r="1"/>
                    <circle cx="19" cy="21" r="1"/>
                    <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                </svg>
                @if ($cartCount > 0)
                    <span class="absolute -top-1.5 -right-2.5 bg-accent-sage text-on-brand text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">
                        {{ $cartCount }}
                    </span>
                @endif
            </div>
            <span class="text-[13px] mt-1">Pesanan</span>
        </a>

        <!-- Riwayat -->
        <a href="{{ url('/riwayat') }}" class="flex flex-col items-center justify-center min-w-[56px] min-h-[44px] py-1 transition group {{ $activeTab === 'riwayat' ? 'text-brand font-semibold' : 'text-muted hover:text-text-main' }}">
            <svg class="w-5 h-5 transition-transform group-active:scale-95 {{ $activeTab === 'riwayat' ? 'stroke-brand' : 'stroke-current' }}" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 8v4l3 3"/>
                <circle cx="12" cy="12" r="9"/>
            </svg>
            <span class="text-[13px] mt-1">Riwayat</span>
        </a>
    </div>
</nav>
