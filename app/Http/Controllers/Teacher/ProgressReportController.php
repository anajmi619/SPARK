<?php

namespace App\Http\Controllers\Teacher;

use App\Actions\Student\CalculateStudentProgress;
use App\Actions\Teacher\BuildProgressReports;
use App\Enums\AcademicYearStatus;
use App\Enums\AiFeedbackSource;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\FilterProgressReportsRequest;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\QuizAnswer;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;

class ProgressReportController extends Controller
{
    public function __construct(private BuildProgressReports $reports) {}

    public function dashboard(FilterProgressReportsRequest $request): View
    {
        $context = $this->context($request);
        $students = $this->reports->students($context['year']?->academic_year_id, $context['kelasId'], allowedClassIds: $context['allowedClassIds']);

        return view('dashboards.teacher', $context + [
            'summary' => $this->reports->summary($students),
            'topics' => $this->reports->topicRows($students),
            'aiUsage' => $this->aiUsage($request->user()),
        ]);
    }

    public function index(FilterProgressReportsRequest $request): View
    {
        $context = $this->context($request);
        $students = $this->reports->students($context['year']?->academic_year_id, $context['kelasId'], $request->validated('search'), $context['allowedClassIds']);

        return view('teacher.reports.index', $context + [
            'students' => $this->reports->studentRows($students, $request->boolean('support_only'))
                ->orderBy('users.full_name')->orderBy('users.user_id')->paginate(15)->withQueryString(),
        ]);
    }

    public function analytics(FilterProgressReportsRequest $request): View
    {
        $context = $this->context($request);
        $students = $this->reports->students($context['year']?->academic_year_id, $context['kelasId'], allowedClassIds: $context['allowedClassIds']);

        return view('teacher.reports.analytics', $context + [
            'summary' => $this->reports->summary($students),
            'topics' => $this->reports->topicRows($students),
            'students' => $this->reports->studentRows($students, true)
                ->orderByDesc('scores.weak_topics')->orderBy('users.full_name')->orderBy('users.user_id')
                ->paginate(15)->withQueryString(),
        ]);
    }

    public function show(FilterProgressReportsRequest $request, User $student, CalculateStudentProgress $progress): View
    {
        $context = $this->context($request, $student);
        $students = $this->reports->students($context['year']?->academic_year_id, $context['kelasId'], allowedClassIds: $context['allowedClassIds'])
            ->whereKey($student->user_id);
        abort_unless((clone $students)->exists(), 404);

        return view('teacher.reports.show', $context + [
            'student' => $student,
            'progress' => $progress->handle($student),
            'summary' => $this->reports->summary($students),
            'topics' => $this->reports->topicRows($students),
        ]);
    }

    /**
     * @return array{year: AcademicYear|null, academicYears: Collection<int, AcademicYear>, classes: Collection<int, Kelas>, kelasId: int|null, allowedClassIds: array<int, int>|null, filterQuery: array<string, mixed>}
     */
    private function context(FilterProgressReportsRequest $request, ?User $student = null): array
    {
        $years = AcademicYear::query()->orderByDesc('year')->get();
        $yearId = $request->validated('academic_year_id') ?? $student?->academic_year_id;
        $year = $yearId !== null
            ? $years->firstWhere('academic_year_id', (int) $yearId)
            : ($years->firstWhere('status', AcademicYearStatus::Active)
                ?? $years->firstWhere('status', AcademicYearStatus::Closed) ?? $years->first());
        $viewer = $request->user();
        abort_unless($viewer instanceof User && $viewer->isStaff(), 403);
        $classes = $viewer->role === UserRole::Admin
            ? Kelas::query()->orderBy('nama')->get()
            : $viewer->assignedClasses()->orderBy('nama')->get();
        $allowedClassIds = $viewer->role === UserRole::Admin
            ? null
            : $classes->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();
        $kelasId = $request->filled('kelas_id') ? $request->integer('kelas_id') : null;

        if ($kelasId !== null && ! $classes->contains('id', $kelasId)) {
            abort(403);
        }

        return [
            'year' => $year,
            'academicYears' => $years,
            'classes' => $classes,
            'kelasId' => $kelasId,
            'allowedClassIds' => $allowedClassIds,
            'filterQuery' => array_filter([
                'academic_year_id' => $year?->academic_year_id, 'kelas_id' => $kelasId,
            ], fn ($value): bool => $value !== null),
        ];
    }

    /** @return array{apiCalls: int, cacheHits: int, dailyLimit: int}|null */
    private function aiUsage(?User $viewer): ?array
    {
        if (! $viewer?->isAdmin()) {
            return null;
        }

        $start = now()->startOfDay()->utc();
        $end = now()->endOfDay()->utc();

        return [
            'apiCalls' => QuizAnswer::query()
                ->where('ai_feedback_source', AiFeedbackSource::Api)
                ->whereBetween('answered_at', [$start, $end])
                ->count(),
            'cacheHits' => QuizAnswer::query()
                ->where('ai_feedback_source', AiFeedbackSource::Cache)
                ->whereBetween('answered_at', [$start, $end])
                ->count(),
            'dailyLimit' => (int) config('quiz.ai_feedback_daily_limit', 100),
        ];
    }
}
