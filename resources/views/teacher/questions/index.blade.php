<x-layouts::app :title="__('Urus Soalan Kuiz')">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('Urus Soalan Kuiz') }}</flux:heading>
                <flux:subheading>{{ __('Topik :number: :name', ['number' => $topic->topic_order, 'name' => $topic->topic_name]) }}</flux:subheading>
            </div>

            @if ($questions->count() < 10)
                <flux:button variant="primary" :href="route('teacher.questions.create', $topic)" icon="plus" wire:navigate>
                    {{ __('Tambah Soalan') }}
                </flux:button>
            @endif
        </div>

        @if (session('status'))
            <div class="border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex items-center justify-between border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Soalan yang ditetapkan') }}</span>
            <span class="text-zinc-600 dark:text-zinc-300">{{ __(':count daripada 10 soalan', ['count' => $questions->count()]) }}</span>
        </div>

        <div class="overflow-x-auto border border-zinc-200 dark:border-zinc-700">
            <table class="w-full min-w-[1000px] divide-y divide-zinc-200 text-left text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    <tr>
                        <th scope="col" class="w-20 px-4 py-3 font-medium">{{ __('Bil.') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Soalan') }}</th>
                        <th scope="col" class="w-36 px-4 py-3 font-medium">{{ __('Jawapan Betul') }}</th>
                        <th scope="col" class="w-32 px-4 py-3 font-medium">{{ __('Status') }}</th>
                        <th scope="col" class="w-64 px-4 py-3 font-medium">{{ __('Tindakan') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-950">
                    @forelse ($questions as $question)
                        @php
                            $correctAnswer = match ($question->correct_option->value) {
                                'A' => $question->option_a,
                                'B' => $question->option_b,
                                'C' => $question->option_c,
                                'D' => $question->option_d,
                            };
                            $statusLabel = $question->status->value === 'active' ? __('Aktif') : __('Tidak Aktif');
                        @endphp
                        <tr class="align-top">
                            <td class="px-4 py-4 font-medium text-zinc-900 dark:text-zinc-100">{{ $question->question_order }}</td>
                            <td class="px-4 py-4">
                                <p class="max-w-2xl text-zinc-900 dark:text-zinc-100">{{ $question->question_text }}</p>
                                @if ($question->curriculum_context !== null)
                                    <p class="mt-1 text-xs leading-5 text-zinc-500">{{ __('Konteks kurikulum disediakan') }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $question->correct_option->value }}.</span>
                                <span class="text-zinc-600 dark:text-zinc-300">{{ $correctAnswer }}</span>
                            </td>
                            <td class="px-4 py-4">
                                <flux:badge size="sm" :color="$question->status->value === 'active' ? 'emerald' : 'zinc'">{{ $statusLabel }}</flux:badge>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('teacher.questions.edit', ['topic' => $topic, 'question' => $question]) }}" class="inline-flex border border-zinc-900 bg-zinc-900 px-3 py-2 text-xs font-medium text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900" wire:navigate>
                                        {{ __('Kemas Kini') }}
                                    </a>

                                    @if ($question->answers_count > 0)
                                        <span class="px-1 py-2 text-xs text-zinc-500">{{ __('Mempunyai rekod jawapan') }}</span>
                                    @else
                                        <form method="POST" action="{{ route('teacher.questions.destroy', ['topic' => $topic, 'question' => $question]) }}" onsubmit="return confirm('{{ __('Padam soalan ini?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="border border-red-400 px-3 py-2 text-xs font-medium text-red-700 dark:border-red-700 dark:text-red-300">{{ __('Padam') }}</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-zinc-500">{{ __('Tiada soalan kuiz ditetapkan untuk topik ini.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            <a href="{{ route('teacher.topics.index') }}" class="inline-flex border border-zinc-400 px-4 py-2 text-sm font-medium text-zinc-900 dark:border-zinc-600 dark:text-zinc-100" wire:navigate>{{ __('Kembali ke Modul Pembelajaran') }}</a>
        </div>
    </div>
</x-layouts::app>
