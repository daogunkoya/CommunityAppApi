<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\TypingIndicator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ConversationController extends Controller
{
    /**
     * Get all conversations for the authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $conversations = Conversation::whereHas('participants', function ($query) use ($user) {
            $query->where('users.id', $user->id);
        })->with(['participants', 'lastMessage'])->get()->map(function ($conversation) use ($user) {
            return $this->formatConversation($conversation, $user);
        });

        return response()->json([
            'success' => true,
            'data' => $conversations
        ]);
    }

    /**
     * Create a new conversation
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            $data = $request->validate([
                'type' => 'required|in:direct,group,tournament,community',
                'name' => 'nullable|string|max:255',
                'participant_ids' => 'required_if:type,direct,group|array',
                'participant_ids.*' => 'integer|exists:users,id',
                'context_id' => 'required_if:type,tournament,community|integer',
            ]);

            // For direct conversations, check if one already exists
            if ($data['type'] === 'direct' && isset($data['participant_ids']) && count($data['participant_ids']) === 1) {
                $existingConversation = Conversation::whereHas('participants', function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                })->whereHas('participants', function ($query) use ($data) {
                    $query->where('users.id', $data['participant_ids'][0]);
                })->where('type', 'direct')->first();

                if ($existingConversation) {
                    return response()->json([
                        'success' => true,
                        'data' => $this->formatConversation($existingConversation, $user)
                    ]);
                }
            }

            $conversation = Conversation::create([
                'type' => $data['type'],
                'name' => $data['type'] === 'direct' ? null : ($data['name'] ?? 'Group Chat'),
                'context_id' => $data['context_id'] ?? null,
            ]);

            // Add participants
            $participantIds = array_merge([$user->id], $data['participant_ids'] ?? []);
            $conversation->participants()->attach($participantIds);

            return response()->json([
                'success' => true,
                'data' => $this->formatConversation($conversation, $user)
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating conversation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create conversation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get messages for a conversation
     */
    public function getMessages(Request $request, $conversationId)
    {
        $user = $request->user();
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 50);

        $conversation = Conversation::whereHas('participants', function ($query) use ($user) {
            $query->where('users.id', $user->id);
        })->findOrFail($conversationId);

        $messages = $conversation->messages()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Mark messages as read
        $conversation->messages()
            ->where('user_id', '!=', $user->id)
            ->where('read_at', null)
            ->update(['read_at' => now()]);

        $formattedMessages = $messages->getCollection()->map(function ($message) use ($user) {
            return [
                'id' => $message->id,
                'sender' => [
                    'id' => $message->user->id,
                    'name' => $message->user->full_name,
                    'avatar' => $message->user->profile_picture
                ],
                'content' => $message->content,
                'created_at' => $message->created_at->toISOString(),
                'created_at_relative' => $message->created_at->diffForHumans(),
                'is_own' => $message->user_id === $user->id
            ];
        })->reverse()->values();

        return response()->json([
            'success' => true,
            'data' => $formattedMessages,
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total()
            ],
            'conversation' => [
                'id' => $conversation->id,
                'unread_count' => 0 // Since we just marked all messages as read
            ]
        ]);
    }

    /**
     * Get conversation participants
     */
    public function getParticipants(Request $request, $conversationId)
    {
        $user = $request->user();

        $conversation = Conversation::whereHas('participants', function ($query) use ($user) {
            $query->where('users.id', $user->id);
        })->with(['participants'])->findOrFail($conversationId);

        $participants = $conversation->participants->map(function ($participant) {
            return [
                'id' => $participant->id,
                'name' => $participant->full_name,
                'avatar' => $participant->profile_picture,
                'is_online' => $participant->is_online,
                'last_seen_formatted' => $participant->last_seen_formatted,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $participants
        ]);
    }

    /**
     * Mark a conversation as read
     */
    public function markAsRead(Request $request, $conversationId)
    {
        $user = $request->user();

        $conversation = Conversation::whereHas('participants', function ($query) use ($user) {
            $query->where('users.id', $user->id);
        })->findOrFail($conversationId);

        // Mark all unread messages as read
        $updatedCount = $conversation->messages()
            ->where('user_id', '!=', $user->id)
            ->where('read_at', null)
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "Marked {$updatedCount} messages as read",
            'conversation' => [
                'id' => $conversation->id,
                'unread_count' => 0
            ]
        ]);
    }

    /**
     * Send a message to a conversation
     */
    public function sendMessage(Request $request, $conversationId)
    {
        $user = $request->user();
        $data = $request->validate([
            'content' => 'required|string|max:1000'
        ]);

        $conversation = Conversation::whereHas('participants', function ($query) use ($user) {
            $query->where('users.id', $user->id);
        })->findOrFail($conversationId);

        $message = $conversation->messages()->create([
            'user_id' => $user->id,
            'content' => $data['content']
        ]);

        // Update conversation's last message
        $conversation->update(['last_message_id' => $message->id]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $message->id,
                'sender' => [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'avatar' => $user->profile_picture
                ],
                'content' => $message->content,
                'created_at' => $message->created_at->toISOString(),
                'created_at_relative' => $message->created_at->diffForHumans(),
                'is_own' => true
            ]
        ]);
    }

    /**
     * Format a conversation for API response
     */
    private function formatConversation(Conversation $conversation, User $currentUser)
    {
        $otherParticipants = $conversation->participants->where('id', '!=', $currentUser->id);

        if ($conversation->type === 'direct' && $otherParticipants->count() === 1) {
            // Get the user ID directly to avoid Eloquent caching issues
            $otherUserId = $otherParticipants->first()->id;
            $participantInfo = $this->getParticipantInfoById($otherUserId);

            // Debug logging for Sarah's conversation
            if ($otherUserId == 2) {
                Log::info("Sarah conversation formatting - user_id: {$otherUserId}");
                Log::info("Sarah participant info: " . json_encode($participantInfo));
            }

            $result = [
                'id' => $conversation->id,
                'type' => $conversation->type,
                'name' => $participantInfo['name'],
                'avatar' => $participantInfo['avatar'],
                'last_message' => $this->formatLastMessage($conversation->lastMessage),
                'unread_count' => $this->getUnreadCount($conversation, $currentUser),
                'participants_count' => $conversation->participants->count(),
                'participant_online_status' => $participantInfo['online_status'],
                'participant_last_seen' => $participantInfo['last_seen'],
            ];

            // Debug logging for Sarah's final result
            if ($otherUserId == 2) {
                Log::info("Sarah final conversation result: " . json_encode($result));
            }

            return $result;
        }

        return [
            'id' => $conversation->id,
            'type' => $conversation->type,
            'name' => $conversation->name ?: 'Group Chat',
            'avatar' => null,
            'last_message' => $this->formatLastMessage($conversation->lastMessage),
            'unread_count' => $this->getUnreadCount($conversation, $currentUser),
            'participants_count' => $conversation->participants->count(),
            'participant_online_status' => null,
            'participant_last_seen' => null,
        ];
    }

    /**
     * Get participant information including online status by user ID
     */
    private function getParticipantInfoById($userId)
    {
        // Use direct database query to get fresh data
        $freshUser = DB::table('users')->where('id', $userId)->first();

        if (!$freshUser) {
            Log::error("User not found with ID: {$userId}");
            return [
                'name' => 'Unknown User',
                'avatar' => null,
                'online_status' => false,
                'last_seen' => 'Never',
            ];
        }

        $name = $freshUser->first_name . ' ' . $freshUser->last_name;
        $avatar = $freshUser->profile_picture;

        // Calculate online status manually
        $isOnline = (bool) $freshUser->is_online;
        $lastSeenAt = $freshUser->last_seen_at;

        // Calculate last seen formatted manually
        $lastSeenFormatted = $this->formatLastSeen($lastSeenAt);

        // Debug logging for Sarah
        if ($freshUser->id == 2) {
            Log::info("Sarah direct DB by ID - ID: {$freshUser->id}, Online: " . ($isOnline ? 'true' : 'false') . ", Last seen: {$lastSeenFormatted}");
            Log::info("Sarah raw DB by ID - is_online: {$freshUser->is_online}, last_seen_at: {$freshUser->last_seen_at}");
            Log::info("Sarah final return - online_status: " . ($isOnline ? 'true' : 'false') . ", last_seen: {$lastSeenFormatted}");
        }

        return [
            'name' => $name,
            'avatar' => $avatar,
            'online_status' => $isOnline,
            'last_seen' => $lastSeenFormatted,
        ];
    }

    /**
     * Get participant information including online status
     */
    private function getParticipantInfo(User $user)
    {
        return $this->getParticipantInfoById($user->id);
    }

    /**
     * Format last seen time
     */
    private function formatLastSeen($lastSeenAt)
    {

        if (!$lastSeenAt) {
            return 'Never';
        }

        $lastSeenCarbon = Carbon::parse($lastSeenAt);
        $diffMinutes = abs(now()->diffInMinutes($lastSeenCarbon));

        if ($diffMinutes < 1) {
            return 'Just now';
        } elseif ($diffMinutes < 60) {
            return $diffMinutes . 'm ago';
        } elseif ($diffMinutes < 1440) {
            // Round to nearest hour for better display
            $diffHours = round(abs(now()->diffInHours($lastSeenCarbon)));
            return $diffHours . 'h ago';
        } else {
            return $lastSeenCarbon->format('M j, Y');
        }
    }

    /**
     * Format last message
     */
    private function formatLastMessage($lastMessage)
    {
        if (!$lastMessage) {
            return null;
        }

        return [
            'content' => $lastMessage->content,
            'time' => $lastMessage->created_at->diffForHumans()
        ];
    }

    /**
     * Get unread count for a conversation
     */
    private function getUnreadCount(Conversation $conversation, User $currentUser)
    {
        return $conversation->messages()
            ->where('user_id', '!=', $currentUser->id)
            ->where('read_at', null)
            ->count();
    }

    /**
     * Start typing indicator
     */
    public function startTyping(Request $request, $conversationId)
    {
        $user = $request->user();

        // Verify user is part of the conversation
        $conversation = Conversation::whereHas('participants', function ($query) use ($user) {
            $query->where('users.id', $user->id);
        })->findOrFail($conversationId);

        // Start typing indicator
        TypingIndicator::startTyping($user->id, $conversationId, 'conversation');

        return response()->json([
            'success' => true,
            'message' => 'Typing indicator started'
        ]);
    }

    /**
     * Stop typing indicator
     */
    public function stopTyping(Request $request, $conversationId)
    {
        $user = $request->user();

        // Verify user is part of the conversation
        $conversation = Conversation::whereHas('participants', function ($query) use ($user) {
            $query->where('users.id', $user->id);
        })->findOrFail($conversationId);

        // Stop typing indicator
        TypingIndicator::stopTyping($user->id, $conversationId, 'conversation');

        return response()->json([
            'success' => true,
            'message' => 'Typing indicator stopped'
        ]);
    }

    /**
     * Get active typing users for a conversation
     */
    public function getTypingUsers(Request $request, $conversationId)
    {
        $user = $request->user();

        // Verify user is part of the conversation
        $conversation = Conversation::whereHas('participants', function ($query) use ($user) {
            $query->where('users.id', $user->id);
        })->findOrFail($conversationId);

        // Get active typing users (excluding current user)
        $typingUsers = TypingIndicator::getActiveTypingUsers($conversationId, 'conversation')
            ->filter(function ($typingUser) use ($user) {
                return $typingUser['user_id'] !== $user->id;
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $typingUsers
        ]);
    }
}
