<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
            'name' => fake()->name(),
            'employee_no' => fake()->unique()->bothify('EMP####'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'department' => fake()->randomElement(['Engineering', 'Marketing', 'HR', 'Finance', 'Operations']),
            'position' => fake()->randomElement(['Developer', 'Manager', 'Director', 'Analyst', 'Specialist']),
            'role' => 'student',
            'status' => 'active',
            'hire_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'trial_end_date' => null,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    public function mentor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'mentor',
        ]);
    }

    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'student',
        ]);
    }

    public function trialEmployee(): static
    {
        return $this->state(fn (array $attributes) => [
            'trial_end_date' => now()->addDays(30),
        ]);
    }

    public function permanentEmployee(): static
    {
        return $this->state(fn (array $attributes) => [
            'trial_end_date' => now()->subDays(30),
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
