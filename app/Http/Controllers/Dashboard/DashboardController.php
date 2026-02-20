<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\GameEvent;
use App\Models\Tournament;
use App\Models\Discussion;
use App\Enums\SkillLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Get complete dashboard data in one optimized request
     * MAIN ENDPOINT - Serves entire dashboard with personalized content
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            Log::info("Dashboard - Loading complete dashboard for user {$user->id}");

            // Get user's sport interests first
            $userInterestIds = $user->gameInterests()->pluck('game_type_id')->toArray();
            $interestNames = $user->gameInterests()->pluck('name')->toArray();

            // Load all dashboard sections efficiently
            $data = [
                'stats' => $this->getStatsData(),
                'activity' => $this->getActivityData($user, $userInterestIds, 10),
                'recommended_games' => $this->getRecommendedGamesData($user, $userInterestIds, 3),
                'tournaments' => $this->getTournamentsData($user, $userInterestIds, 2),
                'user_interests' => [
                    'names' => $interestNames,
                    'ids' => $userInterestIds,
                    'count' => count($userInterestIds),
                    'has_interests' => !empty($userInterestIds),
                ],
                'loaded_at' => now()->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => !empty($userInterestIds)
                    ? 'Showing personalized content for: ' . implode(', ', $interestNames)
                    : 'Showing general content. Set your interests for personalized feed.',
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard index error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

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

    /**
     * Get personalized activity feed based on user's sport interests
     * FILTERS CONTENT BY USER'S INTERESTS (e.g., Tennis & Basketball only)
     */
    public function activity(Request $request)
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'limit' => 'integer|min:1|max:50',
                'page' => 'integer|min:1',
            ]);

            $limit = $validated['limit'] ?? 10;
            $page = $validated['page'] ?? 1;

            // Get user's sport interests
            $userInterestIds = $user->gameInterests()->pluck('game_type_id')->toArray();

            if (empty($userInterestIds)) {
                return $this->getGeneralActivity($limit, $page);
            }

            Log::info("Dashboard Activity - User {$user->id} interests: " . implode(', ', $userInterestIds));

            // Get blocked user IDs
            $blockedUserIds = $user->blockedUsers()->pluck('blocked_user_id')->toArray();

            $activities = collect();

            // Get game events matching user's interests
            $gameEvents = GameEvent::with(['organiser', 'gameType', 'participants'])
                ->whereIn('game_type_id', $userInterestIds)
                ->whereNotIn('organiser_id', $blockedUserIds) // Exclude blocked users
                ->when($user->latitude && $user->longitude && $user->radius, function ($query) use ($user) {
                    $earthRadius = 6371;
                    return $query->selectRaw("
                        *,
                        ({$earthRadius} * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
                    ", [$user->latitude, $user->longitude, $user->latitude])
                        ->having('distance', '<=', $user->radius ?? 50);
                })
                ->where('starts_at', '>=', now())
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($event) {
                    return [
                        'id' => $event->id,
                        'type' => 'game',
                        'author' => [
                            'id' => $event->organiser->id,
                            'name' => $event->organiser->full_name,
                            'avatar' => $event->organiser->profile_picture,
                        ],
                        'content' => $event->notes ?? "Join us for {$event->gameType->name} at {$event->location}!",
                        'sport' => $event->gameType->name,
                        'location' => $event->location,
                        'date' => $event->starts_at->format('l, M j, g:i A'),
                        'created_at' => $event->created_at,
                        'timestamp' => $event->created_at->timestamp,
                        'likes' => 0,
                        'comments' => 0,
                        'is_liked' => false,
                    ];
                });

            $activities = $activities->merge($gameEvents);

            // Get discussions matching user's interests
            $discussions = Discussion::with(['user', 'gameType'])
                ->whereIn('game_type_id', $userInterestIds)
                ->whereNotIn('user_id', $blockedUserIds) // Exclude blocked users
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($discussion) use ($user) {
                    $likesCount = $discussion->likes ? $discussion->likes->count() : 0;
                    $commentsCount = $discussion->comments ? $discussion->comments->count() : 0;
                    $isLiked = $discussion->likes ? $discussion->likes->where('user_id', $user->id)->isNotEmpty() : false;

                    return [
                        'id' => $discussion->id,
                        'type' => 'discussion',
                        'author' => [
                            'id' => $discussion->user->id,
                            'name' => $discussion->user->full_name,
                            'avatar' => $discussion->user->profile_picture,
                        ],
                        'content' => $discussion->body,
                        'sport' => $discussion->gameType->name ?? null,
                        'created_at' => $discussion->created_at,
                        'timestamp' => $discussion->created_at->timestamp,
                        'likes' => $likesCount,
                        'comments' => $commentsCount,
                        'is_liked' => $isLiked,
                    ];
                });

            $activities = $activities->merge($discussions);

            // Sort by timestamp
            $activities = $activities->sortByDesc('timestamp')->values();
            $total = $activities->count();
            $activities = $activities->slice(($page - 1) * $limit, $limit)->values();

            $interestNames = $user->gameInterests()->pluck('name')->toArray();

            return response()->json([
                'success' => true,
                'data' => $activities,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'last_page' => ceil($total / $limit),
                ],
                'meta' => [
                    'user_interests' => $interestNames,
                    'filtered' => true,
                    'message' => 'Showing activity for: ' . implode(', ', $interestNames),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard activity error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch activity feed',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get recommended games based on user's interests
     */
    public function recommendedGames(Request $request)
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'limit' => 'integer|min:1|max:50',
            ]);

            $limit = $validated['limit'] ?? 3;
            $userInterestIds = $user->gameInterests()->pluck('game_type_id')->toArray();

            if (empty($userInterestIds)) {
                $games = GameEvent::with(['organiser', 'gameType', 'participants'])
                    ->where('starts_at', '>=', now())
                    ->orderBy('starts_at', 'asc')
                    ->limit($limit)
                    ->get();
            } else {
                $games = GameEvent::with(['organiser', 'gameType', 'participants'])
                    ->whereIn('game_type_id', $userInterestIds)
                    ->where('starts_at', '>=', now())
                    ->where('organiser_id', '!=', $user->id)
                    ->whereDoesntHave('participants', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })
                    ->when($user->latitude && $user->longitude && $user->radius, function ($query) use ($user) {
                        $earthRadius = 6371;
                        return $query->selectRaw("
                            *,
                            ({$earthRadius} * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
                        ", [$user->latitude, $user->longitude, $user->latitude])
                            ->having('distance', '<=', $user->radius ?? 50);
                    })
                    ->orderBy('starts_at', 'asc')
                    ->limit($limit)
                    ->get();
            }

            $formattedGames = $games->map(function ($game) {
                return [
                    'id' => $game->id,
                    'title' => $game->title ?? "{$game->gameType->name} Game",
                    'sport' => $game->gameType->name,
                    'location' => $game->location,
                    'date' => $game->starts_at->format('l, M j, g:i A'),
                    'starts_at' => $game->starts_at->toISOString(),
                    'participants' => $game->participants->count(),
                    'maxParticipants' => $game->max_participants,
                    'skillLevel' => $this->formatSkillLevel($game->skill_level),
                    'organizer' => $game->organiser->full_name,
                    'organizer_id' => $game->organiser->id,
                    'status' => $this->getGameStatus($game),
                    'distance' => isset($game->distance) ? round($game->distance, 1) . ' km' : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedGames,
            ]);

        } catch (\Exception $e) {
            Log::error('Recommended games error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recommended games',
            ], 500);
        }
    }

    /**
     * Get relevant tournaments based on user's interests
     */
    public function relevantTournaments(Request $request)
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'limit' => 'integer|min:1|max:50',
            ]);

            $limit = $validated['limit'] ?? 2;
            $userInterestIds = $user->gameInterests()->pluck('game_type_id')->toArray();

            $tournaments = Tournament::with(['gameType', 'participants'])
                ->when(!empty($userInterestIds), function ($query) use ($userInterestIds) {
                    return $query->whereIn('game_type_id', $userInterestIds);
                })
                ->whereIn('status', ['open', 'filling-fast', 'registration-open'])
                ->where('start_date', '>=', now())
                ->orderBy('start_date', 'asc')
                ->limit($limit)
                ->get();

            $formattedTournaments = $tournaments->map(function ($tournament) {
                return [
                    'id' => $tournament->id,
                    'title' => $tournament->name,
                    'sport' => $tournament->gameType->name,
                    'location' => $tournament->location,
                    'date' => $tournament->start_date->format('M j, Y'),
                    'start_date' => $tournament->start_date->toISOString(),
                    'participants' => $tournament->participants->count(),
                    'maxParticipants' => $tournament->max_participants,
                    'prize' => $tournament->prize_pool ? '£' . $tournament->prize_pool : 'No prize',
                    'status' => ucfirst(str_replace('-', ' ', $tournament->status)),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedTournaments,
            ]);

        } catch (\Exception $e) {
            Log::error('Relevant tournaments error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tournaments',
            ], 500);
        }
    }

    /**
     * Get upcoming games that user has joined
     */
    public function upcomingGames(Request $request)
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'limit' => 'integer|min:1|max:50',
            ]);

            $limit = $validated['limit'] ?? 5;

            $games = $user->joinedEvents()
                ->with(['organiser', 'gameType', 'participants'])
                ->where('starts_at', '>=', now())
                ->orderBy('starts_at', 'asc')
                ->limit($limit)
                ->get()
                ->map(function ($game) {
                    return [
                        'id' => $game->id,
                        'title' => $game->title ?? "{$game->gameType->name} Game",
                        'sport' => $game->gameType->name,
                        'location' => $game->location,
                        'starts_at' => $game->starts_at->toISOString(),
                        'formatted_date' => $game->starts_at->format('l, M j, g:i A'),
                        'time_until' => $game->starts_at->diffForHumans(),
                        'organizer' => $game->organiser->full_name,
                        'participants' => $game->participants->count(),
                        'max_participants' => $game->max_participants,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $games,
            ]);

        } catch (\Exception $e) {
            Log::error('Upcoming games error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch upcoming games',
            ], 500);
        }
    }

    /**
     * Get user's sport interests
     */
    public function userInterests(Request $request)
    {
        try {
            $user = $request->user();

            $interests = $user->gameInterests()
                ->get()
                ->map(function ($gameType) {
                    return [
                        'game_type_id' => $gameType->id,
                        'name' => $gameType->name,
                        'skill_level' => $gameType->pivot->skill_level ?? 1,
                        'color' => $gameType->color,
                        'icon_path' => $gameType->icon_path,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'interests' => $interests,
                    'sport_names' => $interests->pluck('name')->toArray(),
                    'sport_ids' => $interests->pluck('game_type_id')->toArray(),
                    'count' => $interests->count(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('User interests error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user interests',
            ], 500);
        }
    }

    /**
     * Get general activity (fallback when user has no interests)
     */
    private function getGeneralActivity(int $limit = 10, int $page = 1)
    {
        $games = GameEvent::with(['organiser', 'gameType'])
            ->where('starts_at', '>=', now())
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'type' => 'game',
                    'author' => [
                        'id' => $event->organiser->id,
                        'name' => $event->organiser->full_name,
                        'avatar' => $event->organiser->profile_picture,
                    ],
                    'content' => $event->notes ?? "New {$event->gameType->name} game",
                    'sport' => $event->gameType->name,
                    'location' => $event->location,
                    'date' => $event->starts_at->format('l, M j, g:i A'),
                    'created_at' => $event->created_at,
                    'timestamp' => $event->created_at->timestamp,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $games,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $games->count(),
            ],
            'meta' => [
                'filtered' => false,
                'message' => 'Showing general activity. Set your sport interests for personalized content.',
            ],
        ]);
    }

    /**
     * Helper: Format skill level
     */
    private function formatSkillLevel(int|SkillLevel $level): string
    {
        // Handle SkillLevel enum
        if ($level instanceof SkillLevel) {
            $level = $level->value;
        }

        return match ($level) {
            1 => 'Beginner',
            2 => 'Intermediate',
            3 => 'Advanced',
            4 => 'Expert',
            default => 'All Levels',
        };
    }

    /**
     * Helper: Get game status
     */
    private function getGameStatus($game): string
    {
        $participantCount = $game->participants->count();
        $maxParticipants = $game->max_participants;

        if ($participantCount >= $maxParticipants) {
            return 'Full';
        } elseif ($participantCount >= ($maxParticipants * 0.8)) {
            return 'Almost Full';
        } else {
            return 'Open';
        }
    }

    /**
     * Helper: Get stats data for unified dashboard
     */
    private function getStatsData(): array
    {
        return [
            'total_users' => User::where('is_active', true)->count(),
            'online_users' => User::where('is_active', true)
                ->where('last_seen_at', '>=', now()->subMinutes(5))
                ->count(),
            'total_events' => GameEvent::count(),
            'events_this_week' => GameEvent::where('starts_at', '>=', now()->startOfWeek())
                ->where('starts_at', '<=', now()->endOfWeek())
                ->count(),
            'success_rate' => $this->calculateSuccessRate(),
            'community_rating' => 4.8,
        ];
    }

    /**
     * Helper: Get activity data for unified dashboard
     */
    private function getActivityData($user, array $interests, int $limit): array
    {
        if (empty($interests)) {
            return [];
        }

        $activities = collect();

        // Get games
        $games = GameEvent::with(['organiser', 'gameType'])
            ->whereIn('game_type_id', $interests)
            ->where('starts_at', '>=', now())
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'type' => 'game',
                    'author' => [
                        'id' => $event->organiser->id,
                        'name' => $event->organiser->full_name,
                        'avatar' => $event->organiser->profile_picture,
                    ],
                    'content' => $event->notes ?? "Join us for {$event->gameType->name}!",
                    'sport' => $event->gameType->name,
                    'location' => $event->location,
                    'date' => $event->starts_at->format('l, M j, g:i A'),
                    'created_at' => $event->created_at,
                    'timestamp' => $event->created_at->timestamp,
                ];
            });

        return $activities->merge($games)->sortByDesc('timestamp')->take($limit)->values()->toArray();
    }

    /**
     * Helper: Get recommended games data for unified dashboard
     */
    private function getRecommendedGamesData($user, array $interests, int $limit): array
    {
        if (empty($interests)) {
            return [];
        }

        return GameEvent::with(['organiser', 'gameType', 'participants'])
            ->whereIn('game_type_id', $interests)
            ->where('starts_at', '>=', now())
            ->where('organiser_id', '!=', $user->id)
            ->orderBy('starts_at', 'asc')
            ->limit($limit)
            ->get()
            ->map(function ($game) {
                return [
                    'id' => $game->id,
                    'title' => $game->title ?? "{$game->gameType->name} Game",
                    'sport' => $game->gameType->name,
                    'location' => $game->location,
                    'date' => $game->starts_at->format('l, M j, g:i A'),
                    'participants' => $game->participants->count(),
                    'maxParticipants' => $game->max_participants,
                    'status' => $this->getGameStatus($game),
                ];
            })
            ->toArray();
    }

    /**
     * Helper: Get tournaments data for unified dashboard
     */
    private function getTournamentsData($user, array $interests, int $limit): array
    {
        if (empty($interests)) {
            return [];
        }

        return Tournament::with(['gameType', 'participants'])
            ->whereIn('game_type_id', $interests)
            ->whereIn('status', ['open', 'filling-fast', 'registration-open'])
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at', 'asc')
            ->limit($limit)
            ->get()
            ->map(function ($tournament) {
                return [
                    'id' => $tournament->id,
                    'title' => $tournament->name,
                    'sport' => $tournament->gameType->name,
                    'location' => $tournament->location,
                    'date' => $tournament->starts_at->format('M j, Y'),
                    'participants' => $tournament->participants->count(),
                    'maxParticipants' => $tournament->max_participants,
                    'status' => ucfirst(str_replace('-', ' ', $tournament->status)),
                ];
            })
            ->toArray();
    }

    /**
     * Helper: Calculate success rate
     */
    private function calculateSuccessRate(): int
    {
        $eventsWithParticipants = GameEvent::whereHas('participants')
            ->where('starts_at', '<', now())
            ->count();
        $completedEvents = GameEvent::where('starts_at', '<', now())->count();

        return $completedEvents > 0 ? (int) round(($eventsWithParticipants / $completedEvents) * 100) : 0;
    }
}

