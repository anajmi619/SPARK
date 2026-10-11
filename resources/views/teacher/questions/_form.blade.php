@php
    $optionValues = [
        'a' => old('option_a', $question?->option_a),
        'b' => old('option_b', $question?->option_b),
        'c' => old('option_c', $question?->option_c),
        'd' => old('option_d', $question?->option_d),
    ];
    $correctOption = old('correct_option', $question?->correct_option?->value);
    $status = old('status', $question?->status?->value ?? 'active');
@endphp

<form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="max-w-3xl space-y-5 border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
    @csrf
    @if ($formMethod !== 'POST')
        @method($formMethod)
    @endif

    <div>
        <label for="question_text" class="mb-1 block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Soalan') }}</label>
        <textarea id="question_text" name="question_text" rows="4" required class="w-full border border-zinc-300 bg-white px-3 py-2 text-sm leading-6 text-zinc-900 outline-none focus:border-zinc-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100">{{ old('question_text', $question?->question_text) }}</textarea>
        @error('question_text')
            <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
        @enderror
    </div>

    <x-question-image-field name="image" label="Imej Soalan" :path="$question?->image_path" />

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach (['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'] as $optionKey => $optionLetter)
            <div>
                <label for="option_{{ $optionKey }}" class="mb-1 block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Pilihan :option', ['option' => $optionLetter]) }}</label>
                <input id="option_{{ $optionKey }}" name="option_{{ $optionKey }}" type="text" value="{{ $optionValues[$optionKey] }}" maxlength="255" class="w-full border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-zinc-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100" />
                <p class="my-2 text-xs text-zinc-600 dark:text-zinc-300">{{ __('Sediakan teks, imej atau kedua-duanya.') }}</p>
                <x-question-image-field :name="'option_'.$optionKey.'_image'" :label="'Imej Pilihan '.$optionLetter" :path="$question?->getAttribute('option_'.$optionKey.'_image_path')" />
                @error('option_'.$optionKey)
                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                @enderror
            </div>
        @endforeach
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="correct_option" class="mb-1 block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Jawapan Betul') }}</label>
            <select id="correct_option" name="correct_option" required class="w-full border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-zinc-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100">
                <option value="">{{ __('Pilih jawapan') }}</option>
                @foreach (['A', 'B', 'C', 'D'] as $optionLetter)
                    <option value="{{ $optionLetter }}" @selected($correctOption === $optionLetter)>{{ __('Pilihan :option', ['option' => $optionLetter]) }}</option>
                @endforeach
            </select>
            @error('correct_option')
                <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="status" class="mb-1 block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Status') }}</label>
            <select id="status" name="status" required class="w-full border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-zinc-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100">
                <option value="active" @selected($status === 'active')>{{ __('Aktif') }}</option>
                <option value="inactive" @selected($status === 'inactive')>{{ __('Tidak Aktif') }}</option>
            </select>
            @error('status')
                <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="curriculum_context" class="mb-1 block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Konteks Kurikulum (Pilihan)') }}</label>
        <textarea id="curriculum_context" name="curriculum_context" rows="4" class="w-full border border-zinc-300 bg-white px-3 py-2 text-sm leading-6 text-zinc-900 outline-none focus:border-zinc-700 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100">{{ old('curriculum_context', $question?->curriculum_context) }}</textarea>
        @error('curriculum_context')
            <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="explanation" class="mb-1 block text-sm font-medium">{{ __('Penerangan Jawapan (Pilihan)') }}</label>
        <textarea id="explanation" name="explanation" rows="3" maxlength="500" aria-describedby="explanation-hint" class="w-full border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950">{{ old('explanation', $question?->explanation) }}</textarea>
        <p id="explanation-hint" class="text-xs text-zinc-600 dark:text-zinc-300">{{ __('Terangkan secara ringkas kenapa jawapan ini betul. Maksimum 500 aksara.') }}</p>
        @error('explanation')<p class="text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
    </div>

    <div class="flex flex-wrap gap-3">
        <button type="submit" class="border border-zinc-900 bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900">{{ $submitLabel }}</button>
        <a href="{{ route('teacher.questions.index', $topic) }}" class="border border-zinc-400 px-4 py-2 text-sm font-medium text-zinc-900 dark:border-zinc-600 dark:text-zinc-100" wire:navigate>{{ __('Kembali ke Soalan Kuiz') }}</a>
    </div>
</form>
