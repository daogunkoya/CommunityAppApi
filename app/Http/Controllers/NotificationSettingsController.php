<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationSettingsController extends Controller
{
    /**
     * Register or update an Expo Push Token for the authenticated user.
     */
    public function registerDevice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'expo_push_token' => 'required|string',
            'device_model' => 'nullable|string',
            'platform' => 'nullable|string|in:ios,android,web',
        ]);

        $user = $request->user();

        // Use updateOrCreate to ensure we don't duplicate tokens for the same user
        $device = $user->devices()->updateOrCreate(
            ['expo_push_token' => $validated['expo_push_token']],
            [
                'device_model' => $validated['device_model'] ?? null,
                'platform' => $validated['platform'] ?? null,
                'last_active_at' => now(),
                'is_active' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Device push token registered successfully.',
            'data' => $device
        ]);
    }

    /**
     * Get the user's notification preferences.
     */
    public function getPreferences(Request $request): JsonResponse
    {
        $user = $request->user();

        // Fetch preferences or provide an empty array (defaults will be handled by the frontend/dispatcher)
        $preferences = $user->notificationPreferences()->get();

        return response()->json([
            'success' => true,
            'data' => $preferences
        ]);
    }

    /**
     * Update a specific notification preference category.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => 'required|string|in:messages,game_invites,discussions,marketing,system',
            'channel_push' => 'required|boolean',
            'channel_email' => 'required|boolean',
            'channel_sms' => 'required|boolean',
            'frequency' => 'required|string|in:instant,daily,weekly,never',
        ]);

        $user = $request->user();

        // Update or create the preference for this specific category
        $preference = $user->notificationPreferences()->updateOrCreate(
            ['category' => $validated['category']],
            [
                'channel_push' => $validated['channel_push'],
                'channel_email' => $validated['channel_email'],
                'channel_sms' => $validated['channel_sms'],
                'frequency' => $validated['frequency'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated successfully.',
            'data' => $preference
        ]);
    }
}
