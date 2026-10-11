<?php

namespace App\Concerns;

use App\Enums\TopicStatus;
use App\Models\Topic;
use App\Rules\YouTubeLink;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

trait TopicContentValidationRules
{
    /**
     * Normalize optional learning text before validation.
     */
    protected function prepareTopicContentForValidation(): void
    {
        foreach (['content_text', 'youtube_url'] as $field) {
            $value = $this->input($field);
            if (is_string($value)) {
                $value = trim($value);
                $this->merge([$field => $value === '' ? null : $value]);
            }
        }
    }

    /**
     * Get shared rules for a topic's learning materials.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function topicContentRules(): array
    {
        $topic = $this->route('topic');
        $hasPdf = $this->hasFile('pdf_file') || ($topic instanceof Topic && $topic->pdf_path !== null && ! $this->boolean('remove_pdf'));
        $hasVideo = $this->filled('youtube_url');

        return [
            'content_text' => [
                Rule::requiredIf($this->input('status') === TopicStatus::Published->value && ! $hasPdf && ! $hasVideo),
                'nullable',
                'string',
                'max:20000',
            ],
            'pdf_file' => ['nullable', 'file', 'mimes:pdf', 'extensions:pdf', 'max:51200'],
            'image_file' => ['prohibited'],
            'youtube_url' => ['nullable', 'string', 'max:500', new YouTubeLink],
            'remove_pdf' => ['nullable', 'boolean'],
            'status' => ['required', Rule::enum(TopicStatus::class)],
        ];
    }

    /**
     * Return the validated learning text as a nullable string.
     */
    public function contentText(): ?string
    {
        if ($this->input('content_text') === null) {
            return null;
        }

        return $this->string('content_text')->toString();
    }

    /**
     * Return the uploaded PDF, when one was supplied.
     */
    public function pdfFile(): ?UploadedFile
    {
        $file = $this->file('pdf_file');

        return $file instanceof UploadedFile ? $file : null;
    }

    /**
     * Return the optional YouTube link.
     */
    public function youtubeUrl(): ?string
    {
        return $this->filled('youtube_url') ? $this->string('youtube_url')->toString() : null;
    }

    /**
     * Return the validated topic publication status.
     */
    public function topicStatus(): TopicStatus
    {
        return TopicStatus::from($this->string('status')->toString());
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'youtube_url.string' => 'Sila masukkan satu pautan YouTube yang sah.',
            'youtube_url.max' => 'Pautan YouTube tidak boleh melebihi 500 aksara.',
            'content_text.required' => 'Sediakan teks, fail PDF atau video sebelum menerbitkan modul.',
            'pdf_file.file' => 'Sila pilih fail PDF yang sah.',
            'pdf_file.mimes' => 'Fail pembelajaran mestilah dalam format PDF.',
            'pdf_file.extensions' => 'Fail pembelajaran mesti menggunakan sambungan .pdf.',
            'pdf_file.max' => 'Saiz fail PDF tidak boleh melebihi 50 MB.',
            'pdf_file.uploaded' => 'Muat naik PDF gagal. Sila semak saiz fail dan cuba lagi.',
            'image_file.prohibited' => 'Muat naik imej telah digantikan dengan fail PDF.',
        ];
    }
}
