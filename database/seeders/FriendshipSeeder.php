<?php

namespace Database\Seeders;


use App\Models\Post;
use App\Models\Space;
use App\Models\Profile;

class FriendshipSeeder extends SocialSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // private profiles are left out: see the note on follow() and requestFollow()
        $public = Profile::where('is_private', false)->get();

        foreach ($public as $profile) {
            foreach ($public->except($profile->id)->random(rand(3, 8)) as $other) {
                $profile->requestFollow($other);
                $other->acceptFollower($profile, reciprocate: true);
            }
        }

        foreach ($public as $profile) {
            $friends = $profile->friends()->get();

            if ($friends->isEmpty()) {
                continue;
            }

            $circle = $friends->push($profile);

            for ($i = 0, $n = rand(0, 4); $i < $n; $i++) {
                $post = Post::factory()
                    ->onWallOf($profile)
                    ->by(rand(0, 1) ? $profile : $friends->random())
                    ->visibility('friends')
                    ->from('facebookclone')
                    ->create($this->stamps($this->pastMoment(60)));

                $this->discuss($post, $circle, 'facebookclone', 6);
                $this->react($post, $circle);
            }
        }

        for ($i = 0; $i < 3; $i++) {
            $creator = $public->random();
            $group = Space::factory()->group($creator)->create();
            $members = $public->except($creator->id)->random(rand(5, 15));

            $group->members()->attach(
                [$creator->id => ['role' => 'admin', 'status' => 'accepted']]
                + $members->mapWithKeys(fn (Profile $member) => [
                    $member->id => ['role' => 'member', 'status' => 'accepted'],
                ])->all()
            );

            $everyone = $members->push($creator);

            for ($j = 0, $n = rand(4, 10); $j < $n; $j++) {
                $post = Post::factory()
                    ->inSpace($group)
                    ->by($everyone->random())
                    ->visibility('space')
                    ->from('facebookclone')
                    ->create($this->stamps($this->pastMoment(60)));

                $this->discuss($post, $everyone, 'facebookclone', 8);
                $this->react($post, $everyone);
            }
        }
    }
}
