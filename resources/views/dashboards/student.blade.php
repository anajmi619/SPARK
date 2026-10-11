<x-student-layout :title="__('Papan Pemuka Pelajar')">
    <div class="flex flex-col gap-8">
        <header class="flex items-center justify-between gap-4">
            <div>
                <p class="mb-2 text-sm font-extrabold text-blue-800">Papan Pemuka Pelajar</p>
                <h1>Hai, {{ auth()->user()->full_name }}!</h1>
                <p class="mt-3 text-zinc-600">Jom teroka sesuatu yang baharu hari ini.</p>
            </div>
            <x-science-friend class="hidden h-28 w-36 shrink-0 sm:block" />
        </header>
        <x-student-progress :progress="$progress" class="spark-progress" />
        <section aria-labelledby="modul-title">
            <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                <div><h2 id="modul-title">Modul Pembelajaran</h2><p class="mt-1 text-sm text-zinc-600">Sains KSSR Tahun 6</p></div>
                <span class="text-sm font-bold text-zinc-600">{{ $topics->count() }} topik tersedia</span>
            </div>
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($topics as $topic)
                    @php
                        $bestScore = $progress['topicScores'][$topic->topic_id] ?? null;
                        $topicPoints = ($bestScore ?? 0) * \App\Actions\Student\CalculateStudentProgress::POINTS_PER_CORRECT;
                        $topicIcon = ['beaker', 'light-bulb', 'bolt', 'globe-alt'][($topic->topic_order - 1) % 4];
                    @endphp
                    <article class="spark-topic">
                        <div class="flex items-center justify-between gap-3">
                            <span class="spark-topic-icon"><flux:icon :name="$topicIcon" class="size-7" /></span>
                            <span class="text-xs font-extrabold text-zinc-600">TOPIK {{ $topic->topic_order }}</span>
                        </div>
                        <h3 class="break-words">{{ $topic->topic_name }}</h3>
                        <p class="text-sm leading-6 break-words text-zinc-600">{{ \Illuminate\Support\Str::limit($topic->content_text, 110) }}</p>
                        <div class="mt-auto space-y-2">
                            <div class="flex flex-wrap justify-between gap-2 text-xs font-bold text-zinc-600">
                                <span>{{ $bestScore === null ? __('Belum Menjawab Kuiz') : __('Skor Terbaik: :score / 10', ['score' => $bestScore]) }}</span>
                                <span>{{ $topicPoints }} / 100 mata</span>
                            </div>
                            <progress class="spark-meter" value="{{ $topicPoints }}" max="100" aria-label="{{ __('Kemajuan skor terbaik: :topic', ['topic' => $topic->topic_name]) }}"></progress>
                        </div>
                        <a href="{{ route('student.learning.show', $topic) }}" class="spark-button spark-secondary">Buka Modul <flux:icon.arrow-right class="size-4" /></a>
                    </article>
                @empty
                    <p class="py-8 text-zinc-600">Tiada modul pembelajaran diterbitkan buat masa ini.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-student-layout>
