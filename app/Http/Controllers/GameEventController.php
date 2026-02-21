<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\GameEventRepository;
use App\Models\GameEvent;
use App\Models\GameType;
use App\Models\User;
use App\Models\Community;
use App\Models\Conversation;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Events\StoreEventRequest;
use App\Http\Requests\Events\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Actions\Events\CreateGameEventAction;
use App\Actions\Events\JoinGameEventAction;
use App\Actions\Events\LeaveGameEventAction;

use App\Services\ContentFilterService;

class GameEventController extends Controller
{
    protected ContentFilterService $contentFilter;

    public function __construct(GameEventRepository $service, ContentFilterService $contentFilter)
    {
        $this->service = $service;
        $this->contentFilter = $contentFilter;
    }

    /**
     * Get all game events with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'sport' => 'nullable|string|exists:game_types,name',
                'location' => 'nullable|string|max:255',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'skill_level' => 'nullable|integer|min:1|max:3',
                'my_games_only' => 'nullable|in:0,1,true,false',
                'nearby_only' => 'nullable|in:0,1,true,false',
                'radius_km' => 'nullable|numeric|min:1|max:100',
                'community_id' => 'nullable|exists:communities,id',
                'per_page' => 'nullable|integer|min:1|max:50',
                'page' => 'nullable|integer|min:1',
            ]);

            $perPage = $validated['per_page'] ?? 12; // Show 12 games per page for better UX
            $page = $validated['page'] ?? 1;
            $user = $request->user();

            // Get blocked user IDs
            $blockedUserIds = $user ? $user->blockedUsers()->pluck('blocked_user_id')->toArray() : [];

            $query = GameEvent::with(['gameType', 'organiser', 'participants', 'community'])
                ->where('starts_at', '>', now()->startOfMinute())
                ->whereHas('gameType', function ($q) {
                    $q->whereNotNull('name')->where('name', '!=', '');
                });

            if (!empty($blockedUserIds)) {
                $query->whereNotIn('organiser_id', $blockedUserIds);
            }

            // If user has location, calculate distance and sort by distance
            if ($user && $user->latitude && $user->longitude) {
                $query->selectRaw("
                    game_events.*,
                    CASE
                        WHEN latitude IS NOT NULL AND longitude IS NOT NULL
                        THEN (6371 * acos(cos(radians(?)) * cos(radians(latitude)) *
                        cos(radians(longitude) - radians(?)) + sin(radians(?)) *
                        sin(radians(latitude))))
                        ELSE NULL
                    END AS distance
                ", [$user->latitude, $user->longitude, $user->latitude])
                    ->orderByRaw('CASE WHEN distance IS NOT NULL THEN 0 ELSE 1 END')
                    ->orderBy('distance', 'asc')
                    ->orderBy('starts_at', 'asc');
            } else {
                $query->orderBy('starts_at', 'asc');
            }

            // Apply filters
            if (isset($validated['sport'])) {
                $query->whereHas('gameType', function ($q) use ($validated) {
                    $q->where('name', $validated['sport']);
                });
            }

            if (isset($validated['location'])) {
                $query->where('location', 'like', '%' . $validated['location'] . '%');
            }

            // Handle nearby games filter
            if (isset($validated['nearby_only']) && $user) {
                $nearbyOnly = $validated['nearby_only'];
                $shouldFilter = in_array($nearbyOnly, [true, 'true', '1', 1]);
                $radiusKm = $validated['radius_km'] ?? 10; // Default 10km radius

                if ($shouldFilter && $user->latitude && $user->longitude) {
                    $query->whereRaw('
                        (6371 * acos(cos(radians(?)) * cos(radians(latitude)) *
                        cos(radians(longitude) - radians(?)) + sin(radians(?)) *
                        sin(radians(latitude)))) <= ?
                    ', [$user->latitude, $user->longitude, $user->latitude, $radiusKm]);
                }
            }

            // Handle community filter
            if (isset($validated['community_id'])) {
                $query->where('community_id', $validated['community_id']);
            }

            if (isset($validated['date_from'])) {
                $query->whereDate('starts_at', '>=', $validated['date_from']);
            }

            if (isset($validated['date_to'])) {
                $query->whereDate('starts_at', '<=', $validated['date_to']);
            }

            if (isset($validated['skill_level'])) {
                $query->where('skill_level', $validated['skill_level']);
            }

            // Handle my_games_only filter
            if (isset($validated['my_games_only']) && $user) {
                $myGamesOnly = $validated['my_games_only'];
                $shouldFilter = in_array($myGamesOnly, [true, 'true', '1', 1]);

                if ($shouldFilter) {
                    $query->where('organiser_id', $user->id);
                }
            }

            $events = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => EventResource::collection($events),
                'pagination' => [
                    'current_page' => $events->currentPage(),
                    'last_page' => $events->lastPage(),
                    'per_page' => $events->perPage(),
                    'total' => $events->total(),
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('GameEvent index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch game events',
            ], 500);
        }
    }

    /**
     * Create a new game event
     */
    /**
     * Create a new game event
     */
    public function store(StoreEventRequest $request, CreateGameEventAction $action): JsonResponse
    {
        try {
            $data = $request->validated();

            // Validate content for profanity
            $this->contentFilter->validateContent(
                $data['title'] ?? '',
                $data['description'] ?? '',
                $data['notes'] ?? ''
            );

            $event = $action->execute($data, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Game event created successfully',
                'data' => new EventResource($event->load(['gameType', 'organiser']))
            ], 201);

        } catch (\Exception $e) {
            Log::error('GameEvent store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Failed to create game event',
            ], 500);
        }
    }

    /**
     * Get a specific game event
     */
    /**
     * Get a specific game event
     */
    public function show(Request $request, GameEvent $event): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => new EventResource($event->load(['gameType', 'organiser', 'participants', 'community']))
            ]);

        } catch (\Exception $e) {
            Log::error('GameEvent show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch game event',
            ], 500);
        }
    }

    /**
     * Update a game event
     */
    /**
     * Update a game event
     */
    public function update(UpdateEventRequest $request, GameEvent $event): JsonResponse
    {
        try {
            $data = $request->validated();

            // Validate content for profanity
            $this->contentFilter->validateContent(
                $data['title'] ?? '',
                $data['description'] ?? '',
                $data['notes'] ?? ''
            );

            $event->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Game event updated successfully',
                'data' => new EventResource($event->load(['gameType', 'organiser']))
            ]);

        } catch (\Exception $e) {
            Log::error('GameEvent update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Failed to update game event',
            ], 500);
        }
    }

    /**
     * Delete a game event
     */
    public function destroy(Request $request, GameEvent $event): JsonResponse
    {
        try {
            // Check if user is the organiser
            if ($event->organiser_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized - Only the organiser can delete this event'
                ], 403);
            }

            $event->delete();

            return response()->json([
                'success' => true,
                'message' => 'Game event deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('GameEvent destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete game event',
            ], 500);
        }
    }

    /**
     * Join a game event
     */
    /**
     * Join a game event
     */
    public function join(Request $request, GameEvent $event, JoinGameEventAction $action): JsonResponse
    {
        try {
            $result = $action->execute($event, $request->user());

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('GameEvent join error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to join event',
            ], 500);
        }
    }

    /**
     * Leave a game event
     */
    /**
     * Leave a game event
     */
    public function leave(Request $request, GameEvent $event, LeaveGameEventAction $action): JsonResponse
    {
        try {
            $result = $action->execute($event, $request->user());

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('GameEvent leave error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to leave event',
            ], 500);
        }
    }

    /**
     * Get sports statistics
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            // Get sport statistics with total counts (not just upcoming)
            $sportStats = GameType::withCount('gameEvents')->get()->map(function ($sport) {
                return [
                    'name' => $sport->name,
                    'count' => $sport->game_events_count,
                    'color' => $this->getSportColor($sport->name),
                ];
            })->filter(function ($sport) {
                return $sport['count'] > 0; // Only return sports with events
            })->sortByDesc('count')->values();

            // Get upcoming events count
            $upcomingEvents = GameEvent::where('starts_at', '>=', now())->count();
            $totalEvents = GameEvent::count();
            $totalParticipants = DB::table('game_event_participants')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'sports' => $sportStats,
                    'upcoming_events' => $upcomingEvents,
                    'total_events' => $totalEvents,
                    'total_participants' => $totalParticipants,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('GameEvent stats error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
            ], 500);
        }
    }

    /**
     * Get sport color for UI
     */
    private function getSportColor(string $sport): string
    {
        return match (strtolower($sport)) {
            'tennis' => 'bg-sport-green',
            'football' => 'bg-sport-orange',
            'basketball' => 'bg-sport-blue',
            'cycling' => 'bg-sport-red',
            'swimming' => 'bg-primary',
            default => 'bg-accent',
        };
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Format distance for display
     */
    private function formatDistance(float $distance): string
    {
        $miles = $distance * 0.621371; // Convert km to miles

        if ($distance < 8) { // ~5 miles in km
            return '< 5 miles away';
        } elseif ($distance < 10) {
            return round($miles, 1) . ' miles';
        } else {
            return round($miles) . ' miles';
        }
    }

    /**
     * Get comments for a game event
     */
    public function getComments(Request $request, GameEvent $event): JsonResponse
    {
        try {
            $comments = $event->comments()
                ->with('author:id,full_name,profile_picture')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'body' => $comment->body,
                        'author' => [
                            'id' => $comment->author->id,
                            'name' => $comment->author->full_name,
                            'avatar' => $comment->author->profile_picture,
                        ],
                        'created_at' => $comment->created_at->format('Y-m-d H:i:s'),
                        'created_at_relative' => $comment->created_at->diffForHumans(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $comments
            ]);

        } catch (\Exception $e) {
            Log::error('GameEvent getComments error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch comments',
            ], 500);
        }
    }

    /**
     * Add a comment to a game event
     */
    public function addComment(Request $request, GameEvent $event): JsonResponse
    {
        try {
            $validated = $request->validate([
                'body' => 'required|string|max:1000',
            ]);

            // Validate content for profanity
            $this->contentFilter->validateContent($validated['body']);

            $user = $request->user();

            $comment = $event->comments()->create([
                'user_id' => $user->id,
                'body' => $validated['body'],
            ]);

            // Notify Game Organiser
            $organiser = $event->organiser;
            if ($organiser && $organiser->id !== $user->id) {
                $gameName = $event->gameType->name ?? 'your game';
                $organiser->notify(new \App\Notifications\NewCommentNotification(
                    $user,
                    $gameName,
                    "/games/{$event->id}"
                ));
            }

            $comment->load('author:id,full_name,profile_picture');

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'author' => [
                        'id' => $comment->author->id,
                        'name' => $comment->author->full_name,
                        'avatar' => $comment->author->profile_picture,
                    ],
                    'created_at' => $comment->created_at->format('Y-m-d H:i:s'),
                    'created_at_relative' => $comment->created_at->diffForHumans(),
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('GameEvent addComment error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Failed to add comment',
            ], 500);
        }
    }

    /**
     * Delete a comment from a game event
     */
    public function deleteComment(Request $request, GameEvent $event, int $commentId): JsonResponse
    {
        try {
            $user = $request->user();
            $comment = $event->comments()->where('id', $commentId)->first();

            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found'
                ], 404);
            }

            // Check if user is the comment author or event organiser
            if ($comment->user_id !== $user->id && $event->organiser_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized - Only the comment author or event organiser can delete comments'
                ], 403);
            }

            $comment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('GameEvent deleteComment error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete comment',
            ], 500);
        }
    }
}
