<?php

namespace App\Http\Services;

use App\Models\Game;
use App\Models\Team;

class FixtureService
{
    public function generateFixtures()
    {
        $teams = Team::all();

        $teamIds = $teams->pluck('id')->toArray();
        $teamCount = count($teamIds);
        $totalRounds = $teamCount - 1;
        $matchesPerRound = $teamCount / 2;

        $homeTeams = $teamIds;
        $awayTeams = array_reverse($teamIds);

        $week = 1;

        for ($round = 0; $round < $totalRounds; $round++) {
            for ($i = 0; $i < $matchesPerRound; $i++) {
                $home = $homeTeams[$i];
                $away = $awayTeams[$i];

                if ($home !== $away) {
                    // İlk yarı
                    Game::create([
                        'home_team_id' => $home,
                        'away_team_id' => $away,
                        'week' => $week,
                    ]);

                    // İkinci yarı (rövanş)
                    Game::create([
                        'home_team_id' => $away,
                        'away_team_id' => $home,
                        'week' => $week + $totalRounds,
                    ]);
                }
            }

            $week++;

            // Round-robin rotasyonu
            $last = array_pop($homeTeams);
            array_splice($homeTeams, 1, 0, [$last]);
            $awayTeams = array_reverse($homeTeams);
        }
    }

    public function getFixtures(?int $week = null): \Illuminate\Database\Eloquent\Collection
    {
        $gameCount = Game::all()->count();

        if ($gameCount === 0) {
            $this->generateFixtures();
        }

        return Game::with(['homeTeam', 'awayTeam'])
            ->when($week, function ($query, $week) {
                return $query->where('week', $week);
            })
            ->orderBy('week')->get();
    }

    public function resetData(): void
    {
        Game::played()->update([
            'home_team_goal' => null,
            'away_team_goal' => null,
            'is_played' => false,
        ]);
    }

}
