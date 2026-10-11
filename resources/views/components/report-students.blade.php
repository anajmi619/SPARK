@props(['students', 'filterQuery'])

<div class="min-w-0 overflow-x-auto" tabindex="0" role="region" aria-label="{{ __('Kemajuan Pelajar') }}">
    <table class="w-full min-w-[850px] text-left text-sm">
        <thead class="border-y border-zinc-200 bg-zinc-50 text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
            <tr>
                @foreach ([__('Pelajar'), __('Kelas'), __('Purata Skor Terbaik'), __('Kuiz Topik Selesai'), __('Mata'), __('Topik Lemah'), __('Laporan')] as $heading)
                    <th scope="col" class="px-4 py-3 font-medium">{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @forelse ($students as $student)
                <tr class="text-zinc-700 dark:text-zinc-300">
                    <th scope="row" class="max-w-64 px-4 py-4 text-left font-medium break-words text-zinc-900 dark:text-zinc-100">
                        {{ $student->full_name }}
                        <span class="mt-1 block text-xs font-normal text-zinc-500">{{ $student->username }}{{ $student->status === 'inactive' ? ' / Tidak Aktif' : '' }}</span>
                    </th>
                    <td class="px-4 py-4">{{ $student->kelas_nama ?? '-' }}</td>
                    <td class="px-4 py-4 tabular-nums">{{ $student->average_score === null ? __('Tiada Keputusan') : number_format((float) $student->average_score, 1).'%' }}</td>
                    <td class="px-4 py-4 tabular-nums">{{ $student->completed_topics }} / 12 <span class="block text-xs text-zinc-500">{{ number_format((int) $student->completed_topics / \App\Actions\Student\CalculateStudentProgress::TOTAL_TOPICS * 100, 1) }}%</span></td>
                    <td class="px-4 py-4 tabular-nums">{{ number_format((int) $student->total_points) }}</td>
                    <td class="px-4 py-4 tabular-nums {{ $student->weak_topics > 0 ? 'font-semibold text-red-700 dark:text-red-300' : '' }}">{{ $student->weak_topics }}</td>
                    <td class="px-4 py-4"><flux:button size="sm" icon="document-text" :href="route('teacher.progress.show', ['student' => $student->user_id] + $filterQuery + request()->only('search', 'support_only', 'page'))" aria-label="{{ __('Laporan :name', ['name' => $student->full_name]) }}" wire:navigate>{{ __('Lihat') }}</flux:button></td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-zinc-500">{{ __('Tiada pelajar sepadan dengan penapis ini.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if ($students->hasPages())
    <nav class="flex flex-wrap items-center justify-between gap-3 pt-4" aria-label="{{ __('Halaman laporan') }}">
        <flux:button :href="$students->previousPageUrl()" :disabled="$students->onFirstPage()" icon="chevron-left">{{ __('Sebelumnya') }}</flux:button>
        <span class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Halaman :current daripada :last', ['current' => $students->currentPage(), 'last' => $students->lastPage()]) }}</span>
        <flux:button :href="$students->nextPageUrl()" :disabled="! $students->hasMorePages()" icon-trailing="chevron-right">{{ __('Seterusnya') }}</flux:button>
    </nav>
@endif
