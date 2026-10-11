<?php

namespace App\Http\Controllers\Teacher;

use App\Actions\Teacher\CreateTopicModule;
use App\Actions\Teacher\DeleteTopicModule;
use App\Actions\Teacher\UpdateTopicContent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreTopicModuleRequest;
use App\Http\Requests\Teacher\UpdateTopicContentRequest;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class TopicManagementController extends Controller
{
    /**
     * Display all configured Science topics and their content status.
     */
    public function index(): View
    {
        return view('teacher.topics.index', [
            'topics' => Topic::query()
                ->withCount(['questions', 'quizAttempts'])
                ->orderBy('topic_order')
                ->get(),
        ]);
    }

    /**
     * Show the form for adding the next Science module.
     */
    public function create(): View
    {
        return view('teacher.topics.create');
    }

    /**
     * Store a new Science module and its uploaded learning media.
     */
    public function store(
        StoreTopicModuleRequest $request,
        CreateTopicModule $createTopicModule,
    ): RedirectResponse {
        $teacher = $request->user();

        abort_unless($teacher instanceof User, 403);

        $createTopicModule->handle(
            $request->topicName(),
            $request->contentText(),
            $request->pdfFile(),
            $request->youtubeUrl(),
            $request->topicStatus(),
            $teacher,
        );

        return redirect()->route('teacher.topics.index')->with('status', 'Modul pembelajaran telah ditambah.');
    }

    /**
     * Show the learning-content editor for one topic.
     */
    public function edit(Topic $topic): View
    {
        return view('teacher.topics.edit', ['topic' => $topic]);
    }

    /**
     * Update a topic's learning materials and publication state.
     */
    public function update(
        UpdateTopicContentRequest $request,
        Topic $topic,
        UpdateTopicContent $updateTopicContent,
    ): RedirectResponse {
        $teacher = $request->user();

        abort_unless($teacher instanceof User, 403);

        $updateTopicContent->handle(
            $topic,
            $request->contentText(),
            $request->pdfFile(),
            $request->youtubeUrl(),
            $request->boolean('remove_pdf'),
            $request->topicStatus(),
            $teacher,
        );

        return redirect()->route('teacher.topics.index')->with('status', 'Kandungan pembelajaran telah dikemas kini.');
    }

    /**
     * Delete an unused module and its stored media files.
     */
    public function destroy(Topic $topic, DeleteTopicModule $deleteTopicModule): RedirectResponse
    {
        $deleteTopicModule->handle($topic);

        return redirect()->route('teacher.topics.index')->with('status', 'Modul pembelajaran telah dipadamkan.');
    }
}
