<?php

namespace App\Http\Services;

use App\Models\Game;
use App\Models\Team;
use App\Repositories\GameRepository;

class GameSimulatorService
{
    public function __construct(private readonly GameRepository $gameRepository)
    {
    }

    public function simulateGame(Game $game): Game
    {
        /* @var Team $homeTeam */
        $homeTeam = $game->homeTeam;
        /* @var Team $awayTeam */
        $awayTeam = $game->awayTeam;

        $homePower = $this->calculatePower($homeTeam, true);
        $awayPower = $this->calculatePower($awayTeam, false);

        $total = $homePower + $awayPower;

        $homeWinProbability = ($homePower / $total);
        $awayWinProbability = ($awayPower / $total);

        $drawProbability = abs($homeWinProbability - $awayWinProbability) < 0.05 ? true : false;

        if ($drawProbability) {
            $game->home_team_goal = $game->away_team_goal = random_int(0, 4);
        } else {
            $winnerGoals = random_int(1, 4);
            $loserGoals = random_int(0, $winnerGoals - 1);

            $game->home_team_goal = $homePower > $awayPower ? $winnerGoals : $loserGoals;
            $game->away_team_goal = $awayPower > $homePower ? $winnerGoals : $loserGoals;
        }

        $game->is_played = true;

        $game->save();
        return $game;
    }

    public function calculatePower(Team $team, $isHome = false): int
    {
        $randomFactor = random_int(0, 100);

        $totalPower = (
            ($team->power * 0.6) +
            ($team->goalkeeper_power * 0.10) +
            ($team->supporter_power * 0.10) +
            ($randomFactor * 0.20)
        );

        if (!$isHome) {
            $totalPower *= 0.95;
        }

        return $totalPower;
    }

    public function playWeek(): array
    {
        $currentWeek = $this->getCurrentWeek();

        $games = $this->findGames($currentWeek);
        $playedGames = [];

        foreach ($games as $game) {
            $playedGames[] = $this->simulateGame($game);
        }

        return $playedGames;
    }

    public function playAll(): array
    {
        $games = $this->findGames();
        $playedGames = [];

        foreach ($games as $game) {
            $playedGames[] = $this->simulateGame($game);
        }

        return $playedGames;
    }

    public function findGames(?int $week = null)
    {
        return $this->gameRepository->findUnplayedGames($week);
    }

    public function getCurrentWeek()
    {
        return $this->gameRepository->findCurrentWeek();
    }
}
