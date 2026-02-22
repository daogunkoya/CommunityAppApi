<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class NotificationCenterController extends Controller
{
    /**
     * Fetch all notifications for the authenticated user, paginated.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Laravel natively provides the ->notifications() relationship
            $notifications = $user->notifications()->paginate(20);

            // Also return the unread count so the frontend can populate the red badge
            $unreadCount = $user->unreadNotifications()->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'notifications' => $notifications->items(),
                    'pagination' => [
                        'current_page' => $notifications->currentPage(),
                        'last_page' => $notifications->lastPage(),
                        'per_page' => $notifications->perPage(),
                        'total' => $notifications->total(),
                        'has_more' => $notifications->hasMorePages()
                    ],
                    'unread_count' => $unreadCount
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Notification Center Fetch Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notifications.'
            ], 500);
        }
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();

            // Ensure we are casting the ID to string for UUID matching
            $notificationId = (string) $id;

            $notification = $user->notifications()->where('id', $notificationId)->first();

            if ($notification) {
                $notification->markAsRead();

                return response()->json([
                    'success' => true,
                    'message' => 'Notification marked as read.',
                    'unread_count' => $user->unreadNotifications()->count()
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Notification not found.'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Notification Mark As Read Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read.'
            ], 500);
        }
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $user->unreadNotifications->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read.',
                'unread_count' => 0
            ]);
        } catch (\Exception $e) {
            Log::error('Notification Mark All As Read Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notifications as read.'
            ], 500);
        }
    }
}
