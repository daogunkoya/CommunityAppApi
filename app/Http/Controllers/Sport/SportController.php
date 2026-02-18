<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sport;

use App\Http\Controllers\Controller;
use App\Models\GameType;
use App\Models\Game;
use Illuminate\Http\Request;

class SportController extends Controller
{
    /**
     * Get all game types
     */
    public function gameTypes()
    {
        return GameType::all();
    }

    /**
     * Get all games
     */
    public function games()
    {
        return Game::all();
    }

    /**
     * Get sport statistics
     */
    public function stats()
    {
        $sportStats = GameType::withCount(['events' => function ($query) {
                $query->where('starts_at', '>', now()->startOfMinute());
            }])
            ->get()
            ->filter(function ($sport) {
                return $sport->events_count > 0;
            })
            ->map(function ($sport) {
                return [
                    'name' => $sport->name,
                    'count' => $sport->events_count,
                    'color' => match (strtolower($sport->name)) {
                        'tennis' => 'bg-sport-green',
                        'football' => 'bg-sport-orange',
                        'basketball' => 'bg-sport-blue',
                        'cycling' => 'bg-sport-red',
                        'swimming' => 'bg-primary',
                        default => 'bg-accent',
                    },
                ];
            })
            ->sortByDesc('count')
            ->values();

        return response()->json([
            'success' => true,
            'data' => $sportStats
        ]);
    }

    /**
     * Get sport statistics filtered by user's interests
     */
    public function userSportStats(Request $request)
    {
        try {
            $user = $request->user();
            
            // Get user's sport interests
            $userInterests = $user->gameInterests()->get();
            
            if ($userInterests->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No sport interests set. Set your interests to see personalized sport statistics.'
                ]);
            }
            
            // Get game type IDs for user's interests
            $gameTypeIds = $userInterests->pluck('id')->toArray();
            
            // Count events for each sport in user's interests
            $sportStats = GameType::whereIn('id', $gameTypeIds)
                ->withCount(['events' => function ($query) {
                    $query->where('starts_at', '>', now()->startOfMinute());
                }])
                ->get()
                ->filter(function ($sport) {
                    return $sport->events_count > 0;
                })
                ->map(function ($sport) {
                    return [
                        'name' => $sport->name,
                        'count' => $sport->events_count,
                        'color' => match (strtolower($sport->name)) {
                            'tennis' => 'bg-sport-green',
                            'football' => 'bg-sport-orange',
                            'basketball' => 'bg-sport-blue',
                            'cycling' => 'bg-sport-red',
                            'swimming' => 'bg-primary',
                            default => 'bg-accent',
                        },
                    ];
                })
                ->sortByDesc('count')
                ->values();
            
            return response()->json([
                'success' => true,
                'data' => $sportStats,
                'message' => 'Sport statistics filtered by your interests'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('User sport stats error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user sport statistics',
            ], 500);
        }
    }
}