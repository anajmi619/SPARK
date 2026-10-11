@props(['progress'])

@php
    $level = $progress['currentLevel'];
    $nextLevel = $progress['nextLevel'];
@endphp

<section {{ $attributes->class('grid min-w-0 gap-6 border-y border-zinc-200 py-6 md:grid-cols-[minmax(0,1fr)_minmax(0,2fr)] dark:border-zinc-700') }} aria-label="{{ __('Mata dan Kemajuan Tahap') }}">
    <div class="flex min-w-0 flex-col gap-2">
        <h2 class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Jumlah Mata Semasa') }}</h2>
        <p class="flex flex-wrap items-baseline gap-2 tabular-nums">
            <span class="text-3xl font-semibold text-zinc-900 dark:text-zinc-100" data-test="total-points">{{ number_format($progress['totalPoints']) }}</span>
            <span class="text-sm text-zinc-500">/ {{ number_format(\App\Actions\Student\CalculateStudentProgress::MAX_POINTS) }} {{ __('mata') }}</span>
        </p>
        <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Topik dengan kuiz selesai: :count / :total', ['count' => $progress['completedTopics'], 'total' => \App\Actions\Student\CalculateStudentProgress::TOTAL_TOPICS]) }}</p>
    </div>

    <div class="flex min-w-0 flex-col gap-4">
        <div class="flex min-w-0 items-center gap-4 sm:gap-6">
            <x-level-badge :number="$level->level_number" :name="$level->level_name" size="size-24 sm:size-28" data-test="level-badge" />
            <div class="min-w-0">
                <h2 class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Lencana Semasa') }} <span aria-hidden="true">/</span> {{ __('Tahap :number', ['number' => $level->level_number]) }}</h2>
                <p class="mt-1 text-lg font-semibold break-words text-zinc-900 dark:text-zinc-100" data-test="current-level">{{ $level->level_name }}</p>
                <p class="mt-1 text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Terus kumpul mata untuk meningkatkan lencana anda.') }}</p>
            </div>
        </div>

        @if ($nextLevel !== null)
            <div class="flex flex-wrap justify-between gap-2 text-xs text-zinc-600 dark:text-zinc-300">
                <span>{{ __('Kemajuan Tahap') }}</span>
                <span class="tabular-nums">{{ number_format($progress['totalPoints'] - $level->minimum_points) }} / {{ number_format($nextLevel->minimum_points - $level->minimum_points) }} {{ __('mata') }}</span>
            </div>
            <flux:progress :value="$progress['levelProgress']" max="100" color="emerald" aria-label="{{ __('Kemajuan ke tahap seterusnya') }}" />
            <p class="text-sm leading-6 text-zinc-700 dark:text-zinc-300" data-test="next-level">
                {{ __(':points mata lagi untuk mencapai Tahap :number: :name.', ['points' => number_format($progress['pointsToNextLevel']), 'number' => $nextLevel->level_number, 'name' => $nextLevel->level_name]) }}
            </p>
        @else
            <flux:progress value="100" max="100" color="emerald" aria-label="{{ __('Tahap tertinggi dicapai') }}" />
            <p class="text-sm font-medium text-emerald-800 dark:text-emerald-200">{{ __('Tahniah! Tahap tertinggi telah dicapai.') }}</p>
            @if ($progress['totalPoints'] < \App\Actions\Student\CalculateStudentProgress::MAX_POINTS)
                <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __(':points mata lagi untuk mencapai 1,200 mata.', ['points' => \App\Actions\Student\CalculateStudentProgress::MAX_POINTS - $progress['totalPoints']]) }}</p>
            @else
                <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Mata maksimum telah dicapai.') }}</p>
            @endif
        @endif
    </div>
</section>
