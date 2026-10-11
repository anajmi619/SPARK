<?php

namespace App\Http\Controllers\Teacher;

use App\Actions\Teacher\CreateUserAccount;
use App\Actions\Teacher\ResetManagedAccountPassword;
use App\Actions\Teacher\ReviewStudentRegistrations;
use App\Actions\Teacher\SetManagedAccountStatus;
use App\Enums\AcademicYearStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\FilterUserAccountsRequest;
use App\Http\Requests\Teacher\ResetStudentPasswordRequest;
use App\Http\Requests\Teacher\ReviewStudentRegistrationsRequest;
use App\Http\Requests\Teacher\StoreUserAccountRequest;
use App\Http\Requests\Teacher\UpdateManagedAccountStatusRequest;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class UserAccountManagementController extends Controller
{
    /**
     * Display searchable account records for the school administrator.
     */
    public function index(FilterUserAccountsRequest $request): View
    {
        $filters = $request->validated();
        $search = $filters['search'] ?? null;

        $accounts = User::query()
            ->with(['academicYear', 'kelas', 'assignedClasses'])
            ->when($search, function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('full_name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->when($filters['academic_year_id'] ?? null, fn ($query, $academicYearId) => $query->where('academic_year_id', $academicYearId))
            ->when($filters['kelas_id'] ?? null, fn ($query, $kelasId) => $query->where('kelas_id', $kelasId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString();

        return view('teacher.users.index', [
            'accounts' => $accounts,
            'classes' => Kelas::query()->orderBy('nama')->get(),
            'academicYears' => AcademicYear::query()->orderByDesc('year')->get(),
        ]);
    }

    /**
     * Show the teacher-managed user creation form.
     */
    public function create(): View
    {
        return view('teacher.users.create', [
            'classes' => Kelas::query()->orderBy('nama')->get(),
            'academicYears' => AcademicYear::query()
                ->whereIn('status', [AcademicYearStatus::Active, AcademicYearStatus::Upcoming])
                ->orderByDesc('year')
                ->get(),
        ]);
    }

    /**
     * Store a teacher-created student or teacher account.
     */
    public function store(StoreUserAccountRequest $request, CreateUserAccount $createUserAccount): RedirectResponse
    {
        $user = $createUserAccount->handle($request->accountData());
        $roleLabel = $user->role === UserRole::Student ? 'pelajar' : 'guru';

        return redirect()
            ->route('teacher.users.index')
            ->with('status', "Akaun {$roleLabel} telah berjaya dicipta.");
    }

    /**
     * Process one or more pending student registrations.
     */
    public function review(
        ReviewStudentRegistrationsRequest $request,
        ReviewStudentRegistrations $reviewStudentRegistrations,
    ): RedirectResponse {
        $validated = $request->validated();
        $total = $reviewStudentRegistrations->handle($validated['user_ids'], $validated['decision']);
        $message = $validated['decision'] === 'approve'
            ? "{$total} pendaftaran pelajar telah diluluskan."
            : "{$total} pendaftaran pelajar telah ditolak.";

        return redirect()->route('teacher.users.index')->with('status', $message);
    }

    /**
     * Change an active-year student between active and inactive states.
     */
    public function updateStatus(
        UpdateManagedAccountStatusRequest $request,
        User $account,
        SetManagedAccountStatus $setManagedAccountStatus,
    ): RedirectResponse {
        $administrator = $request->user();
        abort_unless($administrator instanceof User, 403);
        $setManagedAccountStatus->handle($administrator, $account, UserStatus::from($request->validated('status')));

        return redirect()->route('teacher.users.index')->with('status', 'Status akaun telah dikemas kini.');
    }

    /**
     * Show the administrator-assisted student password reset form.
     */
    public function editPassword(User $account): View
    {
        abort_unless(in_array($account->role, [UserRole::Student, UserRole::Teacher], true), 404);

        return view('teacher.users.reset-password', ['student' => $account]);
    }

    /**
     * Store a new password for a student account.
     */
    public function updatePassword(
        ResetStudentPasswordRequest $request,
        User $account,
        ResetManagedAccountPassword $resetManagedAccountPassword,
    ): RedirectResponse {
        $resetManagedAccountPassword->handle($account, $request->validated('password'));

        return redirect()->route('teacher.users.index')->with('status', 'Kata laluan akaun telah ditetapkan semula.');
    }
}
