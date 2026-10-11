<x-layouts::app :title="__('Tambah Soalan Kuiz')">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl">{{ __('Tambah Soalan Kuiz') }}</flux:heading>
            <flux:subheading>{{ __('Topik :number: :name', ['number' => $topic->topic_order, 'name' => $topic->topic_name]) }}</flux:subheading>
        </div>

        @include('teacher.questions._form', [
            'question' => null,
            'formAction' => route('teacher.questions.store', $topic),
            'formMethod' => 'POST',
            'submitLabel' => __('Tambah Soalan'),
        ])
    </div>
</x-layouts::app>
