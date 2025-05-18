<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Game extends Model
{
    protected $fillable = [
        'home_team_id',
        'away_team_id',
        'home_team_goal',
        'away_team_goal',
        'is_played',
        'week'
    ];

    /**
     * @return BelongsTo
     */
    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    /**
     * @return BelongsTo
     */
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    /**
     * Scope a query to only include active users.
     */
    #[Scope]
    protected function played(Builder $query): void
    {
        $query->where('is_played', true);
    }

    /**
     * Scope a query to only include active users.
     */
    #[Scope]
    protected function unPlayed(Builder $query): Builder
    {
        return $query->where('is_played', false);
    }

}
