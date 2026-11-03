<x-layouts.buyer title="Detail Pesanan" :hideNav="false">
    <x-page-header title="Detail Pesanan" :backUrl="url('/')" />

    <div class="p-4 space-y-4">
        <!-- Status Banner -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-5 text-center space-y-3">
            <div class="w-14 h-14 mx-auto rounded-full bg-amber-50 text-amber-600 flex items-center justify-center text-2xl">
                ⏳
            </div>
            <div>
                <span class="text-xs font-mono font-bold text-gray-500 uppercase tracking-widest">{{ $order->order_code }}</span>
                <h2 class="text-lg font-extrabold text-gray-900 mt-0.5">Pesanan Berhasil Dibuat</h2>
                <div class="flex items-center justify-center gap-2 mt-2">
                    <x-badge variant="warning" class="font-bold">
                        Menunggu Pembayaran
                    </x-badge>
                    <x-badge variant="danger" class="font-semibold text-[10px]">
                        Belum Bayar
                    </x-badge>
                </div>
            </div>
        </div>

        <!-- Canteen & Delivery Location Info -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-4 space-y-3 divide-y divide-gray-100 text-xs">
            <!-- Canteen Info -->
            <div class="pb-3 flex items-center justify-between">
                <div>
                    <span class="text-gray-400 block text-[10px] uppercase font-bold">Kantin</span>
                    <h3 class="text-sm font-bold text-gray-900">{{ $order->canteen->name }}</h3>
                </div>
                @if ($order->canteen->phone)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->canteen->phone) }}" target="_blank" class="px-3 py-1.5 bg-emerald-50 text-emerald-700 font-bold rounded-xl border border-emerald-200">
                        Hubungi Kantin
                    </a>
                @endif
            </div>

            <!-- Customer & Zone Info -->
            <div class="pt-3 space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-400">Pemesan:</span>
                    <span class="font-bold text-gray-900">{{ $order->customer_name }}</span>
                </div>
                @if ($order->customer_phone)
                    <div class="flex justify-between">
                        <span class="text-gray-400">WhatsApp:</span>
                        <span class="font-medium text-gray-800">{{ $order->customer_phone }}</span>
                    </div>
                @endif
                <div class="flex justify-between items-start">
                    <span class="text-gray-400">Lokasi Duduk:</span>
                    <span class="font-bold text-gray-900 text-right max-w-[200px]">{{ $order->zone->name ?? '-' }}</span>
                </div>
                @if ($order->location_notes)
                    <div class="flex justify-between items-start">
                        <span class="text-gray-400">Ciri/Keterangan:</span>
                        <span class="font-medium text-gray-700 italic text-right max-w-[200px]">"{{ $order->location_notes }}"</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Items Ordered -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-4 space-y-3">
            <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Rincian Menu</h3>

            <div class="divide-y divide-gray-100">
                @foreach ($order->items as $item)
                    <div class="py-2.5 flex items-start justify-between text-xs">
                        <div class="pr-2">
                            <h4 class="font-bold text-gray-800 leading-snug">
                                {{ $item->quantity }}x {{ $item->item_name }}
                            </h4>
                            @if ($item->note)
                                <p class="text-[11px] text-gray-500 italic mt-0.5">Catatan: "{{ $item->note }}"</p>
                            @endif
                            <span class="text-gray-400 text-[10px]">@ @rupiah($item->unit_price)</span>
                        </div>
                        <span class="font-bold text-gray-900 flex-shrink-0">
                            @rupiah($item->subtotal)
                        </span>
                    </div>
                @endforeach
            </div>

            <!-- Total -->
            <div class="border-t border-gray-100 pt-3 space-y-1.5 text-xs">
                <div class="flex justify-between text-gray-500">
                    <span>Subtotal Menu</span>
                    <span class="font-medium text-gray-800">@rupiah($order->subtotal)</span>
                </div>
                <div class="flex justify-between text-gray-500">
                    <span>Ongkir Kampus</span>
                    <span class="font-medium text-emerald-600 font-bold">
                        {{ $order->delivery_fee > 0 ? rupiah($order->delivery_fee) : 'GRATIS' }}
                    </span>
                </div>
                <div class="flex justify-between text-sm font-extrabold text-gray-900 pt-2 border-t border-gray-100">
                    <span>Total Pembayaran</span>
                    <span class="text-orange-600">@rupiah($order->total_amount)</span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="space-y-2 pt-2">
            <x-button :href="url('/')" variant="primary" size="lg" class="w-full">
                Kembali ke Beranda
            </x-button>
        </div>
    </div>
</x-layouts.buyer>
