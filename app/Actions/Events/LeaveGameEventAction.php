<?php

namespace App\Actions\Events;

use App\Models\GameEvent;
use App\Models\User;

class LeaveGameEventAction
{
    /**
     * @return array{success: bool, message: string}
     */
    public function execute(GameEvent $event, User $user): array
    {
        // Check if user is participating
        if (!$event->participants()->where('user_id', $user->id)->exists()) {
            return [
                'success' => false,
                'message' => 'Not participating in this event'
            ];
        }

        $event->participants()->detach($user->id);

        return [
            'success' => true,
            'message' => 'Successfully left the event'
        ];
    }
}
