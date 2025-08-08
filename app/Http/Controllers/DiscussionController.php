<?php

namespace App\Http\Controllers;

use App\Models\Discussion;
use App\Models\Comment;
use App\Models\Like;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DiscussionController extends Controller
{
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
                'my_discussions_only' => 'nullable|string|in:true,false,0,1',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
            ]);

            $perPage = $validated['per_page'] ?? 15;
            $user = $request->user();

            $query = Discussion::with(['user', 'gameType', 'comments', 'likes'])
                ->withCount(['comments', 'likes']);

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

            // Apply game type filter
            if (isset($validated['game_type'])) {
                Log::info('Game type filter applied:', ['received' => $validated['game_type']]);
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
            ]);

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
                'message' => 'Failed to create discussion',
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
}
