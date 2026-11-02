<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Profile;

class ProfileSeeder extends SocialSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()
            ->withProfile(['handle' => 'mikey', 'display_name' => 'Mikey'])
            ->create(['name' => 'Mikey', 'email' => 'mikey@mikey.app']);

        User::factory()->count(40)->withProfile()->create();
        User::factory()->count(6)->withProfile(['is_private' => true])->create();
    }
}
