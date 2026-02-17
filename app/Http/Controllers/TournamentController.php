<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\TournamentBracket;
use App\Models\User;
use App\Models\GameType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TournamentController extends Controller
{
    /**
     * Get all tournaments with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'sport' => 'nullable|string|exists:game_types,name',
                'game_type' => 'nullable|string|max:255',
                'status' => 'nullable|string|in:draft,open,filling-fast,almost-full,registration-closed,in-progress,completed,cancelled,pending_approval',
                'skill_level' => 'nullable|integer|min:1|max:4',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
                'featured_only' => 'nullable|boolean',
                'my_tournaments_only' => 'nullable|boolean',
                'filter_by_interests' => 'nullable|string|in:true,false,0,1',
                'per_page' => 'nullable|integer|min:1|max:50',
                'page' => 'nullable|integer|min:1',
            ]);

            $perPage = $validated['per_page'] ?? 15;
            $page = $validated['page'] ?? 1;
            $user = $request->user();

            $query = Tournament::with(['gameType', 'organiser', 'participants'])
                ->withCount(['participants as current_participants_count']);

            // Apply search filter
            if (isset($validated['search'])) {
                $query->where(function ($q) use ($validated) {
                    $q->where('name', 'like', '%' . $validated['search'] . '%')
                        ->orWhere('description', 'like', '%' . $validated['search'] . '%')
                        ->orWhere('location', 'like', '%' . $validated['search'] . '%');
                });
            }

            // Apply user interest filtering (DEFAULT BEHAVIOR)
            $shouldFilterByInterests = !isset($validated['filter_by_interests']) ||
                (isset($validated['filter_by_interests']) &&
                    in_array($validated['filter_by_interests'], ['true', '1'], true));

            if ($shouldFilterByInterests) {
                $userInterests = $user->gameInterests()->pluck('game_type_id')->toArray();

                if (!empty($userInterests)) {
                    Log::info('Filtering tournaments by user interests:', [
                        'user_id' => $user->id,
                        'interests' => $userInterests
                    ]);

                    $query->whereIn('game_type_id', $userInterests);
                }
            }

            // Apply sport filter
            if (isset($validated['sport'])) {
                $query->whereHas('gameType', function ($q) use ($validated) {
                    $q->where('name', $validated['sport']);
                });
            }

            // Apply game_type filter (for dropdown)
            if (isset($validated['game_type'])) {
                $query->whereHas('gameType', function ($q) use ($validated) {
                    $gameTypeMap = [
                        'football' => 'Football',
                        'tennis' => 'Tennis',
                        'basketball' => 'Basketball',
                        'cricket' => 'Cricket',
                        'rugby' => 'Rugby',
                        'golf' => 'Golf',
                        'swimming' => 'Swimming',
                        'cycling' => 'Cycling',
                        'running' => 'Running',
                        'volleyball' => 'Volleyball',
                        'badminton' => 'Badminton',
                        'table-tennis' => 'Table Tennis',
                        'hockey' => 'Hockey',
                        'boxing' => 'Boxing',
                        'martial-arts' => 'Martial Arts',
                    ];

                    $dbGameType = $gameTypeMap[$validated['game_type']] ?? $validated['game_type'];
                    $q->where('name', $dbGameType);
                });
            }

            // Apply status filter
            if (isset($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            // Apply skill level filter
            if (isset($validated['skill_level'])) {
                $query->where('skill_level', $validated['skill_level']);
            }

            // Apply date range filter
            if (isset($validated['date_from'])) {
                $query->where('starts_at', '>=', $validated['date_from']);
            }
            if (isset($validated['date_to'])) {
                $query->where('starts_at', '<=', $validated['date_to'] . ' 23:59:59');
            }

            // Apply featured filter
            if (isset($validated['featured_only']) && $validated['featured_only']) {
                $query->where('is_featured', true);
            }

            // Apply my tournaments filter
            if (isset($validated['my_tournaments_only']) && $validated['my_tournaments_only']) {
                $query->where('organiser_id', $user->id);
            }

            // Order by featured first, then by start date
            $query->orderBy('is_featured', 'desc')
                ->orderBy('starts_at', 'asc');

            $tournaments = $query->paginate($perPage, ['*'], 'page', $page);

            // Get user interests for response metadata
            $userInterests = $user->gameInterests()->get();
            $interestNames = $userInterests->pluck('name')->toArray();
            $hasInterests = !empty($userInterests);

            return response()->json([
                'success' => true,
                'data' => $tournaments->map(function ($tournament) use ($user) {
                    return [
                        'id' => $tournament->id,
                        'name' => $tournament->name,
                        'description' => $tournament->description,
                        'sport' => [
                            'id' => $tournament->gameType->id,
                            'name' => $tournament->gameType->name,
                            'color' => $tournament->gameType->color,
                        ],
                        'organiser' => [
                            'id' => $tournament->organiser->id,
                            'name' => $tournament->organiser->full_name,
                            'avatar' => $tournament->organiser->profile_picture,
                        ],
                        'location' => $tournament->location,
                        'address' => $tournament->address,
                        'starts_at' => $tournament->starts_at->format('Y-m-d H:i:s'),
                        'ends_at' => $tournament->ends_at->format('Y-m-d H:i:s'),
                        'registration_deadline' => $tournament->registration_deadline->format('Y-m-d H:i:s'),
                        'max_participants' => $tournament->max_participants,
                        'current_participants' => $tournament->current_participants_count,
                        'entry_fee' => $tournament->entry_fee,
                        'prize_pool' => $tournament->prize_pool,
                        'prize_description' => $tournament->prize_description,
                        'skill_level' => $tournament->skill_level,
                        'skill_level_label' => $tournament->skill_level_label,
                        'status' => $tournament->status,
                        'status_color' => $tournament->status_color,
                        'is_featured' => $tournament->is_featured,
                        'registration_enabled' => $tournament->registration_enabled,
                        'waiting_list_enabled' => $tournament->waiting_list_enabled,
                        'registration_progress' => $tournament->registration_progress,
                        'is_full' => $tournament->is_full,
                        'deadline_text' => $tournament->deadline_text,
                        'days_until_deadline' => $tournament->days_until_deadline,
                        'user_participation' => [
                            'is_registered' => $tournament->participants()->where('user_id', $user->id)->exists(),
                            'is_waiting' => $tournament->participants()->where('user_id', $user->id)->where('is_waiting', true)->exists(),
                            'can_register' => $tournament->registration_enabled && !$tournament->is_full && $tournament->registration_deadline > now(),
                            'can_edit' => $tournament->organiser_id === $user->id,
                        ],
                        'created_at' => $tournament->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $tournament->updated_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'pagination' => [
                    'current_page' => $tournaments->currentPage(),
                    'last_page' => $tournaments->lastPage(),
                    'per_page' => $tournaments->perPage(),
                    'total' => $tournaments->total(),
                ],
                'meta' => [
                    'filtered_by_interests' => $shouldFilterByInterests && $hasInterests,
                    'user_interests' => [
                        'names' => $interestNames,
                        'count' => count($interestNames),
                        'has_interests' => $hasInterests,
                    ],
                    'message' => $hasInterests
                        ? "Showing tournaments for your interests: " . implode(', ', $interestNames)
                        : "Showing all tournaments. Set your sport interests for personalized content.",
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Tournament index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tournaments',
            ], 500);
        }
    }

    /**
     * Get a specific tournament with details
     */
    public function show(Tournament $tournament): JsonResponse
    {
        try {
            $tournament->load(['gameType', 'organiser', 'participants', 'matches', 'brackets']);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $tournament->id,
                    'name' => $tournament->name,
                    'description' => $tournament->description,
                    'sport' => [
                        'id' => $tournament->gameType->id,
                        'name' => $tournament->gameType->name,
                        'color' => $tournament->gameType->color,
                    ],
                    'organiser' => [
                        'id' => $tournament->organiser->id,
                        'name' => $tournament->organiser->full_name,
                        'avatar' => $tournament->organiser->profile_picture,
                    ],
                    'location' => $tournament->location,
                    'address' => $tournament->address,
                    'city' => $tournament->city,
                    'state' => $tournament->state,
                    'postal_code' => $tournament->postal_code,
                    'country' => $tournament->country,
                    'latitude' => $tournament->latitude,
                    'longitude' => $tournament->longitude,
                    'starts_at' => $tournament->starts_at->format('Y-m-d H:i:s'),
                    'ends_at' => $tournament->ends_at->format('Y-m-d H:i:s'),
                    'registration_deadline' => $tournament->registration_deadline->format('Y-m-d H:i:s'),
                    'max_participants' => $tournament->max_participants,
                    'min_participants' => $tournament->min_participants,
                    'current_participants' => $tournament->current_participants_count,
                    'waiting_list_count' => $tournament->waiting_list_count,
                    'entry_fee' => $tournament->entry_fee,
                    'prize_pool' => $tournament->prize_pool,
                    'prize_description' => $tournament->prize_description,
                    'skill_level' => $tournament->skill_level,
                    'skill_level_label' => $tournament->skill_level_label,
                    'status' => $tournament->status,
                    'status_color' => $tournament->status_color,
                    'is_featured' => $tournament->is_featured,
                    'rules' => $tournament->rules,
                    'format' => $tournament->format,
                    'bracket_type' => $tournament->bracket_type,
                    'registration_enabled' => $tournament->registration_enabled,
                    'waiting_list_enabled' => $tournament->waiting_list_enabled,
                    'registration_progress' => $tournament->registration_progress,
                    'is_full' => $tournament->is_full,
                    'deadline_text' => $tournament->deadline_text,
                    'days_until_deadline' => $tournament->days_until_deadline,
                    'participants' => $tournament->participants->map(function ($participant) {
                        return [
                            'id' => $participant->id,
                            'name' => $participant->full_name,
                            'avatar' => $participant->profile_picture,
                            'registration_date' => $participant->pivot->registration_date,
                            'payment_status' => $participant->pivot->payment_status,
                            'is_waiting' => $participant->pivot->is_waiting,
                        ];
                    }),
                    'matches' => $tournament->matches->map(function ($match) {
                        return [
                            'id' => $match->id,
                            'match_number' => $match->match_number,
                            'round' => $match->round,
                            'player1' => $match->player1 ? [
                                'id' => $match->player1->id,
                                'name' => $match->player1->full_name,
                            ] : null,
                            'player2' => $match->player2 ? [
                                'id' => $match->player2->id,
                                'name' => $match->player2->full_name,
                            ] : null,
                            'winner' => $match->winner ? [
                                'id' => $match->winner->id,
                                'name' => $match->winner->full_name,
                            ] : null,
                            'scheduled_at' => $match->scheduled_at?->format('Y-m-d H:i:s'),
                            'status' => $match->status,
                            'score' => $match->score,
                        ];
                    }),
                    'created_at' => $tournament->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $tournament->updated_at->format('Y-m-d H:i:s'),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Tournament show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tournament',
            ], 500);
        }
    }

    /**
     * Create a new tournament
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'game_type_id' => 'required|exists:game_types,id',
                'location' => 'required|string|max:255',
                'address' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:255',
                'state' => 'nullable|string|max:255',
                'postal_code' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'starts_at' => 'required|date|after:now',
                'ends_at' => 'required|date|after:starts_at',
                'registration_deadline' => 'required|date|before:starts_at',
                'max_participants' => 'nullable|integer|min:2',
                'min_participants' => 'nullable|integer|min:2',
                'entry_fee' => 'nullable|numeric|min:0',
                'prize_pool' => 'nullable|numeric|min:0',
                'prize_description' => 'nullable|string|max:500',
                'skill_level' => 'required|integer|min:1|max:4',
                'rules' => 'nullable|string|max:2000',
                'format' => 'nullable|string|in:single-elimination,double-elimination,round-robin,swiss',
                'bracket_type' => 'nullable|string|in:standard,seeded,random',
                'waiting_list_enabled' => 'boolean',
            ]);

            DB::beginTransaction();

            $user = $request->user();

            $tournament = Tournament::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'game_type_id' => $validated['game_type_id'],
                'organiser_id' => $user->id,
                'location' => $validated['location'],
                'address' => $validated['address'],
                'city' => $validated['city'] ?? null,
                'state' => $validated['state'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'country' => $validated['country'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'starts_at' => $validated['starts_at'],
                'ends_at' => $validated['ends_at'],
                'registration_deadline' => $validated['registration_deadline'],
                'max_participants' => $validated['max_participants'],
                'min_participants' => $validated['min_participants'] ?? 2,
                'entry_fee' => $validated['entry_fee'] ?? 0,
                'prize_pool' => $validated['prize_pool'] ?? 0,
                'prize_description' => $validated['prize_description'] ?? '',
                'skill_level' => $validated['skill_level'],
                'status' => 'open', // Changed from 'pending_approval' to show immediately
                'approval_status' => 'approved', // Auto-approve for now
                'rules' => $validated['rules'] ?? '',
                'format' => $validated['format'] ?? 'single-elimination',
                'bracket_type' => $validated['bracket_type'] ?? 'standard',
                'registration_enabled' => true,
                'waiting_list_enabled' => $validated['waiting_list_enabled'] ?? false,
            ]);

            // Auto-create tournament conversation
            $conversation = \App\Models\Conversation::create([
                'type' => 'group', // Use group type to enable member list in app
                'name' => $tournament->name . ' - Tournament Chat',
                'context_id' => $tournament->id,
            ]);

            // Add the organiser to the conversation
            $conversation->participants()->attach($user->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tournament created successfully',
                'data' => [
                    'id' => $tournament->id,
                    'name' => $tournament->name,
                    'status' => $tournament->status,
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
            Log::error('Tournament store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create tournament',
            ], 500);
        }
    }

    /**
     * Register for a tournament
     */
    public function register(Request $request, Tournament $tournament): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if user is already registered
            if ($tournament->participants()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already registered for this tournament',
                ], 400);
            }

            // Check if registration is enabled
            if (!$tournament->registration_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registration is not enabled for this tournament',
                ], 400);
            }

            // Check if registration deadline has passed
            if ($tournament->registration_deadline < now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registration deadline has passed',
                ], 400);
            }

            // Check if tournament is full
            $isWaiting = false;
            if ($tournament->is_full) {
                if (!$tournament->waiting_list_enabled) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tournament is full',
                    ], 400);
                }
                $isWaiting = true;
            }

            DB::beginTransaction();

            $tournament->participants()->attach($user->id, [
                'registration_date' => now(),
                'payment_status' => 'pending',
                'is_waiting' => $isWaiting,
            ]);

            // Auto-add user to tournament conversation (create if missing for older tournaments)
            $tournamentConversation = \App\Models\Conversation::where('type', 'group')
                ->where('context_id', $tournament->id)
                ->first();

            if (!$tournamentConversation) {
                $tournamentConversation = \App\Models\Conversation::create([
                    'type' => 'group',
                    'name' => $tournament->name . ' - Tournament Chat',
                    'context_id' => $tournament->id,
                ]);
                $tournamentConversation->participants()->attach($tournament->organiser_id);
            }

            if (!$tournamentConversation->participants()->where('users.id', $user->id)->exists()) {
                $tournamentConversation->participants()->attach($user->id);
            }

            DB::commit();

            $responseData = ['is_waiting' => $isWaiting];
            $responseData['conversation_id'] = $tournamentConversation->id;

            return response()->json([
                'success' => true,
                'message' => $isWaiting ? 'Added to waiting list' : 'Successfully registered for tournament',
                'data' => $responseData,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Tournament register error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to register for tournament',
            ], 500);
        }
    }

    /**
     * Unregister from a tournament
     */
    public function unregister(Request $request, Tournament $tournament): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if user is registered
            if (!$tournament->participants()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not registered for this tournament',
                ], 400);
            }

            DB::beginTransaction();

            $tournament->participants()->detach($user->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Successfully unregistered from tournament',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Tournament unregister error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to unregister from tournament',
            ], 500);
        }
    }

    /**
     * Get available game types for tournaments (user's interests only)
     */
    public function availableGameTypes(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Get user's sport interests
            $userInterests = $user->gameInterests()->get();

            if ($userInterests->isEmpty()) {
                // New users with no interests: return all game types
                $allTypes = GameType::orderBy('name')->get(['id', 'name', 'color', 'icon_path']);
                return response()->json([
                    'success' => true,
                    'data' => $allTypes->map(fn($gt) => [
                        'id' => $gt->id,
                        'name' => $gt->name,
                        'color' => $gt->color,
                        'icon_path' => $gt->icon_path,
                    ])->values()->all(),
                    'message' => 'All game types (set your interests in profile for personalized lists)',
                ]);
            }

            // Return the user's interests as available game types
            $gameTypes = $userInterests->map(function ($interest) {
                return [
                    'id' => $interest->id,
                    'name' => $interest->name,
                    'color' => $interest->color,
                    'icon_path' => $interest->icon_path,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $gameTypes,
                'message' => 'Available game types based on your interests'
            ]);

        } catch (\Exception $e) {
            Log::error('Available game types error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch available game types',
            ], 500);
        }
    }

    /**
     * Get upcoming matches for tournaments
     */
    public function upcomingMatches(Request $request): JsonResponse
    {
        try {
            $matches = TournamentMatch::with(['tournament.gameType', 'player1', 'player2'])
                ->where('status', 'scheduled')
                ->where('scheduled_at', '>=', now())
                ->orderBy('scheduled_at', 'asc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $matches->map(function ($match) {
                    return [
                        'id' => $match->id,
                        'tournament' => [
                            'id' => $match->tournament->id,
                            'name' => $match->tournament->name,
                            'sport' => $match->tournament->gameType->name,
                        ],
                        'match_number' => $match->match_number,
                        'round' => $match->round,
                        'player1' => $match->player1 ? [
                            'id' => $match->player1->id,
                            'name' => $match->player1->full_name,
                        ] : null,
                        'player2' => $match->player2 ? [
                            'id' => $match->player2->id,
                            'name' => $match->player2->full_name,
                        ] : null,
                        'scheduled_at' => $match->scheduled_at->format('Y-m-d H:i:s'),
                        'scheduled_at_relative' => $match->scheduled_at->diffForHumans(),
                    ];
                }),
            ]);

        } catch (\Exception $e) {
            Log::error('Tournament upcoming matches error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch upcoming matches',
            ], 500);
        }
    }

    /**
     * Update tournament
     */
    public function update(Request $request, Tournament $tournament): JsonResponse
    {
        try {
            // Check if user is the organiser
            if ($request->user()->id !== $tournament->organiser_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only update your own tournaments'
                ], 403);
            }

            // Only allow updates if tournament is in draft status
            if ($tournament->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tournament cannot be updated in its current status'
                ], 403);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'game_type_id' => 'required|integer|exists:game_types,id',
                'location' => 'required|string|max:255',
                'address' => 'required|string',
                'starts_at' => 'required|date|after:today',
                'ends_at' => 'required|date|after:starts_at',
                'registration_deadline' => 'required|date|before:starts_at',
                'max_participants' => 'required|integer|min:1',
                'entry_fee' => 'required|numeric|min:0',
                'prize_pool' => 'required|numeric|min:0',
                'prize_description' => 'nullable|string',
                'skill_level' => 'required|integer|min:1|max:4',
                'rules' => 'nullable|string',
                'format' => 'nullable|string',
            ]);

            $tournament->update($validated);

            // Load relationships for response
            $tournament->load(['gameType', 'organiser', 'participants']);

            return response()->json([
                'success' => true,
                'message' => 'Tournament updated successfully',
                'data' => $this->formatTournamentResponse($tournament, $request->user())
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating tournament: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update tournament'
            ], 500);
        }
    }

    /**
     * Approve tournament (admin only)
     */
    public function approve(Request $request, Tournament $tournament): JsonResponse
    {
        try {
            // Check if user is admin
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admins can approve tournaments'
                ], 403);
            }

            // Check if tournament is pending approval
            if ($tournament->approval_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tournament is not pending approval'
                ], 400);
            }

            $tournament->update([
                'approval_status' => 'approved',
                'status' => 'open',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            // Load relationships for response
            $tournament->load(['gameType', 'organiser', 'participants', 'approvedBy']);

            return response()->json([
                'success' => true,
                'message' => 'Tournament approved successfully',
                'data' => $this->formatTournamentResponse($tournament, $request->user())
            ]);

        } catch (\Exception $e) {
            Log::error('Error approving tournament: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve tournament'
            ], 500);
        }
    }

    /**
     * Reject tournament (admin only)
     */
    public function reject(Request $request, Tournament $tournament): JsonResponse
    {
        try {
            // Check if user is admin
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admins can reject tournaments'
                ], 403);
            }

            // Check if tournament is pending approval
            if ($tournament->approval_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tournament is not pending approval'
                ], 400);
            }

            $validated = $request->validate([
                'reason' => 'required|string|max:1000',
            ]);

            $tournament->update([
                'approval_status' => 'rejected',
                'status' => 'cancelled',
                'rejection_reason' => $validated['reason'],
            ]);

            // Load relationships for response
            $tournament->load(['gameType', 'organiser', 'participants']);

            return response()->json([
                'success' => true,
                'message' => 'Tournament rejected successfully',
                'data' => $this->formatTournamentResponse($tournament, $request->user())
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error rejecting tournament: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject tournament'
            ], 500);
        }
    }

    /**
     * Format tournament response with user participation data
     */
    private function formatTournamentResponse(Tournament $tournament, User $user): array
    {
        $userParticipation = $tournament->participants()
            ->where('user_id', $user->id)
            ->first();

        return [
            'id' => $tournament->id,
            'name' => $tournament->name,
            'description' => $tournament->description,
            'sport' => [
                'id' => $tournament->gameType->id,
                'name' => $tournament->gameType->name,
                'color' => $tournament->gameType->color,
            ],
            'organiser' => [
                'id' => $tournament->organiser->id,
                'name' => $tournament->organiser->first_name . ' ' . $tournament->organiser->last_name,
                'avatar' => $tournament->organiser->profile_picture,
            ],
            'location' => $tournament->location,
            'address' => $tournament->address,
            'starts_at' => $tournament->starts_at->toDateTimeString(),
            'ends_at' => $tournament->ends_at->toDateTimeString(),
            'registration_deadline' => $tournament->registration_deadline->toDateTimeString(),
            'max_participants' => $tournament->max_participants,
            'current_participants_count' => $tournament->current_participants_count,
            'entry_fee' => $tournament->entry_fee,
            'prize_pool' => $tournament->prize_pool,
            'prize_description' => $tournament->prize_description,
            'skill_level' => $tournament->skill_level,
            'skill_level_label' => $tournament->skill_level_label,
            'status' => $tournament->status,
            'status_color' => $tournament->status_color,
            'is_featured' => $tournament->is_featured,
            'registration_enabled' => $tournament->registration_enabled,
            'waiting_list_enabled' => $tournament->waiting_list_enabled,
            'registration_progress' => $tournament->registration_progress,
            'is_full' => $tournament->is_full,
            'deadline_text' => $tournament->deadline_text,
            'days_until_deadline' => $tournament->days_until_deadline,
            'rules' => $tournament->rules,
            'format' => $tournament->format,
            'approval_status' => $tournament->approval_status,
            'rejection_reason' => $tournament->rejection_reason,
            'approved_by' => $tournament->approvedBy ? [
                'id' => $tournament->approvedBy->id,
                'name' => $tournament->approvedBy->first_name . ' ' . $tournament->approvedBy->last_name,
            ] : null,
            'approved_at' => $tournament->approved_at?->toDateTimeString(),
            'user_participation' => $userParticipation ? [
                'is_registered' => true,
                'is_waiting' => $userParticipation->pivot->is_waiting,
                'registration_date' => $userParticipation->pivot->registration_date,
                'payment_status' => $userParticipation->pivot->payment_status,
                'can_register' => $tournament->registration_enabled && !$tournament->is_full,
                'can_edit' => $user->id === $tournament->organiser_id && $tournament->status === 'draft',
            ] : [
                'is_registered' => false,
                'is_waiting' => false,
                'registration_date' => null,
                'payment_status' => null,
                'can_register' => $tournament->registration_enabled && !$tournament->is_full,
                'can_edit' => $user->id === $tournament->organiser_id && $tournament->status === 'draft',
            ],
            'participants' => $tournament->participants->map(function ($participant) {
                return [
                    'id' => $participant->id,
                    'name' => $participant->first_name . ' ' . $participant->last_name,
                    'is_waiting' => $participant->pivot->is_waiting,
                    'registration_date' => $participant->pivot->registration_date,
                ];
            }),
            'created_at' => $tournament->created_at->toDateTimeString(),
            'updated_at' => $tournament->updated_at->toDateTimeString(),
        ];
    }
}
