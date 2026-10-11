<x-layouts::app :title="__('Analitik Kelas')">
    <div class="flex w-full min-w-0 flex-col gap-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:heading size="xl">{{ __('Analitik Kelas') }}</flux:heading>
            <flux:button :href="route('teacher.dashboard', $filterQuery)" icon="home" wire:navigate>{{ __('Papan Pemuka') }}</flux:button>
        </div>
        <x-report-filters :action="route('teacher.analytics.index')" :academic-years="$academicYears" :year="$year" :classes="$classes" :kelas-id="$kelasId" />
        <x-report-summary :summary="$summary" />
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Prestasi Mengikut Topik') }}</h2>
        <x-report-topics :topics="$topics" :student-count="$summary['studentCount']" />
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 pt-6 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Pelajar Memerlukan Sokongan') }} ({{ $students->total() }})</h2>
            <flux:button :href="route('teacher.progress.index', $filterQuery)" icon="academic-cap" wire:navigate>{{ __('Semua Pelajar') }}</flux:button>
        </div>
        <x-report-students :students="$students" :filter-query="$filterQuery" />
    </div>
</x-layouts::app>
