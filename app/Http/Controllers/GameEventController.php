<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\GameEventRepository;
use App\Models\GameEvent;
use App\Models\GameType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GameEventController extends Controller
{
    public function __construct(public GameEventRepository $service) {}

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
                'per_page' => 'nullable|integer|min:1|max:50',
                'page' => 'nullable|integer|min:1',
            ]);

            $perPage = $validated['per_page'] ?? 12; // Show 12 games per page for better UX
            $page = $validated['page'] ?? 1;
            $user = $request->user();

            $query = GameEvent::with(['gameType', 'organiser', 'participants'])
                ->where('starts_at', '>=', now())
                ->orderBy('starts_at', 'asc');

            // Apply filters
            if (isset($validated['sport'])) {
                $query->whereHas('gameType', function ($q) use ($validated) {
                    $q->where('name', $validated['sport']);
                });
            }

            if (isset($validated['location'])) {
                $query->where('location', 'like', '%' . $validated['location'] . '%');
            }

            if (isset($validated['date_from'])) {
                $query->where('starts_at', '>=', $validated['date_from']);
            }

            if (isset($validated['date_to'])) {
                $query->where('starts_at', '<=', $validated['date_to']);
            }

            if (isset($validated['skill_level'])) {
                $query->where('skill_level', $validated['skill_level']);
            }

            $events = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $events->map(function ($event) use ($user) {
                    return [
                        'id' => $event->id,
                        'title' => $event->gameType->name . ' Game',
                        'sport' => $event->gameType->name,
                        'location' => $event->location,
                        'starts_at' => Carbon::parse($event->starts_at)->format('Y-m-d H:i'),
                        'starts_at_relative' => Carbon::parse($event->starts_at)->diffForHumans(),
                        'skill_level' => $event->skill_level->value,
                        'skill_level_label' => $event->skill_level->label(),
                        'venue_booked' => $event->venue_booked,
                        'notes' => $event->notes,
                        'max_participants' => $event->max_participants,
                        'current_participants' => $event->participants()->count(),
                        'waiting_list_enabled' => $event->waiting_list_enabled,
                        'is_full' => $event->max_participants && $event->participants()->count() >= $event->max_participants,
                        'organiser' => [
                            'id' => $event->organiser->id,
                            'name' => $event->organiser->full_name,
                            'avatar' => $event->organiser->profile_picture,
                        ],
                        'participants' => $event->participants->map(function ($participant) {
                            return [
                                'id' => $participant->id,
                                'name' => $participant->full_name,
                                'avatar' => $participant->profile_picture,
                                'is_waiting' => $participant->pivot->is_waiting,
                            ];
                        }),
                        'user_participation' => [
                            'is_participating' => $event->participants()->where('user_id', $user->id)->exists(),
                            'is_waiting' => $event->participants()->where('user_id', $user->id)->where('is_waiting', true)->exists(),
                            'can_join' => !$event->participants()->where('user_id', $user->id)->exists() &&
                                        (!$event->max_participants || $event->participants()->count() < $event->max_participants),
                        ],
                        'created_at' => $event->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $event->updated_at->format('Y-m-d H:i:s'),
                    ];
                }),
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
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'game_type_id' => 'required|exists:game_types,id',
                'location' => 'required|string|max:255',
                'starts_at' => 'required|date|after:now',
                'skill_level' => 'required|integer|min:1|max:3',
                'max_participants' => 'nullable|integer|min:1|max:50',
                'waiting_list_enabled' => 'boolean',
                'notes' => 'nullable|string|max:1000',
                'venue_booked' => 'boolean',
            ]);

            DB::beginTransaction();

            $event = GameEvent::create([
                ...$validated,
                'organiser_id' => $request->user()->id,
            ]);

            // Auto-join the organiser
            $event->participants()->attach($request->user()->id, ['is_waiting' => false]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Game event created successfully',
                'data' => [
                    'id' => $event->id,
                    'sport' => $event->gameType->name,
                    'location' => $event->location,
                    'starts_at' => Carbon::parse($event->starts_at)->format('Y-m-d H:i'),
                    'skill_level' => $event->skill_level->value,
                    'organiser' => [
                        'id' => $event->organiser->id,
                        'name' => $event->organiser->full_name,
                    ],
                ]
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GameEvent store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create game event',
            ], 500);
        }
    }

    /**
     * Get a specific game event
     */
    public function show(GameEvent $event): JsonResponse
    {
        try {
            $event->load(['gameType', 'organiser', 'participants']);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $event->id,
                    'title' => $event->gameType->name . ' Game',
                    'sport' => $event->gameType->name,
                    'location' => $event->location,
                    'starts_at' => Carbon::parse($event->starts_at)->format('Y-m-d H:i'),
                    'starts_at_relative' => Carbon::parse($event->starts_at)->diffForHumans(),
                    'skill_level' => $event->skill_level->value,
                    'skill_level_label' => $event->skill_level->label(),
                    'venue_booked' => $event->venue_booked,
                    'notes' => $event->notes,
                    'max_participants' => $event->max_participants,
                    'current_participants' => $event->participants()->count(),
                    'waiting_list_enabled' => $event->waiting_list_enabled,
                    'is_full' => $event->max_participants && $event->participants()->count() >= $event->max_participants,
                    'organiser' => [
                        'id' => $event->organiser->id,
                        'name' => $event->organiser->full_name,
                        'avatar' => $event->organiser->profile_picture,
                    ],
                    'participants' => $event->participants->map(function ($participant) {
                        return [
                            'id' => $participant->id,
                            'name' => $participant->full_name,
                            'avatar' => $participant->profile_picture,
                            'is_waiting' => $participant->pivot->is_waiting,
                        ];
                    }),
                    'created_at' => $event->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $event->updated_at->format('Y-m-d H:i:s'),
                ]
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
    public function update(Request $request, GameEvent $event): JsonResponse
    {
        try {
            // Check if user is the organiser
            if ($event->organiser_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized - Only the organiser can update this event'
                ], 403);
            }

            $validated = $request->validate([
                'game_type_id' => 'sometimes|exists:game_types,id',
                'location' => 'sometimes|string|max:255',
                'starts_at' => 'sometimes|date|after:now',
                'skill_level' => 'sometimes|integer|min:1|max:3',
                'max_participants' => 'nullable|integer|min:1|max:50',
                'waiting_list_enabled' => 'sometimes|boolean',
                'notes' => 'nullable|string|max:1000',
                'venue_booked' => 'sometimes|boolean',
            ]);

            $event->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Game event updated successfully',
                'data' => [
                    'id' => $event->id,
                    'sport' => $event->gameType->name,
                    'location' => $event->location,
                    'starts_at' => Carbon::parse($event->starts_at)->format('Y-m-d H:i'),
                    'skill_level' => $event->skill_level->value,
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('GameEvent update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update game event',
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
    public function join(Request $request, GameEvent $event): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if user is already participating
            if ($event->participants()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Already participating in this event'
                ], 400);
            }

            // Check if event is full
            if ($event->max_participants && $event->participants()->count() >= $event->max_participants) {
                if (!$event->waiting_list_enabled) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Event is full and waiting list is disabled'
                    ], 400);
                }
                // Add to waiting list
                $event->participants()->attach($user->id, ['is_waiting' => true]);
                return response()->json([
                    'success' => true,
                    'message' => 'Added to waiting list'
                ]);
            }

            $event->participants()->attach($user->id, ['is_waiting' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Successfully joined the event'
            ]);

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
    public function leave(Request $request, GameEvent $event): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if user is participating
            if (!$event->participants()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not participating in this event'
                ], 400);
            }

            $event->participants()->detach($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Successfully left the event'
            ]);

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
            $stats = GameType::withCount(['gameEvents' => function ($query) {
                $query->where('starts_at', '>=', now());
            }])->get()->map(function ($sport) {
                return [
                    'name' => $sport->name,
                    'count' => $sport->game_events_count,
                    'color' => $this->getSportColor($sport->name),
                ];
            });

            $totalEvents = GameEvent::where('starts_at', '>=', now())->count();
            $totalParticipants = DB::table('game_event_participants')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'sports' => $stats,
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
}
