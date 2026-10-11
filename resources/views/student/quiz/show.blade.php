<x-student-layout :title="__('Kuiz :topic', ['topic' => $attempt->topic->topic_name])">
    <div class="mx-auto flex max-w-3xl flex-col gap-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div><p class="mb-2 text-sm font-extrabold text-blue-800">Jom uji kefahaman!</p><h1>Kuiz: {{ $attempt->topic->topic_name }}</h1></div>
            <a href="{{ route('student.learning.show', $attempt->topic) }}" class="spark-button spark-secondary"><flux:icon.arrow-left class="size-4" /> Kembali ke Modul</a>
        </div>
        <div class="space-y-2">
            <p class="text-sm font-extrabold">{{ __('Soalan :number daripada :total', ['number' => $questionNumber, 'total' => $totalQuestionCount]) }}</p>
            <progress class="spark-meter" value="{{ $questionNumber - 1 }}" max="{{ $totalQuestionCount }}" aria-label="Soalan yang telah dijawab"></progress>
        </div>
        <form method="POST" action="{{ route('student.quiz.answer', ['attempt' => $attempt, 'question' => $question]) }}" class="spark-quiz space-y-6">
            @csrf
            <x-quiz-image :path="$question->image_path" alt="Imej soalan kuiz" class="mx-auto max-h-72 w-full object-contain" />
            <fieldset class="space-y-5">
                <legend class="mb-5 text-xl leading-8 font-extrabold break-words">{{ $question->question_text }}</legend>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach (['a', 'b', 'c', 'd'] as $option)
                        <label class="spark-option">
                            <input name="selected_option" type="radio" value="{{ strtoupper($option) }}" required @checked(old('selected_option') === strtoupper($option)) />
                            <span class="flex min-w-0 flex-1 flex-col gap-3">
                                <span class="font-extrabold">{{ strtoupper($option) }}.</span>
                                <x-quiz-image :path="$question->getAttribute('option_'.$option.'_image_path')" :alt="'Imej pilihan '.strtoupper($option)" class="h-36 w-full object-contain" />
                                <span class="text-base leading-6 break-words">{{ $question->getAttribute('option_'.$option) }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
            @error('selected_option')<p role="alert" class="text-sm text-rose-800 dark:text-rose-200">{{ $message }}</p>@enderror
            <div class="flex justify-end"><button type="submit" class="spark-button">Hantar Jawapan <flux:icon.check class="size-5" /></button></div>
        </form>
    </div>
</x-student-layout>
