<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('users')->where('role', 'student')->whereNull('kelas_id')->exists()) {
            throw new RuntimeException('Semua pelajar mesti dipadankan dengan kelas sebelum class_name dibuang.');
        }
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE users DROP CHECK chk_users_role_details');
            $this->addRoleConstraint('kelas_id');
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['academic_year_id', 'class_name', 'status']);
            $table->dropColumn('class_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('class_name', 50)->nullable();
            $table->index(['academic_year_id', 'class_name', 'status']);
        });
        foreach (DB::table('kelas')->get() as $kelas) {
            DB::table('users')->where('kelas_id', $kelas->id)->update(['class_name' => $kelas->nama]);
        }
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE users DROP CHECK chk_users_role_details');
            $this->addRoleConstraint('class_name');
        }
    }

    private function addRoleConstraint(string $column): void
    {
        DB::statement("ALTER TABLE users ADD CONSTRAINT chk_users_role_details CHECK (
            (role = 'student' AND academic_year_id IS NOT NULL AND current_level_id IS NOT NULL AND {$column} IS NOT NULL)
            OR (role = 'teacher_admin' AND academic_year_id IS NULL AND current_level_id IS NULL AND {$column} IS NULL)
        )");
    }
};
