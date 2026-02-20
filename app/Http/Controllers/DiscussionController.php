<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Discussion;
use App\Models\Comment;
use App\Models\Like;
use App\Models\User;
use App\Models\TypingIndicator;
use App\Models\GameType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\ContentFilterService;
use Carbon\Carbon;

class DiscussionController extends Controller
{
    protected ContentFilterService $contentFilter;

    public function __construct(ContentFilterService $contentFilter)
    {
        $this->contentFilter = $contentFilter;
    }

    /**
     * Get all discussions with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'topic' => 'nullable|string|max:255',
                'sort' => 'nullable|in:latest,popular,trending',
                'per_page' => 'nullable|integer|min:1|max:50',
                'game_type' => 'nullable|string|max:255',
                'game_event_id' => 'nullable|integer',
                'my_discussions_only' => 'nullable|string|in:true,false,0,1',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
                'filter_by_interests' => 'nullable|string|in:true,false,0,1',
            ]);

            $perPage = $validated['per_page'] ?? 15;
            $user = $request->user();

            // Get blocked user IDs
            $blockedUserIds = $user->blockedUsers()->pluck('blocked_user_id')->toArray();

            $query = Discussion::with(['user', 'gameType', 'comments', 'likes'])
                ->withCount(['comments', 'likes'])
                ->whereNotIn('user_id', $blockedUserIds); // Exclude blocked users

            // Apply search filter
            if (isset($validated['search'])) {
                $query->where(function ($q) use ($validated) {
                    $q->where('title', 'like', '%' . $validated['search'] . '%')
                        ->orWhere('body', 'like', '%' . $validated['search'] . '%');
                });
            }

            // Apply topic filter
            if (isset($validated['topic'])) {
                $query->where(function ($q) use ($validated) {
                    $q->where('title', 'like', '%' . $validated['topic'] . '%')
                        ->orWhere('body', 'like', '%' . $validated['topic'] . '%');
                });
            }

            // Apply user interest filtering (DEFAULT BEHAVIOR)
            $shouldFilterByInterests = !isset($validated['filter_by_interests']) ||
                (isset($validated['filter_by_interests']) &&
                    in_array($validated['filter_by_interests'], ['true', '1'], true));

            if ($shouldFilterByInterests) {
                // Get user's sport interests
                $userInterests = $user->gameInterests()->pluck('game_type_id')->toArray();

                if (!empty($userInterests)) {
                    Log::info('Filtering discussions by user interests (DEFAULT):', [
                        'user_id' => $user->id,
                        'interests' => $userInterests
                    ]);

                    $query->whereIn('game_type_id', $userInterests);
                } else {
                    Log::info('User has no interests set, showing all discussions');
                }
            } else {
                Log::info('User requested to see all discussions (override interest filtering)');
            }

            // Apply manual game type filter (overrides interest filtering)
            if (isset($validated['game_type'])) {
                Log::info('Manual game type filter applied:', ['received' => $validated['game_type']]);
                $query->whereHas('gameType', function ($q) use ($validated) {
                    // Convert frontend values to database format
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
                    Log::info('Game type mapping:', ['frontend' => $validated['game_type'], 'database' => $dbGameType]);
                    $q->where('name', $dbGameType);
                });
            }

            // Apply game event filter - show only discussions for specific game event
            if (isset($validated['game_event_id'])) {
                Log::info('Filtering by game_event_id:', ['game_event_id' => $validated['game_event_id']]);
                $query->where('game_event_id', $validated['game_event_id']);
            }

            // Apply my discussions filter
            if (isset($validated['my_discussions_only'])) {
                $isMyDiscussions = in_array($validated['my_discussions_only'], ['true', '1'], true);
                if ($isMyDiscussions) {
                    $query->where('user_id', $user->id);
                }
            }

            // Apply date range filter
            if (isset($validated['date_from'])) {
                $query->where('created_at', '>=', $validated['date_from']);
            }
            if (isset($validated['date_to'])) {
                $query->where('created_at', '<=', $validated['date_to'] . ' 23:59:59');
            }

            // Apply sorting
            if (isset($validated['sort'])) {
                switch ($validated['sort']) {
                    case 'popular':
                        $query->orderBy('likes_count', 'desc');
                        break;
                    case 'trending':
                        $query->orderBy('comments_count', 'desc');
                        break;
                    case 'latest':
                    default:
                        $query->orderBy('created_at', 'desc');
                        break;
                }
            }

            $discussions = $query->paginate($perPage);

            // Get user interests for response metadata
            $userInterests = $user->gameInterests()->get();
            $interestNames = $userInterests->pluck('name')->toArray();
            $hasInterests = !empty($userInterests);

            return response()->json([
                'success' => true,
                'data' => $discussions->map(function ($discussion) use ($user) {
                    return [
                        'id' => $discussion->id,
                        'title' => $discussion->title,
                        'body' => $discussion->body,
                        'excerpt' => Str::limit($discussion->body, 150),
                        'author' => [
                            'id' => $discussion->user->id,
                            'name' => $discussion->user->full_name,
                            'avatar' => $discussion->user->profile_picture,
                        ],
                        'game_type' => $discussion->gameType ? [
                            'id' => $discussion->gameType->id,
                            'name' => $discussion->gameType->name,
                            'color' => $discussion->gameType->color,
                        ] : null,
                        'stats' => [
                            'likes_count' => $discussion->likes_count,
                            'comments_count' => $discussion->comments_count,
                        ],
                        'user_interaction' => [
                            'is_liked' => $discussion->likes()->where('user_id', $user->id)->exists(),
                            'can_edit' => $discussion->user_id === $user->id,
                            'can_delete' => $discussion->user_id === $user->id,
                        ],
                        'created_at' => $discussion->created_at->format('Y-m-d H:i:s'),
                        'created_at_relative' => $discussion->created_at->diffForHumans(),
                        'updated_at' => $discussion->updated_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'pagination' => [
                    'current_page' => $discussions->currentPage(),
                    'last_page' => $discussions->lastPage(),
                    'per_page' => $discussions->perPage(),
                    'total' => $discussions->total(),
                ],
                'meta' => [
                    'filtered_by_interests' => $shouldFilterByInterests && $hasInterests,
                    'user_interests' => [
                        'names' => $interestNames,
                        'count' => count($interestNames),
                        'has_interests' => $hasInterests,
                    ],
                    'message' => $hasInterests
                        ? "Showing discussions for your interests: " . implode(', ', $interestNames)
                        : "Showing all discussions. Set your sport interests for personalized content.",
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Discussion index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch discussions',
            ], 500);
        }
    }

    /**
     * Create a new discussion
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'body' => 'required|string|max:5000',
                'game_type_id' => 'nullable|exists:game_types,id',
                'game_event_id' => 'nullable|exists:game_events,id',
            ]);

            // Validate content for profanity
            $this->contentFilter->validateContent($validated['title'], $validated['body']);

            DB::beginTransaction();

            $discussion = Discussion::create([
                ...$validated,
                'user_id' => $request->user()->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Discussion created successfully',
                'data' => [
                    'id' => $discussion->id,
                    'title' => $discussion->title,
                    'body' => $discussion->body,
                    'author' => [
                        'id' => $discussion->user->id,
                        'name' => $discussion->user->full_name,
                    ],
                    'created_at' => $discussion->created_at->format('Y-m-d H:i:s'),
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
            Log::error('Discussion store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Failed to create discussion',
            ], 500);
        }
    }

    /**
     * Get a specific discussion with comments
     */
    public function show(Discussion $discussion): JsonResponse
    {
        try {
            $discussion->load(['user', 'gameType', 'comments.user', 'likes']);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $discussion->id,
                    'title' => $discussion->title,
                    'body' => $discussion->body,
                    'author' => [
                        'id' => $discussion->user->id,
                        'name' => $discussion->user->full_name,
                        'avatar' => $discussion->user->profile_picture,
                    ],
                    'game_type' => $discussion->gameType ? [
                        'id' => $discussion->gameType->id,
                        'name' => $discussion->gameType->name,
                        'color' => $discussion->gameType->color,
                    ] : null,
                    'stats' => [
                        'likes_count' => $discussion->likes->count(),
                        'comments_count' => $discussion->comments->count(),
                    ],
                    'comments' => $discussion->comments->map(function ($comment) {
                        return [
                            'id' => $comment->id,
                            'body' => $comment->body,
                            'author' => [
                                'id' => $comment->user->id,
                                'name' => $comment->user->full_name,
                                'avatar' => $comment->user->profile_picture,
                            ],
                            'created_at' => $comment->created_at->format('Y-m-d H:i:s'),
                            'created_at_relative' => $comment->created_at->diffForHumans(),
                        ];
                    }),
                    'created_at' => $discussion->created_at->format('Y-m-d H:i:s'),
                    'created_at_relative' => $discussion->created_at->diffForHumans(),
                    'updated_at' => $discussion->updated_at->format('Y-m-d H:i:s'),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Discussion show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch discussion',
            ], 500);
        }
    }

    /**
     * Update a discussion
     */
    public function update(Request $request, Discussion $discussion): JsonResponse
    {
        try {
            // Check if user is the author
            if ($discussion->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized - Only the author can update this discussion'
                ], 403);
            }

            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'body' => 'sometimes|string|max:5000',
            ]);

            $discussion->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Discussion updated successfully',
                'data' => [
                    'id' => $discussion->id,
                    'title' => $discussion->title,
                    'body' => $discussion->body,
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Discussion update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update discussion',
            ], 500);
        }
    }

    /**
     * Delete a discussion
     */
    public function destroy(Request $request, Discussion $discussion): JsonResponse
    {
        try {
            // Check if user is the author
            if ($discussion->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized - Only the author can delete this discussion'
                ], 403);
            }

            $discussion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Discussion deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Discussion destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete discussion',
            ], 500);
        }
    }

    /**
     * Add a comment to a discussion
     */
    public function storeComment(Request $request, Discussion $discussion): JsonResponse
    {
        try {
            $validated = $request->validate([
                'body' => 'required|string|max:1000',
            ]);

            DB::beginTransaction();

            $comment = Comment::create([
                'body' => $validated['body'],
                'user_id' => $request->user()->id,
                'discussion_id' => $discussion->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully',
                'data' => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'author' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->full_name,
                        'avatar' => $comment->user->profile_picture,
                    ],
                    'created_at' => $comment->created_at->format('Y-m-d H:i:s'),
                    'created_at_relative' => $comment->created_at->diffForHumans(),
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
            Log::error('Comment store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment',
            ], 500);
        }
    }

    /**
     * Get comments for a discussion
     */
    public function showComments(Discussion $discussion): JsonResponse
    {
        try {
            $comments = $discussion->comments()
                ->with('user')
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'body' => $comment->body,
                        'author' => [
                            'id' => $comment->user->id,
                            'name' => $comment->user->full_name,
                            'avatar' => $comment->user->profile_picture,
                        ],
                        'created_at' => $comment->created_at->format('Y-m-d H:i:s'),
                        'created_at_relative' => $comment->created_at->diffForHumans(),
                    ];
                }),
            ]);

        } catch (\Exception $e) {
            Log::error('Comments show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch comments',
            ], 500);
        }
    }

    /**
     * Like a discussion
     */
    public function like(Request $request, Discussion $discussion): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if already liked
            if ($discussion->likes()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Already liked this discussion'
                ], 400);
            }

            $discussion->likes()->create([
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Discussion liked successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Discussion like error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to like discussion',
            ], 500);
        }
    }

    /**
     * Unlike a discussion
     */
    public function unlike(Request $request, Discussion $discussion): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if liked
            if (!$discussion->likes()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not liked this discussion'
                ], 400);
            }

            $discussion->likes()->where('user_id', $user->id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Discussion unliked successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Discussion unlike error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to unlike discussion',
            ], 500);
        }
    }

    /**
     * Get available game types for discussions (user's interests only)
     */
    public function availableGameTypes(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Get user's sport interests
            $userInterests = $user->gameInterests()->get();

            if ($userInterests->isEmpty()) {
                // New users (e.g. just signed up or no interests): return all game types
                // so they can create games, use filters, and set interests from profile
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
     * Get trending topics
     */
    public function trendingTopics(): JsonResponse
    {
        try {
            $topics = Discussion::select('title')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('title')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($topic) {
                    return [
                        'name' => $topic->title,
                        'count' => $topic->count,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $topics,
            ]);

        } catch (\Exception $e) {
            Log::error('Trending topics error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch trending topics',
            ], 500);
        }
    }

    /**
     * Start typing indicator for discussion comments
     */
    public function startTyping(Request $request, Discussion $discussion): JsonResponse
    {
        Log::info('Discussion startTyping method called', [
            'discussion_id' => $discussion->id,
            'discussion_title' => $discussion->title
        ]);

        try {
            $user = $request->user();

            Log::info('Discussion typing start request', [
                'user_id' => $user->id,
                'user_name' => $user->full_name,
                'discussion_id' => $discussion->id,
                'discussion_title' => $discussion->title
            ]);

            // Start typing indicator using discussion ID as context
            TypingIndicator::startTyping($user->id, $discussion->id, 'discussion');

            Log::info('Discussion typing indicator started successfully');

            return response()->json([
                'success' => true,
                'message' => 'Typing indicator started'
            ]);
        } catch (\Exception $e) {
            Log::error('Discussion typing start error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to start typing indicator',
            ], 500);
        }
    }

    /**
     * Stop typing indicator for discussion comments
     */
    public function stopTyping(Request $request, Discussion $discussion): JsonResponse
    {
        try {
            $user = $request->user();

            // Stop typing indicator
            TypingIndicator::stopTyping($user->id, $discussion->id, 'discussion');

            return response()->json([
                'success' => true,
                'message' => 'Typing indicator stopped'
            ]);
        } catch (\Exception $e) {
            Log::error('Discussion typing stop error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to stop typing indicator',
            ], 500);
        }
    }

    /**
     * Get active typing users for discussion comments
     */
    public function getTypingUsers(Request $request, Discussion $discussion): JsonResponse
    {
        Log::info('Discussion getTypingUsers method called', [
            'discussion_id' => $discussion->id,
            'discussion_title' => $discussion->title
        ]);

        try {
            $user = $request->user();

            Log::info('Discussion typing users request', [
                'user_id' => $user->id,
                'discussion_id' => $discussion->id
            ]);

            // Get active typing users (excluding current user)
            $typingUsers = TypingIndicator::getActiveTypingUsers($discussion->id, 'discussion')
                ->filter(function ($typingUser) use ($user) {
                    return $typingUser['user_id'] !== $user->id;
                })
                ->values();

            Log::info('Discussion typing users response', [
                'discussion_id' => $discussion->id,
                'typing_users_count' => $typingUsers->count(),
                'typing_users' => $typingUsers->toArray()
            ]);

            return response()->json([
                'success' => true,
                'data' => $typingUsers
            ]);
        } catch (\Exception $e) {
            Log::error('Discussion typing users error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch typing users',
            ], 500);
        }
    }
}
