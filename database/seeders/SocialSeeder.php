<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Profile;
use App\Models\Reaction;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Collection;

abstract class SocialSeeder extends Seeder
{
    /**
     * A random moment in the past few months.
     */
    protected function pastMoment(int $days = 90): CarbonInterface
    {
        return now()->subMinutes(rand(60, $days * 1440));
    }

    /**
     * A moment shortly after another one, never in the future.
     */
    protected function after(CarbonInterface $moment, int $maxMinutes = 720): CarbonInterface
    {
        return $moment->copy()->addMinutes(rand(2, $maxMinutes))->min(now());
    }

    /**
     * Helper to create the timestamps various models need.
     * 
     * @return array<string, \Carbon\CarbonInterface>
     */
    protected function stamps(CarbonInterface $at): array
    {
        return [
            'created_at' => $at,
            'updated_at' => $at,
            'last_activity_at' => $at,
        ];
    }

    /**
     * Replies bump last_activity_at to `now();` put it back to the latest reply.
     */
    protected function settleActivity(Post $root): void
    {
        Post::whereKey($root->id)->update([
            'last_activity_at' => Post::where('root_id', $root->id)->max('created_at') ?? $root->created_at,
        ]);
    }

    /**
     * Flat replies straight to the root post.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\Profile>  $authors
     * @return \Illuminate\Support\Collection<int, \App\Models\Post>
     */
    protected function discuss(Post $root, Collection $authors, string $client, int $max): \Illuminate\Support\Collection
    {
        $replies = collect();
        $at = $root->created_at;

        for ($i = 0, $n = rand(0, $max); $i < $n; $i++) {
            $at = $this->after($at);

            $replies->push(Post::factory()
                ->reply($root)
                ->by($authors->random())
                ->from($client)
                ->create($this->stamps($at)));
        }

        $this->settleActivity($root);

        return $replies;
    }

    /**
     * Create a few votes on a post.
     * 
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\Profile>  $profiles
     */
    protected function vote(Post $post, Collection $profiles, int $max = 15): void
    {
        foreach ($profiles->random(rand(0, min($max, $profiles->count()))) as $voter) {
            Reaction::factory()
                ->on($post)
                ->by($voter)
                ->when(rand(0, 4) === 0, fn ($factory) => $factory->downvote())
                ->create();
        }
    }

    /**
     * Create a few reactions on a post.
     * 
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\Profile>  $profiles
     */
    protected function react(Post $post, Collection $profiles, int $max = 10): void
    {
        foreach ($profiles->random(rand(0, min($max, $profiles->count()))) as $profile) {
            Reaction::factory()->reaction()->on($post)->by($profile)->create();
        }
    }
}