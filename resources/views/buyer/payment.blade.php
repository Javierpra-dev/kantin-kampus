
<x-layouts.buyer title="Pembayaran" :hideNav="true">
    <x-page-header title="Pembayaran" :backUrl="route('order.show', $order->order_code)" />

    <div
        x-data="{
            remainingSeconds: {{ $remainingSeconds }},
            countdownText: '15:00',
            isExpired: {{ $isExpired ? 'true' : 'false' }},
            isPaid: false,
            pollUrl: '{{ route('payment.status', $order->order_code) }}',
            redirectUrl: '{{ route('order.show', $order->order_code) }}',
            summaryOpen: false,

            init() {
                this.updateCountdown();
                if (!this.isExpired) {
                    const timer = setInterval(() => {
                        if (this.remainingSeconds > 0) {
                            this.remainingSeconds--;
                            this.updateCountdown();
                        } else {
                            this.isExpired = true;
                            clearInterval(timer);
                        }
                    }, 1000);

                    const poller = setInterval(async () => {
                        if (this.isPaid || this.isExpired) {
                            clearInterval(poller);
                            return;
                        }
                        try {
                            const res = await fetch(this.pollUrl, {
                                headers: { 'Accept': 'application/json' }
                            });
                            if (res.ok) {
                                const data = await res.json();
                                if (data.is_paid) {
                                    this.isPaid = true;
                                    clearInterval(poller);
                                    window.location.href = data.redirect_url;
                                } else if (data.is_expired) {
                                    this.isExpired = true;
                                    clearInterval(poller);
                                }
                            }
                        } catch (e) {
                            console.error('Payment polling error:', e);
                        }
                    }, 3000);
                }
            },

            updateCountdown() {
                const mins = Math.floor(this.remainingSeconds / 60);
                const secs = this.remainingSeconds % 60;
                this.countdownText = String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
            },

            downloadQr() {
                const qrImg = document.getElementById('qris-image');
                if (qrImg && qrImg.src) {
                    const a = document.createElement('a');
                    a.href = qrImg.src;
                    a.download = 'QRIS-{{ $order->order_code }}.png';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                }
            }
        }"
        class="p-4 space-y-4 pb-12"
    >
        <!-- Total Amount Card -->
        <div class="bg-surface rounded-3xl border border-line shadow-sm p-5 text-center space-y-1">
            <span class="text-xs font-semibold text-muted uppercase tracking-wider">Total Pembayaran</span>
            <div class="text-3xl font-extrabold text-text-main tracking-tight">
                @rupiah($order->total_amount)
            </div>
            <div class="text-xs text-muted font-mono mt-1">
                Kode Pesanan: <span class="font-bold text-text-main">{{ $order->order_code }}</span>
            </div>
        </div>

        <!-- EXPIRED STATE -->
        <template x-if="isExpired">
            <div class="bg-surface rounded-3xl border border-danger/30 shadow-sm p-6 text-center space-y-4">
                <div class="w-16 h-16 mx-auto rounded-full bg-danger/10 flex items-center justify-center text-3xl">
                    ⏳
                </div>
                <div>
                    <h3 class="text-base font-bold text-danger">Pembayaran kedaluwarsa</h3>
                    <p class="text-xs text-muted mt-1 max-w-xs mx-auto">
                        Batas waktu pembayaran QRIS telah habis. Silakan buat kode QRIS baru untuk melanjutkan pesanan Anda.
                    </p>
                </div>
                <form action="{{ route('payment.retry', $order->order_code) }}" method="POST">
                    @csrf
                    <button
                        type="submit"
                        class="w-full py-3 bg-brand hover:opacity-90 active:scale-98 text-on-brand rounded-xl text-xs font-bold transition shadow-md"
                    >
                        Buat QRIS Baru dan Coba Lagi
                    </button>
                </form>
            </div>
        </template>

        <!-- ACTIVE QRIS CARD -->
        <template x-if="!isExpired">
            <div class="space-y-4">
                <!-- QR Code Container — always on white background (dark-mode safe) -->
                <div class="bg-surface rounded-3xl border border-line shadow-sm p-6 text-center space-y-4">
                    <!-- Countdown -->
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-butter/20 border border-butter/40 rounded-full text-xs font-bold text-text-main">
                        <svg class="w-4 h-4 animate-spin text-butter" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Bayar dalam <span x-text="countdownText">15:00</span></span>
                    </div>

                    <!-- QR Code Image — guaranteed white card -->
                    <div class="mx-auto w-64 h-64 qr-white-card rounded-2xl border-2 border-gray-200 shadow-inner flex items-center justify-center">
                        <img
                            id="qris-image"
                            src="{{ $order->qr_url }}"
                            alt="QRIS {{ $order->order_code }}"
                            class="w-full h-full object-contain"
                        />
                    </div>

                    <!-- Instruction -->
                    <div class="space-y-1">
                        <h4 class="text-xs font-bold text-text-main uppercase tracking-wider">Metode QRIS Nasional</h4>
                        <p class="text-xs text-muted">
                            Scan QR dengan e-wallet atau m-banking kamu
                        </p>
                        <div class="flex items-center justify-center gap-2 pt-1 text-[11px] text-muted font-semibold">
                            <span>GoPay</span> • <span>OVO</span> • <span>DANA</span> • <span>ShopeePay</span> • <span>BCA / Mandiri / BRI</span>
                        </div>
                    </div>

                    <!-- Secondary Button "Simpan QR" -->
                    <div class="pt-2">
                        <button
                            type="button"
                            @click="downloadQr()"
                            class="w-full py-2.5 px-4 rounded-xl border border-line bg-surface hover:bg-page active:scale-98 text-text-main text-xs font-bold transition flex items-center justify-center gap-2 shadow-xs"
                        >
                            <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <span>Simpan QR</span>
                        </button>
                    </div>
                </div>

                <!-- Simulation Demo helper -->
                @if ($simulationEnabled)
                    <div class="bg-butter/10 border border-dashed border-butter/50 rounded-2xl p-3.5 space-y-2">
                        <div class="flex items-center justify-between text-xs font-bold text-text-main">
                            <span class="flex items-center gap-1.5">
                                <span>🧪</span>
                                <span>Demo Sandbox / Simulasi Pembayaran</span>
                            </span>
                            <span class="text-[10px] bg-butter/30 text-text-main px-2 py-0.5 rounded-full">Dev Mode</span>
                        </div>
                        <p class="text-[11px] text-muted leading-relaxed">
                            Jika Anda sedang menguji aplikasi tanpa scanner sungguhan, gunakan simulasi bayar instan di bawah ini:
                        </p>
                        <form action="{{ route('payment.simulate', $order->order_code) }}" method="POST">
                            @csrf
                            <button
                                type="submit"
                                class="w-full py-2.5 bg-butter/80 hover:bg-butter active:scale-98 text-text-main rounded-xl text-xs font-bold transition shadow-sm flex items-center justify-center gap-1.5"
                            >
                                <span>⚡ Simulasi Bayar Berhasil</span>
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </template>

        <!-- Collapsible "Ringkasan pesanan" -->
        <div class="bg-surface rounded-3xl border border-line shadow-sm overflow-hidden">
            <button
                type="button"
                @click="summaryOpen = !summaryOpen"
                class="w-full px-5 py-4 flex items-center justify-between text-left hover:bg-page transition"
            >
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-text-main uppercase tracking-wider">Ringkasan pesanan</span>
                    <span class="text-xs text-muted">({{ $order->items->count() }} menu)</span>
                </div>
                <svg
                    class="w-4 h-4 text-muted transition-transform duration-200"
                    :class="summaryOpen ? 'rotate-180' : ''"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="summaryOpen" x-cloak class="px-5 pb-4 border-t border-line pt-3 space-y-2.5 text-xs">
                <div class="flex justify-between text-muted">
                    <span>Kantin:</span>
                    <span class="font-bold text-text-main">{{ $order->canteen->name }}</span>
                </div>
                <div class="flex justify-between text-muted">
                    <span>Lokasi Duduk:</span>
                    <span class="font-bold text-text-main">{{ $order->zone->name ?? '-' }}</span>
                </div>

                <div class="divide-y divide-line pt-1">
                    @foreach ($order->items as $item)
                        <div class="py-2 flex justify-between items-start">
                            <div>
                                <span class="font-bold text-text-main">{{ $item->quantity }}x {{ $item->item_name }}</span>
                                @if ($item->note)
                                    <div class="text-[10px] text-accent-sage mt-0.5">Catatan: {{ $item->note }}</div>
                                @endif
                            </div>
                            <span class="font-semibold text-text-main">@rupiah($item->subtotal)</span>
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-line pt-2 flex justify-between font-extrabold text-sm text-text-main">
                    <span>Total Tagihan:</span>
                    <span class="text-brand">@rupiah($order->total_amount)</span>
                </div>
            </div>
        </div>

        <!-- Back link -->
        <div class="text-center pt-2">
            <a href="{{ route('order.show', $order->order_code) }}" class="text-xs font-semibold text-muted hover:text-text-main underline">
                Lihat Status & Detail Pesanan
            </a>
        </div>
    </div>
</x-layouts.buyer>
