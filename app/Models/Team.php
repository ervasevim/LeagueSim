<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class Team extends Model
{

    //kaleci taraftar gücü
    public const LOCATION_HOME = 'home';
    public const LOCATION_AWAY = 'away';

    protected $table = 'teams';

    protected $fillable = ['name', 'power', 'goalkeeper_power', 'supporter_power'];


    public function homeGames(): HasMany
    {
        return $this->hasMany(Game::class, 'home_team_id');
    }

    public function awayGames(): HasMany
    {
        return $this->hasMany(Game::class, 'away_team_id');
    }


    public function getGames(?string $location = null, bool $isPlayed = false)
    {
        if ($location && !in_array($location, [self::LOCATION_HOME, self::LOCATION_AWAY])) {
            throw new InvalidArgumentException('Location must be either "home" or "away".');
        }

        $homeGames = $isPlayed ? $this->homeGames()->played()->get() : $this->homeGames;
        $awayGames = $isPlayed ? $this->awayGames()->played()->get() : $this->awayGames;

        if ($location == self::LOCATION_HOME) {
            return $homeGames;
        } elseif ($location == self::LOCATION_AWAY) {
            return $awayGames;
        }
        return collect($homeGames)->merge($awayGames);
    }

    public function getWinRate(): float|int
    {
        return $this->getWonCount() / $this->getPlayedGamesCount();
    }

    public function getPlayedGamesCount(?string $location = null): int
    {
        return $this->getGames($location, true)->count();
    }

    public function getWonCount(): int
    {
        $homeWins = $this->homeGames->filter(function (Game $game) {
            return $game->is_played && $game->home_team_goal > $game->away_team_goal;
        })->count();

        $awayWins = $this->awayGames->filter(function (Game $game) {
            return $game->is_played && $game->away_team_goal > $game->home_team_goal;
        })->count();

        return $homeWins + $awayWins;
    }

    public function getDrawnCount(): int
    {
        return $this->getGames(null, true)->filter(function (Game $game) {
            return $game->home_team_goal === $game->away_team_goal;
        })->count();
    }

    public function getLostCount(): int
    {
        $homeLost = $this->homeGames->filter(function (Game $game) {
            return $game->is_played && $game->home_team_goal < $game->away_team_goal;
        })->count();

        $awayLost = $this->awayGames->filter(function (Game $game) {
            return $game->is_played && $game->away_team_goal < $game->home_team_goal;
        })->count();


        return $homeLost + $awayLost;
    }

    public function getGoalDifference(): int
    {
        $homeScored = $this->getGames(self::LOCATION_HOME, true)->sum('home_team_goal');
        $homeConceded = $this->getGames(self::LOCATION_HOME, true)->sum('away_team_goal');

        $awayScored = $this->getGames(self::LOCATION_AWAY, true)->sum('away_team_goal');
        $awayConceded = $this->getGames(self::LOCATION_AWAY, true)->sum('home_team_goal');

        $totalScored = $homeScored + $awayScored;
        $totalConceded = $homeConceded + $awayConceded;

        return $totalScored - $totalConceded;
    }

    public function getPoints()
    {
        return $this->getWonCount() * 3 + $this->getDrawnCount();
    }

    /**
     * @return mixed
     */
    public function getAvgGoals()
    {
        return $this->games->sum(function ($game) {
            $location = $game->home_team_id === $this->id ? self::LOCATION_HOME : self::LOCATION_AWAY;
            return $location == self::LOCATION_HOME ? $game->home_score : $game->away_score;
        });
    }
    /*
     * TODO :
     * played = P
     * won = w
     * Drawn => d
     * lost = l
     * GD = Atılan Gol Sayısı - Yenen Gol Sayısı
     */

}
