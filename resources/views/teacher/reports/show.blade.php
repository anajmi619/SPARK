<x-layouts::app :title="__('Laporan Kemajuan Pelajar')">
    <div class="flex w-full min-w-0 flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <flux:heading size="xl">{{ __('Laporan Kemajuan Pelajar') }}</flux:heading>
                <p class="mt-2 text-base font-medium break-words text-zinc-900 dark:text-zinc-100">{{ $student->full_name }}</p>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $student->username }} / {{ $student->kelas?->nama }} / {{ $year?->year }}</p>
            </div>
            <flux:button :href="route('teacher.progress.index', $filterQuery + request()->only('search', 'support_only', 'page'))" icon="arrow-left" wire:navigate>{{ __('Kembali ke Senarai') }}</flux:button>
        </div>
        @if ($year?->status === \App\Enums\AcademicYearStatus::Closed)
            <p class="border-l-4 border-amber-500 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-100">{{ __('Rekod Tahun :year - Baca Sahaja', ['year' => $year->year]) }}</p>
        @endif
        <dl class="grid gap-5 sm:grid-cols-3">
            <div><dt class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Purata Skor Terbaik') }}</dt><dd class="mt-2 text-xl font-semibold">{{ $summary['averageScore'] === null ? __('Tiada Keputusan') : number_format($summary['averageScore'], 1).'%' }}</dd></div>
            <div><dt class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Kuiz Topik Selesai (12 Topik)') }}</dt><dd class="mt-2 text-xl font-semibold">{{ number_format($summary['completion'], 1) }}%</dd></div>
            <div><dt class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Topik Lemah') }}</dt><dd class="mt-2 text-xl font-semibold">{{ $topics->where('weak_students', '>', 0)->count() }}</dd></div>
        </dl>
        <x-student-progress :progress="$progress" />
        <h2 class="text-lg font-semibold">{{ __('Keputusan Mengikut Topik') }}</h2>
        <div class="min-w-0 overflow-x-auto" tabindex="0" role="region" aria-label="{{ __('Keputusan Mengikut Topik') }}">
            <table class="w-full min-w-[780px] text-left text-sm">
                <thead class="border-y border-zinc-200 bg-zinc-50 text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"><tr>
                    @foreach ([__('Topik'), __('Skor Terbaik'), __('Mata'), __('Percubaan Selesai'), __('Status'), __('Kuiz Terakhir Selesai')] as $heading)
                        <th scope="col" class="px-4 py-3 font-medium">{{ $heading }}</th>
                    @endforeach
                </tr></thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($topics as $topic)
                        <tr class="text-zinc-700 dark:text-zinc-300">
                            <th scope="row" class="max-w-64 px-4 py-4 font-medium break-words">{{ $topic->topic_name }} @if ($topic->status === 'archived')<span class="block text-xs text-zinc-500">{{ __('Diarkibkan') }}</span>@endif</th>
                            <td class="px-4 py-4 tabular-nums">{{ isset($progress['topicScores'][$topic->topic_id]) ? $progress['topicScores'][$topic->topic_id].' / 10' : __('Tiada Keputusan') }}</td>
                            <td class="px-4 py-4 tabular-nums">{{ ($progress['topicScores'][$topic->topic_id] ?? 0) * \App\Actions\Student\CalculateStudentProgress::POINTS_PER_CORRECT }} / 100</td>
                            <td class="px-4 py-4 tabular-nums">{{ $topic->attempt_count }}</td>
                            <td class="px-4 py-4 {{ $topic->weak_students > 0 ? 'font-medium text-red-700 dark:text-red-300' : '' }}">{{ $topic->average_score === null ? __('Belum Selesai') : ($topic->weak_students > 0 ? __('Perlu Sokongan') : __('Selesai')) }}</td>
                            <td class="px-4 py-4 tabular-nums">{{ $topic->last_completed_at === null ? '-' : \Carbon\CarbonImmutable::parse($topic->last_completed_at, 'UTC')->setTimezone('Asia/Kuala_Lumpur')->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-zinc-500">{{ __('Tiada topik tersedia.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts::app>
