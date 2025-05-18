<?php

namespace App\Repositories;

use App\Models\Game;

class GameRepository
{
    public function findUnplayedGames(?int $week = null)
    {
        return Game::when($week, function ($query, $week) {
            return $query->where('week', $week);
        })
            ->where('is_played', false)
            ->orderBy('week', 'asc')
            ->get();
    }

    public function findCurrentWeek()
    {
        $week = Game::where('is_played', false)
            ->orderBy('week', 'asc')
            ->value('week');

        if ($week === null) {
            $week = Game::max('week');
        }

        return $week;
    }
}
