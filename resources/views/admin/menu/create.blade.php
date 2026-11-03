<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="color-scheme" content="light dark">
    <title>Tambah Menu Baru - {{ $canteen->name }}</title>

    <!-- Anti-flash: apply .dark BEFORE first paint (full engine in theme.js via Vite) -->
    <script>(function(){try{var v=localStorage.getItem('theme');var dark=(v==='dark')||(v!=='light'&&window.matchMedia('(prefers-color-scheme: dark)').matches);document.documentElement.classList.toggle('dark',dark);document.documentElement.style.colorScheme=dark?'dark':'light';}catch(e){}}());</script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    @vite(['resources/js/theme.js'])

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        page:        'var(--color-page)',
                        surface:     'var(--color-surface)',
                        line:        'var(--color-line)',
                        'text-main': 'var(--color-text-main)',
                        muted:       'var(--color-muted)',
                        brand:       'var(--color-brand)',
                        'on-brand':  'var(--color-on-brand)',
                        'accent-sage':'var(--color-accent-sage)',
                        'sage-tint': 'var(--color-sage-tint)',
                        danger:      'var(--color-danger)',
                        butter:      'var(--color-butter)',
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        :root {
            --color-page:#F7F8F4; --color-surface:#FFFFFF; --color-line:#E3E8E1;
            --color-text-main:#1E2A24; --color-muted:#78857D; --color-brand:#2F4A3A;
            --color-on-brand:#FFFFFF; --color-accent-sage:#5B8C6B; --color-sage-tint:#E6EEE6;
            --color-danger:#D64545; --color-butter:#F2C94C;
        }
        .dark {
            --color-page:#111613; --color-surface:#1A221D; --color-line:#2A352E;
            --color-text-main:#E8EDE9; --color-muted:#94A199; --color-brand:#86B896;
            --color-on-brand:#0E1A12; --color-accent-sage:#86B896; --color-sage-tint:#1E2E22;
            --color-danger:#F07575; --color-butter:#F2C94C;
        }
        *,*::before,*::after{transition-property:background-color,border-color,color;transition-duration:150ms;transition-timing-function:ease;}
        @media(prefers-reduced-motion:reduce){*,*::before,*::after{transition:none!important;}}
    </style>
</head>
<body class="bg-page font-sans antialiased min-h-screen text-text-main">
    <div class="max-w-md mx-auto min-h-screen bg-page flex flex-col relative shadow-xl pb-12">

        <!-- Header -->
        <header class="sticky top-0 z-30 bg-surface border-b border-line px-4 py-3.5 flex items-center gap-3">
            <a
                href="{{ route('admin.menu.index') }}"
                class="w-9 h-9 rounded-full bg-page hover:bg-sage-tint text-text-main flex items-center justify-center transition"
                aria-label="Kembali"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="text-base font-extrabold text-text-main leading-tight">Tambah Menu Baru</h1>
                <p class="text-xs text-muted truncate">{{ $canteen->name }}</p>
            </div>

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
        </header>

        <!-- Form -->
        <main class="p-4">
            <form action="{{ route('admin.menu.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div class="bg-surface rounded-3xl border border-line shadow-sm p-5 space-y-4">
                    <!-- Nama Menu -->
                    <div>
                        <label for="name" class="block text-xs font-semibold text-text-main mb-1">
                            Nama Menu <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            required
                            value="{{ old('name') }}"
                            placeholder="mis. Nasi Goreng Spesial"
                            class="block w-full rounded-xl border border-line bg-surface px-3.5 py-2.5 text-sm text-text-main placeholder:text-muted focus:border-accent-sage focus:ring-1 focus:ring-accent-sage transition @error('name') border-danger @enderror"
                        />
                        @error('name')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Kategori -->
                    <div>
                        <label for="category" class="block text-xs font-semibold text-text-main mb-1">
                            Kategori <span class="text-danger">*</span>
                        </label>
                        <select
                            name="category"
                            id="category"
                            required
                            class="block w-full rounded-xl border border-line bg-surface px-3.5 py-2.5 text-sm text-text-main focus:border-accent-sage focus:ring-1 focus:ring-accent-sage transition @error('category') border-danger @enderror"
                        >
                            <option value="">Pilih Kategori</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>
                                    {{ $cat }}
                                </option>
                            @endforeach
                        </select>
                        @error('category')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Harga -->
                    <div>
                        <label for="price" class="block text-xs font-semibold text-text-main mb-1">
                            Harga (Rupiah) <span class="text-danger">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-xs font-bold text-muted pointer-events-none">
                                Rp
                            </span>
                            <input
                                type="number"
                                name="price"
                                id="price"
                                required
                                min="0"
                                step="1"
                                value="{{ old('price') }}"
                                placeholder="15000"
                                class="block w-full rounded-xl border border-line bg-surface pl-10 pr-3.5 py-2.5 text-sm text-text-main placeholder:text-muted focus:border-accent-sage focus:ring-1 focus:ring-accent-sage transition @error('price') border-danger @enderror"
                            />
                        </div>
                        @error('price')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Deskripsi -->
                    <div>
                        <label for="description" class="block text-xs font-semibold text-text-main mb-1">
                            Deskripsi Singkat <span class="text-xs font-normal text-muted">(Opsional)</span>
                        </label>
                        <textarea
                            name="description"
                            id="description"
                            rows="3"
                            placeholder="Tuliskan keterangan porsi, rasa, atau bahan pelengkap..."
                            class="block w-full rounded-xl border border-line bg-surface px-3.5 py-2.5 text-sm text-text-main placeholder:text-muted focus:border-accent-sage focus:ring-1 focus:ring-accent-sage transition @error('description') border-danger @enderror"
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Upload Foto -->
                    <div>
                        <label for="photo" class="block text-xs font-semibold text-text-main mb-1">
                            Foto Menu <span class="text-xs font-normal text-muted">(Opsional, maks. 2 MB)</span>
                        </label>
                        <input
                            type="file"
                            name="photo"
                            id="photo"
                            accept="image/*"
                            class="block w-full text-xs text-muted file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-sage-tint file:text-brand hover:file:bg-line transition cursor-pointer"
                        />
                        <p class="mt-1 text-[11px] text-muted">Format: JPG, PNG, WEBP. Maksimal 2 MB.</p>
                        @error('photo')
                            <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tag Rasa (flavor_tags) -->
                    <div>
                        <span class="block text-xs font-semibold text-text-main mb-1.5">
                            Tag Rasa <span class="text-xs font-normal text-muted">(Opsional, dapat memilih lebih dari satu)</span>
                        </span>
                        <div class="flex flex-wrap gap-4 pt-1">
                            @php
                                $selectedTags = old('flavor_tags', []);
                            @endphp
                            <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-text-main font-medium">
                                <input
                                    type="checkbox"
                                    name="flavor_tags[]"
                                    value="segar"
                                    {{ in_array('segar', $selectedTags) ? 'checked' : '' }}
                                    class="w-4 h-4 rounded border-line text-accent-sage focus:ring-accent-sage"
                                />
                                <span>Segar</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-text-main font-medium">
                                <input
                                    type="checkbox"
                                    name="flavor_tags[]"
                                    value="hangat"
                                    {{ in_array('hangat', $selectedTags) ? 'checked' : '' }}
                                    class="w-4 h-4 rounded border-line text-accent-sage focus:ring-accent-sage"
                                />
                                <span>Hangat</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-text-main font-medium">
                                <input
                                    type="checkbox"
                                    name="flavor_tags[]"
                                    value="pedas"
                                    {{ in_array('pedas', $selectedTags) ? 'checked' : '' }}
                                    class="w-4 h-4 rounded border-line text-accent-sage focus:ring-accent-sage"
                                />
                                <span>Pedas</span>
                            </label>
                        </div>
                        @error('flavor_tags')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                        @error('flavor_tags.*')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Sisa Porsi & Promo -->
                    <div class="pt-2 border-t border-line space-y-4">
                        <!-- Sisa Porsi Hari Ini -->
                        <div>
                            <label for="daily_stock" class="block text-xs font-semibold text-text-main mb-1">
                                Sisa porsi hari ini <span class="text-xs font-normal text-muted">(Opsional)</span>
                            </label>
                            <input
                                type="number"
                                name="daily_stock"
                                id="daily_stock"
                                min="0"
                                step="1"
                                value="{{ old('daily_stock') }}"
                                placeholder="Kosongkan jika tak terbatas"
                                class="block w-full rounded-xl border border-line bg-surface px-3.5 py-2.5 text-sm text-text-main placeholder:text-muted focus:border-accent-sage focus:ring-1 focus:ring-accent-sage transition @error('daily_stock') border-danger @enderror"
                            />
                            <p class="mt-1 text-[11px] text-muted">Akan berkurang otomatis setiap ada pesanan masuk. Saat 0, menu otomatis Habis.</p>
                            @error('daily_stock')
                                <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Harga Promo -->
                        <div>
                            <label for="promo_price" class="block text-xs font-semibold text-text-main mb-1">
                                Harga promo <span class="text-xs font-normal text-muted">(Opsional, harus lebih murah dari harga normal)</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-xs font-bold text-muted pointer-events-none">
                                    Rp
                                </span>
                                <input
                                    type="number"
                                    name="promo_price"
                                    id="promo_price"
                                    min="0"
                                    step="1"
                                    value="{{ old('promo_price') }}"
                                    placeholder="mis. 10000"
                                    class="block w-full rounded-xl border border-line bg-surface pl-10 pr-3.5 py-2.5 text-sm text-text-main placeholder:text-muted focus:border-accent-sage focus:ring-1 focus:ring-accent-sage transition @error('promo_price') border-danger @enderror"
                                />
                            </div>
                            @error('promo_price')
                                <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Jam Promo -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="promo_start" class="block text-xs font-semibold text-text-main mb-1">
                                    Promo mulai jam
                                </label>
                                <input
                                    type="time"
                                    name="promo_start"
                                    id="promo_start"
                                    value="{{ old('promo_start') }}"
                                    class="block w-full rounded-xl border border-line bg-surface px-3.5 py-2.5 text-sm text-text-main focus:border-accent-sage focus:ring-1 focus:ring-accent-sage transition @error('promo_start') border-danger @enderror"
                                />
                                @error('promo_start')
                                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="promo_end" class="block text-xs font-semibold text-text-main mb-1">
                                    Promo sampai jam
                                </label>
                                <input
                                    type="time"
                                    name="promo_end"
                                    id="promo_end"
                                    value="{{ old('promo_end') }}"
                                    class="block w-full rounded-xl border border-line bg-surface px-3.5 py-2.5 text-sm text-text-main focus:border-accent-sage focus:ring-1 focus:ring-accent-sage transition @error('promo_end') border-danger @enderror"
                                />
                                @error('promo_end')
                                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-3 pt-2">
                    <a
                        href="{{ route('admin.menu.index') }}"
                        class="flex-1 py-3 text-center border border-line bg-surface hover:bg-page rounded-xl text-xs font-bold text-text-main transition"
                    >
                        Batal
                    </a>
                    <button
                        type="submit"
                        class="flex-1 py-3 bg-brand hover:opacity-90 active:scale-98 text-on-brand rounded-xl text-xs font-bold transition shadow-md"
                    >
                        Simpan Menu
                    </button>
                </div>
            </form>
        </main>
    </div>
</body>
</html>
