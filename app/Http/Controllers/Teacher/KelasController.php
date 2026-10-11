<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreKelasRequest;
use App\Models\Kelas;
use Illuminate\Contracts\View\View;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KelasController extends Controller
{
    public function index(): View
    {
        return view('teacher.kelas.index', [
            'classes' => Kelas::query()->withCount(['students', 'teachers'])->orderBy('nama')->paginate(20),
        ]);
    }

    public function store(StoreKelasRequest $request): RedirectResponse
    {
        try {
            Kelas::query()->create($request->validated());
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['nama' => 'Nama kelas telah digunakan.']);
        }

        return redirect()->route('teacher.kelas.index')->with('status', 'Kelas berjaya ditambah.');
    }

    public function destroy(Kelas $kelas): RedirectResponse
    {
        $deleted = DB::transaction(function () use ($kelas): bool {
            $locked = Kelas::query()->whereKey($kelas->id)->lockForUpdate()->firstOrFail();
            if ($locked->students()->exists() || $locked->teachers()->exists()) {
                return false;
            }
            $locked->delete();

            return true;
        });

        return redirect()->route('teacher.kelas.index')
            ->with($deleted ? 'status' : 'error', $deleted
                ? 'Kelas berjaya dipadam.'
                : 'Kelas tidak boleh dipadam kerana masih mempunyai pelajar atau guru yang ditugaskan.');
    }
}
