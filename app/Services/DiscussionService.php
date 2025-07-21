<?php

namespace App\Services;

use App\Models\Game;
use App\Repositories\DiscussionRepository;

class DiscussionService
{
    public function __construct(protected DiscussionRepository $repository) {}

    public function fetchDashboardContent()
    {
        return [
            'discussions' => $this->repository->allWithCommentsAndLikes(),
            'upcoming_games' => Game::with('players')->where('date', '>=', now())->get(),
        ];
    }
}
