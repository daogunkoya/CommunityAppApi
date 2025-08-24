<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\GameEvent;
use App\Models\Tournament;
use App\Models\Discussion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function stats(Request $request)
    {
        try {
            $user = $request->user();

            // Get total users count
            $totalUsers = User::where('is_active', true)->count();

            // Get online users count (users active in last 5 minutes)
            $onlineUsers = User::where('is_active', true)
                ->where('last_seen_at', '>=', now()->subMinutes(5))
                ->count();

            // Get total events count
            $totalEvents = GameEvent::count();

            // Get upcoming events count (next 7 days)
            $upcomingEvents = GameEvent::where('starts_at', '>=', now())
                ->where('starts_at', '<=', now()->addDays(7))
                ->count();

            // Get events this week
            $eventsThisWeek = GameEvent::where('starts_at', '>=', now()->startOfWeek())
                ->where('starts_at', '<=', now()->endOfWeek())
                ->count();

            // Get total participants across all events
            $totalParticipants = DB::table('game_event_participants')->count();

            // Get total tournaments
            $totalTournaments = Tournament::count();

            // Get active tournaments (registration open or in progress)
            $activeTournaments = Tournament::whereIn('status', ['open', 'filling-fast', 'in-progress'])
                ->count();

            // Get total discussions
            $totalDiscussions = Discussion::count();

            // Get discussions this week
            $discussionsThisWeek = Discussion::where('created_at', '>=', now()->startOfWeek())
                ->where('created_at', '<=', now()->endOfWeek())
                ->count();

            // Calculate success rate (events that had participants)
            $eventsWithParticipants = GameEvent::whereHas('participants')
                ->where('starts_at', '<', now())
                ->count();
            $completedEvents = GameEvent::where('starts_at', '<', now())->count();
            $successRate = $completedEvents > 0 ? round(($eventsWithParticipants / $completedEvents) * 100) : 0;

            // Get community rating (average user rating if available)
            $communityRating = 4.8; // Placeholder - can be calculated from user ratings if implemented

            return response()->json([
                'success' => true,
                'data' => [
                    'total_users' => $totalUsers,
                    'online_users' => $onlineUsers,
                    'total_events' => $totalEvents,
                    'upcoming_events' => $upcomingEvents,
                    'events_this_week' => $eventsThisWeek,
                    'total_participants' => $totalParticipants,
                    'total_tournaments' => $totalTournaments,
                    'active_tournaments' => $activeTournaments,
                    'total_discussions' => $totalDiscussions,
                    'discussions_this_week' => $discussionsThisWeek,
                    'success_rate' => $successRate,
                    'community_rating' => $communityRating,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard stats error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard statistics',
            ], 500);
        }
    }
}

