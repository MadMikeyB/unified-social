<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Space;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    /**
     * Define the model's default state: an image attached to a new post.
     * Rows only, no file is written to the disk.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profile_id' => Profile::factory(),
            'mediable_type' => (new Post)->getMorphClass(),
            'mediable_id' => Post::factory(),
            'role' => 'attachment',
            'disk' => 'public',
            'path' => 'media/'.Str::uuid().'.jpg',
            'mime' => 'image/jpeg',
            'alt' => fake()->sentence(6),
            'position' => 0,
        ];
    }

    /**
     * Set the media to be attached to a specific post.
     *
     * @param  \App\Models\Post  $post
     * @return static
     */
    public function attachedTo(Post $post): static
    {
        return $this->state([
            'profile_id' => $post->profile_id,
            'mediable_type' => $post->getMorphClass(),
            'mediable_id' => $post->id,
            'role' => 'attachment',
        ]);
    }

    /**
     * Set the media to be the avatar of a profile.
     *
     * @param  \App\Models\Profile  $profile
     * @return static
     */
    public function avatar(Profile $profile): static
    {
        return $this->forProfile($profile, 'avatar');
    }

    /**
     * Set the media to be the banner of a profile.
     *
     * @param  \App\Models\Profile  $profile
     * @return static
     */
    public function banner(Profile $profile): static
    {
        return $this->forProfile($profile, 'banner');
    }

    /**
     * Set the media to be the icon of a space.
     *
     * @param  \App\Models\Space  $space
     * @param  \App\Models\Profile  $uploader
     * @return static
     */
    public function icon(Space $space, Profile $uploader): static
    {
        return $this->state([
            'profile_id' => $uploader->id,
            'mediable_type' => $space->getMorphClass(),
            'mediable_id' => $space->id,
            'role' => 'icon',
        ]);
    }

    /**
     * Set the position of the media.
     *
     * @param  int  $position
     * @return static
     */
    public function position(int $position): static
    {
        return $this->state(['position' => $position]);
    }

    /**
     * Set the media to be for a specific profile and role.
     *
     * @param  \App\Models\Profile  $profile
     * @param  string  $role
     * @return static
     */
    protected function forProfile(Profile $profile, string $role): static
    {
        return $this->state([
            'profile_id' => $profile->id,
            'mediable_type' => $profile->getMorphClass(),
            'mediable_id' => $profile->id,
            'role' => $role,
        ]);
    }
}