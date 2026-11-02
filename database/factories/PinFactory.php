<?php

namespace Database\Factories;

use App\Models\Pin;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Space;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pin>
 */
class PinFactory extends Factory
{
    protected $model = Pin::class;

    /**
     * Define the model's default state: a profile pinning a post.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pinner_type' => (new Profile)->getMorphClass(),
            'pinner_id' => Profile::factory(),
            'pinnable_type' => (new Post)->getMorphClass(),
            'pinnable_id' => Post::factory(),
            'kind' => 'pinned',
            'position' => 0,
        ];
    }

    /**
     * Set the pin to be by a specific profile or space.
     *
     * @param  \App\Models\Profile|\App\Models\Space  $pinner
     * @return static
     */
    public function by(Profile|Space $pinner): static
    {
        return $this->state([
            'pinner_type' => $pinner->getMorphClass(),
            'pinner_id' => $pinner->getKey(),
        ]);
    }

    /**
     * Set the pin to be for a specific post or profile.
     *
     * @param  \App\Models\Post|\App\Models\Profile  $pinnable
     * @return static
     */
    public function of(Post|Profile $pinnable): static
    {
        return $this->state([
            'pinnable_type' => $pinnable->getMorphClass(),
            'pinnable_id' => $pinnable->getKey(),
        ]);
    }

    /**
     * Set the pin to be pinned. lol.
     *
     * @return static
     */
    public function pinned(): static
    {
        return $this->state(['kind' => 'pinned']);
    }

    /**
     * Set the pin to be featured.
     *
     * @return static
     */
    public function featured(): static
    {
        return $this->state(['kind' => 'featured']);
    }

    /**
     * Set the position of the pin.
     *
     * @param  int  $position
     * @return static
     */
    public function position(int $position): static
    {
        return $this->state(['position' => $position]);
    }
}