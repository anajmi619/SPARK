<x-layouts::app :title="__('Urus Modul Pembelajaran')">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('Urus Modul Pembelajaran') }}</flux:heading>
                <flux:subheading>{{ __('Kandungan Sains KSSR Tahun 6 mengikut topik.') }}</flux:subheading>
            </div>

            @if ($topics->count() < 12)
                <flux:button variant="primary" :href="route('teacher.topics.create')" icon="plus" wire:navigate>
                    {{ __('Tambah Modul') }}
                </flux:button>
            @endif
        </div>

        @if (session('status'))
            <div class="border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100">
                {{ session('status') }}
            </div>
        @endif

        <div class="overflow-x-auto border border-zinc-200 dark:border-zinc-700">
            <table class="w-full min-w-[1000px] divide-y divide-zinc-200 text-left text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    <tr>
                        <th scope="col" class="w-20 px-4 py-3 font-medium">{{ __('Topik') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Kandungan Pembelajaran') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Media') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Tindakan') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-950">
                    @forelse ($topics as $topic)
                        @php
                            $imageUrl = $topic->image_path === null ? null : \Illuminate\Support\Facades\Storage::disk('public')->url($topic->image_path);
                            $videoUrl = $topic->youtubeEmbedUrl();
                            $statusLabel = match ($topic->status->value) {
                                'draft' => __('Draf'),
                                'published' => __('Diterbitkan'),
                                'archived' => __('Diarkibkan'),
                            };
                        @endphp
                        <tr class="align-top">
                            <td class="px-4 py-4 font-medium text-zinc-900 dark:text-zinc-100">{{ $topic->topic_order }}</td>
                            <td class="px-4 py-4">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $topic->topic_name }}</div>
                                @if ($topic->content_text !== null)
                                    <p class="mt-1 max-w-2xl text-xs leading-5 text-zinc-500">{{ \Illuminate\Support\Str::limit($topic->content_text, 140) }}</p>
                                @elseif ($topic->pdf_path === null && $videoUrl === null && $imageUrl === null)
                                    <p class="mt-1 text-xs text-zinc-500">{{ __('Tiada kandungan pembelajaran.') }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-2">
                                    @if ($topic->pdf_path !== null)
                                        <flux:button size="sm" :href="route('topics.pdf', $topic)" target="_blank" rel="noopener noreferrer" icon="document-text">PDF</flux:button>
                                    @elseif ($imageUrl !== null)
                                        <a href="{{ $imageUrl }}" target="_blank" rel="noreferrer" class="border border-zinc-400 px-2 py-1 text-xs font-medium text-zinc-900 dark:border-zinc-600 dark:text-zinc-100">{{ __('Imej') }}</a>
                                    @endif
                                    @if ($videoUrl !== null)
                                        <a href="{{ $videoUrl }}" target="_blank" rel="noreferrer" class="border border-zinc-400 px-2 py-1 text-xs font-medium text-zinc-900 dark:border-zinc-600 dark:text-zinc-100">{{ __('Video') }}</a>
                                    @endif
                                    @if ($topic->pdf_path === null && $imageUrl === null && $videoUrl === null)
                                        <span class="text-xs text-zinc-500">{{ __('Tiada media') }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <flux:badge size="sm" :color="match ($topic->status->value) { 'published' => 'emerald', 'draft' => 'amber', default => 'zinc' }">{{ $statusLabel }}</flux:badge>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <flux:button size="sm" :href="route('teacher.topics.edit', $topic)" icon="pencil-square" wire:navigate>
                                        {{ __('Urus Kandungan') }}
                                    </flux:button>

                                    <flux:button size="sm" :href="route('teacher.questions.index', $topic)" icon="question-mark-circle" wire:navigate>
                                        {{ __('Urus Soalan Kuiz') }}
                                    </flux:button>

                                    @if ($topic->questions_count === 0 && $topic->quiz_attempts_count === 0)
                                        <form method="POST" action="{{ route('teacher.topics.destroy', $topic) }}" onsubmit="return confirm('{{ __('Padam modul ini?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="border border-red-400 px-3 py-2 text-xs font-medium text-red-700 dark:border-red-700 dark:text-red-300">{{ __('Padam Modul') }}</button>
                                        </form>
                                    @else
                                        <span class="px-1 py-2 text-xs text-zinc-500">{{ __('Mempunyai rekod kuiz') }}</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-zinc-500">{{ __('Tiada topik ditetapkan.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts::app>
