<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

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
            'email_verified_at' => fake()->boolean(70) ? now() : null, // 70% verified, 30% unverified
            'password' => static::$password ??= Hash::make('12345678'),
            'active' => true,
            'remember_token' => Str::random(10),
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
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
    
    /**
     * Configure the model factory to assign a random role to users.
     */
    public function withRandomRole(): static
    {
        return $this->afterCreating(function (User $user) {
            // Get all role names except 'admin' (since we don't want too many admins)
            $availableRoles = ['editor', 'viewer', 'user'];
            
            // Randomly decide if this user should have a role (80% chance)
            if (fake()->boolean(80)) {
                // Assign a random role from available roles
                $roleName = fake()->randomElement($availableRoles);
                $user->assignRole($roleName);
            }
        });
    }
}
