<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE users DROP CHECK chk_users_role_details');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->change();
        });

        DB::table('users')->where('role', 'teacher_admin')->update(['role' => 'admin']);

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement(<<<'SQL'
                ALTER TABLE users ADD CONSTRAINT chk_users_role_details CHECK (
                    (role = 'student'
                        AND academic_year_id IS NOT NULL
                        AND current_level_id IS NOT NULL
                        AND kelas_id IS NOT NULL)
                    OR
                    (role IN ('teacher', 'admin')
                        AND academic_year_id IS NULL
                        AND current_level_id IS NULL
                        AND kelas_id IS NULL)
                )
                SQL);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE users DROP CHECK chk_users_role_details');
        }

        DB::table('users')->whereIn('role', ['teacher', 'admin'])->update(['role' => 'teacher_admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->change();
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement(<<<'SQL'
                ALTER TABLE users ADD CONSTRAINT chk_users_role_details CHECK (
                    (role = 'student'
                        AND academic_year_id IS NOT NULL
                        AND current_level_id IS NOT NULL
                        AND kelas_id IS NOT NULL)
                    OR
                    (role = 'teacher_admin'
                        AND academic_year_id IS NULL
                        AND current_level_id IS NULL
                        AND kelas_id IS NULL)
                )
                SQL);
        }
    }
};
