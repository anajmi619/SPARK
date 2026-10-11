@props(['topics', 'studentCount'])

<div class="min-w-0 overflow-x-auto" tabindex="0" role="region" aria-label="{{ __('Prestasi Mengikut Topik') }}">
    <table class="w-full min-w-[720px] text-left text-sm">
        <thead class="border-y border-zinc-200 bg-zinc-50 text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
            <tr>
                <th scope="col" class="w-1/4 px-4 py-3 font-medium">{{ __('Topik') }}</th>
                <th scope="col" class="w-1/4 px-4 py-3 font-medium">{{ __('Purata Skor Terbaik') }}</th>
                <th scope="col" class="w-1/4 px-4 py-3 font-medium">{{ __('Pelajar Selesai Kuiz') }}</th>
                <th scope="col" class="px-4 py-3 font-medium">{{ __('Skor Bawah 60%') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @forelse ($topics as $topic)
                @php
                    $weak = $topic->average_score !== null && (float) $topic->average_score < \App\Actions\Teacher\BuildProgressReports::SUPPORT_THRESHOLD;
                    $completion = $studentCount > 0 ? round((int) $topic->completed_students / $studentCount * 100, 1) : 0;
                @endphp
                <tr class="align-top">
                    <th scope="row" class="px-4 py-4 font-medium break-words text-zinc-900 dark:text-zinc-100">
                        {{ $topic->topic_order }}. {{ $topic->topic_name }}
                        @if ($topic->status !== 'published')
                            <span class="mt-1 block text-xs font-normal text-zinc-500">{{ $topic->status === 'archived' ? __('Diarkibkan') : __('Draf') }}</span>
                        @endif
                    </th>
                    <td class="px-4 py-4">
                        @if ($topic->average_score === null)
                            <span class="text-zinc-500">{{ __('Tiada Keputusan') }}</span>
                        @else
                            <div class="mb-2 flex flex-wrap gap-2 text-zinc-800 tabular-nums dark:text-zinc-200">
                                <span>{{ number_format((float) $topic->average_score, 1) }}%</span>
                                @if ($weak)<flux:badge size="sm" color="amber">{{ __('Topik Lemah') }}</flux:badge>@endif
                            </div>
                            <flux:progress :value="(float) $topic->average_score" max="100" :color="$weak ? 'amber' : 'sky'" aria-label="{{ __('Purata skor :topic', ['topic' => $topic->topic_name]) }}" />
                        @endif
                    </td>
                    <td class="px-4 py-4 text-zinc-700 tabular-nums dark:text-zinc-300">
                        <p class="mb-2">{{ $topic->completed_students }} / {{ $studentCount }} <span class="text-xs">({{ number_format($completion, 1) }}%)</span></p>
                        <flux:progress :value="$completion" max="100" color="emerald" aria-label="{{ __('Pelajar selesai kuiz :topic', ['topic' => $topic->topic_name]) }}" />
                    </td>
                    <td class="px-4 py-4 text-zinc-700 tabular-nums dark:text-zinc-300">{{ $topic->weak_students }} {{ __('pelajar') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-zinc-500">{{ __('Tiada topik tersedia.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
