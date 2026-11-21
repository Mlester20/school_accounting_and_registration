<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\App\Models\User>
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => 'USR-' . Str::upper(Str::random(8)),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'status' => 'active',
            'must_change_password' => true,
            'last_login_at' => null,
        ];
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => 'ADMIN-' . Str::upper(Str::random(4)),
        ])->afterCreating(function (User $user) {
            $user->assignRole(UserRole::Admin->value);
        });
    }

    /**
     * Indicate that the user is a registrar.
     */
    public function registrar(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => 'REG-' . Str::upper(Str::random(4)),
        ])->afterCreating(function (User $user) {
            $user->assignRole(UserRole::Registrar->value);
        });
    }

    /**
     * Indicate that the user is accounting.
     */
    public function accounting(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => 'ACC-' . Str::upper(Str::random(4)),
        ])->afterCreating(function (User $user) {
            $user->assignRole(UserRole::Accounting->value);
        });
    }

    /**
     * Indicate that the user is a faculty.
     */
    public function faculty(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => 'FAC-' . Str::upper(Str::random(4)),
        ])->afterCreating(function (User $user) {
            $user->assignRole(UserRole::Faculty->value);
        });
    }

    /**
     * Indicate that the user is a student.
     */
    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => 'STU-' . Str::upper(Str::random(4)),
        ])->afterCreating(function (User $user) {
            $user->assignRole(UserRole::Student->value);
        });
    }
}