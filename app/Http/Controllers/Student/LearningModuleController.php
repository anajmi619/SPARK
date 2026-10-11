<?php

namespace App\Http\Controllers\Student;

use App\Actions\Student\CalculateStudentProgress;
use App\Enums\QuestionStatus;
use App\Enums\TopicStatus;
use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LearningModuleController extends Controller
{
    /**
     * Display the published learning modules available to the student.
     */
    public function dashboard(Request $request, CalculateStudentProgress $calculateStudentProgress): View
    {
        $student = $request->user();
        abort_unless($student instanceof User, 403);

        return view('dashboards.student', [
            'progress' => $calculateStudentProgress->handle($student),
            'topics' => Topic::query()
                ->where('status', TopicStatus::Published->value)
                ->orderBy('topic_order')
                ->get(),
        ]);
    }

    /**
     * Display one published learning module and its available media.
     */
    public function show(Topic $topic): View
    {
        abort_unless($topic->status === TopicStatus::Published, 404);

        return view('student.learning.show', [
            'topic' => $topic,
            'pdfUrl' => $topic->pdf_path === null ? null : route('topics.pdf', $topic),
            'imageUrl' => $topic->pdf_path !== null || $topic->image_path === null ? null : Storage::disk('public')->url($topic->image_path),
            'videoUrl' => $topic->youtubeEmbedUrl(),
            'activeQuestionCount' => $topic->questions()
                ->where('status', QuestionStatus::Active->value)
                ->count(),
        ]);
    }
}
