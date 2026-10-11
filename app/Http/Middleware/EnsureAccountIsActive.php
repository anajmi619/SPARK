<?php

namespace App\Http\Middleware;

use App\Enums\AcademicYearStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user instanceof User) {
            $user->refresh();
            if ($user->status !== UserStatus::Active
                || ($user->role === UserRole::Student && $user->academicYear?->status !== AcademicYearStatus::Active)) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Akaun atau tahun akademik anda tidak lagi aktif.'], 403);
                }

                return redirect()->route('login')->with('status', 'Akaun atau tahun akademik anda tidak lagi aktif. Sila hubungi guru.');
            }
        }

        return $next($request);
    }
}
