<?php

namespace App\Concerns;

use App\Enums\AnswerOption;
use App\Enums\QuestionStatus;
use App\Models\QuizQuestion;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

trait QuizQuestionValidationRules
{
    /**
     * Normalize text inputs before question validation.
     */
    protected function prepareQuizQuestionForValidation(): void
    {
        foreach (['question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'curriculum_context', 'explanation'] as $field) {
            $value = $this->input($field);
            if (is_string($value)) {
                $value = trim($value);
                $this->merge([$field => $value === '' ? null : $value]);
            }
        }
    }

    /**
     * Get validation rules shared by the question create and update forms.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function quizQuestionRules(): array
    {
        $rules = [
            'question_text' => ['required', 'string', 'max:5000'],
            'explanation' => ['nullable', 'string', 'max:500'],
            'correct_option' => ['required', Rule::enum(AnswerOption::class)],
            'curriculum_context' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(QuestionStatus::class)],
        ];
        $question = $this->route('question');
        $imageMaxSizeKilobytes = (int) config('quiz.image_max_size_kilobytes', 10 * 1024);
        foreach (QuizQuestion::imageFields() as $field) {
            $rules[$field] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'dimensions:min_width=1,min_height=1', 'max:'.$imageMaxSizeKilobytes];
            $rules['remove_'.$field] = ['nullable', 'boolean'];
        }
        foreach (['a', 'b', 'c', 'd'] as $option) {
            $field = 'option_'.$option.'_image';
            $hasImage = $this->hasFile($field) || ($question instanceof QuizQuestion
                && $question->getAttribute($field.'_path') !== null && ! $this->boolean('remove_'.$field));
            $rules['option_'.$option] = [Rule::requiredIf(! $hasImage), 'nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    /** @return array<string, UploadedFile> */
    public function imageUploads(): array
    {
        $files = [];
        foreach (QuizQuestion::imageFields() as $field) {
            $file = $this->file($field);
            if ($file instanceof UploadedFile) {
                $files[$field] = $file;
            }
        }

        return $files;
    }

    /** @return list<string> */
    public function imageRemovals(): array
    {
        return array_values(array_filter(QuizQuestion::imageFields(), fn (string $field): bool => $this->boolean('remove_'.$field)));
    }

    public function explanation(): ?string
    {
        return $this->filled('explanation') ? $this->string('explanation')->toString() : null;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $imageMaxSizeMegabytes = (int) (config('quiz.image_max_size_kilobytes', 10 * 1024) / 1024);
        $messages = [
            'question_text.required' => 'Sila masukkan teks soalan.',
            'explanation.max' => 'Penerangan tidak boleh melebihi 500 aksara.',
            'correct_option.required' => 'Sila pilih jawapan yang betul.',
        ];
        foreach (QuizQuestion::imageFields() as $field) {
            foreach (['image', 'mimes', 'extensions', 'dimensions'] as $rule) {
                $messages[$field.'.'.$rule] = 'Sila pilih imej JPG, JPEG, PNG atau WEBP yang sah.';
            }
            $messages[$field.'.max'] = 'Saiz imej tidak boleh melebihi '.$imageMaxSizeMegabytes.' MB.';
            $messages[$field.'.uploaded'] = 'Muat naik imej gagal. Sila cuba lagi.';
        }
        foreach (['a', 'b', 'c', 'd'] as $option) {
            $messages['option_'.$option.'.required'] = 'Sediakan teks atau imej bagi setiap pilihan jawapan.';
        }

        return $messages;
    }

    /**
     * Return the four validated answer options indexed by their option letter.
     *
     * @return array{a: string, b: string, c: string, d: string}
     */
    public function answerOptions(): array
    {
        return [
            'a' => $this->string('option_a')->toString(),
            'b' => $this->string('option_b')->toString(),
            'c' => $this->string('option_c')->toString(),
            'd' => $this->string('option_d')->toString(),
        ];
    }

    /**
     * Return the validated question text.
     */
    public function questionText(): string
    {
        return $this->string('question_text')->toString();
    }

    /**
     * Return the option that is correct for this question.
     */
    public function correctOption(): AnswerOption
    {
        return AnswerOption::from($this->string('correct_option')->toString());
    }

    /**
     * Return optional curriculum context for future AI feedback generation.
     */
    public function curriculumContext(): ?string
    {
        if ($this->input('curriculum_context') === null) {
            return null;
        }

        return $this->string('curriculum_context')->toString();
    }

    /**
     * Return the question availability status.
     */
    public function questionStatus(): QuestionStatus
    {
        return QuestionStatus::from($this->string('status')->toString());
    }
}
