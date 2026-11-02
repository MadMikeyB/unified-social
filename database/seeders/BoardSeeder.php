<?php

namespace Database\Seeders;

use App\Models\Pin;
use App\Models\Post;
use App\Models\Space;
use App\Models\Profile;
use Illuminate\Support\Str;

class BoardSeeder extends SocialSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $profiles = Profile::all();

        $categories = [
            'General' => ['Announcements', 'Introductions', 'Off Topic'],
            'Gaming' => ['PC Gaming', 'Console', 'Modding'],
            'Development' => ['Laravel', 'Frontend', 'Servers'],
        ];

        $categoryPosition = 0;

        foreach ($categories as $categoryName => $boardNames) {
            $category = Space::factory()
                ->category()
                ->position($categoryPosition++)
                ->create(['name' => $categoryName, 'slug' => Str::slug($categoryName)]);

            foreach ($boardNames as $boardPosition => $boardName) {
                $board = Space::factory()
                    ->board($category)
                    ->position($boardPosition)
                    ->create(['name' => $boardName, 'slug' => Str::slug($categoryName.' '.$boardName)]);

                $threads = collect();

                for ($i = 0, $n = rand(6, 12); $i < $n; $i++) {
                    $at = $this->pastMoment();

                    $thread = Post::factory()
                        ->thread($board)
                        ->by($profiles->random())
                        ->from('forumclone')
                        ->create($this->stamps($at));

                    $replies = collect();

                    for ($j = 0, $m = rand(0, 20); $j < $m; $j++) {
                        $at = $this->after($at, 1440);

                        // the real parent is stored; the forum front end flattens it
                        $quoted = $replies->isNotEmpty() && rand(0, 4) === 0 ? $replies->random() : null;

                        $replies->push(Post::factory()
                            ->reply($quoted ?? $thread)
                            ->when($quoted, fn ($factory) => $factory->quote($quoted))
                            ->by($profiles->random())
                            ->from('forumclone')
                            ->create($this->stamps($at)));
                    }

                    $this->settleActivity($thread);

                    if (rand(0, 9) === 0) {
                        Post::whereKey($thread->id)->update(['is_locked' => true]);
                    }

                    $threads->push($thread);
                }

                Pin::factory()->by($board)->of($threads->first())->pinned()->create();
            }
        }
    }
}
