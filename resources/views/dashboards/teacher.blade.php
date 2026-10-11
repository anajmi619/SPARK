<x-layouts::app :title="__('Papan Pemuka Guru')">
    <div class="flex h-full w-full min-w-0 flex-1 flex-col gap-6">
        <div class="admin-welcome">
            <div>
                <p class="admin-eyebrow">{{ __('Selamat datang, :name', ['name' => auth()->user()->full_name]) }}</p>
                <flux:heading size="xl" level="1">{{ __('Papan Pemuka Guru') }}</flux:heading>
            </div>
            @if ($year)
                <div class="admin-year"><flux:icon.calendar-days class="size-6" /><span>{{ __('Tahun Akademik') }}<strong>{{ $year->year }}</strong></span></div>
            @endif
        </div>
        <x-report-filters :action="route('teacher.dashboard')" :academic-years="$academicYears" :year="$year" :classes="$classes" :kelas-id="$kelasId" />
        <x-report-summary :summary="$summary" />
        @if ($aiUsage !== null)
            <section class="admin-ai" aria-labelledby="ai-usage-heading">
                <div class="admin-ai-summary">
                    <h2 id="ai-usage-heading" class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Penggunaan Maklum Balas AI Hari Ini') }}</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Maklum balas daripada cache: :cached', ['cached' => $aiUsage['cacheHits']]) }}</p>
                </div>
                <div class="admin-ai-usage">
                    <p class="mb-2 text-sm tabular-nums">{{ __('Panggilan Gemini: :used daripada :limit had harian.', ['used' => $aiUsage['apiCalls'], 'limit' => $aiUsage['dailyLimit']]) }}</p>
                    <flux:progress :value="min($aiUsage['apiCalls'], $aiUsage['dailyLimit'])" :max="max(1, $aiUsage['dailyLimit'])" color="sky" aria-label="{{ __('Penggunaan harian Gemini') }}" />
                </div>
            </section>
        @endif
        <div class="flex flex-wrap gap-3">
            <flux:button variant="primary" :href="route('teacher.progress.index', $filterQuery)" icon="academic-cap" wire:navigate>{{ __('Pantau Kemajuan') }}</flux:button>
            <flux:button :href="route('teacher.analytics.index', $filterQuery)" icon="chart-bar" wire:navigate>{{ __('Analitik Kelas') }}</flux:button>
            @if (auth()->user()->isAdmin())
                <flux:button :href="route('teacher.users.index')" icon="users" wire:navigate>
                    {{ __('Urus Pengguna') }}
                </flux:button>
            @endif
            <flux:button :href="route('teacher.topics.index')" icon="book-open" wire:navigate>
                {{ __('Urus Modul Pembelajaran') }}
            </flux:button>
        </div>
        <div class="admin-section-title">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Prestasi Mengikut Topik') }}</h2>
            <span class="text-xs text-zinc-600 dark:text-zinc-300">{{ __('Ambang sokongan: skor bawah 60%') }}</span>
        </div>
        <x-report-topics :topics="$topics" :student-count="$summary['studentCount']" />
    </div>
</x-layouts::app>
