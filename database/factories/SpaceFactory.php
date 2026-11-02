<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\Space;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Space>
 */
class SpaceFactory extends Factory
{
    protected $model = Space::class;

    /**
     * Define the model's default state: a top-level community.
     * Walls aren't made here, a profile creates its own.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(fake()->numberBetween(1, 3), true));

        return [
            'parent_id' => null,
            'owner_type' => null,
            'owner_id' => null,
            'kind' => 'community',
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'description' => fake()->sentence(),
            'visibility' => 'public',
            'requires_approval' => false,
            'position' => 0,
            'settings' => null,
        ];
    }

    /**
     * Set the space to be a category.
     *
     * @return static
     */
    public function category(): static
    {
        return $this->state(['kind' => 'category']);
    }

    /**
     * Set the space to be members-only.
     *
     * @return static
     */
    public function board(Space $category): static
    {
        return $this->state([
            'kind' => 'board',
            'parent_id' => $category->id,
        ]);
    }

    /**
     * Set the space to be members-only.
     *
     * @return static
     */
    public function group(Profile $creator): static
    {
        return $this->state([
            'kind' => 'group',
            'owner_type' => $creator->getMorphClass(),
            'owner_id' => $creator->id,
            'visibility' => 'members',
            'requires_approval' => true,
        ]);
    }

    /**
     * Set the space to be members-only.
     *
     * @return static
     */
    public function membersOnly(): static
    {
        return $this->state(['visibility' => 'members']);
    }

    /**
     * Set the space to require approval for new members.
     *
     * @return static
     */
    public function private(): static
    {
        return $this->state(['visibility' => 'private', 'requires_approval' => true]);
    }

    /**
     * Set the position of the space.
     *
     * @param  int  $position
     * @return static
     */
    public function position(int $position): static
    {
        return $this->state(['position' => $position]);
    }
}