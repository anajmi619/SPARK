<?php

namespace App\Http\Controllers\Student;

use App\Actions\Student\CalculateStudentProgress;
use App\Actions\Student\GenerateAiFeedback;
use App\Actions\Student\StartQuizAttempt;
use App\Actions\Student\SubmitQuizAnswer;
use App\Enums\AttemptStatus;
use App\Enums\QuestionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SubmitQuizAnswerRequest;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    /**
     * Start a topic quiz or resume the student's unfinished attempt.
     */
    public function start(Request $request, Topic $topic, StartQuizAttempt $startQuizAttempt): RedirectResponse
    {
        $student = $request->user();

        abort_unless($student instanceof User, 403);

        $attempt = $startQuizAttempt->handle($topic, $student);

        return redirect()->route('student.quiz.show', $attempt);
    }

    /**
     * Display the next unanswered active question in an attempt.
     */
    public function show(Request $request, QuizAttempt $attempt): View|RedirectResponse
    {
        $this->ensureStudentOwnsAttempt($request, $attempt);

        if ($attempt->status === AttemptStatus::Completed) {
            return redirect()->route('student.quiz.result', $attempt);
        }

        $question = $this->nextUnansweredQuestion($attempt);

        abort_if($question === null, 404);

        return view('student.quiz.show', [
            'attempt' => $attempt,
            'question' => $question,
            'questionNumber' => $attempt->answers()->count() + 1,
            'totalQuestionCount' => $this->activeQuestionCount($attempt),
        ]);
    }

    /**
     * Record one selected option and redirect to its evaluated answer state.
     */
    public function answer(
        SubmitQuizAnswerRequest $request,
        QuizAttempt $attempt,
        QuizQuestion $question,
        SubmitQuizAnswer $submitQuizAnswer,
        GenerateAiFeedback $generateAiFeedback,
    ): RedirectResponse {
        $this->ensureStudentOwnsAttempt($request, $attempt);

        $nextQuestion = $this->nextUnansweredQuestion($attempt);

        abort_unless($nextQuestion !== null && $nextQuestion->is($question), 404);

        $submission = $submitQuizAnswer->handle(
            $attempt,
            $question,
            $request->selectedOption(),
            $generateAiFeedback,
        );

        if ($submission['levelUp'] !== null) {
            $request->session()->flash('quiz_level_up', [
                'attempt_id' => $attempt->attempt_id,
                'name' => $submission['levelUp']->level_name,
                'number' => $submission['levelUp']->level_number,
            ]);
        }

        return redirect()->route('student.quiz.feedback', ['attempt' => $attempt, 'answer' => $submission['answer']]);
    }

    /**
     * Display feedback after an answer has been evaluated.
     */
    public function feedback(Request $request, QuizAttempt $attempt, QuizAnswer $answer): View
    {
        $this->ensureStudentOwnsAttempt($request, $attempt);

        abort_unless($answer->attempt_id === $attempt->attempt_id, 404);

        if ($request->session()->get('quiz_level_up.attempt_id') === $attempt->attempt_id) {
            $request->session()->keep('quiz_level_up');
        }

        return view('student.quiz.feedback', [
            'attempt' => $attempt,
            'answer' => $answer,
            'hasRemainingQuestion' => $this->nextUnansweredQuestion($attempt) !== null,
        ]);
    }

    /**
     * Display a completed attempt's score.
     */
    public function result(Request $request, QuizAttempt $attempt, CalculateStudentProgress $calculateStudentProgress): View|RedirectResponse
    {
        $this->ensureStudentOwnsAttempt($request, $attempt);

        if ($attempt->status !== AttemptStatus::Completed) {
            return redirect()->route('student.quiz.show', $attempt);
        }

        $student = $request->user();
        abort_unless($student instanceof User, 403);

        $progress = $calculateStudentProgress->handle($student);

        return view('student.quiz.result', [
            'attempt' => $attempt,
            'totalQuestionCount' => $attempt->answers()->count(),
            'progress' => $progress,
            'bestTopicScore' => $progress['topicScores'][$attempt->topic_id] ?? 0,
            'levelUp' => $request->session()->get('quiz_level_up.attempt_id') === $attempt->attempt_id
                ? $request->session()->get('quiz_level_up') : null,
        ]);
    }

    /**
     * Return the next active topic question that the student has not answered in this attempt.
     */
    private function nextUnansweredQuestion(QuizAttempt $attempt): ?QuizQuestion
    {
        return QuizQuestion::query()
            ->where('topic_id', $attempt->topic_id)
            ->where('status', QuestionStatus::Active->value)
            ->whereDoesntHave('answers', function ($query) use ($attempt): void {
                $query->where('attempt_id', $attempt->attempt_id);
            })
            ->orderBy('question_order')
            ->first();
    }

    /**
     * Return the number of active questions that form the current quiz.
     */
    private function activeQuestionCount(QuizAttempt $attempt): int
    {
        return QuizQuestion::query()
            ->where('topic_id', $attempt->topic_id)
            ->where('status', QuestionStatus::Active->value)
            ->count();
    }

    /**
     * Confirm that the requested attempt belongs to the signed-in student.
     */
    private function ensureStudentOwnsAttempt(Request $request, QuizAttempt $attempt): void
    {
        $student = $request->user();

        abort_unless($student instanceof User && $attempt->student_id === $student->user_id, 404);
    }
}
