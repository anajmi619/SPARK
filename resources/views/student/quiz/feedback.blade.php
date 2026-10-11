<x-student-layout :title="__('Maklum Balas Kuiz')">
    @php($presentation = $answer->presentation_snapshot)
    <div class="mx-auto flex max-w-3xl flex-col gap-6">
        <header>
            <p class="mb-2 text-sm font-extrabold text-blue-800">Kuiz: {{ $attempt->topic->topic_name }}</p>
            <h1>{{ $answer->is_correct ? 'Hebat, jawapan anda betul!' : 'Usaha yang baik. Jom semak bersama!' }}</h1>
        </header>
        <section class="spark-quiz space-y-6" aria-label="Maklum balas jawapan">
            <x-quiz-image :path="$presentation['image'] ?? null" alt="Imej soalan kuiz" class="mx-auto max-h-72 w-full object-contain" />
            <h2 class="break-words">{{ $answer->question_snapshot }}</h2>
            @if ($presentation !== null)
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($presentation['options'] as $letter => $option)
                        <div class="spark-option spark-option-evaluated {{ $letter === $presentation['correct'] ? 'spark-correct' : ($letter === $answer->selected_option->value ? 'spark-selected' : '') }}">
                            <div class="flex w-full flex-col gap-3">
                                <div class="flex flex-wrap items-center justify-between gap-2 text-sm font-extrabold">
                                    <span>{{ $letter }}.</span>
                                    @if ($letter === $presentation['correct'])<span class="text-green-900 dark:text-green-200">Jawapan Betul</span>@endif
                                    @if ($letter === $answer->selected_option->value)<span class="text-zinc-700">Jawapan Anda</span>@endif
                                </div>
                                <x-quiz-image :path="$option['image']" :alt="'Imej pilihan '.$letter" class="h-36 w-full object-contain" />
                                <p class="break-words">{{ $option['text'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="space-y-3 border-l-4 border-emerald-500 bg-emerald-50 p-4 dark:bg-emerald-950">
                    <p>Jawapan Anda: {{ $answer->selected_option->value }}. {{ $answer->selected_answer_snapshot }}</p>
                    <p class="font-bold">Jawapan Betul: {{ $answer->correct_answer_snapshot }}</p>
                </div>
            @endif
            @if ($answer->explanation_snapshot)
                <div class="border-l-4 border-sky-400 bg-sky-50 p-4 dark:bg-sky-950">
                    <h3>Penerangan Guru</h3>
                    <p class="mt-2 text-sm leading-7 whitespace-pre-line break-words">{{ $answer->explanation_snapshot }}</p>
                </div>
            @endif
            @unless ($answer->is_correct)
                <div class="border-l-4 border-amber-400 bg-amber-50 p-4 dark:bg-amber-950">
                    <h3>Maklum Balas AI</h3>
                    <p class="mt-2 text-sm leading-7 whitespace-pre-line">{{ filled($answer->ai_feedback_text) ? $answer->ai_feedback_text : 'Maklum balas AI tidak dapat dijana buat masa ini. Sila rujuk jawapan betul dan baca semula kandungan modul.' }}</p>
                </div>
            @endunless
            <div class="flex flex-wrap items-center justify-between gap-4 border-t border-zinc-200 pt-5 dark:border-zinc-700">
                <span class="flex items-center gap-2 text-xs font-bold text-zinc-600"><flux:icon.lock-closed class="size-4" /> Jawapan telah disimpan</span>
                <a href="{{ $hasRemainingQuestion ? route('student.quiz.show', $attempt) : route('student.quiz.result', $attempt) }}" class="spark-button">{{ $hasRemainingQuestion ? 'Seterusnya' : 'Lihat Keputusan' }} <flux:icon.arrow-right class="size-4" /></a>
            </div>
        </section>
    </div>
</x-student-layout>
