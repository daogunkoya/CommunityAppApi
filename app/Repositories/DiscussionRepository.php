<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Discussion;

class DiscussionRepository
{
   public function allWithCommentsAndLikes()
    {
        return Discussion::with(['user', 'comments.user', 'likes'])->latest()->get();
    }
}
