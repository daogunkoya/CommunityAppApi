<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BlockController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'blocked_user_id' => 'required|exists:users,id',
        ]);

        if ($request->user()->id == $validated['blocked_user_id']) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot block yourself'
            ], 422);
        }

        try {
            $request->user()->blockedUsers()->firstOrCreate([
                'blocked_user_id' => $validated['blocked_user_id']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User blocked successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to block user'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            // $id is the user_id to unblock
            $deleted = $request->user()->blockedUsers()
                ->where('blocked_user_id', $id)
                ->delete();

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'User unblocked successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'User was not blocked'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unblock user'
            ], 500);
        }
    }

    /**
     * Get list of blocked users
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $blockedUsers = $request->user()->usersBlockedByMe()
                ->select('users.id', 'users.first_name', 'users.last_name', 'users.profile_picture') // Optimize selection
                ->get();

            return response()->json([
                'success' => true,
                'data' => $blockedUsers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch blocked users'
            ], 500);
        }
    }
}
