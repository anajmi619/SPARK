<?php

namespace App\Http\Controllers\Teacher;

use App\Actions\Teacher\ManageAcademicYears;
use App\Enums\AcademicYearStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\ScheduleAcademicYearRequest;
use App\Http\Requests\Teacher\StoreAcademicYearRequest;
use App\Models\AcademicYear;
use App\Models\RolloverAudit;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    public function index(): View
    {
        $years = $this->years();

        return view('teacher.academic-years.index', [
            'years' => $years,
            'current' => $years->firstWhere('status', AcademicYearStatus::Active),
            'next' => $years->where('status', AcademicYearStatus::Upcoming)->sortBy('year')->first(),
            'nextNumber' => ($years->max('year') ?? now()->year) + 1,
            'audits' => RolloverAudit::query()->with(['academicYear', 'previousYear', 'performer'])->latest('id')->paginate(15),
        ]);
    }

    public function show(AcademicYear $academicYear): View
    {
        $years = $this->years();
        $next = $years->where('status', AcademicYearStatus::Upcoming)->sortBy('year')->first();
        abort_unless($next !== null && $next->is($academicYear), 404);

        return view('teacher.academic-years.show', [
            'year' => $next,
            'current' => $years->firstWhere('status', AcademicYearStatus::Active),
        ]);
    }

    public function store(StoreAcademicYearRequest $request, ManageAcademicYears $manage): RedirectResponse
    {
        $manage->create($request->integer('year'));

        return to_route('teacher.academic-years.index')->with('status', 'Tahun akademik baharu telah ditambah.');
    }

    public function update(ScheduleAcademicYearRequest $request, AcademicYear $academicYear, ManageAcademicYears $manage): RedirectResponse
    {
        $teacher = $request->user();
        abort_unless($teacher instanceof User, 403);
        $manage->schedule($academicYear, $request->rolloverDate(), $teacher);

        return to_route('teacher.academic-years.index')->with('status', 'Jadual peralihan telah disimpan.');
    }

    public function cancel(Request $request, AcademicYear $academicYear, ManageAcademicYears $manage): RedirectResponse
    {
        $teacher = $request->user();
        abort_unless($teacher instanceof User && $teacher->role === UserRole::Admin, 403);
        $manage->schedule($academicYear, null, $teacher);

        return to_route('teacher.academic-years.index')->with('status', 'Jadual peralihan telah dibatalkan.');
    }

    /** @return Collection<int, AcademicYear> */
    private function years(): Collection
    {
        return AcademicYear::query()->withCount([
            'users as active_students_count' => fn (Builder $query) => $query->where('role', UserRole::Student)->where('status', UserStatus::Active),
            'users as approved_students_count' => fn (Builder $query) => $query->where('role', UserRole::Student)->where('status', UserStatus::Approved),
            'users as pending_students_count' => fn (Builder $query) => $query->where('role', UserRole::Student)->where('status', UserStatus::Pending),
        ])->orderByDesc('year')->get();
    }
}
