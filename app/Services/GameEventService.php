<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\GameEventRepository;
use App\Models\GameEvent;
    class GameEventService
{

    public function __construct(public GameEventRepository $repository)
    {

    }
    public function listUpcoming(User $user)
    {
        return  $this->repository->upcomingEventsForUser($user);

    
    }


}

