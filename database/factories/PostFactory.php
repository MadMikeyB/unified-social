<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\Profile;
use App\Models\Space;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * Define the model's default state: a standalone post with no space and no title.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profile_id' => Profile::factory(),
            'space_id' => null,
            'parent_id' => null,
            'root_id' => null,
            'quoted_post_id' => null,
            'reposted_post_id' => null,
            'title' => null,
            'slug' => fn (array $attributes) => $attributes['title'] ? Str::slug($attributes['title']) : null,
            'body' => fake()->realText(fake()->numberBetween(60, 280)),
            'visibility' => 'public',
            'client' => null,
            'is_locked' => false,
        ];
    }

    /**
     * Set the post to be by a specific profile.
     *
     * @param  \App\Models\Profile  $profile
     * @return static
     */
    public function by(Profile $profile): static
    {
        return $this->state(['profile_id' => $profile->id]);
    }

    /**
     * Set the post to be in a specific space.
     *
     * @param  \App\Models\Space  $space
     * @return static
     */
    public function inSpace(Space $space): static
    {
        return $this->state(['space_id' => $space->id]);
    }

    /**
     * Set the post to be a titled root post: a forum thread, a subreddit post, a group post
     *
     * @param  \App\Models\Space  $space
     * @return static
     */
    public function thread(Space $space): static
    {
        return $this->state([
            'space_id' => $space->id,
            'title' => rtrim(fake()->sentence(fake()->numberBetween(4, 10)), '.'),
            'body' => implode("\n\n", fake()->paragraphs(fake()->numberBetween(1, 4))),
        ]);
    }

    /**
     * Set the post to be a reply to another post. root_id and space_id are filled in by the model from the parent
     *
     * @param  \App\Models\Post  $parent
     * @return static
     */
    public function reply(Post $parent): static
    {
        return $this->state([
            'parent_id' => $parent->id,
            'title' => null,
        ]);
    }

    /**
     * Set the post to be a repost of another post. If a space is given, it will be a crosspost.
     *
     * @param  \App\Models\Post  $original
     * @param  \App\Models\Space|null  $into
     * @return static
     */
    public function repost(Post $original, ?Space $into = null): static
    {
        return $this->state([
            'reposted_post_id' => $original->id,
            'space_id' => $into?->id,
            'title' => $into ? $original->title : null,
            'body' => null,
        ]);
    }

    /**
     * Set the post to be a quote of another post. If a space is given, it will be a crosspost.
     *
     * @param  \App\Models\Post  $original
     * @param  \App\Models\Space|null  $into
     * @return static
     */
    public function quote(Post $original): static
    {
        return $this->state(['quoted_post_id' => $original->id]);
    }

    /**
     * Set the post to be on a specific profile's wall.
     *
     * @param  \App\Models\Profile  $owner
     * @return static
     */
    public function onWallOf(Profile $owner): static
    {
        return $this->state(fn () => ['space_id' => $owner->wall()->value('id')]);
    }

    /**
     * Set the visibility of the post.
     *
     * @param  string  $visibility
     * @return static
     */
    public function visibility(string $visibility): static
    {
        return $this->state(['visibility' => $visibility]);
    }

    /**
     * Set the post to be locked.
     *
     * @return static
     */
    public function locked(): static
    {
        return $this->state(['is_locked' => true]);
    }

    /**
     * Set the client for the post.
     *
     * @param  string  $client
     * @return static
     */
    public function from(string $client): static
    {
        return $this->state(['client' => $client]);
    }
}