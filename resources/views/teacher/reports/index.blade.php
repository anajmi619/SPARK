<x-layouts::app :title="__('Pantau Kemajuan')">
    <div class="flex w-full min-w-0 flex-col gap-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:heading size="xl">{{ __('Pantau Kemajuan') }}</flux:heading>
            <flux:button :href="route('teacher.analytics.index', $filterQuery)" icon="chart-bar" wire:navigate>{{ __('Analitik Kelas') }}</flux:button>
        </div>
        <x-report-filters :action="route('teacher.progress.index')" :academic-years="$academicYears" :year="$year" :classes="$classes" :kelas-id="$kelasId" searchable />
        <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __(':count pelajar', ['count' => $students->total()]) }}</p>
        <x-report-students :students="$students" :filter-query="$filterQuery" />
    </div>
</x-layouts::app>
