<?php

namespace App\Http\Services;

use App\Models\Game;
use App\Models\Team;
use Illuminate\Support\Arr;

class FixtureService
{
    /**
     *  * Generates the fixture schedule for the season.
     *
     * @return void
     */
    public function generateFixtures()
    {
        $teams = Team::all()->keyBy('id');
        $teams = $teams->shuffle();
        $weekCount = $teams->count() - 1;

        $matches = [];
        // iterate all weeks
        for ($week = 0; $week < $weekCount; $week++) {
            foreach ($teams as $homeTeam) {
                // flip home/away status
                $homeTeam->home = !$homeTeam->home;

                foreach ($teams as $awayTeam) {
                    if ($homeTeam->id == $awayTeam->id)
                        continue;

                    // Validates if the match is playable
                    if ($this->_canPlayThisWeek($matches, $week, $homeTeam->id, $awayTeam->id) &&
                        $this->_canPlayThisHalf($matches, $homeTeam->id, $awayTeam->id)) {
                        $awayTeam->home = !$homeTeam->home;
                        // each team cannot be home or away during 3 weeks
                        $this->_fixHomeAwayStatus($matches, $week, $homeTeam, $awayTeam);
                        // set home / away team ids
                        $homeTeamId = $homeTeam->home ? $homeTeam->id : $awayTeam->id;
                        $awayTeamId = $homeTeam->home ? $awayTeam->id : $homeTeam->id;
                        // insert match
                        $matches[$week][] = [
                            'home' => $homeTeamId,
                            'away' => $awayTeamId
                        ];

                        // for first half
                        Game::create([
                            'home_team_id' => $homeTeamId,
                            'away_team_id' => $awayTeamId,
                            'week' => $week + 1,
                        ]);

                        // insert reverse of first half for second half
                        Game::create([
                            'home_team_id' => $awayTeamId,
                            'away_team_id' => $homeTeamId,
                            'week' => $week + $weekCount + 1,
                        ]);

                        break;
                    }

                }
            }
        }
    }

    /**
     * Checks home team and away team play this week.
     * The rule is each teams can play one time on week
     *
     * @param $matches
     * @param $week
     * @param $homeTeamId
     * @param $awayTeamId
     * @return bool
     */
    private function _canPlayThisWeek($matches, $week, $homeTeamId, $awayTeamId): bool
    {
        // if is first match of week
        if (!isset($matches[$week]))
            return true;

        $matchesThisWeek = $matches[$week];

        $playedTeams = [];
        foreach ($matchesThisWeek as $match) {
            $playedTeams[] = $match['home'];
            $playedTeams[] = $match['away'];
        }

        return !in_array($homeTeamId, $playedTeams) && !in_array($awayTeamId, $playedTeams);
    }

    /**
     * It checks whether the two teams have already played a match in this half
     *
     * @param $matches
     * @param $homeTeamId
     * @param $awayTeamId
     * @return bool
     */
    private function _canPlayThisHalf($matches, $homeTeamId, $awayTeamId): bool
    {
        $allMatches = Arr::flatten($matches, 1);
        foreach ($allMatches as $match) {
            if (($match['home'] == $homeTeamId || $match['away'] == $homeTeamId) &&
                ($match['home'] == $awayTeamId || $match['away'] == $awayTeamId)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Adjusts the home/away status of the teams. Each team can be the home or away side at most twice in a row
     *
     * @param $matches
     * @param $week
     * @param $homeTeam
     * @param $awayTeam
     * @return void
     */
    private function _fixHomeAwayStatus($matches, $week, &$homeTeam, &$awayTeam)
    {
        $homeTeamStatusIsOk = false;
        $awayTeamStatusIsOk = false;

        $homeTeamId = $homeTeam->home ? $homeTeam->id : $awayTeam->id;
        $awayTeamId = $awayTeam->home ? $homeTeam->id : $awayTeam->id;

        foreach ($matches as $matchWeek => $matchesOnWeek) {
            // just pay attention on last 2 week
            if ($week - $matchWeek > 2)
                continue;

            foreach ($matchesOnWeek as $match) {
                if ($match['home'] != $homeTeamId) {
                    $homeTeamStatusIsOk = true;
                }

                if ($match['away'] != $awayTeamId) {
                    $awayTeamStatusIsOk = true;
                }
            }
        }

        if (!$homeTeamStatusIsOk || !$awayTeamStatusIsOk) {
            $homeTeam->home = !$homeTeam->home;
            $awayTeam->home = !$awayTeam->home;
        }
    }

    /**
     * Retrieves the fixtures (games) optionally filtered by a specific week.
     *
     * If no games exist in the database, it triggers the generation of fixtures first.
     * Then returns a collection of games with their associated home and away teams,
     * filtered by the given week if provided, and ordered by week.
     *
     * @param int|null $week Optional week number to filter fixtures.
     * @return \Illuminate\Database\Eloquent\Collection Collection of Game models.
     */
    public function getFixtures(?int $week = null): \Illuminate\Database\Eloquent\Collection
    {
        // Count existing games to check if fixtures need to be generated
        $gameCount = Game::all()->count();

        // Generate fixtures if none exist
        if ($gameCount === 0) {
            $this->generateFixtures();
        }

        // Retrieve games with related teams, optionally filtering by week
        return Game::with(['homeTeam', 'awayTeam'])
            ->when($week, function ($query, $week) {
                return $query->where('week', $week);
            })
            ->orderBy('week')->get();
    }

    /**
     * Resets the state of all played games.
     *
     * This method clears the goals for both home and away teams and marks games as unplayed,
     * effectively preparing the schedule for a fresh simulation or season restart.
     */
    public function resetData(): void
    {
        // Update all played games to reset scores and status
        Game::played()->update([
            'home_team_goal' => null,
            'away_team_goal' => null,
            'is_played' => false,
        ]);
    }
}
