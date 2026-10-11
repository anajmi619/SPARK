@if (auth()->user()->role === \App\Enums\UserRole::Student)
    <div class="spark-settings-grid">
        <aside>
            <div class="mb-6 flex items-center gap-3">
                <span class="spark-settings-avatar" aria-hidden="true">{{ auth()->user()->initials() }}</span>
                <div class="min-w-0">
                    <p class="font-extrabold break-words">{{ auth()->user()->full_name }}</p>
                    <p class="spark-settings-muted mt-1 text-sm break-words">{{ auth()->user()->username }}</p>
                </div>
            </div>
            <nav class="spark-settings-nav" aria-label="Tetapan">
                @foreach ([['profile.edit', 'user-circle', 'Profil'], ['security.edit', 'shield-check', 'Keselamatan'], ['appearance.edit', 'swatch', 'Paparan']] as [$route, $icon, $label])
                    <a href="{{ route($route) }}" @if(request()->routeIs($route)) aria-current="page" @endif>
                        <span class="spark-settings-nav-icon"><flux:icon :name="$icon" class="size-5" /></span>
                        <span>{{ $label }}</span>
                        <flux:icon.chevron-right class="ml-auto hidden size-4 md:block" />
                    </a>
                @endforeach
            </nav>
        </aside>
        <section class="spark-settings-content" aria-labelledby="settings-title">
            <h2 id="settings-title">{{ $heading ?? '' }}</h2>
            <p class="spark-settings-muted mt-2 leading-relaxed">{{ $subheading ?? '' }}</p>
            <div class="mt-7 max-w-xl">{{ $slot }}</div>
        </section>
    </div>
@else
<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <flux:navlist aria-label="{{ __('Tetapan') }}">
            <flux:navlist.item :href="route('profile.edit')" wire:navigate>{{ __('Profil') }}</flux:navlist.item>
            <flux:navlist.item :href="route('security.edit')" wire:navigate>{{ __('Keselamatan') }}</flux:navlist.item>
            <flux:navlist.item :href="route('appearance.edit')" wire:navigate>{{ __('Paparan') }}</flux:navlist.item>
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
@endif
