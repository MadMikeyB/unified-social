<?php

namespace Database\Factories;

use App\Models\Follow;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Follow>
 */
class FollowFactory extends Factory
{
    protected $model = Follow::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'follower_id' => Profile::factory(),
            'followee_id' => Profile::factory(),
            'status' => 'accepted',
        ];
    }

    /**
     * Set the follower and followee for the follow relationship.
     *
     * @param \App\Models\Profile $follower
     * @param \App\Models\Profile $followee
     * @return static
     */
    public function between(Profile $follower, Profile $followee): static
    {
        return $this->state([
            'follower_id' => $follower->id,
            'followee_id' => $followee->id,
        ]);
    }

    /**
     * Set the status of the follow relationship to pending.
     *
     * @return static
     */
    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }
}