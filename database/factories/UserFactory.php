<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
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
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= 'password',
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
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

    /**
     * Indicate that the model does not have two-factor authentication configured.
     */
    public function withoutTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    /**
     * Create a learner user.
     */
    public function learner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'learner',
        ]);
    }

    /**
     * Create a content manager user.
     */
    public function contentManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'content_manager',
        ]);
    }

    /**
     * Create a trainer user.
     */
    public function trainer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'trainer',
        ]);
    }

    /**
     * Create an LMS admin user.
     */
    public function lmsAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'lms_admin',
        ]);
    }

    /**
     * Create a compliance officer user.
     */
    public function complianceOfficer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'compliance_officer',
        ]);
    }

    /**
     * Create an auditor user.
     */
    public function auditor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'auditor',
        ]);
    }

    /**
     * Create a teaching assistant user.
     */
    public function teachingAssistant(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'teaching_assistant',
        ]);
    }
}
