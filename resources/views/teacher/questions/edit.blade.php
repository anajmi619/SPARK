<x-layouts::app :title="__('Kemas Kini Soalan Kuiz')">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl">{{ __('Kemas Kini Soalan Kuiz') }}</flux:heading>
            <flux:subheading>{{ __('Topik :number: :name, Soalan :question', ['number' => $topic->topic_order, 'name' => $topic->topic_name, 'question' => $question->question_order]) }}</flux:subheading>
        </div>

        @include('teacher.questions._form', [
            'question' => $question,
            'formAction' => route('teacher.questions.update', ['topic' => $topic, 'question' => $question]),
            'formMethod' => 'PATCH',
            'submitLabel' => __('Simpan Perubahan'),
        ])
    </div>
</x-layouts::app>
