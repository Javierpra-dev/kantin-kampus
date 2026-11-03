<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <title>Pesanan Masuk - {{ $canteen->name }}</title>

    {{-- Anti-flash: apply .dark BEFORE first paint (full engine in theme.js via Vite) --}}
    <script>(function(){try{var v=localStorage.getItem('theme');var dark=(v==='dark')||(v!=='light'&&window.matchMedia('(prefers-color-scheme: dark)').matches);document.documentElement.classList.toggle('dark',dark);document.documentElement.style.colorScheme=dark?'dark':'light';}catch(e){}}());</script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @vite(['resources/js/theme.js'])

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'] },
                    colors: {
                        page:    'var(--color-page)',
                        surface: 'var(--color-surface)',
                        line:    'var(--color-line)',
                        'text-main': 'var(--color-text-main)',
                        muted:   'var(--color-muted)',
                        brand:   'var(--color-brand)',
                        'on-brand': 'var(--color-on-brand)',
                        'accent-sage': 'var(--color-accent-sage)',
                        'sage-tint':  'var(--color-sage-tint)',
                        danger:  'var(--color-danger)',
                        butter:  'var(--color-butter)',
                    }
                }
            }
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }

        :root {
            --color-page:        #F7F8F4;
            --color-surface:     #FFFFFF;
            --color-line:        #E3E8E1;
            --color-text-main:   #1E2A24;
            --color-muted:       #78857D;
            --color-brand:       #2F4A3A;
            --color-on-brand:    #FFFFFF;
            --color-accent-sage: #5B8C6B;
            --color-sage-tint:   #E6EEE6;
            --color-danger:      #D64545;
            --color-butter:      #F2C94C;
        }
        .dark {
            --color-page:        #111613;
            --color-surface:     #1A221D;
            --color-line:        #2A352E;
            --color-text-main:   #E8EDE9;
            --color-muted:       #94A199;
            --color-brand:       #86B896;
            --color-on-brand:    #0E1A12;
            --color-accent-sage: #86B896;
            --color-sage-tint:   #1E2E22;
            --color-danger:      #F07575;
            --color-butter:      #F2C94C;
        }
        *, *::before, *::after {
            transition-property: background-color, border-color, color;
            transition-duration: 150ms;
            transition-timing-function: ease;
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition: none !important; }
        }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="bg-page font-sans antialiased min-h-screen text-text-main">
    <div
        x-data="{
            isOpen: {{ $canteen->is_open ? 'true' : 'false' }},
            soundEnabled: false,
            knownNewOrderIds: {{ json_encode($counts['baru'] > 0 ? $orders->pluck('id')->toArray() : []) }},
            activeTab: '{{ $currentTab }}',
            photoModalOpen: false,
            currentPhotoUrl: '',

            viewPhoto(url) {
                this.currentPhotoUrl = url;
                this.photoModalOpen = true;
            },

            enableSound() {
                this.soundEnabled = !this.soundEnabled;
                if (this.soundEnabled) {
                    this.playNotificationSound();
                }
            },

            playNotificationSound() {
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(587.33, ctx.currentTime);
                    osc.frequency.setValueAtTime(880, ctx.currentTime + 0.12);
                    gain.gain.setValueAtTime(0.25, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.35);
                } catch (e) {
                    console.log('Audio playback prevented or unsupported:', e);
                }
            },

            async toggleCanteen() {
                try {
                    const res = await fetch('{{ route('admin.canteen.toggle') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.isOpen = data.is_open;
                    }
                } catch (e) {
                    console.error('Error toggling canteen:', e);
                }
            },

            async pollOrders() {
                try {
                    const res = await fetch('{{ route('admin.orders.poll') }}', {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.isOpen = data.canteen_is_open;

                        const incomingIds = data.new_order_ids || [];
                        const hasNewArrival = incomingIds.some(id => !this.knownNewOrderIds.includes(id));
                        if (hasNewArrival) {
                            if (this.soundEnabled) {
                                this.playNotificationSound();
                            }
                            this.knownNewOrderIds = incomingIds;
                            window.location.reload();
                        }
                    }
                } catch (e) {
                    console.error('Polling error:', e);
                }
            }
        }"
        x-init="setInterval(() => pollOrders(), 5000)"
        class="max-w-md mx-auto min-h-screen bg-page flex flex-col relative shadow-xl shadow-black/5"
    >
        <!-- Top App Bar -->
        <header class="sticky top-0 z-30 bg-surface border-b border-line px-4 py-3 shadow-xs">
            <div class="flex items-center justify-between mb-2.5">
                <div>
                    <span class="text-[10px] uppercase font-bold text-muted tracking-wider">Panel Dapur</span>
                    <h1 class="text-base font-extrabold text-text-main leading-tight">{{ $canteen->name }}</h1>
                </div>

                <div class="flex items-center gap-1.5">
                    <!-- Nav Tabs -->
                    <a href="{{ route('admin.orders') }}" class="px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-brand text-on-brand shadow-xs transition">
                        Pesanan
                    </a>
                    <a href="{{ route('admin.menu.index') }}" class="px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-page text-text-main border border-line hover:bg-sage-tint transition">
                        Menu
                    </a>
                    <a href="{{ route('admin.profile') }}" class="px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-page text-text-main border border-line hover:bg-sage-tint transition">
                        Profil
                    </a>

                    <!-- Sound Toggle -->
                    <button
                        type="button"
                        @click="enableSound()"
                        class="px-2 py-1.5 rounded-xl text-xs font-semibold flex items-center gap-1 transition border"
                        :class="soundEnabled
                            ? 'bg-sage-tint text-brand border-accent-sage/40'
                            : 'bg-page text-muted border-line hover:bg-sage-tint'"
                        title="Toggle Notifikasi Suara"
                    >
                        <span x-text="soundEnabled ? '🔔' : '🔕'"></span>
                    </button>

                    <!-- Theme Switcher (plain JS, no Alpine dependency) -->
                    <div class="relative" style="position:relative">
                        <button type="button" data-theme-toggle aria-label="Ganti tema" style="position:relative;z-index:10;"
                            class="flex items-center justify-center w-11 h-11 min-w-[44px] min-h-[44px] rounded-xl bg-page border border-line hover:bg-sage-tint text-text-main transition">
                            <svg class="theme-icon-sun w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                            <svg class="theme-icon-moon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="display:none"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                        </button>
                        <div class="theme-menu hidden absolute right-0 top-full mt-1 z-[200] bg-surface border border-line rounded-xl shadow-lg overflow-hidden min-w-[110px]">
                            <button type="button" data-theme-choice="light" class="flex items-center justify-between w-full text-left px-3 py-2 text-xs text-text-main hover:bg-page transition"><span>Terang</span><svg class="theme-check w-3.5 h-3.5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="display:none"><polyline points="20 6 9 17 4 12"/></svg></button>
                            <button type="button" data-theme-choice="auto"  class="flex items-center justify-between w-full text-left px-3 py-2 text-xs text-text-main hover:bg-page transition"><span>Otomatis</span><svg class="theme-check w-3.5 h-3.5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="display:none"><polyline points="20 6 9 17 4 12"/></svg></button>
                            <button type="button" data-theme-choice="dark"  class="flex items-center justify-between w-full text-left px-3 py-2 text-xs text-text-main hover:bg-page transition"><span>Gelap</span><svg class="theme-check w-3.5 h-3.5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="display:none"><polyline points="20 6 9 17 4 12"/></svg></button>
                        </div>
                    </div>

                    <!-- Logout -->
                    <form action="{{ route('admin.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="p-1.5 rounded-xl text-muted hover:text-danger hover:bg-danger/10 transition" title="Keluar" aria-label="Logout">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Open / Closed Toggle Bar -->
            <div class="flex items-center justify-between bg-page p-2.5 rounded-2xl border border-line">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full" :class="isOpen ? 'bg-accent-sage animate-pulse' : 'bg-danger'"></span>
                    <span class="text-xs font-bold" :class="isOpen ? 'text-brand' : 'text-danger'" x-text="isOpen ? 'Kantin Sedang BUKA (Menerima Pesanan)' : 'Kantin Sedang TUTUP'">
                        {{ $canteen->is_open ? 'Kantin Sedang BUKA (Menerima Pesanan)' : 'Kantin Sedang TUTUP' }}
                    </span>
                </div>

                <button
                    type="button"
                    @click="toggleCanteen()"
                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                    :class="isOpen ? 'bg-accent-sage' : 'bg-line'"
                    role="switch"
                    :aria-checked="isOpen"
                >
                    <span
                        aria-hidden="true"
                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                        :class="isOpen ? 'translate-x-5' : 'translate-x-0'"
                    ></span>
                </button>
            </div>
        </header>

        <!-- Flash messages -->
        @if (session('success'))
            <div class="m-3 p-3 bg-sage-tint border border-line text-brand text-xs rounded-xl flex items-center justify-between">
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Status Tabs -->
        <div class="sticky top-[105px] z-20 bg-surface/95 backdrop-blur-md border-b border-line px-3 pt-2">
            <div class="flex gap-1.5 overflow-x-auto no-scrollbar pb-2">
                @php
                    $tabs = [
                        'baru'     => ['label' => 'Baru',     'count' => $counts['baru']],
                        'diproses' => ['label' => 'Diproses', 'count' => $counts['diproses']],
                        'diantar'  => ['label' => 'Diantar',  'count' => $counts['diantar']],
                        'selesai'  => ['label' => 'Selesai',  'count' => $counts['selesai']],
                    ];
                @endphp

                @foreach ($tabs as $key => $tab)
                    @php $isActive = ($currentTab === $key); @endphp
                    <a
                        href="{{ route('admin.orders', ['tab' => $key]) }}"
                        class="flex-1 min-w-[75px] py-2 px-2.5 rounded-xl text-center text-xs font-bold transition flex items-center justify-center gap-1.5 {{ $isActive ? 'bg-brand text-on-brand shadow-sm' : 'bg-page text-text-main hover:bg-sage-tint border border-line' }}"
                    >
                        <span>{{ $tab['label'] }}</span>
                        <span class="px-1.5 rounded-full text-[10px] {{ $isActive ? 'bg-on-brand/20 text-on-brand font-extrabold' : 'bg-line text-muted' }}">
                            {{ $tab['count'] }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Orders List -->
        <main class="p-4 space-y-4 flex-1">
            @forelse ($orders as $order)
                @php $isNew = ($currentTab === 'baru'); @endphp
                <div class="bg-surface rounded-3xl border border-line shadow-sm p-4 space-y-3.5 transition {{ $isNew ? 'border-l-4 border-l-accent-sage' : '' }}">
                    <!-- Card Header -->
                    <div class="flex items-start justify-between gap-2 border-b border-line pb-2.5">
                        <div>
                            <span class="text-xs font-mono font-extrabold text-brand">
                                {{ $order->order_code }}
                            </span>
                            <h3 class="text-sm font-bold text-text-main leading-tight mt-0.5">
                                {{ $order->customer_name }}
                            </h3>
                            @if ($order->customer_phone)
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->customer_phone) }}" target="_blank" class="text-[11px] text-accent-sage font-semibold flex items-center gap-1 mt-0.5 hover:underline">
                                    <span>📱 WhatsApp: {{ $order->customer_phone }}</span>
                                </a>
                            @endif
                        </div>
                        <div class="text-right flex-shrink-0">
                            <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold bg-page text-muted border border-line">
                                {{ $order->created_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>

                    <!-- Delivery Zone -->
                    <div class="bg-page rounded-2xl p-3 border border-line">
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1 flex-1 min-w-0">
                                <span class="text-[10px] uppercase font-bold text-muted tracking-wider block">LOKASI PENGANTARAN</span>
                                <div class="text-base font-extrabold text-brand leading-snug">
                                    📍 {{ $order->zone->name ?? 'Zona Kampus' }}
                                </div>
                                @if ($order->location_notes)
                                    <div class="text-xs font-semibold text-text-main italic pt-1 border-t border-line mt-1">
                                        Ciri tempat: "{{ $order->location_notes }}"
                                    </div>
                                @endif
                            </div>

                            @if ($order->location_photo)
                                <div class="flex-shrink-0 text-center">
                                    <button
                                        type="button"
                                        @click="viewPhoto('{{ route('admin.orders.photo', $order->id) }}')"
                                        class="block w-16 h-16 rounded-xl overflow-hidden border-2 border-accent-sage shadow-xs hover:opacity-90 transition cursor-pointer relative group"
                                        title="Perbesar foto tempat duduk"
                                    >
                                        <img
                                            src="{{ route('admin.orders.photo', $order->id) }}"
                                            alt="Foto tempat duduk"
                                            class="w-full h-full object-cover group-hover:scale-105 transition duration-200"
                                            loading="lazy"
                                        />
                                        <div class="absolute inset-0 bg-black/10 group-hover:bg-black/0 transition flex items-center justify-center">
                                            <svg class="w-4 h-4 text-white opacity-85 drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/>
                                            </svg>
                                        </div>
                                    </button>
                                    <span class="block text-[10px] text-muted font-semibold mt-1">Foto duduk</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Items List -->
                    <div class="space-y-2">
                        <span class="text-[10px] uppercase font-bold text-muted tracking-wider">PESANAN MENU</span>
                        <div class="divide-y divide-line">
                            @foreach ($order->items as $item)
                                <div class="py-2 flex items-start justify-between text-xs">
                                    <div>
                                        <div class="font-extrabold text-text-main">
                                            {{ $item->quantity }}x {{ $item->item_name }}
                                        </div>
                                        @if ($item->note)
                                            <div class="mt-1 inline-block px-2 py-0.5 bg-butter/20 text-text-main font-bold rounded-lg text-[11px] border border-butter/40">
                                                ⚠️ Catatan: {{ $item->note }}
                                            </div>
                                        @endif
                                    </div>
                                    <span class="font-bold text-text-main">@rupiah($item->subtotal)</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="border-t border-line pt-2 flex justify-between items-center text-xs">
                            <span class="text-muted font-medium">Total Dibayar:</span>
                            <span class="font-extrabold text-brand text-sm">@rupiah($order->total_amount)</span>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="pt-1">
                        @if ($currentTab === 'baru')
                            <form action="{{ route('admin.orders.process', $order->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full py-3 bg-brand hover:opacity-90 active:scale-98 text-on-brand text-xs font-bold rounded-xl transition shadow-md flex items-center justify-center gap-1.5">
                                    <span>🍳 Proses Pesanan</span>
                                </button>
                            </form>
                        @elseif ($currentTab === 'diproses')
                            <form action="{{ route('admin.orders.deliver', $order->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full py-3 bg-accent-sage hover:opacity-90 active:scale-98 text-white text-xs font-bold rounded-xl transition shadow-md flex items-center justify-center gap-1.5">
                                    <span>🛵 Antar Pesanan</span>
                                </button>
                            </form>
                        @elseif ($currentTab === 'diantar')
                            <form action="{{ route('admin.orders.complete', $order->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full py-3 bg-accent-sage hover:opacity-90 active:scale-98 text-white text-xs font-bold rounded-xl transition shadow-md flex items-center justify-center gap-1.5">
                                    <span>✅ Selesaikan Pesanan</span>
                                </button>
                            </form>
                        @elseif ($currentTab === 'selesai')
                            <div class="py-2 text-center text-xs font-bold text-brand bg-sage-tint rounded-xl border border-line">
                                ✓ Pesanan Telah Selesai
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-16 px-4 bg-surface rounded-3xl border border-line shadow-sm space-y-3">
                    <div class="w-16 h-16 mx-auto rounded-full bg-page flex items-center justify-center text-3xl">
                        ☕
                    </div>
                    <h3 class="text-sm font-bold text-text-main">Tidak ada pesanan di tab ini</h3>
                    <p class="text-xs text-muted max-w-xs mx-auto">
                        Pesanan baru yang telah dibayar oleh pembeli akan langsung muncul di sini.
                    </p>
                </div>
            @endforelse
        </main>

        <!-- Full-screen Photo Viewer Modal -->
        <div
            x-show="photoModalOpen"
            x-cloak
            @keydown.escape.window="photoModalOpen = false"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/85 p-4 backdrop-blur-sm"
        >
            <div
                @click.outside="photoModalOpen = false"
                class="relative max-w-lg w-full bg-surface rounded-2xl overflow-hidden border border-line shadow-2xl flex flex-col items-center"
            >
                <div class="w-full flex items-center justify-between px-4 py-3 bg-brand text-on-brand">
                    <span class="text-xs font-bold">Foto Tempat Duduk Pemesan</span>
                    <button
                        type="button"
                        @click="photoModalOpen = false"
                        class="w-8 h-8 rounded-full bg-on-brand/20 hover:bg-on-brand/30 text-on-brand flex items-center justify-center transition font-bold cursor-pointer"
                        aria-label="Tutup"
                    >
                        &times;
                    </button>
                </div>

                <div class="p-3 w-full flex items-center justify-center max-h-[75vh] overflow-auto bg-page">
                    <img
                        :src="currentPhotoUrl"
                        alt="Foto tempat duduk pembeli"
                        class="max-w-full max-h-[70vh] rounded-xl object-contain shadow-lg"
                    />
                </div>

                <div class="px-4 pb-3 w-full text-center bg-surface">
                    <button
                        type="button"
                        @click="photoModalOpen = false"
                        class="px-5 py-2 bg-page hover:bg-sage-tint text-text-main border border-line rounded-full text-xs font-semibold transition cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
