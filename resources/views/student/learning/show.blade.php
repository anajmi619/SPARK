<x-student-layout :title="$topic->topic_name">
    <div class="flex w-full min-w-0 flex-1 flex-col gap-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <span class="inline-flex border border-zinc-300 px-2 py-1 text-xs font-medium text-zinc-700 dark:border-zinc-600 dark:text-zinc-200">{{ __('Topik :number', ['number' => $topic->topic_order]) }}</span>
                <h1 class="mt-3">{{ $topic->topic_name }}</h1>
            </div>

            <a href="{{ route('student.dashboard') }}" class="spark-button spark-secondary"><flux:icon.arrow-left class="size-4" /> {{ __('Kembali ke Papan Pemuka') }}</a>
        </div>

        @if ($topic->content_text !== null)
            <article class="max-w-4xl">
                <div class="whitespace-pre-line break-words text-sm leading-7 text-zinc-800 dark:text-zinc-200">{{ $topic->content_text }}</div>
            </article>
        @endif

        @if ($pdfUrl !== null)
            <x-pdf-preview class="max-w-5xl" :url="$pdfUrl" :download-url="route('topics.pdf', ['topic' => $topic, 'download' => 1])" :title="$topic->topic_name" />
        @endif

        @if ($imageUrl !== null)
            <figure class="max-w-4xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <img src="{{ $imageUrl }}" alt="{{ __('Imej pembelajaran untuk :topic', ['topic' => $topic->topic_name]) }}" class="max-h-[34rem] w-full object-contain" />
            </figure>
        @endif

        @if ($videoUrl !== null)
            <section class="max-w-4xl py-4">
                <h2 class="mb-3 text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Video Pembelajaran') }}</h2>
                <iframe src="{{ $videoUrl }}" title="{{ __('Video pembelajaran: :topic', ['topic' => $topic->topic_name]) }}" class="aspect-video w-full rounded-lg border border-zinc-300" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            </section>
        @else
            <div class="flex items-center gap-3 border-y border-sky-200 py-6 text-sm font-bold text-zinc-600"><flux:icon.play-circle class="size-7 text-blue-700" /> Video belum tersedia</div>
        @endif

        @if ($activeQuestionCount > 0)
            <form method="POST" action="{{ route('student.quiz.start', $topic) }}">
                @csrf
                <button type="submit" class="spark-button"><flux:icon.bolt class="size-5" /> {{ __('Mula Kuiz') }}</button>
            </form>
        @endif
    </div>
</x-student-layout>
