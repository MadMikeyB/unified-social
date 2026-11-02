<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\Profile;
use App\Models\Reaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reaction>
 */
class ReactionFactory extends Factory
{
    protected $model = Reaction::class;

    /**
     * Define the model's default state: an upvote.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profile_id' => Profile::factory(),
            'post_id' => Post::factory(),
            'kind' => 'vote',
            'type' => null,
            'value' => 1,
        ];
    }

    /**
     * Set the reaction to be by a specific profile.
     *
     * @param  \App\Models\Profile  $profile
     * @return static
     */
    public function by(Profile $profile): static
    {
        return $this->state(['profile_id' => $profile->id]);
    }

    /**
     * Set the reaction to be on a specific post.
     *
     * @param  \App\Models\Post  $post
     * @return static
     */
    public function on(Post $post): static
    {
        return $this->state(['post_id' => $post->id]);
    }

    /**
     * Set the reaction to be an upvote.
     *
     * @return static
     */
    public function upvote(): static
    {
        return $this->state(['kind' => 'vote', 'type' => null, 'value' => 1]);
    }

    /**
     * Set the reaction to be a downvote.
     *
     * @return static
     */
    public function downvote(): static
    {
        return $this->state(['kind' => 'vote', 'type' => null, 'value' => -1]);
    }

    /**
     * Set the reaction to be a specific type of reaction.
     *
     * @param  string|null  $type
     * @return static
     */
    public function reaction(?string $type = null): static
    {
        return $this->state(fn () => [
            'kind' => 'reaction',
            'type' => $type ?? fake()->randomElement(['like', 'love', 'haha', 'wow', 'sad', 'angry']),
            'value' => 0,
        ]);
    }
}