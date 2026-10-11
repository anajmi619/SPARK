<?php

namespace App\Http\Controllers\Teacher;

use App\Actions\Teacher\CreateQuizQuestion;
use App\Actions\Teacher\DeleteQuizQuestion;
use App\Actions\Teacher\UpdateQuizQuestion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreQuizQuestionRequest;
use App\Http\Requests\Teacher\UpdateQuizQuestionRequest;
use App\Models\QuizQuestion;
use App\Models\Topic;
use App\Services\QuizQuestionImages;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class QuizQuestionManagementController extends Controller
{
    /**
     * Display all quiz questions configured for one learning topic.
     */
    public function index(Topic $topic): View
    {
        return view('teacher.questions.index', [
            'topic' => $topic,
            'questions' => $topic->questions()->withCount('answers')->orderBy('question_order')->get(),
        ]);
    }

    /**
     * Show the form for adding the next question to a topic.
     */
    public function create(Topic $topic): View
    {
        return view('teacher.questions.create', ['topic' => $topic]);
    }

    /**
     * Store a new four-option quiz question.
     */
    public function store(
        StoreQuizQuestionRequest $request,
        Topic $topic,
        CreateQuizQuestion $createQuizQuestion,
        QuizQuestionImages $images,
    ): RedirectResponse {
        $images->save($request->imageUploads(), [], fn () => $createQuizQuestion->handle(
            $topic,
            $request->questionText(),
            $request->answerOptions(),
            $request->correctOption(),
            $request->curriculumContext(),
            $request->questionStatus(),
            $request->explanation(),
        ));

        return redirect()->route('teacher.questions.index', $topic)->with('status', 'Soalan kuiz telah ditambah.');
    }

    /**
     * Show the edit form for a question belonging to the selected topic.
     */
    public function edit(Topic $topic, QuizQuestion $question): View
    {
        $this->ensureQuestionBelongsToTopic($topic, $question);

        return view('teacher.questions.edit', compact('topic', 'question'));
    }

    /**
     * Update one topic quiz question.
     */
    public function update(
        UpdateQuizQuestionRequest $request,
        Topic $topic,
        QuizQuestion $question,
        UpdateQuizQuestion $updateQuizQuestion,
        QuizQuestionImages $images,
    ): RedirectResponse {
        $this->ensureQuestionBelongsToTopic($topic, $question);

        $images->save($request->imageUploads(), $request->imageRemovals(), fn () => $updateQuizQuestion->handle(
            $question,
            $request->questionText(),
            $request->answerOptions(),
            $request->correctOption(),
            $request->curriculumContext(),
            $request->questionStatus(),
            $request->explanation(),
        ));

        return redirect()->route('teacher.questions.index', $topic)->with('status', 'Soalan kuiz telah dikemas kini.');
    }

    /**
     * Delete a question when it has not been answered by a student.
     */
    public function destroy(
        Topic $topic,
        QuizQuestion $question,
        DeleteQuizQuestion $deleteQuizQuestion,
    ): RedirectResponse {
        $this->ensureQuestionBelongsToTopic($topic, $question);

        $deleteQuizQuestion->handle($question);

        return redirect()->route('teacher.questions.index', $topic)->with('status', 'Soalan kuiz telah dipadamkan.');
    }

    /**
     * Prevent a question from being managed through a different topic URL.
     */
    private function ensureQuestionBelongsToTopic(Topic $topic, QuizQuestion $question): void
    {
        abort_unless($question->topic_id === $topic->topic_id, 404);
    }
}
