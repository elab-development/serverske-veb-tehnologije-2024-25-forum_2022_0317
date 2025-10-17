<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $publishableUsers = User::where('can_publish', true)->pluck('id')->all();

        if (empty($publishableUsers)) {
            return;
        }

        Theme::all()->each(function ($theme) use ($publishableUsers) {
            $topCount = random_int(6, 10);

            $topPosts = collect(range(1, $topCount))->map(function () use ($theme, $publishableUsers) {
                return Post::factory()->create([
                    'theme_id' => $theme->id,
                    'user_id' => collect($publishableUsers)->random(),
                    'title' => fake()->randomElement([
                        'Kako optimalno strukturisati Laravel projekat?',
                        'Iskustva sa Inertia vs Livewire',
                        'Saveti za pripremu tehničkog intervjua',
                        'Docker Compose best practices',
                        'Koji je najveći izazov u React performansama?',
                        'CI/CD pipeline – odakle krenuti?',
                        'Prvi posao u IT – kako do ponude?',
                        'Testing strategije u Laravelu (Pest/Vitest)',
                        'Vite i Laravel Mix – šta birate i zašto?',
                        'Refaktorisanje legacy koda – iskustva',
                    ]),
                    'content' => fake()->randomElement([
                        'Delim iskustva i voleo bih da čujem vaše savete i alate koje koristite.',
                        'Koje paterne koristite za servise i repozitorijume? Šta se pokazalo najbolje u praksi?',
                        'Kako pristupate testiranju – da li koristite TDD ili više integration/e2e?',
                        'Koji su vam omiljeni paketi i zbog čega?',
                        'Imate li preporuku za resurse i kurseve?'
                    ]),
                ]);
            });

            $topPosts->random(max(1, (int) round($topPosts->count() * fake()->randomFloat(2, 0.5, 0.7))))
                ->each(function (Post $parent) use ($publishableUsers) {
                    $repliesCount = random_int(1, 3);
                    for ($i = 0; $i < $repliesCount; $i++) {
                        Post::factory()
                            ->replyTo($parent)
                            ->create([
                                'user_id' => collect($publishableUsers)->random(),
                                'content' => fake()->randomElement([
                                    'Slažem se, dodatno bih preporučio da podeliš strukturu foldera.',
                                    'Proveri i opterećenje u CI pipeline-u, caching može dosta da pomogne.',
                                    'Dobar savet! Dodao bih i preporuku za kod review checklistu.',
                                    'Možda bi vredi pogledati i alternativni pristup sa feature flagovima.',
                                ]),
                            ]);
                    }
                });
        });
    }
}
