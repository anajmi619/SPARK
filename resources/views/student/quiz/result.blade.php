<x-student-layout :title="__('Keputusan Kuiz')">
    <div class="flex w-full min-w-0 max-w-5xl flex-1 flex-col gap-6">
        <div>
            <h1>{{ __('Keputusan Kuiz') }}</h1>
            <p class="my-2 text-sm font-bold text-blue-800">Satu lagi langkah ke hadapan. Teruskan usaha!</p>
            <flux:subheading>{{ __('Kuiz: :topic', ['topic' => $attempt->topic->topic_name]) }}</flux:subheading>
        </div>

        @if ($levelUp !== null)
            <div role="status" class="flex flex-col items-center gap-3 border-l-4 border-emerald-500 bg-emerald-50 p-4 text-center text-emerald-900 sm:flex-row sm:text-left dark:bg-emerald-950 dark:text-emerald-100">
                <x-level-badge :number="$levelUp['number']" :name="$levelUp['name']" size="size-20 sm:size-24" data-test="earned-level-badge" />
                <div>
                    <p class="text-xs font-extrabold tracking-wide uppercase">{{ __('Lencana Baharu Diperoleh') }}</p>
                    <p class="mt-1 text-sm leading-6">{{ __('Tahniah, anda naik tahap! Tahap :number: :name.', ['number' => $levelUp['number'], 'name' => $levelUp['name']]) }}</p>
                </div>
            </div>
        @endif

        <dl class="spark-result-grid grid gap-6 py-3 sm:grid-cols-3">
            <div class="min-w-0">
                <dt class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Skor Percubaan Ini') }}</dt>
                <dd class="mt-2">
                    <p class="flex flex-wrap items-baseline gap-2 tabular-nums"><span class="text-3xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $attempt->score }}</span> <span class="text-sm text-zinc-500">{{ __('daripada :total', ['total' => $totalQuestionCount]) }}</span></p>
                    <p class="mt-2 text-xs text-zinc-500">{{ __('Percubaan :number', ['number' => $attempt->attempt_number]) }}</p>
                </dd>
            </div>
            <div class="min-w-0">
                <dt class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Skor Terbaik Topik') }}</dt>
                <dd class="mt-2 flex flex-wrap items-baseline gap-2 tabular-nums"><span class="text-3xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $bestTopicScore }}</span> <span class="text-sm text-zinc-500">/ 10</span></dd>
            </div>
            <div class="min-w-0">
                <dt class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Mata Topik Semasa') }}</dt>
                <dd class="mt-2 flex flex-wrap items-baseline gap-2 tabular-nums"><span class="text-3xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $bestTopicScore * \App\Actions\Student\CalculateStudentProgress::POINTS_PER_CORRECT }}</span> <span class="text-sm text-zinc-500">/ 100 {{ __('mata') }}</span></dd>
            </div>
        </dl>

        <x-student-progress :progress="$progress" class="spark-progress" />

        <div class="flex flex-wrap gap-3">
            <form method="POST" action="{{ route('student.quiz.start', $attempt->topic) }}">
                @csrf
                <button type="submit" class="spark-button"><flux:icon.arrow-path class="size-5" /> {{ __('Ulang Kuiz') }}</button>
            </form>
            <a href="{{ route('student.learning.show', $attempt->topic) }}" class="spark-button spark-secondary"><flux:icon.book-open class="size-5" /> Teruskan Pembelajaran</a>
            <a href="{{ route('student.dashboard') }}" class="spark-button spark-secondary"><flux:icon.home class="size-5" /> Papan Pemuka</a>
        </div>
    </div>
</x-student-layout>
