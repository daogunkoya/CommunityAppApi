<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\SkillLevel;
use Illuminate\Database\Eloquent\Builder;

class GameEvent extends Model
{
    /** @use HasFactory<\Database\Factories\GameEventFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
    'skill_level' => SkillLevel::class,
];

  public function scopeMatchesUserSkill(Builder $query, User $user): Builder
{
    $interests = $user->gameInterests()
        ->get()
        ->pluck('pivot.skill_level', 'id'); // 'id' is game_type_id

    return $query->where(function ($q) use ($interests) {
        foreach ($interests as $gameTypeId => $userSkillLevel) {
            $level = $userSkillLevel instanceof \App\Enums\SkillLevel ? $userSkillLevel->value : $userSkillLevel;

            $q->orWhere(function ($subQ) use ($gameTypeId, $level) {
                $subQ->where('game_type_id', $gameTypeId)
                     ->where('skill_level', '<=', $level);
            });
        }
    });
}


    public function participants()
    {
        return $this->belongsToMany(User::class, 'game_event_participants')
            ->withPivot('is_waiting');
    }

    public function gameType(): BelongsTo
    {
        return $this->belongsTo(GameType::class);
    }

    public function organiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organiser_id');
    }
}
