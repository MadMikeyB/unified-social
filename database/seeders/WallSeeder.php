<?php

namespace Database\Seeders;

use App\Models\Pin;
use App\Models\Post;
use App\Models\Profile;

class WallSeeder extends SocialSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Profile::all() as $profile) {
            $circle = $profile->friends()->get();

            if ($circle->isEmpty()) {
                $circle = $profile->following()->get();
            }

            if ($circle->isEmpty()) {
                continue;
            }

            foreach ($circle->shuffle()->take(8)->values() as $position => $featured) {
                Pin::factory()->by($profile)->of($featured)->featured()->position($position)->create();
            }

            for ($i = 0, $n = rand(0, 6); $i < $n; $i++) {
                Post::factory()
                    ->onWallOf($profile)
                    ->by($circle->random())
                    ->from('myspaceclone')
                    ->create($this->stamps($this->pastMoment()));
            }

            $profile->update([
                'settings' => array_merge($profile->settings ?? [], [
                    'myspace' => ['theme' => fake()->randomElement(['classic', 'midnight', 'bubblegum', 'forest'])],
                ]),
            ]);
        }
    }
}
