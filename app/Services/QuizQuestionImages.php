<?php

namespace App\Services;

use App\Models\QuizQuestion;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class QuizQuestionImages
{
    /**
     * Commit media paths with the question; remove superseded files only after success.
     *
     * @param  array<string, UploadedFile>  $uploads
     * @param  list<string>  $removals
     * @param  Closure(): QuizQuestion  $save
     */
    public function save(array $uploads, array $removals, Closure $save): QuizQuestion
    {
        $newPaths = [];
        $oldPaths = [];
        try {
            foreach ($uploads as $field => $file) {
                $path = $file->store('quiz/images', 'public');
                if ($path === false) {
                    throw new RuntimeException('Imej tidak dapat disimpan.');
                }
                $newPaths[$field.'_path'] = $path;
            }
            $question = DB::transaction(function () use ($save, $newPaths, $removals, &$oldPaths): QuizQuestion {
                $question = $save();
                $updates = $newPaths;
                foreach ($removals as $field) {
                    $updates[$field.'_path'] ??= null;
                }
                foreach ($updates as $column => $path) {
                    $oldPath = $question->getAttribute($column);
                    if (is_string($oldPath)) {
                        $oldPaths[] = $oldPath;
                    }
                }
                $question->update($updates);

                return $question->refresh();
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete(array_values($newPaths));
            throw $exception;
        }
        Storage::disk('public')->delete($oldPaths);

        return $question;
    }
}
