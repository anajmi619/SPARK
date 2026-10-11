<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="admin-main">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
