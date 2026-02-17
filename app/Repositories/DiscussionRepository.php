<?php

namespace App\Repositories;

use App\Models\Discussion;

class DiscussionRepository
{
   public function allWithCommentsAndLikes()
    {
        return Discussion::with(['user', 'comments.user', 'likes'])->latest()->get();
    }
}
