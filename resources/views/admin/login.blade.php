<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="color-scheme" content="light dark">
    <title>Masuk Admin Kantin</title>

    <!-- Anti-flash: apply .dark BEFORE first paint (full engine in theme.js via Vite) -->
    <script>(function(){try{var v=localStorage.getItem('theme');var dark=(v==='dark')||(v!=='light'&&window.matchMedia('(prefers-color-scheme: dark)').matches);document.documentElement.classList.toggle('dark',dark);document.documentElement.style.colorScheme=dark?'dark':'light';}catch(e){}}());</script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
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
<body class="bg-page font-sans antialiased min-h-screen flex items-center justify-center p-4 text-text-main">
    <div class="max-w-sm w-full bg-surface rounded-3xl border border-line shadow-xl p-6 space-y-5 relative">
        <!-- Theme Toggle -->
        <div class="absolute top-5 right-5">
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
        </div>
        <!-- Header -->
        <div class="text-center space-y-1">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-brand text-on-brand flex items-center justify-center text-2xl shadow-md">
                👨‍🍳
            </div>
            <h1 class="text-lg font-extrabold text-text-main pt-2">Panel Admin Kantin</h1>
            <p class="text-xs text-muted">Masuk untuk mengelola pesanan kantin Anda</p>
        </div>

        @if (session('success'))
            <div class="p-3 bg-sage-tint border border-line text-brand text-xs rounded-xl">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="p-3 bg-danger/10 border border-danger/20 text-danger text-xs rounded-xl space-y-1">
                @foreach ($errors->all() as $err)
                    <p>{{ $err }}</p>
                @endforeach
            </div>
        @endif

        <!-- Form -->
        <form action="{{ route('admin.login.submit') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="username" class="block text-xs font-semibold text-text-main mb-1">Username</label>
                <input
                    type="text"
                    name="username"
                    id="username"
                    required
                    autofocus
                    value="{{ old('username') }}"
                    placeholder="mis. kantin1"
                    class="block w-full rounded-xl border border-line bg-surface px-3.5 py-2.5 text-sm text-text-main placeholder:text-muted focus:border-accent-sage focus:ring-1 focus:ring-accent-sage transition"
                />
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-text-main mb-1">Password</label>
                <input
                    type="password"
                    name="password"
                    id="password"
                    required
                    placeholder="••••••••"
                    class="block w-full rounded-xl border border-line bg-surface px-3.5 py-2.5 text-sm text-text-main placeholder:text-muted focus:border-accent-sage focus:ring-1 focus:ring-accent-sage transition"
                />
            </div>

            <div class="pt-2">
                <button
                    type="submit"
                    class="w-full py-3 bg-brand hover:opacity-90 text-on-brand font-bold rounded-xl text-xs transition shadow-md active:scale-98"
                >
                    Masuk ke Dapur Kantin
                </button>
            </div>
        </form>

        <!-- Help / Demo Hint -->
        <div class="border-t border-line pt-3 text-center">
            <p class="text-[11px] text-muted">
                Demo Admin: username <code class="font-bold text-text-main">kantin1</code> s.d. <code class="font-bold text-text-main">kantin4</code> | password <code class="font-bold text-text-main">password</code>
            </p>
            <div class="mt-3">
                <a href="{{ url('/') }}" class="text-xs text-accent-sage hover:underline">
                    &larr; Kembali ke Tampilan Pembeli
                </a>
            </div>
        </div>
    </div>
</body>
</html>
