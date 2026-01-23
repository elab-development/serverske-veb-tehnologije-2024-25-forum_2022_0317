<?php

namespace Database\Seeders;

use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LikeSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::pluck('id')->all();

        Post::chunk(500, function ($posts) use ($userIds) {
            foreach ($posts as $post) {
                $likeCount = random_int(0, min(10, count($userIds)));
                $likers = collect($userIds)->shuffle()->take($likeCount);

                DB::transaction(function () use ($likers, $post) {
                    foreach ($likers as $uid) {
                        Like::firstOrCreate([
                            'user_id' => $uid,
                            'post_id' => $post->id,
                        ]);
                    }
                });
            }
        });
    }
}
