<?php

namespace App\Actions\Teacher;

use App\Enums\AcademicYearStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetManagedAccountStatus
{
    public function handle(User $actor, User $account, UserStatus $requestedStatus): void
    {
        DB::transaction(function () use ($actor, $account, $requestedStatus): void {
            $account = User::query()->whereKey($account->user_id)->lockForUpdate()->firstOrFail();

            if ($account->is($actor)) {
                throw ValidationException::withMessages([
                    'status' => ['Anda tidak boleh menyahaktifkan akaun sendiri.'],
                ]);
            }

            if ($account->role === UserRole::Student) {
                AcademicYear::query()->whereKey($account->academic_year_id)->lockForUpdate()->first();
                $account->loadMissing('academicYear');

                if ($account->academicYear?->status !== AcademicYearStatus::Active) {
                    throw ValidationException::withMessages([
                        'status' => ['Status pelajar hanya boleh diubah untuk tahun akademik aktif.'],
                    ]);
                }
            }

            if ($account->role === UserRole::Admin && $requestedStatus === UserStatus::Inactive) {
                $activeAdministrators = User::query()
                    ->where('role', UserRole::Admin)
                    ->where('status', UserStatus::Active)
                    ->lockForUpdate()
                    ->get(['user_id']);

                if (! $activeAdministrators->has(1)) {
                    throw ValidationException::withMessages([
                        'status' => ['Pentadbir aktif terakhir tidak boleh dinyahaktifkan.'],
                    ]);
                }
            }

            if ($account->status === $requestedStatus) {
                throw ValidationException::withMessages([
                    'status' => ['Akaun tersebut sudah mempunyai status yang dipilih.'],
                ]);
            }

            if (! in_array($account->status, [UserStatus::Active, UserStatus::Inactive], true)) {
                throw ValidationException::withMessages([
                    'status' => ['Status akaun ini tidak boleh diubah melalui tindakan tersebut.'],
                ]);
            }

            $account->update(['status' => $requestedStatus]);

            if ($requestedStatus === UserStatus::Inactive) {
                DB::table('sessions')->where('user_id', $account->user_id)->delete();
            }
        });
    }
}
