<x-layouts.buyer title="Pesananmu" :hideNav="false">
    <x-page-header title="Pesananmu" :backUrl="url('/')" />

    <div
        x-data="{
            currentStep: {{ $stepData['step'] }},
            currentStatus: '{{ $order->status }}',
            currentPaymentStatus: '{{ $order->payment_status }}',
            friendlyLine: '{{ addslashes($stepData['friendly_line']) }}',
            statusUrl: '{{ route('order.status', $order->order_code) }}',
            isRefreshing: false,

            async pollStatus() {
                try {
                    const res = await fetch(this.statusUrl, {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.currentStep = data.step;
                        this.currentStatus = data.status;
                        this.currentPaymentStatus = data.payment_status;
                        this.friendlyLine = data.friendly_line;
                    }
                } catch (e) {
                    console.error('Polling error:', e);
                }
            }
        }"
        x-init="setInterval(() => pollStatus(), 5000)"
        class="p-4 space-y-4 pb-28"
    >
        <!-- Friendly Status Card & Order Code -->
        <div class="bg-surface rounded-3xl border border-line shadow-sm p-5 text-center space-y-2">
            <span class="text-[11px] font-mono font-bold text-muted uppercase tracking-widest">
                KODE PESANAN: {{ $order->order_code }}
            </span>
            <h2 class="text-base font-extrabold text-text-main leading-snug" x-text="friendlyLine">
                {{ $stepData['friendly_line'] }}
            </h2>
            <div class="flex items-center justify-center gap-2 pt-1">
                <template x-if="currentPaymentStatus === 'dibayar'">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sage-tint text-brand border border-line">
                        Sudah Dibayar
                    </span>
                </template>
                <template x-if="currentPaymentStatus !== 'dibayar'">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-butter/20 text-text-main border border-butter/30">
                        Menunggu Pembayaran
                    </span>
                </template>
            </div>
        </div>

        <!-- Vertical Progress Tracker (4 Steps) -->
        <div class="bg-surface rounded-3xl border border-line shadow-sm p-5">
            <h3 class="text-xs font-bold text-muted uppercase tracking-wider mb-4">Status Pengantaran</h3>

            @php
                $steps = [
                    1 => ['title' => 'Menunggu pembayaran', 'desc' => 'Pembayaran sedang diproses'],
                    2 => ['title' => 'Diproses', 'desc' => 'Pesanan sedang disiapkan di dapur'],
                    3 => ['title' => 'Diantar', 'desc' => 'Kurir mengantar makanan ke lokasi duduk'],
                    4 => ['title' => 'Selesai', 'desc' => 'Pesanan berhasil diterima'],
                ];
            @endphp

            <div class="relative space-y-6 pl-2">
                <!-- Vertical connecting line -->
                <div class="absolute top-3 left-6 -ml-px w-0.5 h-[80%] bg-line -z-0"></div>

                @foreach ($steps as $stepNum => $stepInfo)
                    <div class="relative flex items-start gap-3.5 z-10">
                        <!-- Step Icon -->
                        <div
                            class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300 ring-4 ring-surface flex-shrink-0"
                            :class="{
                                'bg-brand text-on-brand shadow-md': currentStep === {{ $stepNum }},
                                'bg-accent-sage text-white': currentStep > {{ $stepNum }},
                                'bg-page text-muted border border-line': currentStep < {{ $stepNum }}
                            }"
                        >
                            <template x-if="currentStep > {{ $stepNum }}">
                                <svg class="w-4 h-4 stroke-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </template>

                            <template x-if="currentStep === {{ $stepNum }}">
                                <span class="w-2.5 h-2.5 rounded-full bg-on-brand animate-pulse"></span>
                            </template>

                            <template x-if="currentStep < {{ $stepNum }}">
                                <span>{{ $stepNum }}</span>
                            </template>
                        </div>

                        <!-- Step Labels -->
                        <div class="pt-0.5">
                            <h4
                                class="text-xs font-bold transition-colors"
                                :class="{
                                    'text-brand font-extrabold': currentStep === {{ $stepNum }},
                                    'text-text-main': currentStep > {{ $stepNum }},
                                    'text-muted font-medium': currentStep < {{ $stepNum }}
                                }"
                            >
                                {{ $stepInfo['title'] }}
                            </h4>
                            <p class="text-[11px] text-muted mt-0.5">{{ $stepInfo['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Payment Action -->
        <div x-show="currentPaymentStatus !== 'dibayar'" class="space-y-2">
            <a
                href="{{ route('payment.show', $order->order_code) }}"
                class="w-full py-3 px-4 bg-brand hover:opacity-90 active:scale-98 text-on-brand rounded-2xl text-xs font-bold transition shadow-md flex items-center justify-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                </svg>
                <span>Lanjut ke Pembayaran QRIS</span>
            </a>

            @if (config('midtrans.payment_simulation', true))
                <form action="{{ route('order.simulate-payment', $order->order_code) }}" method="POST">
                    @csrf
                    <button
                        type="submit"
                        class="w-full py-2 px-3 text-[11px] font-semibold text-text-main bg-butter/20 hover:bg-butter/30 border border-butter/40 rounded-xl transition"
                    >
                        ⚡ Simulasi Bayar (Dev Mode)
                    </button>
                </form>
            @endif
        </div>

        <!-- Order Summary Card -->
        <div class="bg-surface rounded-3xl border border-line shadow-sm p-4 space-y-3">
            <h3 class="text-xs font-bold text-text-main uppercase tracking-wider flex items-center justify-between">
                <span>Rincian Pesanan</span>
                <span class="text-muted font-normal">{{ $order->created_at->translatedFormat('d M Y, H:i') }}</span>
            </h3>

            <div class="bg-page p-3 rounded-2xl space-y-1.5 text-xs">
                <div class="flex justify-between">
                    <span class="text-muted">Kantin:</span>
                    <span class="font-bold text-text-main">{{ $order->canteen->name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted">Nama Pemesan:</span>
                    <span class="font-bold text-text-main">{{ $order->customer_name }}</span>
                </div>
                @if ($order->customer_phone)
                    <div class="flex justify-between">
                        <span class="text-muted">WhatsApp:</span>
                        <span class="font-medium text-text-main">{{ $order->customer_phone }}</span>
                    </div>
                @endif
                <div class="flex justify-between items-start">
                    <span class="text-muted">Lokasi Duduk:</span>
                    <span class="font-bold text-text-main text-right">{{ $order->zone->name ?? '-' }}</span>
                </div>
                @if ($order->location_notes)
                    <div class="flex justify-between items-start pt-0.5">
                        <span class="text-muted">Keterangan:</span>
                        <span class="font-medium text-text-main italic text-right max-w-[200px]">"{{ $order->location_notes }}"</span>
                    </div>
                @endif
            </div>

            <div class="divide-y divide-line">
                @foreach ($order->items as $item)
                    <div class="py-2.5 flex items-start justify-between text-xs">
                        <div class="pr-2">
                            <h5 class="font-bold text-text-main leading-snug">
                                {{ $item->quantity }}x {{ $item->item_name }}
                            </h5>
                            @if ($item->note)
                                <p class="text-[11px] text-accent-sage bg-sage-tint px-2 py-0.5 rounded-md inline-block mt-1 font-medium">
                                    Catatan: {{ $item->note }}
                                </p>
                            @endif
                            <div class="text-[10px] text-muted mt-0.5">
                                @rupiah($item->unit_price) per porsi
                            </div>
                        </div>
                        <span class="font-bold text-text-main flex-shrink-0">
                            @rupiah($item->subtotal)
                        </span>
                    </div>
                @endforeach
            </div>

            <div class="border-t border-line pt-2.5 space-y-1 text-xs">
                <div class="flex justify-between text-muted">
                    <span>Subtotal</span>
                    <span class="font-medium text-text-main">@rupiah($order->subtotal)</span>
                </div>
                <div class="flex justify-between text-muted">
                    <span>Ongkos Antar</span>
                    <span class="font-medium text-accent-sage font-bold">
                        {{ $order->delivery_fee > 0 ? rupiah($order->delivery_fee) : 'GRATIS' }}
                    </span>
                </div>
                <div class="flex justify-between text-sm font-extrabold text-text-main pt-1.5 border-t border-line">
                    <span>Total</span>
                    <span class="text-brand">@rupiah($order->total_amount)</span>
                </div>
            </div>
        </div>

        <!-- WhatsApp Contact Button -->
        @php
            $canteenWa = $order->canteen->whatsapp ?: $order->canteen->phone;
        @endphp
        @if (!empty($canteenWa))
            @php
                $cleanPhone = preg_replace('/[^0-9]/', '', $canteenWa);
                if (str_starts_with($cleanPhone, '0')) {
                    $cleanPhone = '62' . substr($cleanPhone, 1);
                }
                $waText = urlencode("Halo {$order->canteen->name}, saya mau menanyakan pesanan {$order->order_code} atas nama {$order->customer_name}.");
                $waUrl = "https://wa.me/{$cleanPhone}?text={$waText}";
            @endphp
            <div>
                <a
                    href="{{ $waUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="w-full inline-flex items-center justify-center gap-2 py-3 px-4 rounded-xl border border-accent-sage/40 bg-sage-tint/50 hover:bg-sage-tint text-brand text-xs font-bold transition shadow-xs"
                >
                    <svg class="w-4 h-4 fill-accent-sage" viewBox="0 0 24 24">
                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                    </svg>
                    <span>Hubungi Kantin via WhatsApp</span>
                </a>
            </div>
        @endif
    </div>
</x-layouts.buyer>
