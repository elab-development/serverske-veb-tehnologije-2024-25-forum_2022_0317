<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $themes = [
            [
                'name' => 'Laravel & PHP',
                'description' => 'Pitanja, saveti i najbolje prakse za Laravel i PHP ekosistem.'
            ],
            [
                'name' => 'Frontend (React, Vue)',
                'description' => 'UI biblioteke, performanse, alatke i trendovi.'
            ],
            [
                'name' => 'DevOps & Cloud',
                'description' => 'Docker, CI/CD, orkestracija, AWS/Azure/GCP.'
            ],
            [
                'name' => 'Karijera & Intervjui',
                'description' => 'Portfolija, priprema za razgovore, saveti iz prakse.'
            ],
            [
                'name' => 'Opšta diskusija',
                'description' => 'Van-tematske priče i community teme.'
            ],
        ];

        foreach ($themes as $t) {
            Theme::firstOrCreate(['name' => $t['name']], $t);
        }

        Theme::factory()->count(3)->create();
    }
}
