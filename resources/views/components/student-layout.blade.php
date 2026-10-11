@props(['title', 'guest' => false, 'settings' => false])
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title }} - SPARK</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml" />
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if ($settings)
        @livewireStyles
    @endif
    @unless ($guest)
        @fluxAppearance
    @endunless
</head>
<body class="spark-ui {{ $guest ? 'spark-guest' : '' }} {{ $settings ? 'spark-settings-page' : '' }}">
    <a href="#kandungan" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-white focus:p-4">Langkau ke kandungan</a>
    <header class="spark-header">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-8">
            <a href="{{ $guest ? route('login') : route('student.dashboard') }}" class="spark-brand" aria-label="SPARK {{ $guest ? 'Log Masuk' : 'Papan Pemuka' }}">
                <span class="spark-brand-icon"><flux:icon.bolt class="size-6" /></span> SPARK
                <span class="hidden border-l border-zinc-300 pl-4 text-xs font-bold text-zinc-600 sm:block">SAINS KSSR<br />TAHUN 6</span>
            </a>
            @unless ($guest)
                <nav class="flex flex-wrap items-center gap-2 text-sm font-extrabold" aria-label="Navigasi pelajar">
                    <a class="spark-nav" href="{{ route('student.dashboard') }}" @if(request()->routeIs('student.dashboard')) aria-current="page" @endif><flux:icon.home class="size-4" /> Papan Pemuka</a>
                    <a class="spark-nav" href="{{ route('profile.edit') }}" @if(request()->routeIs('profile.edit', 'security.edit', 'appearance.edit')) aria-current="page" @endif><flux:icon.user-circle class="size-4" /> Profil</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="spark-nav" type="submit"><flux:icon.arrow-right-start-on-rectangle class="size-4" /> Log Keluar</button></form>
                </nav>
            @else
                <span class="text-sm font-bold text-zinc-600">Sedikit usaha, banyak penemuan.</span>
            @endunless
        </div>
    </header>
    <main id="kandungan" class="mx-auto w-full max-w-6xl px-5 py-8 sm:px-8 sm:py-10 {{ $guest ? 'spark-auth-main' : '' }}">
        {{ $slot }}
    </main>
    <footer class="mx-auto mt-auto w-full max-w-6xl px-5 py-6 text-center text-xs font-bold text-zinc-600">SPARK &middot; Belajar. Cuba. Berjaya.</footer>
    @if ($settings)
        <flux:toast.group><flux:toast /></flux:toast.group>
        @livewireScripts
    @endif
    @fluxScripts
</body>
</html>
