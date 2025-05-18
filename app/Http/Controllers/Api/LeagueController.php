<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Services\ChampionshipPredictorService;
use App\Http\Services\FixtureService;
use App\Http\Services\GameSimulatorService;
use App\Models\Game;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeagueController extends Controller
{
    public function getTeams(): JsonResponse
    {
        return response()->json([
            'data' => Team::all()
        ]);
    }

    public function getFixtures(Request $request, FixtureService $fixtureService): JsonResponse
    {
        $week = $request->get('week');
        $games = $this->fixtureService->getFixtures($week);
        return response()->json([
            'data' => $games,
        ]);
    }

    public function playNextWeek(GameSimulatorService $gameSimulatorService): JsonResponse
    {
        $playedGames = $gameSimulatorService->playWeek();

        return response()->json([
            'data' => $playedGames,
        ]);
    }

    public function playAllWeek(GameSimulatorService $gameSimulatorService): JsonResponse
    {
        $playedGames = $gameSimulatorService->playAll();

        return response()->json([
            'data' => $playedGames,
        ]);
    }

    public function calculateStandings()
    {
        $teams = Team::all();

        $standings = [];
        foreach ($teams as $team) {
            $standings[$team->id] = [
                'team' => $team,
                'name' => $team->name,
                'played' => $team->getPlayedGamesCount(),
                'wins' => $team->getWonCount(),
                'draws' => $team->getDrawnCount(),
                'losses' => $team->getLostCount(),
                'goal_difference' => $team->getGoalDifference(),
            ];
        }

        $games = Game::all();


        return $standings;
    }

    public function calculatePredictions(ChampionshipPredictorService $predictorService): JsonResponse
    {
        $predictions = $predictorService->predictChampionshipChances();

        return response()->json([
            'data' => $predictions
        ]);
    }
}
