<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'title' => rtrim(fake()->unique()->sentence(5), '.'),
            'content' => fake()->paragraphs(2, true),
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'theme_id' => Theme::inRandomOrder()->value('id') ?? Theme::factory(),
            'replied_to_id' => null,
        ];
    }

    public function replyTo(?Post $parent = null): static
    {
        return $this->state(function () use ($parent) {
            $parent = $parent ?: Post::inRandomOrder()->first();
            return [
                'replied_to_id' => $parent?->id,
                'theme_id' => $parent?->theme_id,
                'title' => 'Re: ' . ($parent?->title ?? fake()->sentence(3)),
            ];
        });
    }
}
