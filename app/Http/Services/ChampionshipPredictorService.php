<?php

namespace App\Http\Services;

use App\Models\Game;
use App\Models\Team;
use App\Repositories\GameRepository;
use Random\RandomException;

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

    /**
     *  Predicts the chances of each team winning the championship.
     *
     *  It first checks if the current week is sufficient for meaningful prediction.
     *  Then, it initializes team statistics including power, points, and goal difference.
     *  For a predefined number of simulations, it simulates the remaining games based on team strengths,
     *  updating points and goal differences accordingly.
     *
     *  After all simulations, it calculates each team's chance of winning as the percentage of simulations
     *  in which that team finished first.
     * @return array
     * @throws RandomException
     */
    public function predictChampionshipChances(): array
    {
        // Get current week from repository, return empty if before week 4
        $currentWeek = $this->gameRepository->findCurrentWeek();

        if ($currentWeek < 4) {
            return [];
        }

        $teams = Team::with(['homeGames', 'awayGames'])->get();
        $teamStats = [];

        // Initialize stats for each team
        foreach ($teams as $team) {
            $teamStats[$team->id] = [
                'team' => $team,
                'name' => $team->name,
                'power' => $this->gameSimulatorService->calculatePower($team, true), // Calculate initial team power (home/away factor not added here)
                'base_points' => $team->getPoints(), // Current points before simulation
                'base_goal_diff' => $team->getGoalDifference(), // Current goal difference before simulation
                'wins' => 0, // Count of wins from simulation outcomes
            ];
        }

        // Get all remaining games that are not yet played
        $remainingGames = Game::unPlayed()->get();
        $simulationCount = 100;

        // Run simulations to estimate championship chances
        for ($i = 0; $i < $simulationCount; $i++) {
            // Copy base stats for simulation iteration
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

            // Simulate each remaining game based on team powers and current stats
            foreach ($remainingGames as $game) {
                $homeTeam = $game->homeTeam;
                $awayTeam = $game->awayTeam;

                $homeStats = $simulatedTeamStats[$homeTeam->id];
                $awayStats = $simulatedTeamStats[$awayTeam->id];

                // Calculate power for home and away teams with home advantage
                $homePower = $this->gameSimulatorService->calculatePower($homeTeam, true);
                $awayPower = $this->gameSimulatorService->calculatePower($awayTeam, false);

                // Calculate scores incorporating power, points, and goal difference with weighted factors
                $homeScore = ($homePower * self::WEIGHT_POWER) + ($homeStats['points'] * self::WEIGHT_POINTS) + ($homeStats['goal_diff'] * self::WEIGHT_GOAL_DIFF);
                $awayScore = ($awayPower * self::WEIGHT_POWER) + ($awayStats['points'] * self::WEIGHT_POINTS) + ($awayStats['goal_diff'] * self::WEIGHT_GOAL_DIFF);
                $totalScore = $homeScore + $awayScore;

                // Calculate win probabilities
                $homeWinProb = $homeScore / $totalScore;
                $awayWinProb = $awayScore / $totalScore;

                // Decide if the match is likely a draw if win probabilities are very close
                $drawProbability = abs($homeWinProb - $awayWinProb) < 0.05;

                // Generate match result based on probabilities
                if ($drawProbability) {
                    $homeGoals = $awayGoals = random_int(0, 4);
                } else {
                    // Decisive match -> winner gets more goals
                    $winnerGoals = random_int(1, 4);
                    $loserGoals = random_int(0, $winnerGoals - 1);
                    $homeGoals = $homePower > $awayPower ? $winnerGoals : $loserGoals;
                    $awayGoals = $awayPower > $homePower ? $winnerGoals : $loserGoals;
                }

                // Update points based on match result
                if ($homeGoals > $awayGoals) {
                    $simulatedTeamStats[$homeTeam->id]['points'] += 3;
                } elseif ($awayGoals > $homeGoals) {
                    $simulatedTeamStats[$awayTeam->id]['points'] += 3;
                } else {
                    // Draw: both teams get 1 point
                    $simulatedTeamStats[$homeTeam->id]['points'] += 1;
                    $simulatedTeamStats[$awayTeam->id]['points'] += 1;
                }

                // Update goal difference accordingly
                $simulatedTeamStats[$homeTeam->id]['goal_diff'] += $homeGoals - $awayGoals;
                $simulatedTeamStats[$awayTeam->id]['goal_diff'] += $awayGoals - $homeGoals;
            }

            // After all games simulated, sort teams by points and goal difference to find the winner
            uasort($simulatedTeamStats, function ($a, $b) {
                if ($a['points'] === $b['points']) {
                    return $b['goal_diff'] <=> $a['goal_diff'];
                }
                return $b['points'] <=> $a['points'];
            });

            $winnerId = array_key_first($simulatedTeamStats);

            // Increment win count for the winning team in this simulation
            $teamStats[$winnerId]['wins']++;
        }

        // Calculate championship chances as percentages
        $results = [];
        foreach ($teamStats as $data) {
            $results[] = [
                'name' => $data['name'],
                'wins' => $data['wins'],
                'chance' => $data['wins'] != 0 ? round(($data['wins'] / $simulationCount) * 100) : 0 // Probability as percent
            ];
        }

        return $results;
    }
}
