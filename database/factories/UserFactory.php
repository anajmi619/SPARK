<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\Level;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_year_id' => null,
            'current_level_id' => null,
            'full_name' => fake()->name(),
            'username' => fake()->unique()->bothify('user_####'),
            'password_hash' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Admin,
            'kelas_id' => null,
            'status' => UserStatus::Active,
            'recovery_requested_at' => null,
            'remember_token' => null,
        ];
    }

    public function student(): static
    {
        return $this->state(fn (array $attributes): array => [
            'academic_year_id' => AcademicYear::factory(),
            'current_level_id' => Level::factory(),
            'role' => UserRole::Student,
            'kelas_id' => Kelas::factory(),
            'status' => UserStatus::Pending,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => UserStatus::Active,
        ]);
    }

    public function teacher(): static
    {
        return $this->state(fn (array $attributes): array => [
            'academic_year_id' => null,
            'current_level_id' => null,
            'role' => UserRole::Teacher,
            'kelas_id' => null,
            'status' => UserStatus::Active,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'academic_year_id' => null,
            'current_level_id' => null,
            'role' => UserRole::Admin,
            'kelas_id' => null,
            'status' => UserStatus::Active,
        ]);
    }
}
