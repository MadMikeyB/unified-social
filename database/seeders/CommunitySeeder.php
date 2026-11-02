<?php

namespace Database\Seeders;

use App\Models\Pin;
use App\Models\Post;
use App\Models\Space;
use App\Models\Profile;

class CommunitySeeder extends SocialSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $profiles = Profile::all();
        $names = ['laravel', 'webdev', 'pcgaming', 'selfhosted', 'mechanicalkeyboards', 'minecraft', 'destiny2', 'escapefromtarkov'];

        $communities = Space::factory()
            ->count(count($names))
            ->sequence(fn ($sequence) => [
                'name' => $names[$sequence->index],
                'slug' => $names[$sequence->index],
            ])
            ->create();

        $allPosts = collect();

        foreach ($communities as $community) {
            $members = $profiles->random(rand(10, 30));

            $community->members()->attach(
                $members->mapWithKeys(fn (Profile $member) => [
                    $member->id => ['role' => 'member', 'status' => 'accepted'],
                ])->all()
            );

            $community->members()->updateExistingPivot($members->first()->id, ['role' => 'moderator']);

            $posts = collect();

            for ($i = 0, $n = rand(8, 15); $i < $n; $i++) {
                $post = Post::factory()
                    ->thread($community)
                    ->by($members->random())
                    ->from('redditclone')
                    ->create($this->stamps($this->pastMoment(30)));

                // each comment answers the post or any earlier comment, which builds a tree
                $tree = collect([$post]);

                for ($j = 0, $m = rand(0, 25); $j < $m; $j++) {
                    $parent = $tree->random();

                    $tree->push(Post::factory()
                        ->reply($parent)
                        ->by($profiles->random())
                        ->from('redditclone')
                        ->create($this->stamps($this->after($parent->created_at, 240))));
                }

                $this->settleActivity($post);

                $tree->each(fn (Post $item) => $this->vote($item, $profiles));

                $posts->push($post);
            }

            Pin::factory()->by($community)->of($posts->random())->pinned()->create();

            $allPosts = $allPosts->merge($posts);
        }

        foreach ($allPosts->random(6) as $original) {
            Post::factory()
                ->repost($original, $communities->except($original->space_id)->random())
                ->by($profiles->random())
                ->from('redditclone')
                ->create($this->stamps($this->after($original->created_at, 2880)));
        }
    }
}
