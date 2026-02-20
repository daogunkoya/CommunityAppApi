<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Get all users (for testing/development)
     */
    public function index(Request $request)
    {
        $users = User::select('id', 'first_name', 'last_name', 'email', 'profile_picture', 'location', 'is_online', 'last_seen_at')
            ->where('is_active', true)
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'profile_picture' => $user->profile_picture,
                    'location' => $user->location,
                    'is_online' => $user->is_online,
                    'last_seen_at' => $user->last_seen_at,
                    'last_seen_formatted' => $user->last_seen_formatted,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Get a specific user's public profile
     */
    public function show($id)
    {
        $user = User::select('id', 'first_name', 'last_name', 'profile_picture', 'location', 'is_online', 'last_seen_at', 'bio', 'gender', 'created_at')
            ->with([
                'gameInterests' => function ($query) {
                    $query->select('game_types.id', 'game_types.name', 'skill_level');
                }
            ])
            ->findOrFail($id);

        $userData = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => $user->full_name,
            'profile_picture' => $user->profile_picture,
            'location' => $user->location,
            'is_online' => $user->is_online,
            'last_seen_at' => $user->last_seen_at,
            'last_seen_formatted' => $user->last_seen_formatted,
            'interests' => $user->gameInterests, // Sports/Interests
            'created_at_formatted' => $user->created_at->format('F Y'), // Member since
        ];

        return response()->json([
            'success' => true,
            'data' => $userData
        ]);
    }

    /**
     * Mark user as online
     */
    public function markOnline(Request $request)
    {
        $user = $request->user();
        $user->markAsOnline();
        return response()->json(['success' => true, 'message' => 'Marked as online']);
    }

    /**
     * Mark user as offline
     */
    public function markOffline(Request $request)
    {
        $user = $request->user();
        $user->markAsOffline();
        return response()->json(['success' => true, 'message' => 'Marked as offline']);
    }

    /**
     * Update user's last seen timestamp
     */
    public function ping(Request $request)
    {
        $user = $request->user();
        $user->updateLastSeen();
        return response()->json(['success' => true, 'message' => 'Ping updated']);
    }
}

