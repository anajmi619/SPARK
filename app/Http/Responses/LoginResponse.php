<?php

namespace App\Http\Responses;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Redirect an authenticated user to the dashboard permitted by their stored role.
     */
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $route = $user->role === UserRole::Student
            ? 'student.dashboard'
            : 'teacher.dashboard';

        return redirect()->route($route);
    }
}
