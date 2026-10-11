<?php

use App\Enums\UserRole;
use App\Http\Controllers\Auth\PasswordRecoveryRequestController;
use App\Http\Controllers\Auth\RegisterStudentController;
use App\Http\Controllers\Student\LearningModuleController;
use App\Http\Controllers\Student\QuizController;
use App\Http\Controllers\Teacher\AcademicYearController;
use App\Http\Controllers\Teacher\KelasController;
use App\Http\Controllers\Teacher\ProgressReportController;
use App\Http\Controllers\Teacher\QuizQuestionManagementController;
use App\Http\Controllers\Teacher\TopicManagementController;
use App\Http\Controllers\Teacher\UserAccountManagementController;
use App\Http\Controllers\TopicPdfController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('daftar', [RegisterStudentController::class, 'create'])->name('register');
    Route::post('daftar', [RegisterStudentController::class, 'store'])
        ->middleware('throttle:registration')
        ->name('register.store');

    Route::get('lupa-kata-laluan', [PasswordRecoveryRequestController::class, 'create'])->name('password.request');
    Route::post('lupa-kata-laluan', [PasswordRecoveryRequestController::class, 'store'])
        ->middleware('throttle:password-recovery')
        ->name('password.recovery.store');
});

Route::middleware('auth')->group(function () {
    Route::get('modul-pembelajaran/{topic}/pdf', TopicPdfController::class)->name('topics.pdf');

    Route::get('dashboard', function () {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user->role === UserRole::Student
            ? redirect()->route('student.dashboard')
            : redirect()->route('teacher.dashboard');
    })->name('dashboard');

    Route::get('pelajar/papan-pemuka', [LearningModuleController::class, 'dashboard'])
        ->middleware('role:student')
        ->name('student.dashboard');

    Route::get('pelajar/modul-pembelajaran/{topic}', [LearningModuleController::class, 'show'])
        ->middleware('role:student')
        ->name('student.learning.show');

    Route::post('pelajar/modul-pembelajaran/{topic}/kuiz', [QuizController::class, 'start'])
        ->middleware('role:student')
        ->name('student.quiz.start');
    Route::get('pelajar/kuiz/{attempt}', [QuizController::class, 'show'])
        ->middleware('role:student')
        ->name('student.quiz.show');
    Route::post('pelajar/kuiz/{attempt}/soalan/{question}', [QuizController::class, 'answer'])
        ->middleware(['role:student', 'throttle:quiz-answers'])
        ->name('student.quiz.answer');
    Route::get('pelajar/kuiz/{attempt}/maklum-balas/{answer}', [QuizController::class, 'feedback'])
        ->middleware('role:student')
        ->name('student.quiz.feedback');
    Route::get('pelajar/kuiz/{attempt}/keputusan', [QuizController::class, 'result'])
        ->middleware('role:student')
        ->name('student.quiz.result');

    Route::get('guru/papan-pemuka', [ProgressReportController::class, 'dashboard'])
        ->middleware('role:teacher,admin')
        ->name('teacher.dashboard');

    Route::middleware('role:teacher,admin')->prefix('guru')->name('teacher.')->group(function () {
        Route::get('pantau-kemajuan', [ProgressReportController::class, 'index'])->name('progress.index');
        Route::get('pantau-kemajuan/{student}', [ProgressReportController::class, 'show'])->name('progress.show');
        Route::get('analitik-kelas', [ProgressReportController::class, 'analytics'])->name('analytics.index');

        Route::get('modul-pembelajaran/tambah', [TopicManagementController::class, 'create'])->name('topics.create');
        Route::post('modul-pembelajaran', [TopicManagementController::class, 'store'])->name('topics.store');
        Route::get('modul-pembelajaran', [TopicManagementController::class, 'index'])->name('topics.index');
        Route::get('modul-pembelajaran/{topic}', [TopicManagementController::class, 'edit'])->name('topics.edit');
        Route::patch('modul-pembelajaran/{topic}', [TopicManagementController::class, 'update'])->name('topics.update');
        Route::delete('modul-pembelajaran/{topic}', [TopicManagementController::class, 'destroy'])->name('topics.destroy');

        Route::get('modul-pembelajaran/{topic}/soalan', [QuizQuestionManagementController::class, 'index'])->name('questions.index');
        Route::get('modul-pembelajaran/{topic}/soalan/tambah', [QuizQuestionManagementController::class, 'create'])->name('questions.create');
        Route::post('modul-pembelajaran/{topic}/soalan', [QuizQuestionManagementController::class, 'store'])->name('questions.store');
        Route::get('modul-pembelajaran/{topic}/soalan/{question}', [QuizQuestionManagementController::class, 'edit'])->name('questions.edit');
        Route::patch('modul-pembelajaran/{topic}/soalan/{question}', [QuizQuestionManagementController::class, 'update'])->name('questions.update');
        Route::delete('modul-pembelajaran/{topic}/soalan/{question}', [QuizQuestionManagementController::class, 'destroy'])->name('questions.destroy');
    });

    Route::middleware(['role:admin', 'password.confirm'])->prefix('guru')->name('teacher.')->group(function () {
        Route::get('kelas', [KelasController::class, 'index'])->name('kelas.index');

        Route::get('tahun-akademik', [AcademicYearController::class, 'index'])->name('academic-years.index');
        Route::get('tahun-akademik/{academicYear}/pratonton', [AcademicYearController::class, 'show'])->name('academic-years.show');

        Route::get('pengguna', [UserAccountManagementController::class, 'index'])->name('users.index');
        Route::get('pengguna/tambah', [UserAccountManagementController::class, 'create'])->name('users.create');

        Route::middleware('password.confirm')->group(function () {
            Route::post('kelas', [KelasController::class, 'store'])->name('kelas.store');
            Route::delete('kelas/{kelas}', [KelasController::class, 'destroy'])->name('kelas.destroy');

            Route::post('tahun-akademik', [AcademicYearController::class, 'store'])->name('academic-years.store');
            Route::patch('tahun-akademik/{academicYear}/jadual', [AcademicYearController::class, 'update'])->name('academic-years.update');
            Route::delete('tahun-akademik/{academicYear}/jadual', [AcademicYearController::class, 'cancel'])->name('academic-years.cancel');

            Route::post('pengguna', [UserAccountManagementController::class, 'store'])->name('users.store');
            Route::post('pengguna/semakan', [UserAccountManagementController::class, 'review'])->name('users.review');
            Route::patch('pengguna/{account}/status', [UserAccountManagementController::class, 'updateStatus'])->name('users.status.update');
            Route::get('pengguna/{account}/kata-laluan', [UserAccountManagementController::class, 'editPassword'])->name('users.password.edit');
            Route::put('pengguna/{account}/kata-laluan', [UserAccountManagementController::class, 'updatePassword'])->name('users.password.update');
        });
    });
});

require __DIR__.'/settings.php';
