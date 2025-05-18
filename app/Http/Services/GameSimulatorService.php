<?php

namespace App\Http\Services;

use App\Models\Game;
use App\Models\Team;
use App\Repositories\GameRepository;
use Random\RandomException;

class GameSimulatorService
{
    public function __construct(private readonly GameRepository $gameRepository)
    {
    }

    /**
     * Simulates the outcome of a single game based on the relative power of the home and away teams.
     *
     * This method calculates each team's strength, determines win/draw probabilities,
     * and assigns a random score accordingly. It then marks the game as played and saves the result.
     *
     * @param Game $game The game instance to simulate.
     * @return Game The updated game instance with simulated scores.
     * @throws RandomException
     */
    public function simulateGame(Game $game): Game
    {
        /* @var Team $homeTeam */
        $homeTeam = $game->homeTeam;
        /* @var Team $awayTeam */
        $awayTeam = $game->awayTeam;

        // Calculate power/strength for both teams (home advantage considered)
        $homePower = $this->calculatePower($homeTeam, true);
        $awayPower = $this->calculatePower($awayTeam, false);

        // Total combined power to calculate win probabilities
        $total = $homePower + $awayPower;

        $homeWinProbability = ($homePower / $total);
        $awayWinProbability = ($awayPower / $total);

        // If probabilities are very close, consider the match likely to be a draw
        $drawProbability = abs($homeWinProbability - $awayWinProbability) < 0.05 ? true : false;

        if ($drawProbability) {
            // Assign equal random goals to both teams for a draw
            $game->home_team_goal = $game->away_team_goal = random_int(0, 4);
        } else {
            // Randomly assign goals to winner and loser based on relative power
            $winnerGoals = random_int(1, 4);
            $loserGoals = random_int(0, $winnerGoals - 1);

            $game->home_team_goal = $homePower > $awayPower ? $winnerGoals : $loserGoals;
            $game->away_team_goal = $awayPower > $homePower ? $winnerGoals : $loserGoals;
        }

        // Mark game as played
        $game->is_played = true;

        // Save the updated game data to the database
        $game->save();

        return $game;
    }

    /**
     * Calculates the overall power score of a team, factoring in key attributes and randomness.
     *
     * Home teams receive a slight advantage by default.
     *
     * @param Team $team The team whose power is being calculated.
     * @param bool $isHome Whether the team is playing at home (default: false).
     * @return int The calculated power score of the team.
     * @throws RandomException
     */
    public function calculatePower(Team $team, $isHome = false): int
    {
        // Introduce a random factor to add unpredictability to the power calculation
        $randomFactor = random_int(0, 100);

        // Weighted sum of team's power attributes plus randomness
        $totalPower = (
            ($team->power * 0.6) +
            ($team->goalkeeper_power * 0.10) +
            ($team->supporter_power * 0.10) +
            ($randomFactor * 0.20)
        );

        // Slight reduction in power if the team is playing away
        if (!$isHome) {
            $totalPower *= 0.95;
        }

        return $totalPower;
    }

    /**
     * Simulates all games in the current week and returns the updated game results.
     *
     * @return array Array of simulated Game objects for the current week.
     * @throws RandomException
     */
    public function playWeek(): array
    {
        $currentWeek = $this->getCurrentWeek();

        $games = $this->findGames($currentWeek);
        $playedGames = [];

        // Simulate each game and collect the results
        foreach ($games as $game) {
            $playedGames[] = $this->simulateGame($game);
        }

        return $playedGames;
    }

    /**
     * Simulates all unplayed games regardless of the week and returns the updated game results.
     *
     * @return array Array of simulated Game objects.
     * @throws RandomException
     */
    public function playAll(): array
    {
        $games = $this->findGames();
        $playedGames = [];

        // Simulate each game and collect the results
        foreach ($games as $game) {
            $playedGames[] = $this->simulateGame($game);
        }

        return $playedGames;
    }

    /**
     * Retrieves unplayed games optionally filtered by week.
     *
     * @param int|null $week The week number to filter games, or null for all weeks.
     * @return \Illuminate\Database\Eloquent\Collection Collection of unplayed Game objects.
     */
    public function findGames(?int $week = null)
    {
        return $this->gameRepository->findUnplayedGames($week);
    }

    /**
     * Retrieves the current week number for the championship.
     *
     * @return int Current week number.
     */
    public function getCurrentWeek()
    {
        return $this->gameRepository->findCurrentWeek();
    }
}
