<?php

namespace App\Http\Controllers;

use App\Enums\AcademicYearStatus;
use App\Enums\TopicStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TopicPdfController extends Controller
{
    /**
     * Serve private learning PDFs through the browser's document reader.
     */
    public function __invoke(Request $request, Topic $topic): BinaryFileResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->status === UserStatus::Active, 403);

        $canRead = match ($user->role) {
            UserRole::Teacher, UserRole::Admin => true,
            UserRole::Student => $user->academicYear?->status === AcademicYearStatus::Active,
            default => false,
        };
        abort_unless($canRead, 403);

        if ($user->role === UserRole::Student) {
            abort_unless($topic->status === TopicStatus::Published, 404);
        }

        $path = $topic->pdf_path;
        abort_unless($path !== null && str_starts_with($path, 'topics/documents/') && ! str_contains($path, '..'), 404);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);

        return response()->file($disk->path($path), [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
        ])->setPrivate()->setContentDisposition(
            $request->boolean('download') ? 'attachment' : 'inline',
            'nota-topik-'.$topic->topic_order.'.pdf',
        );
    }
}
