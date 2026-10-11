<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StorePasswordRecoveryRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PasswordRecoveryRequestController extends Controller
{
    /**
     * Show the administrator-assisted password recovery form.
     */
    public function create(): View
    {
        return view('livewire.auth.forgot-password');
    }

    /**
     * Record an account recovery request without disclosing account existence.
     */
    public function store(StorePasswordRecoveryRequest $request): RedirectResponse
    {
        User::query()
            ->where('username', $request->validated('username'))
            ->update(['recovery_requested_at' => now()]);

        return redirect()
            ->route('login')
            ->with('status', 'Permohonan pemulihan telah diterima. Jika akaun wujud, pentadbir sekolah akan membantu anda.');
    }
}
