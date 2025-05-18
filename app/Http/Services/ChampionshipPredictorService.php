<?php

namespace App\Http\Services;

use App\Models\Game;
use App\Models\Team;
use App\Repositories\GameRepository;

class ChampionshipPredictorService
{
    private const WEIGHT_POWER = "0.5";
    private const WEIGHT_GOAL_DIFF = "0.3";
    private const WEIGHT_POINTS = "0.2";

    public function __construct(
        private readonly GameSimulatorService $gameSimulatorService,
        private readonly GameRepository       $gameRepository,
    )
    {
    }

    public function predictChampionshipChances(): array
    {
        $currentWeek = $this->gameRepository->findCurrentWeek();

        if ($currentWeek < 4) {
            return [];
        }

        $teams = Team::with(['homeGames', 'awayGames'])->get();
        $teamStats = [];

        foreach ($teams as $team) {
            $teamStats[$team->id] = [
                'team' => $team,
                'name' => $team->name,
                'power' => $this->gameSimulatorService->calculatePower($team, true), //deplasman faktörü eklenmemiş
                'base_points' => $team->getPoints(),
                'base_goal_diff' => $team->getGoalDifference(),
                'simulatedWins' => 0,
                'wins' => 0,
            ];
        }

        $remainingGames = Game::unPlayed()->get();
        $simulationCount = 100;

        //takımın gücünü hesaba katarak $simulationCount kadar maç simüle eder
        for ($i = 0; $i < $simulationCount; $i++) {
            $simulatedTeamStats = [];
            foreach ($teamStats as $id => $data) {
                $simulatedTeamStats[$id] = [
                    'team' => $data['team'],
                    'name' => $data['name'],
                    'power' => $data['power'],
                    'goal_diff' => $data['base_goal_diff'],
                    'points' => $data['base_points'],
                ];
            }

            foreach ($remainingGames as $game) {
                /* @var Team $homeTeam */
                $homeTeam = $game->homeTeam;
                /* @var Team $awayTeam */
                $awayTeam = $game->awayTeam;

                $homeStats = $simulatedTeamStats[$homeTeam->id];
                $awayStats = $simulatedTeamStats[$awayTeam->id];

                $homePower = $this->gameSimulatorService->calculatePower($homeTeam, true);
                $awayPower = $this->gameSimulatorService->calculatePower($awayTeam, false);

                $homeScore = ($homePower * self::WEIGHT_POWER) + ($homeStats['points'] * self::WEIGHT_POINTS) + ($homeStats['goal_diff'] * self::WEIGHT_GOAL_DIFF);
                $awayScore = ($awayPower * self::WEIGHT_POWER) + ($awayStats['points'] * self::WEIGHT_POINTS) + ($awayStats['goal_diff'] * self::WEIGHT_GOAL_DIFF);
                $totalScore = $homeScore + $awayScore;

                $homeWinProb = $homeScore / $totalScore;
                $awayWinProb = $awayScore / $totalScore;
                $drawProbability = abs($homeWinProb - $awayWinProb) < 0.05;

                if ($drawProbability) {
                    $homeGoals = $awayGoals = random_int(0, 4);
                } else {
                    $winnerGoals = random_int(1, 4);
                    $loserGoals = random_int(0, $winnerGoals - 1);
                    $homeGoals = $homePower > $awayPower ? $winnerGoals : $loserGoals;
                    $awayGoals = $awayPower > $homePower ? $winnerGoals : $loserGoals;
                }

                // Skorları güncelle
                if ($homeGoals > $awayGoals) {
                    $simulatedTeamStats[$homeTeam->id]['points'] += 3;
                } elseif ($awayGoals > $homeGoals) {
                    $simulatedTeamStats[$awayTeam->id]['points'] += 3;
                } else {
                    $simulatedTeamStats[$homeTeam->id]['points'] += 1;
                    $simulatedTeamStats[$awayTeam->id]['points'] += 1;
                }

                // Averaj güncelle
                $simulatedTeamStats[$homeTeam->id]['goal_diff'] += $homeGoals - $awayGoals;
                $simulatedTeamStats[$awayTeam->id]['goal_diff'] += $awayGoals - $homeGoals;

                // Simülasyon sonunda en yüksek puana sahip takımı bul
                uasort($simulatedTeamStats, function ($a, $b) {
                    if ($a['points'] === $b['points']) {
                        return $b['goal_diff'] <=> $a['goal_diff'];
                    }
                    return $b['points'] <=> $a['points'];
                });

                $winnerId = array_key_first($simulatedTeamStats);
                $teamStats[$winnerId]['wins']++;
            }

            $results = [];
            foreach ($teamStats as $data) {
                $results[] = [
                    'name' => $data['name'],
                    'wins' => $data['wins'],
                    'chance' => $data['wins'] != 0 ? round(($data['wins'] / $simulationCount / $remainingGames->count()) * 100) : 0
                ];
            }
        }

        return $results;
    }
}
