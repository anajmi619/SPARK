<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $legacyAdministratorIds = DB::table('users')
                ->where('role', 'admin')
                ->orderBy('user_id')
                ->pluck('user_id');

            $teacherIds = $legacyAdministratorIds->slice(1)->values();

            if ($teacherIds->isEmpty()) {
                return;
            }

            DB::table('users')->whereIn('user_id', $teacherIds)->update(['role' => 'teacher']);

            $classIds = DB::table('kelas')->pluck('id');
            $now = now();
            $assignments = $teacherIds->crossJoin($classIds)
                ->map(fn (array $ids): array => [
                    'teacher_id' => $ids[0],
                    'kelas_id' => $ids[1],
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

            DB::table('kelas_teacher')->insertOrIgnore($assignments);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This legacy-account classification cannot be reversed safely because
        // later teacher and administrator accounts are indistinguishable here.
    }
};
