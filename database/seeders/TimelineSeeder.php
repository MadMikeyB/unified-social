<?php

namespace Database\Seeders;

use App\Models\Pin;
use App\Models\Post;
use App\Models\Profile;

class TimelineSeeder extends SocialSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $profiles = Profile::all();

        // private profiles end up with pending requests
        foreach ($profiles as $profile) {
            foreach ($profiles->except($profile->id)->random(rand(5, 15)) as $other) {
                $profile->follow($other);
            }
        }

        $posts = collect();

        foreach ($profiles as $profile) {
            for ($i = 0, $n = rand(0, 8); $i < $n; $i++) {
                $posts->push(Post::factory()
                    ->by($profile)
                    ->from('twitterclone')
                    ->create($this->stamps($this->pastMoment())));
            }
        }

        // reply chains: most replies answer the previous one, some branch back to the start
        foreach ($posts->random(min(40, $posts->count())) as $post) {
            $parent = $post;

            for ($i = 0, $n = rand(1, 5); $i < $n; $i++) {
                $reply = Post::factory()
                    ->reply($parent)
                    ->by($profiles->random())
                    ->from('twitterclone')
                    ->create($this->stamps($this->after($parent->created_at)));

                $parent = rand(0, 3) ? $reply : $post;
            }

            $this->settleActivity($post);
        }

        foreach ($posts->random(min(25, $posts->count())) as $original) {
            Post::factory()
                ->repost($original)
                ->by($profiles->except($original->profile_id)->random())
                ->from('twitterclone')
                ->create($this->stamps($this->after($original->created_at, 2880)));
        }

        foreach ($posts->random(min(12, $posts->count())) as $original) {
            Post::factory()
                ->quote($original)
                ->by($profiles->except($original->profile_id)->random())
                ->from('twitterclone')
                ->create($this->stamps($this->after($original->created_at, 2880)));
        }

        foreach ($profiles->random(10) as $profile) {
            $own = $posts->where('profile_id', $profile->id);

            if ($own->isNotEmpty()) {
                Pin::factory()->by($profile)->of($own->random())->pinned()->create();
            }
        }
    }
}
