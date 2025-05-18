<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = [

            [
                'name' => 'Liverpool',
                'power' => 90,
                'goalkeeper_power' => 86,
                'supporter_power' => 92
            ],
            [
                'name' => 'Manchester City',
                'power' => 95,
                'goalkeeper_power' => 90,
                'supporter_power' => 88
            ],
            [
                'name' => 'Chelsea',
                'power' => 82,
                'goalkeeper_power' => 80,
                'supporter_power' => 78
            ],
            [
                'name' => 'Arsenal',
                'power' => 88,
                'goalkeeper_power' => 82,
                'supporter_power' => 85
            ]
        ];

        foreach ($teams as $team) {
            Team::create($team);
        }
    }
}
