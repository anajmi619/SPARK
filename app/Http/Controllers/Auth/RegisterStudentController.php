<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterStudent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreStudentRegistrationRequest;
use App\Models\Kelas;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class RegisterStudentController extends Controller
{
    /**
     * Show the student registration form.
     */
    public function create(): View
    {
        return view('livewire.auth.register', ['classes' => Kelas::query()->orderBy('nama')->get()]);
    }

    /**
     * Store a pending student account without starting an authenticated session.
     */
    public function store(StoreStudentRegistrationRequest $request, RegisterStudent $registerStudent): RedirectResponse
    {
        $registerStudent->handle(
            $request->string('full_name')->toString(),
            $request->string('username')->toString(),
            $request->integer('kelas_id'),
            $request->string('password')->toString(),
        );

        return redirect()
            ->route('login')
            ->with('status', 'Pendaftaran berjaya dihantar. Sila tunggu kelulusan guru.');
    }
}
