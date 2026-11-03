<x-layouts.buyer title="Riwayat Pesanan">
    <x-page-header title="Riwayat Pesanan" :backUrl="url('/')" />

    <div class="p-4 space-y-3">
        @forelse ($orders as $order)
            <a href="{{ route('order.show', $order->order_code) }}" class="block group">
                <x-card padding="p-4" class="hover:border-accent-sage/60 transition-all">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="text-[10px] font-mono font-bold text-muted uppercase tracking-wider">{{ $order->order_code }}</span>
                            <h3 class="text-sm font-bold text-text-main mt-0.5">{{ $order->canteen->name }}</h3>
                            <p class="text-xs text-muted mt-0.5">
                                {{ $order->items->pluck('item_name')->implode(', ') }}
                            </p>
                        </div>
                        <div class="flex flex-col items-end gap-1 flex-shrink-0">
                            @if ($order->status === 'selesai')
                                <x-badge variant="success" size="xs">Selesai</x-badge>
                            @elseif ($order->status === 'diantar')
                                <x-badge variant="primary" size="xs">Diantar</x-badge>
                            @elseif ($order->status === 'diproses')
                                <x-badge variant="warning" size="xs">Diproses</x-badge>
                            @else
                                <x-badge variant="danger" size="xs">Menunggu Bayar</x-badge>
                            @endif
                            <span class="text-xs font-extrabold text-brand">@rupiah($order->total_amount)</span>
                        </div>
                    </div>
                    <div class="border-t border-line mt-3 pt-2 flex items-center justify-between text-[11px] text-muted">
                        <span>{{ $order->created_at->translatedFormat('d M Y, H:i') }}</span>
                        <span class="text-accent-sage font-semibold group-hover:underline">Lihat detail &rarr;</span>
                    </div>
                </x-card>
            </a>
        @empty
            <div class="py-16 text-center bg-surface rounded-3xl border border-line shadow-sm p-6 space-y-3">
                <div class="w-16 h-16 mx-auto rounded-full bg-page flex items-center justify-center text-3xl">
                    📜
                </div>
                <h2 class="text-sm font-bold text-text-main">Belum Ada Riwayat Pesanan</h2>
                <p class="text-xs text-muted max-w-xs mx-auto">
                    Pesanan yang Anda buat akan tersimpan di riwayat sesi ini.
                </p>
                <div class="pt-2">
                    <x-button :href="url('/')" variant="primary" size="md">
                        Mulai Pesan Makanan
                    </x-button>
                </div>
            </div>
        @endforelse
    </div>
</x-layouts.buyer>
