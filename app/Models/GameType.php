<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameType extends Model
{
    /** @use HasFactory<\Database\Factories\GameTypeFactory> */
    use HasFactory;

    protected $guarded = [];

    public function usersInterested()
    {
        return $this->belongsToMany(User::class, 'game_user_interest')->withPivot('skill_level');
    }

    public function events()
    {
        return $this->hasMany(GameEvent::class);
    }

    public function organiser()
    {
        return $this->belongsTo(User::class, 'organiser_id');
    }

    public function gameType()
    {
        return $this->belongsTo(GameType::class);
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'game_event_user')->withTimestamps();
    }
}
