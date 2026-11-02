<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Profile>
 */
class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    /**
     * Define the model's default state. The wall space is created by the model's own hook.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'handle' => str(fake()->unique()->userName())->replace('.', '_')->lower()->toString(),
            'display_name' => fake()->name(),
            'bio' => fake()->optional(0.7)->sentence(12),
            'is_private' => false,
            'settings' => null,
        ];
    }

    /**
     * Set the profile to be private.
     *
     * @return static
     */
    public function private(): static
    {
        return $this->state(['is_private' => true]);
    }

    /**
     * Set the settings for a specific front-end of the profile.
     * 
     * @param  array<string, mixed>  $settings
     */
    public function withSettings(string $frontEnd, array $settings): static
    {
        return $this->state(fn (array $attributes) => [
            'settings' => array_merge($attributes['settings'] ?? [], [$frontEnd => $settings]),
        ]);
    }
}