<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <title>Profil Kantin - {{ $canteen->name }}</title>

    <!-- Anti-flash: apply .dark BEFORE first paint (full engine in theme.js via Vite) -->
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
            --color-page: #F7F8F4; --color-surface: #FFFFFF; --color-line: #E3E8E1;
            --color-text-main: #1E2A24; --color-muted: #78857D; --color-brand: #2F4A3A;
            --color-on-brand: #FFFFFF; --color-accent-sage: #5B8C6B; --color-sage-tint: #E6EEE6;
            --color-danger: #D64545; --color-butter: #F2C94C;
        }
        .dark {
            --color-page: #111613; --color-surface: #1A221D; --color-line: #2A352E;
            --color-text-main: #E8EDE9; --color-muted: #94A199; --color-brand: #86B896;
            --color-on-brand: #0E1A12; --color-accent-sage: #86B896; --color-sage-tint: #1E2E22;
            --color-danger: #F07575; --color-butter: #F2C94C;
        }
        *, *::before, *::after { transition-property: background-color, border-color, color; transition-duration: 150ms; transition-timing-function: ease; }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { transition: none !important; } }
    </style>
</head>
<body class="bg-page font-sans antialiased min-h-screen text-text-main">
    <div
        x-data="{
            isOpen: {{ old('is_open', $canteen->is_open) ? 'true' : 'false' }},
            desc: '{{ addslashes(old('description', $canteen->description ?? '')) }}',
            async toggleCanteen() {
                try {
                    const res = await fetch('{{ route('admin.canteen.toggle') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    if (data.success) { this.isOpen = data.is_open; }
                } catch (e) { console.error('Error toggling canteen:', e); }
            }
        }"
        class="max-w-md mx-auto min-h-screen bg-page flex flex-col relative shadow-xl shadow-black/5 pb-20"
    >
        <!-- Top App Bar & Navigation -->
        <header class="sticky top-0 z-30 bg-surface border-b border-line px-4 py-3 shadow-xs">
            <div class="flex items-center justify-between mb-2.5">
                <div>
                    <span class="text-[10px] uppercase font-bold text-muted tracking-wider">Profil Kantin</span>
                    <h1 class="text-base font-extrabold text-text-main leading-tight">{{ $canteen->name }}</h1>
                </div>

                <nav class="flex items-center gap-1.5">
                    <a href="{{ route('admin.orders') }}" class="px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-page text-text-main hover:bg-sage-tint border border-line transition">Pesanan</a>
                    <a href="{{ route('admin.menu.index') }}" class="px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-page text-text-main hover:bg-sage-tint border border-line transition">Menu</a>
                    <a href="{{ route('admin.profile') }}" class="px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-brand text-on-brand shadow-xs transition">Profil</a>

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

                    <form action="{{ route('admin.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="p-1.5 rounded-xl text-muted hover:text-danger hover:bg-danger/10 transition" title="Keluar" aria-label="Logout">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>
                </nav>
            </div>

            <!-- Open / Closed Toggle Bar -->
            <div class="flex items-center justify-between bg-page p-2.5 rounded-2xl border border-line">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full" :class="isOpen ? 'bg-accent-sage animate-pulse' : 'bg-danger'"></span>
                    <span class="text-xs font-bold" :class="isOpen ? 'text-brand' : 'text-danger'" x-text="isOpen ? 'Kantin Sedang BUKA' : 'Kantin Sedang TUTUP'">
                        {{ $canteen->is_open ? 'Kantin Sedang BUKA' : 'Kantin Sedang TUTUP' }}
                    </span>
                </div>
                <button type="button" @click="toggleCanteen()"
                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                    :class="isOpen ? 'bg-accent-sage' : 'bg-line'" role="switch" :aria-checked="isOpen" aria-label="Toggle status buka tutup kantin">
                    <span aria-hidden="true" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="isOpen ? 'translate-x-5' : 'translate-x-0'"></span>
                </button>
            </div>
        </header>

        <!-- Flash messages -->
        @if (session('success'))
            <div class="m-4 p-3.5 bg-sage-tint border border-line text-brand text-xs rounded-2xl flex items-center justify-between shadow-xs">
                <span class="font-bold">{{ session('success') }}</span>
                <span class="text-brand font-extrabold text-sm">✓</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="m-4 p-3.5 bg-danger/10 border border-danger/20 text-text-main text-xs rounded-2xl space-y-1 shadow-xs">
                <span class="font-bold block text-danger">Mohon periksa kembali isian formulir:</span>
                <ul class="list-disc list-inside space-y-0.5 text-[11px] text-muted">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Profile Form -->
        <main class="p-4 flex-1">
            <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @method('PUT')

                <input type="hidden" name="is_open" :value="isOpen ? 1 : 0" />

                <div class="bg-surface rounded-3xl border border-line shadow-sm p-5 space-y-4">
                    <!-- Current Photo Banner -->
                    <div>
                        <span class="block text-xs font-semibold text-text-main mb-1.5">Foto Sampul Kantin (Landscape)</span>
                        <div class="relative w-full h-40 rounded-2xl overflow-hidden bg-sage-tint border border-line shadow-xs">
                            <img src="{{ $canteen->photo_url }}" alt="{{ $canteen->name }}" class="w-full h-full object-cover" />
                            <div class="absolute bottom-2 left-2 px-2.5 py-1 rounded-lg bg-black/60 text-white text-[10px] font-semibold">Foto saat ini</div>
                        </div>
                    </div>

                    <!-- Upload New Photo -->
                    <div>
                        <label for="photo" class="block text-xs font-semibold text-text-main mb-1">
                            Ganti Foto Sampul <span class="text-xs font-normal text-muted">(Opsional, maks. 3 MB)</span>
                        </label>
                        <input type="file" name="photo" id="photo" accept="image/*"
                            class="block w-full text-xs text-muted file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-sage-tint file:text-brand hover:file:bg-line transition cursor-pointer" />
                        <p class="mt-1 text-[11px] text-muted">Format: JPG, PNG, WEBP. Maksimal 3 MB (otomatis dioptimasi GD ke JPEG 1600px).</p>
                        @error('photo') <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p> @enderror
                    </div>

                    <!-- Nama Kantin -->
                    <div>
                        <label for="name" class="block text-xs font-semibold text-text-main mb-1">Nama Kantin <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" required value="{{ old('name', $canteen->name) }}" placeholder="mis. Kantin 1"
                            class="block w-full rounded-xl border border-line bg-surface px-3.5 py-2.5 text-sm text-text-main placeholder:text-muted focus:border-accent-sage focus:ring-1 focus:ring-accent-sage transition @error('name') border-danger @enderror" />
                        @error('name') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <!-- Deskripsi dengan Live Counter -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="description" class="block text-xs font-semibold text-text-main">
                                Deskripsi Singkat <span class="text-xs font-normal text-muted">(Maks. 120 karakter)</span>
                            </label>
                            <span class="text-[11px] font-bold" :class="desc.length > 120 ? 'text-danger' : 'text-muted'" x-text="desc.length + '/120'"></span>
                        </div>
                        <textarea name="description" id="description" rows="2" maxlength="120" x-model="desc"
                            placeholder="mis. Spesialis Aneka Nasi Goreng & Masakan Tradisional Kampus"
                            class="block w-full rounded-xl border border-line bg-surface px-3.5 py-2.5 text-sm text-text-main placeholder:text-muted focus:border-accent-sage focus:ring-1 focus:ring-accent-sage transition @error('description') border-danger @enderror"
                        >{{ old('description', $canteen->description) }}</textarea>
                        <p class="mt-1 text-[11px] text-muted">Deskripsi singkat muncul di carousel beranda dan kartu pencarian pembeli.</p>
                        @error('description') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <!-- Jam Buka & Tutup -->
                    <div>
                        <span class="block text-xs font-semibold text-text-main mb-1">Jam Operasional</span>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="open_time" class="block text-[11px] text-muted mb-1">Buka</label>
                                <input type="time" name="open_time" id="open_time" value="{{ old('open_time', $canteen->open_time ?? '08:00') }}"
                                    class="block w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm text-text-main focus:border-accent-sage focus:ring-1 focus:ring-accent-sage transition @error('open_time') border-danger @enderror" />
                            </div>
                            <div>
                                <label for="close_time" class="block text-[11px] text-muted mb-1">Tutup</label>
                                <input type="time" name="close_time" id="close_time" value="{{ old('close_time', $canteen->close_time ?? '17:00') }}"
                                    class="block w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm text-text-main focus:border-accent-sage focus:ring-1 focus:ring-accent-sage transition @error('close_time') border-danger @enderror" />
                            </div>
                        </div>
                        <p class="mt-1 text-[11px] text-muted">Format jam ini akan tampil pada header halaman kantin.</p>
                    </div>

                    <!-- Nomor WhatsApp -->
                    <div>
                        <label for="whatsapp" class="block text-xs font-semibold text-text-main mb-1">
                            Nomor WhatsApp Kantin <span class="text-xs font-normal text-muted">(Opsional)</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-xs font-bold text-muted pointer-events-none">📱 +62 / 08</span>
                            <input type="tel" name="whatsapp" id="whatsapp" value="{{ old('whatsapp', $canteen->whatsapp ?? $canteen->phone) }}" placeholder="081234567890"
                                class="block w-full rounded-xl border border-line bg-surface pl-24 pr-3.5 py-2.5 text-sm text-text-main placeholder:text-muted focus:border-accent-sage focus:ring-1 focus:ring-accent-sage transition @error('whatsapp') border-danger @enderror" />
                        </div>
                        <p class="mt-1 text-[11px] text-muted">Digunakan oleh tombol "Hubungi Kantin via WhatsApp" pada pelacakan pesanan pembeli.</p>
                        @error('whatsapp') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit"
                        class="w-full h-[52px] bg-brand hover:opacity-90 active:scale-98 text-on-brand rounded-full text-sm font-bold transition shadow-lg flex items-center justify-center gap-2 cursor-pointer">
                        <span>Simpan perubahan</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </button>
                </div>
            </form>
        </main>
    </div>
</body>
</html>
