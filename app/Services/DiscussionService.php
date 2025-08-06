<?php

namespace App\Services;

use App\Models\Game;
use App\Repositories\DiscussionRepository;
use App\Repositories\GameEventRepository;

class DiscussionService
{
    public function __construct(
        protected DiscussionRepository $repository,
        public GameEventRepository $service
        ) {}

    public function fetchDashboardContent()
    {
        return [
            'discussions' => $this->repository->allWithCommentsAndLikes(),
            //'upcoming_games' => Game::with('players')->where('date', '>=', now())->get(),
            'upcoming_games' => $this->service->upcomingEventsForUser(auth()->user()),
        ];
    }
}
