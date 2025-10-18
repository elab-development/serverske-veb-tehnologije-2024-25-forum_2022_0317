<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Forum Admin',
            'email' => 'admin@forum.local',
            'password' => Hash::make('password'),
            'can_publish' => true,
        ]);

        User::factory()->count(3)->moderator()->create();

        User::factory()->count(20)->create();
        User::factory()->count(3)->blockedFromPublishing()->create();

        $this->call([
            ThemeSeeder::class,
            PostSeeder::class,
            LikeSeeder::class,
        ]);
    }
}
