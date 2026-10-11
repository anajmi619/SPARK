<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
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
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->unsignedTinyInteger('current_level_id')->nullable();
            $table->string('full_name', 100);
            $table->string('username', 50)->unique();
            $table->string('password_hash');
            $table->enum('role', array_column(UserRole::cases(), 'value'));
            $table->string('class_name', 50)->nullable();
            $table->enum('status', array_column(UserStatus::cases(), 'value'))->default(UserStatus::Pending->value);
            $table->dateTime('recovery_requested_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('academic_year_id')
                ->references('academic_year_id')
                ->on('academic_years')
                ->restrictOnDelete();
            $table->foreign('current_level_id')
                ->references('level_id')
                ->on('levels')
                ->restrictOnDelete();
            $table->index(['academic_year_id', 'class_name', 'status']);
            $table->index(['role', 'status']);
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement(<<<'SQL'
                ALTER TABLE users ADD CONSTRAINT chk_users_role_details CHECK (
                    (role = 'student'
                        AND academic_year_id IS NOT NULL
                        AND current_level_id IS NOT NULL
                        AND class_name IS NOT NULL)
                    OR
                    (role = 'teacher_admin'
                        AND academic_year_id IS NULL
                        AND current_level_id IS NULL
                        AND class_name IS NULL)
                )
                SQL);
        }

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
