<?php

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
}

