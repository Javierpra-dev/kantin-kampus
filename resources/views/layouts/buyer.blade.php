@props([
    'title' => 'Kantin Kampus',
    'hideNav' => false,
])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <title>{{ $title ?? 'Kantin Kampus' }}</title>

    {{-- Anti-flash: apply .dark BEFORE first paint (full engine loads in app.js via Vite) --}}
    <script>(function(){try{var v=localStorage.getItem('theme');var dark=(v==='dark')||(v!=='light'&&window.matchMedia('(prefers-color-scheme: dark)').matches);document.documentElement.classList.toggle('dark',dark);document.documentElement.style.colorScheme=dark?'dark':'light';}catch(e){}}());</script>

    <!-- Google Font: Bricolage Grotesque & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-page text-text-main antialiased min-h-screen">
    <div class="max-w-[480px] mx-auto min-h-screen bg-page flex flex-col relative">

        <!-- Flash messages -->
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3500)" class="m-4 p-3.5 bg-sage-tint border border-line text-brand text-[13px] rounded-[14px] flex items-center justify-between">
                <span class="font-medium">{{ session('success') }}</span>
                <button @click="show = false" class="text-brand text-lg font-bold leading-none ml-2">&times;</button>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" class="m-4 p-3.5 bg-danger/10 border border-danger/20 text-danger text-[13px] rounded-[14px] flex items-center justify-between">
                <span class="font-medium">{{ session('error') }}</span>
                <button @click="show = false" class="text-danger text-lg font-bold leading-none ml-2">&times;</button>
            </div>
        @endif

        <!-- Main Content Slot -->
        <main class="flex-1 pb-24">
            {{ $slot ?? '' }}
            @yield('content')
        </main>

        <!-- Bottom Navigation -->
        @if (!($hideNav ?? false))
            <x-bottom-nav />
        @endif
    </div>

    @stack('scripts')
</body>
</html>
