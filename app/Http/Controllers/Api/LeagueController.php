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
use Random\RandomException;

class LeagueController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function getTeams(): JsonResponse
    {
        return response()->json([
            'data' => Team::all()
        ]);
    }

    /**
     * @param Request $request
     * @param FixtureService $fixtureService
     * @return JsonResponse
     */
    public function getFixtures(Request $request, FixtureService $fixtureService): JsonResponse
    {
        $week = $request->get('week');
        $games = $fixtureService->getFixtures($week);
        return response()->json([
            'data' => $games,
        ]);
    }

    /**
     * @param GameSimulatorService $gameSimulatorService
     * @return JsonResponse
     * @throws RandomException
     */
    public function playNextWeek(GameSimulatorService $gameSimulatorService): JsonResponse
    {
        $playedGames = $gameSimulatorService->playWeek();

        return response()->json([
            'data' => $playedGames,
        ]);
    }

    /**
     * @param GameSimulatorService $gameSimulatorService
     * @return JsonResponse
     * @throws RandomException
     */
    public function playAllWeek(GameSimulatorService $gameSimulatorService): JsonResponse
    {
        $playedGames = $gameSimulatorService->playAll();

        return response()->json([
            'data' => $playedGames,
        ]);
    }

    /**
     * @return array
     */
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

    /**
     * @param ChampionshipPredictorService $predictorService
     * @return JsonResponse
     * @throws RandomException
     */
    public function calculatePredictions(ChampionshipPredictorService $predictorService): JsonResponse
    {
        $predictions = $predictorService->predictChampionshipChances();

        return response()->json([
            'data' => $predictions
        ]);
    }

    /**
     * @param FixtureService $fixtureService
     * @return JsonResponse
     */
    public function resetData(FixtureService $fixtureService): JsonResponse
    {
        $fixtureService->resetData();

        return response()->json([
            'message' => "Data has been successfully reset."
        ]);
    }

    /**
     * @param GameSimulatorService $gameSimulatorService
     * @return JsonResponse
     */
    public function currentWeek(GameSimulatorService $gameSimulatorService): JsonResponse
    {
        return response()->json([
            'data' => $gameSimulatorService->getCurrentWeek()
        ]);
    }
}
