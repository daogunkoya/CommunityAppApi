<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Models\Conversation;
use App\Models\GameEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class JoinGameEventAction
{
    /**
     * @return array{success: bool, message: string, data?: array}
     */
    public function execute(GameEvent $event, User $user): array
    {
        return DB::transaction(function () use ($event, $user) {
            // Check if user is already participating
            $participant = $event->participants()->where('user_id', $user->id)->first();

            if (!$participant) {
                // Check if event is full
                if ($event->max_participants && $event->participants()->count() >= $event->max_participants) {
                    if (!$event->waiting_list_enabled) {
                        return [
                            'success' => false,
                            'message' => 'Event is full and waiting list is disabled'
                        ];
                    }
                    // Add to waiting list
                    $event->participants()->attach($user->id, ['is_waiting' => true]);
                    $this->addToCommunityChat($event, $user);

                    return [
                        'success' => true,
                        'message' => 'Added to waiting list'
                    ];
                }

                $event->participants()->attach($user->id, ['is_waiting' => false]);
                $this->addToCommunityChat($event, $user);
            } else {
                // User is already participating
                if ($participant->pivot->is_waiting) {
                    return [
                        'success' => true,
                        'message' => 'You are on the waiting list'
                    ];
                }
                // If confirmed participant, proceed to return conversation
            }

            // Create or get game chat between organiser and joiner
            $conversation = $this->getOrCreateDirectChat($event, $user);

            // Notify Organiser that someone joined their game
            $organiser = User::find($event->organiser_id);
            if ($organiser && $organiser->id !== $user->id) {
                $organiser->notify(new \App\Notifications\GameInviteNotification($event, $user));
            }

            return [
                'success' => true,
                'message' => 'Successfully joined the event',
                'data' => [
                    'conversation_id' => $conversation ? $conversation->id : null,
                ],
            ];
        });
    }

    protected function addToCommunityChat(GameEvent $event, User $user): void
    {
        if ($event->community_id) {
            $communityConversation = Conversation::where('type', 'community')
                ->where('context_id', $event->community_id)
                ->first();

            if ($communityConversation && !$communityConversation->participants()->where('users.id', $user->id)->exists()) {
                $communityConversation->participants()->attach($user->id);
            }
        }
    }

    protected function getOrCreateDirectChat(GameEvent $event, User $user): ?Conversation
    {
        $organiserId = $event->organiser_id;

        // processing logic: if user is organiser, return generic success without chat creation
        // (This matches original controller logic edge case)
        if ($user->id === $organiserId) {
            return null;
        }

        $otherUserId = $organiserId;

        $conversation = Conversation::where('type', 'direct')
            ->where('context_id', $event->id)
            ->whereHas('participants', fn($q) => $q->where('users.id', $otherUserId))
            ->whereHas('participants', fn($q) => $q->where('users.id', $user->id))
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'type' => 'direct',
                'name' => null,
                'context_id' => $event->id,
            ]);
            $conversation->participants()->attach([$otherUserId, $user->id]);

            $joinerName = $user->full_name;
            $guidelines = "This game is not confirmed until agreed in chat by both players involved.\n"
                . "Any court fee should be discussed and shared if the creator allows it.\n"
                . "Please communicate early if plans change.";
            $systemContent = $joinerName . ' joined the game!' . "\n\n" . $guidelines;

            $systemMessage = $conversation->messages()->create([
                'user_id' => $user->id,
                'content' => $systemContent,
            ]);
            $conversation->update(['last_message_id' => $systemMessage->id]);
        }

        return $conversation;
    }
}
