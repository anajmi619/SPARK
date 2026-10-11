@if (auth()->user()->role === \App\Enums\UserRole::Student)
    <header class="mb-8 flex items-center justify-between gap-4">
        <div>
            <p class="spark-settings-eyebrow mb-2 text-sm font-extrabold">Akaun Saya</p>
            <h1>Tetapan</h1>
            <p class="spark-settings-muted mt-3">Urus profil dan tetapan akaun anda.</p>
        </div>
        <x-science-friend class="hidden h-28 w-36 shrink-0 sm:block" />
    </header>
@else
<div class="relative mb-6 w-full">
    <flux:heading size="xl" level="1">{{ __('Tetapan') }}</flux:heading>
    <flux:subheading size="lg" class="mb-6">{{ __('Urus profil dan tetapan akaun anda') }}</flux:subheading>
    <flux:separator variant="subtle" />
</div>
@endif
