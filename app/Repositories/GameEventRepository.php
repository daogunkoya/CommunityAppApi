<?php

namespace App\Repositories;

use App\Models\GameEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
class GameEventRepository
{
    public function upcomingEventsForUser(User $user): Collection
    {
        return GameEvent::with(['organiser', 'participants', 'gameType'])
            ->where('start_time', '>=', now())
           //  ->matchesUserSkill($user)
            ->orderBy('start_time')
            ->get();
    }
}

