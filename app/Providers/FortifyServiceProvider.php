<?php

namespace App\Providers;

use App\Enums\AcademicYearStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Responses\LoginResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureAuthentication();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure SPARK's role and cohort aware login process.
     */
    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            $validated = Validator::make($request->all(), [
                'username' => ['required', 'string'],
                'password' => ['required', 'string'],
                'role' => ['required', 'string', Rule::in(['student', 'teacher'])],
            ])->validate();

            $allowedRoles = $validated['role'] === 'student'
                ? [UserRole::Student]
                : [UserRole::Teacher, UserRole::Admin];

            $user = User::query()
                ->with('academicYear')
                ->where('username', $validated['username'])
                ->first();

            if (! $user instanceof User
                || ! Hash::check($validated['password'], $user->password_hash)
                || ! in_array($user->role, $allowedRoles, true)
                || $user->status !== UserStatus::Active) {
                return null;
            }

            if ($user->role === UserRole::Student
                && $user->academicYear?->status !== AcademicYearStatus::Active) {
                return null;
            }

            return $user;
        });

        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('livewire.auth.login'));
        Fortify::confirmPasswordView(fn () => view('livewire.auth.confirm-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('registration', fn (Request $request) => Limit::perHour(5)
            ->by('registration|'.$request->ip()));

        RateLimiter::for('password-recovery', function (Request $request): array {
            $username = Str::transliterate(Str::lower((string) $request->input('username')));

            return [
                Limit::perMinute(5)->by('recovery-ip|'.$request->ip()),
                Limit::perHour(3)->by('recovery-user|'.$username.'|'.$request->ip()),
            ];
        });

        RateLimiter::for('quiz-answers', fn (Request $request) => Limit::perMinute(30)
            ->by('quiz-answer|'.($request->user()?->getAuthIdentifier() ?? $request->ip())));
    }
}
