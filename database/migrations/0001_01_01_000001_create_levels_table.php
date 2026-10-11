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
        Schema::create('levels', function (Blueprint $table) {
            $table->tinyIncrements('level_id');
            $table->unsignedTinyInteger('level_number')->unique();
            $table->string('level_name', 50)->unique();
            $table->unsignedSmallInteger('minimum_points');
            $table->unsignedSmallInteger('maximum_points');
            $table->timestamps();
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE levels ADD CONSTRAINT chk_levels_number CHECK (level_number BETWEEN 1 AND 7)');
            DB::statement('ALTER TABLE levels ADD CONSTRAINT chk_levels_points CHECK (minimum_points <= maximum_points AND maximum_points <= 1200)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('levels');
    }
};
