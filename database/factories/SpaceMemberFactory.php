<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\Space;
use App\Models\SpaceMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SpaceMember>
 */
class SpaceMemberFactory extends Factory
{
    protected $model = SpaceMember::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'space_id' => Space::factory(),
            'profile_id' => Profile::factory(),
            'role' => 'member',
            'status' => 'accepted',
        ];
    }

    /**
     * Set the space and profile for the space member.
     *
     * @param \App\Models\Space $space
     * @param \App\Models\Profile $profile
     * @return static
     */
    public function of(Space $space, Profile $profile): static
    {
        return $this->state([
            'space_id' => $space->id,
            'profile_id' => $profile->id,
        ]);
    }

    /**
     * Set the role of the space member to moderator.
     *
     * @return static
     */
    public function moderator(): static
    {
        return $this->state(['role' => 'moderator']);
    }

    /**
     * Set the status of the space member to pending.
     *
     * @return static
     */
    public function admin(): static
    {
        return $this->state(['role' => 'admin']);
    }

    /**
     * Set the status of the space member to pending.
     *
     * @return static
     */
    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    /**
     * Set the status of the space member to banned.
     *
     * @return static
     */
    public function banned(): static
    {
        return $this->state(['status' => 'banned']);
    }
}