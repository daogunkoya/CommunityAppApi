<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CommunityController extends Controller
{
    /**
     * Get user's communities
     */
    public function getUserCommunities(Request $request): JsonResponse
    {
        $user = $request->user();

        $communities = $user->communities()
            ->where('is_active', true)
            ->get()
            ->map(function ($community) {
                return [
                    'id' => $community->id,
                    'name' => $community->name,
                    'type' => $community->type,
                    'city' => $community->city,
                    'state' => $community->state,
                    'country' => $community->country,
                    'full_location' => $community->full_location,
                    'is_primary' => $community->pivot->is_primary,
                    'joined_at' => $community->pivot->joined_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $communities,
        ]);
    }

    /**
     * Get all available communities
     */
    public function getAllCommunities(Request $request): JsonResponse
    {
        $communities = Community::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($community) {
                return [
                    'id' => $community->id,
                    'name' => $community->name,
                    'type' => $community->type,
                    'city' => $community->city,
                    'state' => $community->state,
                    'country' => $community->country,
                    'full_location' => $community->full_location,
                    'description' => $community->description,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $communities,
        ]);
    }

    /**
     * Get community statistics
     */
    public function getCommunityStats(Request $request, int $communityId): JsonResponse
    {
        $community = Community::findOrFail($communityId);

        $stats = [
            'id' => $community->id,
            'name' => $community->name,
            'type' => $community->type,
            'full_location' => $community->full_location,
            'total_users' => $community->users()->count(),
            'total_events' => $community->gameEvents()->count(),
            'upcoming_events' => $community->gameEvents()
                ->where('starts_at', '>', now())
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get user's primary community
     */
    public function getPrimaryCommunity(Request $request): JsonResponse
    {
        $user = $request->user();

        $primaryCommunity = $user->communities()
            ->where('is_active', true)
            ->where('is_primary', true)
            ->first();

        if (!$primaryCommunity) {
            return response()->json([
                'success' => false,
                'message' => 'No primary community found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $primaryCommunity->id,
                'name' => $primaryCommunity->name,
                'type' => $primaryCommunity->type,
                'city' => $primaryCommunity->city,
                'state' => $primaryCommunity->state,
                'country' => $primaryCommunity->country,
                'full_location' => $primaryCommunity->full_location,
                'joined_at' => $primaryCommunity->pivot->joined_at,
            ],
        ]);
    }
}
